<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['sponsor_id']) || ($_SESSION['role'] ?? '') !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

/* =========================
   PROJECT PATH CONFIG
   =========================
   هذا هو مسار مشروعك على localhost (URL)
   حسب كلامك: /project/graduation_project/...
*/
define('PROJECT_BASE_URL', '/graduation_project'); // لا تحطي / آخرها

/**
 * يرجّع مسار ملفات السيرفر الحقيقي (Filesystem root) للمشروع
 * عادةً DOCUMENT_ROOT = htdocs
 */
function project_root_fs(): string {
    $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    return $docRoot . PROJECT_BASE_URL;
}

/**
 * تنظيف آمن للـ output في HTML attributes
 */
function esc_attr(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/**
 * توحيد أي path جاي من DB إلى شكل "uploads/...."
 * - يقبل: uploads/...
 * - يقبل: /project/graduation_project/uploads/...
 * - يقبل: ../uploads/...
 * - يقبل: /uploads/... (لو صار)
 */
function normalize_upload_rel(?string $dbPath): string {
    $p = trim((string)$dbPath);
    if ($p === '') return '';

    // full URL (rare) => ما رح نعمل file_exists
    if (preg_match('~^https?://~i', $p)) {
        return $p;
    }

    // شيل أي backslashes
    $p = str_replace('\\', '/', $p);

    // إذا فيه PROJECT_BASE_URL داخل النص، قصّه
    $pos = stripos($p, PROJECT_BASE_URL . '/');
    if ($pos !== false) {
        $p = substr($p, $pos + strlen(PROJECT_BASE_URL) + 1);
    }

    // شيل ../ من البداية
    while (str_starts_with($p, '../')) {
        $p = substr($p, 3);
    }

    // إذا بدأ بـ /uploads/... خليه uploads/...
    if (str_starts_with($p, '/uploads/')) {
        $p = ltrim($p, '/');
    }

    // إذا بدأ بـ uploads/ تمام
    return $p;
}

/**
 * يحوّل uploads/... إلى URL مطلق من جذر المشروع
 */
function upload_public_url(string $rel): string {
    $rel = ltrim($rel, '/');
    return PROJECT_BASE_URL . '/' . $rel;
}

/**
 * يحوّل uploads/... إلى مسار filesystem للفحص file_exists
 */
function upload_fs_path(string $rel): string {
    $rel = ltrim($rel, '/');
    return rtrim(project_root_fs(), '/\\') . '/' . $rel;
}

/**
 * الدالة الأهم:
 * - تاخذ path من DB
 * - تعمل normalize
 * - إذا الملف موجود فعليًا => ترجع URL صحيح
 * - إذا مش موجود => ترجع placeholder URL
 */
function img_url_from_db(?string $dbPath, string $placeholderRel): string {
    $rel = normalize_upload_rel($dbPath);

    // full URL (http) => رجعه كما هو
    if ($rel !== '' && preg_match('~^https?://~i', $rel)) {
        return esc_attr($rel);
    }

    // إذا فاضي => placeholder
    if ($rel === '') {
        return esc_attr(upload_public_url($placeholderRel));
    }

    // إذا مش داخل uploads/، اعتبره placeholder (حماية)
    if (!str_starts_with($rel, 'uploads/')) {
        return esc_attr(upload_public_url($placeholderRel));
    }

    // تحقق وجود الملف على السيرفر
    $fs = upload_fs_path($rel);
    if (is_file($fs)) {
        return esc_attr(upload_public_url($rel));
    }

    // غير موجود => placeholder
    return esc_attr(upload_public_url($placeholderRel));
}

/* =========================
   FETCH EVENT
   ========================= */
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

$event = null;

if ($eventId > 0) {
    $sql = "
        SELECT
            e.*,
            c.club_name,
            sp.company_name AS sponsor_name
        FROM event e
        INNER JOIN club c ON e.club_id = c.club_id
        LEFT JOIN sponsor sp ON sp.sponsor_id = e.sponsor_id
        WHERE e.event_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $event = $res->fetch_assoc();
    $stmt->close();
}

function formatWhenFull($startStr, $endStr): string {
    if (!$startStr) return 'Date to be announced';
    $start = new DateTime($startStr);
    if ($endStr) {
        $end = new DateTime($endStr);
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $start->format('l • M d, Y • g:i A') . ' – ' . $end->format('g:i A');
        }
        return $start->format('M d, Y g:i A') . ' – ' . $end->format('M d, Y g:i A');
    }
    return $start->format('l • M d, Y • g:i A');
}

$title = $event ? ($event['event_name'] ?? 'Event Details') : 'Event not found';

// ✅ Banner URL (حل نهائي)
$bannerUrl = $event
    ? img_url_from_db($event['banner_image'] ?? '', 'uploads/events/default_event.jpg')
    : esc_attr(upload_public_url('uploads/events/default_event.jpg'));

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_attr($title); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751;
    --royal:#e5b758;
    --lightBlue:#ffe9a8;
    --gold:#e5b758;
    --paper:#fff7e3;
    --ink:#1e1a16;
    --card:#ffffff;
    --shadow:0 18px 38px rgba(77,54,16,.16);
    --radius:22px;
    --maxw:1100px;
  }
  *{box-sizing:border-box}
  html,body{margin:0;padding:0}
  body{
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:linear-gradient(180deg,#fff9ec,#f7f0dd);
    color:var(--ink);
  }
  .wrap{ max-width:var(--maxw); margin:40px auto 0; padding:0 20px; }
  .content{ max-width:var(--maxw); margin:40px auto 60px; padding:0 20px; }
  .headline{
    margin:10px 0 16px;
    font-weight:800; line-height:1.1;
    font-size:clamp(32px, 4.7vw, 52px);
    color:var(--navy);
  }
  .meta{
    display:flex; flex-wrap:wrap; align-items:center; gap:14px;
    margin-bottom:22px; color:#7c6122; font-weight:700;
  }
  .badge{
    background:var(--gold);
    color:#242751;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:900;
    box-shadow:0 10px 22px rgba(77,54,16,.10);
  }
  .dot{ width:6px;height:6px;border-radius:50%;background:#d9c38e; }
  .hero{
    position:relative; border-radius:var(--radius); overflow:hidden;
    box-shadow:var(--shadow);
    background:#f4d15b;
    aspect-ratio:16 / 9;
  }
  .hero img{ width:100%; height:100%; object-fit:cover; display:block; }
  .credit{
    position:absolute;right:12px;bottom:10px;
    background:rgba(0,0,0,.55); color:#fff; font-size:12px;
    padding:6px 10px; border-radius:999px;
  }
  article{
    margin-top:28px; background:var(--card);
    border-radius:var(--radius); box-shadow:var(--shadow); padding:30px;
  }
  article p{ margin:0 0 18px; line-height:1.75; font-size:18px; }
  article p.lead{ font-size:19px; font-weight:600; }

  .summary{
    display:grid; gap:18px; margin-top:22px;
    grid-template-columns: 1.2fr .8fr;
  }
  @media (max-width: 880px){ .summary{ grid-template-columns:1fr; } }

  .info{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:22px;
  }
  .info-grid{
    display:grid;
    gap:14px;
    grid-template-columns:1fr 1fr;
  }
  @media (max-width:700px){ .info-grid{ grid-template-columns:1fr; } }

  .info-item{
    display:flex;
    gap:12px;
    align-items:flex-start;
    background:#fff5d1;
    padding:14px 16px;
    border-radius:14px;
    border:1px solid rgba(229,183,88,.35);
  }
  .info-item .icon{
    width:28px;height:28px;display:grid;place-items:center;
    border-radius:10px;background:#ffe8a4;font-weight:900;color:var(--navy);
    flex:0 0 28px;
  }
  .info-item b{display:block;font-size:14px;color:#5b4215;margin-bottom:4px;}
  .info-item span{display:block;font-size:15px;color:#302319;}

  .cta{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:16px;
  }
  .btn{
    border:0;border-radius:12px;padding:12px 16px;
    font-weight:900;cursor:pointer;
    box-shadow:0 8px 18px rgba(77,54,16,.18);
    background:#fff3c2;color:#5a4613;
  }
  .btn.primary{background:var(--gold);color:var(--navy);}
  .btn.ghost{background:#fff;border:2px solid #f3d47a;color:#7a5a1a;}

  .side-card{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:22px;
  }
  .side-card h3{margin:0 0 10px;font-size:18px;color:var(--navy);}
  .tagline{ color:#7e6a3d; font-weight:600; }

  .map-wrap{ margin-top:26px; }
  .map{
    border:0;
    width:100%;
    height:320px;
    border-radius:16px;
    box-shadow:var(--shadow);
  }

  footer{ margin-top:0 !important; }
</style>
</head>

<body>

<?php include('header.php'); ?>

<main class="wrap">
  <?php if (!$event): ?>
    <h1 class="headline">Event not found</h1>
    <p style="font-size:18px;color:#7e6a3d;">This event does not exist or was removed.</p>
  <?php else: ?>

    <?php
      $categoryShow = trim((string)($event['category'] ?? '')) === '' ? '—' : $event['category'];
      $sponsorShow  = trim((string)($event['sponsor_name'] ?? '')) === '' ? 'No sponsor listed' : $event['sponsor_name'];
    ?>

    <h1 class="headline"><?php echo htmlspecialchars($event['event_name']); ?></h1>

    <div class="meta">
      <span class="badge">Event</span>
      <span class="dot"></span>
      <span>
        Hosted by: <?php echo htmlspecialchars($event['club_name']); ?>
        • Sponsored by <?php echo htmlspecialchars($sponsorShow); ?>
      </span>
    </div>

    <figure class="hero">
      <img src="<?php echo $bannerUrl; ?>" alt="Event banner">
      <figcaption class="credit">Photo: CCH Media</figcaption>
    </figure>

    <section class="summary">
      <div class="info">
        <div class="info-grid">
          <div class="info-item">
            <div class="icon">🗓</div>
            <div>
              <b>When</b>
              <span>
                <?php echo htmlspecialchars(formatWhenFull($event['starting_date'], $event['ending_date'])); ?>
              </span>
            </div>
          </div>

          <div class="info-item">
            <div class="icon">📍</div>
            <div>
              <b>Where</b>
              <span><?php echo htmlspecialchars($event['event_location'] ?: 'Location to be announced'); ?></span>
            </div>
          </div>

          <div class="info-item">
            <div class="icon">🏷</div>
            <div>
              <b>Category</b>
              <span><?php echo htmlspecialchars($categoryShow); ?></span>
            </div>
          </div>

          <div class="info-item">
            <div class="icon">🤝</div>
            <div>
              <b>Sponsored by</b>
              <span><?php echo htmlspecialchars($sponsorShow); ?></span>
            </div>
          </div>
        </div>

        <div class="cta">
          <button class="btn primary" id="addCalBtn">Add to Calendar</button>
          <button class="btn ghost" id="shareBtn">Share</button>
        </div>
      </div>

      <aside class="side-card">
        <h3>Tickets & Notes</h3>
        <p class="tagline">General admission is free. Seats are first-come, first-served.</p>
        <ul style="margin:10px 0 0 18px; line-height:1.7;">
          <li>Please bring your student ID.</li>
          <li>QR check-in available at entrance.</li>
        </ul>
      </aside>
    </section>

    <article class="content">
      <p class="lead">
        <?php echo htmlspecialchars($event['description'] ?: 'Event description will be added soon.'); ?>
      </p>

      <div class="map-wrap">
        <h2 style="color:var(--navy); margin:0 0 12px;">Location</h2>
        <?php
          $mapQuery = $event['event_location'] ?: 'Jordan University';
          $mapSrc   = "https://www.google.com/maps?q=" . urlencode($mapQuery) . "&output=embed";
        ?>
        <iframe
          class="map"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          src="<?php echo esc_attr($mapSrc); ?>">
        </iframe>
      </div>
    </article>

  <?php endif; ?>
</main>

<?php include('footer.php'); ?>

<script>
<?php if ($event): ?>
document.getElementById('shareBtn').addEventListener('click', async () => {
  const shareData = {
    title: '<?php echo addslashes($event['event_name']); ?>',
    text: 'Join me at this CCH event!',
    url: window.location.href
  };
  try{
    if(navigator.share){
      await navigator.share(shareData);
    }else{
      await navigator.clipboard.writeText(shareData.url);
      alert('Link copied to clipboard!');
    }
  }catch(e){}
});

document.getElementById('addCalBtn').addEventListener('click', () => {
  const title = '<?php echo addslashes($event['event_name']); ?>';
  const desc  = '<?php echo addslashes($event['description'] ?? 'CCH event'); ?>';
  const loc   = '<?php echo addslashes($event['event_location'] ?? 'Campus'); ?>';
  const start = '<?php echo $event['starting_date'] ? (new DateTime($event['starting_date']))->format("Ymd\\THis") : ""; ?>';
  const end   = '<?php echo $event['ending_date'] ? (new DateTime($event['ending_date']))->format("Ymd\\THis") : ""; ?>';

  if(!start){
    alert('Start date not set for this event yet.');
    return;
  }

  const ics =
`BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Campus Clubs Hub//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:${start}
${end ? 'DTEND:'+end : ''}
SUMMARY:${title}
DESCRIPTION:${desc}
LOCATION:${loc}
UID:${Date.now()}@cch.local
END:VEVENT
END:VCALENDAR`;

  const blob = new Blob([ics], {type: 'text/calendar;charset=utf-8'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'cch-event.ics';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
});
<?php endif; ?>
</script>

</body>
</html>

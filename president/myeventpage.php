<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$president_id = (int)$_SESSION['student_id'];
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($event_id <= 0) {
    header('Location: index.php');
    exit;
}

/* ✅ ONLY CHANGE: fix image paths without changing DB values */
function img_path($path){
    if (!$path) return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path; // full URL
    if ($path[0] === '/') return $path;                     // absolute path
    return '../' . ltrim($path, '/');                       // make uploads/... work from /president/
}

/* helpers (legacy tags inside description) */
function cch_get_tag($text, $tag) {
    if (!$text) return '';
    $pattern = '/\[' . preg_quote($tag,'/') . '\](.*?)\[\/' . preg_quote($tag,'/') . '\]/s';
    if (preg_match($pattern, $text, $m)) return trim($m[1]);
    return '';
}
function cch_strip_tags($text) {
    if (!$text) return '';
    $text = preg_replace('/\[CCH_DESC\].*?\[\/CCH_DESC\]/s', '', $text);
    $text = preg_replace('/\[CCH_CATEGORY\].*?\[\/CCH_CATEGORY\]/s', '', $text);
    $text = preg_replace('/\[CCH_SPONSOR\].*?\[\/CCH_SPONSOR\]/s', '', $text);
    $text = preg_replace('/\[CCH_NOTES\].*?\[\/CCH_NOTES\]/s', '', $text);
    return trim($text);
}

/* get president club_id */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? AND role='club_president' LIMIT 1");
$stmt->bind_param("i", $president_id);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();

$club_id = isset($pres['club_id']) ? (int)$pres['club_id'] : 1;
if ($club_id <= 1) {
    header('Location: index.php');
    exit;
}

/* load event: must belong to president club
   IMPORTANT: sponsor is from event.sponsor_id (NOT sponsor_club_support)
*/
$stmt = $conn->prepare("
    SELECT
      e.event_id, e.event_name, e.description, e.event_location, e.max_attendees,
      e.starting_date, e.ending_date, e.attendees_count, e.banner_image,
      e.club_id, e.category, e.sponsor_id,
      c.club_name,
      sp.company_name AS sponsor_name
    FROM event e
    JOIN club c ON c.club_id = e.club_id
    LEFT JOIN sponsor sp ON sp.sponsor_id = e.sponsor_id
    WHERE e.event_id=? AND e.club_id=?
    LIMIT 1
");
$stmt->bind_param("ii", $event_id, $club_id);
$stmt->execute();
$res = $stmt->get_result();
$event = $res->fetch_assoc();
$stmt->close();

if (!$event) {
    header('Location: index.php');
    exit;
}

/* description */
$rawDesc  = $event['description'] ?? '';
$descMain = cch_get_tag($rawDesc, 'CCH_DESC');
if ($descMain === '') $descMain = cch_strip_tags($rawDesc);

/* category: prefer column, fallback legacy tag */
$category = trim((string)($event['category'] ?? ''));
if ($category === '') {
    $category = cch_get_tag($rawDesc, 'CCH_CATEGORY');
}

/* sponsor: prefer DB sponsor_name (event.sponsor_id), fallback legacy tag */
$sponsor_name = trim((string)($event['sponsor_name'] ?? ''));
if ($sponsor_name === '') {
    $legacySponsor = cch_get_tag($rawDesc, 'CCH_SPONSOR');
    if ($legacySponsor !== '') $sponsor_name = $legacySponsor;
}

/* notes from tag */
$notes = cch_get_tag($rawDesc, 'CCH_NOTES');

/* ✅ cover image FROM DB ONLY (event.banner_image) */
$coverRaw = $event['banner_image'] ?? '';
$cover = img_path($coverRaw); // if empty => ''

/* date/time formatting */
$startDT = !empty($event['starting_date']) ? new DateTime($event['starting_date']) : null;
$endDT   = !empty($event['ending_date']) ? new DateTime($event['ending_date']) : null;

$whenText = '—';
if ($startDT) {
    $weekday   = $startDT->format('l');
    $dateStr   = $startDT->format('M d, Y');
    $startTime = $startDT->format('g:i A');
    $endTime   = $endDT ? $endDT->format('g:i A') : '';
    $whenText  = $weekday . " • " . $dateStr . " • " . $startTime . ($endTime ? "–".$endTime : "");
}

$isLeader = true;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Event Details</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8; --gold:#e5b758;
    --paper:#eef2f7; --ink:#0e1228; --card:#ffffff;
    --shadow:0 18px 38px rgba(12,22,60,.16); --radius:22px; --maxw:1100px;
  }
  *{box-sizing:border-box} html,body{margin:0;padding:0}
  body{font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:linear-gradient(180deg,#f5f7fb,#eef2f7);color:var(--ink)}
  .wrap{ max-width:var(--maxw); margin:40px auto 0; padding:0 20px; }
  .content{ max-width:var(--maxw); margin:40px auto 60px; padding:0 20px; }
  .headline{ margin:10px 0 16px; font-weight:800; line-height:1.1; font-size:clamp(32px, 4.7vw, 52px); color:var(--navy); display:flex; align-items:center; justify-content:space-between; gap:12px }
  .meta{ display:flex; flex-wrap:wrap; align-items:center; gap:14px; margin-bottom:22px; color:#666c85; font-weight:700; }
  .badge{ background:var(--royal); color:#fff; padding:6px 12px; border-radius:999px; font-size:13px; }
  .dot{ width:6px;height:6px;border-radius:50%;background:#c5c9d7; }
  .edit-btn{
    display:inline-block; padding:10px 16px; border-radius:999px; font-weight:800; font-size:14px; text-decoration:none;
    color:#fff; background:linear-gradient(135deg,#5d7ff2,#3664e9); box-shadow:0 8px 20px rgba(54,100,233,.22)
  }
  .edit-btn:hover{ background:linear-gradient(135deg,#4d70ee,#2958e0); }
  .hero{ position:relative; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow); background:#d0d8ff; aspect-ratio:16 / 9; }
  .hero img{ width:100%; height:100%; object-fit:cover; display:block; }
  .credit{ position:absolute;right:12px;bottom:10px; background:rgba(0,0,0,.55); color:#fff; font-size:12px; padding:6px 10px; border-radius:999px; }
  article{ margin-top:28px; background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:30px; }
  article p{ margin:0 0 18px; line-height:1.75; font-size:18px; } article p.lead{ font-size:19px; font-weight:600; }
  .summary{ display:grid; gap:18px; margin-top:22px; grid-template-columns: 1.2fr .8fr; } @media (max-width: 880px){ .summary{ grid-template-columns:1fr; } }
  .info{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:22px; }
  .info-grid{ display:grid; gap:14px; grid-template-columns:1fr 1fr; } @media (max-width:700px){ .info-grid{ grid-template-columns:1fr; } }
  .info-item{ display:flex; gap:12px; align-items:flex-start; background:#f6f8ff; padding:14px 16px; border-radius:14px; }
  .info-item .icon{ width:28px; height:28px; display:grid; place-items:center; border-radius:10px; background:#e7ecff; font-weight:800; color:var(--royal); flex:0 0 28px; }
  .info-item b{ display:block; font-size:14px; color:#425; margin-bottom:4px; } .info-item span{ display:block; font-size:15px; color:#233; }
  .cta{ display:flex; flex-wrap:wrap; gap:12px; margin-top:16px; }
  .btn{ border:0; border-radius:12px; padding:12px 16px; font-weight:800; cursor:pointer; box-shadow:0 8px 18px rgba(10,23,60,.10); background:#f2f5ff; color:#1a1f36; }
  .btn.primary{ background:var(--royal); color:#fff; } .btn.ghost{ background:#fff; border:2px solid #e7ecff; }
  .side-card{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:22px; }
  .side-card h3{ margin:0 0 10px; font-size:18px; color:var(--navy); }
  .tagline{ color:#596180; font-weight:600; }
  .map-wrap{ margin-top:26px; } .map{ border:0; width:100%; height:320px; border-radius:16px; box-shadow:var(--shadow); }
  footer{ margin-top:0 !important; }
</style>
</head>
<body>

<?php include('header.php'); ?>

<main class="wrap">
  <div class="headline">
    <span><?php echo htmlspecialchars($event['event_name']); ?></span>
    <?php if($isLeader): ?>
      <a class="edit-btn" href="editevent.php?id=<?php echo (int)$event_id; ?>">Edit</a>
    <?php endif; ?>
  </div>

  <div class="meta">
    <span class="badge">Event</span>
    <span class="dot"></span>
    <span>Hosted by: <?php echo htmlspecialchars($event['club_name']); ?> • Jordan</span>
  </div>

  <figure class="hero">
    <?php if (!empty($cover)): ?>
      <img src="<?php echo htmlspecialchars($cover); ?>" alt="Event cover">
    <?php endif; ?>
    <figcaption class="credit">Photo: CCH Media</figcaption>
  </figure>

  <section class="summary">
    <div class="info">
      <div class="info-grid">
        <div class="info-item">
          <div class="icon">🗓</div>
          <div><b>When</b><span id="whenText"><?php echo htmlspecialchars($whenText); ?></span></div>
        </div>
        <div class="info-item">
          <div class="icon">📍</div>
          <div><b>Where</b><span id="whereText"><?php echo htmlspecialchars($event['event_location'] ?: '—'); ?></span></div>
        </div>
        <div class="info-item">
          <div class="icon">🏷</div>
          <div><b>Category</b><span><?php echo htmlspecialchars($category !== '' ? $category : '—'); ?></span></div>
        </div>
        <div class="info-item">
          <div class="icon">🤝</div>
          <div><b>Sponsored by</b><span><?php echo htmlspecialchars($sponsor_name !== '' ? $sponsor_name : 'No sponsor listed'); ?></span></div>
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
      <?php
        $lines = array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string)$notes)));
      ?>
      <?php if (!empty($lines)): ?>
        <ul style="margin:10px 0 0 18px; line-height:1.7;">
          <?php foreach($lines as $ln): ?>
            <li><?php echo htmlspecialchars($ln); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <ul style="margin:10px 0 0 18px; line-height:1.7;">
          <li>Please bring your student ID.</li>
          <li>QR check-in available at entrance.</li>
          <li>Snacks & coffee provided.</li>
        </ul>
      <?php endif; ?>
    </aside>
  </section>

  <article class="content">
    <p class="lead"><?php echo htmlspecialchars($descMain ?: '—'); ?></p>

    <div class="map-wrap">
      <h2 style="color:var(--navy); margin:0 0 12px;">Location</h2>
      <iframe class="map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              src="https://www.google.com/maps?q=<?php echo urlencode($event['event_location'] ?: 'Jordan'); ?>&output=embed"></iframe>
    </div>
  </article>
</main>

<?php include('footer.php'); ?>

<script>
document.getElementById('shareBtn').addEventListener('click', async () => {
  const shareData = { title: document.title, text: 'Join me at this CCH event!', url: window.location.href };
  try{
    if(navigator.share){ await navigator.share(shareData); }
    else { await navigator.clipboard.writeText(shareData.url); alert('Link copied!'); }
  }catch(e){}
});

document.getElementById('addCalBtn').addEventListener('click', () => {
  const title = <?php echo json_encode($event['event_name']); ?>;
  const desc  = <?php echo json_encode($descMain ?: ''); ?>;
  const loc   = <?php echo json_encode($event['event_location'] ?: ''); ?>;

  const start = <?php echo json_encode($startDT ? $startDT->format('Ymd\THis') : ''); ?>;
  const end   = <?php echo json_encode($endDT ? $endDT->format('Ymd\THis') : ''); ?>;

  if(!start){ alert('Event start date is missing.'); return; }

  const ics=`BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//CCH//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:${start}
DTEND:${end || start}
SUMMARY:${title}
DESCRIPTION:${(desc||'').replace(/\n/g,'\\n')}
LOCATION:${loc}
UID:${Date.now()}@cch.local
END:VEVENT
END:VCALENDAR`;

  const blob=new Blob([ics],{type:'text/calendar;charset=utf-8'});
  const url=URL.createObjectURL(blob); const a=document.createElement('a');
  a.href=url; a.download='cch-event.ics'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
});
</script>
</body>
</html>

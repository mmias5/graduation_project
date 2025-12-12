<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || ($_SESSION['role'] ?? '') !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

if (!($conn instanceof mysqli)) {
    die("DB connection error.");
}

/* =========================
   Get club id from URL
   pastevents.php?club_id=3  OR  pastevents.php?id=3
========================= */
$clubId = 0;
if (isset($_GET['club_id'])) $clubId = (int)$_GET['club_id'];
elseif (isset($_GET['id']))  $clubId = (int)$_GET['id'];

if ($clubId <= 0 || $clubId == 1) {
    echo "<script>alert('Invalid club.'); location.href='discoverclubs.php';</script>";
    exit;
}

/* =========================
   Fetch past events (ended before now)
========================= */
$events = [];
$stmt = $conn->prepare("
    SELECT
        e.event_id,
        e.event_name,
        e.description,
        e.event_location,
        e.category,
        e.max_attendees,
        e.starting_date,
        e.ending_date,
        e.attendees_count,
        e.banner_image
    FROM event e
    WHERE e.club_id = ?
      AND e.ending_date IS NOT NULL
      AND e.ending_date < NOW()
    ORDER BY e.ending_date DESC
");
$stmt->bind_param("i", $clubId);
$stmt->execute();
$res = $stmt->get_result();
if ($res) {
    while ($row = $res->fetch_assoc()) $events[] = $row;
}
$stmt->close();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtDT($dt){
    if (!$dt) return '';
    $ts = strtotime($dt);
    if (!$ts) return $dt;
    return date("M d, Y • h:i A", $ts);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Past Events</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<!-- IMPORTANT:
     We keep CSS minimal + scoped so we don't ruin your project styling -->
<style>
  .pe-wrap{
    max-width: 1100px;
    margin: 24px auto 48px;
    padding: 0 18px;
    font-family: "Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  }

  .pe-title{
    margin: 0 0 6px;
    font-weight: 800;
    font-size: 28px;
  }

  .pe-subtitle{
    margin: 0 0 18px;
    color: #6b7280;
    font-weight: 600;
    font-size: 14px;
  }

  .pe-grid{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  @media (max-width: 850px){
    .pe-grid{ grid-template-columns: 1fr; }
  }

  .pe-card{
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 14px 34px rgba(10,23,60,.10);
    display: grid;
    grid-template-columns: 86px 1fr;
    gap: 14px;
    border: 1px solid rgba(0,0,0,.06);
  }

  .pe-thumb{
    width: 86px;
    height: 86px;
    border-radius: 16px;
    overflow: hidden;
    background: #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight: 800;
    color: #111827;
  }
  .pe-thumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .pe-badge{
    display:inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    background: rgba(72,113,219,.12);
    color: #242751;
    margin-bottom: 8px;
  }

  .pe-name{
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 800;
  }

  .pe-desc{
    margin: 0 0 10px;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.35;
    max-height: 40px;
    overflow: hidden;
  }

  .pe-meta{
    display:flex;
    flex-wrap: wrap;
    gap: 10px;
    color: #6b7280;
    font-size: 12px;
    font-weight: 700;
  }
  .pe-meta strong{ color:#111827; }

  .pe-empty{
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 14px 34px rgba(10,23,60,.10);
    border: 1px solid rgba(0,0,0,.06);
    color:#6b7280;
    font-weight:700;
  }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<main class="pe-wrap" role="main">
  <h1 class="pe-title">Past Events</h1>
  <p class="pe-subtitle">All events that already ended for this club.</p>

  <?php if (count($events) === 0): ?>
    <div class="pe-empty">No past events found for this club yet.</div>
  <?php else: ?>
    <div class="pe-grid">
      <?php foreach ($events as $e): ?>
        <?php
          $name   = $e['event_name'] ?? 'Event';
          $cat    = trim((string)($e['category'] ?? 'General')); if ($cat==='') $cat='General';
          $desc   = $e['description'] ?? '';
          $loc    = $e['event_location'] ?? '—';
          $start  = $e['starting_date'] ?? null;
          $end    = $e['ending_date'] ?? null;
          $maxAtt = (int)($e['max_attendees'] ?? 0);
          $att    = (int)($e['attendees_count'] ?? 0);
          $banner = $e['banner_image'] ?? '';
          $init   = strtoupper(substr($name, 0, 2));
        ?>

        <article class="pe-card">
          <div class="pe-thumb">
            <?php if (!empty($banner)): ?>
              <img src="<?php echo h($banner); ?>" alt="<?php echo h($name); ?> banner">
            <?php else: ?>
              <?php echo h($init); ?>
            <?php endif; ?>
          </div>

          <div>
            <span class="pe-badge"><?php echo h($cat); ?></span>
            <h3 class="pe-name"><?php echo h($name); ?></h3>

            <?php if (!empty($desc)): ?>
              <p class="pe-desc"><?php echo h($desc); ?></p>
            <?php endif; ?>

            <div class="pe-meta">
              <span>📍 <strong><?php echo h($loc); ?></strong></span>
              <span>🕒 <strong><?php echo h(fmtDT($start)); ?></strong></span>
              <span>✅ Ended: <strong><?php echo h(fmtDT($end)); ?></strong></span>
              <span>👥 <strong><?php echo $att; ?></strong><?php echo $maxAtt ? " / $maxAtt" : ""; ?> attendees</span>
            </div>
          </div>
        </article>

      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>

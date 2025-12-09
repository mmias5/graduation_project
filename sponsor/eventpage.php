<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

// --------- Get event_id from URL ---------
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($eventId <= 0) {
    die('Invalid event ID.');
}

// --------- Fetch event from DB ---------
$stmt = $conn->prepare("
    SELECT event_id, event_name, description, event_location,
           max_attendees, starting_date, ending_date,
           attendees_count, banner_image
    FROM event
    WHERE event_id = ?
");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    die('Event not found.');
}

// --------- Format dates for display + calendar ---------
date_default_timezone_set('Asia/Amman');

$startDt = new DateTime($event['starting_date']);
$endDt   = new DateTime($event['ending_date']);

// Example: Thursday • Dec 10, 2025 • 4:00 PM–6:00 PM
$whenText = $startDt->format('l • M d, Y • g:i A') . '–' . $endDt->format('g:i A');

// ICS format: 20251210T160000
$icsStart = $startDt->format('Ymd\THis');
$icsEnd   = $endDt->format('Ymd\THis');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — <?php echo htmlspecialchars($event['event_name']); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Sponsor Brand Tokens ===== */
  :root{
    --navy:#242751;        /* deep navy used for text & icons */
    --royal:#e5b758;       /* sponsor gold used as primary accent */
    --lightBlue:#ffe9a8;   /* soft warm highlight */
    --gold:#e5b758;
    --paper:#fff7e3;       /* warm background */
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

  /* MAIN WRAPPER */
  .wrap{ max-width:var(--maxw); margin:40px auto 0; padding:0 20px; }
  .content{ max-width:var(--maxw); margin:40px auto 60px; padding:0 20px; }

  /* ===== HEADLINE ===== */
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
    background:var(--gold); color:#fff; padding:6px 12px;
    border-radius:999px; font-size:13px;
  }
  .dot{ width:6px;height:6px;border-radius:50%;background:#d9c38e; }

  /* ===== HERO IMAGE ===== */
  .hero{
    position:relative; border-radius:var(--radius); overflow:hidden;
    box-shadow:var(--shadow); background:#f4d15b; aspect-ratio:16 / 9;
  }
  .hero img{ width:100%; height:100%; object-fit:cover; display:block; }
  .credit{
    position:absolute;right:12px;bottom:10px;
    background:rgba(0,0,0,.55); color:#fff; font-size:12px;
    padding:6px 10px; border-radius:999px;
  }

  /* ===== ARTICLE ===== */
  article{
    margin-top:28px; background:var(--card);
    border-radius:var(--radius); box-shadow:var(--shadow); padding:30px;
  }
  article p{ margin:0 0 18px; line-height:1.75; font-size:18px; }
  article p.lead{ font-size:19px; font-weight:600; }

  /* ===== EVENT EXTRAS ===== */
  .summary{
    display:grid; gap:18px; margin-top:22px;
    grid-template-columns: 1.2fr .8fr;
  }
  @media (max-width: 880px){ .summary{ grid-template-columns:1fr; } }

  .info{
    background:var(--card); border-radius:var(--radius);
    box-shadow:var(--shadow); padding:22px;
  }
  .info-grid{
    display:grid; gap:14px; grid-template-columns:1fr 1fr;
  }
  @media (max-width:700px){ .info-grid{ grid-template-columns:1fr; } }
  .info-item{
    display:flex; gap:12px; align-items:flex-start;
    background:#fff5d1; padding:14px 16px; border-radius:14px;
  }
  .info-item .icon{
    width:28px; height:28px; display:grid; place-items:center;
    border-radius:10px; background:#ffe8a4;
    font-weight:800; color:var(--navy);
    flex:0 0 28px;
  }
  .info-item b{ display:block; font-size:14px; color:#5b4215; margin-bottom:4px; }
  .info-item span{ display:block; font-size:15px; color:#302319; }

  .cta{
    display:flex; flex-wrap:wrap; gap:12px; margin-top:16px;
  }
  .btn{
    border:0; border-radius:12px; padding:12px 16px; font-weight:800;
    cursor:pointer; box-shadow:0 8px 18px rgba(77,54,16,.18);
    background:#fff3c2; color:#5a4613;
  }
  .btn.primary{ background:var(--gold); color:var(--navy); }
  .btn.ghost{
    background:#fff;
    border:2px solid #f3d47a;
    color:#7a5a1a;
  }

  .side-card{
    background:var(--card); border-radius:var(--radius);
    box-shadow:var(--shadow); padding:22px;
  }
  .side-card h3{ margin:0 0 10px; font-size:18px; color:var(--navy); }
  .tagline{ color:#7e6a3d; font-weight:600; }

  /* Map */
  .map-wrap{ margin-top:26px; }
  .map{
    border:0; width:100%; height:320px;
    border-radius:16px; box-shadow:var(--shadow);
  }

  /* Remove extra space bottom from global footer include */
  footer{ margin-top:0 !important; }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<main class="wrap">

  <h1 class="headline">
    <?php echo htmlspecialchars($event['event_name']); ?>
  </h1>

  <figure class="hero">
    <img src="<?php echo htmlspecialchars($event['banner_image']); ?>"
         alt="<?php echo htmlspecialchars($event['event_name']); ?>">
    <figcaption class="credit">Event Banner</figcaption>
  </figure>

  <!-- SUMMARY: details + side card (tickets / notes) -->
  <section class="summary">
    <div class="info">
      <div class="info-grid">
        <div class="info-item">
          <div class="icon">🗓</div>
          <div>
            <b>When</b>
            <span id="whenText">
              <?php echo htmlspecialchars($whenText); ?>
            </span>
          </div>
        </div>
        <div class="info-item">
          <div class="icon">📍</div>
          <div>
            <b>Where</b>
            <span id="whereText">
              <?php echo htmlspecialchars($event['event_location']); ?>
            </span>
          </div>
        </div>
        <div class="info-item">
          <div class="icon">🏷</div>
          <div>
            <b>Max attendees</b>
            <span>
              <?php echo (int)$event['max_attendees']; ?> seats
              • <?php echo (int)$event['attendees_count']; ?> registered
            </span>
          </div>
        </div>
        <div class="info-item">
          <div class="icon">🤝</div>
          <div>
            <b>Sponsored by</b>
            <span>TechVision Corp</span>
            <!-- لاحقاً تقدر تربطها بتابل sponsor -->
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
      <p class="tagline">
        General admission is free. Seats are first-come, first-served.
      </p>
      <ul style="margin:10px 0 0 18px; line-height:1.7;">
        <li>Please bring your student ID.</li>
        <li>QR check-in available at entrance.</li>
        <li>Snacks & coffee provided.</li>
      </ul>
    </aside>
  </section>

  <!-- DESCRIPTION -->
  <article class="content">
    <p class="lead">
      <?php echo nl2br(htmlspecialchars($event['description'])); ?>
    </p>

    <p>
      This event is part of the UniHive initiative to support active student
      clubs, sponsors, and cross-university collaboration.
    </p>

    <!-- Map -->
    <div class="map-wrap">
      <h2 style="color:var(--navy); margin:0 0 12px;">Location</h2>
      <iframe
        class="map"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps?q=<?php
          echo urlencode($event['event_location'] . ' Jordan');
        ?>&output=embed">
      </iframe>
    </div>

  </article>

</main>

<?php include 'footer.php'; ?>

<script>
// ========= Interactions =========

// Use PHP values inside JS
const eventTitle = <?php echo json_encode($event['event_name']); ?>;
const eventDesc  = <?php echo json_encode($event['description']); ?>;
const eventLoc   = <?php echo json_encode($event['event_location']); ?>;
const icsStart   = <?php echo json_encode($icsStart); ?>;
const icsEnd     = <?php echo json_encode($icsEnd); ?>;

// Share (uses Web Share API when available; falls back to copy)
document.getElementById('shareBtn').addEventListener('click', async () => {
  const shareData = {
    title: eventTitle,
    text: 'Join me at this UniHive event: ' + eventTitle,
    url: window.location.href
  };
  try {
    if (navigator.share) {
      await navigator.share(shareData);
    } else {
      await navigator.clipboard.writeText(shareData.url);
      alert('Link copied to clipboard!');
    }
  } catch(e) {
    console.log(e);
  }
});

// Add to Calendar (.ics generator)
document.getElementById('addCalBtn').addEventListener('click', () => {
  const title = eventTitle;
  const desc  = eventDesc;
  const loc   = eventLoc;
  const start = icsStart;
  const end   = icsEnd;

  const ics =
`BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Campus Clubs Hub//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:${start}
DTEND:${end}
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
  a.download = 'event-<?php echo (int)$event['event_id']; ?>.ics';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
});
</script>

</body>
</html>

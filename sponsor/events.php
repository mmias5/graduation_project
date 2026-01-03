<?php
session_start();

require_once '../config.php';

/* ===== Fetch UPCOMING events only ===== */
$sql = "
   /* ===== Fetch UPCOMING events only (FIXED sponsor + NO duplicates) ===== */
    SELECT
        e.event_id,
        e.event_name,
        e.event_location,
        e.starting_date,
        e.attendees_count,
        c.club_name,

        /* sponsor priority:
           1) event.sponsor_id (if exists)
           2) latest sponsor_club_support for that club (if exists)
        */
        COALESCE(sp_event.company_name, sp_club.company_name) AS sponsor_name

    FROM event e
    INNER JOIN club c
        ON c.club_id = e.club_id

    /* 1) Sponsor directly on event (if your table has sponsor_id) */
    LEFT JOIN sponsor sp_event
        ON sp_event.sponsor_id = e.sponsor_id

    /* 2) Latest sponsor support per club (prevents duplicates) */
    LEFT JOIN (
        SELECT scs1.club_id, scs1.sponsor_id
        FROM sponsor_club_support scs1
        INNER JOIN (
            SELECT club_id, MAX(start_date) AS max_start
            FROM sponsor_club_support
            GROUP BY club_id
        ) x
          ON x.club_id = scs1.club_id
         AND (
              (scs1.start_date = x.max_start)
              OR (scs1.start_date IS NULL AND x.max_start IS NULL)
         )
    ) scs_latest
        ON scs_latest.club_id = c.club_id

    LEFT JOIN sponsor sp_club
        ON sp_club.sponsor_id = scs_latest.sponsor_id

    WHERE e.starting_date >= NOW()
    ORDER BY e.starting_date ASC
";

$res = $conn->query($sql);

$events = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
}

/* ===== Helpers ===== */
function dateParts(?string $dt){
    if (!$dt) return ['--','---',''];
    $d = new DateTime($dt);
    return [$d->format('d'), strtoupper($d->format('M')), $d->format('D')];
}
function timePart(?string $dt){
    if (!$dt) return '';
    return (new DateTime($dt))->format('g:i A');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Upcoming Events</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --royal:#4871db;
  --gold:#e5b758;
  --lightGold:#f4df6d;
  --paper:#EEF2F7;
  --card:#ffffff;
  --ink:#0e1228;
  --muted:#6b7280;
  --shadow:0 14px 34px rgba(10,23,60,.12);
  --radius:18px;
}

body{
  margin:0;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 700px at -10% 40%, rgba(168,186,240,.3), transparent 60%),
    radial-gradient(900px 600px at 110% 60%, rgba(72,113,219,.22), transparent 60%),
    var(--paper);
}

.wrapper{
  max-width:1100px;
  margin:32px auto 48px;
  padding:0 18px;
}

.page-title{
  font-size:30px;
  font-weight:800;
  color:var(--navy);
  margin:10px 0 6px;
}

.page-title::after{
  content:"";
  display:block;
  width:170px;
  height:6px;
  border-radius:999px;
  margin-top:10px;
  background:linear-gradient(90deg,var(--gold),var(--lightGold));
}

.subtle{
  color:var(--muted);
  margin:8px 0 18px;
  font-size:15px;
}

/* ===== Search Bar (ADDED) ===== */
.search-wrap{
  margin-bottom:22px;
}
.search-input{
  width:100%;
  padding:14px 18px;
  border-radius:999px;
  border:2px solid #dde3f0;
  font-size:15px;
  font-weight:600;
  outline:none;
}
.search-input:focus{
  border-color:var(--gold);
}

/* ===== Grid ===== */
.grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:18px;
}
@media (max-width:800px){
  .grid{ grid-template-columns:1fr; }
}

.card{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:18px;
  display:grid;
  grid-template-columns:90px 1fr;
  gap:16px;
  cursor:pointer;
  transition:.12s;
  border:2px solid transparent;
}
.card:hover{
  transform:translateY(-2px);
  box-shadow:0 18px 38px rgba(12,22,60,.16);
  border-color:var(--gold);
}

.date{
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  background:#FBF5D6;
  border-radius:14px;
  padding:12px 10px;
  text-align:center;
  font-weight:800;
  min-height:90px;
  color:var(--navy);
  border:2px solid var(--gold);
}
.date .day{ font-size:28px; }
.date .mon{ font-size:12px; margin-top:2px; }
.date .sep{ font-size:11px; color:#6b7280; margin-top:6px; }

.topline{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
}
.badge{
  background:#e8f5ff;
  color:#135f9b;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
}
.chip.sponsor{
  background:#fffdf3;
  color:#8a5b00;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  border:1px solid #ffecb5;
}
.title{
  margin:8px 0 4px;
  font-weight:800;
  font-size:18px;
}
.mini{
  color:var(--muted);
  font-size:13px;
  display:flex;
  gap:14px;
}
.footer{
  margin-top:8px;
  font-size:13px;
  color:var(--muted);
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="wrapper">
  <h1 class="page-title">Upcoming Events</h1>
  <p class="subtle">Discover what’s happening next across UniHive clubs.</p>

  <!-- 🔍 Search Bar -->
  <div class="search-wrap">
    <input
      type="text"
      id="eventSearch"
      class="search-input"
      placeholder="Search events, clubs, sponsors..."
      aria-label="Search events"
    >
  </div>

  <div class="grid" id="eventsGrid">
    <?php if (empty($events)): ?>
      <p style="grid-column:1/-1;color:var(--muted);font-size:14px;">
        No upcoming events at the moment.
      </p>
    <?php else: ?>
      <?php foreach ($events as $ev):
        [$day,$mon,$dow] = dateParts($ev['starting_date']);
        $time = timePart($ev['starting_date']);
        $sponsor = $ev['sponsor_name'] ?: 'TBD';
        $url = 'eventpage.php?event_id='.(int)$ev['event_id'];
      ?>
      <article
        class="card"
        data-search="<?php
          echo strtolower(
            $ev['club_name'].' '.$ev['event_name'].' '.$ev['event_location'].' '.$sponsor
          );
        ?>"
        data-href="<?php echo htmlspecialchars($url); ?>">
        <div class="date">
          <div class="day"><?php echo $day; ?></div>
          <div class="mon"><?php echo $mon; ?></div>
          <div class="sep"><?php echo $dow; ?></div>
        </div>
        <div>
          <div class="topline">
            <span class="badge">Upcoming</span>
            <span class="chip sponsor">Sponsor: <?php echo htmlspecialchars($sponsor); ?></span>
          </div>
          <div class="title">
            <?php echo htmlspecialchars($ev['club_name'].' — '.$ev['event_name']); ?>
          </div>
          <div class="mini">
            <?php if (!empty($ev['event_location'])): ?>
              <span>📍 <?php echo htmlspecialchars($ev['event_location']); ?></span>
            <?php endif; ?>
          </div>
          <div class="footer">
            <?php if ($time): ?>
              <span>🕒 <?php echo $time; ?></span>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
/* Card navigation */
document.addEventListener('click',e=>{
  const c=e.target.closest('.card[data-href]');
  if(c) location.href=c.dataset.href;
});

/* Search logic */
document.getElementById('eventSearch').addEventListener('input',function(){
  const q=this.value.toLowerCase();
  document.querySelectorAll('.card').forEach(card=>{
    card.style.display = card.dataset.search.includes(q) ? '' : 'none';
  });
});
</script>

</body>
</html>

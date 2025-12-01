<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}
// event_details.php
// session_start();
// $isLeader = isset($_SESSION['role']) && $_SESSION['role']==='leader';
$event_id = $_GET['id'] ?? 1;
$isLeader = true; // demo: show button. Switch to your real check.
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
    <span>CCH Tech & Innovation Meetup — Fall 2025</span>
    <?php if($isLeader): ?>
      <a class="edit-btn" href="editevent.php">Edit</a>
    <?php endif; ?>
  </div>

  <div class="meta">
    <span class="badge">Event</span>
    <span class="dot"></span>
    <span>Hosted by: Campus Clubs Hub • Jordan</span>
  </div>

  <figure class="hero">
    <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?q=80&w=1600&auto=format&fit=crop"
         alt="Students attending a technology meetup on campus">
    <figcaption class="credit">Photo: CCH Media</figcaption>
  </figure>

  <section class="summary">
    <div class="info">
      <div class="info-grid">
        <div class="info-item">
          <div class="icon">🗓</div>
          <div><b>When</b><span id="whenText">Thursday • Nov 20, 2025 • 4:00–7:30 PM</span></div>
        </div>
        <div class="info-item">
          <div class="icon">📍</div>
          <div><b>Where</b><span id="whereText">Amman, JU Main Campus — Innovation Hall</span></div>
        </div>
        <div class="info-item">
          <div class="icon">🏷</div>
          <div><b>Category</b><span>Technology • Workshops • Networking</span></div>
        </div>
        <div class="info-item">
  <div class="icon">🤝</div>
  <div>
    <b>Sponsored by</b>
    <span>TechVision Corp</span>
    <!-- Example: you can make it dynamic later -->
    <!-- <span><?php echo htmlspecialchars($sponsor_name ?? 'No sponsor listed'); ?></span> -->
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
        <li>Snacks & coffee provided.</li>
      </ul>
    </aside>
  </section>

  <article class="content">
    <p class="lead">A hands-on evening to explore analytics, club growth tactics, and tech demos built on Campus Clubs Hub.</p>
    <p>The meetup features practical mini-workshops on event analytics, loyalty points, and collaboration. Learn how to interpret engagement peaks, configure ranking signals, and package sponsor-ready highlight summaries after each event.</p>

    <div class="map-wrap">
      <h2 style="color:var(--navy); margin:0 0 12px;">Location</h2>
      <iframe class="map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              src="https://www.google.com/maps?q=Jordan%20University%20Innovation%20Hall&output=embed"></iframe>
    </div>
  </article>
</main>

<?php include('footer.php'); ?>

<script>
// Share
document.getElementById('shareBtn').addEventListener('click', async () => {
  const shareData = { title: document.title, text: 'Join me at this CCH event!', url: window.location.href };
  try{ if(navigator.share){ await navigator.share(shareData); } else { await navigator.clipboard.writeText(shareData.url); alert('Link copied!'); } }
  catch(e){ console.log(e); }
});

// Add to Calendar (.ics)
document.getElementById('addCalBtn').addEventListener('click', () => {
  const title='CCH Tech & Innovation Meetup — Fall 2025';
  const desc='Hands-on workshops, panels, and networking across universities. Powered by CCH.';
  const loc='Amman, JU Main Campus — Innovation Hall';
  const start='20251120T160000'; const end='20251120T193000';
  const ics=`BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//CCH//EN
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
  const blob=new Blob([ics],{type:'text/calendar;charset=utf-8'});
  const url=URL.createObjectURL(blob); const a=document.createElement('a');
  a.href=url; a.download='cch-event.ics'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
});
</script>
</body>
</html>

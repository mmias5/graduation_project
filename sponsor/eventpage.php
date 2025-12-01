
<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Event Details</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Brand Tokens ===== */
  :root{
    --navy:#242751;
    --royal:#4871db;
    --gold:#e5b758;
    --lightGold:#f4df6d;
    --paper:#eef2f7;
    --ink:#0e1228;
    --card:#ffffff;
    --muted:#6b7280;
    --shadow:0 18px 38px rgba(12,22,60,.16);
    --radius:22px;
    --maxw:1100px;
  }

  *{box-sizing:border-box}
  html,body{
    margin:0;
    padding:0;
    height:100%;
  }

  body{
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:
      radial-gradient(1200px 700px at -10% 40%, rgba(168,186,240,.30), transparent 60%),
      radial-gradient(900px 600px at 110% 60%, rgba(72,113,219,.22), transparent 60%),
      var(--paper);
    background-repeat:no-repeat;
    color:var(--ink);
  }

  /* MAIN WRAPPER (shared for everything) */
  .event-page{
    max-width:var(--maxw);
    margin:32px auto 60px;
    padding:0 20px;
  }

  /* ===== HEADLINE ===== */
  .headline{
    margin:10px 0 10px;
    font-weight:800;
    line-height:1.1;
    font-size:clamp(32px, 4.7vw, 52px);
    color:var(--navy);
  }
  .headline::after{
    content:"";
    display:block;
    width:190px;
    height:6px;
    border-radius:999px;
    margin-top:12px;
    background:linear-gradient(90deg,var(--gold),var(--lightGold));
  }

  .meta{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:14px;
    margin:16px 0 26px;
    color:var(--muted);
    font-weight:700;
  }
  .badge{
    background:var(--gold);
    color:#1f2933;
    padding:6px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:800;
  }
  .dot{
    width:6px;
    height:6px;
    border-radius:50%;
    background:#c5c9d7;
  }

  /* ===== HERO IMAGE ===== */
  .hero{
    position:relative;
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:var(--shadow);
    background:#d0d8ff;
    aspect-ratio:16 / 9;
  }
  .hero img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }
  .hero::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.15),rgba(0,0,0,.05));
    pointer-events:none;
  }
  .credit{
    position:absolute;
    right:12px;
    bottom:10px;
    background:rgba(0,0,0,.60);
    color:#fff;
    font-size:12px;
    padding:6px 10px;
    border-radius:999px;
    z-index:2;
  }

  /* ===== SUMMARY: INFO + SIDE CARD ===== */
  .summary{
    display:grid;
    gap:18px;
    margin-top:26px;
    grid-template-columns:1.2fr .8fr;
  }
  @media (max-width:880px){
    .summary{ grid-template-columns:1fr; }
  }

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
  @media (max-width:700px){
    .info-grid{ grid-template-columns:1fr; }
  }

  .info-item{
    display:flex;
    gap:12px;
    align-items:flex-start;
    background:#F6F7FE;
    padding:14px 16px;
    border-radius:16px;
  }
  .info-item .icon{
    width:30px;
    height:30px;
    display:grid;
    place-items:center;
    border-radius:10px;
    background:#FFF9E1;
    font-weight:800;
    color:var(--navy);
    flex:0 0 30px;
    font-size:16px;
  }
  .info-item b{
    display:block;
    font-size:14px;
    color:var(--navy);
    margin-bottom:4px;
  }
  .info-item span{
    display:block;
    font-size:15px;
    color:#273047;
  }

  .cta{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:18px;
  }
  .btn{
    border:0;
    border-radius:999px;
    padding:11px 18px;
    font-weight:800;
    cursor:pointer;
    box-shadow:0 10px 22px rgba(10,23,60,.16);
    font-size:14px;
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .btn.primary{
    background:var(--navy);
    color:#fff;
  }
  .btn.primary:hover{
    background:#1c2045;
  }
  .btn.ghost{
    background:#ffffff;
    color:var(--navy);
    border:2px solid #e5e7f3;
    box-shadow:0 8px 18px rgba(10,23,60,.08);
  }

  .side-card{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:22px;
  }
  .side-card h3{
    margin:0 0 10px;
    font-size:18px;
    color:var(--navy);
    font-weight:800;
  }
  .tagline{
    color:var(--muted);
    font-weight:600;
    margin:0 0 6px;
  }
  .side-card ul{
    margin:10px 0 0 18px;
    padding:0;
    font-size:14px;
    color:#374151;
    line-height:1.7;
  }
  .side-card li::marker{
    color:var(--gold);
  }

  /* ===== DESCRIPTION + MAP CARD ===== */
  .event-article{
    margin-top:28px;
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:30px;
  }
  .event-article p{
    margin:0 0 18px;
    line-height:1.75;
    font-size:16px;
    color:#2d3345;
  }
  .event-article p.lead{
    font-size:17px;
    font-weight:600;
  }

  .section-title{
    color:var(--navy);
    margin:8px 0 12px;
    font-size:18px;
    font-weight:800;
  }

  .map-wrap{
    margin-top:10px;
  }
  .map{
    border:0;
    width:100%;
    height:320px;
    border-radius:16px;
    box-shadow:var(--shadow);
  }

  /* Kill any global footer margin so no weird gap below */
  footer{ margin:0 !important; }
</style>
</head>

<body>

<?php include('header.php'); ?>

<main class="event-page">

  <h1 class="headline">CCH Tech & Innovation Meetup — Fall 2025</h1>

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

  <!-- SUMMARY: details + side card (tickets / notes) -->
  <section class="summary">
    <div class="info">
      <div class="info-grid">
        <div class="info-item">
          <div class="icon">🗓</div>
          <div>
            <b>When</b>
            <span id="whenText">Thursday • Nov 20, 2025 • 4:00–7:30 PM</span>
          </div>
        </div>
        <div class="info-item">
          <div class="icon">📍</div>
          <div>
            <b>Where</b>
            <span id="whereText">Amman, JU Main Campus — Innovation Hall</span>
          </div>
        </div>
        <div class="info-item">
          <div class="icon">🏷</div>
          <div>
            <b>Category</b>
            <span>Technology • Workshops • Networking</span>
          </div>
        </div>
        <div class="info-item">
          <div class="icon">🤝</div>
          <div>
            <b>Sponsored by</b>
            <span>TechVision Corp</span>
            <!-- Example for dynamic sponsor: -->
            <!-- <span><?php echo htmlspecialchars($sponsor_name ?? 'No sponsor listed'); ?></span> -->
          </div>
        </div>
      </div>

      <div class="cta">
        <button class="btn primary" id="addCalBtn">Add to Calendar</button>
        <button class="btn ghost" id="shareBtn">Share Event</button>
      </div>
    </div>

    <aside class="side-card">
      <h3>Tickets &amp; Notes</h3>
      <p class="tagline">General admission is free. Seats are first-come, first-served.</p>
      <ul>
        <li>Please bring your student ID.</li>
        <li>QR check-in available at entrance.</li>
        <li>Snacks &amp; coffee provided.</li>
      </ul>
    </aside>
  </section>

  <!-- DESCRIPTION + MAP (same wrapper = perfect alignment) -->
  <section class="event-article">
    <p class="lead">
      A hands-on evening to explore analytics, club growth tactics, and tech demos built on Campus Clubs Hub.
      Meet student leaders, exchange ideas, and discover what’s launching next.
    </p>

    <p>
      The meetup features practical mini-workshops on event analytics, loyalty points, and cross-university
      collaboration. You’ll learn how to interpret engagement peaks, configure ranking signals, and package
      sponsor-ready highlight summaries after each event.
    </p>

    <div class="map-wrap">
      <h2 class="section-title">Location</h2>
      <iframe
        class="map"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps?q=Jordan%20University%20Innovation%20Hall&output=embed">
      </iframe>
    </div>
  </section>

</main>

<?php include('footer.php'); ?>

<script>
/* ========= Interactions ========= */

// Share (Web Share API if available, otherwise copy link)
document.getElementById('shareBtn').addEventListener('click', async () => {
  const shareData = {
    title: 'CCH Tech & Innovation Meetup — Fall 2025',
    text: 'Join me at the CCH Tech & Innovation Meetup!',
    url: window.location.href
  };
  try{
    if(navigator.share){
      await navigator.share(shareData);
    }else{
      await navigator.clipboard.writeText(shareData.url);
      alert('Link copied to clipboard!');
    }
  }catch(e){ console.log(e); }
});

// Add to Calendar (.ics generator)
document.getElementById('addCalBtn').addEventListener('click', () => {
  const title = 'CCH Tech & Innovation Meetup — Fall 2025';
  const desc  = 'Hands-on workshops, panels, and networking across universities. Powered by CCH.';
  const loc   = 'Amman, JU Main Campus — Innovation Hall';
  const start = '20251120T160000';
  const end   = '20251120T193000';

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

  const blob = new Blob([ics], {type:'text/calendar;charset=utf-8'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'cch-event.ics';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
});
</script>

</body>
</html>

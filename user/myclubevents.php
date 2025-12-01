<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    // لو بدك تخلي الـ president يدخل على صفحة مختلفة
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_president') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — My Club Events</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Brand Tokens (global) ===== */
  :root{
    --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8;
    --gold:#e5b758; --sun:#f4df6d; --coral:#ff5e5e;
    --paper:#e9ecef; --ink:#0e1228; --card:#fff;
    --shadow:0 10px 30px rgba(0,0,0,.16);
  }

  body{
    margin:0;
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--ink);
    background:linear-gradient(180deg,#f7f9ff 0%, var(--paper) 100%);
  }

  /* ===== Events page ===== */
  .wrapper{max-width:1100px;margin:20px auto 40px;padding:0 18px}
  .page-title{font-size:30px;font-weight:800;color:var(--navy);margin:10px 0 4px}
  .subtle{color:#6b7280;margin:0 0 15px;font-size:15px}
  .section{margin:15px 0}
  .section h2{font-size:20px;margin:0 0 10px;color:var(--navy);font-weight:800}

  .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
  @media (max-width:800px){ .grid{grid-template-columns:1fr} }

  .card{
    background:var(--card);border-radius:16px;box-shadow:0 14px 34px rgba(10,23,60,.12);
    padding:18px;display:grid;grid-template-columns:90px 1fr;gap:16px;
    cursor:pointer; transition:transform .12s ease, box-shadow .12s ease;
  }
  .card:hover{ transform:translateY(-2px); box-shadow:0 18px 38px rgba(12,22,60,.16); }
  .card:focus{ outline:3px solid var(--royal); outline-offset:3px; }

  .date{
    display:flex;flex-direction:column;justify-content:center;align-items:center;
    background:#f2f5ff;border-radius:14px;padding:12px 10px;text-align:center;
    font-weight:800;min-height:90px;color:var(--navy);
  }
  .date .day{font-size:28px}
  .date .mon{font-size:12px;margin-top:2px}
  .date .sep{font-size:11px;color:#6b7280;margin-top:6px}

  .topline{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .badge{background:#eaf6ee;color:#1f8f4e;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:800}
  .chip.sponsor{background:#fff7e6;color:#8a5b00;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:700;border:1px solid #ffecb5}

  .title{margin:8px 0 4px;font-weight:800;font-size:18px;color:var(--ink)}
  .mini{color:#6b7280;font-size:13px;display:flex;gap:14px;flex-wrap:wrap}
  .footer{margin-top:8px;font-size:13px;color:#6b7280;display:flex;align-items:center}

  .state.completed{ background:#ecfdf3; color:#127a39; padding:6px 10px;border-radius:12px;font-size:12px;font-weight:800}
  .stars{position:relative;display:inline-block;font-size:16px;letter-spacing:2px;--rating:4.5}
  .stars::before{content:"★★★★★";color:#e5e7eb}
  .stars::after{content:"★★★★★";position:absolute;left:0;top:0;width:calc(var(--rating)/5*100%);overflow:hidden;color:#f5c542;white-space:nowrap}
  .review{display:flex;align-items:center;gap:8px;font-weight:800;color:#111827}
  .sepbar{height:1px;background:#e5e7eb;margin:14px 0}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<!-- ===== Events Content ===== -->
<div class="wrapper">
  <h1 class="page-title">My Club Events</h1>
  <p class="subtle">Discover upcoming club activities and revisit completed ones.</p>

  <section class="section">
    <h2>Upcoming Events</h2>
    <div class="grid">

      <!-- ===== EVENT 1 ===== -->
      <article
        class="card"
        data-href="eventpage.php"
        role="link"
        tabindex="0"
        aria-label="Open event: Club B — Hack Night">
        <div class="date"><div class="day">10</div><div class="mon">SEP</div><div class="sep">Tue</div></div>
        <div>
          <div class="topline"><span class="badge">+30 pt</span><span class="chip sponsor">Sponsor: TechCorp</span></div>
          <div class="title">Club B — Hack Night</div>
          <div class="mini"><span>📍 Location</span></div>
          <div class="footer"><span class="mini">🕒 6:00 PM</span></div>
        </div>
      </article>

      <!-- ===== EVENT 2 ===== -->
      <article
        class="card"
        data-href="eventpage.php"
        role="link"
        tabindex="0"
        aria-label="Open event: Club B — Finance 101">
        <div class="date"><div class="day">15</div><div class="mon">SEP</div><div class="sep">Sun</div></div>
        <div>
          <div class="topline"><span class="badge">+30 pt</span><span class="chip sponsor">Sponsor: BlueBank</span></div>
          <div class="title">Club B — Finance 101</div>
          <div class="mini"><span>📍 Main Hall</span></div>
          <div class="footer"><span class="mini">🕒 4:30 PM</span></div>
        </div>
      </article>

    </div>
  </section>

  <div class="sepbar"></div>

  <!-- ===== PAST EVENTS ===== -->
  <section class="section">
    <h2>Past Events</h2>
    <div class="grid">

      <article
        class="card"
        data-href="eventpage.php"
        role="link"
        tabindex="0"
        aria-label="Open past event: Club D — Bake-Off Charity">
        <div class="date"><div class="day">06</div><div class="mon">SEP</div><div class="sep">Fri</div></div>
        <div>
          <div class="topline"><span class="state completed">Completed</span><span class="chip sponsor">Sponsor: StarFoods</span></div>
          <div class="title">Club D — Bake-Off Charity</div>
          <div class="mini"><span>📍 Cafeteria</span></div>
          <div class="footer"><span class="review"><span class="stars" style="--rating:4.5"></span>4.5</span></div>
        </div>
      </article>

      <article
        class="card"
        data-href="eventpage.php"
        role="link"
        tabindex="0"
        aria-label="Open past event: Club A — Sustainability Talk">
        <div class="date"><div class="day">28</div><div class="mon">AUG</div><div class="sep">Thu</div></div>
        <div>
          <div class="topline"><span class="state completed">Completed</span><span class="chip sponsor">Sponsor: GreenLabs</span></div>
          <div class="title">Club A — Sustainability Talk</div>
          <div class="mini"><span>📍 Auditorium</span></div>
          <div class="footer"><span class="review"><span class="stars" style="--rating:3.8"></span>3.8</span></div>
        </div>
      </article>

    </div>
  </section>
</div>

<?php include 'footer.php'; ?>

<script>
/* Make any .card with data-href clickable + keyboard accessible */
(function(){
  function shouldIgnore(target){
    // don’t hijack clicks on interactive elements inside the card
    const interactive = ['A','BUTTON','INPUT','SELECT','TEXTAREA','LABEL','SVG','PATH'];
    return interactive.includes(target.tagName);
  }

  document.addEventListener('click', (e) => {
    const card = e.target.closest('.card[data-href]');
    if(!card) return;
    if(shouldIgnore(e.target)) return;
    const url = card.getAttribute('data-href');
    if(url) window.location.href = url;
  });

  document.addEventListener('keydown', (e) => {
    if(e.key !== 'Enter' && e.key !== ' ') return;
    const card = e.target.closest('.card[data-href]');
    if(!card) return;
    e.preventDefault();
    const url = card.getAttribute('data-href');
    if(url) window.location.href = url;
  });
})();
</script>

</body>
</html>

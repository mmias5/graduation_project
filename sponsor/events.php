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
<title>UniHive — Upcoming Events</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Brand Tokens ===== */
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
    background-repeat:no-repeat;
  }

  /* ===== Events page ===== */
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
    text-align:left;
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
    margin:8px 0 22px;
    font-size:15px;
  }

  .section{
    margin:10px 0;
  }

  .section h2{
    font-size:20px;
    margin:0 0 12px;
    color:var(--navy);
    font-weight:800;
  }

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
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    border:2px solid transparent;
  }
  .card:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 38px rgba(12,22,60,.16);
    border-color:var(--gold);
  }
  .card:focus{
    outline:3px solid var(--royal);
    outline-offset:3px;
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
  .date .day{
    font-size:28px;
  }
  .date .mon{
    font-size:12px;
    margin-top:2px;
    letter-spacing:1px;
  }
  .date .sep{
    font-size:11px;
    color:#6b7280;
    margin-top:6px;
  }

  .topline{
    display:flex;
    align-items:center;
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
    color:var(--ink);
  }

  .mini{
    color:var(--muted);
    font-size:13px;
    display:flex;
    gap:14px;
    flex-wrap:wrap;
  }

  .footer{
    margin-top:8px;
    font-size:13px;
    color:var(--muted);
    display:flex;
    align-items:center;
  }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<!-- ===== Events Content ===== -->
<div class="wrapper">
  <h1 class="page-title">Upcoming Events</h1>
  <p class="subtle">Discover what’s happening next across UniHive clubs and activities.</p>

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
        <div class="date">
          <div class="day">10</div>
          <div class="mon">SEP</div>
          <div class="sep">Tue</div>
        </div>
        <div>
          <div class="topline">
            <span class="badge">+30 pt</span>
            <span class="chip sponsor">Sponsor: TechCorp</span>
          </div>
          <div class="title">Club B — Hack Night</div>
          <div class="mini">
            <span>📍 Innovation Lab</span>
          </div>
          <div class="footer">
            <span class="mini">🕒 6:00 PM</span>
          </div>
        </div>
      </article>

      <!-- ===== EVENT 2 ===== -->
      <article
        class="card"
        data-href="eventpage.php"
        role="link"
        tabindex="0"
        aria-label="Open event: Club B — Finance 101">
        <div class="date">
          <div class="day">15</div>
          <div class="mon">SEP</div>
          <div class="sep">Sun</div>
        </div>
        <div>
          <div class="topline">
            <span class="badge">+30 pt</span>
            <span class="chip sponsor">Sponsor: BlueBank</span>
          </div>
          <div class="title">Club B — Finance 101</div>
          <div class="mini">
            <span>📍 Main Hall</span>
          </div>
          <div class="footer">
            <span class="mini">🕒 4:30 PM</span>
          </div>
        </div>
      </article>

      <!-- You can add more upcoming events here using the same card structure -->

    </div>
  </section>
</div>

<?php include 'footer.php'; ?>

<script>
/* Make any .card with data-href clickable + keyboard accessible */
(function(){
  function shouldIgnore(target){
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

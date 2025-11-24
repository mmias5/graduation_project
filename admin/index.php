<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Admin Dashboard</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --navy: #242751;
      --coral: #ff5c5c;

      /* page + main backgrounds */
      --pageBg: #e9ecf1;    /* slightly darker grey */
      --mainBg: #f5f6fb;    /* lighter grey (content area) */

      --card: #ffffff;      /* pure white cards */
      --biCard: #ffffff;

      --ink: #111827;
      --muted: #6b7280;

      --radiusLg: 26px;
      --radiusXl: 32px;

      --shadowSoft: 0 20px 38px rgba(15,23,42,.10);
      --shadowLight: 0 10px 24px rgba(15,23,42,.08);

      --sidebarWidth: 230px;
    }

    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
    }

    body{
      min-height:100vh;
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:var(--ink);
      background:var(--pageBg);   /* soft grey behind everything */
    }

    /* ========= MAIN CONTENT ========= */
    .main{
      margin-left:var(--sidebarWidth);      /* space for fixed sidebar */
      padding:28px 40px 40px;
      background:var(--mainBg);            /* light grey panel */
      min-height:100vh;
      box-shadow:-18px 0 40px rgba(15,23,42,.06);
    }

    .kpi-header{
      font-weight:800;
      font-size:1.24rem;
      margin-bottom:20px;
      color:var(--navy);
    }

    /* KPI row: 4 cards in one row on desktop */
    .kpi-row{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:20px;
      margin-bottom:40px;
    }

    .kpi-card{
      min-height:70px;
      padding:22px 24px;
      border-radius:var(--radiusXl);
      background:var(--card);      /* pure white */
      box-shadow:var(--shadowLight);
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      text-align:center;
      border:1px solid rgba(148,163,184,.18);
    }

    .kpi-pill{
      font-size:.78rem;
      font-weight:600;
      padding:4px 10px;
      border-radius:999px;
      background:#ffe1e1;         /* soft coral */
      color:var(--coral);
      margin-bottom:10px;
    }

    .kpi-label{
      font-size:.98rem;
      font-weight:500;
      color:var(--muted);
      margin-bottom:6px;
    }

    .kpi-value{
      font-size:2.1rem;
      font-weight:800;
      color:var(--navy);
      letter-spacing:.02em;
    }

    /* ========= BI DASHBOARD PLACEHOLDER ========= */
    .bi-wrapper{
      margin-top:8px;
    }

    .bi-title-main{
      font-size:1.3rem;
      font-weight:800;
      color:var(--navy);
      margin-bottom:4px;
    }

    .bi-sub{
      font-size:.95rem;
      color:var(--muted);
      margin-bottom:18px;
    }

    .bi-card{
      width:100%;
      aspect-ratio:16 / 9;
      border-radius:var(--radiusXl);
      background:var(--biCard);         /* white card on grey bg */
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      font-size:1.8rem;
      font-weight:700;
      color:var(--navy);
      box-shadow:var(--shadowSoft);
      border:1px solid rgba(148,163,184,.26);
      text-align:center;
    }

    .bi-note{
      margin-top:8px;
      font-size:.95rem;
      color:var(--muted);
      font-weight:500;
    }

    /* ========= RESPONSIVE ========= */
    @media (max-width:1100px){
      .kpi-row{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
    }

    @media (max-width:700px){
      .main{
        margin-left:0;
        padding:22px 18px 28px;
        box-shadow:none;
      }
      .kpi-row{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main class="main">
    <div class="kpi-header">KPI’s</div>

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-pill">Overview</div>
        <div class="kpi-label">Total students</div>
        <div class="kpi-value">1,240</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Overview</div>
        <div class="kpi-label">Total clubs</div>
        <div class="kpi-value">32</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Events</div>
        <div class="kpi-label">Events completed</div>
        <div class="kpi-value">118</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Engagement</div>
        <div class="kpi-label">Engagement rate</div>
        <div class="kpi-value">74%</div>
      </div>
    </div>

    <section class="bi-wrapper">
      <div class="bi-title-main">Admin BI Dashboard</div>
      <div class="bi-sub">Live analytics on clubs, events, and student engagement.</div>

      <div class="bi-card">
        BI Dashboard placeholder
        <div class="bi-note">Add your Power BI embed URL here.</div>
      </div>
    </section>
  </main>

</body>
</html>

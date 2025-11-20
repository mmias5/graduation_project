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
      --navySoft: #32365b;
      --pink: #ff5c5c;
      --pinkDeep: #ff5c5c;

      --paper: #f5f6fb;
      --card: #f2f4ff;
      --biCard: #eaedf4;
      --ink: #111827;
      --muted: #6b7280;

      --radiusLg:26px;
      --radiusXl:34px;
      --shadowSoft:0 22px 50px rgba(15,23,42,.18);
      --shadowLight:0 14px 32px rgba(148,163,184,.35);
      --sidebarWidth:230px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      min-height:100vh;
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:var(--ink);
      display:flex;
      background:
        radial-gradient(1100px 650px at 0% 0%, rgba(255,92,92,.12), transparent 60%),
        #ffffff;
    }

    /* ========= MAIN CONTENT ========= */
    .main{
      flex:1;
      padding:28px 40px 40px;
      overflow-x:hidden;
      background:
        radial-gradient(1300px 700px at 80% 0%, rgba(255,92,92,.10), transparent 65%),
        linear-gradient(180deg,#f9fafb 0%,#ffffff 100%);
    }

    .kpi-header{
      font-weight:700;
      font-size:1.18rem;
      margin-bottom:18px;
      color:var(--navy);
    }

    /* 4 big KPI cards side by side */
    .kpi-row{
      display:flex;
      gap:20px;
      margin-bottom:42px;
      flex-wrap:wrap;
    }

    .kpi-card{
      flex:1;
      min-width:220px;
      min-height:70px;
      padding:24px 26px;
      border-radius:var(--radiusXl);
      background:
        radial-gradient(circle at 0% 0%, rgba(255,92,92,.18), transparent 55%),
        #f3f4ff;
      box-shadow:var(--shadowLight);
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      text-align:center;
      border:1px solid rgba(148,163,184,.35);
      position:relative;
      overflow:hidden;
    }

    .kpi-pill{
      font-size:.78rem;
      font-weight:600;
      padding:4px 10px;
      border-radius:999px;
      background:rgba(255,92,92,.12);
      color:var(--pinkDeep);
      margin-bottom:12px;
    }

    .kpi-label{
      font-size:.98rem;
      font-weight:500;
      color:var(--muted);
      margin-bottom:6px;
    }

    .kpi-value{
      font-size:2.1rem;
      font-weight:700;
      color:var(--navy);
      letter-spacing:.02em;
    }

    .kpi-card::after{
      content:"";
      position:absolute;
      width:120px;
      height:120px;
      border-radius:50%;
      background:radial-gradient(circle,rgba(255,92,92,.28),transparent 65%);
      right:-30px;
      top:-30px;
      opacity:.8;
    }

    /* ========= BI DASHBOARD PLACEHOLDER ========= */
    .bi-wrapper{
      margin-top:8px;
    }

    .bi-card{
      width:100%;
      aspect-ratio:16 / 9;
      border-radius:var(--radiusXl);
      background:
        radial-gradient(circle at 0% 0%, rgba(255,92,92,.25), transparent 55%),
        var(--biCard);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:clamp(2.2rem, 4vw, 3.4rem);
      font-weight:600;
      color:var(--navy);
      box-shadow:var(--shadowSoft);
      border:1px solid rgba(148,163,184,.35);
    }

    /* ========= RESPONSIVE ========= */
    @media (max-width:1100px){
      .kpi-row{
        flex-wrap:wrap;
      }
      .kpi-card{
        flex:1 1 calc(50% - 20px);
      }
    }

    @media (max-width:700px){
      body{flex-direction:column;}
      .main{
        padding:22px 18px 28px;
      }
      .kpi-card{
        flex:1 1 100%;
        min-height:260px;
      }
      .bi-card{
        min-height:260px;
      }
    }
  </style>
</head>
<body>

  <!-- sidebar from shared file -->
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
        <div class="kpi-label">Total done events</div>
        <div class="kpi-value">118</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Engagement</div>
        <div class="kpi-label">Engagement rate %</div>
        <div class="kpi-value">74%</div>
      </div>
    </div>

    <section class="bi-wrapper">
      <div class="bi-card">
        BI Dashboard
      </div>
    </section>
  </main>

</body>
</html>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Admin Dashboard</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      /* main colors */
      --navy:#242751;
      --navySoft:#32365b;
      --pink:#ff6b9c;
      --pinkDeep:#ff4f82;

      --paper:#f5f6fb;
      --card:#f2f4ff;
      --biCard:#eaedf4;
      --ink:#111827;
      --muted:#6b7280;

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
        radial-gradient(1100px 650px at 0% 0%, rgba(255,107,156,.12), transparent 60%),
        #ffffff;
    }

    /* ========== Sidebar ========== */
    .sidebar{
      width:var(--sidebarWidth);
      background:linear-gradient(180deg,#242751 0%,#292d56 60%,#232547 100%);
      color:#f9fafb;
      display:flex;
      flex-direction:column;
      padding:26px 20px;
      box-shadow:0 0 40px rgba(15,23,42,.55);
      position:relative;
      z-index:2;
    }

    .sidebar-section{
      margin-bottom:32px;
    }

    .sidebar-title{
      font-weight:800;
      font-size:1.12rem;
      letter-spacing:.03em;
      margin-bottom:32px;
    }

    .sidebar-nav{
      display:flex;
      flex-direction:column;
      gap:10px;
      font-size:.97rem;
    }

    .nav-item{
      padding:9px 12px;
      border-radius:999px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      cursor:pointer;
      transition:background .18s ease, color .18s ease, transform .15s ease;
    }

    .nav-item span{pointer-events:none;}

    .nav-item:hover{
      background:rgba(255,255,255,.08);
      transform:translateX(2px);
    }

    .nav-item.active{
      background:linear-gradient(135deg,var(--pinkDeep),var(--pink));
      color:#111827;
      font-weight:600;
      box-shadow:0 10px 26px rgba(255,107,156,.55);
    }

    .nav-arrow{
      font-size:.78rem;
      opacity:.9;
    }

    .sidebar-bottom{
      margin-top:auto;
      display:flex;
      align-items:center;
      gap:9px;
      font-size:.86rem;
      color:#d1d5db;
      opacity:.9;
    }

    .gear-icon{
      width:26px;
      height:26px;
      border-radius:999px;
      border:1px solid rgba(209,213,219,.7);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:14px;
      background:rgba(15,23,42,.35);
    }

    /* ========== Main content ========== */
    .main{
      flex:1;
      padding:28px 40px 40px;
      overflow-x:hidden;
      background:
        radial-gradient(1300px 700px at 80% 0%, rgba(255,107,156,.10), transparent 65%),
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
      min-height:70px;        /* نفس ارتفاع الـ BI Dashboard تقريبًا */
      padding:24px 26px;
      border-radius:var(--radiusXl);
      background:radial-gradient(circle at 0% 0%, rgba(255,107,156,.18), transparent 55%), #f3f4ff;
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
      background:rgba(255,107,156,.12);
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
      background:radial-gradient(circle,rgba(255,107,156,.28),transparent 65%);
      right:-30px;
      top:-30px;
      opacity:.8;
    }

    /* ========== BI Dashboard placeholder ========== */
    .bi-wrapper{
      margin-top:8px;
    }

    .bi-card{
      width:100%;
      min-height:330px;
      border-radius:var(--radiusXl);
      background:radial-gradient(circle at 0% 0%, rgba(255,107,156,.20), transparent 55%), var(--biCard);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:clamp(2.2rem, 4vw, 3.4rem);
      font-weight:600;
      color:var(--navy);
      box-shadow:var(--shadowSoft);
      border:1px solid rgba(148,163,184,.35);
    }

    /* ===== responsive ===== */
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
      .sidebar{
        width:100%;
        flex-direction:row;
        align-items:center;
        justify-content:space-between;
        padding:18px 16px;
      }
      .sidebar-title{margin-bottom:0;font-size:1rem;}
      .sidebar-nav{
        flex-direction:row;
        flex-wrap:wrap;
        justify-content:flex-end;
      }
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

  <!-- ========== Sidebar ========== -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-title">Admin panel</div>
      <nav class="sidebar-nav">
        <div class="nav-item active">
          <span>home</span>
        </div>
         <div class="nav-item">
          <span>Club management</span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="nav-item">
          <span>Events</span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="nav-item">
          <span>Students</span>
        </div>
        <div class="nav-item">
          <span>Sponsors</span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="nav-item">
          <span>News management</span>
        </div>
      </nav>
    </div>

    <div class="sidebar-bottom">
      <div class="gear-icon">⚙</div>
      <span>Settings</span>
    </div>
  </aside>

  <!-- ========== Main content ========== -->
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

    <!-- keep this as a div for Power BI later -->
    <section class="bi-wrapper">
      <div class="bi-card">
        BI Dashboard
      </div>
    </section>
  </main>

</body>
</html>

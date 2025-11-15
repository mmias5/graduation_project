<?php
// index_sponsor.php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Sponsors Portal</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --navy:#242751;
      --royal:#4871db;
      --gold:#e5b758;
      --paper:#eef2f7;
      --ink:#0e1228;
      --card:#ffffff;
      --radius-xl:28px;
      --shadow-soft:0 24px 48px rgba(10,23,60,.18);
    }

    *{box-sizing:border-box;}
    html,body{margin:0;padding:0;}
    body{
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:var(--ink);
      background:var(--paper);
    }

    main.sponsor-home{
      min-height:100vh;
    }

    /* ===== Hero Video ===== */
    .sponsor-hero-video{
      position:relative;
      width:100%;
      overflow:hidden;
      background:var(--gold);
    }
    .sponsor-hero-video video{
      width:100%;
      height:70vh;
      max-height:820px;
      min-height:420px;
      object-fit:cover;
      display:block;
    }
    .sponsor-hero-video::after{
      content:"";
      position:absolute;
      inset:0;
      background:linear-gradient(to bottom,
        rgba(0,0,0,.08) 0%,
        rgba(0,0,0,0) 40%,
        rgba(0,0,0,.08) 100%);
      pointer-events:none;
    }

    /* ===== BI Dashboard Section ===== */
    .bi-section{
      padding:60px 6vw 70px;
    }
    .bi-wrap{
      max-width:1200px;
      margin:0 auto;
      background:var(--card);
      border-radius:var(--radius-xl);
      box-shadow:var(--shadow-soft);
      padding:24px;
    }
    .bi-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      margin-bottom:18px;
    }
    .bi-title{
      font-size:1.4rem;
      font-weight:800;
      color:var(--navy);
    }
    .bi-sub{
      font-size:.9rem;
      color:#6b7280;
    }
    .bi-pill{
      font-size:.8rem;
      padding:6px 14px;
      border-radius:999px;
      background:#f4edd1;
      color:var(--navy);
      border:1px solid rgba(0,0,0,.06);
      font-weight:600;
      white-space:nowrap;
    }
    .bi-frame{
      position:relative;
      width:100%;
      border-radius:20px;
      overflow:hidden;
      border:2px solid rgba(36,39,81,.08);
      background:#f3f5fb;
      aspect-ratio:16/9;
    }
    .bi-frame iframe{
      width:100%;
      height:100%;
      border:0;
    }
    .bi-placeholder{
      position:absolute;
      inset:0;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      font-size:1rem;
      color:#6b7280;
      text-align:center;
      padding:0 24px;
    }

    /* ===== Best Of Ranking Section ===== */
    .ranking-section{
      padding:0 6vw 80px;
    }
    .ranking-card{
      max-width:1200px;
      margin:0 auto;
      background:linear-gradient(145deg,#f4f6ff,#eef2f9);
      border-radius:32px;
      box-shadow:0 30px 60px rgba(12,22,60,.20);
      padding:30px 34px 32px;
    }
    .ranking-header{
      font-size:2.0rem;
      font-weight:800;
      color:var(--navy);
      text-align:center;
      margin-bottom:26px;
    }
    .ranking-list{
      display:flex;
      flex-direction:column;
      gap:14px;
      margin-bottom:24px;
    }
    .ranking-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      background:var(--card);
      border-radius:22px;
      padding:16px 22px;
      box-shadow:0 12px 26px rgba(12,22,60,.08);
    }
    .ranking-row.top{
      background:linear-gradient(90deg,#e5c768,#e5b758);
    }
    .ranking-row.left{
      display:flex;
      align-items:center;
      gap:16px;
    }
    .club-badge{
      width:56px;
      height:56px;
      border-radius:19px;
      background:var(--navy);
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:800;
      font-size:1.2rem;
      border:3px solid #f7e7ac;
      box-shadow:0 10px 22px rgba(14,18,40,.35);
      flex-shrink:0;
    }
    .ranking-row.top .club-badge{
      background:#fff;
      color:var(--navy);
      border-color:#f4df6d;
    }

    /* FIX: Top row text colors */
    .ranking-row.top .club-text-main{
      color:#fff;
    }
    .ranking-row.top .club-text-sub{
      color:var(--navy);
      opacity:.95;
    }

    .club-text-main{
      font-size:1.05rem;
      font-weight:800;
      color:var(--navy);
    }
    .club-text-sub{
      font-size:.85rem;
      color:#6b7280;
      margin-top:2px;
    }

    .ranking-medal{
      font-size:1.6rem;
      flex-shrink:0;
    }

    .ranking-footer{
      display:flex;
      justify-content:flex-start;
      margin-top:6px;
    }

    /* Updated Navy Button */
    .view-more-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:10px 26px;
      border-radius:999px;
      border:none;
      font-weight:700;
      font-size:.95rem;
      cursor:pointer;
      background:var(--navy);
      color:#fff;
      text-decoration:none;
      box-shadow:0 14px 28px rgba(36,39,81,.35);
      transition:transform .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .view-more-btn:hover{
      transform:translateY(-2px);
      background:#1c2045;
      box-shadow:0 18px 36px rgba(36,39,81,.45);
    }

    @media (max-width:900px){
      .bi-wrap,
      .ranking-card{
        padding:22px 18px;
        border-radius:22px;
      }
      .ranking-row{
        padding:12px 14px;
      }
      .ranking-header{
        font-size:1.6rem;
      }
    }
    @media (max-width:640px){
      .bi-header{
        flex-direction:column;
        align-items:flex-start;
      }
      .ranking-row{
        flex-direction:row;
        gap:10px;
      }
      .club-badge{
        width:50px;
        height:50px;
        border-radius:16px;
        font-size:1.05rem;
      }
      .view-more-btn{
        width:100%;
        justify-content:center;
      }
    }
    /* Fix title text becoming white inside the gold top row */
.ranking-row.top .club-text-main {
  color: var(--navy) !important;
}

.ranking-row.top .club-text-sub {
  color: var(--navy) !important;
  opacity: .95;
}

  </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="sponsor-home">

  <!-- ===== Hero Video ===== -->
  <section class="sponsor-hero-video">
    <video
      src="tools/videos/sponsor-intro.mp4"
      autoplay
      muted
      loop
      playsinline>
      Your browser does not support the video tag.
    </video>
  </section>

  <!-- ===== BI Dashboard Section ===== -->
  <section class="bi-section">
    <div class="bi-wrap">
      <div class="bi-header">
        <div>
          <div class="bi-title">Sponsorship Impact Dashboard</div>
          <div class="bi-sub">
            Live KPIs on club performance, event reach, and student engagement.
          </div>
        </div>
        <div class="bi-pill">Interactive • Powered by Power BI</div>
      </div>

      <div class="bi-frame">
        <div class="bi-placeholder">
          <strong>BI dashboard placeholder</strong><br>
          Connect your Power BI embed URL here to make this area fully interactive.
        </div>
      </div>
    </div>
  </section>

  <!-- ===== Best Of Ranking Section ===== -->
  <section class="ranking-section">
    <div class="ranking-card">
      <div class="ranking-header">Best Of Ranking</div>

      <div class="ranking-list">

        <!-- 1 -->
        <div class="ranking-row top">
          <div class="ranking-row left">
            <div class="club-badge">AI</div>
            <div>
              <div class="club-text-main">AI Innovators</div>
              <div class="club-text-sub">Sponsored by Tech Bee</div>
            </div>
          </div>
          <div class="ranking-medal">🏆</div>
        </div>

        <!-- 2 -->
        <div class="ranking-row">
          <div class="ranking-row left">
            <div class="club-badge">BL</div>
            <div>
              <div class="club-text-main">Business Leaders</div>
              <div class="club-text-sub">Sponsored by FinCorp</div>
            </div>
          </div>
          <div class="ranking-medal">🥈</div>
        </div>

        <!-- 3 -->
        <div class="ranking-row">
          <div class="ranking-row left">
            <div class="club-badge">AM</div>
            <div>
              <div class="club-text-main">Art &amp; Media</div>
              <div class="club-text-sub">Sponsored by CreatiCo</div>
            </div>
          </div>
          <div class="ranking-medal">🥉</div>
        </div>

        <!-- 4 -->
        <div class="ranking-row">
          <div class="ranking-row left">
            <div class="club-badge">GC</div>
            <div>
              <div class="club-text-main">Green Campus</div>
              <div class="club-text-sub">Sponsored by Eco+ Labs</div>
            </div>
          </div>
          <div class="ranking-medal">🎖️</div>
        </div>

      </div>

      <div class="ranking-footer">
        <a href="clubsranking.php" class="view-more-btn">View More</a>
      </div>
    </div>
  </section>

</main>

<?php include 'footer.php'; ?>

</body>
</html>

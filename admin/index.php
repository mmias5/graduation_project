<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>

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

      --pageBg: #e9ecf1;
      --mainBg: #f5f6fb;

      --card: #ffffff;
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
      background:var(--pageBg);
    }

    /* ========= MAIN CONTENT ========= */
    .main{
      margin-left:var(--sidebarWidth);      /* space for sidebar */
      padding:28px 40px 40px;
      background:var(--mainBg);
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
      background:var(--card);
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
      background:#ffe1e1;
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
      margin-bottom:40px;
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
      aspect-ratio:16 / 9;   /* you can change to 19 / 6 if you want */
      border-radius:var(--radiusXl);
      background:var(--biCard);
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

    /* ========= CLUBS RANKING SECTION ========= */

    .rank-section{
      margin-top:32px;
    }

    .rank-header{
      display:flex;
      align-items:center;
      gap:14px;
      margin-bottom:18px;
    }

    .rank-title{
      font-size:1.2rem;
      font-weight:800;
      color:var(--navy);
    }

    .rank-search{
      margin-left:auto;
      width:min(360px,100%);
      position:relative;
    }

    .rank-search input{
      width:100%;
      padding:10px 40px;
      border-radius:999px;
      border:1px solid #d1d5e4;
      background:#ffffff;
      font-size:.9rem;
      outline:none;
      box-shadow:0 4px 10px rgba(15,23,42,.05);
    }

    .rank-search svg{
      position:absolute;
      left:12px;
      top:50%;
      transform:translateY(-50%);
      width:18px;
      height:18px;
      opacity:.6;
    }

    .rank-card{
      background:var(--card);
      border-radius:var(--radiusLg);
      box-shadow:var(--shadowLight);
      border:1px solid rgba(148,163,184,.22);
      overflow:hidden;
    }

    .rank-table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      font-size:.93rem;
    }

    .rank-table thead{
      background:var(--navy);
      color:#f9fafb;
    }

    .rank-table thead th{
      text-align:left;
      padding:10px 16px;
      font-weight:600;
      font-size:.78rem;
      letter-spacing:.03em;
      text-transform:uppercase;
    }

    .rank-table tbody td{
      padding:14px 16px;
      border-top:1px solid #eef0f6;
      vertical-align:middle;
      background:#ffffff;
    }

    .rank-table tbody tr:nth-child(even) td{
      background:#fafbff;
    }

    .rank-table tbody tr:hover td{
      background:#f3f4ff;
    }

    .col-rank{
      width:56px;
      text-align:center;
      font-weight:800;
      color:#6b7280;
    }

    .clubcell{
      display:flex;
      align-items:center;
      gap:10px;
    }

    .club-avatar{
      width:28px;
      height:28px;
      border-radius:50%;
      overflow:hidden;
      display:grid;
      place-items:center;
      background:#fff;
      border:2px solid var(--coral);
      font-size:.8rem;
      font-weight:700;
      color:var(--navy);
    }

    .club-avatar img{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .club-meta{
      display:flex;
      flex-direction:column;
      gap:2px;
    }

    .club-name{
      font-weight:600;
      font-size:.95rem;
      color:var(--ink);
    }

    .club-sponsor{
      font-size:.78rem;
      color:var(--muted);
    }

    .club-sponsor strong{
      color:var(--coral);
      font-weight:700;
    }

    .pill{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:34px;
      padding:3px 10px;
      border-radius:999px;
      background:#eef2ff;
      color:#1d2a6b;
      font-size:.78rem;
      font-weight:700;
    }

    .points-cell{
      font-weight:700;
      color:var(--navy);
      white-space:nowrap;
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
      .rank-header{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
      }
      .rank-search{
        margin-left:0;
        width:100%;
      }
      .rank-table thead th:nth-child(5),
      .rank-table tbody td:nth-child(5){
        display:none; /* hide Members column on very small phones */
      }
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main class="main">
    <!-- KPI CARDS -->
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

    <!-- BI DASHBOARD -->
    <section class="bi-wrapper">
      <div class="bi-title-main">Admin BI Dashboard</div>
      <div class="bi-sub">Live analytics on clubs, events, and student engagement.</div>

      <div class="bi-card">
        BI Dashboard placeholder
        <div class="bi-note">Add your Power BI embed URL here.</div>
      </div>
    </section>

    <!-- CLUBS RANKING TABLE (ADMIN THEME) -->
    <section class="rank-section">
      <div class="rank-header">
        <div class="rank-title">Clubs Ranking</div>

        <div class="rank-search" role="search">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 4a6 6 0 014.8 9.6l4.3 4.3-1.4 1.4-4.3-4.3A6 6 0 1110 4zm0 2a4 4 0 100 8 4 4 0 000-8z"/>
          </svg>
          <input id="rankSearch" type="text" placeholder="Search club name…" autocomplete="off">
        </div>
      </div>

      <div class="rank-card" role="region" aria-label="All clubs ranking">
        <table class="rank-table" id="clubsTbl">
          <thead>
            <tr>
              <th class="col-rank">Rank</th>
              <th>Club</th>
              <th>Points</th>
              <th>Events</th>
              <th>Members</th>
            </tr>
          </thead>
          <tbody>
            <tr data-name="club d">
              <td class="col-rank">1</td>
              <td>
                <div class="clubcell">
                  <span class="club-avatar"><img src="pics/club-d.png" alt=""></span>
                  <div class="club-meta">
                    <span class="club-name">Club D</span>
                    <span class="club-sponsor">Sponsored by <strong>Nike</strong></span>
                  </div>
                </div>
              </td>
              <td class="points-cell">1950</td>
              <td><span class="pill">22</span></td>
              <td><span class="pill">85</span></td>
            </tr>

            <tr data-name="club b">
              <td class="col-rank">2</td>
              <td>
                <div class="clubcell">
                  <span class="club-avatar"><img src="pics/club-b.png" alt=""></span>
                  <div class="club-meta">
                    <span class="club-name">Club B</span>
                    <span class="club-sponsor">Sponsored by <strong>Puma</strong></span>
                  </div>
                </div>
              </td>
              <td class="points-cell">1750</td>
              <td><span class="pill">20</span></td>
              <td><span class="pill">88</span></td>
            </tr>

            <tr data-name="club c">
              <td class="col-rank">3</td>
              <td>
                <div class="clubcell">
                  <span class="club-avatar"><img src="pics/club-c.png" alt=""></span>
                  <div class="club-meta">
                    <span class="club-name">Club C</span>
                    <span class="club-sponsor">Sponsored by <strong>Pepsi</strong></span>
                  </div>
                </div>
              </td>
              <td class="points-cell">1700</td>
              <td><span class="pill">13</span></td>
              <td><span class="pill">72</span></td>
            </tr>

            <tr data-name="club a2">
              <td class="col-rank">4</td>
              <td>
                <div class="clubcell">
                  <span class="club-avatar"><img src="pics/club-a2.png" alt=""></span>
                  <div class="club-meta">
                    <span class="club-name">Club A2</span>
                    <span class="club-sponsor">Sponsored by <strong>CarePlus</strong></span>
                  </div>
                </div>
              </td>
              <td class="points-cell">1580</td>
              <td><span class="pill">19</span></td>
              <td><span class="pill">88</span></td>
            </tr>

            <tr data-name="club e">
              <td class="col-rank">5</td>
              <td>
                <div class="clubcell">
                  <span class="club-avatar"><img src="pics/club-e.png" alt=""></span>
                  <div class="club-meta">
                    <span class="club-name">Club E</span>
                    <span class="club-sponsor">Sponsored by <strong>ArtWorks</strong></span>
                  </div>
                </div>
              </td>
              <td class="points-cell">1530</td>
              <td><span class="pill">22</span></td>
              <td><span class="pill">67</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <script>
    // Search by club name (case-insensitive, using data-name like your original code)
    (function () {
      const q = document.getElementById('rankSearch');
      if (!q) return;
      const rows = Array.from(document.querySelectorAll('#clubsTbl tbody tr'));
      q.addEventListener('input', function () {
        const s = q.value.trim().toLowerCase();
        rows.forEach(tr => {
          const name = (tr.dataset.name || '').toLowerCase();
          tr.style.display = name.includes(s) ? '' : 'none';
        });
      });
    })();
  </script>

</body>
</html>

<?php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// Helper to get single int
function getSingleInt(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['cnt'];
    }
    return 0;
}

/* ========= KPIs ========= */

// Total students
$totalStudents = getSingleInt($conn, "SELECT COUNT(*) AS cnt FROM student");

// Total clubs (exclude default club)
$totalClubs = getSingleInt($conn, "SELECT COUNT(*) AS cnt FROM club WHERE club_id <> 1");

// Total events (all events)
$totalEvents = getSingleInt($conn, "SELECT COUNT(*) AS cnt FROM event WHERE ending_date IS NOT NULL AND ending_date < NOW()");


// Engagement = students joined any club other than "No Club" (club_id = 1)
$engagedStudents = getSingleInt(
    $conn,
    "SELECT COUNT(*) AS cnt FROM student WHERE club_id IS NOT NULL AND club_id <> 1"
);
$engagementRate = $totalStudents > 0
    ? round(($engagedStudents * 100) / $totalStudents)
    : 0;

/* ========= Clubs ranking =========
   - rank + points from ranking table
   - events_count from event table
   - members_count + status from club table
   - sponsors from sponsor_club_support + sponsor (active by date)
*/
$clubsRanking = [];

$sqlRank = "
    SELECT
        r.rank_position,
        r.total_points,
        c.club_id,
        c.club_name,
        c.logo,
        c.status,
        COALESCE(c.member_count,0) AS members_count,

        /*  Only DONE events (past) */
        COALESCE(COUNT(DISTINCT e.event_id),0) AS events_count,

        /*  Sponsored by from club.sponsor_id */
        COALESCE(sp.company_name, '') AS sponsors

    FROM ranking r
    INNER JOIN club c
        ON c.club_id = r.club_id

    /*  Only count events that ended */
    LEFT JOIN event e
        ON e.club_id = c.club_id
       AND e.ending_date IS NOT NULL
       AND e.ending_date < NOW()

    /*  Sponsor from club table */
    LEFT JOIN sponsor sp
        ON sp.sponsor_id = c.sponsor_id

    WHERE c.club_id <> 1

    GROUP BY
        r.rank_position,
        r.total_points,
        c.club_id,
        c.club_name,
        c.logo,
        c.status,
        c.member_count,
        sp.company_name

    ORDER BY r.rank_position ASC
    LIMIT 50
";


$resRank = $conn->query($sqlRank);
if ($resRank && $resRank->num_rows > 0) {
    while ($row = $resRank->fetch_assoc()) {
        $clubsRanking[] = $row;
    }
}
function assetUrl(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  // إذا كان رابط كامل
  if (preg_match('~^https?://~i', $path)) return $path;

  // إذا كان uploads/.. خليه under project root
  if (strpos($path, 'uploads/') === 0) {
    return '/graduation_project/' . ltrim($path, '/');
  }

  // إذا كان assets/.. (داخل admin) خلّيه زي ما هو
  return $path;
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

      --sidebarWidth: 240px;
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
      margin-left:var(--sidebarWidth);
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

    /* ========= BI DASHBOARD ========= */
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

    /*  Sponsor-like card container */
    .bi-wrap{
      width:100%;
      background:var(--biCard);
      border-radius:var(--radiusXl);
      box-shadow:var(--shadowSoft);
      padding:24px;
      border:1px solid rgba(148,163,184,.22);
    }

    .bi-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      margin-bottom:18px;
    }

    .bi-pill{
      font-size:.8rem;
      padding:6px 14px;
      border-radius:999px;
      background:#ffe1e1;
      color:var(--navy);
      border:1px solid rgba(0,0,0,.06);
      font-weight:700;
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
      text-align:center;
      padding:0 24px;
      color:var(--muted);
      font-weight:600;
    }

    .bi-note{
      margin-top:8px;
      font-size:.95rem;
      color:var(--muted);
      font-weight:500;
    }

    /* ========= CLUBS RANKING SECTION ========= */
    .rank-section{ margin-top:32px; }

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

    .rank-table tbody tr:nth-child(even) td{ background:#fafbff; }
    .rank-table tbody tr:hover td{ background:#f3f4ff; }

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
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    .status-chip{
      display:inline-flex;
      align-items:center;
      padding:3px 10px;
      border-radius:999px;
      font-size:.72rem;
      font-weight:800;
      border:1px solid rgba(148,163,184,.22);
    }
    .status-active{ background:#dcfce7; color:#166534; }
    .status-inactive{ background:#fee2e2; color:#991b1b; }

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
      .kpi-row{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:700px){
      .main{
        margin-left:0;
        padding:22px 18px 28px;
        box-shadow:none;
      }
      .kpi-row{ grid-template-columns:1fr; }
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
        display:none;
      }
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <main class="main">
    <!-- KPI CARDS -->
    <div class="kpi-header">KPI’s</div>

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-pill">Overview</div>
        <div class="kpi-label">Total students</div>
        <div class="kpi-value"><?= number_format($totalStudents); ?></div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Overview</div>
        <div class="kpi-label">Total clubs</div>
        <div class="kpi-value"><?= number_format($totalClubs); ?></div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Events</div>
        <div class="kpi-label">Events completed</div>
        <div class="kpi-value"><?= number_format($totalEvents); ?></div>
      </div>

      <div class="kpi-card">
        <div class="kpi-pill">Engagement</div>
        <div class="kpi-label">Engagement rate</div>
        <div class="kpi-value"><?= $engagementRate; ?>%</div>
      </div>
    </div>

    <!-- BI DASHBOARD -->
    <section class="bi-wrapper">
      <div class="bi-wrap">
        <div class="bi-header">
          <div>
            <div class="bi-title-main">Admin BI Dashboard</div>
            <div class="bi-sub">Live analytics on clubs, events, and student engagement.</div>
          </div>
          <div class="bi-pill">Interactive • Powered by Power BI</div>
        </div>

      <div class="bi-frame">
  <iframe
    title="admin_bi"
    src="https://app.powerbi.com/reportEmbed?reportId=29dca5d8-eeb0-4b61-83dd-137d7ecacbd5&autoAuth=true&ctid=05405dba-373c-4e20-a30e-3e6fcf507cfe"
    allowfullscreen="true">
  </iframe>
</div>

    </section>

    <!-- CLUBS RANKING TABLE -->
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
            <?php if (empty($clubsRanking)): ?>
              <tr>
                <td colspan="5" style="padding:14px 16px; font-size:.9rem; color:var(--muted);">
                  No clubs found yet.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($clubsRanking as $club):
                $clubNameRaw = (string)$club['club_name'];
                $clubName    = htmlspecialchars($clubNameRaw);
                $logoPath = (string)($club['logo'] ?? '');
                $logo     = $logoPath !== '' ? htmlspecialchars(assetUrl($logoPath)) : '';

                $rankPos     = (int)$club['rank_position'];
                $points      = (int)$club['total_points'];
                $eventsCnt   = (int)$club['events_count'];
                $membersCnt  = (int)$club['members_count'];

                $statusRaw   = strtolower(trim((string)$club['status']));
                $isActive    = ($statusRaw === 'active');

                $sponsors = !empty($club['sponsors'])
                    ? htmlspecialchars($club['sponsors'])
                    : 'No active sponsor';
              ?>
                <tr data-name="<?= htmlspecialchars(strtolower($clubNameRaw)); ?>">
                  <td class="col-rank"><?= $rankPos; ?></td>
                  <td>
                    <div class="clubcell">
                      <span class="club-avatar">
                        <?php if ($logo): ?>
                          <img src="<?= $logo; ?>" alt="">
                        <?php else: ?>
                          <?= strtoupper(substr($clubNameRaw,0,1)); ?>
                        <?php endif; ?>
                      </span>

                      <div class="club-meta">
                        <span class="club-name">
                          <?= $clubName; ?>
                          <?php if ($isActive): ?>
                            <span class="status-chip status-active">Active</span>
                          <?php else: ?>
                            <span class="status-chip status-inactive">Inactive</span>
                          <?php endif; ?>
                        </span>

                        <span class="club-sponsor">
                          Sponsored by <strong><?= $sponsors; ?></strong>
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="points-cell"><?= number_format($points); ?></td>
                  <td><span class="pill"><?= $eventsCnt; ?></span></td>
                  <td><span class="pill"><?= $membersCnt; ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <script>
    // Search by club name
    (function () {
      const q = document.getElementById('rankSearch');
      if (!q) return;
      const rows = Array.from(document.querySelectorAll('#clubsTbl tbody tr'));
      q.addEventListener('input', function () {
        const s = q.value.trim().toLowerCase();
        rows.forEach(tr => {
          const name = (tr.dataset.name || '').toLowerCase();
          tr.style.display = !s || name.includes(s) ? '' : 'none';
        });
      });
    })();
  </script>

</body>
</html>

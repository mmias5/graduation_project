<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}


// ===== DB اتصال =====
require_once __DIR__ . '/config.php';

$sponsorId = (int)($_SESSION['sponsor_id'] ?? 0);

// نحاول نجيب آخر فترة ترتيب
$latestPeriod = null;
$periodSql = "SELECT period_start, period_end 
              FROM ranking 
              ORDER BY period_end DESC 
              LIMIT 1";
$periodRes = $conn->query($periodSql);

if ($periodRes && $periodRes->num_rows > 0) {
    $latestPeriod = $periodRes->fetch_assoc();
}

$topClubs = [];

// لو في بيانات ترتيب، نجيب أفضل أربع أندية
if ($latestPeriod) {
    $sql = "
        SELECT 
            r.rank_position,
            r.total_points,
            c.club_id,
            c.club_name,
            c.logo AS club_logo,
            s.company_name AS sponsor_name
        FROM ranking r
        JOIN club c 
            ON r.club_id = c.club_id
        LEFT JOIN sponsor_club_support scs 
            ON scs.club_id = c.club_id
        LEFT JOIN sponsor s 
            ON s.sponsor_id = scs.sponsor_id
        WHERE r.period_start = ? 
          AND r.period_end   = ?
        ORDER BY r.rank_position ASC
        LIMIT 4
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            'ss',
            $latestPeriod['period_start'],
            $latestPeriod['period_end']
        );
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // نولّد اختصار النادي للـ badge (أول حرفين)
            $name = trim($row['club_name']);
            $parts = preg_split('/\s+/', $name);
            if (count($parts) >= 2) {
                $badge = mb_strtoupper(
                    mb_substr($parts[0], 0, 1) .
                    mb_substr($parts[1], 0, 1)
                );
            } else {
                $badge = mb_strtoupper(mb_substr($name, 0, 2));
            }
            $row['badge'] = $badge;

            // لو ما في اسم سبونسر، نخليها فاضية ونعالجها في الـ HTML
            if (empty($row['sponsor_name'])) {
                $row['sponsor_name'] = null;
            }

            $topClubs[] = $row;
        }
        $stmt->close();
    }
}

// لو ما في ولا نتيجة، نستعمل نفس الداتا الوهمية تبعتك
if (empty($topClubs)) {
    $topClubs = [
        [
            'rank_position' => 1,
            'club_name'     => 'AI Innovators',
            'sponsor_name'  => 'Tech Bee',
            'badge'         => 'AI',
            'total_points'  => null,
        ],
        [
            'rank_position' => 2,
            'club_name'     => 'Business Leaders',
            'sponsor_name'  => 'FinCorp',
            'badge'         => 'BL',
            'total_points'  => null,
        ],
        [
            'rank_position' => 3,
            'club_name'     => 'Art & Media',
            'sponsor_name'  => 'CreatiCo',
            'badge'         => 'AM',
            'total_points'  => null,
        ],
        [
            'rank_position' => 4,
            'club_name'     => 'Green Campus',
            'sponsor_name'  => 'Eco+ Labs',
            'badge'         => 'GC',
            'total_points'  => null,
        ],
    ];
}
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
      --navy: #242751;
      --royal: #4871db;
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
/* ===== Sponsor Ranking (User Layout) ===== */
.sponsor-ranking{
  margin: 60px auto;
  max-width: 1200px;
}

.sponsor-ranking .ranking-wrap{
  background:#ffffff;
  border-radius:28px;
  padding:32px;
  box-shadow:0 24px 48px rgba(10,23,60,.18);
}

.sponsor-ranking .ranking-title{
  text-align:center;
  font-size:2rem;
  font-weight:800;
  color:var(--navy);
  margin-bottom:26px;
}

.sponsor-ranking .ranking-list{
  display:grid;
  gap:14px;
}

.sponsor-ranking .rank-item{
  display:grid;
  grid-template-columns:56px 1fr 70px;
  align-items:center;
  background:#fff;
  border-radius:16px;
  padding:14px 18px;
  border:1px solid rgba(36,39,81,.08);
  box-shadow:0 12px 26px rgba(12,22,60,.08);
}

.sponsor-ranking .rank-item.top{
  background:linear-gradient(90deg,#e5c768,#e5b758);
}

.sponsor-ranking .rank-avatar{
  width:44px;
  height:44px;
  border-radius:12px;
  overflow:hidden;
  background:#eef2ff;
  display:flex;
  align-items:center;
  justify-content:center;
}

.sponsor-ranking .rank-avatar img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.sponsor-ranking .rank-meta{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.sponsor-ranking .rank-name{
  font-size:18px;
  font-weight:800;
  color:var(--navy);
}

.sponsor-ranking .rank-points{
  font-size:13px;
  color:#6b7280;
}

.sponsor-ranking .rank-item.top .rank-name,
.sponsor-ranking .rank-item.top .rank-points{
  color:var(--navy);
}

.sponsor-ranking .rank-trophy{
  text-align:right;
  font-size:20px;
}

.sponsor-ranking .ranking-footer{
  margin-top:20px;
  display:flex;
  justify-content:flex-start;
}

.sponsor-ranking .ranking-btn{
  padding:10px 26px;
  border-radius:999px;
  background:var(--navy);
  color:#fff;
  font-weight:700;
  text-decoration:none;
  box-shadow:0 14px 28px rgba(36,39,81,.35);
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
    /* ===== Video Card Style (for future use) ===== */
    .video-card{
      position:relative;
      z-index:2;
      width:100%;
      max-width:1180px;
      aspect-ratio: 16 / 6;
      border-radius:20px;
      overflow:hidden;
      margin:auto;
      background: var(--royal);
      box-shadow:
        0 0 0 2px rgba(10,23,60,.45),
        0 18px 40px var(navy);
    }

    .video-card img,
    .video-card video{
      position:absolute;
      inset:0;
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
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
  <section class="hero-card safe-space">
    <!-- decorative stars (behind the card) -->
    <img class="star star-left"  src="tools/pics/bg.png" alt="" aria-hidden="true">
    <img class="star star-right" src="tools/pics/bg.png" alt="" aria-hidden="true">

    <!-- video card -->
    <div class="video-card">
      <video autoplay muted loop playsinline preload="auto">
        <source src="tools/video/indexvideo.mp4" type="video/mp4">
      </video>
    </div>
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
  
 <!-- ===== BEST OF RANKING (User Layout / Sponsor Colors) ===== -->
<section class="ranking sponsor-ranking">
  <div class="ranking-wrap">
    <h2 class="ranking-title">Best Of Ranking</h2>

    <div class="ranking-list">
      <?php
      $i = 0;
      foreach ($topClubs as $club):
        $i++;
        $trophy = ($i === 1) ? '🏆' : (($i === 2) ? '🥈' : (($i === 3) ? '🥉' : '🎖️'));
      ?>
        <div class="rank-item <?php echo $i === 1 ? 'top' : ''; ?>">
          <div class="rank-avatar">
            <?php if (!empty($club['club_logo'])): ?>
              <img src="<?php echo htmlspecialchars($club['club_logo']); ?>">
            <?php else: ?>
              <img src="https://dummyimage.com/120x120/a9bff8/242751.png&text=CL">
            <?php endif; ?>
          </div>

          <div class="rank-meta">
            <div class="rank-name"><?php echo htmlspecialchars($club['club_name']); ?></div>
            <div class="rank-points">
              <?php echo $club['total_points'] !== null ? number_format($club['total_points']).' pts' : '—'; ?>
            </div>
          </div>

          <div class="rank-trophy"><?php echo $trophy; ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="ranking-footer">
      <a href="clubsranking.php" class="ranking-btn">View More</a>
    </div>
  </div>
</section>


</main>

<?php include 'footer.php'; ?>

</body>
</html>

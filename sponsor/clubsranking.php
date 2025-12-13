<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['sponsor_id']) || ($_SESSION['role'] ?? '') !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

/* =========================
   1) Get latest ranking period
   ========================= */
$currentPeriodEnd = null;
$periodRes = $conn->query("
    SELECT period_end
    FROM ranking
    ORDER BY period_end DESC
    LIMIT 1
");
if ($periodRes && $periodRes->num_rows > 0) {
    $currentPeriodEnd = $periodRes->fetch_assoc()['period_end'];
}

/* =========================
   2) Get ranking clubs (ranking table) + fallback (club.points)
   ========================= */
$rankingClubs = [];

if ($currentPeriodEnd !== null) {
    $stmt = $conn->prepare("
        SELECT
            r.club_id,
            r.total_points,
            r.rank_position,
            r.period_start,
            r.period_end,
            c.club_name,
            c.status AS club_status,
            c.logo AS club_logo,
            c.member_count,
            COALESCE(s.company_name, 'Not sponsored yet') AS sponsor_name,
            s.logo AS sponsor_logo,
            COUNT(DISTINCT e.event_id) AS events_count
        FROM ranking r
        JOIN club c ON c.club_id = r.club_id
        LEFT JOIN sponsor_club_support scs
            ON scs.club_id = c.club_id
           AND (scs.start_date IS NULL OR scs.start_date <= r.period_end)
           AND (scs.end_date   IS NULL OR scs.end_date   >= r.period_start)
        LEFT JOIN sponsor s ON s.sponsor_id = scs.sponsor_id
        LEFT JOIN event e
            ON e.club_id = c.club_id
           AND e.ending_date IS NOT NULL
           AND e.ending_date < NOW()
        WHERE r.period_end = ?
        GROUP BY
            r.club_id,
            r.total_points,
            r.rank_position,
            r.period_start,
            r.period_end,
            c.club_name,
            c.status,
            c.logo,
            c.member_count,
            sponsor_name,
            sponsor_logo
        ORDER BY r.rank_position ASC, r.total_points DESC
    ");
    $stmt->bind_param('s', $currentPeriodEnd);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $rankingClubs[] = $row;
    $stmt->close();
}

if (empty($rankingClubs)) {
    $sql = "
        SELECT
            c.club_id,
            c.points AS total_points,
            NULL AS rank_position,
            NULL AS period_start,
            NULL AS period_end,
            c.club_name,
            c.status AS club_status,
            c.logo AS club_logo,
            c.member_count,
            COALESCE(s.company_name, 'Not sponsored yet') AS sponsor_name,
            s.logo AS sponsor_logo,
            COUNT(DISTINCT e.event_id) AS events_count
        FROM club c
        LEFT JOIN sponsor_club_support scs ON scs.club_id = c.club_id
        LEFT JOIN sponsor s ON s.sponsor_id = scs.sponsor_id
        LEFT JOIN event e
            ON e.club_id = c.club_id
           AND e.ending_date IS NOT NULL
           AND e.ending_date < NOW()
        WHERE c.club_id <> 1
        GROUP BY
            c.club_id,
            c.points,
            c.club_name,
            c.status,
            c.logo,
            c.member_count,
            sponsor_name,
            sponsor_logo
        ORDER BY total_points DESC, c.club_name ASC
    ";
    $res = $conn->query($sql);
    $rank = 1;
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['rank_position'] = $rank++;
            $rankingClubs[] = $row;
        }
    }
}

/* Top 3 */
$top1 = $rankingClubs[0] ?? null;
$top2 = $rankingClubs[1] ?? null;
$top3 = $rankingClubs[2] ?? null;

function initials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'CL';
    $parts = preg_split('/\s+/', $name);
    if (count($parts) >= 2) {
        $a = mb_substr($parts[0], 0, 1);
        $b = mb_substr($parts[1], 0, 1);
        return mb_strtoupper($a.$b);
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}

function safe_img(?string $url): string {
    $u = trim((string)$url);
    return $u !== '' ? htmlspecialchars($u, ENT_QUOTES, 'UTF-8') : '';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Clubs Hub — Clubs Ranking</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* =============================
   Unified Ranking UI (Student)
   ============================= */
.cch-ranking{
  --navy:#242751;
  --royal:#4871db;
  --gold:#e5b758;
  --coral:#ff5e5e;
  --paper:#eef2f7;
  --ink:#0e1228;
  --card:#ffffff;
  --shadow:0 10px 24px rgba(10,23,60,.16);
  --radius:16px;

  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:
    radial-gradient(1200px 480px at 50% 0%, rgba(255,255,255,.75) 0%, rgba(255,255,255,0) 60%),
    linear-gradient(180deg, var(--paper) 0%, var(--paper) 100%);
}

.cch-ranking .wrap{ max-width:1180px; margin:auto; padding:40px 20px 48px; }

/* Title + Search */
.cch-ranking .head{ display:flex; align-items:center; gap:14px; margin-bottom:24px; }
.cch-ranking .title{
  font-weight:800;
  font-size:28px;
  color:var(--navy);
  padding:8px 0;
}
.cch-ranking .title::after{
  content:"";
  display:block;
  width:160px;
  height:6px;
  border-radius:999px;
  margin-top:10px;
  background:var(--gold);
  opacity:.9;
}
.cch-ranking .search{ margin-left:auto; width:min(420px,100%); position:relative; }
.cch-ranking .search input{
  width:100%;
  padding:12px 44px;
  border-radius:999px;
  border:1px solid #d5d9ea;
  background:#fff;
  font-size:15px;
  outline:none;
  box-shadow:0 4px 12px rgba(72,113,219,.12);
}
.cch-ranking .search input::placeholder{ color:#9ba3c3; }
.cch-ranking .search svg{
  position:absolute;
  left:14px; top:50%;
  transform:translateY(-50%);
  width:18px; height:18px;
  opacity:.7;
  fill:var(--navy);
}

/* Sponsor + Logo */
.cch-ranking .sponsor{
  display:flex; align-items:center; gap:6px;
  margin-top:4px;
  font-size:12px; font-weight:700;
  color:#6b7280;
}
.cch-ranking .sponsor.small{ font-size:11px; }
.cch-ranking .sponsor-logo{
  width:20px;height:20px;border-radius:50%;
  overflow:hidden; display:grid; place-items:center;
  background:#fff; border:2px solid var(--gold);
}
.cch-ranking .sponsor-logo.small{ width:18px;height:18px; }
.cch-ranking .sponsor-logo img{ width:100%;height:100%;object-fit:cover; }

/* Status badge */
.cch-ranking .status-badge{
  display:inline-flex; align-items:center; gap:6px;
  height:22px; padding:0 10px;
  border-radius:999px;
  font-weight:900;
  font-size:12px;
  border:1px solid rgba(0,0,0,.06);
  background:#F3F6FF;
  color:#3556D4;
  margin-top:6px;
  width:fit-content;
}
.cch-ranking .status-badge::before{
  content:"";
  width:8px;height:8px;border-radius:50%;
  background:#9aa3b2;
}
.cch-ranking .status-badge.active{
  background:#EEFDF3; color:#147a3d;
}
.cch-ranking .status-badge.active::before{ background:#22c55e; }
.cch-ranking .status-badge.inactive{
  background:#FFF1F2; color:#b42318;
}
.cch-ranking .status-badge.inactive::before{ background:#ff5e5e; }

/* Podium */
.cch-ranking .podium{
  display:grid;
  grid-template-columns:1fr 1.15fr 1fr;
  gap:16px;
  align-items:end;
  margin-bottom:26px;
}
.cch-ranking .pod{
  background:var(--card);
  border-radius:20px;
  box-shadow:var(--shadow);
  padding:18px 14px 14px;
  text-align:center;
}
.cch-ranking .pod.s2{ transform:translateY(10px); }
.cch-ranking .pod.s3{ transform:translateY(18px); }

.cch-ranking .medal{
  width:54px;height:54px;border-radius:50%;
  margin:-42px auto 8px;
  overflow:hidden;
  background:#fff;
  display:grid; place-items:center;
  border:6px solid transparent;
  box-shadow:0 6px 14px rgba(0,0,0,.12);
  font-weight:900;
  color:var(--navy);
}
.cch-ranking .medal.g{ border-color:var(--gold); }
.cch-ranking .medal.s{ border-color:#C5CFDF; }
.cch-ranking .medal.b{ border-color:#D18A57; }
.cch-ranking .medal img{ width:100%;height:100%;object-fit:cover; }

.cch-ranking .clubname{ font-weight:900; font-size:18px; margin-top:4px; color:var(--navy); }
.cch-ranking .subpt{ font-size:14px; font-weight:800; color:#4b5563; margin-top:4px; }

.cch-ranking .ped{
  margin-top:12px;
  border-radius:12px;
  font-weight:900;
  padding:6px 0;
}
.cch-ranking .ped.g{ background:#F4DF6D; color:var(--navy); }
.cch-ranking .ped.s{ background:#E2E8F7; color:#3b4f86; }
.cch-ranking .ped.b{ background:#F7E1D4; color:#7a4e30; }

/* Table */
.cch-ranking .card{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.cch-ranking table{ width:100%; border-collapse:separate; border-spacing:0; }
.cch-ranking thead th{
  text-align:left;
  font-size:13px;
  color:#fff;
  padding:12px 16px;
  background:var(--navy);
  border-bottom:3px solid var(--gold);
}
.cch-ranking tbody td{
  padding:16px 16px;
  border-top:1px solid #EEF0F6;
  vertical-align:middle;
  font-size:15px;
}
.cch-ranking tbody tr:hover{ background:#FAFBFF; }
.cch-ranking .col-rank{ width:56px; text-align:center; font-weight:900; color:#6b7090; }

.cch-ranking .clubcell{
  display:flex; align-items:center; gap:10px; white-space:nowrap;
}
.cch-ranking .avatar{
  width:28px;height:28px;border-radius:50%;
  overflow:hidden;
  display:grid; place-items:center;
  background:#fff;
  border:2px solid var(--gold);
  color:var(--navy);
  font-weight:900;
  font-size:12px;
}
.cch-ranking .avatar img{ width:100%;height:100%;object-fit:cover; }

.cch-ranking .points{
  display:flex; align-items:center; gap:8px;
  font-weight:900;
  white-space:nowrap;
  color:var(--navy);
}
.cch-ranking .points svg{ width:16px;height:16px; }

.cch-ranking .pill{
  display:grid; place-items:center;
  min-width:30px;
  height:22px;
  padding:0 10px;
  border-radius:999px;
  background:#F1F3FA;
  color:var(--navy);
  font-weight:800;
  font-size:12px;
}

@media (max-width:900px){
  .cch-ranking .podium{ grid-template-columns:1fr; }
  .cch-ranking .pod.s2,.cch-ranking .pod.s3{ transform:none; }
  .cch-ranking thead th:nth-child(5),
  .cch-ranking tbody td:nth-child(5){ display:none; }
}
</style>
</head>

<body>
<?php include 'header.php'; ?>

<section class="cch-ranking">
  <div class="wrap">

    <div class="head">
      <div class="title">Clubs Ranking</div>
      <div class="search" role="search">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10 4a6 6 0 014.8 9.6l4.3 4.3-1.4 1.4-4.3-4.3A6 6 0 1110 4zm0 2a4 4 0 100 8 4 4 0 000-8z"/>
        </svg>
        <input id="rankSearch" type="text" placeholder="Search club name…" autocomplete="off">
      </div>
    </div>

    <!-- Top 3 -->
    <div class="podium">

      <!-- #2 -->
      <?php if ($top2): 
        $n = $top2['club_name'] ?? '';
        $p = (int)($top2['total_points'] ?? 0);
        $logo = $top2['club_logo'] ?? '';
        $sp = $top2['sponsor_name'] ?? 'Not sponsored yet';
        $splogo = $top2['sponsor_logo'] ?? '';
      ?>
      <article class="pod s2">
        <div class="medal s">
          <?php if (trim($logo) !== ''): ?>
            <img src="<?php echo safe_img($logo); ?>" alt="<?php echo htmlspecialchars($n); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(initials($n)); ?></span>
          <?php endif; ?>
        </div>

        <div class="clubname"><?php echo htmlspecialchars($n); ?></div>

        <div class="sponsor">
          <span class="sponsor-logo">
            <?php if (trim($splogo) !== ''): ?>
              <img src="<?php echo safe_img($splogo); ?>" alt="">
            <?php else: ?>
              <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($sp,0,1))); ?></span>
            <?php endif; ?>
          </span>
          Sponsored by <strong><?php echo htmlspecialchars($sp); ?></strong>
        </div>

        <div class="subpt"><?php echo number_format($p); ?> pt</div>
        <div class="ped s">2</div>
      </article>
      <?php endif; ?>

      <!-- #1 -->
      <?php if ($top1):
        $n = $top1['club_name'] ?? '';
        $p = (int)($top1['total_points'] ?? 0);
        $logo = $top1['club_logo'] ?? '';
        $sp = $top1['sponsor_name'] ?? 'Not sponsored yet';
        $splogo = $top1['sponsor_logo'] ?? '';
      ?>
      <article class="pod">
        <div class="medal g">
          <?php if (trim($logo) !== ''): ?>
            <img src="<?php echo safe_img($logo); ?>" alt="<?php echo htmlspecialchars($n); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(initials($n)); ?></span>
          <?php endif; ?>
        </div>

        <div class="clubname"><?php echo htmlspecialchars($n); ?></div>

        <div class="sponsor">
          <span class="sponsor-logo">
            <?php if (trim($splogo) !== ''): ?>
              <img src="<?php echo safe_img($splogo); ?>" alt="">
            <?php else: ?>
              <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($sp,0,1))); ?></span>
            <?php endif; ?>
          </span>
          Sponsored by <strong><?php echo htmlspecialchars($sp); ?></strong>
        </div>

        <div class="subpt"><?php echo number_format($p); ?> pt</div>
        <div class="ped g">1</div>
      </article>
      <?php endif; ?>

      <!-- #3 -->
      <?php if ($top3):
        $n = $top3['club_name'] ?? '';
        $p = (int)($top3['total_points'] ?? 0);
        $logo = $top3['club_logo'] ?? '';
        $sp = $top3['sponsor_name'] ?? 'Not sponsored yet';
        $splogo = $top3['sponsor_logo'] ?? '';
      ?>
      <article class="pod s3">
        <div class="medal b">
          <?php if (trim($logo) !== ''): ?>
            <img src="<?php echo safe_img($logo); ?>" alt="<?php echo htmlspecialchars($n); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(initials($n)); ?></span>
          <?php endif; ?>
        </div>

        <div class="clubname"><?php echo htmlspecialchars($n); ?></div>

        <div class="sponsor">
          <span class="sponsor-logo">
            <?php if (trim($splogo) !== ''): ?>
              <img src="<?php echo safe_img($splogo); ?>" alt="">
            <?php else: ?>
              <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($sp,0,1))); ?></span>
            <?php endif; ?>
          </span>
          Sponsored by <strong><?php echo htmlspecialchars($sp); ?></strong>
        </div>

        <div class="subpt"><?php echo number_format($p); ?> pt</div>
        <div class="ped b">3</div>
      </article>
      <?php endif; ?>

    </div>

    <!-- Table -->
    <div class="card" role="region" aria-label="All clubs">
      <table id="clubsTbl">
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
          <?php if (!empty($rankingClubs)): ?>
            <?php foreach ($rankingClubs as $row):
              $clubName = $row['club_name'] ?? '';
              $clubLogo = $row['club_logo'] ?? '';
              $points   = (int)($row['total_points'] ?? 0);
              $events   = (int)($row['events_count'] ?? 0);
              $members  = (int)($row['member_count'] ?? 0);

              $rankPos  = $row['rank_position'];
              if ($rankPos === null || $rankPos === '') $rankPos = '?';

              $spName   = $row['sponsor_name'] ?? 'Not sponsored yet';
              $spLogo   = $row['sponsor_logo'] ?? '';

              $st = strtolower(trim($row['club_status'] ?? 'inactive'));
              $stClass = ($st === 'active') ? 'active' : 'inactive';
              $stLabel = ($st === 'active') ? 'Active' : 'Inactive';

              $dataName = mb_strtolower($clubName);
            ?>
            <tr data-name="<?php echo htmlspecialchars($dataName); ?>">
              <td class="col-rank"><?php echo htmlspecialchars((string)$rankPos); ?></td>

              <td class="clubcell">
                <span class="avatar">
                  <?php if (trim($clubLogo) !== ''): ?>
                    <img src="<?php echo safe_img($clubLogo); ?>" alt="">
                  <?php else: ?>
                    <?php echo htmlspecialchars(initials($clubName)); ?>
                  <?php endif; ?>
                </span>

                <div>
                  <div><?php echo htmlspecialchars($clubName); ?></div>

                  <div class="status-badge <?php echo $stClass; ?>">
                    <?php echo $stLabel; ?>
                  </div>

                  <div class="sponsor small">
                    <span class="sponsor-logo small">
                      <?php if (trim($spLogo) !== ''): ?>
                        <img src="<?php echo safe_img($spLogo); ?>" alt="">
                      <?php else: ?>
                        <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($spName,0,1))); ?></span>
                      <?php endif; ?>
                    </span>
                    Sponsored by <strong><?php echo htmlspecialchars($spName); ?></strong>
                  </div>
                </div>
              </td>

              <td>
                <span class="points">
                  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/>
                  </svg>
                  <?php echo number_format($points); ?>
                </span>
              </td>

              <td><span class="pill"><?php echo $events; ?></span></td>
              <td><span class="pill"><?php echo $members; ?></span></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align:center; padding:20px; color:#6b7280;">
                No clubs ranking data available yet.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</section>

<?php include 'footer.php'; ?>

<script>
(() => {
  const q = document.getElementById('rankSearch');
  const rows = [...document.querySelectorAll('#clubsTbl tbody tr')];
  if (!q) return;
  q.addEventListener('input', () => {
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

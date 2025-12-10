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

require_once '../config.php'; // عدلي المسار إذا ملف config بمكان ثاني

// ---------- 1) Get latest ranking period ----------
$currentPeriodStart = null;
$currentPeriodEnd   = null;
$rankingClubs       = [];

$periodSql = "
    SELECT period_start, period_end
    FROM ranking
    ORDER BY period_end DESC
    LIMIT 1
";
$periodRes = $conn->query($periodSql);
if ($periodRes && $periodRes->num_rows > 0) {
    $periodRow = $periodRes->fetch_assoc();
    $currentPeriodStart = $periodRow['period_start'];
    $currentPeriodEnd   = $periodRow['period_end'];
}

// ---------- 2) Get clubs ranking with sponsor + events + members ----------
if ($currentPeriodEnd !== null) {
    // نستعمل جدول ranking
    $stmt = $conn->prepare("
        SELECT
            r.club_id,
            r.total_points,
            r.rank_position,
            r.period_start,
            r.period_end,
            c.club_name,
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
           AND (e.starting_date BETWEEN r.period_start AND r.period_end)
        WHERE r.period_end = ?
        GROUP BY
            r.club_id,
            r.total_points,
            r.rank_position,
            r.period_start,
            r.period_end,
            c.club_name,
            c.logo,
            c.member_count,
            sponsor_name,
            sponsor_logo
        ORDER BY r.rank_position ASC, r.total_points DESC
    ");
    $stmt->bind_param('s', $currentPeriodEnd);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rankingClubs[] = $row;
    }
    $stmt->close();
} else {
    // ما في بيانات بجدول ranking -> fallback: رتب حسب points من جدول club
    $sql = "
        SELECT
            c.club_id,
            c.points AS total_points,
            NULL AS rank_position,
            NULL AS period_start,
            NULL AS period_end,
            c.club_name,
            c.logo AS club_logo,
            c.member_count,
            COALESCE(s.company_name, 'Not sponsored yet') AS sponsor_name,
            s.logo AS sponsor_logo,
            COUNT(DISTINCT e.event_id) AS events_count
        FROM club c
        LEFT JOIN sponsor_club_support scs ON scs.club_id = c.club_id
        LEFT JOIN sponsor s ON s.sponsor_id = scs.sponsor_id
        LEFT JOIN event e ON e.club_id = c.club_id
        WHERE c.club_id <> 1  -- استثنينا No Club / Not Assigned
        GROUP BY
            c.club_id,
            c.points,
            c.club_name,
            c.logo,
            c.member_count,
            sponsor_name,
            sponsor_logo
        ORDER BY total_points DESC
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

// جهزي عناصر التوب 3
$top1 = $rankingClubs[0] ?? null;
$top2 = $rankingClubs[1] ?? null;
$top3 = $rankingClubs[2] ?? null;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Clubs Ranking</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<?php include 'header.php'; ?>
<!-- ===== CCH • Clubs Ranking (final, with gradient background & sponsor logos) ===== -->
<style>
/* ---------- Scoped theme ---------- */
.cch-ranking{
  --navy: #212153;
  --royal: #4871DB;
  --gold: #E5B758;
  --coral: #FF5E5E;
  --ink: #0E1228;
  --card: #fff;
  --shadow:0 10px 24px rgba(0,0,0,.12);
  --radius:16px;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;

  /* 🎨 Background like your Discover page */
  background:
    radial-gradient(1200px 420px at 50% 0%, rgba(255,255,255,.55) 0%, rgba(255,255,255,0) 60%),
    linear-gradient(180deg, #e9ecef 100%);
}
.cch-ranking .wrap{ max-width:1180px; margin:auto; padding:32px 20px 40px }

/* ---------- Title + Search ---------- */
.cch-ranking .head{ display:flex; align-items:center; gap:14px; margin-bottom:22px }
.cch-ranking .title{
  background:linear-gradient(135deg,var(--royal),var(--gold));
  color:#fff; padding:10px 18px; border-radius:12px; font-weight:800; font-size:28px;
  box-shadow:var(--shadow);
}
.cch-ranking .search{ margin-left:auto; width:min(420px,100%); position:relative }
.cch-ranking .search input{
  width:100%; padding:12px 44px; border-radius:999px; border:1px solid #d9dceb; background:#fff;
  font-size:15px; outline:none; box-shadow:0 4px 12px rgba(72,113,219,.12)
}
.cch-ranking .search svg{ position:absolute; left:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; opacity:.7 }

/* ---------- Sponsor line + logo pill (used in top3 & table) ---------- */
.cch-ranking .sponsor{ display:flex; align-items:center; gap:6px; margin-top:4px; font-size:12px; font-weight:600; color:#6b7090 }
.cch-ranking .sponsor.small{ font-size:11px }
.cch-ranking .sponsor-logo{
  width:20px; height:20px; border-radius:50%; overflow:hidden; display:grid; place-items:center;
  background:#fff; border:2px solid var(--coral)
}
.cch-ranking .sponsor-logo.small{ width:18px; height:18px }
.cch-ranking .sponsor-logo img{ width:100%; height:100%; object-fit:cover }

/* ---------- Top 3 Podium ---------- */
.cch-ranking .podium{
  display:grid; grid-template-columns:1fr 1.15fr 1fr; gap:16px; align-items:end; margin-bottom:26px
}
.cch-ranking .pod{ background:var(--card); border-radius:20px; box-shadow:var(--shadow); padding:18px 14px 14px; text-align:center }
.cch-ranking .pod.s2{ transform:translateY(10px) } .cch-ranking .pod.s3{ transform:translateY(18px) }

/* Club logo circle (image) */
.cch-ranking .medal{
  width:54px; height:54px; border-radius:50%; margin:-42px auto 8px; overflow:hidden;
  background:#fff; display:grid; place-items:center; border:6px solid transparent; box-shadow:0 6px 14px rgba(0,0,0,.12)
}
.cch-ranking .medal.g{ border-color:var(--gold) } .cch-ranking .medal.s{ border-color:#C5CFDF } .cch-ranking .medal.b{ border-color:#D18A57 }
.cch-ranking .medal img{ width:100%; height:100%; object-fit:cover }

.cch-ranking .clubname{ font-weight:800; font-size:18px; margin-top:4px }
.cch-ranking .subpt{ font-size:14px; font-weight:700; color:#5b6384; margin-top:4px }
.cch-ranking .ped{ margin-top:12px; border-radius:12px; font-weight:800; padding:6px 0 }
.cch-ranking .ped.g{ background:#FFE8A3; color:#7c5f00 } .cch-ranking .ped.s{ background:#E8EEF6; color:#465a7c } .cch-ranking .ped.b{ background:#F4E0D4; color:#724f34 }

/* ---------- Table ---------- */
.cch-ranking .card{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden }
.cch-ranking table{ width:100%; border-collapse:separate; border-spacing:0 }
.cch-ranking thead th{ text-align:left; font-size:13px; color:white; padding:12px 16px; background:#4871DB }
.cch-ranking tbody td{ padding:16px 16px; border-top:1px solid #EEF0F6; vertical-align:middle; font-size:15px }
.cch-ranking tbody tr:hover{ background: #FAFBFF }
.cch-ranking .col-rank{ width:56px; text-align:center; font-weight:900; color:#6b7090 }

/* Club cell with image avatar + name + sponsor */
.cch-ranking .clubcell{ display:flex; align-items:center; gap:10px; white-space:nowrap }
.cch-ranking .avatar{
  width:28px; height:28px; border-radius:50%; overflow:hidden; display:grid; place-items:center;
  background:#fff; border:2px solid var(--coral); color:#3556D4; font-weight:700
}
.cch-ranking .avatar img{ width:100%; height:100%; object-fit:cover }

.cch-ranking .points{ display:flex; align-items:center; gap:8px; font-weight:800; white-space:nowrap }
.cch-ranking .points svg{ width:16px; height:16px }
.cch-ranking .pill{ display:grid; place-items:center; min-width:30px; height:22px; padding:0 10px; border-radius:999px; background:#EEF2FF; color:#3556D4; font-weight:700; font-size:12px }

/* ---------- Responsive ---------- */
@media (max-width:900px){
  .cch-ranking .podium{ grid-template-columns:1fr }
  .cch-ranking .pod.s2,.cch-ranking .pod.s3{ transform:none }
  .cch-ranking thead th:nth-child(5), .cch-ranking tbody td:nth-child(5){ display:none } /* hide Members on narrow screens */
}

</style>

<section class="cch-ranking">
  <div class="wrap">

    <!-- Header + Search -->
    <div class="head">
      <div class="title">Clubs Ranking</div>
      <div class="search" role="search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a6 6 0 014.8 9.6l4.3 4.3-1.4 1.4-4.3-4.3A6 6 0 1110 4zm0 2a4 4 0 100 8 4 4 0 000-8z"/></svg>
        <input id="rankSearch" type="text" placeholder="Search club name…" autocomplete="off">
      </div>
    </div>

    <!-- Top 3 (from DB) -->
    <div class="podium">
      <!-- #2 -->
      <?php if ($top2): ?>
      <article class="pod s2">
        <div class="medal s">
          <?php if (!empty($top2['club_logo'])): ?>
            <img src="<?php echo htmlspecialchars($top2['club_logo']); ?>" alt="<?php echo htmlspecialchars($top2['club_name']); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(substr($top2['club_name'], 0, 1)); ?></span>
          <?php endif; ?>
        </div>
        <div class="clubname"><?php echo htmlspecialchars($top2['club_name']); ?></div>
        <div class="sponsor">
          <?php if (!empty($top2['sponsor_logo'])): ?>
            <span class="sponsor-logo">
              <img src="<?php echo htmlspecialchars($top2['sponsor_logo']); ?>" alt="">
            </span>
          <?php else: ?>
            <span class="sponsor-logo"></span>
          <?php endif; ?>
          Sponsored by <strong><?php echo htmlspecialchars($top2['sponsor_name']); ?></strong>
        </div>
        <div class="subpt"><?php echo (int)$top2['total_points']; ?> pt</div>
        <div class="ped s">2</div>
      </article>
      <?php endif; ?>

      <!-- #1 -->
      <?php if ($top1): ?>
      <article class="pod">
        <div class="medal g">
          <?php if (!empty($top1['club_logo'])): ?>
            <img src="<?php echo htmlspecialchars($top1['club_logo']); ?>" alt="<?php echo htmlspecialchars($top1['club_name']); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(substr($top1['club_name'], 0, 1)); ?></span>
          <?php endif; ?>
        </div>
        <div class="clubname"><?php echo htmlspecialchars($top1['club_name']); ?></div>
        <div class="sponsor">
          <?php if (!empty($top1['sponsor_logo'])): ?>
            <span class="sponsor-logo">
              <img src="<?php echo htmlspecialchars($top1['sponsor_logo']); ?>" alt="">
            </span>
          <?php else: ?>
            <span class="sponsor-logo"></span>
          <?php endif; ?>
          Sponsored by <strong><?php echo htmlspecialchars($top1['sponsor_name']); ?></strong>
        </div>
        <div class="subpt"><?php echo (int)$top1['total_points']; ?> pt</div>
        <div class="ped g">1</div>
      </article>
      <?php else: ?>
      <!-- في حالة ما في أي بيانات -->
      <article class="pod">
        <div class="clubname">No ranking data yet</div>
      </article>
      <?php endif; ?>

      <!-- #3 -->
      <?php if ($top3): ?>
      <article class="pod s3">
        <div class="medal b">
          <?php if (!empty($top3['club_logo'])): ?>
            <img src="<?php echo htmlspecialchars($top3['club_logo']); ?>" alt="<?php echo htmlspecialchars($top3['club_name']); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(substr($top3['club_name'], 0, 1)); ?></span>
          <?php endif; ?>
        </div>
        <div class="clubname"><?php echo htmlspecialchars($top3['club_name']); ?></div>
        <div class="sponsor">
          <?php if (!empty($top3['sponsor_logo'])): ?>
            <span class="sponsor-logo">
              <img src="<?php echo htmlspecialchars($top3['sponsor_logo']); ?>" alt="">
            </span>
          <?php else: ?>
            <span class="sponsor-logo"></span>
          <?php endif; ?>
          Sponsored by <strong><?php echo htmlspecialchars($top3['sponsor_name']); ?></strong>
        </div>
        <div class="subpt"><?php echo (int)$top3['total_points']; ?> pt</div>
        <div class="ped b">3</div>
      </article>
      <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="card" role="region" aria-label="All clubs">
      <table id="clubsTbl">
        <thead style="background:#4871DB">
        <tr>
          <th class="col-rank" style="color:white">Rank</th>
          <th>Club</th>
          <th>Points</th>
          <th>Events</th>
          <th>Members</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($rankingClubs)): ?>
          <?php foreach ($rankingClubs as $row): ?>
            <?php
              $rankPos = $row['rank_position'] ?? null;
              if ($rankPos === null) {
                  $rankPos = '?';
              }
            ?>
            <tr data-name="<?php echo htmlspecialchars(strtolower($row['club_name'])); ?>">
              <td class="col-rank"><?php echo (int)$rankPos; ?></td>
              <td class="clubcell">
                <span class="avatar">
                  <?php if (!empty($row['club_logo'])): ?>
                    <img src="<?php echo htmlspecialchars($row['club_logo']); ?>" alt="">
                  <?php else: ?>
                    <span><?php echo htmlspecialchars(substr($row['club_name'], 0, 1)); ?></span>
                  <?php endif; ?>
                </span>
                <div>
                  <span><?php echo htmlspecialchars($row['club_name']); ?></span>
                  <div class="sponsor small">
                    <?php if (!empty($row['sponsor_logo'])): ?>
                      <span class="sponsor-logo small">
                        <img src="<?php echo htmlspecialchars($row['sponsor_logo']); ?>" alt="">
                      </span>
                    <?php else: ?>
                      <span class="sponsor-logo small"></span>
                    <?php endif; ?>
                    Sponsored by <strong><?php echo htmlspecialchars($row['sponsor_name']); ?></strong>
                  </div>
                </div>
              </td>
              <td>
                <span class="points">
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/>
                  </svg>
                  <?php echo (int)$row['total_points']; ?>
                </span>
              </td>
              <td><span class="pill"><?php echo (int)$row['events_count']; ?></span></td>
              <td><span class="pill"><?php echo (int)$row['member_count']; ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center; padding:20px;">No clubs ranking data available yet.</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</section>

<script>
/* Search by club name only (case-insensitive) */
(() => {
  const q = document.getElementById('rankSearch');
  const rows = [...document.querySelectorAll('#clubsTbl tbody tr')];
  q.addEventListener('input', () => {
    const s = q.value.trim().toLowerCase();
    rows.forEach(tr => {
      const name = (tr.dataset.name || '').toLowerCase();
      tr.style.display = name.includes(s) ? '' : 'none';
    });
  });
})();
</script>
<?php include 'footer.php'; ?>

</body>
</html>

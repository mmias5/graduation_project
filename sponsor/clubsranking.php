<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php'; // اتصال الـ mysqli نفس باقي صفحات السبونسر

$clubs = [];

if ($conn instanceof mysqli) {
    $sql = "
        SELECT 
            c.club_id,
            c.club_name,
            c.logo,
            c.points,
            c.member_count,
            COUNT(DISTINCT e.event_id)         AS total_events,
            MIN(s.company_name)                AS sponsor_name,
            MIN(s.logo)                        AS sponsor_logo
        FROM club c
        LEFT JOIN event e 
            ON e.club_id = c.club_id
        LEFT JOIN sponsor_club_support scs
            ON scs.club_id = c.club_id
        LEFT JOIN sponsor s
            ON s.sponsor_id = scs.sponsor_id
        WHERE c.status = 'active'
          AND c.club_id <> 1              -- لو عندك system club برقم 1
        GROUP BY 
            c.club_id,
            c.club_name,
            c.logo,
            c.points,
            c.member_count
        ORDER BY 
            c.points DESC,
            total_events DESC,
            c.club_name ASC
    ";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $clubs[] = $row;
        }
        $result->free();
    }
}

// نجهز الـ podium (أعلى 3)
$first  = $clubs[0] ?? null;
$second = $clubs[1] ?? null;
$third  = $clubs[2] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Clubs Ranking</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ---------- Scoped theme ---------- */
.cch-ranking{
  --navy: #242751;
  --royal: #4871DB;
  --gold: #E5B758;
  --coral: #FF5E5E;
  --paper:#EEF2F7;
  --ink: #0E1228;
  --card: #ffffff;
  --shadow:0 10px 24px rgba(10,23,60,.16);
  --radius:16px;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;

  background:
    radial-gradient(1200px 480px at 50% 0%, rgba(255,255,255,.85) 0%, rgba(255,255,255,0) 60%),
    linear-gradient(180deg, var(--paper) 0%, var(--paper) 100%);
}
.cch-ranking .wrap{
  max-width:1180px;
  margin:auto;
  padding:40px 20px 48px;
}

/* ---------- Title + Search ---------- */
.cch-ranking .head{
  display:flex;
  align-items:center;
  gap:14px;
  margin-bottom:24px;
}
.cch-ranking .title{
  font-weight:800;
  font-size:28px;
  color:var(--navy);
  padding:8px 0;
}
.cch-ranking .title::after{
  content:"";
  display:block;
  width:160px;              /* slightly longer */
  height:6px;               /* thicker = more premium */
  border-radius:999px;
  margin-top:10px;
  background:var(--gold);   /* solid gold matches header/footer */
  opacity:0.9;
}
.cch-ranking .search{
  margin-left:auto;
  width:min(420px,100%);
  position:relative;
}
.cch-ranking .search input{
  width:100%;
  padding:12px 44px;
  border-radius:999px;
  border:1px solid #d5d9ea;
  background:#fff;
  font-size:15px;
  outline:none;
  box-shadow:0 4px 12px rgba(72,113,219,.15);
}
.cch-ranking .search input::placeholder{
  color:#9ba3c3;
}
.cch-ranking .search svg{
  position:absolute;
  left:14px;
  top:50%;
  transform:translateY(-50%);
  width:18px;
  height:18px;
  opacity:.7;
  fill:var(--navy);
}

/* ---------- Sponsor line + logo pill ---------- */
.cch-ranking .sponsor{
  display:flex;
  align-items:center;
  gap:6px;
  margin-top:4px;
  font-size:12px;
  font-weight:600;
  color:#6b7280;
}
.cch-ranking .sponsor.small{ font-size:11px }
.cch-ranking .sponsor-logo{
  width:20px;
  height:20px;
  border-radius:50%;
  overflow:hidden;
  display:grid;
  place-items:center;
  background:#fff;
  border:2px solid var(--gold);
}
.cch-ranking .sponsor-logo.small{
  width:18px;
  height:18px;
}
.cch-ranking .sponsor-logo img{
  width:100%;
  height:100%;
  object-fit:cover;
}

/* ---------- Top 3 Podium ---------- */
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

/* Club logo circle (image) */
.cch-ranking .medal{
  width:54px;
  height:54px;
  border-radius:50%;
  margin:-42px auto 8px;
  overflow:hidden;
  background:#fff;
  display:grid;
  place-items:center;
  border:6px solid transparent;
  box-shadow:0 6px 14px rgba(0,0,0,.12);
}
.cch-ranking .medal.g{ border-color:var(--gold); }
.cch-ranking .medal.s{ border-color:#C5CFDF; }
.cch-ranking .medal.b{ border-color:#D18A57; }
.cch-ranking .medal img{
  width:100%;
  height:100%;
  object-fit:cover;
}
.cch-ranking .clubname{
  font-weight:800;
  font-size:18px;
  margin-top:4px;
  color:var(--navy);
}
.cch-ranking .subpt{
  font-size:14px;
  font-weight:700;
  color:#4b5563;
  margin-top:4px;
}
.cch-ranking .ped{
  margin-top:12px;
  border-radius:12px;
  font-weight:800;
  padding:6px 0;
}
.cch-ranking .ped.g{
  background:#F4DF6D;
  color:var(--navy);
}
.cch-ranking .ped.s{
  background:#E2E8F7;
  color:#3b4f86;
}
.cch-ranking .ped.b{
  background:#F7E1D4;
  color:#7a4e30;
}

/* ---------- Table ---------- */
.cch-ranking .card{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.cch-ranking table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
}
.cch-ranking thead th{
  text-align:left;
  font-size:13px;
  color:#ffffff;
  padding:12px 16px;
  background:var(--navy);              /* solid navy */
  border-bottom:3px solid var(--gold); /* subtle gold accent */
}
.cch-ranking tbody td{
  padding:16px 16px;
  border-top:1px solid #EEF0F6;
  vertical-align:middle;
  font-size:15px;
}
.cch-ranking tbody tr:hover{
  background:#FAFBFF;
}
.cch-ranking .col-rank{
  width:56px;
  text-align:center;
  font-weight:900;
  color:#6b7090;
}

/* Club cell with image avatar + name + sponsor */
.cch-ranking .clubcell{
  display:flex;
  align-items:center;
  gap:10px;
  white-space:nowrap;
}
.cch-ranking .avatar{
  width:28px;
  height:28px;
  border-radius:50%;
  overflow:hidden;
  display:grid;
  place-items:center;
  background:#fff;
  border:2px solid var(--gold);
  color:var(--navy);
  font-weight:700;
}
.cch-ranking .avatar img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.cch-ranking .points{
  display:flex;
  align-items:center;
  gap:8px;
  font-weight:800;
  white-space:nowrap;
  color:var(--navy);
}
.cch-ranking .points svg{
  width:16px;
  height:16px;
}
.cch-ranking .pill{
  display:grid;
  place-items:center;
  min-width:30px;
  height:22px;
  padding:0 10px;
  border-radius:999px;
  background:#F1F3FA;
  color:var(--navy);
  font-weight:700;
  font-size:12px;
}

/* ---------- Responsive ---------- */
@media (max-width:900px){
  .cch-ranking .podium{
    grid-template-columns:1fr;
  }
  .cch-ranking .pod.s2,
  .cch-ranking .pod.s3{
    transform:none;
  }
  .cch-ranking thead th:nth-child(5),
  .cch-ranking tbody td:nth-child(5){
    display:none; /* hide Members on narrow screens */
  }
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<section class="cch-ranking">
  <div class="wrap">

    <!-- Header + Search -->
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
      <?php if ($second): 
        $sClubName   = $second['club_name'];
        $sPoints     = (int)($second['points'] ?? 0);
        $sClubLogo   = $second['club_logo'] ?? '';
        $sSponsor    = $second['sponsor_name'] ?: 'Not sponsored yet';
        $sSponsorLogo= $second['sponsor_logo'] ?? '';
      ?>
      <article class="pod s2">
        <div class="medal s">
          <?php if (!empty($sClubLogo)): ?>
            <img src="<?php echo htmlspecialchars($sClubLogo); ?>" alt="<?php echo htmlspecialchars($sClubName); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($sClubName,0,2))); ?></span>
          <?php endif; ?>
        </div>
        <div class="clubname"><?php echo htmlspecialchars($sClubName); ?></div>
        <div class="sponsor">
          <span class="sponsor-logo">
            <?php if (!empty($sSponsorLogo)): ?>
              <img src="<?php echo htmlspecialchars($sSponsorLogo); ?>" alt="">
            <?php else: ?>
              <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($sSponsor,0,1))); ?></span>
            <?php endif; ?>
          </span>
          Sponsored by <strong><?php echo htmlspecialchars($sSponsor); ?></strong>
        </div>
        <div class="subpt"><?php echo number_format($sPoints); ?> pt</div>
        <div class="ped s">2</div>
      </article>
      <?php endif; ?>

      <!-- #1 -->
      <?php if ($first): 
        $fClubName   = $first['club_name'];
        $fPoints     = (int)($first['points'] ?? 0);
        $fClubLogo   = $first['club_logo'] ?? '';
        $fSponsor    = $first['sponsor_name'] ?: 'Not sponsored yet';
        $fSponsorLogo= $first['sponsor_logo'] ?? '';
      ?>
      <article class="pod">
        <div class="medal g">
          <?php if (!empty($fClubLogo)): ?>
            <img src="<?php echo htmlspecialchars($fClubLogo); ?>" alt="<?php echo htmlspecialchars($fClubName); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($fClubName,0,2))); ?></span>
          <?php endif; ?>
        </div>
        <div class="clubname"><?php echo htmlspecialchars($fClubName); ?></div>
        <div class="sponsor">
          <span class="sponsor-logo">
            <?php if (!empty($fSponsorLogo)): ?>
              <img src="<?php echo htmlspecialchars($fSponsorLogo); ?>" alt="">
            <?php else: ?>
              <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($fSponsor,0,1))); ?></span>
            <?php endif; ?>
          </span>
          Sponsored by <strong><?php echo htmlspecialchars($fSponsor); ?></strong>
        </div>
        <div class="subpt"><?php echo number_format($fPoints); ?> pt</div>
        <div class="ped g">1</div>
      </article>
      <?php endif; ?>

      <!-- #3 -->
      <?php if ($third): 
        $tClubName   = $third['club_name'];
        $tPoints     = (int)($third['points'] ?? 0);
        $tClubLogo   = $third['club_logo'] ?? '';
        $tSponsor    = $third['sponsor_name'] ?: 'Not sponsored yet';
        $tSponsorLogo= $third['sponsor_logo'] ?? '';
      ?>
      <article class="pod s3">
        <div class="medal b">
          <?php if (!empty($tClubLogo)): ?>
            <img src="<?php echo htmlspecialchars($tClubLogo); ?>" alt="<?php echo htmlspecialchars($tClubName); ?>">
          <?php else: ?>
            <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($tClubName,0,2))); ?></span>
          <?php endif; ?>
        </div>
        <div class="clubname"><?php echo htmlspecialchars($tClubName); ?></div>
        <div class="sponsor">
          <span class="sponsor-logo">
            <?php if (!empty($tSponsorLogo)): ?>
              <img src="<?php echo htmlspecialchars($tSponsorLogo); ?>" alt="">
            <?php else: ?>
              <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($tSponsor,0,1))); ?></span>
            <?php endif; ?>
          </span>
          Sponsored by <strong><?php echo htmlspecialchars($tSponsor); ?></strong>
        </div>
        <div class="subpt"><?php echo number_format($tPoints); ?> pt</div>
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
        <?php
        $rank = 1;
        foreach ($clubs as $club):
          $clubName   = $club['club_name'];
          $clubLogo   = $club['club_logo'] ?? '';
          $points     = (int)($club['points'] ?? 0);
          $events     = (int)($club['total_events'] ?? 0);
          $members    = (int)($club['member_count'] ?? 0);
          $sponsor    = $club['sponsor_name'] ?: 'Not sponsored yet';
          $sponsorLogo= $club['sponsor_logo'] ?? '';
          $dataName   = mb_strtolower($clubName);
        ?>
          <tr data-name="<?php echo htmlspecialchars($dataName); ?>">
            <td class="col-rank"><?php echo $rank++; ?></td>
            <td class="clubcell">
              <span class="avatar">
                <?php if (!empty($clubLogo)): ?>
                  <img src="<?php echo htmlspecialchars($clubLogo); ?>" alt="">
                <?php else: ?>
                  <?php echo htmlspecialchars(mb_strtoupper(mb_substr($clubName,0,2))); ?>
                <?php endif; ?>
              </span>
              <div>
                <span><?php echo htmlspecialchars($clubName); ?></span>
                <div class="sponsor small">
                  <span class="sponsor-logo small">
                    <?php if (!empty($sponsorLogo)): ?>
                      <img src="<?php echo htmlspecialchars($sponsorLogo); ?>" alt="">
                    <?php else: ?>
                      <span><?php echo htmlspecialchars(mb_strtoupper(mb_substr($sponsor,0,1))); ?></span>
                    <?php endif; ?>
                  </span>
                  Sponsored by <strong><?php echo htmlspecialchars($sponsor); ?></strong>
                </div>
              </div>
            </td>
            <td>
              <span class="points">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2l2.9 6.2 6.7.6-5 4.5 1.5 6.6L12 16.9 5.9 20l1.5-6.6-5-4.5 6.7-.6L12 2z"/>
                </svg>
                <?php echo number_format($points); ?>
              </span>
            </td>
            <td><span class="pill"><?php echo $events; ?></span></td>
            <td><span class="pill"><?php echo $members; ?></span></td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($clubs)): ?>
          <tr>
            <td colspan="5" style="text-align:center; padding:20px; color:#6b7280;">
              No clubs found.
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
/* Search by club name only (case-insensitive) */
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

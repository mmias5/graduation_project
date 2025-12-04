<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

// ===== Fetch clubs from DB =====
$sql = "
    SELECT 
        club_id,
        club_name,
        category,
        description,
        logo,
        member_count,
        points
    FROM club
    WHERE status = 'active'
      AND club_id <> 1
    ORDER BY points DESC
";

$result = $conn->query($sql);

if (!$result) {
    die('SQL error in discoverclubs: ' . $conn->error);
}

$clubs = [];
while ($row = $result->fetch_assoc()) {
    $clubs[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Discover Clubs</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751;
    --royal:#4871db;
    --gold:#e5b758;
    --lightGold:#f4df6d;
    --paper:#EEF2F7;
    --card:#ffffff;
    --ink:#0e1228;
    --muted:#6b7280;
    --shadow:0 14px 34px rgba(10,23,60,.12);
    --radius:18px;
  }

  body{
    margin:0;
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:
      radial-gradient(1200px 700px at -10% 40%, rgba(168,186,240,.3), transparent 60%),
      radial-gradient(900px 600px at 110% 60%, rgba(72,113,219,.22), transparent 60%),
      var(--paper);
    background-repeat:no-repeat;
  }

  .wrapper{
    max-width:1100px;
    margin:32px auto 48px;
    padding:0 18px;
  }

  .page-title{
    font-size:30px;
    font-weight:800;
    color:var(--navy);
    margin:10px 0 6px;
    text-align:left;
  }

  .page-title::after{
    content:"";
    display:block;
    width:200px;
    height:6px;
    border-radius:999px;
    margin-top:10px;
    background:linear-gradient(90deg,var(--gold),var(--lightGold));
  }

  .subtle{
    color:var(--muted);
    margin:8px 0 22px;
    font-size:15px;
  }

  .section{
    margin:10px 0;
  }

  .section h2{
    font-size:20px;
    margin:0 0 12px;
    color:var(--navy);
    font-weight:800;
  }

  .grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
  }

  @media (max-width:800px){
    .grid{ grid-template-columns:1fr; }
  }

  .card{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:18px;
    display:grid;
    grid-template-columns:80px 1fr;
    gap:14px;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    border:2px solid transparent;
  }

  .card:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 38px rgba(12,22,60,.16);
    border-color:var(--gold);
  }

  .club-logo{
    width:80px;
    height:80px;
    border-radius:20px;
    background:#e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    border:2px solid rgba(36,39,81,.1);
    font-size:20px;
    font-weight:800;
    color:var(--navy);
  }
  .club-logo img{
    width:100%;
    height:100%;
    object-fit:cover;
  }

  .topline{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
  }

  .badge{
    background:#e8f5ff;
    color:#135f9b;
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.06em;
  }

  .title{
    margin:6px 0 4px;
    font-weight:800;
    font-size:18px;
    color:var(--ink);
  }

  .desc{
    font-size:13px;
    color:var(--muted);
    margin-bottom:6px;
    max-height:40px;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .meta{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    font-size:12px;
    color:var(--muted);
    margin-top:4px;
  }

  .meta-strong{
    font-weight:700;
    color:#111827;
  }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="wrapper">
  <h1 class="page-title">Discover New Clubs</h1>
  <p class="subtle">
    Browse student clubs you are not sponsoring yet and spot high-potential communities to support.
  </p>

  <section class="section">
    <h2>Clubs you can sponsor</h2>

    <?php if (empty($clubs)): ?>
      <p class="subtle">You are currently sponsoring all available clubs, or there are no clubs to show.</p>
    <?php else: ?>
      <div class="grid">

        <?php foreach ($clubs as $club): ?>
          <?php
            $clubId      = (int)$club['club_id'];
            $name        = $club['club_name'];
            $category    = $club['category'] ?? 'General';
            $desc        = $club['description'] ?? '';
            $logo        = $club['logo'];
            $members     = (int)$club['member_count'];
            $points      = (int)$club['points'];
            $initials    = mb_strtoupper(mb_substr($name, 0, 2));
          ?>

          <article
            class="card"
            data-href="clubpage.php?id=<?php echo $clubId; ?>"
            role="link"
            tabindex="0"
            aria-label="Open club page: <?php echo htmlspecialchars($name); ?>">

            <div class="club-logo">
              <?php if (!empty($logo)): ?>
                <img src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($name); ?> logo">
              <?php else: ?>
                <?php echo htmlspecialchars($initials); ?>
              <?php endif; ?>
            </div>

            <div>
              <div class="topline">
                <span class="badge"><?php echo htmlspecialchars($category); ?></span>
              </div>

              <div class="title"><?php echo htmlspecialchars($name); ?></div>

              <?php if (!empty($desc)): ?>
                <div class="desc"><?php echo htmlspecialchars($desc); ?></div>
              <?php endif; ?>

              <div class="meta">
                <span>
                  <span class="meta-strong"><?php echo $members; ?></span> members
                </span>

                <span>
                  <span class="meta-strong"><?php echo $points; ?></span> points
                </span>
              </div>
            </div>

          </article>
        <?php endforeach; ?>

      </div>
    <?php endif; ?>
  </section>
</div>

<?php include 'footer.php'; ?>

<script>
(function(){
  function shouldIgnore(target){
    const interactive = ['A','BUTTON','INPUT','SELECT','TEXTAREA','LABEL','SVG','PATH'];
    return interactive.includes(target.tagName);
  }

  document.addEventListener('click', (e) => {
    const card = e.target.closest('.card[data-href]');
    if (!card) return;
    if (shouldIgnore(e.target)) return;
    const url = card.getAttribute('data-href');
    if (url) window.location.href = url;
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const card = e.target.closest('.card[data-href]');
    if (!card) return;
    e.preventDefault();
    const url = card.getAttribute('data-href');
    if (url) window.location.href = url;
  });
})();
</script>

</body>
</html>

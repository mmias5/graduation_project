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
// no_club.php — shown when the student hasn't joined any club yet
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Join a Club</title>

<!-- Font -->
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Brand Tokens (scoped for this page) ===== */
  :root{
    --navy:#242751;
    --royal:#4871db;
    --lightBlue:#a9bff8;
    --gold:#e5b758;      /* use for accents if needed */
    --sun:#f6e578;       /* pill button background (soft yellow) */
    --paper:#eef2f7;
    --ink:#0e1228;
    --card:#ffffff;
    --shadow:0 18px 38px rgba(12,22,60,.16);
    --radius-pill:9999px;
    --maxw:1100px;
  }

  *{box-sizing:border-box}
  html,body{margin:0;padding:0}
  body{
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:linear-gradient(180deg,#f5f7fb, #eef2f7);
    color:var(--ink);
  }

  /* ===== Layout ===== */
  .empty-wrap{
    max-width:var(--maxw);
    margin:40px auto 70px;
    padding:24px;
  }
  .empty-card{
    background:var(--card);
    border-radius:22px;
    box-shadow:var(--shadow);
    padding:42px 28px;
    text-align:center;
  }

  .empty-eyebrow{
    display:inline-block;
    font-weight:800;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--royal);
    background:rgba(72,113,219,.10);
    border-radius:12px;
    padding:8px 12px;
    margin-bottom:14px;
  }

  .empty-title{
    margin:0 0 10px;
    font-size:clamp(24px, 3vw, 34px);
    font-weight:800;
    color:var(--navy);
  }

  .empty-text{
    margin:0 auto 26px;
    max-width:760px;
    font-size:clamp(15px, 1.6vw, 18px);
    line-height:1.7;
    color:#354062;
  }

  /* ===== Pill Button (looks like your screenshot) ===== */
  .discover-pill{
    display:inline-block;
    width:min(980px, 100%);
    text-align:center;
    background:var(--sun);
    color:var(--navy);
    font-weight:800;
    font-size:clamp(16px, 1.8vw, 20px);
    line-height:1;
    padding:22px 28px;           /* height of the pill */
    border-radius:var(--radius-pill);
    text-decoration:none;
    box-shadow:0 18px 38px rgba(226, 202, 96, .40); /* soft yellow glow */
    transition:transform .12s ease, box-shadow .12s ease, opacity .12s ease;
    will-change:transform;
  }
  .discover-pill:hover{
    transform:translateY(-2px);
    box-shadow:0 22px 46px rgba(226, 202, 96, .46);
  }
  .discover-pill:active{
    transform:translateY(0);
    box-shadow:0 14px 30px rgba(226, 202, 96, .38);
    opacity:.95;
  }
  .discover-pill:focus{
    outline:3px solid rgba(72,113,219,.35);
    outline-offset:3px;
  }

  /* small helper: spacing from header/footer if those are sticky */
  .mt-from-header{ margin-top: 16px; }
  .mb-to-footer{ margin-bottom: 24px; }

  @media (max-width:640px){
    .empty-card{ padding:28px 18px; }
  }
</style>
</head>

<body>

<?php include('header.php'); ?>

<main class="empty-wrap mt-from-header mb-to-footer" role="main" aria-labelledby="no-club-title">
  <section class="empty-card">
    <span class="empty-eyebrow">Heads up</span>
    <h1 id="no-club-title" class="empty-title">You haven’t joined a club yet</h1>
    <p class="empty-text">
      To see <strong>My Club Members</strong> you need to join a club first.
      Browse the available clubs and pick the one that suits you best.
    </p>

    <!-- The pill link that matches your “Join us” style -->
    <a class="discover-pill" href="discoverclubs.php" title="Go to Discover Clubs">
      Discover Clubs
    </a>
  </section>
</main>

<?php include('footer.php'); ?>

</body>
</html>

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
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — News Article</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Brand Tokens ===== */
  :root{
    --navy:#242751;
    --royal:#4871db;
    --lightBlue:#a9bff8;
    --gold:#e5b758;
    --paper:#eef2f7;
    --ink:#0e1228;
    --card:#ffffff;
    --shadow:0 18px 38px rgba(12,22,60,.16);
    --radius:22px;
    --maxw:1100px;
  }

  *{box-sizing:border-box}
  html,body{margin:0;padding:0}

  body{
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:linear-gradient(180deg,#f5f7fb,#eef2f7);
    color:var(--ink);
  }

  /* MAIN WRAPPER */
  .wrap{
    max-width:var(--maxw);
    margin:40px auto 0px;
    padding:0 20px;
  }
  .content{
    max-width:var(--maxw);
    margin:40px auto 60px;
    padding:0 20px;
  }

  /* ===== HEADLINE ===== */
  .headline{
    margin:10px 0 16px;
    font-weight:800;
    line-height:1.1;
    font-size:clamp(32px, 4.7vw, 52px);
    color:var(--navy);             /* You requested a navy title */
  }

  .meta{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:22px;
    color:#666c85;
    font-weight:700;
  }

  .badge{
    background:var(--royal);       /* Matches new header/footer color */
    color:#fff;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
  }

  .dot{
    width:6px;height:6px;border-radius:50%;background:#c5c9d7;
  }

  /* ===== HERO IMAGE ===== */
  .hero{
    position:relative;
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:var(--shadow);
    background:#d0d8ff;
    aspect-ratio: 16 / 9;
  }
  .hero img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }
  .credit{
    position:absolute;right:12px;bottom:10px;
    background:rgba(0,0,0,.55);
    color:#fff;
    font-size:12px;
    padding:6px 10px;
    border-radius:999px;
  }

  /* ===== ARTICLE ===== */
  article{
    margin-top:28px;
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:30px;
  }
  article p{
    margin:0 0 18px;
    line-height:1.75;
    font-size:18px;
  }
  article p.lead{
    font-size:19px;
    font-weight:600;
  }

  /* Remove extra space bottom */
  footer{ margin-top:0 !important; }
</style>
</head>

<body>

<!-- HEADER GOES HERE -->
<?php include('header.php'); ?>

<main class="wrap">

    <h1 class="headline">Campus Clubs Hub expands cross-university activities with new analytics tools</h1>

    <div class="meta">
      <span class="badge">News</span>
      <span class="dot"></span>
      <span>Campus Clubs Hub • Nov 2025</span>
    </div>

    <figure class="hero">
      <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=1600&auto=format&fit=crop"
           alt="Students collaborating during a campus event">
      <figcaption class="credit">Photo: CCH Media</figcaption>
    </figure>

    <article class="content">
      <p class="lead">
        Campus Clubs Hub (CCH) introduced a major update that enhances how students and clubs engage
        across multiple universities, bringing a more connected community experience.
      </p>

      <p>
        The new update introduces smarter analytics tools allowing club leaders to track event
        performance, engagement peaks, and growth trends. These insights help clubs identify what
        activities students enjoy most and how they can improve participation. The system also
        enhances the ranking algorithm by combining participation, event creation, and community
        feedback metrics.
      </p>

      <p>
        Students now enjoy a unified feed of events and announcements from various universities,
        making it easier to explore new clubs, attend activities, and collaborate beyond their own
        campus. Additionally, the update includes improved loyalty point tracking, QR check-in for
        events, and sponsor-ready highlight summaries.
      </p>

      <p>
        With more features planned for early 2026, CCH continues to grow toward becoming the leading
        digital hub for student activities in Jordan and the region.
      </p>
    </article>

</main>

<!-- FOOTER GOES HERE -->
<?php include('footer.php'); ?>

</body>
</html>

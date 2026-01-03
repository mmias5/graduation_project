<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
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
<title>CCH — About Us</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  /* ===== Brand Tokens ===== */
  :root{
    --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8;
    --gold:#e5b758; --sun:#f4df6d; --coral:#ff5e5e;
    --paper:#e9ecef; --ink:#0e1228; --card:#fff;
    --shadow:0 10px 30px rgba(0,0,0,.16);
    
    --c-navy:   #2B3751;
    --c-blue:   #4871DB;
    --c-yellow: #F6E578;
    --c-red:    #FF5C5E;
    --c-ice:    #E9ECEF;
    --radius-xl: 18px;
  }
  
  body{ margin:0; font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:var(--ink); background:var(--paper); }

  /* ===== STRATEGY SECTION ===== */
  @font-face{
    font-family: "Extenda 90 Exa";
    src: url("assets/fonts/Extenda90Exa.woff2") format("woff2"),
         url("assets/fonts/Extenda90Exa.woff") format("woff");
    font-weight: 700;
    font-style: normal;
    font-display: swap;
  }

  .strategy,
  .strategy *{
    box-sizing: border-box;
  }

  .strategy{
    background: linear-gradient(180deg, var(--c-ice) 0%, #EFF3FA 40%, var(--c-ice) 100%);
    color: var(--c-navy);
    padding-bottom: 48px;
    font-family: "Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  }

  /* Hero banner with title overlay */
  .strategy-hero{
    width: 100%;
    position: relative;
    background: var(--c-navy);
    overflow: hidden;
    aspect-ratio: 1512 / 840;
    max-height: 500px;
  }
  .strategy-hero::after{
    content:"";
    position:absolute; inset:0;
    background: linear-gradient(0deg, rgba(43,55,81,.25), rgba(43,55,81,.25));
    pointer-events:none;
  }
  .strategy-hero img{
    display:block; 
    width:100%; 
    height: 100%;
    object-fit: cover; 
    object-position: center;
    opacity: .92;
  }
  .hero-title{
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #ffffff;
    font-family: "Extenda 90 Exa", "Raleway", sans-serif;
    font-weight: 700;
    font-size: clamp(32px, 5vw, 56px);
    letter-spacing: 1px;
    text-align: center;
    z-index: 10;
    text-shadow: 0 4px 12px rgba(0,0,0,.5);
    margin: 0;
    padding: 0 20px;
  }

  /* Layout */
  .container{ 
    width:min(1120px, 92%); 
    margin-inline:auto; 
  }

  /* Cards grid */
  .strategy-cards{ 
    display:grid; 
    gap: 24px; 
    margin-top: 32px;
    padding-top: 32px;
    width: 100%;
    max-width: 1120px;
    margin-inline: auto;
  }

  /* Single card */
  .strategy-card{
    display:grid; 
    grid-template-columns: 200px 1fr;
    border-radius: var(--radius-xl);
    overflow:hidden;
    width: 100%;
  }

  /* Left pane (icon on white) */
  .card-media{
    background: #fff;
    display:flex; 
    align-items:center; 
    justify-content:center;
    padding: 28px 20px;
    border-top-left-radius: var(--radius-xl);
    border-bottom-left-radius: var(--radius-xl);
    box-shadow: 0 8px 24px rgba(16,24,40,.08);
  }
  .card-media img{ 
    width: 150px; 
    height: 150px; 
    object-fit: contain; 
  }

  /* Right pane (brand blue panel) */
  .card-body{
    background: linear-gradient(180deg, var(--c-blue) 0%, #3F66C9 100%);
    color: #ffffff;
    padding: 18px 20px 22px;
    border-top-right-radius: var(--radius-xl);
    border-bottom-right-radius: var(--radius-xl);
    box-shadow: 0 8px 24px rgba(16,24,40,.08);
  }

  /* Card header with yellow underline */
  .card-head{
    display:inline-block;
    padding-bottom: 6px;
    margin-bottom: 12px;
    border-bottom: 3px solid var(--c-yellow);
  }
  .card-head h3{
    margin:0;
    font-family: "Extenda 90 Exa", "Raleway", sans-serif;
    font-weight:700;
    font-size: clamp(16px, 2vw, 20px);
    color:#ffffff;
    letter-spacing:.3px;
  }

  .card-body p{
    margin:0;
    font-size: 15px;
    line-height: 1.6;
    color: #F7FAFF;
  }

  .goals-list{
    margin:0; 
    padding-left: 18px;
    display:grid; 
    gap:8px;
    font-size:15px; 
    line-height:1.55;
    color: #F7FAFF;
  }
  .goals-list li::marker{ 
    color: var(--c-yellow); 
  }

  /* Responsive */
  @media (max-width: 780px){
    .strategy-card{ 
      grid-template-columns: 1fr; 
    }
    .card-media{ 
      border-radius: var(--radius-xl) var(--radius-xl) 0 0; 
      padding: 24px 20px;
    }
    .card-body{ 
      border-radius: 0 0 var(--radius-xl) var(--radius-xl); 
    }
    .card-media img{ 
      width:150px; 
      height:150px; 
    }
    .strategy-hero{ 
      max-height: 300px; 
    }
    .hero-title{ 
      font-size: clamp(24px, 6vw, 36px); 
    }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- ===== STRATEGY SECTION ===== -->
<section class="strategy">
  <!-- Hero banner with title overlay -->
  <div class="strategy-hero">
    <img src="tools/pics/social_life.png" alt="Campus clubs growth illustration">
    <h1 class="hero-title">About UniHive</h1>
  </div>

  <!-- Cards -->
  <div class="strategy-cards container">
    <!-- Vision -->
    <article class="strategy-card">
      <div class="card-media">
        <img src="tools/pics/visionyellow.png" alt="Vision icon">
      </div>
      <div class="card-body">
        <div class="card-head"><h3>Vision</h3></div>
        <p>
          To build a connected campus ecosystem that enriches student life both socially and professionally—where clubs, sponsors, and universities come together to create opportunities that prepare students to become future leaders and innovators.
        </p>
      </div>
    </article>

    <!-- Mission -->
    <article class="strategy-card">
      <div class="card-media">
        <img src="tools/pics/missionyellow.png" alt="Mission icon">
      </div>
      <div class="card-body">
        <div class="card-head"><h3>Mission</h3></div>
        <p>
          To reimagine campus life through a digital hub that unites students, clubs, administrators, and sponsors in one collaborative ecosystem. We aim to foster meaningful engagement, enhance social interaction, and connect students with real-world industries—empowering them with the skills, experiences, and confidence needed to thrive beyond graduation.
        </p>
      </div>
    </article>

    <!-- Goals -->
    <article class="strategy-card">
      <div class="card-media">
        <img src="tools/pics/goalsyellow.png" alt="Goals icon">
      </div>
      <div class="card-body">
        <div class="card-head"><h3>Goals</h3></div>
        <ul class="goals-list">
          <li>Create a unified ecosystem that simplifies and digitizes campus club management.</li>
          <li>Enhance student social life through events, networking, and interactive rewards.</li>
          <li>Bridge academia and industry by connecting students with real sponsors and companies.</li>
          <li>Develop future talent by nurturing leadership, teamwork, and professional readiness.</li>
          <li>Empower universities with data-driven insights and a strong reputation for innovation and engagement.</li>
        </ul>
      </div>
    </article>
  </div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — About Us</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ======================================
   BRAND TOKENS — UNIHIVE SPONSOR THEME
====================================== */
:root{
  --navy:#242751;
  --royal:#4871DB;
  --gold:#E5B758;
  --lightGold:#F4DF6D;
  --paper:#EEF2F7;
  --card:#ffffff;
  --muted:#6b7280;
  --radius:20px;
  --shadow:0 14px 34px rgba(10,23,60,.12);
}

/* ======================================
   BASE STYLING
====================================== */
body{
  margin:0;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--paper);
  color:var(--navy);
}

/* ======================================
   HERO BANNER
====================================== */
.about-hero{
  width:100%;
  height:380px;
  background:linear-gradient(0deg,rgba(36,39,81,.55),rgba(36,39,81,.55)),
              url("tools/pics/social_life.png") center/cover no-repeat;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  box-shadow:0 10px 30px rgba(0,0,0,.15);
}

.about-title{
  color:#fff;
  font-weight:900;
  font-size:clamp(32px,5vw,54px);
  text-align:center;
  text-shadow:0 4px 18px rgba(0,0,0,.5);
  margin:0;
  font-family:"Raleway",sans-serif;
}

.about-title::after{
  content:"";
  display:block;
  width:180px;
  height:6px;
  border-radius:999px;
  background:linear-gradient(90deg,var(--gold),var(--lightGold));
  margin:14px auto 0;
}

/* ======================================
   MAIN CONTENT WRAPPER
====================================== */
.about-container{
  width:min(1150px,92%);
  margin:50px auto;
  display:grid;
  gap:32px;
}

/* ======================================
   ABOUT CARDS (Vision / Mission / Goals)
====================================== */
.about-card{
  display:grid;
  grid-template-columns:180px 1fr;
  border-radius:var(--radius);
  overflow:hidden;
  background:var(--card);
  box-shadow:var(--shadow);
}

.about-card-media{
  background:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:26px 18px;
  border-right:4px solid var(--gold);
}

.about-card-media img{
  width:130px;
  height:130px;
  object-fit:contain;
}

.about-card-body{
  padding:24px 28px;
  background:#fff;
}

.about-head{
  font-size:24px;
  font-weight:900;
  margin:0 0 12px;
  color:var(--navy);
  display:inline-block;
  padding-bottom:6px;
  border-bottom:4px solid var(--gold);
}

.about-text{
  font-size:16px;
  line-height:1.6;
  color:#3c3f52;
  margin:0;
}

.about-list{
  margin:0;
  padding-left:20px;
  display:grid;
  gap:8px;
  font-size:16px;
  color:#3c3f52;
}

.about-list li::marker{
  color:var(--gold);
  font-size:18px;
}

/* Responsive */
@media(max-width:820px){
  .about-card{
    grid-template-columns:1fr;
  }
  .about-card-media{
    border-right:none;
    border-bottom:4px solid var(--gold);
    padding:24px 0;
  }
}

</style>
</head>

<body>

<?php include 'header.php'; ?>

<!-- ======================================
     HERO SECTION
====================================== -->
<section class="about-hero">
  <h1 class="about-title">About UniHive</h1>
</section>

<!-- ======================================
     ABOUT CARDS
====================================== -->
<section class="about-container">

  <!-- Vision -->
  <article class="about-card">
    <div class="about-card-media">
      <img src="tools/pics/visiongold.png" alt="Vision">
    </div>
    <div class="about-card-body">
      <h3 class="about-head">Vision</h3>
      <p class="about-text">
        To build a unified and engaging campus ecosystem where students, sponsors, and universities connect through meaningful opportunities — preparing future leaders, innovators, and industry-ready talent.
      </p>
    </div>
  </article>

  <!-- Mission -->
  <article class="about-card">
    <div class="about-card-media">
      <img src="tools/pics/missiongold.png" alt="Mission">
    </div>
    <div class="about-card-body">
      <h3 class="about-head">Mission</h3>
      <p class="about-text">
        To create a dynamic digital hub that strengthens campus engagement, enhances student experiences, and bridges the gap between academia and real-world industries. We empower students socially and professionally while enabling sponsors to connect with motivated future talent.
      </p>
    </div>
  </article>

  <!-- Goals -->
  <article class="about-card">
    <div class="about-card-media">
      <img src="tools/pics/goalsgold.png" alt="Goals">
    </div>
    <div class="about-card-body">
      <h3 class="about-head">Goals</h3>
      <ul class="about-list">
        <li>Digitize and streamline club management in one unified platform.</li>
        <li>Enhance social life through active events, rewards, and campus engagement.</li>
        <li>Connect students with sponsors, brands, and real-world opportunities.</li>
        <li>Develop student leadership, teamwork, and professional readiness.</li>
        <li>Support universities with data insights that strengthen campus reputation.</li>
      </ul>
    </div>
  </article>

</section>

<?php include 'footer.php'; ?>

</body>
</html>

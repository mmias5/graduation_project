<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sponsor Header</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ===== Brand Tokens ===== */
:root{
  --navy:#242751;
  --gold:#e5b758;
  --paper:#eef2f7;
  --ink:#0e1228;
  --card:#ffffff;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}

/* ===== Global for sponsor layout (optional if you set elsewhere) ===== */
body{
  margin:0;
  font-family:"Raleway",sans-serif;
  background:var(--paper);
  color:var(--ink);
}

/* ===== Topbar ===== */
.topbar{
  background:var(--gold);
  color:white;
  width:100%;
}
.topbar-inner{
  max-width:1200px;
  margin:0 auto;
  padding:14px 20px;
  display:flex;
  align-items:center;
  justify-content:space-between;
}

/* ===== Logo ===== */
.brand img{
  height:36px;
}

/* ===== Navigation ===== */
.main-nav{
  display:flex;
  align-items:center;
  gap:25px;
}

/* lock button height / alignment so page styles can't change it */
.nav-btn{
  background:transparent;
  border:none;
  color:white;
  font-weight:800;
  font-size:16px;
  cursor:pointer;
  border-radius:999px;
  padding:10px 18px;
  display:inline-flex;
  align-items:center;
  gap:6px;
  line-height:1;          /* important */
}

.nav-btn:hover{
  background:rgba(0,0,0,0.10);
}

/* ===== Dropdown ===== */
.dropdown{
  position:relative;
  display:inline-flex;
  align-items:center;
}

/* Bridge the gap between button and menu */
.dropdown::after{
  content:'';
  position:absolute;
  top:100%;
  left:0;
  right:0;
  height:10px;
  display:none;
}

.dropdown:hover::after{
  display:block;
}

/* anchored to the pill, not magic 45px */
.dropdown .menu{
  position:absolute;
  top:calc(100% + 10px);   /* 10px under the pill */
  left:50%;
  transform:translateX(-50%);
  min-width:200px;
  background:white;
  border-radius:14px;
  border:2px solid #e7e9f2;
  box-shadow:var(--shadow);
  padding:8px;
  display:none;
  z-index:100;
}

.dropdown:hover .menu{
  display:block;
}

/* scope menu links so other "a { }" rules don't break it */
.topbar .menu a{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 14px;
  border-radius:12px;
  font-weight:700;
  text-decoration:none;
  color:var(--navy);
  white-space:nowrap;
}

.topbar .menu a:hover{
  background:#fff4c8;
}

.topbar .menu a svg{
  stroke:var(--gold);
}

/* ===== Logout Button ===== */
.logout-btn{
  background:white;
  color:var(--navy);
  border:2px solid var(--navy);
  padding:10px 20px;
  border-radius:999px;
  font-weight:800;
  cursor:pointer;
}

.logout-btn:hover{
  background:var(--navy);
  color:white;
}
</style>
</head>

<body>

<header class="topbar">
  <div class="topbar-inner">

    <!-- Logo -->
    <div class="brand">
      <img src="tools/pics/sponsorlogo.png" alt="UniHive sponsor portal logo">
    </div>

    <!-- NAV -->
    <nav class="main-nav">

      <button class="nav-btn" onclick="location.href='index.php'">Home</button>

      <!-- Clubs Dropdown -->
      <div class="dropdown">
        <button class="nav-btn">
          Clubs
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
               stroke="white" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div class="menu">
          <a href="clubsranking.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
              <path d="M8 21V10M16 21V6M12 21v-8"/>
            </svg>
            Clubs Ranking
          </a>

          <a href="discoverclubs.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"/>
              <path d="m21 21-4.3-4.3"/>
            </svg>
            Discover Clubs
          </a>
        </div>
      </div>

      <button class="nav-btn" onclick="location.href='events.php'">Events</button>
      <button class="nav-btn" onclick="location.href='aboutus.php'">About Us</button>

      <!-- Account Dropdown -->
<div class="dropdown">
  <button class="nav-btn">
    Account
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
         stroke="white" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
  </button>

  <div class="menu">
    <a href="account.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2">
        <path d="M20 21a8 8 0 1 0-16 0"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Account Management
    </a>

    <a href="../logout.php">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <path d="M16 17l5-5-5-5"/>
        <path d="M21 12H9"/>
      </svg>
      Logout
    </a>
  </div>
</div>

    </nav>
    

    <!-- Logout -->
    <button class="logout-btn" onclick="location.href='../logout.php'">Logout</button>

  </div>
</header>

</body>
</html>
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$currentPage = basename($_SERVER['PHP_SELF']);

/* Get president info from DB  */
$presidentId = $_SESSION['president_id'] ?? $_SESSION['student_id'] ?? null;

$displayName = "President";
$points      = 0;
$clubId      = null;
$clubStatus  = 'Active'; // default

if ($presidentId) {
    $stmt = $conn->prepare("
        SELECT student_name, total_points, club_id
        FROM student
        WHERE student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $presidentId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $displayName = $row['student_name'] ?? $displayName;
        $points      = (int)($row['total_points'] ?? 0);
        $clubId      = $row['club_id'] ?? null;
    }
    $stmt->close();
}

/*  Get club status (Active/Inactive) for this president club */
if (!empty($clubId)) {
    $stmt = $conn->prepare("SELECT status FROM club WHERE club_id = ? LIMIT 1");
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && $r->num_rows > 0) {
        $clubStatus = $r->fetch_assoc()['status'] ?? 'Active';
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Header + Sidebar + Hover Dropdowns</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========= header ========= */
/* ===== Brand Tokens ===== */
:root{
  --navy: #242751; --royal: #4871db; --lightBlue: #a9bff8;
  --gold: #e5b758; --sun: #f4df6d; --coral: #ff5e5e;
  --paper: #e9ecef; --ink: #0e1228; --card: #fff;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}
body{
  margin:0;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:var(--paper);
}
.display{ font-family:"Extenda 90 Exa","Raleway",system-ui,sans-serif; letter-spacing:.3px; }

/* ===== Top Bar ===== */
.topbar{ background:var(--royal); color:#fff; position:relative; z-index:50; }
.topbar-inner{
  max-width:1200px; margin:0 auto; padding:12px 16px;
  display:flex; align-items:center; justify-content:space-between; gap:12px;
}
.brand{ display:flex; align-items:center; gap:12px; }
.brand img{ height:36px; width:auto; display:block; }

/* Nav buttons */
.main-nav{ display:flex; align-items:center; gap:6px; position:relative; }
.nav-btn{
  appearance:none; border:2px solid transparent; background:transparent; color:#fff;
  font-weight:800; font-size:15.5px; padding:10px 14px; border-radius:999px;
  cursor:pointer; white-space:nowrap;
}
.nav-btn:hover{ background:rgba(255,255,255,.08) }
.nav-btn:focus-visible{ outline:2px solid var(--sun); outline-offset:2px }

/* ===== Dropdowns (hover) ===== */
.dropdown{ position:relative; }

/* Invisible hover bridge so cursor never leaves hover area */
.dropdown::after{
  content:"";
  position:absolute;
  left:0; right:0; top:100%;
  height:12px;
  z-index:40;
}

.menu{
  position:absolute;
  top: calc(100% + 12px);
  left:0;
  min-width:220px;
  background:var(--card);
  color:var(--ink);
  border-radius:14px;
  border:2px solid #e7e9f2;
  box-shadow:var(--shadow);
  padding:8px;
  display:none;
  z-index:60;
}

.dropdown:hover .menu,
.dropdown:focus-within .menu{
  display:block;
}

/* Right: user + hamburger */
.user-block{ display:flex; align-items:center; gap:8px; }
.user-badge{
  display:flex; align-items:center; gap:8px;
  color:#fff; font-weight:700; padding:4px 8px; border-radius:999px;
}
.user-icon{
  width:28px; height:28px; border-radius:50%;
  display:grid; place-items:center; border:2px solid #f4df6d;
}
.menu-btn{
  background:transparent; border:0; padding:8px;
  border-radius:10px; cursor:pointer;
}
.menu-btn:hover{ background:rgba(255,255,255,.08) }
.menu-btn:focus-visible{ outline:2px solid var(--sun); outline-offset:2px }

.underbar{ max-width:1200px; margin:0 auto; padding:18px 16px 28px; }

/* ===== Sidebar ===== */
.sidebar-wrap{ position:fixed; inset:0; display:flex; justify-content:flex-end; pointer-events:none; z-index:70; }
.backdrop{ position:absolute; inset:0; background:rgba(0,0,0,.35); opacity:0; transition:.25s; pointer-events:none; }
.sidebar{
  width:330px; max-width:90vw; height:100%; background:var(--card);
  transform:translateX(100%); transition:transform .25s ease; box-shadow:var(--shadow);
  display:flex; flex-direction:column; pointer-events:auto;
}
body.menu-open .sidebar{ transform:translateX(0) }
body.menu-open .backdrop{ opacity:1; pointer-events:auto }

.sidebar-header{
  padding:20px; border-bottom:1px solid #eef0f6;
  display:flex; align-items:center; gap:14px;
  background:linear-gradient(135deg, var(--royal) 0%, var(--lightBlue) 100%);
  color:#fff;
}
.avatar{
  width:48px; height:48px; border-radius:50%;
  border:2px solid #f4df6d; display:grid; place-items:center;
  background:rgba(255,255,255,.1);
}
.user-name{ font-weight:800; font-size:18px }
.points{
  display:inline-flex; align-items:center; gap:8px; margin-top:4px;
  background:#fff; color:#111; padding:4px 10px; border-radius:999px;
  font-weight:700; font-size:13px; border:2px solid var(--sun);
  box-shadow:0 2px 0 rgba(0,0,0,.06);
}

.sidebar-content{ padding:16px; display:grid; gap:12px }
.side-btn{
  width:100%; display:flex; align-items:center; gap:12px; justify-content:flex-start;
  padding:12px 14px; border-radius:12px; cursor:pointer; font-weight:700;
  text-decoration:none; border:2px solid #e7e9f2; background:#fff; color:var(--ink);
  transition:.15s ease;
}
.side-btn:hover{ background:#f7f9ff; border-color:var(--lightBlue) }
.side-btn:focus-visible{ outline:2px solid var(--royal); outline-offset:2px }
.side-btn.primary{
  background:linear-gradient(135deg, var(--royal), var(--lightBlue));
  border-color:transparent; color:#fff; box-shadow:0 6px 18px rgba(72,113,219,.35);
}
.side-btn.primary:hover{ filter:brightness(1.05) }
.logout{ color:#7f1d1d; background:#fff5f5; border-color:#ffd9d9 }
.logout:hover{ background:#ffeaea }

.icon{ display:block }
@media (max-width:900px){ .nav-btn{ padding:8px 12px; font-size:15px } }

/* ==== Dropdown link styling (royal blue text + icons) ==== */
.menu a{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 14px;
  border-radius:12px;
  font-weight:700;
  color:#4871db;
  text-decoration:none;
}
.menu a:link,
.menu a:visited{
  color:#4871db;
  text-decoration:none;
}
.menu a:hover{
  background:#f7f9ff;
  color:#4871db;
}
.menu a .icon,
.menu a svg{
  width:18px;
  height:18px;
}
.cch-modal-wrap{
  position:fixed; inset:0;
  display:none;
  align-items:center; justify-content:center;
  z-index:9999;
}
.cch-modal-wrap.open{ display:flex; }

.cch-modal-backdrop{
  position:absolute; inset:0;
  background:rgba(0,0,0,.45);
}

.cch-modal{
  position:relative;
  width:min(520px, calc(100% - 32px));
  background:#fff;
  border-radius:14px;
  box-shadow:0 18px 60px rgba(0,0,0,.25);
  border:1px solid #e8eaf2;
  padding:22px 22px 18px;
  text-align:center;
  z-index:2;
}

.cch-modal-icon{
  width:74px; height:74px;
  border-radius:50%;
  margin:4px auto 14px;
  display:grid; place-items:center;
  border:4px solid #35b7ff;
  color:#35b7ff;
  font-weight:900;
  font-size:34px;
  line-height:1;
}

.cch-modal-title{
  margin:0 0 8px;
  font-size:26px;
  font-weight:800;
  color:#3b3f49;
}

.cch-modal-text{
  margin:0 0 18px;
  color:#6b7280;
  font-size:14.5px;
  line-height:1.6;
}

.cch-modal-actions{
  display:flex;
  justify-content:center;
  gap:10px;
  flex-wrap:wrap;
  padding-top:4px;
}

.cch-btn{
  appearance:none;
  border:0;
  padding:10px 16px;
  border-radius:6px;
  font-weight:800;
  cursor:pointer;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}

.cch-btn.primary{
  background:#5b6dff;
  color:#fff;
  box-shadow:0 8px 18px rgba(91,109,255,.25);
}

.cch-btn.secondary{
  background:#6b7280;
  color:#fff;
}
.cch-btn:focus-visible{ outline:2px solid var(--sun); outline-offset:2px }

</style>
</head>
<!-- ===== Modal Popup (Discover Clubs + Close) ===== -->
<div id="cchModal" class="cch-modal-wrap" aria-hidden="true">
  <div class="cch-modal-backdrop" id="cchModalBackdrop"></div>

  <div class="cch-modal" role="dialog" aria-modal="true" aria-labelledby="cchModalTitle" aria-describedby="cchModalDesc">
    <div class="cch-modal-icon">i</div>

    <h2 class="cch-modal-title" id="cchModalTitle">You haven’t joined a club yet</h2>
    <p class="cch-modal-text" id="cchModalDesc">
      To access this page, you need to <strong>join a club</strong> first.
    </p>

    <div class="cch-modal-actions">
      <a id="cchModalPrimary" class="cch-btn primary" href="discoverclubs.php">Discover Clubs</a>
      <button id="cchModalClose" class="cch-btn secondary" type="button">Close</button>
    </div>
  </div>
</div>

<body>

<!-- ===== Top Bar ===== -->
<header class="topbar">
  <div class="topbar-inner">
    <!-- Left: Logo image -->
    <div class="brand">
      <img src="tools/pics/jd.png" alt="Campus Clubs Hub logo" />
    </div>

    <!-- Center: Primary nav with dropdowns -->
    <nav class="main-nav" aria-label="Primary">
      <button class="nav-btn" type="button" onclick="location.href='index.php'">Home</button>

      <!-- Clubs dropdown -->
      <div class="dropdown">
        <button class="nav-btn has-caret" type="button" aria-haspopup="true" aria-expanded="false">
          Clubs
          <svg class="caret" width="14" height="14" viewBox="0 0 24 24" fill="none"
               stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div class="menu" role="menu" aria-label="Clubs menu">

          <a href="myclubpage.php" role="menuitem">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 21v-4m8 4v-4M4 10h16M2 6h20v12H2z"/>
            </svg>
            My Club
          </a>

          <a href="memberspage.php" role="menuitem">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            My Club Members
          </a>

          <a href="clubsranking.php" role="menuitem">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 21V10"/>
              <path d="M12 21v-6"/>
              <path d="M16 21V6"/>
            </svg>
            Clubs Ranking
          </a>

          <a href="discoverclubs.php" role="menuitem">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="7"/>
              <path d="m21 21-4.3-4.3"/>
            </svg>
            Discover Clubs
          </a>

        </div>
      </div>

      <!-- Events dropdown -->
      <div class="dropdown">
        <button class="nav-btn has-caret" type="button" aria-haspopup="true" aria-expanded="false">
          Events
          <svg class="caret" width="14" height="14" viewBox="0 0 24 24" fill="none"
               stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div class="menu" role="menu" aria-label="Events menu">

          <a href="myclubevents.php" role="menuitem">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <path d="M16 2v4"/>
              <path d="M8 2v4"/>
              <path d="M3 10h18"/>
            </svg>
            My Club Events
          </a>

          <a href="allevents.php" role="menuitem">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <path d="M3 10h18"/>
              <path d="M7 14h.01"/>
              <path d="M11 14h.01"/>
              <path d="M15 14h.01"/>
            </svg>
            All Events
          </a>

          <!--  Create Event (blocked when club inactive) -->
          <a href="createevent.php"
             role="menuitem"
             id="createEventLink"
             data-club-status="<?= htmlspecialchars($clubStatus) ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Create Event
          </a>

        </div>
      </div>

      <!-- Other top-level items -->
      <button class="nav-btn" type="button" onclick="location.href='rewards.php'">Rewards</button>
      <button class="nav-btn" type="button" onclick="location.href='aboutus.php'">About Us</button>
    </nav>

    <!-- Right: User + sidebar toggle -->
    <div class="user-block">
      <div class="user-badge">
        <span class="user-icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="#f4df6d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21a8 8 0 1 0-16 0"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </span>
        <span id="activeUser" class="display"><?= htmlspecialchars($displayName) ?></span>
      </div>
      <button id="menuToggle" class="menu-btn" aria-label="Open menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="#f4df6d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>
</header>

<!-- ===== Sidebar ===== -->
<div class="sidebar-wrap" role="presentation">
  <div class="backdrop" id="backdrop"></div>
  <aside id="sidebarPanel" class="sidebar" role="dialog" aria-modal="true"
         aria-labelledby="sideTitle" aria-describedby="sideDesc">

    <!-- Sidebar header with edit button -->
    <div class="sidebar-header">
      <div class="avatar" aria-hidden="true">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none"
             stroke="#f4df6d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21a8 8 0 1 0-16 0"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
      </div>

      <div style="display:flex; flex-direction:column;">
        <div style="display:flex; align-items:center; gap:8px;">
          <div id="sideTitle" class="user-name display"><?= htmlspecialchars($displayName) ?></div>

          <!-- Edit profile button -->
          <a href="editprofile.php"
             style="display:flex; align-items:center; justify-content:center;
                    width:22px; height:22px; border-radius:6px;
                    border:2px solid var(--sun); background:#ffffff;
                    cursor:pointer; text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="#242751" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 20h9"/>
              <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
            </svg>
          </a>
        </div>

        <div class="points" id="points">
          <span aria-hidden="true">⭐</span>
          <span><strong><?= (int)$points ?></strong> pts</span>
        </div>
      </div>
    </div>

    <div class="sidebar-content" id="sideDesc">
      <button class="side-btn primary" type="button" onclick="location.href='clubpage.php'">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 21v-4m8 4v-4M4 10h16M2 6h20v12H2z"/>
        </svg>
        <span>My Club</span>
      </button>

       <!-- Scan Attendance -->
<button class="side-btn" type="button" onclick="location.href='qr_attendance.php'">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3z"></path>
    <path d="M15 15h3v3h-3zM18 18h3v3h-3zM18 15h3"></path>
  </svg>
  <span>Scan Attendance</span>
</button>

<!-- Show My QR -->
<button class="side-btn" type="button" onclick="location.href='show_qr.php'">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="7" height="7"></rect>
    <rect x="14" y="3" width="7" height="7"></rect>
    <rect x="3" y="14" width="7" height="7"></rect>
    <rect x="14" y="14" width="7" height="7"></rect>
  </svg>
  <span>Show My QR</span>
</button>


      <a class="side-btn logout" href="../logout.php" role="button">Logout</a>
    </div>
  </aside>
</div>

<script>
  // Sidebar toggle
  const body = document.body;
  document.getElementById('menuToggle').addEventListener('click',()=> body.classList.toggle('menu-open'));
  document.getElementById('backdrop').addEventListener('click',()=> body.classList.remove('menu-open'));
  document.addEventListener('keydown',e=>{ if(e.key==='Escape') body.classList.remove('menu-open'); });

   // ===== Modal helpers =====
function openCchModal(title, message, primaryHref){
  const wrap = document.getElementById('cchModal');
  const titleEl = document.getElementById('cchModalTitle');
  const descEl  = document.getElementById('cchModalDesc');
  const primary = document.getElementById('cchModalPrimary');

  titleEl.textContent = title;
  descEl.innerHTML = message; // allow <strong>
  primary.href = primaryHref || 'discoverclubs.php';

  wrap.classList.add('open');
  wrap.setAttribute('aria-hidden', 'false');
}

function closeCchModal(){
  const wrap = document.getElementById('cchModal');
  wrap.classList.remove('open');
  wrap.setAttribute('aria-hidden', 'true');
}

// close actions
document.getElementById('cchModalClose')?.addEventListener('click', closeCchModal);
document.getElementById('cchModalBackdrop')?.addEventListener('click', closeCchModal);
document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeCchModal(); });

//  Block Create Event if club inactive 
(function(){
  const link = document.getElementById('createEventLink');
  if(!link) return;

  link.addEventListener('click', function(e){
    const status = (link.dataset.clubStatus || '').toLowerCase().trim();
    if(status === 'inactive'){
      e.preventDefault();

      openCchModal(
        "Your club is inactive",
        "To access this page, please contact the <strong>University Administrator</strong> or join another club.",
        "discoverclubs.php"
      );

      return false;
    }
  });
})();


</script>

</body>
</html>

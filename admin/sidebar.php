<?php
  // Detect current page (e.g. "clubcreation.php")
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
/* ========== Sidebar ========== */
.sidebar-logo{
  display:flex;
  margin-bottom:20px;
}

.sidebar-logo img{
  width:100%;     /* adjust size if needed */
  height:auto;
}
.sidebar{
  width:var(--sidebarWidth);
  background:linear-gradient(180deg,#242751 0%,#292d56 60%,#232547 100%);
  color:#f9fafb;
  display:flex;
  flex-direction:column;
  padding:26px 20px;
  box-shadow:0 0 40px rgba(15,23,42,.55);
  position:fixed;
  top:0;
  left:0;
  height:100vh;
  z-index:2;
}

.sidebar-section{
  margin-bottom:32px;
}

.sidebar-title{
  font-weight:800;
  font-size:1.12rem;
  letter-spacing:.03em;
  margin-bottom:32px;
}

.sidebar-nav{
  display:flex;
  flex-direction:column;
  gap:10px;
  font-size:.97rem;
}

/* main nav item */
.nav-item{
  padding:9px 12px;
  border-radius:999px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  cursor:pointer;
  transition:background .18s ease, color .18s ease, transform .15s ease;
  text-decoration:none;
  color:inherit;
}

.nav-item span{pointer-events:none;}

.nav-item:hover{
  background:rgba(255,255,255,.08);
  transform:translateX(2px);
}

.nav-item.active{
  background:#ff5c5c;       /* Solid coral */
  color:#111827;
  font-weight:600;
  box-shadow:none;
  transform:none;
}

.nav-arrow{
  font-size:.78rem;
  opacity:.9;
}

/* dropdown group */
.nav-group{
  display:flex;
  flex-direction:column;
  position:relative;
}

.dropdown-menu{
  display:none;
  flex-direction:column;
  gap:6px;
  margin-top:4px;
  margin-left:14px;
}

.nav-group:hover .dropdown-menu{
  display:flex;
}

.nav-sub{
  padding:7px 10px;
  border-radius:12px;
  font-size:.9rem;
  color:#e5e7ff;
  text-decoration:none;
  background:rgba(255,255,255,.05);
  transition:background .18s ease, transform .15s ease;
}

.nav-sub:hover{
  background:rgba(255,255,255,.12);
  transform:translateX(2px);
}

/* active sub-link */
.nav-sub.active{
  background:#ff5c5c;
  color:#111827;
  font-weight:600;
}

/* logout */
.logout-btn{
  margin-top:auto;
  padding:10px 14px;
  border-radius:999px;
  font-weight:600;
  color:#ffffff;
  background:#ff5c5c;
  text-align:center;
  text-decoration:none;
  cursor:pointer;
  transition:background .18s ease;
}

.logout-btn:hover{
  background:#ff4949;
}
</style>

<aside class="sidebar">
  <div class="sidebar-section">

  <div class="sidebar-logo">
    <img src="tools/pics/adminlogo.png" alt="UniHive Logo">
  </div>

  <div class="sidebar-title">Admin Panel</div>


    <nav class="sidebar-nav">
      <!-- Home -->
      <a href="index.php"
         class="nav-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
        <span>Home</span>
      </a>

      <!-- Club management dropdown -->
      <?php
        $clubPages = ['clubcreation.php','clubedit.php','viewclubs.php','viewmembers.php'];
        $clubActive = in_array($currentPage, $clubPages);
      ?>
      <div class="nav-group">
        <div class="nav-item <?php echo $clubActive ? 'active' : ''; ?>">
          <span>Club Management</span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="dropdown-menu">
          <a href="clubcreation.php"
             class="nav-sub <?php echo $currentPage === 'clubcreation.php' ? 'active' : ''; ?>">
            Creation requests
          </a>
          <a href="clubedit.php"
             class="nav-sub <?php echo $currentPage === 'clubedit.php' ? 'active' : ''; ?>">
            Edit requests
          </a>
          <a href="viewclubs.php"
             class="nav-sub <?php echo $currentPage === 'viewclubs.php' ? 'active' : ''; ?>">
            View clubs
          </a>
        </div>
      </div>

      <!-- Events dropdown -->
      <?php
        $eventPages = ['event_creation_requests.php','event_edit_requests.php','upcomingevents.php'];
        $eventActive = in_array($currentPage, $eventPages);
      ?>
      <div class="nav-group">
        <div class="nav-item <?php echo $eventActive ? 'active' : ''; ?>">
          <span>Events</span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="dropdown-menu">
          <a href="event_creation_requests.php"
             class="nav-sub <?php echo $currentPage === 'event_creation_requests.php' ? 'active' : ''; ?>">
            Creation requests
          </a>
          <a href="event_edit_requests.php"
             class="nav-sub <?php echo $currentPage === 'event_edit_requests.php' ? 'active' : ''; ?>">
            Edit requests
          </a>
          <a href="upcomingevents.php"
             class="nav-sub <?php echo $currentPage === 'upcomingevents.php' ? 'active' : ''; ?>">
            Upcoming events
          </a>
        </div>
      </div>

      <!-- Sponsors -->
      <a href="sponsors.php"
         class="nav-item <?php echo $currentPage === 'sponsors.php' ? 'active' : ''; ?>">
        <span>Sponsors</span>
      </a>

      <!-- Students -->
      <a href="students.php"
         class="nav-item <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>">
        <span>Students</span>
      </a>

      <!-- News -->
      <a href="news.php"
         class="nav-item <?php echo $currentPage === 'news.php' ? 'active' : ''; ?>">
        <span>News management</span>
      </a>
    </nav>
  </div>

  <a href="login.php" class="logout-btn">Logout</a>
</aside>

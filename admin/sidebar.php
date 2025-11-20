  
   <style>
 /* ========== Sidebar ========== */
.sidebar{
  width:var(--sidebarWidth);
  background:linear-gradient(180deg,#242751 0%,#292d56 60%,#232547 100%);
  color:#f9fafb;
  display:flex;
  flex-direction:column;
  padding:26px 20px;
  box-shadow:0 0 40px rgba(15,23,42,.55);
  position:relative;
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
  background:linear-gradient(135deg,var(--pinkDeep),var(--pink));
  color:#111827;
  font-weight:600;
  box-shadow:0 10px 26px rgba(255,92,92,.55);
}

.nav-arrow{
  font-size:.78rem;
  opacity:.9;
}

    </style>
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-title">Admin panel</div>
    <nav class="sidebar-nav">
      <a href="index.php" class="nav-item active">
        <span>home</span>
      </a>

      <a href="clubs.php" class="nav-item">
        <span>Club management</span>
        <span class="nav-arrow">▾</span>
      </a>

      <a href="events.php" class="nav-item">
        <span>Events</span>
        <span class="nav-arrow">▾</span>
      </a>

      <a href="students.php" class="nav-item">
        <span>Students</span>
      </a>

      <a href="sponsors.php" class="nav-item">
        <span>Sponsors</span>
        <span class="nav-arrow">▾</span>
      </a>

      <a href="news.php" class="nav-item">
        <span>News management</span>
      </a>
    </nav>
  </div>
</aside>


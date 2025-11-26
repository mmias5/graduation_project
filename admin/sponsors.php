<?php
// Dummy data – later replace with DB data
$sponsors = [
    [
        "id" => 1,
        "name" => "TechCorp",
        "email" => "techcorp@company.com",
        "sponsoring" => "AI & Robotics Club"
    ],
    [
        "id" => 2,
        "name" => "FitLife",
        "email" => "contact@fitlife.com",
        "sponsoring" => "Campus Runners"
    ],
    [
        "id" => 3,
        "name" => "ArtWorks",
        "email" => "hello@artworks.com",
        "sponsoring" => "Creative Studio"
    ],
    [
        "id" => 4,
        "name" => "CarePlus",
        "email" => "partners@careplus.org",
        "sponsoring" => "Volunteer Circle"
    ],
    [
        "id" => 5,
        "name" => "StarLab",
        "email" => "team@starlab.com",
        "sponsoring" => "Astronomy Society events"
    ],
];

$totalSponsors = count($sponsors);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Sponsors</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --coral:#ff5e5e;
  --paper:#eef2f7;
  --card:#ffffff;
  --ink:#0e1228;
  --muted:#6b7280;
  --radius:22px;
  --shadow:0 14px 34px rgba(10,23,60,.12);

  --sidebarWidth:260px; /* for sidebar.php */
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

/* ===== Main content (sidebar is in sidebar.php) ===== */
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}

.header-row{
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  gap:16px;
  margin-bottom:20px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  color:var(--ink);
}

.total-count{
  font-size:.95rem;
  color:var(--muted);
}

/* Search + Add sponsor row */
.top-controls{
  display:flex;
  gap:14px;
  align-items:center;
  margin-bottom:26px;
}

.search-wrapper{
  flex:1;
  background:#ffffff;
  padding:14px 16px;
  border-radius:999px;
  box-shadow:0 10px 26px rgba(15,23,42,.18);
}

.search-input{
  width:100%;
  border:none;
  outline:none;
  font-size:.96rem;
  color:var(--ink);
}

.search-input::placeholder{
  color:#9ca3af;
}

.add-btn{
  padding:12px 20px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.9rem;
  font-weight:700;
  background:var(--coral);
  color:#ffffff;
  white-space:nowrap;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  /* NO glow */
  box-shadow:none;
}

.add-btn:hover{
  background:#ff4949; /* darker coral, but still no glow */
}


/* Sponsors grid (similar to members layout) */
.sponsors-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(360px,1fr));
  gap:18px 20px;
}

/* Card */
.sponsor-card{
  background:var(--card);
  border-radius:20px;
  box-shadow:0 16px 34px rgba(15,23,42,.16);
  padding:16px 18px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.sponsor-left{
  display:flex;
  flex-direction:column;
  gap:3px;
}

.sponsor-name{
  font-weight:800;
  font-size:1rem;
  color:var(--ink);
}

.sponsor-email{
  font-size:.9rem;
  color:var(--muted);
}

.sponsor-target{
  font-size:.9rem;
  color:var(--ink);
}

/* small label for “Sponsoring” */
.sponsor-target span{
  font-weight:600;
}

/* Right side (placeholder if you want future buttons) */
.sponsor-right{
  font-size:.8rem;
  color:var(--muted);
}

/* Responsive tweak */
@media(max-width:900px){
  .sponsors-grid{
    grid-template-columns:1fr;
  }
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">

  <div class="header-row">
    <div class="page-title">Sponsors</div>
    <div class="total-count"><?= $totalSponsors ?> total</div>
  </div>

  <div class="top-controls">
    <div class="search-wrapper">
      <input
        type="text"
        id="searchSponsors"
        class="search-input"
        placeholder="Search by sponsor name..."
      >
    </div>

    <a href="addsponsor.php" class="add-btn">
      + Add sponsor
    </a>
    <a href="registrationrequests.php" class="add-btn">
      + Registration requests
    </a>
  </div>

  <div class="sponsors-grid" id="sponsorsGrid">
    <?php foreach($sponsors as $s): ?>
      <div
        class="sponsor-card"
        data-name="<?= strtolower($s['name']); ?>"
      >
        <div class="sponsor-left">
          <div class="sponsor-name"><?= $s['name']; ?></div>
          <div class="sponsor-email"><?= $s['email']; ?></div>
          <div class="sponsor-target">
            <span>Sponsoring:</span> <?= $s['sponsoring']; ?>
          </div>
        </div>

        <div class="sponsor-right">
          ID <?= $s['id']; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
// ===== Search by sponsor name =====
const searchInput = document.getElementById('searchSponsors');
const sponsorCards = document.querySelectorAll('.sponsor-card');

searchInput.addEventListener('input', () => {
  const q = searchInput.value.toLowerCase().trim();

  sponsorCards.forEach(card => {
    const name = card.dataset.name;
    card.style.display = !q || name.includes(q) ? 'flex' : 'none';
  });
});
</script>

</body>
</html>

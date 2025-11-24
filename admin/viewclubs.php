<?php
// Dummy data – replace later with DB data
$clubs = [
    [
        "id" => 1,
        "name" => "AI & Robotics Club",
        "category" => "Technology",
        "sponsor" => "TechCorp",
        "description" => "Build robots, compete in challenges, and learn cutting-edge AI.",
        "members" => 120,
        "events"  => 15,
        "points"  => 555,
        "logo"    => "assets/club1.png",
        "active"  => true
    ],
    [
        "id" => 2,
        "name" => "Campus Runners",
        "category" => "Sports",
        "sponsor" => "FitLife",
        "description" => "Weekly runs, marathon training, and fitness challenges.",
        "members" => 98,
        "events"  => 22,
        "points"  => 610,
        "logo"    => "assets/club2.png",
        "active"  => true
    ],
    [
        "id" => 3,
        "name" => "Creative Studio",
        "category" => "Arts",
        "sponsor" => "ArtWorks",
        "description" => "Design, art, illustration and creative collaboration.",
        "members" => 140,
        "events"  => 18,
        "points"  => 530,
        "logo"    => "assets/club3.png",
        "active"  => false
    ],
    [
        "id" => 4,
        "name" => "Volunteer Circle",
        "category" => "Community",
        "sponsor" => "CarePlus",
        "description" => "Volunteer projects and charity events across campus.",
        "members" => 200,
        "events"  => 35,
        "points"  => 890,
        "logo"    => "assets/club4.png",
        "active"  => true
    ],
    [
        "id" => 5,
        "name" => "Astronomy Society",
        "category" => "Science",
        "sponsor" => "StarLab",
        "description" => "Stargazing nights, telescopes and science talks.",
        "members" => 85,
        "events"  => 12,
        "points"  => 410,
        "logo"    => "assets/club5.png",
        "active"  => true
    ]
];

// Build list of unique categories for the filter
$categories = array_unique(array_map(fn($c) => $c["category"], $clubs));
sort($categories);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — View Clubs</title>
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

.page-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:24px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  color:var(--ink);
}

/* ===== Filters row ===== */
.filters-row{
  display:flex;
  gap:14px;
  align-items:center;
  margin-bottom:24px;
}

.search-input{
  flex:1;
  min-width:260px;
  padding:11px 16px;
  border-radius:999px;
  border:none;
  outline:none;
  font-size:.95rem;
  background:#ffffff;
  box-shadow:0 8px 22px rgba(15,23,42,.12);
  color:var(--ink);
}

.search-input::placeholder{
  color:#9ca3af;
}

/* Category dropdown button */
.category-filter{
  position:relative;
}

.category-btn{
  min-width:190px;
  padding:11px 16px;
  border-radius:999px;
  border:none;
  background:#ffffff;
  box-shadow:0 8px 22px rgba(15,23,42,.12);
  display:flex;
  justify-content:space-between;
  align-items:center;
  cursor:pointer;
  font-size:.95rem;
  color:var(--ink);
}

.category-btn span:first-child{
  font-weight:500;
}

.category-arrow{
  font-size:.9rem;
  opacity:.7;
}

.category-menu{
  position:absolute;
  top:110%;
  left:0;
  width:100%;
  background:#ffffff;
  border-radius:18px;
  box-shadow:0 16px 38px rgba(15,23,42,.18);
  padding:8px 0;
  display:none;
  z-index:10;
}

.category-menu.open{
  display:block;
}

.category-option{
  width:100%;
  padding:9px 16px;
  text-align:left;
  border:none;
  background:transparent;
  font-size:.93rem;
  cursor:pointer;
  color:var(--ink);
  transition:background .15s ease;
}

.category-option:hover{
  background:#f3f4f6;
}

/* ===== Cards grid ===== */
.cards-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:22px;
}

/* Club card (admin palette only) */
.club-card{
  background:var(--card);
  border-radius:20px;
  box-shadow:0 18px 40px rgba(15,23,42,.14);
  padding:18px 20px 16px;
  border:2px solid rgba(36,39,81,0.07); /* subtle navy border */
}

/* Active state — stronger navy border */
.club-card.active{
  border-color:var(--navy);
}

/* Card top */
.card-top{
  display:flex;
  justify-content:space-between;
  gap:12px;
  margin-bottom:10px;
}

.card-main{
  display:flex;
  gap:14px;
}

.club-logo{
  width:52px;
  height:52px;
  border-radius:50%;
  background:#e5e7eb;
  object-fit:cover;
  flex-shrink:0;
}

.club-text{
  display:flex;
  flex-direction:column;
  gap:2px;
}

.club-name{
  font-weight:800;
  font-size:1.05rem;
  color:var(--ink);
}

.club-sponsor{
  font-size:.9rem;
  color:var(--muted);
}

.club-sponsor span{
  color:var(--coral);
  font-weight:600;
}

/* Category pill — navy themed */
.category-pill{
  align-self:flex-start;
  padding:6px 14px;
  border-radius:999px;
  border:2px solid var(--navy);
  color:var(--navy);
  font-size:.86rem;
  font-weight:600;
  background:#e3e4f4; /* light navy-ish */
}

/* Description */
.club-desc{
  font-size:.93rem;
  color:var(--muted);
  margin-bottom:10px;
}

/* Stats row */
.stats-row{
  display:flex;
  gap:18px;
  font-size:.88rem;
  font-weight:600;
  color:var(--ink);
  margin-bottom:12px;
}

.stats-row span{
  color:var(--muted);
  font-weight:500;
}

/* Action buttons */
.actions-row{
  display:flex;
  justify-content:space-between;
  gap:10px;
  align-items:center;
}

.btn-group-left{
  display:flex;
  gap:8px;
}

.card-btn{
  padding:8px 14px;
  border-radius:999px;
  font-size:.86rem;
  font-weight:600;
  border:none;
  cursor:pointer;
  transition:background .15s ease, color .15s ease, transform .1s ease;
}

/* Delete = coral */
.btn-delete{
  background:#ffe3e3;
  color:#b91c1c;
}

.btn-delete:hover{
  background:var(--coral);
  color:#ffffff;
  transform:translateY(-1px);
}

/* Activate / Inactivate buttons */
.btn-status{
  background:#dfe2f0;
  color:var(--navy);
}

.btn-status.inactive{
  background:#f3f4f6;
  color:#6b7280;
}

.btn-status:hover{
  background:var(--navy);
  color:#ffffff;
  transform:translateY(-1px);
}

/* View members */
.btn-view-members{
  background:var(--navy);
  color:#ffffff;
  text-decoration:none;
}

.btn-view-members:hover{
  background:#191c3a;
  transform:translateY(-1px);
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-header">
    <div class="page-title">View Clubs</div>
  </div>

  <!-- Filters -->
  <div class="filters-row">
    <input
      type="text"
      id="searchClubs"
      class="search-input"
      placeholder="Search clubs..."
    >

    <div class="category-filter">
      <button type="button" id="categoryBtn" class="category-btn">
        <span id="categoryLabel">All categories</span>
        <span class="category-arrow">▾</span>
      </button>

      <div id="categoryMenu" class="category-menu">
        <button class="category-option" data-category="all">All categories</button>
        <?php foreach($categories as $cat): ?>
          <button class="category-option" data-category="<?= htmlspecialchars($cat); ?>">
            <?= htmlspecialchars($cat); ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Cards grid -->
  <div class="cards-grid" id="cardsGrid">
    <?php foreach($clubs as $club): ?>
      <div
        class="club-card <?= $club['active'] ? 'active' : '' ?>"
        data-name="<?= strtolower($club['name']); ?>"
        data-category="<?= htmlspecialchars($club['category']); ?>"
      >
        <div class="card-top">
          <div class="card-main">
            <img src="<?= $club['logo']; ?>" alt="Club logo" class="club-logo">
            <div class="club-text">
              <div class="club-name"><?= $club['name']; ?></div>
              <div class="club-sponsor">
                Sponsored by <span><?= $club['sponsor']; ?></span>
              </div>
            </div>
          </div>
          <div class="category-pill"><?= $club['category']; ?></div>
        </div>

        <div class="club-desc">
          <?= $club['description']; ?>
        </div>

        <div class="stats-row">
          <div><span>Members:</span> <?= $club['members']; ?></div>
          <div><span>Events:</span> <?= $club['events']; ?></div>
          <div><span>Points:</span> <?= $club['points']; ?></div>
        </div>

        <div class="actions-row">
          <div class="btn-group-left">
            <button class="card-btn btn-delete" type="button">Delete</button>

            <button
              class="card-btn btn-status <?= $club['active'] ? '' : 'inactive'; ?>"
              type="button"
              data-active="<?= $club['active'] ? '1' : '0'; ?>"
            >
              <?= $club['active'] ? 'Inactivate' : 'Activate'; ?>
            </button>
          </div>

          <a
            href="viewmembers.php?club_id=<?= $club['id']; ?>"
            class="card-btn btn-view-members"
          >
            View members
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
// ===== Category dropdown toggle =====
const categoryBtn = document.getElementById('categoryBtn');
const categoryMenu = document.getElementById('categoryMenu');
const categoryLabel = document.getElementById('categoryLabel');

categoryBtn.addEventListener('click', () => {
  categoryMenu.classList.toggle('open');
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
  if (!categoryBtn.contains(e.target) && !categoryMenu.contains(e.target)) {
    categoryMenu.classList.remove('open');
  }
});

// ===== Filtering =====
const searchInput = document.getElementById('searchClubs');
const cards = document.querySelectorAll('.club-card');
let selectedCategory = 'all';

function applyFilters() {
  const query = searchInput.value.toLowerCase().trim();

  cards.forEach(card => {
    const name = card.dataset.name;
    const category = card.dataset.category;

    const matchesName = !query || name.includes(query);
    const matchesCategory = selectedCategory === 'all' || category === selectedCategory;

    if (matchesName && matchesCategory) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

searchInput.addEventListener('input', applyFilters);

document.querySelectorAll('.category-option').forEach(btn => {
  btn.addEventListener('click', () => {
    selectedCategory = btn.dataset.category;
    categoryLabel.textContent = selectedCategory === 'all' ? 'All categories' : btn.textContent;
    categoryMenu.classList.remove('open');
    applyFilters();
  });
});

// ===== Activate / Inactivate toggle (front-end only) =====
document.querySelectorAll('.btn-status').forEach(btn => {
  btn.addEventListener('click', () => {
    const isActive = btn.dataset.active === '1';
    btn.dataset.active = isActive ? '0' : '1';
    btn.textContent = isActive ? 'Activate' : 'Inactivate';
    btn.classList.toggle('inactive', !isActive);

    const card = btn.closest('.club-card');
    if (card) {
      card.classList.toggle('active', !card.classList.contains('active'));
    }

    // Later: send AJAX / form request to update DB.
  });
});

// ===== Delete button (front-end only) =====
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', () => {
    if (confirm('Are you sure you want to delete this club?')) {
      const card = btn.closest('.club-card');
      if (card) card.remove();
      // Later: send request to server to actually delete.
    }
  });
});
</script>

</body>
</html>

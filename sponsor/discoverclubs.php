<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || ($_SESSION['role'] ?? '') !== 'sponsor') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

/* =========================
   Fetch categories (Top 10) for dropdown
========================= */
$categories = [];
$catSql = "
  SELECT category, COUNT(*) AS cnt
  FROM club
  WHERE club_id <> 1
    AND category IS NOT NULL
    AND category <> ''
  GROUP BY category
  ORDER BY cnt DESC
  LIMIT 10
";
$catRes = $conn->query($catSql);
if ($catRes) {
    while ($r = $catRes->fetch_assoc()) {
        $categories[] = $r['category'];
    }
}

/* =========================
   Fetch ALL clubs (no filtering here)
   Filtering will be LIVE on the frontend (JS)
========================= */
$sql = "
    SELECT 
        club_id,
        club_name,
        category,
        description,
        logo,
        member_count,
        points,
        status
    FROM club
    WHERE club_id <> 1
    ORDER BY points DESC
";
$result = $conn->query($sql);
if (!$result) {
    die('SQL error in discoverclubs: ' . $conn->error);
}

$clubs = [];
while ($row = $result->fetch_assoc()) {
    $clubs[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Discover Clubs</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#242751;
    --royal:#4871db;
    --gold:#e5b758;
    --lightGold:#f4df6d;
    --paper:#EEF2F7;
    --card:#ffffff;
    --ink:#0e1228;
    --muted:#6b7280;
    --shadow:0 14px 34px rgba(10,23,60,.12);
    --radius:18px;

    --okBg:#ecfdf3;
    --okText:#067647;
    --badBg:#fff1f2;
    --badText:#9f1239;
  }

  body{
    margin:0;
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:
      radial-gradient(1200px 700px at -10% 40%, rgba(168,186,240,.3), transparent 60%),
      radial-gradient(900px 600px at 110% 60%, rgba(72,113,219,.22), transparent 60%),
      var(--paper);
    background-repeat:no-repeat;
  }

  .wrapper{
    max-width:1100px;
    margin:32px auto 48px;
    padding:0 18px;
  }

  .page-title{
    font-size:30px;
    font-weight:800;
    color:var(--navy);
    margin:10px 0 6px;
    text-align:left;
  }

  .page-title::after{
    content:"";
    display:block;
    width:200px;
    height:6px;
    border-radius:999px;
    margin-top:10px;
    background:linear-gradient(90deg,var(--gold),var(--lightGold));
  }

  .subtle{
    color:var(--muted);
    margin:8px 0 22px;
    font-size:15px;
  }

  .section{
    margin:10px 0;
  }

  .section h2{
    font-size:20px;
    margin:0 0 12px;
    color:var(--navy);
    font-weight:800;
  }

  /* ===== Filters row ===== */
  .filters{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
    margin:10px 0 18px;
  }

  /* make search & category same height */
  .field{
    background:rgba(255,255,255,.9);
    border:1px solid rgba(36,39,81,.15);
    border-radius:16px;
    padding:12px 14px;
    box-shadow:0 12px 26px rgba(10,23,60,.10);
    display:flex;
    align-items:center;
    gap:12px;
    height:56px;
  }

  .field input{
    border:none;
    outline:none;
    background:transparent;
    font-family:inherit;
    font-size:15px;
    color:var(--ink);
    width:240px;
  }

  .btn-clear{
    border:none;
    cursor:pointer;
    padding:12px 18px;
    height:56px;
    border-radius:999px;
    font-weight:800;
    font-family:inherit;
    background:linear-gradient(90deg,var(--gold),var(--lightGold));
    color:var(--navy);
    box-shadow:0 10px 22px rgba(229,183,88,.25);
  }

  /* ===== Custom Category Dropdown ===== */
  .category-wrap{ position:relative; }

  .category-dropdown{
    position:relative;
    width:240px;
  }

  .cat-selected{
    background:#fff;
    padding:12px 14px;
    height:56px;
    border-radius:16px;
    font-weight:700;
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    align-items:center;
    user-select:none;
    width:100%;
    box-sizing:border-box;
  }

  .cat-selected .chev{
    font-size:13px;
    margin-left:12px;
    color:var(--navy);
  }

  .cat-list{
    position:absolute;
    top:110%;
    left:0;
    width:100%;
    background:#fff;
    border-radius:14px;
    box-shadow:0 18px 40px rgba(0,0,0,.15);
    list-style:none;
    padding:6px;
    margin:0;
    display:none;
    max-height:240px;
    overflow:auto;
    z-index:999;
    border:1px solid rgba(36,39,81,.12);
  }

  .category-dropdown.open .cat-list{ display:block; }

  .cat-list li{
    padding:10px 12px;
    border-radius:10px;
    cursor:pointer;
    font-weight:700;
    color:var(--ink);
    transition:background .12s ease;
  }

  .cat-list li:hover{
    background:linear-gradient(90deg,var(--gold),var(--lightGold));
  }

  .cat-list li.active{
    background:rgba(72,113,219,.12);
  }

  .grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
  }

  @media (max-width:800px){
    .grid{ grid-template-columns:1fr; }
    .field input{ width:170px; }
    .category-dropdown{ width:170px; }
  }

  .card{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:18px;
    display:grid;
    grid-template-columns:80px 1fr;
    gap:14px;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    border:2px solid transparent;
  }

  .card:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 38px rgba(12,22,60,.16);
    border-color:var(--gold);
  }

  .club-logo{
    width:80px;
    height:80px;
    border-radius:20px;
    background:#e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    border:2px solid rgba(36,39,81,.1);
    font-size:20px;
    font-weight:800;
    color:var(--navy);
  }
  .club-logo img{
    width:100%;
    height:100%;
    object-fit:cover;
  }

  .topline{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
  }

  .badge{
    background:#e8f5ff;
    color:#135f9b;
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.06em;
  }

  .status-badge{
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.06em;
  }
  .status-active{ background:var(--okBg); color:var(--okText); }
  .status-inactive{ background:var(--badBg); color:var(--badText); }

  .title{
    margin:6px 0 4px;
    font-weight:800;
    font-size:18px;
    color:var(--ink);
  }

  .desc{
    font-size:13px;
    color:var(--muted);
    margin-bottom:6px;
    max-height:40px;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .meta{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    font-size:12px;
    color:var(--muted);
    margin-top:4px;
  }

  .meta-strong{
    font-weight:700;
    color:#111827;
  }

  /* empty state */
  .no-results{
    display:none;
    padding:16px;
    color:var(--muted);
  }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="wrapper">
  <h1 class="page-title">Discover New Clubs</h1>
  <p class="subtle">
    Browse student clubs you are not sponsoring yet and spot high-potential communities to support.
  </p>

  <!-- ===== Filters UI (NO form submit / no reload) ===== -->
  <div class="filters">
    <div class="field" title="Search by club name">
      <span style="font-weight:800;color:var(--navy);">Search</span>
      <input
        type="text"
        id="searchInput"
        placeholder="Type a club name..."
        autocomplete="off"
      />
    </div>

    <!-- Custom styled dropdown -->
    <div class="field category-wrap" title="Filter by category">
      <span style="font-weight:800;color:var(--navy);">Category</span>

      <div class="category-dropdown" id="catDropdown">
        <div class="cat-selected" id="catSelected">
          All Categories
          <span class="chev">▾</span>
        </div>

        <ul class="cat-list" id="catList">
          <li data-value="all" class="active">All Categories</li>
          <?php foreach ($categories as $c): ?>
            <li data-value="<?php echo htmlspecialchars(mb_strtolower($c)); ?>">
              <?php echo htmlspecialchars($c); ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <button class="btn-clear" type="button" id="clearBtn">Clear</button>
  </div>

  <section class="section">
    <h2>Clubs you can sponsor</h2>

    <p class="no-results" id="noResults">No clubs match your search/filter right now.</p>

    <div class="grid" id="clubsGrid">
      <?php foreach ($clubs as $club): ?>
        <?php
          $clubId      = (int)$club['club_id'];
          $name        = $club['club_name'];
          $category    = $club['category'] ?? 'General';
          $desc        = $club['description'] ?? '';
          $logo        = $club['logo'] ?? '';
          $members     = (int)($club['member_count'] ?? 0);
          $points      = (int)($club['points'] ?? 0);

          $statusRaw   = strtolower(trim($club['status'] ?? 'inactive'));
          $isActive    = in_array($statusRaw, ['active', '1', 'enabled'], true);
          $statusText  = $isActive ? 'Active' : 'Inactive';

          $initials    = mb_strtoupper(mb_substr($name, 0, 2));

          $dataName    = mb_strtolower($name);
          $dataCat     = mb_strtolower($category);
          $dataStatus  = $isActive ? 'active' : 'inactive';
        ?>

        <article
          class="card"
          data-href="clubpage.php?id=<?php echo $clubId; ?>"
          data-name="<?php echo htmlspecialchars($dataName); ?>"
          data-category="<?php echo htmlspecialchars($dataCat); ?>"
          data-status="<?php echo $dataStatus; ?>"
          role="link"
          tabindex="0"
          aria-label="Open club page: <?php echo htmlspecialchars($name); ?>">

          <div class="club-logo">
            <?php if (!empty($logo)): ?>
              <img src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($name); ?> logo">
            <?php else: ?>
              <?php echo htmlspecialchars($initials); ?>
            <?php endif; ?>
          </div>

          <div>
            <div class="topline">
              <span class="badge"><?php echo htmlspecialchars($category); ?></span>
              <span class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>">
                <?php echo $statusText; ?>
              </span>
            </div>

            <div class="title"><?php echo htmlspecialchars($name); ?></div>

            <?php if (!empty($desc)): ?>
              <div class="desc"><?php echo htmlspecialchars($desc); ?></div>
            <?php endif; ?>

            <div class="meta">
              <span><span class="meta-strong"><?php echo $members; ?></span> members</span>
              <span><span class="meta-strong"><?php echo $points; ?></span> points</span>
            </div>
          </div>

        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>

<script>
(function(){
  // Keep card click behavior
  function shouldIgnore(target){
    const interactive = ['A','BUTTON','INPUT','SELECT','TEXTAREA','LABEL','SVG','PATH'];
    return interactive.includes(target.tagName);
  }

  document.addEventListener('click', (e) => {
    const card = e.target.closest('.card[data-href]');
    if (!card) return;
    if (shouldIgnore(e.target)) return;
    const url = card.getAttribute('data-href');
    if (url) window.location.href = url;
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const card = e.target.closest('.card[data-href]');
    if (!card) return;
    e.preventDefault();
    const url = card.getAttribute('data-href');
    if (url) window.location.href = url;
  });

  // ===== Live filter (NO reload) =====
  const searchInput = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearBtn');
  const cards = Array.from(document.querySelectorAll('.card[data-name]'));
  const noResults = document.getElementById('noResults');

  // Dropdown
  const dd = document.getElementById('catDropdown');
  const selected = document.getElementById('catSelected');
  const list = document.getElementById('catList');
  let currentCategory = 'all';

  function applyFilter(){
    const q = (searchInput.value || '').trim().toLowerCase();
    let visible = 0;

    cards.forEach(card => {
      const name = card.dataset.name || '';
      const cat  = card.dataset.category || '';

      const okName = q === '' || name.includes(q);
      const okCat  = currentCategory === 'all' || cat === currentCategory;

      const show = okName && okCat;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    noResults.style.display = visible === 0 ? 'block' : 'none';
  }

  // Smooth typing (debounce)
  let timer = null;
  searchInput.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(applyFilter, 120);
  });

  // Dropdown toggle
  selected.addEventListener('click', (e) => {
    e.stopPropagation();
    dd.classList.toggle('open');
  });

  // Choose category
  list.querySelectorAll('li').forEach(li => {
    li.addEventListener('click', () => {
      list.querySelectorAll('li').forEach(x => x.classList.remove('active'));
      li.classList.add('active');

      currentCategory = li.dataset.value;
      selected.childNodes[0].textContent = li.textContent + " ";
      dd.classList.remove('open');
      applyFilter();
    });
  });

  document.addEventListener('click', () => dd.classList.remove('open'));

  // Clear
  clearBtn.addEventListener('click', () => {
    searchInput.value = '';
    currentCategory = 'all';
    selected.childNodes[0].textContent = "All Categories ";
    list.querySelectorAll('li').forEach(x => x.classList.remove('active'));
    list.querySelector('li[data-value="all"]').classList.add('active');
    applyFilter();
    searchInput.focus();
  });

  // initial
  applyFilter();
})();
</script>

</body>
</html>

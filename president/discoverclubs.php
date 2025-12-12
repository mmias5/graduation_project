<?php
session_start();
//user file
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$student_id = $_SESSION['student_id'];

// ============================
// 1) نحضر club_id الخاص بالطالب
// ============================
$student_club_id = 1; // default = No Club / Not Assigned
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($student_club_id_db);
    if ($stmt->fetch()) {
        $student_club_id = (int)$student_club_id_db;
    }
    $stmt->close();
}

// ============================
// 2) نحضر كل الأندية (active) من جدول club
// ============================
$clubs = [];
$categories_map = [];

$sql = "
    SELECT club_id, club_name, description, category, logo, member_count, points, status
    FROM club
    WHERE status = 'active' AND club_id <> 1
    ORDER BY club_name
";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $clubs[] = $row;
        if (!empty($row['category'])) {
            $categories_map[$row['category']] = true;
        }
    }
}

$categories = array_keys($categories_map);
sort($categories);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Discover Clubs</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<?php include 'header.php'; ?>

<style>
  :root{
    --c-navy:#2B3751;
    --c-blue:#4871DB;
    --c-yellow:#F6E578;
    --c-ice:#E9ECEF;
    --radius:18px;
    --shadow:0 10px 28px rgba(16,24,40,.10);
  }

  @font-face{
    font-family:"Extenda 90 Exa";
    src:url("assets/fonts/Extenda90Exa.woff2") format("woff2");
    font-weight:700;
    font-style:normal;
    font-display:swap;
  }

  html,body{
    margin:0;height:100%;
    background:
      radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
      radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%);
    background-repeat:no-repeat;
    background-size:cover;
    font-family:"Raleway",sans-serif;
    color:var(--c-navy);
  }

  .clubs{
    background:inherit;
    margin-top:-3px;
    padding:24px 0 56px;
    position:relative;
  }
  .clubs::before{
    content:""; position:absolute; left:0; right:0; top:-10px; height:12px; background:inherit;
  }

  .clubs .container{
    width:min(1120px,92%);
    margin-inline:auto;
  }

  .clubs-title{
    margin:0 0 14px;
    text-align:center;
    font-family:"Raleway";
    font-size:clamp(22px,3vw,30px);
    font-weight:800;
  }

  .clubs-toolbar{
    display:grid;
    grid-template-columns:1fr 210px;
    gap:10px;
    width:min(900px,100%);
    margin:0 auto 16px;
  }

  .input{
    height:46px;
    border-radius:12px;
    border:2px solid #DEE6FB;
    padding:0 14px;
    font-size:15px;
    box-shadow:inset 0 -2px 0 rgba(0,0,0,.03);
  }

  .input:focus{
    outline: none;
    border-color: var(--c-blue);
    box-shadow: 0 0 0 3px rgba(72,113,219,.15);
  }

  .cat-dd{
    position:relative;
    width:210px;
  }

  .cat-btn{
    width:100%; height:46px;
    background:#fff;
    border-radius:12px;
    border:2px solid #DEE6FB;
    padding:0 14px;
    font-weight:700;
    font-size:15px;
    color:var(--c-navy);
    display:flex;
    justify-content:space-between;
    align-items:center;
    cursor:pointer;
    box-shadow:inset 0 -2px 0 rgba(0,0,0,.03);
  }

  .arrow{ font-size:16px; }

  .cat-menu{
    position:absolute;
    top:54px; left:0;
    width:100%;
    background:#fff;
    border-radius:16px;
    padding:8px;
    box-shadow:0 10px 30px rgba(0,0,0,.15);
    display:none;
    z-index:100;
  }

  .cat-item{
    padding:12px 14px;
    border-radius:12px;
    font-weight:700;
    cursor:pointer;
  }

  .cat-item:hover{
    background:#f5f7ff;
  }

  .cat-item.active{
    background:#eef3ff;
    border:1px solid #d5dfff;
  }

  @media(max-width:680px){
    .clubs-toolbar{ grid-template-columns:1fr; }
    .cat-dd{ width:100%; }
  }

  .club-grid{
    display:grid;
    gap:16px;
    grid-template-columns:repeat(auto-fit, minmax(280px,1fr));
  }

  .club-card-link{
    display:block; text-decoration:none; color:inherit;
  }

  .club-card{
    height:200px;
    background:#fff;
    border:3px solid var(--c-yellow);
    border-radius:16px;
    padding:12px;
    box-shadow:var(--shadow);
    transition:.15s ease;
    display:flex;
    flex-direction:column;
  }

  .club-card:hover{
    transform:translateY(-2px);
    border-color:var(--c-blue);
  }

  .club-head{
    display:flex; justify-content:space-between; align-items:center;
  }

  .club-id{ display:flex; align-items:center; gap:10px; }
  .club-logo{
    width:44px; height:44px;
    border-radius:50%;
    border:3px solid var(--c-blue);
    object-fit:cover;
  }

  .club-title h3{
    margin:0; font-family:"Raleway"; font-size:18px; font-weight:800;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }

  .club-title small{ font-size:11px; color:#76829F; }
  .sponsor{ color:var(--c-blue); font-weight:800; }

  .category-chip{
    background:#F0F3FA;
    border:2px solid var(--c-blue);
    padding:4px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
  }

  .club-desc{
    margin:6px 2px 8px;
    font-size:13px;
    line-height:1.45;
    overflow:hidden;
    line-clamp: 2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .club-meta{
    margin-top:auto;
    display:flex; flex-wrap:wrap; gap:10px 16px;
    font-size:12px; font-weight:700;
    color:#55617B;
  }
</style>

<section class="clubs">
  <div class="container">

    <h2 class="clubs-title">Your Next Adventure Starts Here — Join a Club You’ll Love!</h2>

    <!-- Toolbar -->
    <div class="clubs-toolbar">
      <input id="clubSearch" class="input" placeholder="Search clubs…" />

      <div class="cat-dd">
        <button id="catBtn" class="cat-btn">
          <span id="catLabel">All categories</span>
          <span class="arrow">▾</span>
        </button>

        <div id="catMenu" class="cat-menu">
          <div class="cat-item active" data-value="all">All categories</div>
          <?php foreach ($categories as $cat): ?>
            <div class="cat-item" data-value="<?php echo htmlspecialchars($cat); ?>">
              <?php echo htmlspecialchars($cat); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Clubs Grid -->
    <div class="club-grid" id="clubGrid">
      <?php if (empty($clubs)): ?>
        <p>No clubs available yet.</p>
      <?php else: ?>
        <?php foreach ($clubs as $club): 
          $cat = $club['category'] ?: 'Other';
          $logo = !empty($club['logo']) ? $club['logo'] : 'assets/images/clubs/default.png';
        ?>
          <a class="club-card-link"
             href="clubpage.php?club_id=<?php echo (int)$club['club_id']; ?>"
             data-category="<?php echo htmlspecialchars($cat); ?>">
            <article class="club-card">
              <div class="club-head">
                <div class="club-id">
                  <img class="club-logo"
                       src="<?php echo htmlspecialchars($logo); ?>"
                       alt="<?php echo htmlspecialchars($club['club_name']); ?>">
                  <div class="club-title">
                    <h3><?php echo htmlspecialchars($club['club_name']); ?></h3>
                    <small><?php echo htmlspecialchars($cat); ?></small>
                  </div>
                </div>
                <span class="category-chip"><?php echo htmlspecialchars($cat); ?></span>
              </div>
              <p class="club-desc">
                <?php echo htmlspecialchars($club['description']); ?>
              </p>
              <div class="club-meta">
                <span>Members: <?php echo (int)$club['member_count']; ?></span>
                <span>Points: <?php echo (int)$club['points']; ?></span>
              </div>
            </article>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
  let selectedCategory = "all";

  const searchInput = document.getElementById("clubSearch");
  const clubCards = [...document.querySelectorAll(".club-card-link")];

  const catBtn = document.getElementById("catBtn");
  const catMenu = document.getElementById("catMenu");
  const catLabel = document.getElementById("catLabel");
  const catItems = document.querySelectorAll(".cat-item");

  catBtn.addEventListener("click", () => {
    catMenu.style.display = catMenu.style.display === "block" ? "none" : "block";
  });

  document.addEventListener("click", (e) => {
    if (!catBtn.contains(e.target) && !catMenu.contains(e.target)) {
      catMenu.style.display = "none";
    }
  });

  catItems.forEach(item => {
    item.addEventListener("click", () => {
      catItems.forEach(i => i.classList.remove("active"));
      item.classList.add("active");

      selectedCategory = item.dataset.value;
      catLabel.textContent = item.textContent;

      catMenu.style.display = "none";
      applyFilters();
    });
  });

  function applyFilters(){
    const q = searchInput.value.toLowerCase().trim();

    clubCards.forEach(card => {
      const name = card.querySelector("h3").textContent.toLowerCase();
      const cat = card.dataset.category;

      const matchText = !q || name.includes(q);
      const matchCat  = selectedCategory === "all" || selectedCategory === cat;

      card.style.display = (matchText && matchCat) ? "" : "none";
    });
  }

  searchInput.addEventListener("input", applyFilters);
</script>

<?php include 'footer.php'; ?>
</body>
</html>

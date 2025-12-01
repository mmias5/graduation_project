<?php
session_start();

if (!isset($_SESSION['sponsor_id']) || $_SESSION['role'] !== 'sponsor') {
    header('Location: ../login.php');
    exit;
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
    --royal:#4871DB;
    --gold:#E5B758;
    --paper:#EEF2F7;
    --card:#ffffff;
    --muted:#6b7280;
    --radius:18px;
    --shadow:0 10px 28px rgba(10,23,60,.12);
  }

  @font-face{
    font-family:"Extenda 90 Exa";
    src:url("assets/fonts/Extenda90Exa.woff2") format("woff2");
    font-weight:700;
    font-style:normal;
    font-display:swap;
  }

  html,body{
    margin:0;
    height:100%;
    font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--navy);
    background:
      radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.32), transparent 60%),
      radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
      var(--paper);
    background-repeat:no-repeat;
  }

  /* ===== Discover Clubs container ===== */
  .clubs{
    padding:36px 0 60px;
  }
  .clubs .container{
    width:min(1120px,92%);
    margin-inline:auto;
  }

  .clubs-title{
    margin:0 0 18px;
    text-align:center;
    font-size:clamp(24px,3vw,30px);
    font-weight:800;
    color:var(--navy);
  }
  .clubs-title::after{
    content:"";
    display:block;
    width:190px;
    height:6px;
    border-radius:999px;
    margin:10px auto 0;
    background:linear-gradient(90deg,#e5b758,#f4df6d);
  }

  /* ===== Toolbar: search + dropdown ===== */
  .clubs-toolbar{
    display:grid;
    grid-template-columns:1fr 210px;
    gap:10px;
    width:min(900px,100%);
    margin:0 auto 22px;
  }

  .input{
    height:46px;
    border-radius:999px;
    border:1px solid #d5d9ea;
    padding:0 16px;
    font-size:15px;
    box-shadow:0 4px 12px rgba(72,113,219,.08);
    background:#ffffff;
  }
  .input::placeholder{
    color:#9ba3c3;
  }
  .input:focus{
    outline:none;
    border-color:var(--navy);
    box-shadow:0 0 0 3px rgba(72,113,219,.20);
  }

  /* Custom dropdown */
  .cat-dd{
    position:relative;
    width:210px;
  }

  .cat-btn{
    width:100%;
    height:46px;
    background:#fff;
    border-radius:999px;
    border:1px solid #d5d9ea;
    padding:0 16px;
    font-weight:700;
    font-size:15px;
    color:var(--navy);
    display:flex;
    justify-content:space-between;
    align-items:center;
    cursor:pointer;
    box-shadow:0 4px 12px rgba(72,113,219,.08);
  }

  .arrow{
    font-size:14px;
    color:var(--navy);
  }

  .cat-menu{
    position:absolute;
    top:52px;
    left:0;
    width:100%;
    background:#fff;
    border-radius:16px;
    padding:8px;
    box-shadow:0 12px 32px rgba(10,23,60,.25);
    display:none;
    z-index:100;
  }

  .cat-item{
    padding:10px 12px;
    border-radius:12px;
    font-weight:700;
    font-size:14px;
    cursor:pointer;
    color:var(--navy);
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

  /* ===== Clubs grid ===== */
  .club-grid{
    display:grid;
    gap:18px;
    grid-template-columns:repeat(auto-fit, minmax(280px,1fr));
  }

  .club-card-link{
    display:block;
    text-decoration:none;
    color:inherit;
  }

  .club-card{
    min-height:200px;
    background:var(--card);
    border-radius:20px;
    padding:14px;
    box-shadow:var(--shadow);
    transition:.16s ease;
    display:flex;
    flex-direction:column;
    border:2px solid transparent;
  }
  .club-card::before{
    content:"";
    position:absolute;
  }
  .club-card:hover{
    transform:translateY(-3px);
    border-color:var(--gold);
  }

  .club-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
  }

  .club-id{
    display:flex;
    align-items:center;
    gap:10px;
  }

  .club-logo{
    width:44px;
    height:44px;
    border-radius:50%;
    border:3px solid var(--gold);
    object-fit:cover;
    box-shadow:0 8px 16px rgba(10,23,60,.20);
  }

  .club-title h3{
    margin:0;
    font-size:18px;
    font-weight:800;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    color:var(--navy);
  }

  .club-title small{
    font-size:11px;
    color:var(--muted);
  }

  .sponsor{
    color:var(--navy);
    font-weight:800;
  }

  .category-chip{
    background:#F3F5FF;
    border:1px solid #d5dfff;
    padding:5px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    color:var(--navy);
  }

  .club-desc{
    margin:8px 2px 10px;
    font-size:13px;
    line-height:1.5;
    color:#4b5563;
    overflow:hidden;

    /* line clamp */
    line-clamp: 2;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
  }

  .club-meta{
    margin-top:auto;
    display:flex;
    flex-wrap:wrap;
    gap:10px 18px;
    font-size:12px;
    font-weight:700;
    color:#55617B;
  }
</style>
</head>

<body>

<?php include 'header.php'; ?>

<section class="clubs">
  <div class="container">

    <h2 class="clubs-title">Connect Your Brand With the Most Active UniHive Clubs</h2>

    <!-- Toolbar -->
    <div class="clubs-toolbar">
      <input id="clubSearch" class="input" placeholder="Search clubs…" />

      <!-- Custom dropdown -->
      <div class="cat-dd">
        <button id="catBtn" class="cat-btn" type="button">
          <span id="catLabel">All categories</span>
          <span class="arrow">▾</span>
        </button>

        <div id="catMenu" class="cat-menu">
          <div class="cat-item active" data-value="all">All categories</div>
          <div class="cat-item" data-value="Technology">Technology</div>
          <div class="cat-item" data-value="Sports">Sports</div>
          <div class="cat-item" data-value="Arts">Arts</div>
          <div class="cat-item" data-value="Community">Community</div>
          <div class="cat-item" data-value="Science">Science</div>
        </div>
      </div>
    </div>

    <!-- Clubs Grid -->
    <div class="club-grid" id="clubGrid">

      <a class="club-card-link" href="clubpage.php" data-category="Technology">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club1.png" alt="AI & Robotics Club">
              <div class="club-title">
                <h3>AI &amp; Robotics Club</h3>
                <small>Sponsored by <span class="sponsor">TechCorp</span></small>
              </div>
            </div>
            <span class="category-chip">Technology</span>
          </div>
          <p class="club-desc">Build robots, compete in challenges, and explore cutting-edge AI projects.</p>
          <div class="club-meta">
            <span>Members: 120</span><span>Events: 15</span><span>Points: 555</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Sports">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club2.png" alt="Campus Runners">
              <div class="club-title">
                <h3>Campus Runners</h3>
                <small>Sponsored by <span class="sponsor">FitLife</span></small>
              </div>
            </div>
            <span class="category-chip">Sports</span>
          </div>
          <p class="club-desc">Weekly runs, marathon training, and friendly fitness challenges.</p>
          <div class="club-meta">
            <span>Members: 98</span><span>Events: 22</span><span>Points: 610</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Arts">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club3.png" alt="Creative Studio">
              <div class="club-title">
                <h3>Creative Studio</h3>
                <small>Sponsored by <span class="sponsor">ArtWorks</span></small>
              </div>
            </div>
            <span class="category-chip">Arts</span>
          </div>
          <p class="club-desc">Design, illustration, and all things creative in one collaborative space.</p>
          <div class="club-meta">
            <span>Members: 140</span><span>Events: 18</span><span>Points: 530</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Community">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club4.png" alt="Volunteer Circle">
              <div class="club-title">
                <h3>Volunteer Circle</h3>
                <small>Sponsored by <span class="sponsor">CarePlus</span></small>
              </div>
            </div>
            <span class="category-chip">Community</span>
          </div>
          <p class="club-desc">Volunteer projects and charity events that give back to the community.</p>
          <div class="club-meta">
            <span>Members: 200</span><span>Events: 35</span><span>Points: 890</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Science">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club5.png" alt="Astronomy Society">
              <div class="club-title">
                <h3>Astronomy Society</h3>
                <small>Sponsored by <span class="sponsor">StarLab</span></small>
              </div>
            </div>
            <span class="category-chip">Science</span>
          </div>
          <p class="club-desc">Stargazing nights, telescopes, and talks about the universe.</p>
          <div class="club-meta">
            <span>Members: 85</span><span>Events: 12</span><span>Points: 410</span>
          </div>
        </article>
      </a>

    </div>
  </div>
</section>

<?php include 'footer.php'; ?>

<!-- JavaScript (search + category dropdown filtering) -->
<script>
  let selectedCategory = "all";

  const searchInput = document.getElementById("clubSearch");
  const clubCards = [...document.querySelectorAll(".club-card-link")];

  // Custom dropdown
  const catBtn   = document.getElementById("catBtn");
  const catMenu  = document.getElementById("catMenu");
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

  // Filtering
  function applyFilters(){
    const q = searchInput.value.toLowerCase().trim();

    clubCards.forEach(card => {
      const name = card.querySelector("h3").textContent.toLowerCase();
      const cat  = card.dataset.category;

      const matchText = !q || name.includes(q);   // only title
      const matchCat  = selectedCategory === "all" || selectedCategory === cat;

      card.style.display = (matchText && matchCat) ? "" : "none";
    });
  }

  searchInput.addEventListener("input", applyFilters);
</script>

</body>
</html>

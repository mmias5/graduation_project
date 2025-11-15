<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Header + Sidebar + Hover Dropdowns</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<?php include 'header.php'; ?>
<!-- ✅ START — Discover Clubs Section -->
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

  /* removed redundant @import - font already loaded in head */

  html,body{
    margin:0;height:100%;
    background: radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%)
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

  /* ✅ Toolbar: search + dropdown */
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

  /* ✅ Custom dropdown */
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

  /* ✅ Clubs grid */
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

  /* line clamp (standard + webkit) */
  line-clamp: 2;                 /* ✅ standard */
  display: -webkit-box;          /* required for webkit */
  -webkit-line-clamp: 2;         /* ✅ webkit */
  -webkit-box-orient: vertical;  /* required for webkit */
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

    <!-- ✅ Toolbar -->
    <div class="clubs-toolbar">
      <input id="clubSearch" class="input" placeholder="Search clubs…" />

      <!-- ✅ Custom dropdown -->
      <div class="cat-dd">
        <button id="catBtn" class="cat-btn">
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

    <!-- ✅ Clubs Grid -->
    <div class="club-grid" id="clubGrid">

      <a class="club-card-link" href="#" data-category="Technology">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club1.png">
              <div class="club-title">
                <h3>AI & Robotics Club</h3>
                <small>Sponsored by <span class="sponsor">TechCorp</span></small>
              </div>
            </div>
            <span class="category-chip">Technology</span>
          </div>
          <p class="club-desc">Build robots, compete in challenges, and learn cutting-edge AI.</p>
          <div class="club-meta">
            <span>Member: 120</span><span>Events: 15</span><span>Points: 555</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Sports">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club2.png">
              <div class="club-title">
                <h3>Campus Runners</h3>
                <small>Sponsored by <span class="sponsor">FitLife</span></small>
              </div>
            </div>
            <span class="category-chip">Sports</span>
          </div>
          <p class="club-desc">Weekly runs, marathon training, and fitness challenges.</p>
          <div class="club-meta">
            <span>Member: 98</span><span>Events: 22</span><span>Points: 610</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Arts">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club3.png">
              <div class="club-title">
                <h3>Creative Studio</h3>
                <small>Sponsored by <span class="sponsor">ArtWorks</span></small>
              </div>
            </div>
            <span class="category-chip">Arts</span>
          </div>
          <p class="club-desc">Design, art, illustration and creative collaboration.</p>
          <div class="club-meta">
            <span>Member: 140</span><span>Events: 18</span><span>Points: 530</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Community">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club4.png">
              <div class="club-title">
                <h3>Volunteer Circle</h3>
                <small>Sponsored by <span class="sponsor">CarePlus</span></small>
              </div>
            </div>
            <span class="category-chip">Community</span>
          </div>
          <p class="club-desc">Volunteer projects and charity events across campus.</p>
          <div class="club-meta">
            <span>Member: 200</span><span>Events: 35</span><span>Points: 890</span>
          </div>
        </article>
      </a>

      <a class="club-card-link" href="#" data-category="Science">
        <article class="club-card">
          <div class="club-head">
            <div class="club-id">
              <img class="club-logo" src="assets/images/clubs/club5.png">
              <div class="club-title">
                <h3>Astronomy Society</h3>
                <small>Sponsored by <span class="sponsor">StarLab</span></small>
              </div>
            </div>
            <span class="category-chip">Science</span>
          </div>
          <p class="club-desc">Stargazing nights, telescopes and science talks.</p>
          <div class="club-meta">
            <span>Member: 85</span><span>Events: 12</span><span>Points: 410</span>
          </div>
        </article>
      </a>

    </div>
  </div>
</section>

<!-- ✅ JavaScript (search + category dropdown filtering) -->
<script>
  let selectedCategory = "all";

  const searchInput = document.getElementById("clubSearch");
  const clubCards = [...document.querySelectorAll(".club-card-link")];

  // ----- Custom dropdown -----
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

  // ✅ Filtering
function applyFilters(){
  const q = searchInput.value.toLowerCase().trim();

  clubCards.forEach(card => {
    const name = card.querySelector("h3").textContent.toLowerCase();
    const cat = card.dataset.category;

    const matchText = !q || name.includes(q);   // ✅ ONLY checks title
    const matchCat  = selectedCategory === "all" || selectedCategory === cat;

    card.style.display = (matchText && matchCat) ? "" : "none";
  });
}


  searchInput.addEventListener("input", applyFilters);
</script>

<!-- ✅ END — Discover Clubs Section -->
<?php include 'footer.php'; ?>
<!--end footer-->
</body>
</html>
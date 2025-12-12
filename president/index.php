<?php
session_start();

// بس الطالب العادي يدخل هون
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

/* =======================
   COUNTERS (students / clubs / sponsors)
   ======================= */
$totalStudents = 0;
$totalClubs    = 0;
$totalSponsors = 0;

if (isset($conn) && $conn instanceof mysqli) {
    // طلاب
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM student")) {
        if ($row = $res->fetch_assoc()) $totalStudents = (int)$row['c'];
    }
    // أندية
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM club")) {
        if ($row = $res->fetch_assoc()) $totalClubs = (int)$row['c'];
    }
    // رعاة
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM sponsor")) {
        if ($row = $res->fetch_assoc()) $totalSponsors = (int)$row['c'];
    }
}

/* =======================
   TOP CLUBS RANKING
   ======================= */
$topClubs = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql = "
        SELECT club_id, club_name, logo, points
        FROM club
        WHERE status IS NULL OR status = 'Active'
        ORDER BY points DESC, club_name ASC
        LIMIT 4
    ";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $topClubs[] = $row;
        }
    }
}
if (empty($topClubs)) {
    $topClubs = [
        ['club_id'=>1, 'club_name'=>'AI Innovators', 'logo'=>null, 'points'=>9800],
        ['club_id'=>2, 'club_name'=>'Business Leaders', 'logo'=>null, 'points'=>8800],
        ['club_id'=>3, 'club_name'=>'Art & Media', 'logo'=>null, 'points'=>8400],
        ['club_id'=>4, 'club_name'=>'Green Campus', 'logo'=>null, 'points'=>7900],
    ];
}

/* =======================
   LATEST NEWS (للـ CAROUSEL)
   ======================= */
$newsItems = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql = "
        SELECT news_id, title, category, image
        FROM news
        ORDER BY created_at DESC
        LIMIT 5
    ";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $newsItems[] = $row;
        }
    }
}
// fallback لو فاضية
if (empty($newsItems)) {
    $newsItems = [
        [
            'news_id'  => 0,
            'title'    => 'Top performing clubs',
            'category' => 'Ranking',
            'image'    => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1200&auto=format&fit=crop',
        ],
        [
            'news_id'  => 0,
            'title'    => 'Join a new club',
            'category' => 'Clubs',
            'image'    => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=1200&auto=format&fit=crop',
        ],
        [
            'news_id'  => 0,
            'title'    => 'Redeem your points',
            'category' => 'Rewards',
            'image'    => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop',
        ],
    ];
}

/* =======================
   SPONSORS DOTS (3 sponsors من DB)
   ======================= */
$sponsors = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql = "
        SELECT sponsor_id, company_name, logo
        FROM sponsor
        ORDER BY sponsor_id ASC
        LIMIT 3
    ";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $sponsors[] = $row;
        }
    }
}
// نكمّل 3 عناصر لو أقل من 3
while (count($sponsors) < 3) {
    $sponsors[] = [
        'sponsor_id'   => 0,
        'company_name' => 'Sponsor',
        'logo'         => null,
    ];
}

/* =======================
   HELPERS
   ======================= */
function club_logo_url(?string $logo): string {
    if ($logo && $logo !== '') {
        // عندك بالـ SQL قيم زي: assets/sponsor_coffee.png
        // فبنستخدمها زي ما هي، والمجلد يكون موجود نسبي لملف index.php
        return htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
    }
    return 'https://dummyimage.com/120x120/a9bff8/242751.png&text=CL';
}

function sponsor_logo_url(?string $logo): string {
    if ($logo && $logo !== '') {
        return htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
    }
    return 'https://dummyimage.com/200x200/a9bff8/242751.png&text=S';
}

function news_image_url(?string $image): string {
    if ($image && $image !== '') {
        // نفس الفكرة، لو القيمة assets/news_xxx.png خليها موجودة في نفس المسار
        return htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    }
    return 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1200&auto=format&fit=crop';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Clubs Hub — Home</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========= Brand Tokens ========= */
:root{
  --navy:#4871db; --royal:#4871db; --lightBlue:#a9bff8; --gold:#e5b758;
  --sun:#f4df6d; --coral:#ff5e5e; --paper:#eef2f7; --ink:#0e1228;
  --card:#ffffff; --shadow:0 14px 34px rgba(10, 23, 60, .14); --radius:20px;
}

*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:"Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  color:var(--ink); background:var(--paper);
}

.container{ width:min(1100px, 92%); margin-inline:auto }
.row{ display:flex; gap:12px }
.center{ align-items:center; justify-content:center }
.between{ justify-content:space-between; align-items:center }
.mt-16{ margin-top:16px }

/* keep distance from topbar */
.safe-space{ margin-top:1px; }

/* hero container */
.hero-card{
  position:relative;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:20px 0;
  z-index:1;
}

/* stars */
.hero-card .star{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  width:clamp(220px, 28vw, 300px);
  height:auto;
  opacity:1;
  pointer-events:none;
  z-index:0;
}
.hero-card .star-left{
  left:90px;
  transform:translateY(-50%) rotate(-4deg);
}
.hero-card .star-right{
  right:90px;
  transform:translateY(-50%) rotate(4deg);
}

/* video card */
.video-card{
  position:relative;
  z-index:2;
  width:94%;
  max-width:1150px;
  aspect-ratio: 16 / 6;
  border-radius:20px;
  overflow:hidden;
  margin:auto;
  background: var(--royal);
  box-shadow:
    0 0 0 2px rgba(72,113,219,.85),
    0 18px 40px rgba(10,23,60,.45);
}
.video-card video{
  position:absolute;
  inset:0;
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
  filter:saturate(1.02) contrast(1.02);
}

/* responsive */
@media(max-width:900px){
  .safe-space{ margin-top:40px; }
  .hero-card .star{ width:clamp(160px, 40vw, 240px); }
  .hero-card .star-left{ left:-20px; }
  .hero-card .star-right{ right:-20px; }
  .video-card{ max-width:90vw; }
}
@media (max-width: 900px){
  .safe-space{ margin-top:70px; }
  .hero-card .star{ width:clamp(110px, 26vw, 200px); }
  .hero-card .star-left{  left:-30px; }
  .hero-card .star-right{ right:-30px; }
  .video-card{ max-width:92vw; }
}

/* ========= Cards Carousel ========= */
.cards-wrap{ margin:28px auto }

.carousel{
  position: relative;
  overflow-x: hidden;
  overflow-y: visible;
}
.carousel-track{
  display:flex; gap:24px;
  will-change:transform;
  transform:translateX(0);
}
.carousel .card{
  min-width: calc((100% - 48px) / 3);
  position:relative;
  text-decoration:none;
  color:inherit;
  overflow:visible;
  padding-top:60px;
  padding-bottom:60px;
}
.card-inner{
  position:relative;
  border-radius:26px;
  background:var(--card);
  box-shadow:var(--shadow);
  overflow:hidden;
  transform: scale(.86) translateZ(0);
  transition: transform 700ms cubic-bezier(.22,.61,.36,1),
              box-shadow 700ms cubic-bezier(.22,.61,.36,1);
  will-change: transform;
}
.carousel .card.active .card-inner{
  transform: scale(1.08) translateZ(0);
  box-shadow: 0 22px 60px rgba(10,20,40,.22);
}
.carousel .card:active .card-inner{ transform: scale(.98) }
.card-inner img{ width:100%; height:260px; object-fit:cover; display:block }
.card-inner h3{
  margin:0; padding:14px 16px 18px; font-size:18px; font-weight:800; color:#fff;
  position:absolute; bottom:0; left:0; right:0;
  background:linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(10,20,40,.65) 60%, rgba(10,20,40,.88) 100%);
}
.pill{
  position:absolute; top:12px; left:16px; z-index:2;
  background:#ffffff; color:#000; font-weight:800; font-size:12px;
  padding:6px 12px; border-radius:999px;
}

/* arrows */
.carousel-arrow{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  width:44px; height:44px;
  border:none; border-radius:999px;
  background:#121740; color:#fff;
  font-size:24px; line-height:1;
  display:grid; place-items:center;
  box-shadow:0 10px 24px rgba(10,20,40,.25);
  cursor:pointer; z-index:5;
  opacity:.92; transition:transform .2s ease, opacity .2s ease, background .2s ease;
}
.carousel-arrow:hover{ opacity:1; transform:translateY(-50%) scale(1.05); }
.carousel-arrow:active{ transform:translateY(-50%) scale(.96); }
.carousel-arrow.prev{ left:-12px; }
.carousel-arrow.next{ right:-12px; }

@media (max-width:900px){
  .carousel .card{ min-width: 86% }
  .carousel-arrow{ width:40px; height:40px; font-size:22px; }
  .carousel-arrow.prev{ left:6px; }
  .carousel-arrow.next{ right:6px; }
}

/* ========= Ranking ========= */
.ranking{ margin:30px auto 0 }
.ranking-wrap{ background:#f3f6ff; border-radius:24px; padding:28px; box-shadow:var(--shadow) }
.title{ margin:0 0 18px; font-size:32px; font-weight:800; text-align:center; color:var(--navy); }
.ranking-list{ display:grid; gap:14px }
.rank-item{
  display:grid; grid-template-columns:56px 1fr 80px; align-items:center;
  background:#fff; border-radius:14px; padding:12px 14px; box-shadow:var(--shadow);
}
.rank-item.accent{
  background:linear-gradient(90deg, #f9d778 0%, #efc25a 100%);
  color:#4b3205;
}
.rank-item .avatar{ width:44px; height:44px; border-radius:12px; overflow:hidden; display:grid; place-items:center; background:#e9eefb }
.rank-item .avatar img{ width:100%; height:100%; object-fit:cover }
.rank-item .meta{ display:flex; flex-direction:column; gap:2px }
.rank-item .meta .name{ font-weight:800; font-size:18px }
.rank-item .meta .points{ font-size:13px; opacity:.85 }
.rank-item .trophy{ font-size:20px; text-align:right }

/* ========= Button ========= */
.btn{
  display:inline-block; background:#4871db; color:#fff; text-decoration:none;
  padding:10px 18px; border-radius:999px; font-weight:800; box-shadow:var(--shadow)
}
.btn:hover{ filter:brightness(1.08); background:#fff; color:#4871db }

/* ========= Sponsors ========= */
.sponsors{ margin:34px auto 40px; text-align:center }
.sponsors-title{ font-size:42px; letter-spacing:2px; color:var(--navy); margin:4px 0 16px; font-weight:800 }
.sponsor-panel{
  border:2px solid #efd679; border-radius:22px; padding:26px;
  display:flex; gap:26px; justify-content:center; align-items:center;
  background:#fff; box-shadow:var(--shadow)
}
.sponsor-dot{
  width:94px; height:94px; border-radius:50%;
  background:#f1f4ff;
  display:grid; place-items:center; overflow:hidden;
}
.sponsor-dot.large{
  width:152px; height:152px;
}
.sponsor-dot img{ width:90%; height:90%; object-fit:contain }

/* ========= Footer Stats ========= */
.stats{ position:relative; background:#4871db; color:#fff; margin-top:40px }
.stats-overlay{ position:relative; z-index:1; padding:26px 0 38px }
.stats-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap:26px; align-items:center }
.stat{ display:flex; flex-direction:column; gap:10px }
.stat-top{ display:flex; justify-content:space-between; align-items:center; font-weight:700 }
.bar{ height:8px; background: white; border-radius:999px; overflow:hidden }
.bar span{ display:block; height:100%; width:0; background:#f4df6d; border-radius:999px; transition:width 1.2s ease }
.stat-num{ font-size:18px; font-weight:800; text-align:center }

.carousel.is-animating .carousel-arrow{
  pointer-events:none;
  opacity:.6;
}
</style>
</head>
<body>

<?php include_once "header.php"; ?>

<!-- ===== HERO ===== -->
<section class="hero-card safe-space">
  <img class="star star-left"  src="tools/pics/bg.png" alt="" aria-hidden="true">
  <img class="star star-right" src="tools/pics/bg.png" alt="" aria-hidden="true">

  <div class="video-card">
    <video autoplay muted loop playsinline preload="auto">
      <source src="tools/video/indexvideo.mp4" type="video/mp4">
    </video>
  </div>
</section>

<!-- ===== NEWS CAROUSEL (من جدول news) ===== -->
<section class="cards-wrap container">
  <div class="carousel" id="cards-carousel" aria-roledescription="carousel">
    <div class="carousel-track" id="cards-track">
      <?php foreach ($newsItems as $item): ?>
        <?php
          $href     = $item['news_id'] ? 'news.php?id='.(int)$item['news_id'] : '#';
          $pillText = $item['category'] ? $item['category'] : 'News';
        ?>
        <a class="card" href="<?php echo $href; ?>">
          <div class="card-inner">
            <span class="pill"><?php echo htmlspecialchars($pillText); ?></span>
            <img src="<?php echo news_image_url($item['image']); ?>" alt="">
            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <button class="carousel-arrow prev" aria-label="Previous slide">‹</button>
    <button class="carousel-arrow next" aria-label="Next slide">›</button>
  </div>
</section>

<!-- ===== BEST OF RANKING ===== -->
<section class="ranking container" id="ranking">
  <div class="ranking-wrap">
    <h2 class="title">Best Of Ranking</h2>
    <div class="ranking-list" id="ranking-list">
      <?php
      $i = 0;
      foreach ($topClubs as $club):
          $i++;
          $accent = ($i === 1) ? ' accent' : '';
          $trophy = ($i === 1) ? '🏆' : (($i === 2) ? '🥈' : (($i === 3) ? '🥉' : '🎖️'));
      ?>
        <div class="rank-item<?php echo $accent; ?>">
          <div class="avatar">
            <img src="<?php echo club_logo_url($club['logo']); ?>" alt="">
          </div>
          <div class="meta">
            <div class="name"><?php echo htmlspecialchars($club['club_name']); ?></div>
            <div class="points"><?php echo number_format((int)$club['points']); ?> pts</div>
          </div>
          <div class="trophy"><?php echo $trophy; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="center mt-16"><a class="btn" href="clubsranking.php">View More</a></div>
  </div>
</section>

<!-- ===== SPONSORS FROM DB ===== -->
<section class="sponsors container">
  <h2 class="sponsors-title">SPONSORS</h2>
  <div class="sponsor-panel">
    <?php
      // small – large – small زي الصورة
      $left  = $sponsors[0];
      $mid   = $sponsors[1];
      $right = $sponsors[2];
    ?>
    <div class="sponsor-dot">
      <img src="<?php echo sponsor_logo_url($left['logo']); ?>" alt="<?php echo htmlspecialchars($left['company_name']); ?>">
    </div>
    <div class="sponsor-dot large">
      <img src="<?php echo sponsor_logo_url($mid['logo']); ?>" alt="<?php echo htmlspecialchars($mid['company_name']); ?>">
    </div>
    <div class="sponsor-dot">
      <img src="<?php echo sponsor_logo_url($right['logo']); ?>" alt="<?php echo htmlspecialchars($right['company_name']); ?>">
    </div>
  </div>
</section>

<!-- ===== FOOTER STATS ===== -->
<footer class="stats" id="stats">
  <div class="stats-overlay">
    <div class="stats-grid container">
      <div class="stat">
        <div class="stat-top"><span>Students</span></div>
        <div class="bar"><span data-bar></span></div>
        <div class="stat-num" data-counter data-target="<?php echo max(0,$totalStudents); ?>">0</div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Clubs</span></div>
        <div class="bar"><span data-bar></span></div>
        <div class="stat-num" data-counter data-target="<?php echo max(0,$totalClubs); ?>">0</div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Sponsors</span></div>
        <div class="bar"><span data-bar></span></div>
        <div class="stat-num" data-counter data-target="<?php echo max(0,$totalSponsors); ?>">0</div>
      </div>
    </div>
  </div>
</footer>

<script>
/* ===== CAROUSEL (نفس الأنيميشن القديم) ===== */
(function cardsCarousel(){
  const track    = document.getElementById('cards-track');
  const carousel = document.getElementById('cards-carousel');
  const btnPrev  = carousel ? carousel.querySelector('.carousel-arrow.prev') : null;
  const btnNext  = carousel ? carousel.querySelector('.carousel-arrow.next') : null;

  if(!track || !carousel || !btnPrev || !btnNext){ return; }

  let isAnimating = false;
  let timer;
  const EASE = 'cubic-bezier(.22,.61,.36,1)';
  const DURATION = 700;
  const INTERVAL = 3200;

  function getCards(){ return Array.from(track.querySelectorAll('.card')); }

  function stepWidth(){
    const first = track.querySelector('.card');
    if(!first) return 0;
    const rect = first.getBoundingClientRect();
    const gap  = parseInt(getComputedStyle(track).gap || '24', 10) || 24;
    return Math.round(rect.width + gap);
  }

  function updateActive(){
    const cards = getCards();
    const vp = carousel.getBoundingClientRect();
    const cx = vp.left + vp.width/2;
    let best=null, bestD=1e9;
    cards.forEach(c=>{
      const r=c.getBoundingClientRect(), cc=r.left+r.width/2, d=Math.abs(cc-cx);
      if(d<bestD){ bestD=d; best=c; }
    });
    cards.forEach(c=>c.classList.toggle('active', c===best));
  }

  function cleanupAfter(dir){
    if(dir>0){
      const first = track.firstElementChild;
      if(first) track.appendChild(first);
    }else{
      const last = track.lastElementChild;
      if(last) track.insertBefore(last, track.firstElementChild);
    }
    track.style.transition = 'none';
    track.style.transform  = 'translateX(0)';
    void track.offsetWidth;
    isAnimating = false;
    carousel.classList.remove('is-animating');
    updateActive();
  }

  function slideOnce(dir=1){
    if(isAnimating) return;
    isAnimating = true;
    carousel.classList.add('is-animating');

    const dist = stepWidth();
    if(!dist){
      isAnimating = false;
      carousel.classList.remove('is-animating');
      return;
    }

    track.style.transition = `transform ${DURATION}ms ${EASE}`;
    track.style.transform  = `translateX(${dir>0 ? -dist : dist}px)`;

    let ended = false;
    const onEnd = () => {
      if(ended) return;
      ended = true;
      track.removeEventListener('transitionend', onEnd);
      cleanupAfter(dir);
    };
    track.addEventListener('transitionend', onEnd, {once:true});
    setTimeout(onEnd, DURATION + 120);
  }

  function start(){ stop(); timer = setInterval(()=>slideOnce(1), INTERVAL); }
  function stop(){ if(timer) clearInterval(timer); }

  btnPrev.addEventListener('click', ()=>{ stop(); slideOnce(-1); start(); });
  btnNext.addEventListener('click', ()=>{ stop(); slideOnce(1);  start(); });

  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);

  window.addEventListener('keydown', (e)=>{
    if(e.key==='ArrowLeft'){ stop(); slideOnce(-1); start(); }
    if(e.key==='ArrowRight'){ stop(); slideOnce(1);  start(); }
  });

  let touchX=null, touchTime=0;
  carousel.addEventListener('touchstart', (e)=>{ touchX=e.touches[0].clientX; touchTime=Date.now(); stop(); }, {passive:true});
  carousel.addEventListener('touchend', (e)=>{
    if(touchX==null) return;
    const dx = e.changedTouches[0].clientX - touchX;
    const dt = Date.now()-touchTime;
    if(Math.abs(dx)>(dt<250?40:80)){ slideOnce(dx<0 ? 1 : -1); }
    touchX=null; start();
  }, {passive:true});

  window.addEventListener('resize', ()=>requestAnimationFrame(updateActive));

  updateActive();
  start();
})();

/* ===== ROLLING COUNTERS ===== */
function setupCounters(){
  const counters = [...document.querySelectorAll('[data-counter]')];
  const bars = [...document.querySelectorAll('.stat .bar span[data-bar]')];

  const io = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        counters.forEach(el=>animateCounter(el, parseInt(el.dataset.target,10)||0, 1200));
        bars.forEach((bar)=>{
          if (!bar.dataset.filled){
            let parent = bar.closest('.stat').querySelector('[data-counter]');
            const tgt = parent ? (parseInt(parent.dataset.target,10)||0) : 100;
            const base = tgt > 1000 ? 55000 : 100;
            const pct = Math.max(8, Math.min(100, Math.round((tgt / base) * 100)));
            requestAnimationFrame(()=>{ bar.style.width = pct + '%'; bar.dataset.filled = '1'; });
          }
        });
        io.disconnect();
      }
    });
  }, {threshold: 0.35});
  const stats = document.getElementById('stats');
  if(stats) io.observe(stats);
}
function animateCounter(el, target, duration){
  const start = 0, t0 = performance.now();
  function tick(now){
    const p = Math.min(1, (now - t0)/duration);
    const eased = 1 - Math.pow(1 - p, 3);
    el.textContent = Math.floor(start + (target - start) * eased).toLocaleString();
    if(p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}
document.addEventListener('DOMContentLoaded', setupCounters);
</script>

<?php include "footer.php"; ?>
</body>
</html>

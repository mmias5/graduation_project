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

/* ========= Top Bar ========= (kept in header.php) */
.topbar{ background:#121740; color:#fff; position:sticky; top:0; z-index:50 }
.topbar .container{ padding:12px 0 }
.brand img.logo{ height:36px }
.nav a{ color:#fff; text-decoration:none; font-weight:700; margin:0 12px; opacity:.95 }
.nav a:hover{ opacity:1; text-decoration:underline }
.user{ color:#fff; text-decoration:none; font-weight:700; display:flex; gap:8px; align-items:center }

/* keep distance from topbar */
.safe-space{
  margin-top:50px;
}
/* keep distance from topbar */
.safe-space{
  margin-top:50px;
}

/* hero container */
.hero-card{
  position:relative;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:20px 0;   /* smaller top/bottom spacing */
  z-index:1;
}

/* ✅ SMALLER stars */
.hero-card .star{
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  width:clamp(220px, 28vw, 300px);   /* ✅ smaller size */
  height:auto;
  opacity:1;
  pointer-events:none;
  z-index:0;
}

.hero-card .star-left{
  left:90px;                       /* closer to card */
  transform:translateY(-50%) rotate(-4deg);
}
.hero-card .star-right{
  right:90px;
  transform:translateY(-50%) rotate(4deg);
}

/* ✅ SMALLER video card */
.video-card{
  position:relative;
  z-index:2;
  width:100%;
  max-width:720px;                 /* ✅ reduced from 920px */
  aspect-ratio:16/10;
  border-radius:20px;
  overflow:hidden;
  box-shadow:0 12px 28px rgba(10,23,60,0.18);
  background:#000;
}

.video-card video{
  position:absolute;
  inset:0;
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

/* responsive */
@media(max-width:900px){
  .safe-space{ margin-top:40px; }

  .hero-card .star{
    width:clamp(160px, 40vw, 240px);  /* smaller on mobile */
  }
  .hero-card .star-left{ left:-20px; }
  .hero-card .star-right{ right:-20px; }

  .video-card{
    max-width:90vw;
  }
}

/* tighten on small screens */
@media (max-width: 900px){
  .safe-space{ margin-top:70px; }
  .hero-card .star{ width:clamp(110px, 26vw, 200px); }
  .hero-card .star-left{  left:-30px; }
  .hero-card .star-right{ right:-30px; }
  .video-card{ max-width:92vw; }
}


/* fallback for browsers without aspect-ratio support */
@supports not (aspect-ratio: 1 / 1) {
  .hero::before{
    content:"";
    display:block;
    padding-top: 62.5%;      /* 10/16 = 0.625 (i.e., 62.5%) */
  }
}

.hero > video{
  position: absolute;
  inset: 0;                  /* top:0; right:0; bottom:0; left:0 */
  width: 100%;
  height: 100%;
  object-fit: cover;         /* fill without bars */
  display: block;
  filter: saturate(1.02) contrast(1.02);
}

/* ========= Cards Carousel (1-by-1 slide, center scaling) ========= */
.cards-wrap{ margin:28px auto }

.carousel{
  position: relative;
  overflow-x: hidden;
  overflow-y: visible; /* allow vertical growth of center card */
}
.carousel-track{
  display:flex; gap:24px;
  will-change:transform;
  transform:translateX(0);
}

/* The anchor keeps layout size and provides vertical padding so the scaled
   inner card never gets clipped. */
.carousel .card{
  min-width: calc((100% - 48px) / 3); /* 3 visible on desktop */
  position:relative;
  text-decoration:none;
  color:inherit;
  overflow:visible;

  /* Extra room so the center card can grow without clipping */
  padding-top:60px;
  padding-bottom:60px;
}

/* We scale the INNER wrapper, not the anchor */
.card-inner{
  position:relative;
  border-radius:var(--radius);
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

/* Card media & text */
.card-inner img{ width:100%; height:220px; object-fit:cover; display:block }
.card-inner h3{
  margin:0; padding:14px 16px 18px; font-size:18px; font-weight:800; color:#fff;
  position:absolute; bottom:0; left:0; right:0;
  background:linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(10,20,40,.65) 60%, rgba(10,20,40,.88) 100%);
}
.pill{
  position:absolute; top:10px; left:12px; z-index:2;
  background:rgba(255,255,255,.9); color:var(--ink); font-weight:800; font-size:12px;
  padding:6px 10px; border-radius:999px; box-shadow:0 6px 16px rgba(0,0,0,.1);
}

/* Pause auto-slide on hover */
.carousel:hover .carousel-track{ transition-duration:0s !important }

/* === Carousel arrows === */
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
  .carousel .card{ min-width: 86% }  /* one big card on mobile */
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
  display:grid; grid-template-columns:56px 1fr 40px; align-items:center;
  background:#fff; border-radius:14px; padding:12px 14px; box-shadow:var(--shadow);
}
.rank-item.accent{ background:linear-gradient(90deg, #f9d778 0%, #efc25a 100%); color:#4b3205 }
.rank-item .avatar{ width:44px; height:44px; border-radius:12px; overflow:hidden; display:grid; place-items:center; background:#e9eefb }
.rank-item .avatar img{ width:100%; height:100%; object-fit:cover }
.rank-item .meta{ display:flex; flex-direction:column; gap:6px }
.rank-item .meta .name{ font-weight:800; font-size:18px }
.rank-item .meta .sponsor{ font-size:12px; opacity:.8 }
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
  border:2px solid #efd679; border-radius:22px; padding:26px; display:flex; gap:26px; justify-content:center; align-items:center; background:#fff; box-shadow:var(--shadow)
}
.sponsor-dot{ width:94px; height:94px; border-radius:50%; background:#f1f4ff; display:grid; place-items:center; overflow:hidden }
.sponsor-dot.large{ width:152px; height:152px }
.sponsor-dot img{ width:90%; height:90%; object-fit:contain }

/* ========= Footer Stats with rolling numbers ========= */
.stats{ position:relative; background:#4871db; color:#fff; margin-top:40px }
.stats-overlay{ position:relative; z-index:1; padding:26px 0 38px }
.stats-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap:26px; align-items:center }
.stat{ display:flex; flex-direction:column; gap:10px }
.stat-top{ display:flex; justify-content:space-between; align-items:center; font-weight:700 }
.circle-btn{ width:36px; height:36px; border-radius:50%; border:none; background:#f4df6d; color:#0e1228; font-weight:900; cursor:pointer; display:grid; place-items:center }
.bar{ height:8px; background: white; border-radius:999px; overflow:hidden }
.bar span{ display:block; height:100%; width:0; background:#f4df6d; border-radius:999px; transition:width 1.2s ease }
.stat-num{ font-size:18px; font-weight:800; text-align:center }
/* Disable arrows while animating to prevent double transitions */
.carousel.is-animating .carousel-arrow{
  pointer-events: none;
  opacity: .6;
}


</style>
</head>
<body>

<?php include_once("header.php"); ?>
<!-- ===== Hero Card with side stars ===== -->
<section class="hero-card safe-space">
  <!-- decorative stars (behind the card) -->
  <img class="star star-left"  src="tools/pics/bg.png" alt="" aria-hidden="true">
  <img class="star star-right" src="tools/pics/bg.png" alt="" aria-hidden="true">

  <!-- video card -->
  <div class="video-card">
    <video autoplay muted loop playsinline preload="metadata"
           poster="tools/pics/indexvideo.jpg">
      <source src="tools/video/indexvideo.mp4" type="video/mp4">
    </video>
  </div>
</section>


<!-- ===== Cards Carousel ===== -->
<section class="cards-wrap container">
  <div class="carousel" id="cards-carousel" aria-roledescription="carousel">
    <div class="carousel-track" id="cards-track">
      <!-- Each card is a button (link) -->
      <a class="card" href="news.php">
        <div class="card-inner">
          <span class="pill">Events</span>
          <img src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?q=80&w=1200&auto=format&fit=crop" alt="">
          <h3>Event Name</h3>
        </div>
      </a>

      <a class="card" href="news2.html">
        <div class="card-inner">
          <span class="pill">News</span>
          <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1200&auto=format&fit=crop" alt="">
          <h3>New sponsor with us now!</h3>
        </div>
      </a>

      <a class="card" href="news3.html">
        <div class="card-inner">
          <span class="pill">Events</span>
          <img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=1200&auto=format&fit=crop" alt="">
          <h3>Event Name</h3>
        </div>
      </a>

      <!-- Extra cards for seamless loop -->
      <a class="card" href="news4.html">
        <div class="card-inner">
          <span class="pill">News</span>
          <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop" alt="">
          <h3>Campus hackathon announced</h3>
        </div>
      </a>

      <a class="card" href="news5.html">
        <div class="card-inner">
          <span class="pill">Events</span>
          <img src="https://images.unsplash.com/photo-1496307042754-b4aa456c4a2d?q=80&w=1200&auto=format&fit=crop" alt="">
          <h3>Photography walk</h3>
        </div>
      </a>
    </div>

    <!-- ⇦ ARROWS ⇨ -->
    <button class="carousel-arrow prev" aria-label="Previous slide">‹</button>
    <button class="carousel-arrow next" aria-label="Next slide">›</button>
  </div>
</section>

<!-- ===== Best Of Ranking ===== -->
<section class="ranking container" id="ranking">
  <div class="ranking-wrap">
    <h2 class="title">Best Of Ranking</h2>
    <div id="ranking-list" class="ranking-list" aria-live="polite"></div>
    <div class="center mt-16"><a class="btn" href="clubsranking.php">View More</a></div>
  </div>
</section>

<!-- ===== Sponsors ===== -->
<section class="sponsors container">
  <h2 class="sponsors-title">SPONSORS</h2>
  <div class="sponsor-panel">
    <div class="sponsor-dot"><img src="https://dummyimage.com/200x200/a9bff8/242751.png&text=S1" alt="Sponsor"></div>
    <div class="sponsor-dot large"><img src="https://dummyimage.com/300x300/4871db/ffffff.png&text=S2" alt="Sponsor"></div>
    <div class="sponsor-dot"><img src="https://dummyimage.com/200x200/a9bff8/242751.png&text=S3" alt="Sponsor"></div>
  </div>
</section>

<!-- ===== Footer Stats (rolling counters) ===== -->
<footer class="stats" id="stats">
  <div class="stats-overlay">
    <div class="stats-grid container">
      <div class="stat">
        <div class="stat-top"><span>Students</span></div>
        <div class="bar"><span data-bar></span></div>
        <div class="stat-num" data-counter data-target="55000">0</div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Clubs</span></div>
        <div class="bar"><span data-bar></span></div>
        <div class="stat-num" data-counter data-target="100">0</div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Sponsors</span></div>
        <div class="bar"><span data-bar></span></div>
        <div class="stat-num" data-counter data-target="50">0</div>
      </div>
    </div>
  </div>
</footer>

<script>

/* ===== CAROUSEL — 1-by-1 + center scale + arrows + keys + swipe (robust) ===== */
(function cardsCarousel(){
  const track    = document.getElementById('cards-track');
  const carousel = document.getElementById('cards-carousel');
  const btnPrev  = carousel ? carousel.querySelector('.carousel-arrow.prev') : null;
  const btnNext  = carousel ? carousel.querySelector('.carousel-arrow.next') : null;

  if(!track || !carousel || !btnPrev || !btnNext){
    console.warn('[carousel] missing required elements or duplicate IDs.');
    return;
  }

  let isAnimating = false;
  let timer;
  const EASE = 'cubic-bezier(.22,.61,.36,1)';
  const DURATION = 700;  // ms
  const INTERVAL = 3200; // ms

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
    // reflow
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
    if(!dist){ // safety
      isAnimating = false;
      carousel.classList.remove('is-animating');
      return;
    }

    track.style.transition = `transform ${DURATION}ms ${EASE}`;
    track.style.transform  = `translateX(${dir>0 ? -dist : dist}px)`;

    // Robust end: listen for transitionend + setTimeout fallback
    let ended = false;
    const onEnd = () => {
      if(ended) return;
      ended = true;
      track.removeEventListener('transitionend', onEnd);
      cleanupAfter(dir);
    };
    track.addEventListener('transitionend', onEnd, {once:true});

    // Fallback in case transitionend doesn't fire (browser quirk / tab switch)
    setTimeout(onEnd, DURATION + 120);
  }

  function start(){ stop(); timer = setInterval(()=>slideOnce(1), INTERVAL); }
  function stop(){ if(timer) clearInterval(timer); }

  // arrows
  btnPrev.addEventListener('click', ()=>{ stop(); slideOnce(-1); start(); });
  btnNext.addEventListener('click', ()=>{ stop(); slideOnce(1);  start(); });

  // pause on hover
  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);

  // keyboard
  window.addEventListener('keydown', (e)=>{
    if(e.key==='ArrowLeft'){ stop(); slideOnce(-1); start(); }
    if(e.key==='ArrowRight'){ stop(); slideOnce(1);  start(); }
  });

  // touch swipe
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
/* =========================
   RANKING AUTOFILL (hooks)
   ========================= */
async function loadRankingTop(limit = 4){
  if (Array.isArray(window.__RANKING_DATA__) && window.__RANKING_DATA__.length){
    return topSorted(window.__RANKING_DATA__, limit);
  }
  try{
    const r = await fetch('/api/rankings/top?limit=' + limit, {credentials:'same-origin'});
    if(r.ok){ const data = await r.json(); if(Array.isArray(data) && data.length) return topSorted(data, limit); }
  }catch(e){}
  try{
    const r = await fetch('/rankings.json', {credentials:'same-origin'});
    if(r.ok){ const data = await r.json(); if(Array.isArray(data) && data.length) return topSorted(data, limit); }
  }catch(e){}
  try{
    const raw = localStorage.getItem('cch_ranking');
    if(raw){ const data = JSON.parse(raw); if(Array.isArray(data) && data.length) return topSorted(data, limit); }
  }catch(e){}
  const demo = [
    { club_id: 1, club_name:'AI Innovators', sponsor_name:"Tech Bee", logo_url:'https://dummyimage.com/120x120/1d2a6b/ffffff.png&text=AI', total_points: 9820 },
    { club_id: 2, club_name:'Business Leaders', sponsor_name:"FinCorp", logo_url:'https://dummyimage.com/120x120/2b3a84/ffffff.png&text=BL', total_points: 8815 },
    { club_id: 3, club_name:'Art & Media', sponsor_name:"CreatiCo", logo_url:'https://dummyimage.com/120x120/3e4fb1/ffffff.png&text=AM', total_points: 8420 },
    { club_id: 4, club_name:'Green Campus', sponsor_name:"Eco+ Labs", logo_url:'https://dummyimage.com/120x120/4b64e3/ffffff.png&text=GC', total_points: 7905 },
    { club_id: 5, club_name:'Robotics', sponsor_name:"MechaQ", logo_url:'https://dummyimage.com/120x120/6a7cf0/ffffff.png&text=R', total_points: 7020 },
  ];
  return topSorted(demo, limit);
}
function topSorted(arr, limit){ return [...arr].sort((a,b)=>(b.total_points||0)-(a.total_points||0)).slice(0, limit); }
function renderRanking(items){
  const list = document.getElementById('ranking-list'); list.innerHTML = '';
  items.forEach((item, idx)=>{
    const div = document.createElement('div');
    div.className = 'rank-item' + (idx===0 ? ' accent':'');
    div.innerHTML = `
      <div class="avatar"><img src="${(item.logo_url||'https://dummyimage.com/120x120/a9bff8/242751.png&text=CL')}" alt=""></div>
      <div class="meta">
        <div class="name">${escapeHTML(item.club_name||'Club Name')}</div>
        <div class="sponsor">Sponsored by ${escapeHTML(item.sponsor_name||"company’s name")}</div>
      </div>
      <div class="trophy">${ idx===0 ? '🏆' : idx===1 ? '🥈' : idx===2 ? '🥉' : '🎖️' }</div>`;
    list.appendChild(div);
  });
}
function escapeHTML(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

/* =========================
   Rolling counters — animate when stats visible
   ========================= */
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
            const pct = Math.max(8, Math.min(100, Math.round((tgt / (tgt>1000?55000:100)) * 100)));
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

/* =========================
   INIT
   ========================= */
(async function init(){
  const top = await loadRankingTop(4);
  renderRanking(top);
  setupCounters();
})();
</script>

<?php include("footer.php"); ?>
</body>
</html>

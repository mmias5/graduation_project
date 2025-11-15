<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Club Members</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8;
  --paper:#eef2f7; --ink:#0e1228; --card:#fff; --gold:#e5b758;
  --shadow:0 14px 34px rgba(10,23,60,.12); --radius:20px; --maxw:1100px;
}

/* ==== layout reset + sticky footer (without touching footer.php) ==== */
*{box-sizing:border-box}
html,body{margin:0; height:100%}
body{
  min-height:100vh;
  display:flex;
  flex-direction:column;
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
}
.wrap{max-width:var(--maxw); margin:28px auto 24px; padding:0 18px; flex:1 0 auto;}
footer.cch-footer{ margin-top:auto !important; }

/* header row */
.header-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:8px 0 16px}
.title{font-size:28px;font-weight:900;letter-spacing:.02em;margin:0;color:var(--navy)}
.subtitle{color:#667085;font-weight:700}

/* toolbar */
.toolbar{
  background:var(--card); border:1px solid #e1e6f0; border-radius:16px; box-shadow:var(--shadow);
  padding:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;
}
.input{
  flex:1; border-radius:12px; border:1px solid #d5dbea; padding:10px 12px; font-size:14px; outline:none; background:#fff;
}
.input:focus{border-color:var(--royal); box-shadow:0 0 0 4px rgba(72,113,219,.12)}

/* grid of members */
.grid{display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-top:16px}
@media (max-width:820px){ .grid{grid-template-columns:1fr} }
.card{
  background:var(--card); border:1px solid #e6e8f2; border-radius:16px; box-shadow:var(--shadow);
  padding:14px; display:grid; grid-template-columns:72px 1fr auto; gap:12px; align-items:center;
}
.avatar{width:72px;height:72px;border-radius:14px;object-fit:cover;background:#eef2ff;border:1px solid #e6e8f2}
.name{font-weight:900;font-size:16px}
.meta{color:#596180;font-size:13px}
.role-badge{display:inline-block;background:#fff7e6;border:1px solid #ffecb5;color:#8a5b00;font-weight:800;border-radius:999px;padding:6px 10px;font-size:12px;margin-top:6px}

/* buttons (only View now) */
.actions{display:flex;gap:8px}
.btn{
  cursor:pointer;
  user-select:none;
  transition:transform .16s ease, box-shadow .16s ease, background-color .16s ease, border-color .16s ease;
}
.btn.small{padding:8px 10px;font-size:12px;border-radius:10px}
.btn.ghost{background:#fff;border:1px solid #e6e8f2;color:#1a1f36}
.btn.ghost:hover{transform:translateY(-1px); box-shadow:0 6px 16px rgba(16,24,40,.12)}

/* empty state */
.empty{
  text-align:center;background:var(--card);border:1px solid #e6e8f2;border-radius:16px;padding:28px;box-shadow:var(--shadow);color:#596180
}

/* pager */
.pager{display:flex;gap:8px;justify-content:center;margin:18px 0}
.pager a, .pager span{
  padding:8px 12px;border-radius:10px;border:1px solid #e1e6f0;background:#fff;text-decoration:none;color:#1a1f36;font-weight:700
}
.pager .active{background:var(--royal);color:#fff;border-color:transparent}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrap">
  <div class="header-row">
    <h1 class="title">Club Members</h1>
    <div class="subtitle"><span id="count"></span> total</div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <input id="search" class="input" type="search" placeholder="Search by name…">
  </div>

  <!-- Members -->
  <div id="grid" class="grid"></div>
  <div id="empty" class="empty" style="display:none">No members match your search.</div>

  <!-- Pagination -->
  <div id="pager" class="pager"></div>
</div>

<?php include 'footer.php'; ?>

<script>
/* ==== Mock data (for demo only) ==== */
const MOCK = Array.from({length:18}).map((_,i)=>({
  id:i+1,
  name:['Lina','Omar','Sara','Mustafa','Noor','Jad','Maya','Hiba','Yousef','Rami','Leen','Tala','Ahmad','Dana','Zain','Farah','Khaled','Aya'][i],
  email:`member${i+1}@university.edu`,
  major:['CS','IT','Business','Design'][i%4],
  role:i===0 ? 'President' : 'Member',
  joined:`2025-0${(i%9)+1}-${String(((i*3)%28)+1).padStart(2,'0')}`,
  avatar:`https://i.pravatar.cc/150?img=${(i%70)+1}`
}));

/* ==== Elements ==== */
const grid=document.getElementById('grid');
const empty=document.getElementById('empty');
const pager=document.getElementById('pager');
const countEl=document.getElementById('count');
const searchEl=document.getElementById('search');

/* ==== State ==== */
let state={q:'',page:1,limit:8,data:[...MOCK]};
countEl.textContent=state.data.length;

/* ==== Render ==== */
function renderGrid(){
  const {q,page,limit}=state;
  const ql=q.trim().toLowerCase();
  const filtered=state.data.filter(m=>!ql || m.name.toLowerCase().includes(ql));
  const total=filtered.length;
  const pages=Math.max(1,Math.ceil(total/limit));
  if(page>pages) state.page=pages;
  const start=(state.page-1)*limit;
  const slice=filtered.slice(start,start+limit);
  grid.innerHTML=slice.map(cardHTML).join('');
  empty.style.display=slice.length?'none':'block';
  renderPager(pages);
}

function cardHTML(m){
  return `
  <div class="card" data-id="${m.id}" data-name="${m.name}">
    <img class="avatar" src="${m.avatar}" alt="${m.name}">
    <div>
      <div class="name">${m.name}</div>
      <div class="meta">${m.email}</div>
      <div class="meta">${m.major} • 0225757 • Joined ${m.joined}</div>
      <span class="role-badge">${m.role}</span>
    </div>
    <div class="actions">
      <button class="btn ghost small" onclick="location.href='profile.php'">View</button>
    </div>
  </div>`;
}

function renderPager(pages){
  if(pages<=1){pager.innerHTML='';return;}
  pager.innerHTML=Array.from({length:pages},(_,i)=>{
    const p=i+1;
    return p===state.page?`<span class="active">${p}</span>`:`<a href="#" onclick="gotoPage(${p});return false;">${p}</a>`;
  }).join('');
}

function gotoPage(p){state.page=p;renderGrid();}

/* Live search */
searchEl.addEventListener('input',()=>{
  state.q=searchEl.value;
  state.page=1;
  renderGrid();
});

/* ==== Init ==== */
renderGrid();
window.gotoPage=gotoPage;
</script>
</body>
</html>

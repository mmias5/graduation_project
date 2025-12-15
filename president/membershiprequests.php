<?php
session_start();

if (!isset($_SESSION['student_id']) || ($_SESSION['role'] ?? '') !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$president_id = (int)$_SESSION['student_id'];

/* helpers */
function esc($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function media_url(?string $path): string {
    $p = trim((string)$path);
    if ($p === '') return '';
    if (preg_match('~^https?://~i', $p)) return $p;
    if (strpos($p, '/') === 0) return $p;
    return '../' . ltrim($p, '/'); // page inside /president/
}

/* get president club_id */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $president_id);
$stmt->execute();
$pres = $stmt->get_result()->fetch_assoc();
$stmt->close();

$club_id = isset($pres['club_id']) ? (int)$pres['club_id'] : 1;

$requests = [];
if ($club_id > 1) {
    $sql = "
      SELECT r.request_id, r.student_id, r.reason, r.submitted_at,
             s.student_name, s.email, s.major, s.profile_photo
      FROM club_membership_request r
      JOIN student s ON s.student_id = r.student_id
      WHERE r.club_id = ?
        AND LOWER(r.status) = 'pending'
      ORDER BY r.submitted_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $rr = $stmt->get_result();

    while ($row = $rr->fetch_assoc()) {
        $avatar = trim((string)($row['profile_photo'] ?? ''));
        if ($avatar === '') {
            $avatar = "https://i.pravatar.cc/150?u=" . urlencode("req_" . (int)$row['student_id']);
        } else {
            $avatar = media_url($avatar);
        }

        $submitted = $row['submitted_at'] ? date('Y-m-d', strtotime($row['submitted_at'])) : '—';

        $requests[] = [
            "request_id" => (int)$row['request_id'],
            "student_id" => (int)$row['student_id'],
            "name" => $row['student_name'] ?? '',
            "email" => $row['email'] ?? '',
            "major" => ($row['major'] ?? '') !== '' ? $row['major'] : '—',
            "reason" => $row['reason'] ?? '',
            "submitted" => $submitted,
            "avatar" => $avatar
        ];
    }
    $stmt->close();
}

/* CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Membership Requests</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8;
  --paper:#eef2f7; --ink:#0e1228; --card:#fff; --gold:#e5b758;
  --shadow:0 14px 34px rgba(10,23,60,.12); --radius:20px; --maxw:1100px;
}
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
body > footer{ margin-top:auto !important; }

.header-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:8px 0 16px}
.title{font-size:28px;font-weight:900;letter-spacing:.02em;margin:0;color:var(--navy)}
.subtitle{color:#667085;font-weight:700}

.toolbar{
  background:var(--card); border:1px solid #e1e6f0; border-radius:16px; box-shadow:var(--shadow);
  padding:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;
}
.input{
  flex:1; border-radius:12px; border:1px solid #d5dbea; padding:10px 12px; font-size:14px; outline:none; background:#fff;
}
.input:focus{border-color:var(--royal); box-shadow:0 0 0 4px rgba(72,113,219,.12)}

.grid{display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-top:16px}
@media (max-width:820px){ .grid{grid-template-columns:1fr} }
.card{
  background:var(--card); border:1px solid #e6e8f2; border-radius:16px; box-shadow:var(--shadow);
  padding:14px; display:grid; grid-template-columns:72px 1fr auto; gap:12px; align-items:center;
}
.avatar{width:72px;height:72px;border-radius:14px;object-fit:cover;background:#eef2ff;border:1px solid #e6e8f2}
.name{font-weight:900;font-size:16px}
.meta{color:#596180;font-size:13px}
.role-badge{display:inline-block;background:#e8f5e9;border:1px solid #b2dfdb;color:#046c4e;font-weight:800;border-radius:999px;padding:6px 10px;font-size:12px;margin-top:6px}

.actions{display:flex;gap:8px; flex-wrap:wrap}
.btn{
  cursor:pointer;
  user-select:none;
  transition:transform .16s ease, box-shadow .16s ease, background-color .16s ease, border-color .16s ease;
  border:0;
}
.btn.small{padding:8px 10px;font-size:12px;border-radius:10px}
.btn.ghost{background:#fff;border:1px solid #e6e8f2;color:#1a1f36}
.btn.ghost:hover{transform:translateY(-1px); box-shadow:0 6px 16px rgba(16,24,40,.12)}
.btn.accept{background:#e7f5ec;border:1px solid #b2e2c4;color:#046c4e;font-weight:800}
.btn.accept:hover{background:#d2f0de; transform:translateY(-1px); box-shadow:0 6px 16px rgba(16,24,40,.12)}
.btn.reject{background:#fff;border:2px solid #ffdddd;color:#b42318;font-weight:800}
.btn.reject:hover{background:#ffecec; transform:translateY(-1px)}

.empty{
  text-align:center;background:var(--card);border:1px solid #e6e8f2;border-radius:16px;padding:28px;box-shadow:var(--shadow);color:#596180
}
.pager{display:flex;gap:8px;justify-content:center;margin:18px 0}
.pager a, .pager span{
  padding:8px 12px;border-radius:10px;border:1px solid #e1e6f0;background:#fff;text-decoration:none;color:#1a1f36;font-weight:800
}
.pager .active{background:var(--royal);color:#fff;border-color:transparent}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrap">
  <div class="header-row">
    <h1 class="title">Membership Requests</h1>
    <div class="subtitle"><span id="count"></span> pending</div>
  </div>

  <div class="toolbar">
    <input id="search" class="input" type="search" placeholder="Search by name…">
  </div>

  <div id="grid" class="grid"></div>
  <div id="empty" class="empty" style="display:none">No membership requests match your search.</div>

  <div id="pager" class="pager"></div>
</div>

<?php include 'footer.php'; ?>

<script>
const CSRF = <?php echo json_encode($csrf); ?>;
const CLUB_ID = <?php echo (int)$club_id; ?>;
const REQUESTS = <?php echo json_encode($requests, JSON_UNESCAPED_SLASHES); ?>;

const grid=document.getElementById('grid');
const empty=document.getElementById('empty');
const pager=document.getElementById('pager');
const countEl=document.getElementById('count');
const searchEl=document.getElementById('search');

let state={q:'',page:1,limit:8,data:[...REQUESTS]};
countEl.textContent=state.data.length;

function renderGrid(){
  const ql = (state.q||'').trim().toLowerCase();
  const filtered = state.data.filter(m => !ql || (m.name||'').toLowerCase().includes(ql));
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total/state.limit));
  if(state.page>pages) state.page=pages;

  const start=(state.page-1)*state.limit;
  const slice=filtered.slice(start,start+state.limit);

  grid.innerHTML=slice.map(cardHTML).join('');
  empty.style.display=slice.length?'none':'block';
  renderPager(pages);
  countEl.textContent=total;
}

function cardHTML(m){
  const reason = (m.reason || '').trim();
  const reasonHtml = reason
    ? `<div class="meta" style="margin-top:8px">
         <strong>Reason:</strong> ${escapeHtml(reason)}
       </div>`
    : '';

  return `
  <div class="card" data-request="${m.request_id}">
    <img class="avatar" src="${m.avatar}" alt="${escapeHtml(m.name)}">
    <div>
      <div class="name">${escapeHtml(m.name)}</div>
      <div class="meta">${escapeHtml(m.email)}</div>
      <div class="meta">${escapeHtml(m.major)} • Request sent ${escapeHtml(m.submitted)}</div>
      ${reasonHtml}
      <span class="role-badge">Pending request</span>
    </div>
    <div class="actions">
      <button class="btn ghost small" type="button" onclick="location.href='profile.php?id=${m.student_id}'">View</button>
      <button class="btn accept small" type="button" onclick="acceptRequest(${m.request_id})">Accept</button>
      <button class="btn reject small" type="button" onclick="rejectRequest(${m.request_id})">Reject</button>
    </div>
  </div>`;
}

function renderPager(pages){
  if(pages<=1){pager.innerHTML='';return;}
  pager.innerHTML=Array.from({length:pages},(_,i)=>{
    const p=i+1;
    return p===state.page
      ? `<span class="active">${p}</span>`
      : `<a href="#" onclick="gotoPage(${p});return false;">${p}</a>`;
  }).join('');
}
function gotoPage(p){state.page=p;renderGrid();}

searchEl.addEventListener('input',()=>{
  state.q=searchEl.value;
  state.page=1;
  renderGrid();
});

function escapeHtml(str){
  return String(str ?? '').replace(/[&<>"']/g, s => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[s]));
}

async function acceptRequest(requestId){ await decide(requestId, 'accept'); }
async function rejectRequest(requestId){ await decide(requestId, 'reject'); }

async function decide(requestId, action){
  try{
    const fd = new FormData();
    fd.append('action', action);
    fd.append('request_id', String(requestId));
    fd.append('csrf_token', CSRF);

    const res = await fetch('members_actions.php', { method:'POST', body: fd });
    const data = await res.json();

    if(!data.ok){
      alert(data.error || 'Operation failed');
      return;
    }

    state.data = state.data.filter(r => r.request_id !== requestId);
    renderGrid();
  }catch(e){
    alert('Network error');
  }
}

renderGrid();
window.gotoPage=gotoPage;
window.acceptRequest=acceptRequest;
window.rejectRequest=rejectRequest;
</script>
</body>
</html>

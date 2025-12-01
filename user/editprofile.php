<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    // لو بدك تخلي الـ president يدخل على صفحة مختلفة
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_president') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Edit Profile Photo</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8;
  --paper:#eef2f7; --ink:#0e1228; --card:#fff; --gold:#e5b758;
  --shadow:0 18px 38px rgba(12,22,60,.16); --radius:22px; --maxw:1100px;
}
*{box-sizing:border-box} html,body{margin:0}
body{
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
}

.wrap{max-width:var(--maxw); margin:28px auto 80px; padding:0 18px}

/* hero card */
.hero{
  position:relative;border-radius:26px;overflow:hidden;
  background:#4871db;
  box-shadow:var(--shadow);
  min-height:220px;
  color:#fff;
  display:flex;align-items:flex-end;
}
.hero::after{
  content:"";position:absolute;inset:0;
  background:radial-gradient(600px 300px at 10% 10%,rgba(255,255,255,.18),transparent 60%);
}
.hero-inner{
  position:relative;z-index:1;width:100%;
  display:grid;grid-template-columns:170px 1fr; /* balanced size (same as profile page) */
  gap:20px;align-items:flex-end;padding:24px 22px 26px;
}
@media (max-width:720px){
  .hero-inner{grid-template-columns:1fr;justify-items:center;text-align:center}
}

/* avatar + edit controls */
.avatar-wrap{
  position:relative;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:10px;
}

/* PERFECT BALANCED AVATAR SIZE */
.avatar{
  width:160px;
  height:160px;
  border-radius:26px;
  object-fit:cover;
  border:4px solid rgba(255,255,255,.9);
  background:#dfe5ff;
  box-shadow:0 12px 26px rgba(0,0,0,.18);
}

.avatar-actions{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  justify-content:center;
}
.chip-btn{
  appearance:none;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.75);
  background:rgba(255,255,255,.12);
  color:#fff;
  padding:6px 12px;
  font-size:12px;
  font-weight:800;
  letter-spacing:.03em;
  text-transform:uppercase;
  cursor:pointer;
  backdrop-filter:blur(6px);
}
.chip-btn.secondary{
  background:transparent;
  border-style:dashed;
}

/* text area */
.name{font-size:30px;font-weight:900;margin:0}
.sub{opacity:.95;font-weight:700;margin-top:4px}
.badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.role{
  display:inline-flex;align-items:center;gap:8px;
  background:#fff7e6;border:1px solid #ffecb5;
  color:#8a5b00;font-weight:900;
  border-radius:999px;padding:6px 12px;font-size:12px;
}
.joined{
  display:inline-flex;align-items:center;gap:8px;
  background:#f2f5ff;border:1px solid #e6e8f2;
  color:#1f2a6b;font-weight:900;
  border-radius:999px;padding:6px 12px;font-size:12px;
}

/* content */
.card{
  background:var(--card);
  border:1px solid #e6e8f2;
  border-radius:18px;
  box-shadow:var(--shadow);
  padding:20px;
  margin-top:20px;
}
.card h3{margin:0 0 14px;font-size:18px;color:var(--navy)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:620px){ .grid{grid-template-columns:1fr} }

.kv{
  background:#f6f8ff;
  border:1px solid #e7ecff;
  border-radius:14px;
  padding:12px;
}
.kv b{
  display:block;
  font-size:12px;
  color:#596180;
  margin-bottom:4px;
}
.kv span{
  font-size:15px;
  color:#1a1f36;
  font-weight:700;
}

/* bottom actions */
.form-actions{
  display:flex;
  justify-content:flex-end;
  gap:10px;
  margin-top:18px;
  flex-wrap:wrap;
}
.btn{
  appearance:none;
  border-radius:12px;
  padding:10px 16px;
  font-weight:800;
  border:1px solid #d5dbea;
  background:#fff;
  color:#1a1f36;
  cursor:pointer;
}
.btn.primary{
  border:0;
  background:linear-gradient(135deg,#5d7ff2,#3664e9);
  color:#fff;
  box-shadow:0 8px 20px rgba(54,100,233,.22);
}
.btn.ghost{
  background:transparent;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrap">

  <!-- HERO -->
  <section class="hero">
    <div class="hero-inner">
      <div class="avatar-wrap">
        <img id="avatar" class="avatar" src="" alt="Member avatar">
        <div class="avatar-actions">
          <button type="button" id="changePhotoBtn" class="chip-btn">Change Photo</button>
          <button type="button" id="removePhotoBtn" class="chip-btn secondary">Remove</button>
        </div>

        <input type="file" id="photoInput" accept="image/*" style="display:none">
      </div>

      <div>
        <h2 id="name" class="name">Member Name</h2>
        <div id="email" class="sub">email@university.edu</div>
        <div class="badges">
          <span id="role" class="role">President</span>
          <span id="joined" class="joined">Joined — 2025-01-01</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ABOUT INFO -->
  <section class="card">
    <h3>Member Info</h3>
    <div class="grid">
      <div class="kv"><b>Full name</b><span id="aboutName">—</span></div>
      <div class="kv"><b>Email</b><span id="aboutEmail">—</span></div>
      <div class="kv"><b>Major</b><span id="major">—</span></div>
      <div class="kv"><b>Student ID</b><span id="studentId">—</span></div>
    </div>

    <div class="form-actions">
      <button type="button" class="btn ghost" onclick="window.location.href='index.php'">Cancel</button>
      <button type="button" id="saveBtn" class="btn primary">Save Changes</button>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>

<script>
const MOCK = Array.from({length:10}).map((_,i)=>({
  id:i+1,
  name:['Lina','Omar','Sara','Mustafa','Noor','Jad','Maya','Hiba','Yousef','Rami'][i],
  email:`member${i+1}@university.edu`,
  major:['CS','IT','Business','Design'][i%4],
  studentId:`02257${50 + i}`,
  role:['President','Member'][i%2],
  joined:`2025-0${(i%9)+1}-${String(((i*3)%28)+1).padStart(2,'0')}`,
  avatar:`https://i.pravatar.cc/200?img=${(i%70)+1}`
}));

const defaultPlaceholder =
  'https://images.unsplash.com/photo-1527980965255-d3b416303d12?auto=format&fit=crop&w=600&q=80';

const id = Number(new URLSearchParams(location.search).get('id')) || 1;
const m = MOCK.find(x=>x.id===id) || MOCK[0];

const avatarEl = document.getElementById('avatar');
avatarEl.src = m.avatar;

document.getElementById('name').textContent = m.name;
document.getElementById('email').textContent = m.email;
document.getElementById('role').textContent = m.role;
document.getElementById('joined').textContent = 'Joined — ' + m.joined;

document.getElementById('aboutName').textContent = m.name;
document.getElementById('aboutEmail').textContent = m.email;
document.getElementById('major').textContent = m.major;
document.getElementById('studentId').textContent = m.studentId;

// Photo selection logic
const changeBtn = document.getElementById('changePhotoBtn');
const removeBtn = document.getElementById('removePhotoBtn');
const fileInput = document.getElementById('photoInput');
const saveBtn = document.getElementById('saveBtn');

let pendingPhotoDataUrl = null;

changeBtn.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = e => {
    pendingPhotoDataUrl = e.target.result;
    avatarEl.src = pendingPhotoDataUrl;
  };
  reader.readAsDataURL(file);
});

removeBtn.addEventListener('click', () => {
  pendingPhotoDataUrl = null;
  avatarEl.src = defaultPlaceholder;
  alert('Preview: photo removed. Save changes to confirm.');
});

saveBtn.addEventListener('click', () => {
  alert('Front-end demo: here you would save to server.');
});
</script>

</body>
</html>

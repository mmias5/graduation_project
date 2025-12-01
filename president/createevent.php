<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}
// event_create.php
// session_start();
// Optional: $club_id = $_GET['club_id'] ?? ($_SESSION['club_id'] ?? 1);
// Optional leader check here before showing the form.
$club_id = 1;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Create Event</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8;
  --paper:#eef2f7; --ink:#0e1228; --gold:#f4df6d; --card:#ffffff;
  --shadow:0 14px 34px rgba(10,23,60,.12); --radius:22px;
}
*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
}

/* layout */
.wrapper{max-width:980px;margin:24px auto 80px;padding:0 16px}
.card{
  background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
  border:1px solid #e1e6f0; padding:24px; position:relative;
}
.header-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
.title{font-size:28px;font-weight:900;letter-spacing:.02em;margin:0}
.subtle{color:#6b7280;margin:0 0 16px}

/* hero preview banner */
.banner{
  position:relative; border-radius:18px; overflow:hidden; height:180px;
  background: #dfe7ff; margin-bottom:18px; border:1px solid #e6e8f2;
}
.banner img{width:100%;height:100%;object-fit:cover;display:block}
.banner .overlay{
  position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.0), rgba(0,0,0,.35));
}
.banner .banner-text{
  position:absolute; left:16px; bottom:12px; color:#fff; text-shadow:0 8px 22px rgba(0,0,0,.35);
  display:flex; flex-direction:column; gap:2px;
}
.banner .name{font-size:18px;font-weight:800}
.banner .when{font-size:13px;opacity:.95}

/* form grid */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid.full{grid-template-columns:1fr}
@media (max-width:800px){ .form-grid{grid-template-columns:1fr} }

.field{display:flex;flex-direction:column;gap:8px}
.label{font-weight:800;color:var(--navy);font-size:14px}
.input,.select,.textarea{
  width:100%;border:1px solid #d5dbea;border-radius:14px;padding:12px 14px;font-size:15px;background:#fff;outline:none;
  transition:border-color .12s ease, box-shadow .12s ease;
}
.textarea{min-height:120px;resize:vertical}
.input:focus,.select:focus,.textarea:focus{border-color:var(--royal); box-shadow:0 0 0 4px rgba(72,113,219,.12)}
.hint{font-size:12px;color:#6b7280}

/* uploader */
.uploader{display:flex;align-items:center;gap:14px;flex-wrap:wrap;border:1px dashed #cad2e3;border-radius:14px;padding:12px}
.thumb{width:140px;height:80px;border-radius:12px;background:#f2f5ff;overflow:hidden;display:grid;place-items:center;border:1px solid #e5e7eb}
.thumb img{width:100%;height:100%;object-fit:cover}
.uploader input[type=file]{display:none}
.pick{display:inline-block;padding:10px 12px;border-radius:10px;background:#f3f4ff;color:#1f2a6b;font-weight:800;cursor:pointer}

/* actions */
.actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;flex-wrap:wrap}
.btn{appearance:none;border:0;border-radius:12px;padding:12px 16px;font-weight:800;cursor:pointer}
.btn.ghost{background:#eef2ff;color:#1f2a6b}
.btn.primary{background:linear-gradient(135deg,#5d7ff2,#3664e9);color:#fff;box-shadow:0 8px 20px rgba(54,100,233,.22)}
.btn.primary:hover{background:linear-gradient(135deg,#4d70ee,#2958e0)}

/* toast */
.toast{position:fixed;right:16px;bottom:16px;background:#10b981;color:#fff;padding:12px 14px;border-radius:12px;box-shadow:var(--shadow);font-weight:800;display:none;z-index:999}
.toast.show{display:block;animation:fadein .18s ease}
@keyframes fadein{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrapper">
  <div class="card">
    <div class="header-row">
      <h1 class="title">Create Event</h1>
      <a class="btn ghost" href="index.php">Back to home page</a>
    </div>
    <p class="subtle">Add a new event for your club. You can edit it later from the events list.</p>

    <!-- Preview banner -->
    <div class="banner" id="banner">
      <img id="bannerImg" src="tools/pics/social_life.png" alt="Event cover">
      <div class="overlay"></div>
      <div class="banner-text">
        <div class="name" id="previewTitle">Event title</div>
        <div class="when" id="previewWhen">Date • Start–End</div>
      </div>
    </div>

    <!-- Form -->
    <form id="createEventForm" action="create_event.php" method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="club_id" value="<?= (int)$club_id ?>">

      <div class="form-grid">
        <div class="field">
          <label class="label" for="title">Title</label>
          <input class="input" id="title" name="title" maxlength="255" placeholder="e.g., Hack Night" required>
          <span class="hint">Short, clear name students will recognize.</span>
        </div>

        <div class="field">
          <label class="label" for="location">Location</label>
          <input class="input" id="location" name="location" maxlength="255" placeholder="e.g., Main Hall / Room B204" required>
        </div>

        <div class="field">
          <label class="label" for="date">Date</label>
          <input class="input" id="date" name="date" type="date" required>
        </div>

        <div class="field">
          <label class="label" for="start_time">Start time</label>
          <input class="input" id="start_time" name="start_time" type="time" required>
        </div>

        <div class="field">
          <label class="label" for="end_time">End time</label>
          <input class="input" id="end_time" name="end_time" type="time" required>
          <span class="hint">End must be after start.</span>
        </div>

       <?php $oldCategory = $_POST['category'] ?? ''; ?>
<div class="field">
  <label class="label" for="category">Category</label>
  <input class="input" id="category" name="category"
         value="<?= htmlspecialchars($oldCategory) ?>" placeholder="e.g., Technology">
</div>


        <div class="field">
          <label class="label" for="sponsor">Sponsor (optional)</label>
          <input class="input" id="sponsor" name="sponsor" maxlength="255" placeholder="e.g., TechCorp">
        </div>

        <div class="field" style="grid-column:1 / -1">
          <label class="label" for="description">Description</label>
          <textarea class="textarea" id="description" name="description" maxlength="1000" placeholder="Describe what will happen, agenda, speakers, and who should join..." required></textarea>
        </div>

        <!-- cover upload -->
        <div class="field" style="grid-column:1 / -1">
          <label class="label">Cover image (optional)</label>
          <div class="uploader">
            <div class="thumb"><img id="coverPreview" src="tools/pics/social_life.png" alt="Cover preview"></div>
            <div>
              <label class="pick" for="cover">Choose file</label>
              <input id="cover" name="cover" type="file" accept="image/*">
              <div class="hint">Wide 1200×600 works well (JPG/PNG/WebP).</div>
            </div>
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="btn primary" id="submitBtn" type="submit">Create Event</button>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

<div class="toast" id="toast">Event created ✓</div>

<script>
// banner live preview
const titleEl = document.getElementById('title');
const dateEl = document.getElementById('date');
const startEl = document.getElementById('start_time');
const endEl = document.getElementById('end_time');
const whenOut = document.getElementById('previewWhen');
const titleOut = document.getElementById('previewTitle');

function fmtTime(v){
  if(!v) return '';
  const [h,m] = v.split(':'); let hh = +h;
  const ampm = hh >= 12 ? 'PM' : 'AM';
  hh = hh % 12 || 12;
  return `${hh}:${m} ${ampm}`;
}
function fmtDate(v){
  if(!v) return '';
  const d = new Date(v + 'T00:00:00');
  const opt = {year:'numeric',month:'short',day:'2-digit'};
  return d.toLocaleDateString(undefined,opt);
}
function updateWhen(){
  const d = fmtDate(dateEl.value);
  const s = fmtTime(startEl.value);
  const e = fmtTime(endEl.value);
  whenOut.textContent = d && s && e ? `${d} • ${s}–${e}` : 'Date • Start–End';
}
['input','change'].forEach(ev=>{
  titleEl.addEventListener(ev, ()=> titleOut.textContent = titleEl.value || 'Event title');
  dateEl.addEventListener(ev, updateWhen);
  startEl.addEventListener(ev, updateWhen);
  endEl.addEventListener(ev, updateWhen);
});

// image preview + banner background
const coverInput = document.getElementById('cover');
const coverPreview = document.getElementById('coverPreview');
const bannerImg = document.getElementById('bannerImg');
coverInput.addEventListener('change', ()=>{
  const f = coverInput.files?.[0]; if(!f) return;
  const url = URL.createObjectURL(f);
  coverPreview.src = url;
  bannerImg.src = url;
});

// basic validation and double-submit guard
const form = document.getElementById('createEventForm');
const submitBtn = document.getElementById('submitBtn');
form.addEventListener('submit', (e)=>{
  // end after start check
  const s = startEl.value, nd = endEl.value;
  if(s && nd && s >= nd){
    e.preventDefault();
    alert('End time must be after start time.');
    endEl.focus();
    return;
  }
  submitBtn.disabled = true;
});

// toast if redirected with ?created=1
(function(){
  const p = new URLSearchParams(location.search);
  if(p.get('created')==='1'){
    const t = document.getElementById('toast');
    t.classList.add('show'); setTimeout(()=>t.classList.remove('show'), 2400);
  }
})();
</script>
</body>
</html>

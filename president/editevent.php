<?php
// event_edit.php
// session_start();
// if($_SESSION['role'] !== 'leader'){ http_response_code(403); exit('Forbidden'); }
$event_id = $_GET['id'] ?? 1;

/* TODO: load the event from DB by $event_id.
   Demo hard-coded values below (replace with your fetch). */
$event = [
  'title' => 'CCH Tech & Innovation Meetup — Fall 2025',
  'date' => '2025-11-20',
  'start_time' => '16:00',
  'end_time' => '19:30',
  'location' => 'Amman, JU Main Campus — Innovation Hall',
  'category' => 'Technology',
  'audience' => 'All universities • Students & Club Leaders',
  'description' => 'Hands-on workshops, panels, and networking across universities. Powered by CCH.',
  'cover' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?q=80&w=1600&auto=format&fit=crop',
  'notes' => "General admission is free.\nPlease bring your student ID.\nSnacks & coffee provided."
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Edit Event</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751; --royal:#4871db; --light:#a9bff8; --paper:#eef2f7; --ink:#0e1228; --card:#fff;
  --shadow:0 14px 34px rgba(10,23,60,.12); --radius:22px; --maxw:1100px;
}
*{box-sizing:border-box} html,body{margin:0}
body{font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:linear-gradient(180deg,#f9fbff,#eef2f7)}

.wrapper{max-width:var(--maxw); margin:26px auto 80px; padding:0 20px}
.card{background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); border:1px solid #e1e6f0; padding:24px}
.header-row{display:flex;justify-content:space-between;align-items:center;gap:12px}
.title{font-size:28px;font-weight:900;letter-spacing:.02em;margin:0}
.actions{display:flex;gap:10px}
.btn{appearance:none;border:0;border-radius:12px;padding:12px 16px;font-weight:800;cursor:pointer}
.btn.ghost{background:#eef2ff;color:#1f2a6b}
.btn.primary{background:linear-gradient(135deg,#5d7ff2,#3664e9);color:#fff;box-shadow:0 8px 20px rgba(54,100,233,.22)}
.btn.primary:hover{background:linear-gradient(135deg,#4d70ee,#2958e0)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
.form-grid.full{grid-template-columns:1fr}
@media (max-width:860px){ .form-grid{grid-template-columns:1fr} }
.field{display:flex;flex-direction:column;gap:8px}
.label{font-weight:800;color:var(--navy);font-size:14px}
.input,.select,.textarea{
  width:100%;border:1px solid #d5dbea;border-radius:14px;padding:12px 14px;font-size:15px;background:#fff;outline:none;
  transition:border-color .12s ease, box-shadow .12s ease;
}
.textarea{min-height:120px;resize:vertical}
.input:focus,.select:focus,.textarea:focus{border-color:var(--royal); box-shadow:0 0 0 4px rgba(72,113,219,.12)}
.hint{font-size:12px;color:#6b7280}

/* banner */
.banner{position:relative; border-radius:18px; overflow:hidden; height:200px; background:#dfe7ff; margin-top:16px; border:1px solid #e6e8f2}
.banner img{width:100%;height:100%;object-fit:cover;display:block}
.banner .overlay{position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.0), rgba(0,0,0,.35))}
.banner .text{position:absolute; left:16px; bottom:12px; color:#fff; text-shadow:0 8px 22px rgba(0,0,0,.35)}
.banner .name{font-size:18px;font-weight:800}
.banner .when{font-size:13px;opacity:.95}

.uploader{display:flex;align-items:center;gap:14px;flex-wrap:wrap;border:1px dashed #cad2e3;border-radius:14px;padding:12px}
.thumb{width:160px;height:90px;border-radius:12px;background:#f2f5ff;overflow:hidden;display:grid;place-items:center;border:1px solid #e5e7eb}
.thumb img{width:100%;height:100%;object-fit:cover}
.uploader input[type=file]{display:none}
.pick{display:inline-block;padding:10px 12px;border-radius:10px;background:#f3f4ff;color:#1f2a6b;font-weight:800;cursor:pointer}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrapper">
  <div class="card">
    <div class="header-row">
      <h1 class="title">Edit Event</h1>
      <div class="actions">
        <a class="btn ghost" href="event_details.php?id=<?= htmlspecialchars($event_id) ?>">Cancel</a>
        <button form="eventForm" class="btn primary" type="submit">Save changes</button>
      </div>
    </div>

    <!-- Banner preview -->
    <div class="banner" id="banner">
      <img id="bannerImg" src="<?= htmlspecialchars($event['cover']) ?>" alt="Event cover">
      <div class="overlay"></div>
      <div class="text">
        <div class="name" id="pTitle"><?= htmlspecialchars($event['title']) ?></div>
        <div class="when" id="pWhen"></div>
      </div>
    </div>

    <form id="eventForm" action="update_event.php" method="POST" enctype="multipart/form-data" style="margin-top:16px">
      <input type="hidden" name="id" value="<?= htmlspecialchars($event_id) ?>">

      <div class="form-grid">
        <div class="field">
          <label class="label" for="title">Title</label>
          <input class="input" id="title" name="title" value="<?= htmlspecialchars($event['title']) ?>" required maxlength="255">
        </div>

        <div class="field">
          <label class="label" for="location">Location</label>
          <input class="input" id="location" name="location" value="<?= htmlspecialchars($event['location']) ?>" required maxlength="255">
        </div>

        <div class="field">
          <label class="label" for="date">Date</label>
          <input class="input" id="date" name="date" type="date" value="<?= htmlspecialchars($event['date']) ?>" required>
        </div>

        <div class="field">
          <label class="label" for="start_time">Start time</label>
          <input class="input" id="start_time" name="start_time" type="time" value="<?= htmlspecialchars($event['start_time']) ?>" required>
        </div>

        <div class="field">
          <label class="label" for="end_time">End time</label>
          <input class="input" id="end_time" name="end_time" type="time" value="<?= htmlspecialchars($event['end_time']) ?>" required>
          <span class="hint">End must be after start.</span>
        </div>

        <div class="field">
          <label class="label" for="category">Category</label>
          <input class="input" id="category" name="category" value="<?= htmlspecialchars($event['category']) ?>">
        </div>

        <div class="field">
          <label class="label" for="sponsor">Sponsor (optional)</label>
          <input class="input" id="sponsor" name="sponsor" maxlength="255" placeholder="e.g., TechCorp">
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="label" for="description">Description</label>
          <textarea class="textarea" id="description" name="description" required maxlength="1500"><?= htmlspecialchars($event['description']) ?></textarea>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="label">Cover image</label>
          <div class="uploader">
            <div class="thumb"><img id="coverPreview" src="<?= htmlspecialchars($event['cover']) ?>" alt="Cover preview"></div>
            <div>
              <label class="pick" for="cover">Choose file</label>
              <input id="cover" name="cover" type="file" accept="image/*">
              <div class="hint">Recommended 1200×675 (JPG/PNG/WebP). Leave empty to keep current cover.</div>
            </div>
          </div>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="label" for="notes">Tickets & Notes</label>
          <textarea class="textarea" id="notes" name="notes" placeholder="One item per line"><?= htmlspecialchars($event['notes']) ?></textarea>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
function fmtTime(v){ if(!v) return ''; const [h,m]=v.split(':'); let hh=+h; const am=hh>=12?'PM':'AM'; hh=hh%12||12; return `${hh}:${m} ${am}` }
function fmtDate(v){ if(!v) return ''; const d=new Date(v+'T00:00:00'); return d.toLocaleDateString(undefined,{year:'numeric',month:'short',day:'2-digit'}) }
function updatePreview(){
  const title=document.getElementById('title').value;
  const date=document.getElementById('date').value;
  const st=document.getElementById('start_time').value;
  const et=document.getElementById('end_time').value;
  document.getElementById('pTitle').textContent = title || 'Event title';
  document.getElementById('pWhen').textContent = (date?fmtDate(date):'Date') + ' • ' + (st?fmtTime(st):'Start') + '–' + (et?fmtTime(et):'End');
}
['title','date','start_time','end_time'].forEach(id=>{
  document.getElementById(id).addEventListener('input',updatePreview);
  document.getElementById(id).addEventListener('change',updatePreview);
});
updatePreview();

// cover preview
const coverInput=document.getElementById('cover');
const coverPreview=document.getElementById('coverPreview');
const bannerImg=document.getElementById('bannerImg');
coverInput.addEventListener('change', ()=>{
  const f=coverInput.files?.[0]; if(!f) return;
  const url=URL.createObjectURL(f);
  coverPreview.src=url;
  document.getElementById('bannerImg').src=url;
});

// basic time validation on submit
document.getElementById('eventForm').addEventListener('submit', (e)=>{
  const s=document.getElementById('start_time').value;
  const nd=document.getElementById('end_time').value;
  if(s && nd && s>=nd){ e.preventDefault(); alert('End time must be after start time.'); }
});
</script>
</body>
</html>

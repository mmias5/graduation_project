<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$president_id = (int)$_SESSION['student_id'];
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($event_id <= 0) { header('Location: index.php'); exit; }

/* fix image paths without changing DB values */
function cch_img($path){
    if (!$path) return '';
    if (preg_match('/^https?:\/\//i', $path)) return $path; // full URL
    if ($path[0] === '/') return $path;                     // absolute path
    return '../' . ltrim($path, '/');                       // make uploads/... work from /president/
}

/* CSRF */
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

/* helpers for extra fields inside description */
function cch_get_tag($text, $tag) {
    if (!$text) return '';
    $pattern = '/\[' . preg_quote($tag,'/') . '\](.*?)\[\/' . preg_quote($tag,'/') . '\]/s';
    if (preg_match($pattern, $text, $m)) return trim($m[1]);
    return '';
}
function cch_build_description($desc, $category, $sponsor, $notes) {
    $desc = trim((string)$desc);
    $category = trim((string)$category);
    $sponsor = trim((string)$sponsor);
    $notes = trim((string)$notes);

    return
        "[CCH_DESC]\n{$desc}\n[/CCH_DESC]\n" .
        "[CCH_CATEGORY]\n{$category}\n[/CCH_CATEGORY]\n" .
        "[CCH_SPONSOR]\n{$sponsor}\n[/CCH_SPONSOR]\n" .
        "[CCH_NOTES]\n{$notes}\n[/CCH_NOTES]\n";
}

/* get president club_id */
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? AND role='club_president' LIMIT 1");
$stmt->bind_param("i", $president_id);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();

$club_id = isset($pres['club_id']) ? (int)$pres['club_id'] : 1;
if ($club_id <= 1) { header('Location: index.php'); exit; }

/* load event (must belong to president club) */
$stmt = $conn->prepare("
    SELECT event_id, event_name, description, event_location, max_attendees,
           starting_date, ending_date, banner_image, club_id
    FROM event
    WHERE event_id=? AND club_id=?
    LIMIT 1
");
$stmt->bind_param("ii", $event_id, $club_id);
$stmt->execute();
$res = $stmt->get_result();
$eventRow = $res->fetch_assoc();
$stmt->close();

if (!$eventRow) { header('Location: index.php'); exit; }

/* split old data to form fields */
$rawDesc = $eventRow['description'] ?? '';

/* cover comes from DB banner_image (with correct path). Fallback only if empty */
$coverRaw = $eventRow['banner_image'] ?? '';
$coverFinal = $coverRaw ? cch_img($coverRaw) : "https://images.unsplash.com/photo-1551836022-d5d88e9218df?q=80&w=1600&auto=format&fit=crop";

$event = [
  'title' => $eventRow['event_name'] ?? '',
  'location' => $eventRow['event_location'] ?? '',
  'max_attendees' => $eventRow['max_attendees'],
  'date' => '',
  'start_time' => '',
  'end_time' => '',
  'category' => cch_get_tag($rawDesc, 'CCH_CATEGORY'),
  'sponsor' => cch_get_tag($rawDesc, 'CCH_SPONSOR'),
  'description' => cch_get_tag($rawDesc, 'CCH_DESC'),
  'notes' => cch_get_tag($rawDesc, 'CCH_NOTES'),
  'cover' => $coverFinal,
];
if ($event['description'] === '') $event['description'] = trim($rawDesc);

if (!empty($eventRow['starting_date'])) {
    $dt = new DateTime($eventRow['starting_date']);
    $event['date'] = $dt->format('Y-m-d');
    $event['start_time'] = $dt->format('H:i');
}
if (!empty($eventRow['ending_date'])) {
    $dt = new DateTime($eventRow['ending_date']);
    $event['end_time'] = $dt->format('H:i');
}

$error = '';
$info = '';

/* If there is already a pending request, warn */
$stmt = $conn->prepare("
  SELECT request_id, submitted_at
  FROM event_edit_request
  WHERE event_id=? AND club_id=? AND status='Pending'
  ORDER BY submitted_at DESC
  LIMIT 1
");
$stmt->bind_param("ii", $event_id, $club_id);
$stmt->execute();
$pendingRes = $stmt->get_result();
$pending = $pendingRes->fetch_assoc();
$stmt->close();
if ($pending) {
    $info = "There is already a pending edit request for this event (submitted " . date('Y-m-d H:i', strtotime($pending['submitted_at'])) . ").";
}

/* handle POST: create request */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request. Please refresh and try again.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $date = $_POST['date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $category = trim($_POST['category'] ?? '');
        $sponsor = trim($_POST['sponsor'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $max_attendees = (isset($_POST['max_attendees']) && $_POST['max_attendees'] !== '') ? (int)$_POST['max_attendees'] : null;

        if ($title==='' || $location==='' || $date==='' || $start_time==='' || $end_time==='' || $description==='') {
            $error = "Please fill all required fields.";
        } elseif ($start_time >= $end_time) {
            $error = "End time must be after start time.";
        } else {
            $startDT = $date . " " . $start_time . ":00";
            $endDT   = $date . " " . $end_time . ":00";

            /* Cover upload (optional) -> store with request only */
            $newCoverPath = null;
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
                    $error = "Cover upload failed.";
                } else {
                    $tmp = $_FILES['cover']['tmp_name'];
                    $size = (int)$_FILES['cover']['size'];
                    if ($size > 3 * 1024 * 1024) {
                        $error = "Cover image too large. Max 3MB.";
                    } else {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmp);
                        finfo_close($finfo);

                        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                        if (!isset($allowed[$mime])) {
                            $error = "Only JPG, PNG, or WEBP allowed.";
                        } else {
                            $ext = $allowed[$mime];
                            $uploadDir = __DIR__ . '/../uploads/event_banners_requests/';
                            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

                            $filename = "eventreq_" . $event_id . "_" . time() . "." . $ext;
                            $destPath = $uploadDir . $filename;

                            if (!move_uploaded_file($tmp, $destPath)) {
                                $error = "Could not save the cover. Check folder permissions.";
                            } else {
                                $newCoverPath = 'uploads/event_banners_requests/' . $filename;
                            }
                        }
                    }
                }
            }

            if (!$error) {
                $finalDesc = cch_build_description($description, $category, $sponsor, $notes);

                $stmt = $conn->prepare("
                  INSERT INTO event_edit_request
(event_id, club_id, requested_by_student_id, status,
 new_event_name, new_event_location, new_description,
 new_starting_date, new_ending_date,
 new_max_attendees, new_banner_image)
                  VALUES
                    (?, ?, ?, 'Pending',
                     ?, ?, ?,
                     ?, ?,
                     ?, ?)
                ");

                $stmt->bind_param(
                    "iiisssssis",
                    $event_id,
                    $club_id,
                    $president_id,
                    $title,
                    $location,
                    $finalDesc,
                    $startDT,
                    $endDT,
                    $max_attendees,
                    $newCoverPath
                );

                $stmt->execute();
                $stmt->close();

                /* ✅ redirect using id */
                header("Location: myeventpage.php?id=".$event_id."&req=sent");
                exit;
            }
        }

        /* keep typed values if error */
        $event['title'] = $title;
        $event['location'] = $location;
        $event['date'] = $date;
        $event['start_time'] = $start_time;
        $event['end_time'] = $end_time;
        $event['category'] = $category;
        $event['sponsor'] = $sponsor;
        $event['description'] = $description;
        $event['notes'] = $notes;
        $event['max_attendees'] = $max_attendees;
    }
}
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
  --navy:#242751; --royal:#4871db; --paper:#eef2f7; --ink:#0e1228; --card:#fff;
  --shadow:0 14px 34px rgba(10,23,60,.12); --radius:22px; --maxw:1100px;
}
*{box-sizing:border-box} html,body{margin:0}
body{font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:linear-gradient(180deg,#f9fbff,#eef2f7)}
.wrapper{max-width:var(--maxw); margin:26px auto 80px; padding:0 20px}
.card{background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); border:1px solid #e1e6f0; padding:24px}
.header-row{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.title{font-size:28px;font-weight:900;letter-spacing:.02em;margin:0}
.actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{appearance:none;border:0;border-radius:12px;padding:12px 16px;font-weight:800;cursor:pointer;text-decoration:none;display:inline-block}
.btn.ghost{background:#eef2ff;color:#1f2a6b}
.btn.primary{background:linear-gradient(135deg,#5d7ff2,#3664e9);color:#fff;box-shadow:0 8px 20px rgba(54,100,233,.22)}
.btn.primary:hover{background:linear-gradient(135deg,#4d70ee,#2958e0)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
@media (max-width:860px){ .form-grid{grid-template-columns:1fr} }
.field{display:flex;flex-direction:column;gap:8px}
.label{font-weight:800;color:var(--navy);font-size:14px}
.input,.textarea{
  width:100%;border:1px solid #d5dbea;border-radius:14px;padding:12px 14px;font-size:15px;background:#fff;outline:none;
}
.textarea{min-height:120px;resize:vertical}
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
.alert{margin-top:14px;padding:12px 14px;border-radius:14px;font-weight:800}
.alert.err{background:#ffecec;border:1px solid #ffdddd;color:#b42318}
.alert.info{background:#f2f5ff;border:1px solid #e7ecff;color:#1f2a6b}
.hint{font-size:12px;color:#6b7280}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="wrapper">
  <div class="card">
    <div class="header-row">
      <h1 class="title">Edit Event</h1>
      <div class="actions">
        <a class="btn ghost" href="myeventpage.php?id=<?php echo (int)$event_id; ?>">Cancel</a>
        <button form="eventForm" class="btn primary" type="submit">Save changes</button>
      </div>
    </div>

    <?php if ($info): ?><div class="alert info"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="banner" id="banner">
      <img id="bannerImg" src="<?php echo htmlspecialchars($event['cover']); ?>" alt="Event cover">
      <div class="overlay"></div>
      <div class="text">
        <div class="name" id="pTitle"><?php echo htmlspecialchars($event['title']); ?></div>
        <div class="when" id="pWhen"></div>
      </div>
    </div>

    <form id="eventForm" method="POST" enctype="multipart/form-data" style="margin-top:16px">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

      <div class="form-grid">
        <div class="field">
          <label class="label" for="title">Title</label>
          <input class="input" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required maxlength="150">
        </div>

        <div class="field">
          <label class="label" for="location">Location</label>
          <input class="input" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required maxlength="200">
        </div>

        <div class="field">
          <label class="label" for="date">Date</label>
          <input class="input" id="date" name="date" type="date" value="<?php echo htmlspecialchars($event['date']); ?>" required>
        </div>

        <div class="field">
          <label class="label" for="start_time">Start time</label>
          <input class="input" id="start_time" name="start_time" type="time" value="<?php echo htmlspecialchars($event['start_time']); ?>" required>
        </div>

        <div class="field">
          <label class="label" for="end_time">End time</label>
          <input class="input" id="end_time" name="end_time" type="time" value="<?php echo htmlspecialchars($event['end_time']); ?>" required>
          <span class="hint">End must be after start.</span>
        </div>

        <div class="field">
          <label class="label" for="category">Category</label>
          <input class="input" id="category" name="category" value="<?php echo htmlspecialchars($event['category']); ?>">
        </div>

        <div class="field">
          <label class="label" for="sponsor">Sponsor (optional)</label>
          <input class="input" id="sponsor" name="sponsor" maxlength="255" placeholder="e.g., TechCorp" value="<?php echo htmlspecialchars($event['sponsor']); ?>">
        </div>

        <div class="field">
          <label class="label" for="max_attendees">Max Attendees (optional)</label>
          <input class="input" id="max_attendees" name="max_attendees" type="number" min="1" value="<?php echo htmlspecialchars((string)($event['max_attendees'] ?? '')); ?>">
          <span class="hint">This will be applied only after admin approval.</span>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="label" for="description">Description</label>
          <textarea class="textarea" id="description" name="description" required maxlength="1500"><?php echo htmlspecialchars($event['description']); ?></textarea>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="label">Cover image</label>
          <div class="uploader">
            <div class="thumb"><img id="coverPreview" src="<?php echo htmlspecialchars($event['cover']); ?>" alt="Cover preview"></div>
            <div>
              <label class="pick" for="cover">Choose file</label>
              <input id="cover" name="cover" type="file" accept="image/*">
              <div class="hint">This cover is saved with the request. Admin approval will apply it.</div>
            </div>
          </div>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label class="label" for="notes">Tickets & Notes</label>
          <textarea class="textarea" id="notes" name="notes" placeholder="One item per line"><?php echo htmlspecialchars($event['notes']); ?></textarea>
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

const coverInput=document.getElementById('cover');
const coverPreview=document.getElementById('coverPreview');
const bannerImg=document.getElementById('bannerImg');
coverInput.addEventListener('change', ()=>{
  const f=coverInput.files?.[0]; if(!f) return;
  const url=URL.createObjectURL(f);
  coverPreview.src=url;
  bannerImg.src=url;
});
</script>
</body>
</html>

<?php
// president/qr_attendance.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'club_president') {
  header('Location: ../login.php');
  exit;
}

require_once __DIR__ . '/../config.php';

// President id (some of your code uses president_id OR student_id)
$presidentId = $_SESSION['president_id'] ?? $_SESSION['student_id'] ?? null;
if (!$presidentId) {
  header('Location: index.php');
  exit;
}

// Get president club_id
$stmt = $conn->prepare("SELECT club_id FROM student WHERE student_id=? LIMIT 1");
$stmt->bind_param("i", $presidentId);
$stmt->execute();
$res = $stmt->get_result();
$pres = $res->fetch_assoc();
$stmt->close();

$clubId = (int)($pres['club_id'] ?? 0);
if ($clubId <= 0) {
  die("Club not found for this president.");
}

// Fetch club events (recent first)
$events = [];
$sql = "
  SELECT event_id, event_name, starting_date, ending_date
  FROM event
  WHERE club_id = ?
  ORDER BY starting_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $clubId);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) $events[] = $row;
$stmt->close();

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Scan Attendance</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8;
  --gold:#e5b758; --sun:#f4df6d; --coral:#ff5e5e;
  --paper:#e9ecef; --ink:#0e1228; --card:#fff;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}
*{box-sizing:border-box}
body{
  margin:0; font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--paper); color:var(--ink);
}
.wrap{ max-width:1100px; margin:0 auto; padding:22px 16px 60px; }
.h1{ font-size:28px; font-weight:900; margin:0 0 6px; }
.p{ margin:0 0 18px; color:#5b6477; line-height:1.6; }

.grid{ display:grid; grid-template-columns: 380px 1fr; gap:16px; align-items:start; }
@media (max-width: 960px){ .grid{ grid-template-columns:1fr; } }

.card{
  background:var(--card);
  border:2px solid #e7e9f2;
  border-radius:16px;
  box-shadow:var(--shadow);
  padding:16px;
}

.label{ font-weight:800; margin-bottom:8px; display:block; }
.select{
  width:100%;
  padding:12px 14px;
  border-radius:12px;
  border:2px solid #e7e9f2;
  font-weight:700;
  outline:none;
}
.select:focus{ border-color:var(--lightBlue); }

.btn{
  width:100%;
  margin-top:12px;
  appearance:none; border:0;
  padding:12px 14px;
  border-radius:12px;
  font-weight:900;
  cursor:pointer;
  background:linear-gradient(135deg, var(--royal), var(--lightBlue));
  color:#fff;
  box-shadow:0 10px 22px rgba(72,113,219,.25);
}
.btn:disabled{ opacity:.55; cursor:not-allowed; box-shadow:none; }

.small{
  margin-top:10px; font-size:13px; color:#6b7280;
}

.scannerBox{
  border-radius:16px;
  overflow:hidden;
  border:2px dashed #d6daf0;
  background:#fbfcff;
  padding:12px;
}

#reader{ width:100%; }

.result{
  margin-top:14px;
  border-radius:14px;
  border:2px solid #e7e9f2;
  padding:12px;
  background:#fff;
}
.badge{
  display:inline-flex; align-items:center; gap:8px;
  padding:6px 10px;
  border-radius:999px;
  font-weight:900;
  font-size:12.5px;
  border:2px solid #e7e9f2;
}
.badge.ok{ border-color:#bbf7d0; background:#ecfdf5; color:#065f46; }
.badge.warn{ border-color:#fed7aa; background:#fff7ed; color:#9a3412; }
.badge.err{ border-color:#fecaca; background:#fff1f2; color:#9f1239; }

.row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; align-items:center; }
.avatar{
  width:46px; height:46px; border-radius:50%;
  border:2px solid var(--sun);
  object-fit:cover; background:#f6f7fb;
}
.kv{ display:grid; gap:4px; }
.kv .name{ font-weight:900; font-size:16px; }
.kv .meta{ color:#6b7280; font-weight:700; font-size:13px; }
hr{ border:0; border-top:1px solid #eef0f6; margin:14px 0; }
.hint{ color:#6b7280; font-size:13px; line-height:1.6; }
</style>
</head>

<body>
<div class="wrap">
  <div class="card" style="margin-bottom:14px;">
    <div class="h1">Scan Attendance</div>
    <div class="p">Select an event, then scan student QR codes to check them in and award points.</div>
  </div>

  <div class="grid">
    <!-- Left: Event selection -->
    <div class="card">
      <label class="label" for="eventSelect">1) Select Event</label>

      <select id="eventSelect" class="select">
        <option value="">— Choose event —</option>
        <?php foreach($events as $e): ?>
          <option value="<?= (int)$e['event_id'] ?>">
            #<?= (int)$e['event_id'] ?> — <?= htmlspecialchars($e['event_name']) ?>
            (<?= htmlspecialchars($e['starting_date'] ?? '') ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <button id="startBtn" class="btn" disabled>Start Scanning</button>
      <button id="stopBtn" class="btn" style="background:#6b7280;margin-top:10px;" disabled>Stop</button>

      <div class="small">
        Tip: Use a phone for best scanning. This page uses the device camera.
      </div>

      <hr>
      <div class="hint">
        ✅ QR content expected: the student's <strong>qr_code</strong> value (e.g., <code>QR_STU_0009</code>).<br>
        ✅ The system will prevent duplicates for the same event.
      </div>
    </div>

    <!-- Right: Scanner + result -->
    <div class="card">
      <div class="scannerBox">
        <div id="scanHint" style="text-align:center;color:#6b7280;font-weight:700;padding:18px 10px;">Camera preview will appear here after you click <strong>Start Scanning</strong>.</div>
        <div id="reader"></div>
      </div>

      <div id="resultBox" class="result" style="display:none;">
        <div id="statusBadge" class="badge">…</div>

        <div class="row" id="studentRow" style="display:none;">
          <img id="studentPhoto" class="avatar" src="" alt="Student photo" />
          <div class="kv">
            <div class="name" id="studentName">Student Name</div>
            <div class="meta" id="studentMeta">Major • ID</div>
          </div>
        </div>

        <div class="p" id="resultMsg" style="margin:10px 0 0;">...</div>
      </div>
    </div>
  </div>
</div>

<!-- html5-qrcode (camera scanning) -->
<script src="https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js"></script>

<script>
const eventSelect = document.getElementById('eventSelect');
const startBtn = document.getElementById('startBtn');
const stopBtn  = document.getElementById('stopBtn');

const resultBox   = document.getElementById('resultBox');
const statusBadge = document.getElementById('statusBadge');
const resultMsg   = document.getElementById('resultMsg');

const studentRow   = document.getElementById('studentRow');
const studentPhoto = document.getElementById('studentPhoto');
const studentName  = document.getElementById('studentName');
const studentMeta  = document.getElementById('studentMeta');

let html5QrCode = null;
let scanning = false;
let lastScanned = { value: null, ts: 0 };

// enable Start when event chosen
eventSelect.addEventListener('change', () => {
  startBtn.disabled = !eventSelect.value;
});

// show result helper
function showResult(type, message, student) {
  resultBox.style.display = 'block';

  statusBadge.className = 'badge ' + (type === 'ok' ? 'ok' : type === 'warn' ? 'warn' : 'err');
  statusBadge.textContent = (type === 'ok') ? 'SUCCESS' : (type === 'warn') ? 'ALREADY CHECKED IN' : 'ERROR';

  resultMsg.textContent = message;

  if (student && student.student_id) {
    studentRow.style.display = 'flex';
    studentName.textContent = student.student_name || 'Student';
    studentMeta.textContent = `${student.major || '—'} • ID: ${student.student_id}`;
    studentPhoto.src = student.profile_photo || 'https://via.placeholder.com/46';
  } else {
    studentRow.style.display = 'none';
  }
}

// POST scan to backend
async function submitScan(qrText) {
  const eventId = eventSelect.value;
  if (!eventId) return showResult('err', 'Please select an event first.');

  const form = new FormData();
  form.append('event_id', eventId);
  form.append('qr_code', qrText);

  const resp = await fetch('scan_submit.php', {
    method: 'POST',
    body: form
  });

  let data = null;
  try { data = await resp.json(); } catch(e) {}

  if (!resp.ok || !data) {
    return showResult('err', 'Server error. Please try again.');
  }

  if (data.status === 'ok') {
    return showResult('ok', data.message || 'Checked in.', data.student || null);
  }
  if (data.status === 'duplicate') {
    return showResult('warn', data.message || 'Already checked in.', data.student || null);
  }
  return showResult('err', data.message || 'Error.', data.student || null);
}

// start scanning
startBtn.addEventListener('click', async () => {
  if (scanning) return;

  const eventId = eventSelect.value;
  if (!eventId) return;

  html5QrCode = new Html5Qrcode("reader");
  scanning = true;
  startBtn.disabled = true;
  stopBtn.disabled = false;

  const config = { fps: 10, qrbox: { width: 260, height: 260 } };

  try {
    const cameras = await Html5Qrcode.getCameras();
    if (!cameras || cameras.length === 0) {
      scanning = false;
      stopBtn.disabled = true;
      startBtn.disabled = false;
      return showResult('err', 'No camera found on this device.');
    }

    // pick the first camera (usually back camera on mobile is not guaranteed, but ok)
    const cameraId = cameras[0].id;

    await html5QrCode.start(
      cameraId,
      config,
      async (decodedText) => {
        // Anti-spam: ignore same QR within 2 seconds
        const now = Date.now();
        if (lastScanned.value === decodedText && (now - lastScanned.ts) < 2000) return;
        lastScanned = { value: decodedText, ts: now };

        // Submit
        await submitScan(decodedText.trim());
      },
      (err) => {
        // ignore scan errors (frame not decoded)
      }
    );

  } catch (e) {
    scanning = false;
    stopBtn.disabled = true;
    startBtn.disabled = false;
    showResult('err', 'Could not start camera scanning. Check browser permissions.');
  }
});

// stop scanning
stopBtn.addEventListener('click', async () => {
  if (!html5QrCode || !scanning) return;
  try {
    await html5QrCode.stop();
    await html5QrCode.clear();
  } catch(e) {}
  scanning = false;
  stopBtn.disabled = true;
  startBtn.disabled = !eventSelect.value ? true : false;
  document.getElementById('scanHint').style.display = 'none';
});
</script>
</body>
</html>

<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$president_id = (int)$_SESSION['student_id'];

/* CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

/* Fetch president info */
$stmt = $conn->prepare("
  SELECT student_id, student_name, email, major, profile_photo, club_id
  FROM student
  WHERE student_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $president_id);
$stmt->execute();
$res = $stmt->get_result();
$me = $res->fetch_assoc();
$stmt->close();

if (!$me) {
    header('Location: index.php');
    exit;
}

/* Joined date from Approved request (optional) */
$joined = '—';
if (!empty($me['club_id']) && (int)$me['club_id'] > 1) {
    $club_id = (int)$me['club_id'];
    $stmt = $conn->prepare("
        SELECT MAX(COALESCE(decided_at, submitted_at)) AS joined_at
        FROM club_membership_request
        WHERE student_id=? AND club_id=? AND status='Approved'
    ");
    $stmt->bind_param("ii", $president_id, $club_id);
    $stmt->execute();
    $jr = $stmt->get_result();
    $j = $jr->fetch_assoc();
    $stmt->close();
    if ($j && $j['joined_at']) $joined = date('Y-m-d', strtotime($j['joined_at']));
}

$defaultPlaceholder =
  'https://images.unsplash.com/photo-1527980965255-d3b416303d12?auto=format&fit=crop&w=600&q=80';

$success = '';
$error = '';

/* Handle POST (save changes) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request. Please refresh and try again.";
    } else {
        $removeFlag = isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1';

        // Upload dir
        $uploadDir = __DIR__ . '/../uploads/profile_photos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        // If remove requested
        if ($removeFlag) {
            // delete old file if it is local (not URL)
            $old = $me['profile_photo'] ?? '';
            if ($old && !preg_match('/^https?:\/\//i', $old)) {
                $oldPath = realpath(__DIR__ . '/../' . ltrim($old, '/'));
                $base = realpath(__DIR__ . '/../');
                if ($oldPath && $base && str_starts_with($oldPath, $base)) {
                    @unlink($oldPath);
                }
            }

            $stmt = $conn->prepare("UPDATE student SET profile_photo=NULL WHERE student_id=?");
            $stmt->bind_param("i", $president_id);
            $stmt->execute();
            $stmt->close();

            $success = "Photo removed successfully.";
        }

        // If new file uploaded
        if (!$error && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload failed. Please try again.";
            } else {
                $tmp = $_FILES['profile_photo']['tmp_name'];
                $size = (int)$_FILES['profile_photo']['size'];

                // Basic size limit: 2MB
                if ($size > 2 * 1024 * 1024) {
                    $error = "Image is too large. Max 2MB.";
                } else {
                    // Validate mime
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);

                    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                    if (!isset($allowed[$mime])) {
                        $error = "Only JPG, PNG, or WEBP allowed.";
                    } else {
                        $ext = $allowed[$mime];
                        $filename = 'president_' . $president_id . '_' . time() . '.' . $ext;
                        $destPath = $uploadDir . $filename;

                        if (!move_uploaded_file($tmp, $destPath)) {
                            $error = "Could not save the image. Check folder permissions.";
                        } else {
                            // delete old local file
                            $old = $me['profile_photo'] ?? '';
                            if ($old && !preg_match('/^https?:\/\//i', $old)) {
                                $oldPath = realpath(__DIR__ . '/../' . ltrim($old, '/'));
                                $base = realpath(__DIR__ . '/../');
                                if ($oldPath && $base && str_starts_with($oldPath, $base)) {
                                    @unlink($oldPath);
                                }
                            }

                            // store relative path
                            $relative = 'uploads/profile_photos/' . $filename;

                            $stmt = $conn->prepare("UPDATE student SET profile_photo=? WHERE student_id=?");
                            $stmt->bind_param("si", $relative, $president_id);
                            $stmt->execute();
                            $stmt->close();

                            $success = "Profile photo updated successfully.";
                        }
                    }
                }
            }
        }

        // Refresh $me after update
        $stmt = $conn->prepare("
          SELECT student_id, student_name, email, major, profile_photo, club_id
          FROM student
          WHERE student_id = ?
          LIMIT 1
        ");
        $stmt->bind_param("i", $president_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $me = $res->fetch_assoc();
        $stmt->close();
    }
}

/* final avatar to display */
$avatar = $me['profile_photo'] ?? '';
if (!$avatar) {
    $avatar = $defaultPlaceholder;
} else {
    // if stored relative path, keep it as is
    $avatar = $avatar;
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
  display:grid;grid-template-columns:170px 1fr;
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

/* alerts */
.alert{
  margin-top:16px;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.45);
  background:rgba(255,255,255,.14);
  color:#fff;
  font-weight:800;
  letter-spacing:.01em;
}
.alert.error{
  border-color:rgba(255,210,210,.85);
  background:rgba(255,220,220,.14);
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
        <img id="avatar" class="avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Member avatar">
        <div class="avatar-actions">
          <button type="button" id="changePhotoBtn" class="chip-btn">Change Photo</button>
          <button type="button" id="removePhotoBtn" class="chip-btn secondary">Remove</button>
        </div>

        <input type="file" id="photoInput" accept="image/*" style="display:none">
      </div>

      <div>
        <h2 id="name" class="name"><?php echo htmlspecialchars($me['student_name']); ?></h2>
        <div id="email" class="sub"><?php echo htmlspecialchars($me['email']); ?></div>
        <div class="badges">
          <span id="role" class="role">President</span>
          <span id="joined" class="joined">Joined — <?php echo htmlspecialchars($joined); ?></span>
        </div>

        <?php if ($success): ?>
          <div class="alert"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ABOUT INFO -->
  <section class="card">
    <h3>Member Info</h3>
    <div class="grid">
      <div class="kv"><b>Full name</b><span id="aboutName"><?php echo htmlspecialchars($me['student_name']); ?></span></div>
      <div class="kv"><b>Email</b><span id="aboutEmail"><?php echo htmlspecialchars($me['email']); ?></span></div>
      <div class="kv"><b>Major</b><span id="major"><?php echo htmlspecialchars($me['major'] ?: '—'); ?></span></div>
      <div class="kv"><b>Student ID</b><span id="studentId"><?php echo (int)$me['student_id']; ?></span></div>
    </div>

    <div class="form-actions">
      <button type="button" class="btn ghost" onclick="window.location.href='index.php'">Cancel</button>

      <!-- Real form submit (no UI change) -->
      <form id="saveForm" method="POST" enctype="multipart/form-data" style="margin:0; display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <input type="hidden" name="remove_photo" id="removeFlag" value="0">
        <input type="file" name="profile_photo" id="profilePhotoReal" accept="image/*" style="display:none">
        <button type="submit" id="saveBtn" class="btn primary">Save Changes</button>
      </form>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>

<script>
const defaultPlaceholder = <?php echo json_encode($defaultPlaceholder); ?>;

const avatarEl = document.getElementById('avatar');
const changeBtn = document.getElementById('changePhotoBtn');
const removeBtn = document.getElementById('removePhotoBtn');

const fakeInput = document.getElementById('photoInput');       // your UI input
const realInput = document.getElementById('profilePhotoReal'); // form input (for PHP)
const removeFlag = document.getElementById('removeFlag');

changeBtn.addEventListener('click', () => fakeInput.click());

fakeInput.addEventListener('change', () => {
  const file = fakeInput.files[0];
  if (!file) return;

  // reset remove flag if user picked a file
  removeFlag.value = '0';

  // sync file into the real form input
  const dt = new DataTransfer();
  dt.items.add(file);
  realInput.files = dt.files;

  // preview
  const reader = new FileReader();
  reader.onload = e => {
    avatarEl.src = e.target.result;
  };
  reader.readAsDataURL(file);
});

removeBtn.addEventListener('click', () => {
  // set remove flag, clear inputs, preview placeholder
  removeFlag.value = '1';
  fakeInput.value = '';
  realInput.value = '';
  avatarEl.src = defaultPlaceholder;
});
</script>

</body>
</html>

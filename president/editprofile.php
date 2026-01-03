<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$studentId = (int) $_SESSION['student_id'];

$successMessage = '';
$errorMessage   = '';

/* =========================
   Helpers (paths)
========================= */

$DEFAULT_AVATAR_URL = '../tools/pics/default-avatar.png';   
$UPLOADS_URL_DIR    = '../uploads/students/';               

$UPLOADS_FS_DIR = __DIR__ . '/../uploads/students/';        

function buildPhotoUrl(?string $dbValue, string $uploadsUrlDir, string $defaultUrl): string {
    $v = trim((string)$dbValue);
    if ($v === '') return $defaultUrl;

    if (strpos($v, '/') !== false || strpos($v, '\\') !== false) {
        $v = str_replace('\\', '/', $v);

        if (strpos($v, 'uploads/') === 0) return '../' . $v;
        if (strpos($v, './uploads/') === 0) return '../' . ltrim($v, './');
        return $v; 
    }

    return $uploadsUrlDir . $v;
}

// return normalized file name 
function normalizePhotoFilename(?string $dbValue): ?string {
    $v = trim((string)$dbValue);
    if ($v === '') return null;
    $v = str_replace('\\', '/', $v);
    return basename($v);
}

/* =========================
   Fetch student
========================= */
$stmt = $conn->prepare("
    SELECT student_id, student_name, email, major, role, profile_photo
    FROM student
    WHERE student_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result  = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    $errorMessage = 'Student not found.';
    $student = [
        'student_id'    => $studentId,
        'student_name'  => 'Student',
        'email'         => '',
        'major'         => '',
        'role'          => 'student',
        'profile_photo' => null,
    ];
}

// (SHOW)
$avatarUrl = buildPhotoUrl($student['profile_photo'] ?? null, $UPLOADS_URL_DIR, $DEFAULT_AVATAR_URL);

/* =========================
   Handle form (UPDATE/REMOVE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {

    // before update fetch current photo
    $stmt = $conn->prepare("SELECT profile_photo FROM student WHERE student_id = ? LIMIT 1");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stmt->bind_result($currentPhotoRaw);
    $stmt->fetch();
    $stmt->close();

    $currentPhoto = normalizePhotoFilename($currentPhotoRaw);

    $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1';
    $hasFile     = isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE;

    // make sure uploads exists 
    if (!is_dir($UPLOADS_FS_DIR)) {
        @mkdir($UPLOADS_FS_DIR, 0775, true);
    }

    // -------- remove photo --------
    if ($removePhoto) {
        if ($currentPhoto) {
            $oldPath = $UPLOADS_FS_DIR . $currentPhoto;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $stmt = $conn->prepare("UPDATE student SET profile_photo = NULL WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $stmt->close();

        $student['profile_photo'] = null;
        $avatarUrl = $DEFAULT_AVATAR_URL; // show path after removal
        $successMessage = 'Profile photo removed successfully.';
    }

    // -------- upload new photo --------
    elseif ($hasFile) {
        $file    = $_FILES['profile_photo'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // check mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
        if ($finfo) finfo_close($finfo);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = 'Error uploading file.';
        } elseif (!isset($allowed[$mime])) {
            $errorMessage = 'Only JPG, PNG, or WEBP images are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $errorMessage = 'File is too large. Max size is 2MB.';
        } else {
            $ext     = $allowed[$mime];
            $newName = 'student_' . $studentId . '_' . time() . '.' . $ext;
            $dest    = $UPLOADS_FS_DIR . $newName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {

                // delete old file
                if ($currentPhoto) {
                    $oldPath = $UPLOADS_FS_DIR . $currentPhoto;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // save new name to db 
                $stmt = $conn->prepare("UPDATE student SET profile_photo = ? WHERE student_id = ?");
                $stmt->bind_param("si", $newName, $studentId);
                $stmt->execute();
                $stmt->close();

                $student['profile_photo'] = $newName;
                $avatarUrl = $UPLOADS_URL_DIR . $newName; // show path after upload
                $successMessage = 'Profile photo updated successfully.';
            } else {
                $errorMessage = 'Could not save uploaded file.';
            }
        }
    }

    else {
        $successMessage = 'Nothing to update.';
    }
}

// role label
$roleLabel   = ($student['role'] === 'club_president') ? 'Club President' : 'Student Member';
$joinedLabel = 'Joined — ' . date('Y');
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
.avatar-wrap{
  position:relative;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:10px;
}
.avatar{
  width:160px;height:160px;border-radius:26px;object-fit:cover;
  border:4px solid rgba(255,255,255,.9);
  background:#dfe5ff;
  box-shadow:0 12px 26px rgba(0,0,0,.18);
}
.avatar-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;}
.chip-btn{
  appearance:none;border-radius:999px;
  border:1px solid rgba(255,255,255,.75);
  background:rgba(255,255,255,.12);
  color:#fff;padding:6px 12px;font-size:12px;font-weight:800;
  letter-spacing:.03em;text-transform:uppercase;cursor:pointer;
  backdrop-filter:blur(6px);
}
.chip-btn.secondary{background:transparent;border-style:dashed;}
.name{font-size:30px;font-weight:900;margin:0}
.sub{opacity:.95;font-weight:700;margin-top:4px}
.badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.role{
  display:inline-flex;align-items:center;gap:8px;
  background:#fff7e6;border:1px solid #ffecb5;
  color:#8a5b00;font-weight:900;border-radius:999px;
  padding:6px 12px;font-size:12px;
}
.joined{
  display:inline-flex;align-items:center;gap:8px;
  background:#f2f5ff;border:1px solid #e6e8f2;
  color:#1f2a6b;font-weight:900;border-radius:999px;
  padding:6px 12px;font-size:12px;
}
.card{
  background:var(--card);border:1px solid #e6e8f2;
  border-radius:18px;box-shadow:var(--shadow);
  padding:20px;margin-top:20px;
}
.card h3{margin:0 0 14px;font-size:18px;color:var(--navy)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:620px){ .grid{grid-template-columns:1fr} }
.kv{
  background:#f6f8ff;border:1px solid #e7ecff;
  border-radius:14px;padding:12px;
}
.kv b{display:block;font-size:12px;color:#596180;margin-bottom:4px;}
.kv span{font-size:15px;color:#1a1f36;font-weight:700;}
.alert{margin-bottom:16px;padding:10px 14px;border-radius:12px;font-size:14px;}
.alert-success{background:#ecfdf3;border:1px solid #bbf7d0;color:#166534;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;flex-wrap:wrap;}
.btn{
  appearance:none;border-radius:12px;padding:10px 16px;font-weight:800;
  border:1px solid #d5dbea;background:#fff;color:#1a1f36;cursor:pointer;
}
.btn.primary{border:0;background:linear-gradient(135deg,#5d7ff2,#3664e9);color:#fff;box-shadow:0 8px 20px rgba(54,100,233,.22);}
.btn.ghost{background:transparent;}
</style>
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<div class="wrap">

  <?php if ($successMessage): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
  <?php endif; ?>

  <?php if ($errorMessage): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
  <?php endif; ?>

  <form action="editprofile.php" method="post" enctype="multipart/form-data">
    <section class="hero">
      <div class="hero-inner">
        <div class="avatar-wrap">
          <img id="avatar" class="avatar"
               src="<?php echo htmlspecialchars($avatarUrl); ?>"
               alt="Member avatar">

          <div class="avatar-actions">
            <button type="button" id="changePhotoBtn" class="chip-btn">Change Photo</button>
            <button type="button" id="removePhotoBtn" class="chip-btn secondary">Remove</button>
          </div>

          <input type="file" name="profile_photo" id="photoInput" accept="image/*" style="display:none">
          <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">
        </div>

        <div>
          <h2 class="name"><?php echo htmlspecialchars($student['student_name']); ?></h2>
          <div class="sub"><?php echo htmlspecialchars($student['email']); ?></div>
          <div class="badges">
            <span class="role"><?php echo htmlspecialchars($roleLabel); ?></span>
            <span class="joined"><?php echo htmlspecialchars($joinedLabel); ?></span>
          </div>
        </div>
      </div>
    </section>

    <section class="card">
      <h3>Member Info</h3>
      <div class="grid">
        <div class="kv"><b>Full name</b><span><?php echo htmlspecialchars($student['student_name']); ?></span></div>
        <div class="kv"><b>Email</b><span><?php echo htmlspecialchars($student['email']); ?></span></div>
        <div class="kv"><b>Major</b><span><?php echo htmlspecialchars($student['major'] ?: '—'); ?></span></div>
        <div class="kv"><b>Student ID</b><span><?php echo htmlspecialchars($student['student_id']); ?></span></div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn ghost" onclick="window.location.href='index.php'">Cancel</button>
        <button type="submit" name="save_profile" class="btn primary">Save Changes</button>
      </div>
    </section>
  </form>

</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
const avatarEl      = document.getElementById('avatar');
const changeBtn     = document.getElementById('changePhotoBtn');
const removeBtn     = document.getElementById('removePhotoBtn');
const fileInput     = document.getElementById('photoInput');
const removeFlag    = document.getElementById('removePhotoFlag');

// default avatar URL for student/
const defaultAvatar = "<?php echo addslashes($DEFAULT_AVATAR_URL); ?>";

//open file dilaog
changeBtn.addEventListener('click', () => fileInput.click());

// check file input change
fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = e => {
    avatarEl.src = e.target.result;
    removeFlag.value = "0";
  };
  reader.readAsDataURL(file);
});

// remove photo
removeBtn.addEventListener('click', () => {
  fileInput.value = "";
  avatarEl.src = defaultAvatar;
  removeFlag.value = "1";
});
</script>

</body>
</html>

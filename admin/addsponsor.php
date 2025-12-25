<?php
require_once '../config.php';//database connection
require_once 'admin_auth.php';//3ashan yetakad eno admin

$currentPage = basename($_SERVER['PHP_SELF']);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {//bas y3mal form submit befoot el condition
    $name     = trim($_POST['sponsor_name']     ?? '');
    $email    = trim($_POST['sponsor_email']    ?? '');
    $phone    = trim($_POST['sponsor_phone']    ?? '');
    $password = trim($_POST['sponsor_password'] ?? '');

    if ($name === '')     { $errors[] = 'Sponsor name is required.'; }
    if ($email === '')    { $errors[] = 'Sponsor email is required.'; }
    if ($password === '') { $errors[] = 'Initial password is required.'; }

    $logoPath = 'assets/sponsor_default.png'; // default if no pic uploaded

    if (isset($_FILES['sponsor_logo']) && $_FILES['sponsor_logo']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['sponsor_logo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Failed to upload logo (upload error).';
        } else {
            $tmpName = $_FILES['sponsor_logo']['tmp_name'];
            $size    = (int)$_FILES['sponsor_logo']['size'];

            // Basic size limit (2MB)
            if ($size > 2 * 1024 * 1024) {
                $errors[] = 'Logo file is too large. Max 2MB.';
            }

            // Validate image type using mime
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_file($finfo, $tmpName) : '';
            if ($finfo) finfo_close($finfo);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowed[$mime])) {
                $errors[] = 'Invalid logo format. Allowed: JPG, PNG, WEBP.';
            }

            // If valid, move to uploads folder
            if (empty($errors)) {
                $ext = $allowed[$mime];

                // Create upload dir if not exists
                $uploadDir = __DIR__ . '/uploads/sponsors/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                if (!is_dir($uploadDir)) {
                    $errors[] = 'Upload folder is missing and could not be created.';
                } else {
                    // Safe unique file name
                    $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($name));
                    $safeBase = trim($safeBase, '-');
                    if ($safeBase === '') $safeBase = 'sponsor';

                    $fileName = $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destAbs  = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $destAbs)) {
                        // Save relative path (from admin/)
                        $logoPath = 'uploads/sponsors/' . $fileName;
                    } else {
                        $errors[] = 'Could not save logo file on server.';
                    }
                }
            }
        }
    }

    // ===== Insert sponsor =====
    if (empty($errors)) {

        $stmt = $conn->prepare("
          INSERT INTO sponsor (company_name, email, phone, logo, password)
          VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            // ملاحظة: إذا نظام تسجيل الدخول عندك يعتمد على password_hash، بدّل السطر التالي:
            // $passwordToStore = password_hash($password, PASSWORD_DEFAULT);
            // وإذا عندك تسجيل الدخول يقارن نص بنص خليه زي ما هو:
            $passwordToStore = $password;

            $stmt->bind_param("sssss", $name, $email, $phone, $logoPath, $passwordToStore);

            if ($stmt->execute()) {
                $success = 'Sponsor created successfully.';
                // clear post values after success
                $_POST = [];
            } else {
                $errors[] = 'Database error while inserting sponsor.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Failed to prepare insert statement.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Add Sponsor</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --coral:#ff5e5e;
  --paper:#eef2f7;
  --card:#ffffff;
  --ink:#0e1228;
  --muted:#6b7280;
  --radius:22px;
  --shadow:0 14px 34px rgba(10,23,60,.12);

  --sidebarWidth:240px;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

/* ===== Layout ===== */
.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  color:var(--ink);
  margin-bottom:8px;
}

.page-subtitle{
  font-size:.96rem;
  color:var(--muted);
  margin-bottom:26px;
}

/* Messages */
.alert{
  padding:10px 14px;
  border-radius:12px;
  margin-bottom:18px;
  font-size:.9rem;
}
.alert-success{
  background:#ecfdf3;
  color:#166534;
  border:1px solid #bbf7d0;
}
.alert-error{
  background:#fef2f2;
  color:#b91c1c;
  border:1px solid #fecaca;
}

/* Form shell */
.form-shell{
  background:var(--card);
  padding:30px 32px 34px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  max-width:100%;
}

/* Two-column grid */
.form-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:28px;
  row-gap:18px;
  margin-bottom:22px;
}

.field-label{
  font-weight:700;
  margin-bottom:6px;
  color:var(--ink);
}

.helper-text{
  margin-top:4px;
  font-size:.83rem;
  color:var(--muted);
}

.input-field{
  width:100%;
  padding:11px 14px;
  border-radius:12px;
  border:1px solid #e5e7eb;
  font-size:.95rem;
  outline:none;
}

.input-field:focus{
  border-color:var(--navy);
}

/* Full-width rows */
.full-width{
  grid-column:1 / 3;
}

/* Logo upload box */
.upload-wrap{
  display:flex;
  align-items:center;
  gap:14px;
  padding:12px 14px;
  border:1px dashed #d1d5db;
  border-radius:14px;
  background:#fafbff;
}

.logo-preview{
  width:56px;
  height:56px;
  border-radius:14px;
  border:1px solid #e5e7eb;
  background:#fff;
  object-fit:cover;
}

/* Submit button */
.actions-row{
  margin-top:24px;
  display:flex;
  gap:12px;
}

.primary-btn{
  padding:12px 26px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.95rem;
  font-weight:700;
  background:var(--navy);
  color:#ffffff;
}

.primary-btn:hover{
  background:#181b3b;
}

.secondary-link{
  font-size:.9rem;
  color:var(--muted);
  text-decoration:none;
  align-self:center;
}

.secondary-link:hover{
  text-decoration:underline;
}

@media(max-width:900px){
  .form-grid{
    grid-template-columns:1fr;
  }
  .full-width{
    grid-column:1 / 2;
  }
  .content{
    margin-left:0;
    padding:24px 18px 40px;
  }
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Add Sponsor</div>
  <div class="page-subtitle">
    Create a new sponsor account that will be able to log in to the UniHive sponsor portal.
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?= implode('<br>', array_map('htmlspecialchars', $errors)); ?>
    </div>
  <?php endif; ?>

  <!-- IMPORTANT: enctype for file upload -->
  <form method="post" action="addsponsor.php" enctype="multipart/form-data">
    <div class="form-shell">

      <div class="form-grid">

        <div>
          <div class="field-label">Sponsor name</div>
          <input
            type="text"
            name="sponsor_name"
            class="input-field"
            required
            placeholder="e.g., Coffee Corner"
            value="<?= isset($_POST['sponsor_name']) ? htmlspecialchars($_POST['sponsor_name']) : '';?>"
          >
        </div>

        <div>
          <div class="field-label">Sponsor email</div>
          <input
            type="email"
            name="sponsor_email"
            class="input-field"
            required
            placeholder="name@company.com"
            value="<?= isset($_POST['sponsor_email']) ? htmlspecialchars($_POST['sponsor_email']) : '';?>"
          >
        </div>

        <div>
          <div class="field-label">Phone (optional)</div>
          <input
            type="text"
            name="sponsor_phone"
            class="input-field"
            placeholder="e.g., 0790000000"
            value="<?= isset($_POST['sponsor_phone']) ? htmlspecialchars($_POST['sponsor_phone']) : '';?>"
          >
        </div>

        <div>
          <div class="field-label">Initial password</div>
          <input
            type="text"
            name="sponsor_password"
            class="input-field"
            required
            placeholder="e.g., coffee123"
            value="<?= isset($_POST['sponsor_password']) ? htmlspecialchars($_POST['sponsor_password']) : '';?>"
          >
          <div class="helper-text">
            Share this password with the sponsor so they can log in.
          </div>
        </div>

        <!-- Logo upload (full width) -->
        <div class="full-width">
          <div class="field-label">Sponsor logo (optional)</div>

          <div class="upload-wrap">
            <img
              id="logoPreview"
              class="logo-preview"
              src="assets/sponsor_default.png"
              alt="Logo preview"
            >
            <div style="flex:1">
              <input
                type="file"
                name="sponsor_logo"
                id="sponsor_logo"
                class="input-field"
                accept="image/png,image/jpeg,image/webp"
              >
              <div class="helper-text">Allowed: JPG, PNG, WEBP — Max 2MB.</div>
            </div>
          </div>
        </div>

      </div>

      <div class="actions-row">
        <button type="submit" class="primary-btn">
          Save sponsor
        </button>
        <a href="sponsors.php" class="secondary-link">Cancel and go back</a>
      </div>

    </div>
  </form>

</div>

<script>
// Simple preview
const input = document.getElementById('sponsor_logo');
const preview = document.getElementById('logoPreview');

if (input) {
  input.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    const ok = ['image/jpeg','image/png','image/webp'].includes(file.type);
    if (!ok) {
      alert('Invalid logo format. Allowed: JPG, PNG, WEBP.');
      this.value = '';
      preview.src = 'assets/sponsor_default.png';
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => preview.src = e.target.result;
    reader.readAsDataURL(file);
  });
}
</script>

</body>
</html>

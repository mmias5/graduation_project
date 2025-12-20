<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['sponsor_id']) || ($_SESSION['role'] ?? '') !== 'sponsor') {
  header('Location: ../login.php');
  exit;
}

require_once __DIR__ . '/../config.php';

define('APP_BASE', '/graduation_project'); // عدليها إذا مشروعك اسمه غير

function clean_upload_rel(?string $rel): string {
  $p = trim((string)$rel);
  if ($p === '') return '';
  $p = str_replace('\\', '/', $p);
  $p = preg_replace('~^\./+~', '', $p);
  if (strpos($p, '..') !== false) return '';
  if (stripos($p, 'uploads/') !== 0) return '';
  return $p;
}
function upload_url(?string $rel): string {
  $p = clean_upload_rel($rel);
  return $p ? (APP_BASE . '/' . $p) : '';
}

$sponsorId = (int)$_SESSION['sponsor_id'];
$success = '';
$error   = '';

/* Fetch current sponsor */
$stmt = $conn->prepare("SELECT sponsor_id, company_name, email, phone, logo, password FROM sponsor WHERE sponsor_id=? LIMIT 1");
$stmt->bind_param("i", $sponsorId);
$stmt->execute();
$sponsor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sponsor) die("Sponsor not found.");

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Update basic info
  if (isset($_POST['update_info'])) {
    $company = trim($_POST['company_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');

    if ($company === '') {
      $error = "Company name is required.";
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Please enter a valid email.";
    } else {
      // prevent email duplicates (optional but good)
      $chk = $conn->prepare("SELECT sponsor_id FROM sponsor WHERE email=? AND sponsor_id<>? LIMIT 1");
      $chk->bind_param("si", $email, $sponsorId);
      $chk->execute();
      $dup = $chk->get_result()->fetch_assoc();
      $chk->close();

      if ($dup) {
        $error = "This email is already used by another sponsor.";
      } else {
        $up = $conn->prepare("UPDATE sponsor SET company_name=?, email=?, phone=? WHERE sponsor_id=? LIMIT 1");
        $up->bind_param("sssi", $company, $email, $phone, $sponsorId);
        $up->execute();
        $up->close();

        $success = "Account info updated successfully.";
        $sponsor['company_name'] = $company;
        $sponsor['email'] = $email;
        $sponsor['phone'] = $phone;
      }
    }
  }

  // Update password (PLAIN - because your DB is plain)
  if (isset($_POST['update_password'])) {
    $current = (string)($_POST['current_password'] ?? '');
    $new1    = (string)($_POST['new_password'] ?? '');
    $new2    = (string)($_POST['confirm_password'] ?? '');

    if ($new1 === '' || strlen($new1) < 3) {
      $error = "New password must be at least 3 characters.";
    } elseif ($new1 !== $new2) {
      $error = "Password confirmation does not match.";
    } else {
      $dbPass = (string)($sponsor['password'] ?? '');

      if ($current !== $dbPass) {
        $error = "Current password is incorrect.";
      } else {
        $up = $conn->prepare("UPDATE sponsor SET password=? WHERE sponsor_id=? LIMIT 1");
        $up->bind_param("si", $new1, $sponsorId);
        $up->execute();
        $up->close();

        $success = "Password updated successfully.";
        $sponsor['password'] = $new1;
      }
    }
  }

  // Update logo
  if (isset($_POST['update_logo'])) {
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
      $error = "Please choose a logo image.";
    } else {
      $allowed = ['image/png','image/jpeg','image/webp'];
      $type = mime_content_type($_FILES['logo']['tmp_name']);

      if (!in_array($type, $allowed, true)) {
        $error = "Logo must be PNG/JPG/WebP.";
      } else {
        $dir = __DIR__ . '/../uploads/sponsors';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $ext = ($type === 'image/png') ? 'png' : (($type === 'image/webp') ? 'webp' : 'jpg');
        $filename = 'sponsor_' . $sponsorId . '_' . time() . '.' . $ext;
        $destAbs = $dir . '/' . $filename;

        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destAbs)) {
          $error = "Failed to upload logo.";
        } else {
          $rel = 'uploads/sponsors/' . $filename;

          $up = $conn->prepare("UPDATE sponsor SET logo=? WHERE sponsor_id=? LIMIT 1");
          $up->bind_param("si", $rel, $sponsorId);
          $up->execute();
          $up->close();

          $success = "Logo updated successfully.";
          $sponsor['logo'] = $rel;
        }
      }
    }
  }
}

$logoUrl = upload_url($sponsor['logo'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Account Management</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --gold:#e5b758;
  --paper:#eef2f7;
  --ink:#0e1228;
  --card:#ffffff;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}
* { box-sizing: border-box; }
body{margin:0;font-family:"Raleway",sans-serif;background:var(--paper);color:var(--ink);}
.wrap{max-width:1100px;margin:22px auto;padding:0 16px;}
.card{background:var(--card);border-radius:16px;padding:18px;margin-bottom:14px;box-shadow:var(--shadow);}
h2{margin:0 0 14px;color:var(--navy);}
h3{margin:0 0 10px;color:var(--navy);}
label{display:block;font-size:13px;margin-bottom:6px;color:var(--navy);font-weight:700;}
input{width:100%;padding:10px 12px;border:1px solid #d7dbe6;border-radius:12px;}
.row{display:flex;gap:18px;flex-wrap:wrap; align-items:flex-start;}
.col{flex:1;min-width:240px;}
button{background:var(--gold);color:#fff;border:0;padding:10px 14px;border-radius:12px;font-weight:800;cursor:pointer;}
button:hover{opacity:.95;}
.msg-ok{background:#e9f7ef;color:#1f7a3a;padding:10px 12px;border-radius:12px;margin-bottom:12px;}
.msg-er{background:#fdecec;color:#a12b2b;padding:10px 12px;border-radius:12px;margin-bottom:12px;}
.avatar{width:70px;height:70px;border-radius:16px;object-fit:cover;background:#fff;border:2px solid #e7e9f2;}
.small{font-size:13px;color:#555;margin-top:6px;}
</style>
</head>

<body>

<?php
include 'header.php';
?>

<div class="wrap">
  <h2>Account Management</h2>

  <?php if ($success): ?><div class="msg-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg-er"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <h3>Basic Info</h3>
    <form method="post">
      <div class="row">
        <div class="col">
          <label>Company Name</label>
          <input type="text" name="company_name" value="<?= htmlspecialchars($sponsor['company_name'] ?? '') ?>" required>
        </div>
        <div class="col">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($sponsor['email'] ?? '') ?>" required>
        </div>
        <div class="col">
          <label>Phone</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($sponsor['phone'] ?? '') ?>">
        </div>
      </div>
      <div style="margin-top:12px;">
        <button name="update_info" value="1">Save Changes</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3>Change Password</h3>
    <form method="post">
      <div class="row">
        <div class="col">
          <label>Current Password</label>
          <input type="password" name="current_password" required>
        </div>
        <div class="col">
          <label>New Password</label>
          <input type="password" name="new_password" required>
        </div>
        <div class="col">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" required>
        </div>
      </div>
      <div style="margin-top:12px;">
        <button name="update_password" value="1">Update Password</button>
      </div>
      <div class="small">Note: Your current system stores passwords as plain text (like 123).</div>
    </form>
  </div>

  <div class="card">
    <h3>Logo</h3>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
      <img class="avatar" src="<?= htmlspecialchars($logoUrl ?: (APP_BASE.'/tools/pics/sponsorlogo.png')) ?>" alt="logo">
      <div class="small">Current logo</div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <label>Upload New Logo</label>
      <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required>
      <div style="margin-top:12px;">
        <button name="update_logo" value="1">Update Logo</button>
      </div>
    </form>
  </div>

</div>
<?php
include 'footer.php';
?>
</body>
</html>

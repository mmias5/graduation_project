<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Dummy data – later you will fetch by $_GET['id']
$request = [
    "club_name"      => "Debate Club",
    "applicant_name" => "Sarah Ahmad",
    "category"       => "Academic",
    "description"    => "A club focused on improving public speaking, debating, and logical thinking...",
    "email"          => "debate@university.edu",
    "logo"           => "assets/club1.png",

    // Social links (optional)
    "linkedin"  => "https://www.linkedin.com/company/debate-club",
    "facebook"  => "https://www.facebook.com/debateclub",
    "instagram" => "https://www.instagram.com/debateclub"
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Review Club Request</title>
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

  --sidebarWidth:260px;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
  margin:0;
  background:var(--paper);
  font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}

.content{
  margin-left:var(--sidebarWidth);
  padding:40px 50px 60px;
}

.page-title{
  font-size:2rem;
  font-weight:800;
  margin-bottom:25px;
  color:var(--ink);
}

.form-box{
  background:var(--card);
  padding:32px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
}

.logo-img{
  width:120px;
  height:120px;
  border-radius:18px;
  object-fit:cover;
  background:#f3f4f6;
  margin-bottom:24px;
}

.field-label{
  font-weight:700;
  margin-top:10px;
  margin-bottom:6px;
  color:var(--ink);
}

.field-value{
  background:#f8f9fc;
  padding:14px 18px;
  border-radius:14px;
  margin-bottom:8px;
  color:var(--ink);
}

/* SOCIAL LINKS STYLING */
.social-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
  margin-top:15px;
}

.social-full{
  grid-column:1 / 3;
}

.social-pill{
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px 16px;
  background:#f8f9fc;
  border-radius:14px;
  color:var(--ink);
  border:1px solid #e5e7eb;
}

.social-pill img{
  width:26px;
  height:26px;
}

/* Buttons */
.btn-row{
  margin-top:26px;
  display:flex;
  gap:16px;
}

.action-btn{
  padding:14px 34px;
  border-radius:999px;
  font-weight:700;
  color:#fff;
  text-decoration:none;
  border:none;
  cursor:pointer;
}

.action-btn.approve{
  background:var(--navy);
}

.action-btn.reject{
  background:var(--coral);
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-title">Review Club Request</div>

  <div class="form-box">
    <img src="<?= $request['logo'] ?>" alt="Club logo" class="logo-img">

    <div class="field-label">Club Name</div>
    <div class="field-value"><?= $request['club_name'] ?></div>

    <div class="field-label">Applicant Name</div>
    <div class="field-value"><?= $request['applicant_name'] ?></div>

    <div class="field-label">Category</div>
    <div class="field-value"><?= $request['category'] ?></div>

    <div class="field-label">Description</div>
    <div class="field-value"><?= $request['description'] ?></div>

    <div class="field-label">Contact Email</div>
    <div class="field-value"><?= $request['email'] ?></div>

    <!-- ▬▬▬ SOCIAL LINKS ADDED HERE ▬▬▬ -->
    <div class="field-label" style="margin-top:20px;">Social Links</div>

    <div class="social-grid">

      <!-- LinkedIn -->
      <div class="social-pill">
        <img src="https://cdn-icons-png.flaticon.com/512/174/174857.png">
        <a href="<?= $request['linkedin'] ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
          <?= $request['linkedin'] ?>
        </a>
      </div>

      <!-- Facebook -->
      <div class="social-pill">
        <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png">
        <a href="<?= $request['facebook'] ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
          <?= $request['facebook'] ?>
        </a>
      </div>

      <!-- Instagram (full width) -->
      <div class="social-pill social-full">
        <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png">
        <a href="<?= $request['instagram'] ?>" target="_blank" style="color:var(--ink); text-decoration:none;">
          <?= $request['instagram'] ?>
        </a>
      </div>

    </div>
    <!-- ▬▬▬ END SOCIAL LINKS ▬▬▬ -->

  </div>

  <div class="btn-row">
    <button class="action-btn approve">Approve</button>
    <button class="action-btn reject">Reject</button>
  </div>
</div>

</body>
</html>

<?php
// Dummy data – later you will fetch by $_GET['id']
$request = [
    "club_name"   => "Debate Club",
    "applicant_name"   => "Sarah Ahmad",
    "category"    => "Academic",
    "description" => "A club focused on improving public speaking, debating, and logical thinking...",
    "email"       => "debate@university.edu",
    "logo"        => "assets/club1.png"
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

/* again: NO .sidebar styles here */

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
  </div>

  <div class="btn-row">
    <button class="action-btn approve">Approve</button>
    <button class="action-btn reject">Reject</button>
  </div>
</div>

</body>
</html>

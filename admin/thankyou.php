<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Thank You — UniHive</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root{
  --navy:#242751;
  --royal:#4871db;
  --gold:#e5b758;
  --card:#ffffff;
  --ink:#0e1228;
}

body{
  margin:0;
  font-family:"Raleway",sans-serif;
  background:linear-gradient(135deg,#141938,#242751);
  display:flex;
  justify-content:center;
  align-items:center;
  min-height:100vh;
  color:#fff;
  text-align:center;
}

.container{
  background:rgba(255,255,255,.08);
  padding:40px 32px;
  border-radius:24px;
  backdrop-filter:blur(16px);
  max-width:450px;
  width:90%;
  border:1px solid rgba(255,255,255,.2);
}

h1{
  font-size:1.6rem;
  margin-bottom:10px;
  color:var(--gold);
}

p{
  font-size:.95rem;
  margin-bottom:24px;
  line-height:1.5;
}

a{
  display:inline-block;
  padding:11px 22px;
  border-radius:999px;
  text-decoration:none;
  font-weight:700;
  background:linear-gradient(135deg,var(--gold),var(--royal));
  color:#fff;
}

a:hover{
  filter:brightness(1.05);
}
</style>
</head>
<body>

<div class="container">
  <h1>Request Submitted 🎉</h1>
  <p>
    Thank you for your interest in joining UniHive as a sponsor.<br>
    Our partnerships team will review your request and contact you via email or phone soon.
  </p>
  <a href="login.php">Return to Login</a>
</div>

</body>
</html>

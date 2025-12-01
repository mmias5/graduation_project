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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Sponsor Registration</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --navy:#242751;
      --royal:#4871db;
      --gold:#e5b758;
      --sun:#f4df6d;
      --coral:#ff5e5e;
      --paper:#e9ecef;
      --card:#ffffff;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow:0 20px 46px rgba(7,15,35,.35);
      --radius:26px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      min-height:100vh;
      margin:0;
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      background:
        radial-gradient(900px 700px at -5% 0%, rgba(229,183,88,.32), transparent 60%),
        radial-gradient(900px 800px at 105% 100%,  rgba(72,113,219,.28), transparent 60%),
        linear-gradient(135deg,#141938,#242751);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px;
      color:var(--ink);
    }

    .page-shell{
      width:100%;
      max-width:760px;
      background:rgba(255,255,255,.06);
      border-radius:34px;
      border:1px solid rgba(255,255,255,.32);
      backdrop-filter:blur(20px);
      box-shadow:var(--shadow);
      padding:26px;
    }

    .page-inner{
      background:var(--card);
      border-radius:var(--radius);
      padding:26px 24px 24px;
      box-shadow:0 18px 40px rgba(15,23,42,.2);
      border:1px solid rgba(229,183,88,.2);
    }

    .header-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      margin-bottom:20px;
    }

    .brand-side{
      display:flex;
      align-items:center;
      gap:14px;
    }

    .mini-logo{
      width:54px;
      height:54px;
      border-radius:18px;
      background:
        radial-gradient(circle at 0% 0%, rgba(229,183,88,.4), transparent 60%),
        rgba(15,23,42,.96);
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
      border:1px solid rgba(15,23,42,.5);
      box-shadow:0 10px 26px rgba(15,23,42,.6);
    }
    .mini-logo img{
      max-width:80%;
      max-height:80%;
      object-fit:contain;
    }

    .title-block h1{
      font-size:1.5rem;
      color:var(--navy);
      margin-bottom:4px;
    }
    .title-block p{
      font-size:.9rem;
      color:var(--muted);
      max-width:420px;
    }

    .back-link{
      font-size:.82rem;
      text-decoration:none;
      color:var(--royal);
      font-weight:600;
      padding:8px 12px;
      border-radius:999px;
      border:1px solid rgba(72,113,219,.22);
      background:#f3f4ff;
      display:inline-flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }
    .back-link:hover{background:#e5ebff;}

    .form-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px 16px;
      margin-bottom:12px;
    }

    .form-group{margin-bottom:14px;display:flex;flex-direction:column;}
    label{font-size:.9rem;font-weight:600;color:var(--ink);margin-bottom:6px;}

    input[type="text"],
    input[type="tel"],
    input[type="email"],
    textarea{
      width:100%;
      padding:11px 13px;
      font-size:.95rem;
      border-radius:12px;
      border:1px solid rgba(15,23,42,.12);
      background:#f9fafb;
      color:var(--ink);
      outline:none;
      transition:.16s ease;
      resize:vertical;
    }

    textarea{min-height:120px;}

    input::placeholder, textarea::placeholder{color:rgba(148,163,184,.9);}

    input:focus, textarea:focus{
      background:#ffffff;
      border-color:var(--royal);
      box-shadow:0 0 0 1px rgba(72,113,219,.35), 0 0 0 4px rgba(229,183,88,.18);
      transform:translateY(-1px);
    }

    .button-row{
      margin-top:12px;
      display:flex;
      justify-content:flex-end;
    }

    .btn{
      border:none;
      border-radius:999px;
      padding:10px 20px;
      font-size:.9rem;
      font-weight:700;
      letter-spacing:.08em;
      text-transform:uppercase;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }

    .btn-primary{
      background:linear-gradient(135deg,var(--gold),var(--royal));
      color:#fff;
      box-shadow:0 14px 30px rgba(36,39,81,.4);
    }
    .btn-primary:hover{
      filter:brightness(1.03);
      transform:translateY(-1px);
    }

    .footer-note{margin-top:10px;font-size:.8rem;color:var(--muted);}
  </style>
</head>
<body>

  <main class="page-shell">
    <div class="page-inner">
      <div class="header-row">
        <div class="brand-side">
          <div class="mini-logo">
            <img src="tools/pics/mainlogo.png" alt="UniHive Logo">
          </div>
          <div class="title-block">
            <h1>Sponsor Registration</h1>
            <p>Submit your details so our university partnerships team can review your request and contact you to complete your sponsor account setup on UniHive.</p>
          </div>
        </div>

        <a href="login.php" class="back-link">
          <span>←</span> Back to login
        </a>
      </div>

      <!-- Redirect to thankyou.php -->
      <form method="post" action="thankyou.php">
        <div class="form-grid">
          <div class="form-group">
            <label>Company Name</label>
            <input type="text" name="name" placeholder="Your company name" required>
          </div>

          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="+962 7X XXX XXXX" required>
          </div>

          <div class="form-group">
            <label>Work Email</label>
            <input type="email" name="email" placeholder="you@company.com" required>
          </div>

                    <div class="form-group">
            <label>Website (Optional)</label>
            <input type="text" name="name" placeholder="Your company's website" optional>
          </div>


        </div>

        <div class="form-group">
          <label>Introduce yourself & your brand</label>
          <textarea
            name="description"
            placeholder="Briefly describe your brand, the type of clubs or events you’re interested in, and how you’d like to support students."
            required
          ></textarea>
        </div>

        <div class="button-row">
          <button type="submit" class="btn btn-primary">Submit for Review</button>
        </div>

        <p class="footer-note">
          By submitting, you acknowledge that this is a request form. Our team will review your details and contact you via email or phone with next steps and login information once approved.
        </p>
      </form>
    </div>
  </main>

</body>
</html>

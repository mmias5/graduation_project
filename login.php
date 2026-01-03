<?php
session_start();
require_once __DIR__ . '/config.php';

if (isset($_SESSION['role'])) {

    if ($_SESSION['role'] === 'admin' && isset($_SESSION['admin_id'])) {
        header('Location: admin/index.php');
        exit;
    }

    if ($_SESSION['role'] === 'sponsor' && isset($_SESSION['sponsor_id'])) {
        header('Location: sponsor/index.php');
        exit;
    }

    if (($_SESSION['role'] === 'student' || $_SESSION['role'] === 'club_president') && isset($_SESSION['student_id'])) {
        if ($_SESSION['role'] === 'club_president') {
            header('Location: president/index.php');
        } else {
            header('Location: user/index.php');
        }
        exit;
    }
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $loginError = 'Please enter both email and password.';
    } else {

        // 1) try admin first 
        $stmt = $conn->prepare("
            SELECT admin_id, admin_name, email, password
            FROM uni_administrator
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultAdmin = $stmt->get_result();
        $admin = $resultAdmin->fetch_assoc();
        $stmt->close();

        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id']   = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['admin_name'];
            $_SESSION['role']       = 'admin';

            header('Location: admin/index.php');
            exit;
        }

        // 2) if not admin try sponsor
        $stmt = $conn->prepare("
            SELECT sponsor_id, company_name, email, password
            FROM sponsor
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultSponsor = $stmt->get_result();
        $sponsor = $resultSponsor->fetch_assoc();
        $stmt->close();

        if ($sponsor && $password === $sponsor['password']) {
            $_SESSION['sponsor_id']   = $sponsor['sponsor_id'];
            $_SESSION['sponsor_name'] = $sponsor['company_name'];
            $_SESSION['role']         = 'sponsor';

            header('Location: sponsor/index.php');
            exit;
        }

        // 3) if not admin and sponsor try student/president
        $stmt = $conn->prepare("
            SELECT student_id, student_name, email, password, role, club_id
            FROM student
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultStudent = $stmt->get_result();
        $student = $resultStudent->fetch_assoc();
        $stmt->close();

        if ($student && $password === $student['password']) {
            $_SESSION['student_id']   = $student['student_id'];
            $_SESSION['student_name'] = $student['student_name'];
            $_SESSION['role']         = $student['role'] ?: 'student';
            $_SESSION['club_id']      = $student['club_id'];

            if ($_SESSION['role'] === 'club_president') {
                header('Location: president/index.php');
            } else {
                header('Location: user/index.php');
            }
            exit;
        }

        // 4) if nothing works showw error
        $loginError = 'Invalid email or password.';
    }
}
?>
<!doctype html> 
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Login</title>

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
      --shadow:0 20px 46px rgba(7,15,35,.45);
      --radius:26px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      min-height:100vh;
      margin:0;
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      background:
        radial-gradient(900px 700px at -5% 0%, rgba(229,183,88,.32), transparent 60%),
        radial-gradient(900px 800px at 105% 100%,  rgba(229,183,88,.32), transparent 60%),
        linear-gradient(135deg,#141938,#242751);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px;
      color:var(--ink);
    }

    .auth-shell{
      width:100%;
      max-width:1120px;
      display:grid;
      grid-template-columns:minmax(0,1.15fr) minmax(0,0.85fr);
      border-radius:34px;
      background:rgba(255,255,255,.06);
      box-shadow:var(--shadow);
      overflow:hidden;
      backdrop-filter:blur(20px);
      border:1px solid rgba(255,255,255,.28);
    }

    /* ===== Left side ===== */
    .auth-intro{
      padding:32px 38px 32px 32px;
      background:
        radial-gradient(circle at 0% 0%, rgba(229,183,88,.16), transparent 55%),
        radial-gradient(circle at 115% 115%, rgba(72,113,219,.3), transparent 55%),
        linear-gradient(145deg, rgba(11,17,40,.98), rgba(22,32,70,.98));
      color:#f9fafb;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      gap:28px;
    }

    .auth-logo-hero{
      display:flex;
      align-items:center;
      gap:20px;
      margin-bottom:10px;
    }

    .auth-logo{
      width:100px;
      height:100px;
      border-radius:26px;
      background:
        radial-gradient(circle at 0% 0%, rgba(229,183,88,.38), transparent 60%),
        radial-gradient(circle at 100% 100%, transparent 60%),
        rgba(15,23,42,.96);
      display:flex;
      align-items:center;
      justify-content:center;
      border:1px solid rgba(248,250,252,.24);
      overflow:hidden;
      box-shadow:0 18px 40px rgba(8,18,44,.9);
    }
    .auth-logo img{
      max-width:88%;
      max-height:88%;
      object-fit:contain;
    }

    .auth-brand-text{
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    .auth-brand-text span{
      font-size:.9rem;
      letter-spacing:.18em;
      text-transform:uppercase;
      color:rgba(226,232,240,.9);
      font-weight:600;
    }

    .auth-headline{
      margin-top:10px;
    }
    .auth-headline h1{
      font-size:2.1rem;
      line-height:1.22;
      margin-bottom:10px;
      position:relative;
    }
    .auth-headline h1::after{
      content:"";
      display:block;
      width:82px;
      height:3px;
      border-radius:999px;
      margin-top:10px;
      background:linear-gradient(90deg,var(--gold),var(--royal));
      opacity:.95;
    }

    .auth-subtitle{
      font-size:.98rem;
      color:rgba(226,232,240,.86);
      margin-bottom:14px;
    }
    .auth-headline p{
      font-size:1.16rem;
      line-height:1.65;
      max-width:440px;
      color:rgba(226,232,240,.94);
    }

    .auth-pills{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:18px;
    }
    .auth-pill{
      padding:7px 13px;
      border-radius:999px;
      font-size:.78rem;
      letter-spacing:.08em;
      text-transform:uppercase;
      border:1px solid rgba(248,250,252,.26);
      background:rgba(15,23,42,.7);
      color:rgba(248,250,252,.92);
    }

    .auth-footer-note{
      font-size:.84rem;
      color:rgba(226,232,240,.86);
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      align-items:center;
    }
    .auth-footer-note span.highlight{
      color:var(--gold);
      font-weight:600;
    }

    /* ===== Right side (form) ===== */
    .auth-card{
      background:var(--paper);
      padding:32px 32px 36px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .auth-card-inner{
      width:100%;
      max-width:380px;
      background:var(--card);
      border-radius:var(--radius);
      padding:30px 26px 28px;
      box-shadow:0 20px 42px rgba(15,23,42,.22);
      border:1px solid rgba(229,183,88,.18);
    }

    /* mobile logo */
    .mobile-logo-wrap{
      display:none;
      justify-content:center;
      margin-bottom:16px;
    }
    .mobile-logo-wrap img{
      width:88px;
      height:auto;
      display:block;
    }

    .auth-card-header{
      margin-bottom:18px;
    }
    .role-text{
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.15em;
      color:var(--gold);
      font-weight:700;
      margin-bottom:6px;
    }
    .auth-card-header h2{
      font-size:1.45rem;
      margin-bottom:4px;
      color:var(--navy);
    }
    .auth-card-header p{
      font-size:.92rem;
      color:var(--muted);
    }

    .form-group{
      margin-bottom:16px;
    }
    label{
      display:block;
      margin-bottom:6px;
      font-size:.9rem;
      font-weight:600;
      color:var(--ink);
    }
    .input-wrapper{
      position:relative;
    }
    .input-wrapper input{
      width:100%;
      padding:12px 14px;
      font-size:.96rem;
      border-radius:12px;
      border:1px solid rgba(15,23,42,.12);
      outline:none;
      background:#f9fafb;
      color:var(--ink);
      transition:border .16s ease, box-shadow .16s ease, background .16s ease, transform .12s ease;
    }
    .input-wrapper input::placeholder{
      color:rgba(148,163,184,.9);
      font-size:.9rem;
    }
    .input-wrapper input:focus{
      background:#ffffff;
      border-color:var(--royal);
      box-shadow:0 0 0 1px rgba(72,113,219,.35), 0 0 0 4px rgba(229,183,88,.25);
      transform:translateY(-1px);
    }

    .role-hint{
      margin-top:6px;
      font-size:.8rem;
      color:var(--muted);
      min-height:1.2em;
    }
    .role-hint span{
      font-weight:600;
      color:var(--navy);
    }

    .forgot-row{
      display:flex;
      justify-content:flex-end;
      margin:2px 0 16px;
    }
    .forgot-link{
      font-size:.86rem;
      color:var(--royal);
      text-decoration:none;
      font-weight:500;
    }
    .forgot-link:hover{
      text-decoration:underline;
      color:var(--coral);
    }

    .submit-btn{
      width:100%;
      border:none;
      border-radius:999px;
      padding:12px 16px;
      font-size:.98rem;
      font-weight:700;
      letter-spacing:.08em;
      text-transform:uppercase;
      background:linear-gradient(135deg,var(--royal),var(--navy));
      color:#f9fafb;
      cursor:pointer;
      box-shadow:0 16px 34px rgba(36,39,81,.6);
      transition:transform .12s ease, box-shadow .12s ease, filter .12s ease;
      position:relative;
      overflow:hidden;
    }
    .submit-btn::before{
      content:"";
      position:absolute;
      inset:0;
      background:linear-gradient(135deg,rgba(229,183,88,.45),transparent);
      opacity:0;
      pointer-events:none;
      transition:opacity .12s ease;
    }
    .submit-btn:hover{
      filter:brightness(1.04);
      box-shadow:0 18px 40px rgba(36,39,81,.7);
      transform:translateY(-1px);
    }
    .submit-btn:hover::before{
      opacity:.55;
    }
    .submit-btn:active{
      transform:translateY(0);
      box-shadow:0 10px 26px rgba(36,39,81,.5);
    }

    .secondary-btn{
      width:100%;
      margin-top:10px;
      border-radius:999px;
      padding:11px 16px;
      font-size:.9rem;
      font-weight:700;
      letter-spacing:.08em;
      text-transform:uppercase;
      text-align:center;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border:1px solid var(--gold);
      background:#fff7e0;
      color:var(--navy);
      text-decoration:none;
      box-shadow:0 8px 18px rgba(36,39,81,.16);
      transition:background .12s ease, transform .12s ease, box-shadow .12s ease;
    }
    .secondary-btn:hover{
      background:var(--gold);
      box-shadow:0 12px 24px rgba(36,39,81,.28);
      transform:translateY(-1px);
    }
    .secondary-btn:active{
      transform:translateY(0);
      box-shadow:0 8px 18px rgba(36,39,81,.18);
    }

    .below-text{
      margin-top:16px;
      font-size:.86rem;
      color:var(--muted);
      text-align:center;
      line-height:1.5;
    }
    .below-text span{
      color:var(--royal);
      font-weight:600;
    }

    .error-banner{
      background:#fee2e2;
      border-radius:999px;
      padding:8px 12px;
      margin-bottom:12px;
      font-size:.82rem;
      color:#b91c1c;
      border:1px solid #fecaca;
    }

    @media (max-width:920px){
      .auth-shell{
        grid-template-columns:1fr;
      }
      .auth-intro{
        padding:24px 24px 22px;
      }
      .auth-card{
        padding:22px;
      }
      .auth-card-inner{
        max-width:420px;
      }
    }

    @media (max-width:640px){
      body{
        padding:16px;
      }
      .auth-shell{
        border-radius:26px;
      }
      .auth-intro{
        display:none;
      }
      .auth-card{
        padding:0;
        background:transparent;
      }
      .auth-card-inner{
        box-shadow:0 20px 44px rgba(15,23,42,.6);
      }
      .mobile-logo-wrap{
        display:flex;
      }
    }
  </style>
</head>
<body>

  <main class="auth-shell">
    <!-- Left side: intro & branding -->
    <section class="auth-intro">
      <div>
        <div class="auth-logo-hero">
          <div class="auth-logo">
            <img src="tools/pics/mainlogo.png" alt="UniHive Logo">
          </div>
          <div class="auth-brand-text">
            <span>UniHive</span>
          </div>
        </div>

        <div class="auth-headline">
          <br>
          <h1>Welcome to UniHive</h1>
          <div class="auth-subtitle">
            A unified digital platform for campus engagement and collaboration.
          </div>
          <p>
            UniHive is your digital home for campus connection, discovery, and belonging.
            We bring students, clubs, sponsors, and university partners together to inspire
            activity, support collaboration, and empower meaningful opportunities throughout
            campus life.
          </p>
        </div>
      </div>
    </section>

    <!-- Right side: login form -->
    <section class="auth-card">
      <div class="auth-card-inner">
        <!--  logo -->
        <div class="mobile-logo-wrap">
          <img src="tools/pics/mainlogo.png" alt="UniHive Logo">
        </div>

        <div class="auth-card-header">
          <div class="role-text">Secure sign in</div>
          <h2>Log in to UniHive</h2>
          <p>Enter your email and password to access your dashboard.</p>
        </div>

        <?php if ($loginError): ?>
          <div class="error-banner">
            <?php echo htmlspecialchars($loginError); ?>
          </div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <div class="form-group">
            <label for="email">University / Work Email</label>
            <div class="input-wrapper">
              <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.edu"
                required
              >
            </div>
            <div class="role-hint" id="roleHint">
              We use your email to recognize whether you are a student, sponsor, or admin.
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
              >
            </div>
          </div>

          <div class="forgot-row">
            <a class="forgot-link" href="https://adresetpw.ju.edu.jo/" target="_blank" rel="noopener">
              Forgot password?
            </a>
          </div>

          <button type="submit" class="submit-btn">
            Sign In
          </button>

          <a href="register.php" class="secondary-btn">
            Become a Sponsor
          </a>

          <p class="below-text">
            After you sign in, <span>UniHive</span> uses your email to open the correct
            portal for you — Student, Sponsor, or Admin.
          </p>
        </form>
      </div>
    </section>
  </main>

  <script>
    const emailInput = document.getElementById('email');
    const roleHint   = document.getElementById('roleHint');

    function detectRoleFromEmail(email){
      const v = email.toLowerCase().trim();
      if (!v.includes('@')) return '';

      if (v.includes('student')) {
        return 'Student';
      }
      if (v.includes('sponsor')) {
        return 'Sponsor';
      }
      if (v.includes('admin') || v.includes('staff')) {
        return 'Admin';
      }
      return '';
    }

    emailInput.addEventListener('input', function(){
      const role = detectRoleFromEmail(this.value);
      if (!role){
        roleHint.textContent =
          'We use your email to recognize whether you are a student, sponsor, or admin.';
      } else {
        roleHint.innerHTML = 'Detected role: <span>' + role + '</span>.';
      }
    });
  </script>
</body>
</html>

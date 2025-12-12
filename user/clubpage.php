<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    // لو بدك تخلي الـ president يدخل على صفحة مختلفة
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_president') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once '../config.php'; // الاتصال مع الداتابيس

$studentId = (int)$_SESSION['student_id'];

// ===== 1) جلب بيانات الطالب =====
$sqlStudent = "SELECT * FROM student WHERE student_id = ?";
$stmtStu = $conn->prepare($sqlStudent);
$stmtStu->bind_param("i", $studentId);
$stmtStu->execute();
$resultStu = $stmtStu->get_result();
$student = $resultStu->fetch_assoc();
$stmtStu->close();

if (!$student) {
    // لو صار إشي غريب
    die("Student not found.");
}

$studentClubId = (int)$student['club_id'];

// من وين جاي على الصفحة؟ (Discover ولا My Club)
$isFromDiscover = isset($_GET['club_id']);

// club_id المعروض في الصفحة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['club_id'])) {
    $clubId = (int)$_POST['club_id'];
} elseif (isset($_GET['club_id'])) {
    $clubId = (int)$_GET['club_id'];
} else {
    // من My Club → نستخدم club_id تبع الطالب (ممكن يكون 1 = No Club)
    $clubId = $studentClubId ?: 1;
}

// ما منسمح بـ club_id أقل من 1
if ($clubId < 1) {
    $clubId = 1;
}

// ===== 2) معالجة أزرار JOIN / LEAVE (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action   = $_POST['action'];
    $postedClubId = (int)($_POST['club_id'] ?? 0);

    // أمان: ما نشتغل لو الـ club مش مضبوط
    if ($postedClubId > 0) {
        if ($action === 'join' && $postedClubId !== $studentClubId && $postedClubId !== 1) {
            // هل في طلب سابق لنفس النادي؟
            $sqlLastReq = "
                SELECT request_id, status 
                FROM club_membership_request
                WHERE club_id = ? AND student_id = ?
                ORDER BY submitted_at DESC, request_id DESC
                LIMIT 1
            ";
            $stmtLast = $conn->prepare($sqlLastReq);
            $stmtLast->bind_param("ii", $postedClubId, $studentId);
            $stmtLast->execute();
            $resLast = $stmtLast->get_result();
            $lastReq = $resLast->fetch_assoc();
            $stmtLast->close();

            if ($lastReq && $lastReq['status'] === 'pending') {
                // في طلب معلق بالفعل → ما نضيف كمان واحد
                // بس عادي نرجع لنفس الصفحة
            } else {
                // إدخال طلب عضوية جديد بحالة pending
                $reason = "Join request submitted from student portal.";
                $sqlIns = "
                    INSERT INTO club_membership_request (club_id, student_id, reason, status, submitted_at)
                    VALUES (?, ?, ?, 'pending', NOW())
                ";
                $stmtIns = $conn->prepare($sqlIns);
                $stmtIns->bind_param("iis", $postedClubId, $studentId, $reason);
                $stmtIns->execute();
                $stmtIns->close();
            }
        } elseif ($action === 'leave' && $postedClubId === $studentClubId && $postedClubId !== 1) {
            // 1) تحديث الطالب → يرجع على No Club
            $sqlUpdateStu = "UPDATE student SET club_id = 1 WHERE student_id = ?";
            $stmtUpdateStu = $conn->prepare($sqlUpdateStu);
            $stmtUpdateStu->bind_param("i", $studentId);
            $stmtUpdateStu->execute();
            $stmtUpdateStu->close();

            // 2) (اختياري) تحديث آخر طلب approved → نخليه left
            $sqlFindApproved = "
                SELECT request_id 
                FROM club_membership_request
                WHERE club_id = ? AND student_id = ? AND status = 'approved'
                ORDER BY submitted_at DESC, request_id DESC
                LIMIT 1
            ";
            $stmtFA = $conn->prepare($sqlFindApproved);
            $stmtFA->bind_param("ii", $postedClubId, $studentId);
            $stmtFA->execute();
            $resFA = $stmtFA->get_result();
            $approvedReq = $resFA->fetch_assoc();
            $stmtFA->close();

            if ($approvedReq) {
                $reqId = (int)$approvedReq['request_id'];
                $sqlUpdReq = "
                    UPDATE club_membership_request
                    SET status = 'left',
                        decided_at = NOW(),
                        decided_by_student_id = ?
                    WHERE request_id = ?
                ";
                $stmtUpdReq = $conn->prepare($sqlUpdReq);
                $stmtUpdReq->bind_param("ii", $studentId, $reqId);
                $stmtUpdReq->execute();
                $stmtUpdReq->close();
            }

            // بعد الـ leave نحدّث قيمة club_id في المتغير المحلي كمان
            $studentClubId = 1;
            if (!$isFromDiscover) {
                $clubId = 1;
            }
        }
    }

    // منع إعادة إرسال الفورم لو عمل refresh
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// ===== 3) جلب بيانات النادي =====
$sqlClub = "SELECT * FROM club WHERE club_id = ?";
$stmtClub = $conn->prepare($sqlClub);
$stmtClub->bind_param("i", $clubId);
$stmtClub->execute();
$resClub = $stmtClub->get_result();
$club = $resClub->fetch_assoc();
$stmtClub->close();

// لو club مش موجود
if (!$club) {
    $clubName        = "Club Not Found";
    $clubDescription = "This club does not exist.";
    $clubLogo        = "tools/pics/social_life.png";
    $contactEmail    = "";
    $facebookUrl     = "#";
    $instagramUrl    = "#";
    $linkedinUrl     = "#";
    $memberCount     = 0;
    $clubPoints      = 0;
} else {
    $clubName        = $club['club_name'];
    $clubDescription = $club['description'];
    $clubLogo        = !empty($club['logo']) ? $club['logo'] : "tools/pics/social_life.png";
    $contactEmail    = $club['contact_email'];
    $facebookUrl     = !empty($club['facebook_url']) ? $club['facebook_url'] : "#";
    $instagramUrl    = !empty($club['instagram_url']) ? $club['instagram_url'] : "#";
    $linkedinUrl     = !empty($club['linkedin_url']) ? $club['linkedin_url'] : "#";
    $memberCount     = (int)$club['member_count'];
    $clubPoints      = (int)$club['points'];
}

// ===== 4) جلب آخر طلب عضوية لهذا الطالب مع هذا النادي (لو موجود) =====
$sqlLastReq = "
    SELECT request_id, status
    FROM club_membership_request
    WHERE club_id = ? AND student_id = ?
    ORDER BY submitted_at DESC, request_id DESC
    LIMIT 1
";
$stmtLast = $conn->prepare($sqlLastReq);
$stmtLast->bind_param("ii", $clubId, $studentId);
$stmtLast->execute();
$resLast = $stmtLast->get_result();
$lastReq = $resLast->fetch_assoc();
$stmtLast->close();

$lastReqStatus = $lastReq['status'] ?? null;

// هل الطالب عضو حاليًا في هذا النادي؟
$isCurrentMember = ($studentClubId === $clubId && $clubId !== 1);

// ===== 5) جلب الفعاليات الخاصة بهذا النادي =====
$events = [];
$sqlEvents = "
    SELECT * 
    FROM event
    WHERE club_id = ?
    ORDER BY starting_date ASC
";
$stmtEv = $conn->prepare($sqlEvents);
$stmtEv->bind_param("i", $clubId);
$stmtEv->execute();
$resEv = $stmtEv->get_result();
while ($row = $resEv->fetch_assoc()) {
    $events[] = $row;
}
$stmtEv->close();

$eventsDone = count($events);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Clubs Hub — Your Club</title>

<!-- Font -->
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ========= Brand Tokens ========= */
:root{
  --navy: #242751;
  --royal: #4871db;
  --light: #a9bff8;
  --paper: #eef2f7;
  --ink: #0e1228;
  --gold: #f4df6d;
  --white: #ffffff;
  --muted: #6b7280;
  --shadow:0 14px 34px rgba(10, 23, 60, .18);
  --radius:18px;
}

*{box-sizing:border-box}
html,body{margin:0}
body{
  font-family:"Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
    var(--paper);
  line-height:1.5;
}

/* ========= Top Nav ========= */
.nav{
  position:sticky; top:0; z-index:50;
  background:var(--navy); color:#fff;
  box-shadow:var(--shadow);
}
.nav-inner{
  max-width:1100px; margin:0 auto; padding:16px 20px;
  display:flex; align-items:center; gap:16px; justify-content:space-between;
}
.brand{
  display:flex; align-items:center; gap:12px; color:#fff; text-decoration:none;
}
.brand-mark{
  width:40px;height:40px;border-radius:50%;
  background:conic-gradient(from 90deg at 50% 50%, #ff6a6a, #ffd36b, #7ad3ff, #9af0b2, #ff6a6a);
  box-shadow:0 4px 12px rgba(0,0,0,.2);
}
.brand h1{font-size:18px; letter-spacing:.12em; margin:0; text-transform:uppercase}

.nav-links{
  display:flex; gap:28px; align-items:center;
}
.nav-links a{
  color:#e8edff; text-decoration:none; font-weight:700; font-size:15px;
  opacity:.9;
}
.nav-links a:hover{opacity:1; text-decoration:underline}
.user-badge{
  display:flex; align-items:center; gap:8px; color:#fff; font-weight:700;
}
.user-dot{width:10px; height:10px; background:#ff6a6a; border-radius:50%}

/* ========= Container helpers ========= */
.section{padding:15px 20px}
.wrap{max-width:1100px; margin:0 auto}

/* ========= Hero / Your Club ========= */
.hero{
  padding:0 0 28px 0;
}

.hero-card{
  position:relative; overflow:hidden; border-radius:28px;
  box-shadow:var(--shadow);
  min-height:320px;
  display:flex; align-items:flex-end;
  background: none;
}

.hero-card::before{
  content:"";
  position:absolute; inset:0;
  background-image: var(--hero-bg, url("https://images.unsplash.com/photo-1531189611190-3c6c6b3c3d57?q=80&w=1650&auto=format&fit=crop"));
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  filter: grayscale(.12) contrast(1.03);
  opacity: .95;
}

.hero-card::after{
  content:"";
  position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(36,39,81,.15) 0%,
                                        rgba(36,39,81,.35) 60%,
                                        rgba(36,39,81,.55) 100%);
  pointer-events:none;
}

.hero-top{
  position:absolute; left:24px; right:24px; top:20px;
  display:flex; justify-content:space-between; align-items:center; color:#fff;
  text-shadow:0 8px 26px rgba(0,0,0,.35);
}

.hero-top h1{margin:0; letter-spacing:.35em; font-size:32px}
.tag{
  background:rgba(244,223,109,.95); color:#2b2f55; font-weight:800;
  padding:8px 14px; border-radius:999px; font-size:12px;
}

.hero-pillrow{
  position:relative; width:100%; padding:18px; display:flex; gap:18px; flex-wrap:wrap;
}
.pill{
  flex:1 1 260px; display:flex; align-items:center; gap:14px;
  backdrop-filter: blur(6px);
  background:rgba(255,255,255,.82);
  border:1px solid rgba(255,255,255,.7);
  border-radius:20px; padding:12px 14px; color:#1d244d;
}

.circle{
  width:42px;height:42px;border-radius:50%;
  background:radial-gradient(circle at 30% 30%, #fff, #b9ccff);
  display:grid; place-items:center; font-weight:800; font-size:14px; color:#1d244d;
  border:2px solid rgba(255,255,255,.8);
}

/* ========= Headings with divider ========= */
.h-title{
  font-size:34px; letter-spacing:.35em; text-transform:uppercase; margin:34px 0 12px;
  text-align:left; color:#2b2f55;
}
.hr{
  height:3px; width:280px; background:#2b2f55; opacity:.35; border-radius:3px; margin:10px 0 24px;
}

/* ========= About section ========= */
.about{
  color: #fff;
  background:#4871db; margin-top:18px;
  border-radius:26px; padding:26px; box-shadow:var(--shadow);
}
.about p{max-width:800px; font-size:18px}
.link-grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:20px; max-width:720px; margin-top:18px;
}
.link-tile{
  display:flex; align-items:center; gap:12px;
  padding:12px 14px; border-radius:14px; background:#fff;color:#2b2f55; border:1px solid #e6e8f2;text-decoration:none;
  
}
.link-tile svg{flex:0 0 22px}

.link-tile:hover{
   background: #f4df6d;transform :translateY(-10px);border-color:var(--royal);
}
.links{
  font-weight:700 ;text-align:center;
}

/* ========= Upcoming Events ========= */
.wrapper{max-width:1100px;margin:20px auto 40px;padding:0 18px}
.page-title{font-size:30px;font-weight:800;color:var(--navy);margin:10px 0 4px}
.subtle{color:#6b7280;margin:0 0 15px;font-size:15px}
.section{margin:15px 0}
.section h2{font-size:20px;margin:0 0 10px;color:var(--navy);font-weight:800}

.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
@media (max-width:800px){ .grid{grid-template-columns:1fr} }

.card{
  background:var(--card);border-radius:16px;box-shadow:0 14px 34px rgba(10,23,60,.12);
  padding:18px;display:grid;grid-template-columns:90px 1fr;gap:16px;
}
.date{
  display:flex;flex-direction:column;justify-content:center;align-items:center;
  background:#f2f5ff;border-radius:14px;padding:12px 10px;text-align:center;
  font-weight:800;min-height:90px;color:var(--navy);
}
.date .day{font-size:28px}
.date .mon{font-size:12px;margin-top:2px}
.date .sep{font-size:11px;color:#6b7280;margin-top:6px}

.topline{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.badge{background:#eaf6ee;color:#1f8f4e;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:800}
.chip.sponsor{background:#fff7e6;color:#8a5b00;padding:6px 10px;border-radius:12px;font-size:12px;font-weight:700;border:1px solid #ffecb5}

.title{margin:8px 0 4px;font-weight:800;font-size:18px;color:var(--ink)}
.mini{color:#6b7280;font-size:13px;display:flex;gap:14px;flex-wrap:wrap}
.footer{margin-top:8px;font-size:13px;color:#6b7280;display:flex;align-items:center}

.state.completed{ background:#ecfdf3; color:#127a39; padding:6px 10px;border-radius:12px;font-size:12px;font-weight:800}
.stars{position:relative;display:inline-block;font-size:16px;letter-spacing:2px;--rating:4.5}
.stars::before{content:"★★★★★";color:#e5e7eb}
.stars::after{content:"★★★★★";position:absolute;left:0;top:0;width:calc(var(--rating)/5*100%);overflow:hidden;color:#f5c542;white-space:nowrap}
.review{display:flex;align-items:center;gap:8px;font-weight:800;color:#111827}
.sepbar{height:1px;background:#e5e7eb;margin:14px 0}

/* ========= Stats + CTA ========= */
.stats{ margin-top:34px; display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
.stat{
  background:#fff; border-radius:18px; padding:18px; border:1px solid #e6e8f2; text-align:center;
  box-shadow:0 10px 24px rgba(10,23,60,.06);
}
.stat h5{margin:0 0 6px; letter-spacing:.25em; text-transform:uppercase; color: #2b2f55; font-size:13px}
.kpi{
  background:#f4df6d; border:2px #e5c94a; border-radius:14px; display:inline-block;
  padding:10px 18px; font-weight:900; font-size:22px; letter-spacing:.2em; margin-top:6px;
}

/* Join button */
.join{
  appearance:none;
  -webkit-appearance:none;
  
  width:100%;              /* ✅ Makes it full width again */
  display:block;           /* ✅ Makes it behave like a banner */

  padding:14px 28px;
  line-height:1;
  font-size:18px;
  font-weight:900;

  border:none;
  border-radius:999px;

  background:#f4df6d;
  color:#2b2f55;

  box-shadow:0 12px 26px rgba(255,213,1,.35);
  cursor:pointer;

  margin:26px 0 0;
  text-align:center;        /* ensure centered text */
}

.join:hover{
  background:#fff;
  color:#fae06e;
  box-shadow:0 12px 26px rgba(141,141,141,.35);
}


/* ========= Simple Modal ========= */
.modal-wrap{
  position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
  background:rgba(0,0,0,.45); padding:20px; z-index:100; opacity:0; pointer-events:none; transition:.2s;
}
.modal-wrap.open{ opacity:1; pointer-events:auto; }
.modal{
  width:100%; max-width:420px; background:#fff; border-radius:20px; box-shadow:var(--shadow);
  border:1px solid #e8eaf3; overflow:hidden;
}
.modal-header{
  display:flex; align-items:center; gap:12px; padding:18px 18px 0 18px;
}
.icon-badge{
  width:44px; height:44px; border-radius:50%; display:grid; place-items:center;
  background:#ecfdf5; border:2px solid #bbf7d0;
}
.icon-badge.warn{ background:#fff7ed; border-color:#fed7aa; }
.modal-body{ padding:10px 18px 18px; color:#374151; }
.modal h3{ margin:0; font-size:20px; }
.modal p{ margin:6px 0 0; }
.modal-actions{ padding:12px 18px 18px; display:flex; justify-content:flex-end; gap:8px; }
.btn{
  appearance:none; border:0; padding:10px 14px; border-radius:12px; font-weight:800; cursor:pointer;
}
.btn.primary{ background:linear-gradient(135deg, var(--royal), var(--light)); color:#fff; }
.btn.ghost{ background:#f3f4f6; color:#111827; }

@media (max-width:900px){
  .nav-links{display:none}
  .hero-top h2{font-size:18px}
  .e-right{align-items:flex-start}
  .stats{grid-template-columns:1fr}
  .link-grid{grid-template-columns:1fr 1fr}
}
@media (max-width:520px){
  .pill{flex:1 1 100%}
  .link-grid{grid-template-columns:1fr}
}

/* ===== Brand Tokens (dup for header/footer includes) ===== */
:root{
  --navy:#242751; --royal:#4871db; --lightBlue:#a9bff8;
  --gold:#e5b758; --sun:#f4df6d; --coral:#ff5e5e;
  --paper:#e9ecef; --ink:#0e1228; --card:#fff;
  --shadow:0 10px 30px rgba(0,0,0,.16);
}
body{ color:var(--ink); background:var(--paper); }
.display{ font-family:"Extenda 90 Exa","Raleway",system-ui,sans-serif; letter-spacing:.3px; }
.leave{
  appearance:none;
  -webkit-appearance:none;

  width:100%;
  display:block;

  padding:14px 28px;
  line-height:1;
  font-size:18px;
  font-weight:900;

  border:none;
  border-radius:999px;

  background:#ff5e5e;  /* red brand */
  color:#ffffff;

  box-shadow:0 12px 26px rgba(255,94,94,.35);
  cursor:pointer;

  margin:14px 0 0;   /* space below join */
  text-align:center;
}

.leave:hover{
  background:#fff;
  color:#ff5e5e;
  box-shadow:0 12px 26px rgba(141,141,141,.35);
}

</style>
</head>

<body>
  <?php include 'header.php'; ?>
  <div class="underbar"></div>

  <!-- ========== HERO ========== -->
  <section class="section hero">
    <div class="wrap">
      <div class="hero-card" style="--hero-bg: url('<?php echo htmlspecialchars($clubLogo); ?>');">

        <div class="hero-top">
          <div class="tag">
            <?php echo htmlspecialchars($club['category'] ?? 'Club'); ?>
          </div>
        </div>

        <div class="hero-pillrow">
          <!-- Club pill -->
          <div class="pill">
            <img src="<?php echo htmlspecialchars($clubLogo); ?>"
                alt="Club Logo"
                style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.8)" />
            <div>
              <div style="font-size:12px;opacity:.8">club name</div>
              <strong id="clubs"><?php echo htmlspecialchars($clubName); ?></strong>
            </div>
          </div>

          <!-- بسيط: خلي Pill الثاني general عن النادي -->
          <div class="pill">
            <div class="circle">
              <?php echo $memberCount > 0 ? (int)$memberCount : 0; ?>
            </div>
            <div>
              <div style="font-size:12px;opacity:.8">active members</div>
              <strong><?php echo htmlspecialchars($clubName); ?> Community</strong>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

    <!-- ========== ABOUT ========== -->
  <section class="section">
    <div class="wrap">
      <h3 class="h-title" id="about">About Club</h3>
      <div class="hr"></div>

      <div class="about">
        <p>
          <?php echo nl2br(htmlspecialchars($clubDescription)); ?>
        </p>

        <!-- 🔹 Club President / Contact -->
        <div style="
          background: rgba(255,255,255,0.12);
          border: 1px solid rgba(255,255,255,0.25);
          border-radius: 14px;
          padding: 14px 18px;
          display: flex;
          align-items: center;
          gap: 12px;
          margin: 22px 0;
          color: #fff;
          box-shadow: inset 0 0 10px rgba(0,0,0,0.08);
        ">
          <svg viewBox='0 0 24 24' width='22' height='22' fill='none' stroke='#f4df6d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
            <path d='M4 4h16v16H4z' stroke='none'/>
            <path d='M4 4l8 8l8-8'/>
          </svg>
          <div>
            <div style="font-size:12px;opacity:.85;">President / Club Contact</div>
            <?php if (!empty($contactEmail)): ?>
              <strong><a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" style="color:#f4df6d; text-decoration:none;">
                <?php echo htmlspecialchars($contactEmail); ?>
              </a></strong>
            <?php else: ?>
              <strong>No contact email added yet.</strong>
            <?php endif; ?>
          </div>
        </div>

        <h4 style="letter-spacing:.4em; text-transform:uppercase; margin:16px 0 8px; color: #f4df6d">Links</h4>
        <div class="link-grid">
          <a class="link-tile" href="<?php echo htmlspecialchars($linkedinUrl ?: '#'); ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="#0a66c2" aria-hidden="true"><path d="M20.447 20.452h-3.555V14.86c0-1.333-.027-3.045-1.856-3.045-1.858 0-2.142 1.45-2.142 2.95v5.688H9.338V9h3.414v1.561h.048c.476-.9 1.637-1.85 3.369-1.85 3.602 0 4.268 2.371 4.268 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM6.99 20.452H3.68V9h3.31v11.452z"/></svg>
            <span class="links">LinkedIn</span>
          </a>
          <a class="link-tile" href="<?php echo htmlspecialchars($instagramUrl ?: '#'); ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" style="color:#E4405F">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor" stroke-width="2"/>
              <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
            </svg>
            <span class="links">Instagram</span>
          </a>
          <a class="link-tile" href="<?php echo htmlspecialchars($facebookUrl ?: '#'); ?>" target="_blank" rel="noreferrer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="#1877f2" aria-hidden="true"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.08 5.66 21.2 10.44 22v-7.02H7.9v-2.92h2.54v-2.2c0-2.5 1.5-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.92h-2.34V22C18.34 21.2 22 17.08 22 12.06z"/></svg>
            <span class="links">Facebook</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== UPCOMING EVENTS ========== -->
  <section class="section">
    <div class="wrap">
      <h2>Upcoming Events</h2>

      <div class="grid">
        <?php if (count($events) === 0): ?>
          <p style="color:#6b7280; font-size:14px; grid-column:1/-1;">
            No upcoming events have been added for this club yet.
          </p>
        <?php else: ?>
          <?php foreach ($events as $ev): ?>
            <?php
              $start = new DateTime($ev['starting_date']);
              $day  = $start->format('d');
              $mon  = strtoupper($start->format('M'));
              $dow  = $start->format('D');
              $time = $start->format('g:i A');
              $location = $ev['event_location'];
              $maxAtt  = (int)$ev['max_attendees'];
            ?>
            <article class="card">
              <div class="date">
                <div class="day"><?php echo $day; ?></div>
                <div class="mon"><?php echo $mon; ?></div>
                <div class="sep"><?php echo $dow; ?></div>
              </div>
              <div>
                <div class="topline">
                  <span class="badge">Max <?php echo $maxAtt; ?> seats</span>
                  <span class="chip sponsor">Club Event</span>
                </div>
                <div class="title"><?php echo htmlspecialchars($ev['event_name']); ?></div>
                <div class="mini"><span>📍 <?php echo htmlspecialchars($location); ?></span></div>
                <div class="footer"><span class="mini">🕒 <?php echo $time; ?></span></div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Stats -->
      <div class="stats">
        <div class="stat">
          <h5>Events done</h5>
          <div class="kpi"><?php echo str_pad($eventsDone, 3, "0", STR_PAD_LEFT); ?></div>
        </div>
        <div class="stat">
          <h5>Member</h5>
          <div class="kpi"><?php echo str_pad($memberCount, 3, "0", STR_PAD_LEFT); ?></div>
        </div>
        <div class="stat">
          <h5>Earned points</h5>
          <div class="kpi"><?php echo str_pad($clubPoints, 4, "0", STR_PAD_LEFT); ?></div>
        </div>
      </div>

      <!-- Join / Leave CTA -->
      <?php if ($clubId !== 1): ?>
        <?php if ($isCurrentMember): ?>
          <!-- الطالب عضو في هذا النادي → يظهر Leave فقط -->
          <form method="post" style="margin-top:26px;">
            <input type="hidden" name="club_id" value="<?php echo (int)$clubId; ?>">
            <button id="leaveBtn" class="leave" type="submit" name="action" value="leave" href="discover.php">
              Leave
            </button>
          </form>
        <?php else: ?>
          <!-- الطالب مش عضو → يظهر زر Join -->
          <form method="post" style="margin-top:26px;">
            <input type="hidden" name="club_id" value="<?php echo (int)$clubId; ?>">
            <button id="joinBtn" class="join" type="submit" name="action" value="join" href="discover.php">
              <?php
                if ($lastReqStatus === 'pending') {
                    echo "Request pending…";
                } elseif ($lastReqStatus === 'approved') {
                    echo "You are approved for this club";
                } elseif ($lastReqStatus === 'rejected') {
                    echo "Join us again?";
                } else {
                    echo "Join us!";
                }
              ?>
            </button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ========== Modal (hidden by default) ========== -->
  <div class="modal-wrap" id="joinModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
      <div class="modal-header">
        <div class="icon-badge" id="modalIcon">
          <!-- Tick icon -->
          <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
            <path d="M20 6L9 17l-5-5" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 id="modalTitle">Your request is under review</h3>
      </div>
      <div class="modal-body" id="modalBody">
        <p>Thanks! You’ll get an update soon.</p>
      </div>
      <div class="modal-actions">
        <button class="btn primary" id="okModalBtn" type="button" autofocus>OK</button>
      </div>

    </div>
  </div>

  <div class="underbar"></div>
  <?php include 'footer.php'; ?>

  <script>
    (function(){
      const JOIN_KEY = 'cch_join_request_sent';

      const joinBtn   = document.getElementById('joinBtn');
      const modalWrap = document.getElementById('joinModal');
      const modalIcon = document.getElementById('modalIcon');
      const modalTitle= document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');
      const btnOk     = document.getElementById('okModalBtn');

      // لو ما في زر join (مثلاً الطالب عضو) ما نكمّل الجافاسكربت
      if (!joinBtn) {
        return;
      }

      let lastFocusedEl = null;

      function setFirstState(){
        // Green tick + review text
        modalIcon.classList.remove('warn');
        modalIcon.innerHTML = `
          <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
            <path d="M20 6L9 17l-5-5" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>`;
        modalTitle.textContent = 'Your request is under review';
        modalBody.innerHTML = '<p>Thanks! You’ll get an update soon.</p>';
      }

      function setSecondState(){
        // Orange warn icon + already sent text
        modalIcon.classList.add('warn');
        modalIcon.innerHTML = `
          <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
            <path d="M12 9v4m0 4h.01M10.29 3.86l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.71-3.14l-8-14a2 2 0 0 0-3.42 0z" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>`;
        modalTitle.textContent = 'Request already sent';
        modalBody.innerHTML = '<p>You have already submitted a join request for this club.</p>';
      }

      function openModal(){
        lastFocusedEl = document.activeElement;
        modalWrap.classList.add('open');
        modalWrap.setAttribute('aria-hidden','false');
        btnOk.focus();
        document.body.style.overflow = 'hidden';
      }

      function closeModal(){
        modalWrap.classList.remove('open');
        modalWrap.setAttribute('aria-hidden','true');
        document.body.style.overflow = '';
        if (lastFocusedEl && typeof lastFocusedEl.focus === 'function') lastFocusedEl.focus();
      }

      // clicking join
      joinBtn.addEventListener('click', () => {
        const alreadySent = localStorage.getItem(JOIN_KEY) === '1';
        if (alreadySent) {
          setSecondState();
        } else {
          setFirstState();
          // mark as sent immediately
          localStorage.setItem(JOIN_KEY, '1');
        }
        openModal();
        // ما منمنع submit → الفورم رح ينرسل عادي
      });

      // close actions
      btnOk.addEventListener('click', closeModal);
      modalWrap.addEventListener('click', (e) => {
        if (e.target === modalWrap) closeModal();
      });
      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalWrap.classList.contains('open')) closeModal();
      });
    })();
  </script>
</body>
</html>

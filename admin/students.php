<?php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// ===== Fetch students with club name =====
$sql = "
  SELECT 
    s.student_id,
    s.student_name,
    s.email,
    s.major,
    s.total_points,
    s.role,
    s.club_id,
    c.club_name
  FROM student s
  LEFT JOIN club c ON s.club_id = c.club_id
  ORDER BY s.student_name
";

$result = $conn->query($sql);
$students = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>UniHive — View Students</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --navy:#242751;
      --coral:#ff5e5e;
      --paper:#eef2f7;
      --card:#ffffff;
      --border:#e5e7eb;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow-soft:0 16px 36px rgba(15,23,42,.08);
      --radius-card:24px;
      --sidebarWidth:240px;
    }

    *{box-sizing:border-box;margin:0;padding:0}

    body{
      font-family:"Raleway",system-ui,-apple-system,Segoe UI,sans-serif;
      background:linear-gradient(180deg,#f9fafb 0,#e5e7eb 40%,#e5e7eb 100%);
      color:var(--ink);
      min-height:100vh;
    }

    .admin-main{
      margin-left:var(--sidebarWidth);
      padding:26px 32px 40px;
    }

    .page-header{
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:18px;
      margin-bottom:22px;
    }

    .page-title h1{
      font-size:1.9rem;
      letter-spacing:.02em;
    }

    .page-title p{
      margin-top:4px;
      font-size:.9rem;
      color:var(--muted);
    }

    .students-toolbar{
      display:flex;
      gap:12px;
      align-items:center;
    }

    .search-input{
      background:var(--card);
      border:1px solid var(--border);
      color:var(--ink);
      padding:10px 18px;
      border-radius:999px;
      font-size:.9rem;
      min-width:260px;
      box-shadow:0 8px 22px rgba(148,163,184,.35);
      outline:none;
    }

    .search-input::placeholder{
      color:#9ca3af;
    }

    .students-wrapper{
      background:linear-gradient(145deg,#f9fafb,#edf2ff);
      border-radius:32px;
      padding:24px 22px 26px;
      box-shadow:var(--shadow-soft);
      border:1px solid rgba(148,163,184,.35);
    }

    .students-meta{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:18px;
      font-size:.85rem;
      color:var(--muted);
    }

    .students-count{
      font-weight:600;
    }

    .students-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
      gap:18px;
    }

    .student-card{
      background:var(--card);
      border-radius:var(--radius-card);
      border:1px solid var(--border);
      box-shadow:0 12px 28px rgba(15,23,42,.06);
      padding:16px 18px 18px;
      display:flex;
      flex-direction:column;
      gap:6px;
      position:relative;
      overflow:hidden;
    }

    .student-card::before{
      content:"";
      position:absolute;
      inset:-40%;
      background:radial-gradient(circle at top right,rgba(248,113,113,.12),transparent 60%);
      pointer-events:none;
    }

    .student-main{
      position:relative;
      z-index:1;
    }

    .student-name{
      font-weight:700;
      font-size:1.05rem;
      margin-bottom:2px;
    }

    .student-email{
      font-size:.86rem;
      color:var(--muted);
      margin-bottom:8px;
      word-break:break-all;
    }

    .student-row{
      display:flex;
      gap:8px;
      font-size:.83rem;
      margin-bottom:2px;
    }

    .label{
      font-weight:600;
      color:var(--muted);
      min-width:90px;
    }

    .club-pill{
      margin-top:10px;
      align-self:flex-start;
      padding:6px 12px;
      border-radius:999px;
      background:rgba(255,94,94,.08);
      border:1px solid rgba(255,94,94,.4);
      font-size:.8rem;
      font-weight:600;
      color:var(--coral);
      display:inline-flex;
      align-items:center;
      gap:6px;
      text-decoration:none;
    }

    .club-pill-dot{
      width:7px;
      height:7px;
      border-radius:999px;
      background:var(--coral);
    }

    @media(max-width:900px){
      .admin-main{
        margin-left:0;
        padding:20px 16px 28px;
      }

      .page-header{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
      }

      .students-toolbar{
        width:100%;
      }

      .search-input{
        width:100%;
      }
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <div class="admin-main">
    <!-- Header -->
    <div class="page-header">
      <div class="page-title">
        <h1>View Students</h1>
        <p>See all registered students, their role, points, and which clubs they belong to.</p>
      </div>

      <div class="students-toolbar">
        <input
          type="text"
          id="studentSearch"
          class="search-input"
          placeholder="Search by name or email..."
          oninput="filterStudents()"
        />
      </div>
    </div>

    <!-- Content -->
    <div class="students-wrapper">
      <div class="students-meta">
        <span class="students-count">
          Total students: <?php echo count($students); ?>
        </span>
      </div>

      <div class="students-grid" id="studentsGrid">
        <?php foreach ($students as $student): ?>
          <div class="student-card"
               data-name="<?php echo htmlspecialchars($student['student_name']); ?>"
               data-email="<?php echo htmlspecialchars($student['email']); ?>">
            <div class="student-main">
              <div class="student-name">
                <?php echo htmlspecialchars($student['student_name']); ?>
              </div>
              <div class="student-email">
                <?php echo htmlspecialchars($student['email']); ?>
              </div>

              <div class="student-row">
                <span class="label">Major:</span>
                <span><?php echo htmlspecialchars($student['major']); ?></span>
              </div>

              <div class="student-row">
                <span class="label">Total points:</span>
                <span><?php echo (int)$student['total_points']; ?></span>
              </div>

             <?php
  // Normalize role display
  $rawRole = strtolower(trim($student['role'] ?? ''));

  switch ($rawRole) {
    case 'student':
      $displayRole = 'Student';
      break;
    case 'club_president':
      $displayRole = 'Club president';
      break;
    default:
      // fallback
      $displayRole = ucfirst(str_replace('_', ' ', $rawRole));
      break;
  }
?>
<div class="student-row">
  <span class="label">Role:</span>
  <span><?php echo htmlspecialchars($displayRole); ?></span>
</div>


              <?php
                $clubName = $student['club_name'] ?? '';
                $clubId   = $student['club_id'];
              ?>

              <?php if (!empty($clubName) && !empty($clubId)): ?>
                <a href="clubpage.php?id=<?php echo (int)$clubId; ?>" class="club-pill">
                  <span class="club-pill-dot"></span>
                  <span><?php echo htmlspecialchars($clubName); ?></span>
                </a>
              <?php else: ?>
                <div class="club-pill">
                  <span class="club-pill-dot"></span>
                  <span>No club</span>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($students)): ?>
          <p style="font-size:.9rem;color:var(--muted);">
            No students found.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    function filterStudents(){
      const term = document
        .getElementById('studentSearch')
        .value.toLowerCase()
        .trim();

      document.querySelectorAll('.student-card').forEach(card => {
        const name  = card.getAttribute('data-name').toLowerCase();
        const email = card.getAttribute('data-email').toLowerCase();

        if(!term || name.includes(term) || email.includes(term)){
          card.style.display = '';
        }else{
          card.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>

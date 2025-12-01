<?php
// admin/editsponsor.php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

$sponsorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sponsorId <= 0) {
    header('Location: sponsors.php');
    exit;
}

$message = '';
$messageType = 'success';

/* ---------- Handle POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'info') {
        // تحديث بيانات الراعي
        $name     = trim($_POST['company_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '') {
            $message = 'Name and email are required.';
            $messageType = 'error';
        } else {
            if ($password === '') {
                // بدون تغيير الباسوورد
                $stmt = $conn->prepare("
                  UPDATE sponsor
                  SET company_name = ?, email = ?, phone = ?
                  WHERE sponsor_id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param("sssi", $name, $email, $phone, $sponsorId);
                    $stmt->execute();
                    $stmt->close();
                    $message = 'Sponsor info updated.';
                    $messageType = 'success';
                } else {
                    $message = 'Database error while updating sponsor.';
                    $messageType = 'error';
                }
            } else {
                $stmt = $conn->prepare("
                  UPDATE sponsor
                  SET company_name = ?, email = ?, phone = ?, password = ?
                  WHERE sponsor_id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param("ssssi", $name, $email, $phone, $password, $sponsorId);
                    $stmt->execute();
                    $stmt->close();
                    $message = 'Sponsor info & password updated.';
                    $messageType = 'success';
                } else {
                    $message = 'Database error while updating sponsor.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($formType === 'support') {
        // إضافة علاقة دعم جديدة
        $clubId      = (int)($_POST['club_id'] ?? 0);
        $supportType = trim($_POST['support_type'] ?? '');
        $startDate   = $_POST['start_date'] ?? '';
        $endDate     = $_POST['end_date'] ?? '';
        $notes       = trim($_POST['notes'] ?? '');

        if ($clubId <= 0 || $startDate === '' || $endDate === '') {
            $message = 'Club, start date, and end date are required.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("
              INSERT INTO sponsor_club_support (sponsor_id, club_id, support_type, start_date, end_date, notes)
              VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("iissss", $sponsorId, $clubId, $supportType, $startDate, $endDate, $notes);
                if ($stmt->execute()) {
                    $message = 'New sponsorship added.';
                    $messageType = 'success';
                } else {
                    $message = 'Database error while inserting sponsorship.';
                    $messageType = 'error';
                }
                $stmt->close();
            } else {
                $message = 'Failed to prepare sponsorship insert.';
                $messageType = 'error';
            }
        }
    }
}

/* ---------- Fetch sponsor info ---------- */
$stmt = $conn->prepare("SELECT * FROM sponsor WHERE sponsor_id = ?");
$stmt->bind_param("i", $sponsorId);
$stmt->execute();
$sponsorRes = $stmt->get_result();
$sponsor = $sponsorRes->fetch_assoc();
$stmt->close();

if (!$sponsor) {
    header('Location: sponsors.php?msg=' . urlencode('Sponsor not found.') . '&type=error');
    exit;
}

/* ---------- Fetch all clubs for dropdown ---------- */
$clubs = [];
$resClubs = $conn->query("SELECT club_id, club_name FROM club ORDER BY club_name");
if ($resClubs && $resClubs->num_rows > 0) {
    while ($row = $resClubs->fetch_assoc()) {
        $clubs[] = $row;
    }
}

/* ---------- Fetch sponsor supports (كلها) ---------- */
$supports = [];
$qs = "
  SELECT scs.*, c.club_name,
         CASE
           WHEN CURDATE() BETWEEN scs.start_date AND scs.end_date THEN 'Active'
           WHEN CURDATE() < scs.start_date THEN 'Upcoming'
           ELSE 'Expired'
         END AS status_label
  FROM sponsor_club_support scs
  LEFT JOIN club c ON c.club_id = scs.club_id
  WHERE scs.sponsor_id = ?
  ORDER BY scs.start_date DESC
";
$stmt2 = $conn->prepare($qs);
$stmt2->bind_param("i", $sponsorId);
$stmt2->execute();
$resSupports = $stmt2->get_result();
if ($resSupports && $resSupports->num_rows > 0) {
    while ($row = $resSupports->fetch_assoc()) {
        $supports[] = $row;
    }
}
$stmt2->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UniHive — Edit Sponsor</title>
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

.content{
  margin-left:var(--sidebarWidth);
  padding:32px 36px 48px;
}

.page-title{
  font-size:1.9rem;
  font-weight:800;
  color:var(--ink);
  margin-bottom:6px;
}
.page-subtitle{
  font-size:.96rem;
  color:var(--muted);
  margin-bottom:20px;
}

.alert{
  padding:10px 14px;
  border-radius:12px;
  font-size:.9rem;
  margin-bottom:16px;
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

.grid-2{
  display:grid;
  grid-template-columns:1.1fr 1.1fr;
  gap:22px;
}

.card{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:22px 24px 24px;
}

.card-title{
  font-size:1.05rem;
  font-weight:700;
  color:var(--navy);
  margin-bottom:10px;
}

.field-label{
  font-weight:600;
  margin-bottom:5px;
  font-size:.9rem;
  color:var(--navy);
}

.input-field,
.select-field,
.textarea-field{
  width:100%;
  padding:9px 12px;
  border-radius:12px;
  border:1px solid #e5e7eb;
  font-size:.92rem;
  outline:none;
  margin-bottom:12px;
}
.input-field:focus,
.select-field:focus,
.textarea-field:focus{
  border-color:var(--navy);
}
.textarea-field{
  min-height:70px;
  resize:vertical;
}

.helper-text{
  font-size:.8rem;
  color:var(--muted);
  margin-bottom:12px;
}

.actions-row{
  margin-top:10px;
  display:flex;
  gap:10px;
}

.btn{
  padding:9px 18px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-size:.88rem;
  font-weight:600;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}
.btn-primary{
  background:var(--navy);
  color:#ffffff;
}
.btn-primary:hover{
  background:#181b3b;
}
.btn-ghost{
  background:transparent;
  color:var(--muted);
}
.btn-ghost:hover{
  text-decoration:underline;
}

/* supports table */
.supports-table{
  width:100%;
  border-collapse:collapse;
  font-size:.85rem;
  margin-top:10px;
}
.supports-table th,
.supports-table td{
  padding:8px 6px;
  border-bottom:1px solid #e5e7eb;
}
.supports-table th{
  text-align:left;
  text-transform:uppercase;
  letter-spacing:.06em;
  font-size:.75rem;
  color:#6b7280;
}
.status-pill{
  padding:3px 9px;
  border-radius:999px;
  font-size:.75rem;
}
.status-Active{
  background:#dcfce7;
  color:#166534;
}
.status-Upcoming{
  background:#e0f2fe;
  color:#075985;
}
.status-Expired{
  background:#fee2e2;
  color:#b91c1c;
}

@media(max-width:980px){
  .grid-2{
    grid-template-columns:1fr;
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
  <div class="page-title">Edit Sponsor — <?= htmlspecialchars($sponsor['company_name']); ?></div>
  <div class="page-subtitle">
    Update sponsor account details and manage which clubs they are sponsoring and for what period.
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $messageType === 'error' ? 'alert-error' : 'alert-success'; ?>">
      <?= htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="grid-2">
    <!-- ===== Left: sponsor info ===== -->
    <div class="card">
      <div class="card-title">Sponsor account details</div>
      <form method="post" action="editsponsor.php?id=<?= $sponsorId; ?>">
        <input type="hidden" name="form_type" value="info">

        <label class="field-label">Company name</label>
        <input type="text" name="company_name" class="input-field"
               value="<?= htmlspecialchars($sponsor['company_name']); ?>" required>

        <label class="field-label">Email</label>
        <input type="email" name="email" class="input-field"
               value="<?= htmlspecialchars($sponsor['email']); ?>" required>

        <label class="field-label">Phone</label>
        <input type="text" name="phone" class="input-field"
               value="<?= htmlspecialchars($sponsor['phone']); ?>">

        <label class="field-label">Password (leave blank to keep current)</label>
        <input type="text" name="password" class="input-field" placeholder="New password (optional)">

        <div class="actions-row">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <a href="sponsors.php" class="btn btn-ghost">Back to sponsors</a>
        </div>
      </form>
    </div>

    <!-- ===== Right: add sponsorship ===== -->
    <div class="card">
      <div class="card-title">Add sponsorship (club / event)</div>

      <form method="post" action="editsponsor.php?id=<?= $sponsorId; ?>">
        <input type="hidden" name="form_type" value="support">

        <label class="field-label">Club</label>
        <select name="club_id" class="select-field" required>
          <option value="">Select club...</option>
          <?php foreach ($clubs as $club): ?>
            <option value="<?= (int)$club['club_id']; ?>">
              <?= htmlspecialchars($club['club_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label class="field-label">Support type (optional)</label>
        <input type="text" name="support_type" class="input-field" placeholder="e.g. financial, in-kind">

        <label class="field-label">Start date</label>
        <input type="date" name="start_date" class="input-field" required>

        <label class="field-label">End date</label>
        <input type="date" name="end_date" class="input-field" required>

        <label class="field-label">Notes (event, conditions, etc.)</label>
        <textarea name="notes" class="textarea-field" placeholder="e.g. Hackathon 2026 catering, vouchers for winners..."></textarea>

        <div class="helper-text">
          Once the end date passes, this sponsorship will automatically stop appearing
          under “Currently sponsoring” on the sponsors list.
        </div>

        <div class="actions-row">
          <button type="submit" class="btn btn-primary">Add sponsorship</button>
        </div>
      </form>

      <?php if (!empty($supports)): ?>
        <table class="supports-table">
          <thead>
            <tr>
              <th>Club</th>
              <th>Type</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($supports as $sp): ?>
              <tr>
                <td><?= htmlspecialchars($sp['club_name'] ?? '—'); ?></td>
                <td><?= htmlspecialchars($sp['support_type']); ?></td>
                <td><?= htmlspecialchars($sp['start_date']); ?></td>
                <td><?= htmlspecialchars($sp['end_date']); ?></td>
                <td>
                  <span class="status-pill status-<?= htmlspecialchars($sp['status_label']); ?>">
                    <?= htmlspecialchars($sp['status_label']); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="helper-text" style="margin-top:10px;">
          This sponsor has no recorded sponsorships yet.
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>

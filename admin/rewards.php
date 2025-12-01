<?php
// admin/rewards.php
require_once '../config.php';
require_once 'admin_auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// ===== Helper: generate code like COFFEE70 / random =====
function generateRewardCode(): string {
    $prefix = 'UH';
    $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $middle = '';
    for ($i = 0; $i < 5; $i++) {
        $middle .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . '-' . $middle;
}

// ===== Handle POST (add / delete) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Add new reward ----
    if ($action === 'add') {
        $name   = trim($_POST['reward_name'] ?? '');
        $points = (int)($_POST['points_cost'] ?? 0);

        if ($name === '' || $points <= 0) {
            $_SESSION['flash_error'] = 'Please enter a valid reward name and points.';
            header('Location: rewards.php');
            exit;
        }

        // Upload image (optional)
        $imagePath = null;
        if (!empty($_FILES['reward_image']['name'])) {
            $uploadDir = '../assets/rewards/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmp  = $_FILES['reward_image']['tmp_name'];
            $fileName = basename($_FILES['reward_image']['name']);
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $newName  = 'reward_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target   = $uploadDir . $newName;

            if (move_uploaded_file($fileTmp, $target)) {
                $imagePath = 'assets/rewards/' . $newName; // مثل القيم اللي عندك
            }
        }

        $code = generateRewardCode();

        $sql = "INSERT INTO items_to_redeem (item_name, value, code, picture)
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siss", $name, $points, $code, $imagePath);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Reward added successfully.';
        } else {
            $_SESSION['flash_error'] = 'Error adding reward: ' . $stmt->error;
        }

        header('Location: rewards.php');
        exit;
    }

    // ---- Delete reward ----
    if ($action === 'delete') {
        $id = (int)($_POST['item_id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid reward id.';
            header('Location: rewards.php');
            exit;
        }

        // نجيب الصورة عشان نحذفها من المجلد
        $sel = $conn->prepare("SELECT picture FROM items_to_redeem WHERE item_id = ? LIMIT 1");
        $sel->bind_param("i", $id);
        $sel->execute();
        $res = $sel->get_result();
        if ($row = $res->fetch_assoc()) {
            $pic = $row['picture'];
            if (!empty($pic) && file_exists('../' . $pic)) {
                @unlink('../' . $pic);
            }
        }

        $del = $conn->prepare("DELETE FROM items_to_redeem WHERE item_id = ?");
        $del->bind_param("i", $id);

        if ($del->execute()) {
            $_SESSION['flash_success'] = 'Reward deleted successfully.';
        } else {
            $_SESSION['flash_error'] = 'Error deleting reward: ' . $del->error;
        }

        header('Location: rewards.php');
        exit;
    }
}

// ===== Fetch rewards from DB =====
$rewards = [];
$res = $conn->query("SELECT * FROM items_to_redeem ORDER BY item_id DESC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $rewards[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UniHive — Rewards management</title>

  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --sidebarWidth:240px;
      --navy:#242751;
      --royal:#4871db;
      --coral:#ff5e5e;
      --gold:#e5b758;
      --paper:#eef2f7;
      --card:#ffffff;
      --ink:#0e1228;
      --muted:#6b7280;
      --shadow:0 20px 46px rgba(15,23,42,.18);
      --radius-lg:26px;
    }

    *{
      box-sizing:border-box;
    }

    body{
      margin:0;
      font-family:"Raleway",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:var(--paper);
      color:var(--ink);
    }

    .admin-layout{
      display:flex;
      min-height:100vh;
    }

    .admin-main{
      margin-left:var(--sidebarWidth);
      padding:32px 24px;
      width: calc(100% - var(--sidebarWidth));
      min-height:100vh;
      background:radial-gradient(circle at top left,#f4f7ff 0,#eef2f7 55%,#e4e7f3 100%);
    }

    .page-header{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom:24px;
    }

    .page-title{
      font-size:1.8rem;
      font-weight:800;
      color:var(--navy);
      letter-spacing:.02em;
      margin:0 0 4px;
    }

    .page-subtitle{
      margin:0;
      font-size:.95rem;
      color:var(--muted);
    }

    .card{
      background:var(--card);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow);
      padding:22px 24px;
      margin-bottom:24px;
    }

    .card-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:16px;
    }

    .card-title{
      font-size:1.1rem;
      font-weight:700;
      color:var(--navy);
      margin:0;
    }

    .card-subtitle{
      margin:2px 0 0;
      font-size:.86rem;
      color:var(--muted);
    }

    .form-grid{
      display:grid;
      grid-template-columns:2fr 1fr;
      gap:16px 20px;
    }

    .form-group{
      display:flex;
      flex-direction:column;
      gap:6px;
      font-size:.9rem;
    }

    .form-group label{
      font-weight:600;
      color:var(--navy);
    }

    .form-group small{
      font-size:.8rem;
      color:var(--muted);
    }

    .input-text,
    .input-number{
      border-radius:999px;
      border:1px solid #d1d5db;
      padding:9px 14px;
      font-family:inherit;
      font-size:.9rem;
      outline:none;
      background:#f9fafb;
      transition:.16s ease border,.16s ease box-shadow,.16s ease background;
    }

    .input-text:focus,
    .input-number:focus{
      border-color:var(--royal);
      box-shadow:0 0 0 1px rgba(72,113,219,.3);
      background:#ffffff;
    }

    .input-file{
      border-radius:999px;
      border:1px dashed #d1d5db;
      padding:7px 14px;
      font-size:.85rem;
      background:#f9fafb;
      cursor:pointer;
    }

    .btn{
      border:none;
      outline:none;
      font-family:inherit;
      font-size:.9rem;
      font-weight:600;
      border-radius:999px;
      padding:9px 18px;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:6px;
      transition:.16s ease transform,.16s ease box-shadow,.16s ease background;
      text-decoration:none;
    }

    .btn-primary{
      background:linear-gradient(135deg,#ff7b7b,#ff5e5e);
      color:#fff7f8;
      box-shadow:0 12px 30px rgba(248,113,113,.45);
    }

    .btn-primary:hover{
      transform:translateY(-1px);
      box-shadow:0 16px 40px rgba(248,113,113,.65);
    }

    .btn-ghost{
      background:transparent;
      color:var(--royal);
    }

    .btn-ghost:hover{
      background:rgba(72,113,219,.08);
    }

    .btn-danger{
      border:1px solid rgba(248,113,113,.7);
      color:#b91c1c;
      background:#fff7f7;
      padding:7px 12px;
      font-size:.8rem;
    }

    .btn-danger:hover{
      background:#fee2e2;
    }

    .table-wrapper{
      overflow-x:auto;
    }

    table{
      width:100%;
      border-collapse:collapse;
      font-size:.9rem;
    }

    thead{
      background:#f7f8fd;
    }

    th,
    td{
      padding:10px 12px;
      text-align:left;
      white-space:nowrap;
    }

    th{
      font-size:.78rem;
      text-transform:uppercase;
      letter-spacing:.06em;
      color:#6b7280;
      border-bottom:1px solid #e5e7eb;
    }

    tbody tr{
      background:#ffffff;
      border-bottom:1px solid #edf0f5;
      transition:.12s ease background,.12s ease transform;
    }

    tbody tr:hover{
      background:#f9fafb;
      transform:translateY(-1px);
    }

    .reward-row{
      display:flex;
      align-items:center;
      gap:10px;
    }

    .reward-img{
      width:44px;
      height:44px;
      border-radius:18px;
      object-fit:cover;
      background:#f3f4f6;
      border:1px solid #e5e7eb;
    }

    .reward-name{
      font-weight:600;
      color:var(--navy);
      margin-bottom:2px;
    }

    .reward-meta{
      font-size:.8rem;
      color:var(--muted);
    }

    .points-chip{
      display:inline-flex;
      align-items:center;
      gap:4px;
      padding:4px 11px;
      border-radius:999px;
      background:#fff7e6;
      color:#854d0e;
      font-size:.8rem;
    }

    .code-chip{
      display:inline-flex;
      align-items:center;
      padding:4px 10px;
      border-radius:999px;
      background:#edf2ff;
      color:#1d3a8a;
      font-size:.8rem;
      letter-spacing:.06em;
    }

    .table-actions{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:6px;
    }

    .flash{
      margin-bottom:16px;
      padding:10px 14px;
      border-radius:12px;
      font-size:.9rem;
    }
    .flash.success{
      background:#dcfce7;
      color:#166534;
    }
    .flash.error{
      background:#fee2e2;
      color:#991b1b;
    }

    @media (max-width:900px){
      .admin-main{
        margin-left:0;
        padding:20px 16px 28px;
      }
      .form-grid{
        grid-template-columns:1fr;
      }
      .page-header{
        flex-direction:column;
        align-items:flex-start;
      }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <?php include 'sidebar.php'; ?>

  <main class="admin-main">

    <div class="page-header">
      <div>
        <h1 class="page-title">Rewards management</h1>
        <p class="page-subtitle">Create UniHive rewards and control how many points students need to redeem them.</p>
      </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash success">
        <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash error">
        <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
      </div>
    <?php endif; ?>

    <!-- Add Reward Card -->
    <section class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Add new reward</h2>
          <p class="card-subtitle">Name the reward, set the points cost, and (optionally) add an image.</p>
        </div>
      </div>

      <form id="rewardForm" action="rewards.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-grid">
          <div class="form-group">
            <label for="reward_name">Reward name</label>
            <input id="reward_name" name="reward_name" type="text" class="input-text"
                   placeholder="e.g. Coffee voucher" required>
          </div>

          <div class="form-group">
            <label for="points_cost">Points cost</label>
            <input id="points_cost" name="points_cost" type="number" min="1"
                   class="input-number" placeholder="e.g. 150" required>
          </div>

          <div class="form-group">
            <label for="reward_image">Reward image (optional)</label>
            <input id="reward_image" name="reward_image" type="file"
                   class="input-file" accept="image/*">
            <small>Images are stored under <code>assets/rewards/</code>.</small>
          </div>
        </div>

        <div style="margin-top:18px; display:flex; gap:10px; justify-content:flex-end;">
          <button type="reset" class="btn btn-ghost">Clear</button>
          <button type="submit" class="btn btn-primary">Save reward</button>
        </div>
      </form>
    </section>

    <!-- Rewards List Card -->
    <section class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">All rewards</h2>
          <p class="card-subtitle">
            Showing <?php echo count($rewards); ?> reward(s) from <code>items_to_redeem</code>.
          </p>
        </div>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Reward</th>
              <th>Points</th>
              <th>Code</th>
              <th>Created at</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rewards)): ?>
              <tr>
                <td colspan="5" style="text-align:center; padding:18px 10px; color:#6b7280;">
                  No rewards yet. Use the form above to add one.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rewards as $reward): ?>
                <tr>
                  <!-- Reward cell (image + name + ID) -->
                  <td>
                    <div class="reward-row">
                      <?php if (!empty($reward['picture'])): ?>
                        <img src="../<?php echo htmlspecialchars($reward['picture']); ?>"
                             alt="" class="reward-img">
                      <?php else: ?>
                        <div class="reward-img"></div>
                      <?php endif; ?>

                      <div>
                        <div class="reward-name">
                          <?php echo htmlspecialchars($reward['item_name']); ?>
                        </div>
                        <div class="reward-meta">
                          ID #<?php echo (int)$reward['item_id']; ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Points -->
                  <td>
                    <span class="points-chip">
                      <?php echo (int)$reward['value']; ?> pts
                    </span>
                  </td>

                  <!-- Code -->
                  <td>
                    <span class="code-chip">
                      <?php echo htmlspecialchars($reward['code']); ?>
                    </span>
                  </td>

                  <!-- Created at (no column in DB, so just “—”) -->
                  <td>
  <span class="reward-meta">
    <?php echo htmlspecialchars($reward['created_at']); ?>
  </span>
</td>


                  <!-- Actions -->
                  <td>
                    <div class="table-actions">
                      <form action="rewards.php" method="post"
                            onsubmit="return confirm('Delete reward &quot;<?php echo htmlspecialchars($reward['item_name']); ?>&quot;?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="item_id" value="<?php echo (int)$reward['item_id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>
</div>

</body>
</html>

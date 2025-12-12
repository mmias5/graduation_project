<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'club_president') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        header('Location: ../president/index.php');
        exit;
    }
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$studentId      = (int)$_SESSION['student_id'];
$redeemError    = '';
$redeemSuccess  = '';

/* ========= helpers ========= */

function escapeH(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* مجموع النقاط من جدول student.total_points */
function getStudentPointsFromStudent(mysqli $conn, int $studentId): int {
    $sql = "SELECT COALESCE(total_points,0) AS total_points FROM student WHERE student_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total_points'] ?? 0);
}

/* تحديث total_points بعد الـ redeem */
function updateStudentPoints(mysqli $conn, int $studentId, int $cost): bool {
    // ننقص النقاط فقط إذا عنده نقاط كافية (شرط في الـ WHERE كمان)
    $sql = "UPDATE student SET total_points = total_points - ? WHERE student_id = ? AND total_points >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $cost, $studentId, $cost);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

/* ========= handle redeem POST ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_submit'])) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $redeemError = 'Database connection error.';
    } else {
        $itemId = isset($_POST['redeem_item_id']) ? (int)$_POST['redeem_item_id'] : 0;
        $code   = trim($_POST['redeem_code'] ?? '');

        if ($itemId <= 0 || $code === '') {
            $redeemError = 'Please choose a reward and enter its code.';
        } else {
            // جلب الـ reward من DB
            $sql = "SELECT item_id, item_name, value, code, picture FROM items_to_redeem WHERE item_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$item) {
                $redeemError = 'Reward not found.';
            } elseif (strcasecmp($code, $item['code']) !== 0) {
                $redeemError = 'Invalid code for this reward.';
            } else {
                $costPoints    = (int)$item['value'];
                $currentPoints = getStudentPointsFromStudent($conn, $studentId);

                if ($costPoints <= 0) {
                    $redeemError = 'This reward is not configured correctly.';
                } elseif ($currentPoints < $costPoints) {
                    $redeemError = 'You do not have enough points to redeem this reward.';
                } else {
                    // نحاول ننقص النقاط من جدول student
                    if (updateStudentPoints($conn, $studentId, $costPoints)) {
                        $redeemSuccess = 'Reward redeemed successfully!';
                    } else {
                        // حالة safety لو الـ UPDATE فشل بسبب شرط total_points
                        $redeemError = 'Could not update your points. Please try again.';
                    }
                }
            }
        }
    }
}

/* ========= fetch current points & rewards list ========= */

$currentPoints = isset($conn) && $conn instanceof mysqli
    ? getStudentPointsFromStudent($conn, $studentId)
    : 0;

// target للـ progress bar (مثلاً 250 نقطة)
$progressTarget = 250;
$progressRatio  = $progressTarget > 0 ? min(1, $currentPoints / $progressTarget) : 0.0;

$rewards = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql = "SELECT item_id, item_name, value, code, picture FROM items_to_redeem ORDER BY value ASC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rewards[] = $row;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>UniHive — Rewards</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --c-navy:   #2B3751;
    --c-blue:   #4871DB;
    --c-yellow: #F6E578;
    --c-red:    #FF5C5E;
    --c-ice:    #E9ECEF;

    --radius-xl: 20px;
    --shadow: 0 10px 28px rgba(16,24,40,.10);
    --shadow-soft: 0 6px 18px rgba(16,24,40,.08);
  }

  @font-face{
    font-family: "Extenda 90 Exa";
    src: url("assets/fonts/Extenda90Exa.woff2") format("woff2"),
         url("assets/fonts/Extenda90Exa.woff") format("woff");
    font-weight: 700; font-style: normal; font-display: swap;
  }

  body{
    margin:0;
    background:
      radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
      radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%),
      #eef2f7;
    font-family: "Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    color: var(--c-navy);
  }

  .loyalty, .loyalty *{ box-sizing: border-box; }
  .loyalty{
    background: inherit;
    margin-top: -1px;
    padding: 24px 0 56px;
  }
  .loyalty .container{ width:min(1120px, 92%); margin-inline:auto; }

  .lp-headline{ text-align:center; margin-bottom: 18px; }
  .lp-headline h2{
    margin:0 0 8px;
    font-family: "Extenda 90 Exa","Raleway",sans-serif;
    font-weight:700;
    font-size: clamp(22px, 5vw, 34px);
    letter-spacing:.2px;
    color: var(--c-navy);
  }
  .lp-total{ font-weight:800; font-size: 16px; opacity:.85; margin-bottom: 12px; }

  .loyalty-meter{
    --progress: .35;
    --h: 38px;
    position: relative;
    margin: 0 auto 28px;
    width: min(620px, 92%);
    height: var(--h);
    border-radius: calc(var(--h) / 2);
    background: linear-gradient(90deg, #E0E6F7 0%, #D4DCF1 100%);
    box-shadow: inset 0 2px 8px rgba(30,42,76,.12);
    overflow: hidden;
  }
  .loyalty-meter .fill{
    position:absolute; inset:0;
    width: calc(var(--progress) * 100%);
    border-radius: inherit;
    background: linear-gradient(90deg, var(--c-blue) 0%, var(--c-yellow) 100%);
  }

  .flash{
    width:min(1120px,92%);
    margin:0 auto 10px;
    font-size:14px;
    padding:10px 14px;
    border-radius:12px;
    box-shadow:var(--shadow-soft);
  }
  .flash.error{
    background:#ffe5e8;
    color:#7f1d1d;
    border:1px solid #fecaca;
  }
  .flash.success{
    background:#e7f8ea;
    color:#14532d;
    border:1px solid #bbf7d0;
  }

  .rewards{ display:grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }

  .reward-card{
    background:#fff; border-radius: var(--radius-xl); overflow:hidden;
    box-shadow: var(--shadow);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .reward-card:hover{ transform: translateY(-2px); box-shadow: 0 14px 34px rgba(16,24,40,.12); }

  .rc-photo{
    position:relative;
    width:100%;
    aspect-ratio: 16/9;
    background:#f2f4f8;
    overflow:hidden;
  }
  .rc-photo img{
    width:100%; height:100%; object-fit:cover; display:block;
    transform:scale(1.02);
  }

  .rc-body{ background: linear-gradient(180deg, #ffffff 0%, #F5F7FB 100%); padding: 14px 16px 16px; }
  .rc-title{ font-weight:800; color:var(--c-navy); margin:0 0 6px; line-height:1.2; }
  .rc-sub{ margin:0 0 12px; font-weight:700; color:#65708A; }

  .rc-cta{
    display:inline-flex; align-items:center; justify-content:center;
    padding:10px 16px; border-radius:12px; border:none; cursor:pointer;
    font-weight:700; background: #F6E578;
    color: var(--c-navy); box-shadow: inset 0 -2px 0 rgba(0,0,0,.06);
  }
  .rc-cta:hover{ filter:saturate(1.05);background:#4871DB; color:#fff; }
  .rc-cta:active{ transform: translateY(1px); }

  .modal-backdrop{
    position: fixed; inset: 0; display:none;
    background: rgba(10, 16, 28, .5);
    z-index: 1000;
  }
  .modal{
    position: fixed; inset:0; display:none; place-items:center; z-index:1001;
    padding: 20px;
  }
  .modal.active, .modal-backdrop.active{ display:grid; }
  .modal-card{
    width: min(440px, 94%);
    background: #fff; border-radius: 16px; box-shadow: var(--shadow);
    overflow:hidden;
  }
  .modal-head{
    background: var(--c-blue);
    color:#fff; padding:14px 18px; font-weight:800;
    font-family:"Extenda 90 Exa","Raleway",sans-serif;
  }
  .modal-body{ padding:18px; }
  .modal-body p{ margin:0 0 12px; color:#2b3751; font-weight:600; }
  .form-row{ display:grid; gap:10px; }
  .form-row input{
    height: 44px; border-radius: 10px; border: 2px solid #E5E9F5; outline: none;
    padding: 0 12px; font-size: 15px;
  }
  .form-actions{ margin-top: 12px; display:flex; gap:10px; justify-content:flex-end; }
  .btn{
    height: 42px; padding: 0 16px; border-radius: 12px; border:none; cursor:pointer; font-weight:800;
  }
  .btn-cancel{ background:#EEF2FA; color:#1f2a45; }
  .btn-submit{
    background: var(--c-yellow);
    color: var(--c-navy);
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="loyalty" aria-labelledby="loyalty-heading">
  <div class="container">

    <?php if ($redeemError): ?>
      <div class="flash error"><?php echo escapeH($redeemError); ?></div>
    <?php elseif ($redeemSuccess): ?>
      <div class="flash success"><?php echo escapeH($redeemSuccess); ?></div>
    <?php endif; ?>

    <div class="lp-headline">
      <h2 id="loyalty-heading">Your Loyalty points!</h2>
      <div class="lp-total"><?php echo (int)$currentPoints; ?> pts</div>
    </div>

    <div class="loyalty-meter" style="--progress:<?php echo escapeH(number_format($progressRatio,3,'.','')); ?>" aria-label="Loyalty progress">
      <div class="fill"></div>
    </div>

    <!-- Rewards grid -->
    <div class="rewards">
      <?php foreach ($rewards as $r): ?>
        <?php $cost = (int)$r['value']; ?>
        <article class="reward-card">
          <div class="rc-photo">
            <img src="<?php echo escapeH($r['picture']); ?>" alt="<?php echo escapeH($r['item_name']); ?>" loading="lazy">
          </div>
          <div class="rc-body">
            <h3 class="rc-title"><?php echo escapeH($r['item_name']); ?></h3>
            <p class="rc-sub"><?php echo $cost; ?> Points</p>
            <button
              class="rc-cta redeem-btn"
              type="button"
              data-reward="<?php echo escapeH($r['item_name']); ?>"
              data-item-id="<?php echo (int)$r['item_id']; ?>"
            >
              Redeem
            </button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Modal + Backdrop -->
<div class="modal-backdrop" id="redeemBackdrop"></div>
<div class="modal" id="redeemModal" role="dialog" aria-modal="true" aria-labelledby="redeemTitle">
  <form class="modal-card" method="post">
    <div class="modal-head" id="redeemTitle">Redeem Reward</div>
    <div class="modal-body">
      <p id="rewardName">Enter the code for: <strong>—</strong></p>

      <input type="hidden" name="redeem_item_id" id="redeem_item_id" value="">
      <input type="hidden" name="redeem_submit" value="1">

      <div class="form-row">
        <input id="redeemCode" name="redeem_code" type="text" placeholder="Enter your code" autocomplete="one-time-code" />
      </div>
      <div class="form-actions">
        <button class="btn btn-cancel" id="btnCancel" type="button">Cancel</button>
        <button class="btn btn-submit" id="btnSubmit" type="submit">Submit</button>
      </div>
    </div>
  </form>
</div>

<script>
  const modal = document.getElementById('redeemModal');
  const backdrop = document.getElementById('redeemBackdrop');
  const rewardNameEl = document.getElementById('rewardName').querySelector('strong');
  const codeInput = document.getElementById('redeemCode');
  const itemInput = document.getElementById('redeem_item_id');

  function openModal(name, itemId){
    rewardNameEl.textContent = name;
    codeInput.value = '';
    itemInput.value = itemId || '';
    modal.classList.add('active');
    backdrop.classList.add('active');
    setTimeout(() => codeInput.focus(), 0);
  }
  function closeModal(){
    modal.classList.remove('active');
    backdrop.classList.remove('active');
  }

  document.querySelectorAll('.redeem-btn').forEach(btn=>{
    btn.addEventListener('click', () => {
      openModal(btn.dataset.reward || 'Selected Reward', btn.dataset.itemId || '');
    });
  });

  document.getElementById('btnCancel').addEventListener('click', closeModal);
  backdrop.addEventListener('click', closeModal);
  window.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });
</script>

<?php include 'footer.php'; ?>
</body>
</html>

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
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>CCH — Header + Sidebar + Hover Dropdowns</title>

<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<?php include('header.php'); ?>
<!-- ====== START: Loyalty Points (flush under header + modal) ====== -->
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

  /* Optional Extenda (replace with your files if you have them) */
  @font-face{
    font-family: "Extenda 90 Exa";
    src: url("assets/fonts/Extenda90Exa.woff2") format("woff2"),
         url("assets/fonts/Extenda90Exa.woff") format("woff");
    font-weight: 700; font-style: normal; font-display: swap;
  }
  @import url('https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap');

  /* Page background matches section for seamless look */
  body{
    margin:0;
    background: radial-gradient(1200px 800px at -10% 50%, rgba(168,186,240,.35), transparent 60%),
    radial-gradient(900px 700px at 110% 60%, rgba(72,113,219,.20), transparent 60%)
    font-family: "Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    color: var(--c-navy);
  }

  .loyalty, .loyalty *{ box-sizing: border-box; }
  .loyalty{
    background: inherit;
    margin-top: -1px; /* hides any seam under the header */
    padding: 24px 0 56px;
  }
  .loyalty .container{ width:min(1120px, 92%); margin-inline:auto; }

  /* Headline & total points */
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

  /* Progress meter */
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

  /* Rewards grid */
  .rewards{ display:grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }

  .reward-card{
    background:#fff; border-radius: var(--radius-xl); overflow:hidden;
    box-shadow: var(--shadow);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .reward-card:hover{ transform: translateY(-2px); box-shadow: 0 14px 34px rgba(16,24,40,.12); }

  /* ===== NEW: photo at the top instead of pink box ===== */
  .rc-photo{
    position:relative;
    width:100%;
    aspect-ratio: 16/9;         /* consistent height for all cards */
    background:#f2f4f8;          /* fallback color while image loads */
    overflow:hidden;
  }
  .rc-photo img{
    width:100%; height:100%; object-fit:cover; display:block;
    transform:scale(1.02);       /* tiny zoom to avoid white edges */
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

  /* Modal */
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

<section class="loyalty" aria-labelledby="loyalty-heading">
  <div class="container">
    <div class="lp-headline">
      <h2 id="loyalty-heading">Your Loyalty points!</h2>
      <div class="lp-total">250 pts</div>
    </div>

    <div class="loyalty-meter" style="--progress:.35" aria-label="Loyalty progress">
      <div class="fill"></div>
    </div>

    <!-- Rewards grid -->
    <div class="rewards">
      <!-- Replace the src values with your real images later -->
      <article class="reward-card">
        <div class="rc-photo">
          <img src="https://iq.maroufcoffee.com/wp-content/uploads/2025/02/3.png" alt="Coffee Voucher image" loading="lazy">
        </div>
        <div class="rc-body">
          <h3 class="rc-title">Coffee Voucher</h3>
          <p class="rc-sub">70 Points</p>
          <button class="rc-cta redeem-btn" data-reward="Coffee Voucher">Redeem</button>
        </div>
      </article>

      <article class="reward-card">
        <div class="rc-photo">
          <img src="https://hips.hearstapps.com/hmg-prod/images/bacon-wrapped-stuffed-dates-recipe-10-jpg-65aebfcccad46.jpeg?crop=1.00xw:0.834xh;0,0.0608xh&resize=980:*" alt="Snack Voucher image" loading="lazy">
        </div>
        <div class="rc-body">
          <h3 class="rc-title">Snack Voucher</h3>
          <p class="rc-sub">100 Points</p>
          <button class="rc-cta redeem-btn" data-reward="Coffee Voucher">Redeem</button>
        </div>
      </article>

      <article class="reward-card">
        <div class="rc-photo">
          <img src="https://jobedu.com/cdn/shop/products/wtf_maroon_1024x1024.png?v=1680803300" alt="Merch image" loading="lazy">
        </div>
        <div class="rc-body">
          <h3 class="rc-title">Merch Voucher</h3>
          <p class="rc-sub">115 Points</p>
          <button class="rc-cta redeem-btn" data-reward="Coffee Voucher">Redeem</button>
        </div>
      </article>

      <article class="reward-card">
        <div class="rc-photo">
          <img src="https://i0.wp.com/www.touristjordan.com/wp-content/uploads/2018/11/shutterstock_1626734503-scaled.jpg?fit=500%2C333&ssl=1" alt="Event ticket image" loading="lazy">
        </div>
        <div class="rc-body">
          <h3 class="rc-title">Event Voucher</h3>
          <p class="rc-sub">250 Points</p>
          <button class="rc-cta redeem-btn" data-reward="Coffee Voucher">Redeem</button>
        </div>
      </article>

      <article class="reward-card">
        <div class="rc-photo">
          <img src="https://freem.co.uk/cdn/shop/products/Golden-Gift-Card.jpg?v=1671447700" alt="Gift card image" loading="lazy">
        </div>
        <div class="rc-body">
          <h3 class="rc-title">Gift Card</h3>
          <p class="rc-sub">100 Points</p>
          <button class="rc-cta redeem-btn" data-reward="Coffee Voucher">Redeem</button>
        </div>
      </article>

      <article class="reward-card">
        <div class="rc-photo">
          <img src="https://blog.cdphp.com/wp-content/uploads/2023/09/01-Header-scaled.jpg" alt="Meal voucher image" loading="lazy">
        </div>
        <div class="rc-body">
          <h3 class="rc-title">Meal Voucher</h3>
          <p class="rc-sub">100 Points</p>
          <button class="rc-cta redeem-btn" data-reward="Coffee Voucher">Redeem</button>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- Modal + Backdrop -->
<div class="modal-backdrop" id="redeemBackdrop"></div>
<div class="modal" id="redeemModal" role="dialog" aria-modal="true" aria-labelledby="redeemTitle">
  <div class="modal-card">
    <div class="modal-head" id="redeemTitle">Redeem Reward</div>
    <div class="modal-body">
      <p id="rewardName">Enter the code for: <strong>—</strong></p>
      <div class="form-row">
        <input id="redeemCode" type="text" placeholder="Enter your code" autocomplete="one-time-code" />
      </div>
      <div class="form-actions">
        <button class="btn btn-cancel" id="btnCancel" type="button">Cancel</button>
        <button class="btn btn-submit" id="btnSubmit" type="button">Submit</button>
      </div>
    </div>
  </div>
</div>

<script>
  // ===== Redeem modal logic =====
  const modal = document.getElementById('redeemModal');
  const backdrop = document.getElementById('redeemBackdrop');
  const rewardName = document.getElementById('rewardName').querySelector('strong');
  const codeInput = document.getElementById('redeemCode');

  function openModal(name){
    rewardName.textContent = name;
    codeInput.value = '';
    modal.classList.add('active');
    backdrop.classList.add('active');
    setTimeout(() => codeInput.focus(), 0);
  }
  function closeModal(){
    modal.classList.remove('active');
    backdrop.classList.remove('active');
  }

  document.querySelectorAll('.redeem-btn').forEach(btn=>{
    btn.addEventListener('click', () => openModal(btn.dataset.reward || 'Selected Reward'));
  });
  document.getElementById('btnCancel').addEventListener('click', closeModal);
  backdrop.addEventListener('click', closeModal);
  window.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });

  // Demo submit handler — replace with your real request
  document.getElementById('btnSubmit').addEventListener('click', () => {
    const code = codeInput.value.trim();
    if(!code){ alert('Please enter a code.'); return; }
    // TODO: send code to backend via fetch()
    alert('Submitted code: ' + code);
    closeModal();
  });
</script>
<!-- ====== END: Loyalty Points ====== -->
<?php include('footer.php'); ?>
</body>
</html>

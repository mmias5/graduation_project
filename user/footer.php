<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
  /* ==== Brand Tokens (match your header) ==== */
.cch-footer{
  --navy: #242751;
  --royal: #4871db;
  --lightBlue: #f4df6d;
  --gold: #f4df6d;
  --sun: #f4df6d;
  --coral: #f4df6d;
  --ink: #ffffff;  /* now ONLY the footer and its children see ink=white */
}


  .cch-footer{
    background:var(--royal);
    color:var(--ink);
    font-family:"Raleway", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    border-top:4px solid #e9ecef1f;
  }
  .cch-footer .wrap{
    max-width:1200px;
    margin-inline:auto;
    padding:56px 24px;
    display:grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap:40px;
    align-items:start;
  }

  /* left column */
  .cch-brand{
    display:flex;
    flex-direction:column;
    gap:22px;
  }
  .cch-logo{
    width:min(260px, 60%);
    max-width:100%;
    height:auto;
    object-fit:contain;
    filter: drop-shadow(0 10px 30px rgba(0,0,0,.15));
  }
  .cch-title{
    font-weight:800;
    font-size: clamp(36px, 5vw, 64px);
    line-height:1.05;
    margin:0;
    letter-spacing:.5px;
  }

  /* right column */
  .cch-contact h3{
    margin:0 0 14px;
    font-size:18px;
    font-weight:800;
    letter-spacing:.6px;
    opacity:.9;
  }

  .cch-social{
    display:flex;
    gap:12px;
    margin: 10px 0 18px;
  }
  .cch-social__btn{
    display:inline-grid;
    place-items:center;
    width:40px; height:40px;
    border-radius:12px;
    border:2px solid var(--lightBlue);
    color:var(--lightBlue);
    background:transparent;
    text-decoration:none;
    transition: transform .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
  }
  .cch-social__btn:hover{
    background: #f7f9ff10;
    color:var(--ink);
    border-color:var(--ink);
    transform: translateY(-10px);
  }

  .cch-list{
    margin:0; padding:0;
    list-style:none;
    display:grid;
    gap:10px;
  }
  .cch-list li{
    display:flex; align-items:center; gap:10px;
  }
  .cch-dot{
    width:7px; height:7px; border-radius:50%;
    background:var(--gold);
    box-shadow:0 0 0 2px rgba(255,255,255,.08) inset;
    flex:0 0 auto;
  }
  .cch-link{
    color:#e8ecff;
    text-decoration:none;
    border-bottom:1px dashed transparent;
    transition: color .15s ease, border-color .15s ease;
  }
  .cch-link:hover{
    color:var(--sun);
    border-color:var(--sun);
  }

  /* bottom strip */
  .cch-bottom{
    border-top:1px solid rgba(255,255,255,.08);
    margin-top:28px;
    padding-top:16px;
    font-size:13px;
    opacity:.8;
  }

  /* responsive */
  @media (max-width: 860px){
    .cch-footer .wrap{
      grid-template-columns: 1fr;
      text-align:left;
      gap:28px;
      padding:40px 18px;
    }
    .cch-logo{ width:220px; }
  }
</style>

<footer class="cch-footer" role="contentinfo">
  <div class="wrap">
    <!-- Left: Brand / About -->
    <div class="cch-brand">
      <!-- Replace with your real image path -->
      <img class="cch-logo" src="tools/pics/jd.png" alt="Campus Clubs Hub logo" />
      <h2 class="cch-title">About US</h2>
    </div>

    <!-- Right: Social + Contact -->
    <div class="cch-contact">
      <div class="cch-social" aria-label="Social links">
        <!-- Replace # with your actual links -->
        <a class="cch-social__btn" href="#" target="_blank" rel="noopener" aria-label="Facebook">
          <!-- FB icon -->
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path fill="currentColor" d="M22 12.06C22 6.49 17.52 2 11.95 2S2 6.49 2 12.06c0 5.01 3.66 9.17 8.44 9.94v-7.03H7.9v-2.91h2.54V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.77l-.44 2.91h-2.33V22c4.78-.77 8.44-4.93 8.44-9.94Z"/>
          </svg>
        </a>

        <a class="cch-social__btn" href="#" target="_blank" rel="noopener" aria-label="LinkedIn">
          <!-- LinkedIn icon -->
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path fill="currentColor" d="M6.94 20.45H3.37V9.02h3.57v11.43ZM5.14 7.54a2.06 2.06 0 1 1 0-4.11 2.06 2.06 0 0 1 0 4.11ZM21 20.45h-3.56v-5.61c0-1.34-.03-3.06-1.87-3.06-1.87 0-2.16 1.46-2.16 2.96v5.71H9.84V9.02h3.41v1.56h.05c.47-.9 1.63-1.87 3.37-1.87 3.62 0 4.33 2.38 4.33 5.47v6.27Z"/>
          </svg>
        </a>

        <a class="cch-social__btn" href="#" target="_blank" rel="noopener" aria-label="Instagram">
          <!-- Instagram icon -->
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" style="color:##f4df6d">
    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"
          fill="none" stroke="currentColor" stroke-width="2"/>
    <circle cx="12" cy="12" r="4.5"
            fill="none" stroke="currentColor" stroke-width="2"/>
    <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
  </svg>
        </a>
      </div>

      <h3>Contact Info</h3>
      <ul class="cch-list">
        <li>
          <span class="cch-dot"></span>
          <a class="cch-link" href="tel:+96265355000">Call Us: +962 6 5355000</a>
        </li>
        <li>
          <span class="cch-dot"></span>
          <a class="cch-link" href="mailto:Admin@ju.edu.jo">Admin@ju.edu.jo</a>
        </li>
        <li>
          <span class="cch-dot"></span>
          <a class="cch-link" href="#" target="_blank" rel="noopener">Aljubeiha, Amman, Jordan</a>
        </li>
      </ul>

      <div class="cch-bottom">
        © <span id="cchYear"></span> UNIHIVE. All rights reserved.
      </div>
    </div>
  </div>
</footer>

<script>
  // year helper (optional)
  document.getElementById('cchYear').textContent = new Date().getFullYear();
</script>

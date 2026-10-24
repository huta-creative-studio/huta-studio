<div id="login-modal" class="hv-login-modal" role="dialog" aria-modal="true" aria-labelledby="hv-login-title" hidden>
    <div class="hv-login-overlay" data-close-login></div>
    <div class="hv-login-box">
        <button type="button" class="hv-login-close" data-close-login aria-label="Tutup"><i class="fas fa-times"></i></button>
        <div class="hv-login-header">
            <span class="hv-login-icon"><i class="fas fa-fingerprint"></i></span>
            <h3 id="hv-login-title" class="hv-login-title">Login</h3>
            <p class="hv-login-sub">Pilih portal masuk sesuai dengan hak akses Anda.</p>
        </div>
        <div class="hv-login-grid">
            <a href="{{ site_url('siteman') }}" class="hv-login-card">
                <span class="hv-login-card-icon"><i class="fas fa-shield-alt"></i></span>
                <span class="hv-login-card-text">
                    <span class="title">Administrator</span>
                    <span class="desc">Akses pengelola admin website</span>
                </span>
                <span class="hv-login-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="{{ site_url('layanan-mandiri/masuk') }}" class="hv-login-card">
                <span class="hv-login-card-icon"><i class="fas fa-id-card"></i></span>
                <span class="hv-login-card-text">
                    <span class="title">Layanan Warga</span>
                    <span class="desc">Akses layanan mandiri warga</span>
                </span>
                <span class="hv-login-card-arrow"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
</div>
<style>
.hv-login-modal { position: fixed; inset: 0; z-index: 10800; display: flex; align-items: center; justify-content: center;
  opacity: 0; visibility: hidden; transition: opacity .35s ease, visibility .35s ease; }
.hv-login-modal.open { opacity: 1; visibility: visible; }
.hv-login-overlay { position: absolute; inset: 0; background: rgba(5, 37, 22, 0.55); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
.hv-login-box { position: relative; width: 90%; max-width: 420px; padding: 2.25rem 1.75rem; border-radius: 1.75rem;
  background: var(--hv-card); border: 1px solid var(--hv-border); box-shadow: var(--hv-shadow-lg);
  transform: translateY(24px) scale(.96); transition: transform .4s cubic-bezier(.34,1.56,.64,1); }
.hv-login-modal.open .hv-login-box { transform: translateY(0) scale(1); }
.hv-login-close { position: absolute; top: 1rem; right: 1rem; width: 36px; height: 36px; border: none; border-radius: 999px;
  background: color-mix(in srgb, var(--hv-text) 10%, transparent); color: var(--hv-text); cursor: pointer; transition: transform .3s ease; }
.hv-login-close:hover { transform: rotate(90deg) scale(1.08); }
.hv-login-header { text-align: center; margin-bottom: 1.75rem; }
.hv-login-icon { display: flex; align-items: center; justify-content: center; width: 64px; height: 64px; margin: 0 auto 1rem;
  border-radius: 18px; font-size: 1.8rem; color: #fff;
  background: linear-gradient(135deg, var(--hv-primary), var(--hv-primary-dark));
  box-shadow: 0 10px 25px -5px color-mix(in srgb, var(--hv-primary) 45%, transparent); }
.hv-login-title { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--hv-text-strong); }
.hv-login-sub { margin: .45rem 0 0; font-size: .9rem; color: var(--hv-text); }
.hv-login-grid { display: flex; flex-direction: column; gap: .8rem; }
.hv-login-card { display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.15rem;
  background: color-mix(in srgb, var(--hv-bg) 80%, var(--hv-card)); border: 1px solid var(--hv-border);
  color: var(--hv-text-strong); text-decoration: none; transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
.hv-login-card:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--hv-primary) 45%, transparent); box-shadow: var(--hv-shadow); }
.hv-login-card-icon { display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px;
  background: color-mix(in srgb, var(--hv-primary) 12%, transparent); color: var(--hv-primary); font-size: 1.15rem; flex: none; }
.hv-login-card-text { display: flex; flex-direction: column; min-width: 0; }
.hv-login-card-text .title { font-weight: 700; font-size: .95rem; }
.hv-login-card-text .desc { font-size: .78rem; color: var(--hv-text); }
.hv-login-card-arrow { margin-left: auto; color: var(--hv-text); font-size: .85rem; }
</style>
<script>
(function () {
  var modal = document.getElementById('login-modal');
  if (!modal) return;
  function open(e) { if (e) e.preventDefault(); modal.hidden = false; requestAnimationFrame(function () { modal.classList.add('open'); }); document.body.style.overflow = 'hidden'; }
  function close() { modal.classList.remove('open'); document.body.style.overflow = ''; setTimeout(function () { modal.hidden = true; }, 350); }
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-open-login]');
    if (t) { open(e); return; }
    if (e.target.closest('[data-close-login]')) close();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) close(); });
})();
</script>

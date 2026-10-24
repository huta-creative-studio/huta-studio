<!-- Loading screen Variant A — cincin ganda. Gaya terpusat di assets/css/huta-v2.css -->
<noscript><style>.hv-loading-modern{display:none!important}</style></noscript>
<div id="hv-loading-modern" class="hv-loading-modern" role="status" aria-label="Memuat halaman">
  <div class="hv-loading-modern-inner">
    <span class="hv-loading-modern-logo-wrap">
      <span class="hv-loading-modern-ring hv-loading-modern-ring-1"></span>
      <span class="hv-loading-modern-ring hv-loading-modern-ring-2"></span>
      @if (!empty($desa['logo']))
        <img src="{{ gambar_desa($desa['logo']) }}" alt="" class="hv-loading-modern-logo">
      @endif
    </span>
    <span class="hv-loading-modern-text">
      <span class="hv-loading-modern-title">{{ ucwords(setting('sebutan_desa')) }} {{ title_case($desa['nama_desa'] ?? '') }}</span>
      <span class="hv-loading-modern-progress"><span class="hv-loading-modern-progress-bar"></span></span>
      <span class="hv-loading-modern-sub">Memuat halaman<span class="hv-loading-modern-dots"></span></span>
    </span>
  </div>
</div>
<script>
(function(){
  var el = document.getElementById('hv-loading-modern');
  if (!el) return;
  function hide(){ if (!el.classList.contains('hidden')) el.classList.add('hidden'); }
  var failsafe = setTimeout(hide, 5000);          // pengaman: jangan pernah menyangkut
  function ready(){ clearTimeout(failsafe); setTimeout(hide, 1200); }
  if (document.readyState === 'complete') ready();
  else window.addEventListener('load', ready);
  document.addEventListener('DOMContentLoaded', function(){ setTimeout(hide, 4000); });
})();
</script>

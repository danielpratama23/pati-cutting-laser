<?php
define('PCL_ROOT', __DIR__);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/partials.php';

$S  = site();
$U  = arr($S, 'umum', array());
$K  = arr($S, 'kontak', array());
$H  = arr($S, 'hero', array());
$L  = arr($S, 'layanan', array());
$B  = arr($S, 'bahan', array());
$KR = arr($S, 'karya', array());
$KL = arr($S, 'klien', array());
$CP = arr($S, 'cara_pesan', array());
$AR = arr($S, 'artikel', array());
$KB = arr($S, 'kontak_bagian', array());

$order = wa_link(arr($K, 'pesan_order', ''));
$judul = trim(arr($H, 'judul', ''));
$laser = (strtolower(preg_replace('/\s+/', ' ', $judul)) === 'laser cutting');
$foto  = array_values(array_filter((array)arr($H, 'foto', array()), function ($f) { return trim(arr($f, 'gambar', '')) !== ''; }));
$kat   = (array)arr($KR, 'kategori', array());
$items = (array)arr($KR, 'item', array());
$used  = array();
foreach ($items as $it) $used[arr($it, 'kategori', '')] = true;
$latest = array_slice(all_articles(), 0, max(0, (int)arr($AR, 'tampil_di_beranda', 3)));

page_head(arr($U, 'judul_seo', arr($U, 'nama', '')), arr($U, 'deskripsi_seo', ''));
site_nav(true);
?>

<main>
<section class="hero" id="top">
  <div class="mat-grid" aria-hidden="true"></div>
  <div class="hero-glow" aria-hidden="true"></div>
  <div class="wrap hero-in">
    <div>
      <?php if ($laser): ?>
      <h1><span class="pre"><?= e(arr($H, 'pembuka', '')) ?></span><span class="sr"> <?= e($judul) ?></span><span class="ltw"><?php readfile(__DIR__ . '/inc/ltype.svg'); ?></span></h1>
      <?php else: ?>
      <h1><span class="pre"><?= e(arr($H, 'pembuka', '')) ?></span><span class="hplain"><?= e($judul) ?></span></h1>
      <?php endif; ?>
      <p class="lead"><?= e(arr($H, 'deskripsi', '')) ?></p>
      <div class="cta">
        <a class="btn btn-light mag" href="<?= e($order) ?>" target="_blank" rel="noopener"><?= icon('wa', 'i', true) ?><?= e(arr($H, 'tombol_utama', '')) ?></a>
        <a class="btn btn-ghost" href="#karya"><?= e(arr($H, 'tombol_kedua', '')) ?><svg class="i dn" aria-hidden="true"><use href="#i-arrow-down"/></svg></a>
      </div>
      <?php if (trim(arr($H, 'bukti', '')) !== ''): ?>
      <p class="trust"><?= icon('check') ?><span><?= e(arr($H, 'bukti', '')) ?></span></p>
      <?php endif; ?>
    </div>
    <div class="stack">
      <?php foreach (array_slice($foto, 0, 3) as $i => $f): ?>
      <div class="shot s<?= $i + 1 ?>" style="--k:<?= $i ?>"><div class="fl"><img src="<?= e(arr($f, 'gambar', '')) ?>" alt="<?= e(arr($f, 'alt', arr($f, 'keterangan', ''))) ?>" width="900" height="900"><?php if (trim(arr($f, 'keterangan', '')) !== '' && $i < 2): ?><span class="cap"><?= e(arr($f, 'keterangan', '')) ?></span><?php endif; ?></div></div>
      <?php endforeach; ?>
      <div class="shot seal" style="--k:3"><img src="<?= e(arr($U, 'logo', '/img/logo.png')) ?>" alt="Logo <?= e(arr($U, 'nama', '')) ?>" width="240" height="240"></div>
    </div>
  </div>
  <?php $words = array_values(array_filter((array)arr($H, 'teks_berjalan', array()), 'strlen')); if ($words): ?>
  <div class="marquee" aria-hidden="true"><div class="mq-track"><?php
    $one = '';
    foreach ($words as $w) $one .= '<span>' . e($w) . '</span><svg class="mq-s" viewBox="0 0 24 24"><use href="#i-spark"/></svg>';
    $set = '<div class="mq-set">' . $one . $one . '</div>';
    echo $set . $set;
  ?></div></div>
  <?php endif; ?>
</section>

<section class="sec" id="layanan">
  <div class="wrap">
    <div class="head" data-rv>
      <h2><?= e(arr($L, 'judul', '')) ?></h2>
      <p><?= e(arr($L, 'intro', '')) ?></p>
    </div>
    <div class="svcs">
<?php foreach ((array)arr($L, 'item', array()) as $it): ?>
      <a class="svc" data-rv href="<?= e(wa_link(arr($it, 'pesan_wa', ''))) ?>" target="_blank" rel="noopener"><?= icon(arr($it, 'ikon', 'custom')) ?><h3><?= e(arr($it, 'judul', '')) ?></h3><p><?= e(arr($it, 'teks', '')) ?></p><span class="ask"><?= e(arr($L, 'label_tombol', 'Tanya harga')) ?><?= icon('arrow') ?></span></a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="sec bahan">
  <div class="wrap">
    <div class="head" data-rv>
      <h2><?= e(arr($B, 'judul', '')) ?></h2>
      <p><?= e(arr($B, 'intro', '')) ?></p>
    </div>
    <div class="mats" data-rv>
<?php foreach (array('akrilik' => 'm-akr', 'kayu' => 'm-kayu', 'kulit' => 'm-kulit') as $key => $cls): $m = arr($B, $key, array()); $on = $key === 'akrilik'; ?>
      <button class="mat <?= $cls ?><?= $on ? ' on' : '' ?>" type="button" aria-expanded="<?= $on ? 'true' : 'false' ?>"><span class="tex"><?= $key === 'akrilik' ? '<i></i>' : '' ?></span><span class="mt"><b><?= e(arr($m, 'nama', '')) ?></b><span class="md"><span><span class="mdi"><?= e(arr($m, 'teks', '')) ?></span></span></span></span></button>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="sec" id="karya">
  <div class="wrap">
    <div class="khead" data-rv>
      <div><h2><?= e(arr($KR, 'judul', '')) ?></h2><p><?= e(arr($KR, 'intro', '')) ?></p></div>
      <div class="filters" id="filters" role="group" aria-label="Saring karya">
        <span class="find" aria-hidden="true"></span>
        <button type="button" class="on" data-f="all" aria-pressed="true">Semua</button><?php foreach ($kat as $k): if (empty($used[arr($k, 'kode', '')])) continue; ?><button type="button" data-f="<?= e(arr($k, 'kode', '')) ?>" aria-pressed="false"><?= e(arr($k, 'label', '')) ?></button><?php endforeach; ?>
      </div>
    </div>
    <div class="gallery" id="gallery">
<?php foreach ($items as $it):
    $imgs = array_values(array_filter((array)arr($it, 'foto', array()), 'strlen'));
    if (!$imgs) continue;
    $t = arr($it, 'judul', '');
    $n = count($imgs);
    $catLabel = ucfirst(arr($it, 'kategori', ''));
    foreach ($kat as $k) if (arr($k, 'kode') === arr($it, 'kategori')) $catLabel = arr($k, 'label', $catLabel);
?>
      <article class="item" data-rv data-cat="<?= e(arr($it, 'kategori', '')) ?>" data-imgs="<?= e(implode(',', $imgs)) ?>" data-title="<?= e($t) ?>" data-ig="<?= e(arr($it, 'link', '')) ?>">
        <button class="ph" type="button" aria-label="Perbesar foto <?= e($t) ?>"><img src="<?= e($imgs[0]) ?>" alt="<?= e($t) ?>" loading="lazy" decoding="async" width="900" height="900"><?php if ($n > 1): ?><span class="cnt"><?= icon('stack') ?><?= $n ?> foto</span><?php endif; ?><span class="open" aria-hidden="true"><svg class="i"><use href="#i-expand"/></svg></span></button>
        <div class="ib"><div class="row"><span class="k"><?= e($catLabel) ?></span><time><?= e(arr($it, 'tanggal', '')) ?></time></div><h3><?= e($t) ?></h3><p class="ds"><?= e(arr($it, 'deskripsi', '')) ?></p><?php if (trim(arr($it, 'klien', '')) !== ''): ?><p class="for">Untuk <?= e(arr($it, 'klien', '')) ?></p><?php endif; ?></div>
      </article>
<?php endforeach; ?>
      <a class="tile" id="tile" href="<?= e(wa_link(arr($KR, 'ajakan_pesan_wa', ''))) ?>" target="_blank" rel="noopener" data-rv>
        <svg class="tile-art" viewBox="0 0 200 200" aria-hidden="true"><circle cx="100" cy="100" r="92"/><circle cx="100" cy="100" r="66"/><circle cx="100" cy="100" r="40"/></svg>
        <span class="tt"><b><?= e(arr($KR, 'ajakan_judul', '')) ?></b><span class="t"><?= e(arr($KR, 'ajakan_teks', '')) ?></span></span>
        <span class="btn btn-light"><?= e(arr($KR, 'ajakan_tombol', '')) ?><svg class="i ar" aria-hidden="true"><use href="#i-arrow"/></svg></span>
      </a>
    </div>
  </div>
</section>

<?php $klienItems = (array)arr($KL, 'item', array()); if ($klienItems): ?>
<section class="sec klien" id="klien">
  <div class="wrap">
    <div class="head" data-rv>
      <h2><?= e(arr($KL, 'judul', '')) ?></h2>
      <p><?= e(arr($KL, 'intro', '')) ?></p>
    </div>
    <div class="kls">
<?php foreach ($klienItems as $c): $url = trim(arr($c, 'url', '')); $tag = $url !== '' ? 'a' : 'div'; ?>
      <<?= $tag ?> class="kl" data-rv<?= $url !== '' ? ' href="' . e($url) . '" target="_blank" rel="noopener"' : '' ?>><?php if (trim(arr($c, 'gambar', '')) !== ''): ?><img src="<?= e(arr($c, 'gambar', '')) ?>" alt="Pesanan <?= e(arr($c, 'nama', '')) ?>" loading="lazy" decoding="async" width="900" height="900"><?php endif; ?><?php if ($url !== ''): ?><span class="kl-go" aria-hidden="true"><svg class="i"><use href="#i-arrow-ur"/></svg></span><?php endif; ?><span class="kl-b"><b><?= e(arr($c, 'nama', '')) ?></b><span><?= e(arr($c, 'teks', '')) ?></span></span></<?= $tag ?>>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec" id="cara-pesan">
  <div class="wrap proc">
    <div class="vid" data-rv>
      <video muted loop playsinline preload="metadata" poster="/img/proses.jpg" src="/img/proses.mp4" aria-label="<?= e(arr($CP, 'keterangan_video', 'Video proses')) ?>"></video>
      <button class="vbtn paused" type="button" aria-label="Putar video"><svg class="i fill pa" aria-hidden="true"><use href="#i-pause"/></svg><svg class="i fill pl" aria-hidden="true"><use href="#i-play"/></svg></button>
      <span class="vcap"><?= e(arr($CP, 'keterangan_video', '')) ?></span>
    </div>
    <div>
      <h2 data-rv><?= e(arr($CP, 'judul', '')) ?></h2>
      <div class="stepw">
        <span class="rail" aria-hidden="true"><i></i></span>
        <ol class="steps">
<?php $n = 0; foreach ((array)arr($CP, 'langkah', array()) as $st): $n++; ?>
          <li class="step"><span class="dot"><?= icon(arr($st, 'ikon', 'check')) ?></span><div class="tx"><h3><span><?= $n ?></span><?= e(arr($st, 'judul', '')) ?></h3><p><?= e(arr($st, 'teks', '')) ?></p></div></li>
<?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</section>

<?php if ($latest): ?>
<section class="sec artikel-home" id="artikel">
  <div class="wrap">
    <div class="head" data-rv>
      <h2><?= e(arr($AR, 'judul', 'Artikel')) ?></h2>
      <p><?= e(arr($AR, 'intro', '')) ?> <a class="more" href="/artikel/">Semua artikel</a></p>
    </div>
    <div class="arts">
<?php foreach ($latest as $a): ?>
      <a class="art-card" data-rv href="/artikel/<?= e(arr($a, 'slug', '')) ?>"><?php if (trim(arr($a, 'sampul', '')) !== ''): ?><img src="<?= e(arr($a, 'sampul', '')) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span class="art-ph" aria-hidden="true"><?= icon('spark') ?></span><?php endif; ?><span class="art-b"><time><?= tanggal_id(arr($a, 'tanggal', '')) ?></time><b><?= e(arr($a, 'judul', '')) ?></b><span><?= e(excerpt_of($a, 120)) ?></span></span></a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec sec-contact" id="kontak">
  <div class="wrap">
    <div class="contact" data-rv>
      <div class="mat-grid" aria-hidden="true"></div>
      <div class="cbody">
        <h2><?= e(arr($KB, 'judul', '')) ?></h2>
        <p><?= e(arr($KB, 'teks', '')) ?></p>
        <a class="btn btn-light mag" href="<?= e($order) ?>" target="_blank" rel="noopener"><?= icon('wa', 'i', true) ?><?= e(arr($KB, 'tombol', '')) ?></a>
      </div>
      <ul class="clist">
        <li><a class="crow" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener"><?= icon('wa', 'i', true) ?><span><small>WhatsApp</small><b><?= e(arr($K, 'wa_tampil', '')) ?></b></span><svg class="i go" aria-hidden="true"><use href="#i-arrow-ur"/></svg></a></li>
        <?php if (trim(arr($K, 'instagram_url', '')) !== ''): ?><li><a class="crow" href="<?= e(arr($K, 'instagram_url', '')) ?>" target="_blank" rel="noopener"><?= icon('ig') ?><span><small>Instagram</small><b><?= e(arr($K, 'instagram_nama', '')) ?></b></span><svg class="i go" aria-hidden="true"><use href="#i-arrow-ur"/></svg></a></li><?php endif; ?>
        <?php if (trim(arr($K, 'facebook_url', '')) !== ''): ?><li><a class="crow" href="<?= e(arr($K, 'facebook_url', '')) ?>" target="_blank" rel="noopener"><?= icon('fb') ?><span><small>Facebook</small><b><?= e(arr($K, 'facebook_nama', '')) ?></b></span><svg class="i go" aria-hidden="true"><use href="#i-arrow-ur"/></svg></a></li><?php endif; ?>
        <?php if (trim(arr($K, 'lokasi', '')) !== ''): ?><li><div class="crow"><?= icon('pin') ?><span><small>Lokasi</small><b><?= e(arr($K, 'lokasi', '')) ?></b></span></div></li><?php endif; ?>
      </ul>
    </div>
  </div>
</section>
</main>

<?php site_footer(); ?>

<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Foto karya" hidden>
  <button class="lb-btn lb-x" type="button" aria-label="Tutup"><svg class="i" aria-hidden="true"><use href="#i-x"/></svg></button>
  <button class="lb-btn lb-nav pv" type="button" aria-label="Foto sebelumnya"><svg class="i" aria-hidden="true"><use href="#i-prev"/></svg></button>
  <figure>
    <div class="lb-stage"><img id="lbImg" alt="" draggable="false"></div>
    <figcaption><span><b id="lbT"></b> <span id="lbN"></span></span><a id="lbIg" target="_blank" rel="noopener">Lihat di Instagram<svg class="i" aria-hidden="true"><use href="#i-arrow-ur"/></svg></a></figcaption>
  </figure>
  <button class="lb-btn lb-nav nx" type="button" aria-label="Foto berikutnya"><svg class="i" aria-hidden="true"><use href="#i-next"/></svg></button>
</div>

<script src="/assets/site.js?v=<?= @filemtime(__DIR__ . '/assets/site.js') ?>"></script>
</body>
</html>

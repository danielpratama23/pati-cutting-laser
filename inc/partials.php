<?php
/* bagian halaman yang dipakai bersama: head, menu, footer */

function page_head($title, $desc, $extra = '') {
    $u = arr(site(), 'umum', array());
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#1F3A28">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:site_name" content="<?= e(arr($u, 'nama', '')) ?>">
<link rel="icon" href="<?= e(arr($u, 'logo', '/img/logo.png')) ?>">
<script>document.documentElement.classList.add('js')</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/site.css?v=<?= @filemtime(PCL_ROOT . '/assets/site.css') ?>">
<?= $extra ?>
</head>
<body>
<?php
    readfile(PCL_ROOT . '/inc/sprite.svg');
    echo "\n<div class=\"progress\" aria-hidden=\"true\"><i></i></div>\n";
}

function nav_links($home) {
    $p = $home ? '' : '/';
    $links = array(array($p . '#layanan', 'Layanan'), array($p . '#karya', 'Karya'), array($p . '#klien', 'Klien'), array($p . '#cara-pesan', 'Cara pesan'));
    if (count(all_articles()) > 0) $links[] = array($home ? '#artikel' : '/artikel/', 'Artikel');
    return $links;
}

function site_nav($home = true) {
    $u = arr(site(), 'umum', array());
    $links = nav_links($home);
    ?>
<header class="nav" id="nav">
  <a class="brand" href="<?= $home ? '#top' : '/' ?>"><img src="<?= e(arr($u, 'logo', '/img/logo.png')) ?>" alt="" width="38" height="38"><span><?= e(arr($u, 'nama', '')) ?></span></a>
  <nav class="links" aria-label="Menu utama">
    <span class="ind" aria-hidden="true"></span>
    <?php foreach ($links as $l): ?><a href="<?= e($l[0]) ?>"><?= e($l[1]) ?></a><?php endforeach; ?>
  </nav>
  <a class="btn" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" aria-label="Chat WhatsApp"><?= icon('wa', 'i', true) ?><span>WhatsApp</span></a>
  <button class="burger" id="burger" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="mmenu"><svg class="i a" aria-hidden="true"><use href="#i-menu"/></svg><svg class="i b" aria-hidden="true"><use href="#i-x"/></svg></button>
</header>
<div class="mmenu" id="mmenu">
<?php $k = 0; foreach ($links as $l): ?>  <a href="<?= e($l[0]) ?>" style="--k:<?= $k++ ?>"><?= e($l[1]) ?></a>
<?php endforeach; ?>  <a href="<?= $home ? '#kontak' : '/#kontak' ?>" style="--k:<?= $k ?>">Kontak</a>
</div>
<?php
}

function site_footer() {
    $u = arr(site(), 'umum', array());
    $f = arr(site(), 'footer', array());
    ?>
<footer>
  <div class="wrap foot">
    <a class="brand" href="/"><img src="<?= e(arr($u, 'logo', '/img/logo.png')) ?>" alt="" width="32" height="32" loading="lazy"><span><?= e(arr($u, 'nama', '')) ?></span></a>
    <span><?= e(arr($f, 'hak_cipta', '')) ?></span>
    <nav aria-label="Tautan footer"><?php foreach ((array)arr($f, 'link', array()) as $l): if (trim(arr($l, 'label', '')) === '') continue; ?><a href="<?= e(arr($l, 'url', '#')) ?>"<?= preg_match('#^https?://#', arr($l, 'url', '')) ? ' target="_blank" rel="noopener"' : '' ?>><?= e(arr($l, 'label', '')) ?></a><?php endforeach; ?></nav>
  </div>
</footer>

<a class="wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" aria-label="Chat WhatsApp"><?= icon('wa', 'i', true) ?></a>
<?php
}

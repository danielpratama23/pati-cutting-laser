<?php
define('PCL_ROOT', __DIR__);
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/partials.php';

$S  = site();
$U  = arr($S, 'umum', array());
$K  = arr($S, 'kontak', array());
$AR = arr($S, 'artikel', array());
$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';

if ($slug !== '') {
    $a = load_article($slug);
    if (!$a || empty($a['terbit'])) {
        http_response_code(404);
        page_head('Artikel tidak ditemukan — ' . arr($U, 'nama', ''), '');
        site_nav(false);
        echo '<main class="page"><div class="wrap narrow"><h1 class="ptitle">Artikel tidak ditemukan</h1><p class="plead">Artikel ini sudah dihapus atau alamatnya salah.</p><p><a class="btn" href="/artikel/">Lihat semua artikel</a></p></div></main>';
        site_footer();
        echo '<script src="/assets/page.js"></script></body></html>';
        exit;
    }
    $title = arr($a, 'judul_seo', '') ?: arr($a, 'judul', '');
    $desc  = arr($a, 'deskripsi_seo', '') ?: excerpt_of($a, 155);
    $extra = '<link rel="canonical" href="' . e(base_url() . '/artikel/' . $a['slug']) . '">';
    if (trim(arr($a, 'sampul', '')) !== '') $extra .= "\n" . '<meta property="og:image" content="' . e(base_url() . arr($a, 'sampul', '')) . '">';
    $ld = array('@context' => 'https://schema.org', '@type' => 'Article', 'headline' => arr($a, 'judul', ''), 'datePublished' => arr($a, 'tanggal', ''),
        'author' => array('@type' => 'Organization', 'name' => arr($U, 'nama', '')), 'publisher' => array('@type' => 'Organization', 'name' => arr($U, 'nama', '')));
    if (trim(arr($a, 'sampul', '')) !== '') $ld['image'] = base_url() . arr($a, 'sampul', '');
    $extra .= "\n" . '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
    page_head($title . ' — ' . arr($U, 'nama', ''), $desc, $extra);
    site_nav(false);
    ?>
<main class="page">
  <article class="wrap narrow post">
    <a class="back" href="/artikel/"><?= icon('prev') ?>Semua artikel</a>
    <time class="pdate"><?= tanggal_id(arr($a, 'tanggal', '')) ?></time>
    <h1 class="ptitle"><?= e(arr($a, 'judul', '')) ?></h1>
    <?php if (trim(arr($a, 'ringkasan', '')) !== ''): ?><p class="plead"><?= e(arr($a, 'ringkasan', '')) ?></p><?php endif; ?>
    <?php if (trim(arr($a, 'sampul', '')) !== ''): ?><img class="pcover" src="<?= e(arr($a, 'sampul', '')) ?>" alt="<?= e(arr($a, 'judul', '')) ?>"><?php endif; ?>
    <div class="prose"><?= clean_html(arr($a, 'isi', '')) ?></div>
    <aside class="pcta">
      <div><b>Mau pesan yang seperti ini?</b><span>Kirim kebutuhanmu lewat WhatsApp, kami bantu desainnya.</span></div>
      <a class="btn btn-light" href="<?= e(wa_link(arr($K, 'pesan_order', ''))) ?>" target="_blank" rel="noopener"><?= icon('wa', 'i', true) ?>Chat WhatsApp</a>
    </aside>
  </article>
</main>
<?php
} else {
    $list = all_articles();
    page_head(arr($AR, 'judul', 'Artikel') . ' — ' . arr($U, 'nama', ''), arr($AR, 'intro', ''), '<link rel="canonical" href="' . e(base_url() . '/artikel/') . '">');
    site_nav(false);
    ?>
<main class="page">
  <div class="wrap">
    <h1 class="ptitle"><?= e(arr($AR, 'judul', 'Artikel')) ?></h1>
    <p class="plead"><?= e(arr($AR, 'intro', '')) ?></p>
    <?php if (!$list): ?>
    <p class="empty">Belum ada artikel. Kembali lagi nanti.</p>
    <?php else: ?>
    <div class="arts">
<?php foreach ($list as $a): ?>
      <a class="art-card" href="/artikel/<?= e(arr($a, 'slug', '')) ?>"><?php if (trim(arr($a, 'sampul', '')) !== ''): ?><img src="<?= e(arr($a, 'sampul', '')) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span class="art-ph" aria-hidden="true"><?= icon('spark') ?></span><?php endif; ?><span class="art-b"><time><?= tanggal_id(arr($a, 'tanggal', '')) ?></time><b><?= e(arr($a, 'judul', '')) ?></b><span><?= e(excerpt_of($a, 140)) ?></span></span></a>
<?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php
}
site_footer();
?>
<script src="/assets/page.js?v=<?= @filemtime(__DIR__ . '/assets/page.js') ?>"></script>
</body>
</html>

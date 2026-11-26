<?php
define('PCL_ROOT', __DIR__);
require __DIR__ . '/inc/lib.php';
header('Content-Type: application/xml; charset=utf-8');
$b = base_url();
$urls = array(array($b . '/', @date('Y-m-d', filemtime(PCL_DATA . '/site.json'))));
$list = all_articles();
if ($list) $urls[] = array($b . '/artikel/', arr($list[0], 'tanggal', ''));
foreach ($list as $a) $urls[] = array($b . '/artikel/' . arr($a, 'slug', ''), arr($a, 'diubah', arr($a, 'tanggal', '')));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . e($u[0]) . '</loc>';
    if ($u[1]) echo '<lastmod>' . e(substr($u[1], 0, 10)) . '</lastmod>';
    echo "</url>\n";
}
echo "</urlset>\n";

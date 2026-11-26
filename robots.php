<?php
define('PCL_ROOT', __DIR__);
require __DIR__ . '/inc/lib.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nDisallow: /admin/\n\nSitemap: " . base_url() . "/sitemap.xml\n";

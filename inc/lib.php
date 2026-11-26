<?php
/* Pati Cutting Laser — fungsi bersama untuk situs dan admin. Ditulis agar jalan di PHP 7.2 ke atas. */

if (!defined('PCL_ROOT')) { define('PCL_ROOT', dirname(__DIR__)); }
define('PCL_DATA', PCL_ROOT . '/data');
define('PCL_ARTICLES', PCL_DATA . '/articles');
define('PCL_UPLOADS', PCL_ROOT . '/uploads');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function arr($a, $k, $def = '') { return (is_array($a) && array_key_exists($k, $a)) ? $a[$k] : $def; }

function read_json($file, $def = array()) {
    if (!is_file($file)) return $def;
    $raw = file_get_contents($file);
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $def;
}

function write_json($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) return false;
    $tmp = $file . '.tmp' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, $file);
}

function site() {
    static $s = null;
    if ($s === null) $s = read_json(PCL_DATA . '/site.json');
    return $s;
}

/* simpan salinan isi lama sebelum ditimpa, simpan 30 terakhir */
function backup_site() {
    $src = PCL_DATA . '/site.json';
    if (!is_file($src)) return;
    $dir = PCL_DATA . '/backup';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    copy($src, $dir . '/site-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json');
    $all = glob($dir . '/site-*.json');
    if ($all && count($all) > 30) { sort($all); foreach (array_slice($all, 0, count($all) - 30) as $old) @unlink($old); }
}

function wa_link($text = '') {
    $k = arr(site(), 'kontak', array());
    $num = preg_replace('/\D+/', '', arr($k, 'wa_nomor', ''));
    $u = 'https://wa.me/' . $num;
    if ($text !== '') $u .= '?text=' . rawurlencode($text);
    return $u;
}

function slugify($s) {
    $s = strtolower(trim((string)$s));
    $map = array('á'=>'a','à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ñ'=>'n','ç'=>'c');
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return substr($s, 0, 80) ?: 'artikel';
}

function valid_slug($s) { return is_string($s) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $s) && strlen($s) <= 80; }

/* ---------- artikel ---------- */
function article_path($slug) { return PCL_ARTICLES . '/' . $slug . '.json'; }

function load_article($slug) {
    if (!valid_slug($slug)) return null;
    $a = read_json(article_path($slug), null);
    return is_array($a) ? $a : null;
}

function all_articles($published_only = true) {
    $out = array();
    foreach ((glob(PCL_ARTICLES . '/*.json') ?: array()) as $f) {
        $a = read_json($f, null);
        if (!is_array($a)) continue;
        if ($published_only && empty($a['terbit'])) continue;
        $out[] = $a;
    }
    usort($out, function ($x, $y) { return strcmp(arr($y, 'tanggal', ''), arr($x, 'tanggal', '')); });
    return $out;
}

function tanggal_id($ymd) {
    $t = strtotime((string)$ymd);
    if (!$t) return e($ymd);
    $b = array(1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t);
}

/* isi artikel dari editor: hanya tag dan atribut yang aman */
function clean_html($html) {
    $html = trim((string)$html);
    if ($html === '') return '';
    if (!class_exists('DOMDocument')) return nl2br(e(strip_tags($html)));
    $allowed = array('p'=>array(),'br'=>array(),'h2'=>array(),'h3'=>array(),'strong'=>array(),'b'=>array(),'em'=>array(),'i'=>array(),'u'=>array(),'s'=>array(),
        'a'=>array('href'),'ul'=>array(),'ol'=>array(),'li'=>array(),'blockquote'=>array(),'img'=>array('src','alt'),'figure'=>array(),'figcaption'=>array(),'hr'=>array());
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__r">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__r');
    if (!$root) return '';
    clean_node($root, $allowed, $doc);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}

function clean_node($node, $allowed, $doc) {
    $children = array();
    foreach ($node->childNodes as $c) $children[] = $c;
    foreach ($children as $c) {
        if ($c->nodeType === XML_TEXT_NODE) continue;
        if ($c->nodeType !== XML_ELEMENT_NODE) { $node->removeChild($c); continue; }
        $tag = strtolower($c->nodeName);
        if (in_array($tag, array('script','style','iframe','object','embed','form','input','button','textarea','select','svg','math'), true)) { $node->removeChild($c); continue; }
        clean_node($c, $allowed, $doc);
        if (!isset($allowed[$tag])) {
            while ($c->firstChild) $node->insertBefore($c->firstChild, $c);
            $node->removeChild($c);
            continue;
        }
        $keep = $allowed[$tag];
        $attrs = array();
        foreach ($c->attributes as $at) $attrs[] = $at->nodeName;
        foreach ($attrs as $an) {
            $v = $c->getAttribute($an);
            if (!in_array(strtolower($an), $keep, true)) { $c->removeAttribute($an); continue; }
            if ($an === 'href' || $an === 'src') {
                $v = trim($v);
                $ok = preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $v) && !preg_match('#^//#', $v);
                if ($an === 'src') $ok = preg_match('#^(https?://|/)#i', $v) && !preg_match('#^//#', $v);
                if (!$ok) $c->removeAttribute($an); else $c->setAttribute($an, $v);
            }
        }
        if ($tag === 'a' && $c->hasAttribute('href') && preg_match('#^https?://#i', $c->getAttribute('href'))) {
            $c->setAttribute('target', '_blank');
            $c->setAttribute('rel', 'noopener');
        }
        if ($tag === 'img') { $c->setAttribute('loading', 'lazy'); $c->setAttribute('decoding', 'async'); }
    }
}

function excerpt_of($a, $len = 160) {
    $x = trim((string)arr($a, 'ringkasan', ''));
    if ($x !== '') return $x;
    $t = trim(preg_replace('/\s+/', ' ', strip_tags((string)arr($a, 'isi', ''))));
    if (function_exists('mb_strlen') && mb_strlen($t) > $len) return rtrim(mb_substr($t, 0, $len)) . '…';
    if (strlen($t) > $len) return rtrim(substr($t, 0, $len)) . '…';
    return $t;
}

function base_url() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.:\-]/', '', $_SERVER['HTTP_HOST']) : 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host;
}

function icon($name, $cls = 'i', $fill = false) {
    $name = preg_replace('/[^a-z\-]/', '', (string)$name);
    return '<svg class="' . e($cls . ($fill ? ' fill' : '')) . '" aria-hidden="true"><use href="#i-' . $name . '"/></svg>';
}

function icon_choices() {
    return array('trophy'=>'Trophy','plakat'=>'Plakat','sign'=>'Papan / signage','ring'=>'Cincin','flower'=>'Bunga','custom'=>'Bintang (custom)','laser'=>'Mesin laser','box'=>'Kotak / paket','pen'=>'Pena / desain','chat'=>'Chat','spark'=>'Kilau','check'=>'Centang','pin'=>'Lokasi');
}

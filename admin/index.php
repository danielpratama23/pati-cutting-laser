<?php
/* Admin Pati Cutting Laser */
define('PCL_ROOT', dirname(__DIR__));
require PCL_ROOT . '/inc/lib.php';
require __DIR__ . '/schema.php';

header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(array('lifetime' => 0, 'path' => '/admin', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax'));
} else {
    session_set_cookie_params(0, '/admin; samesite=Lax', '', $secure, true);
}
session_name('pcl_admin');
session_start();

define('AUTH_FILE', PCL_DATA . '/auth.php');
define('SETUP_FILE', PCL_DATA . '/kode-setup.txt');
define('ATTEMPT_FILE', PCL_DATA . '/login-attempts.json');

/* ---------- bantu ---------- */
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function check_csrf() {
    $t = isset($_POST['csrf']) ? (string)$_POST['csrf'] : (isset($_SERVER['HTTP_X_CSRF']) ? (string)$_SERVER['HTTP_X_CSRF'] : '');
    if (!$t || !hash_equals(csrf(), $t)) { http_response_code(400); exit('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'); }
}
function flash($msg, $type = 'ok') { $_SESSION['flash'] = array($msg, $type); }
function go($p, $extra = '') { header('Location: ./?p=' . rawurlencode($p) . $extra); exit; }
function auth_data() {
    if (!is_file(AUTH_FILE)) return null;
    $d = include AUTH_FILE;
    return is_array($d) ? $d : null;
}
function save_auth($user, $pass) {
    $data = array('user' => $user, 'hash' => password_hash($pass, PASSWORD_DEFAULT));
    return file_put_contents(AUTH_FILE, "<?php\nreturn " . var_export($data, true) . ";\n", LOCK_EX) !== false;
}
function logged_in() {
    if (empty($_SESSION['uid'])) return false;
    if (time() - (int)arr($_SESSION, 'seen', 0) > 8 * 3600) { $_SESSION = array(); return false; }
    $_SESSION['seen'] = time();
    return true;
}
function client_ip() { return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0'; }
function attempts() { return read_json(ATTEMPT_FILE, array()); }
function locked_for() {
    $a = attempts(); $r = arr($a, client_ip(), array());
    $until = (int)arr($r, 'until', 0);
    return $until > time() ? $until - time() : 0;
}
function note_fail() {
    $a = attempts(); $ip = client_ip();
    foreach ($a as $k => $v) if ((int)arr($v, 'last', 0) < time() - 86400) unset($a[$k]);
    $r = arr($a, $ip, array('n' => 0));
    $r['n'] = (int)arr($r, 'n', 0) + 1; $r['last'] = time();
    if ($r['n'] >= 5) { $r['until'] = time() + 15 * 60; $r['n'] = 0; }
    $a[$ip] = $r;
    write_json(ATTEMPT_FILE, $a);
}
function clear_fail() { $a = attempts(); unset($a[client_ip()]); write_json(ATTEMPT_FILE, $a); }

function get_path($data, $path) {
    foreach (explode('.', $path) as $k) { if (!is_array($data) || !array_key_exists($k, $data)) return null; $data = $data[$k]; }
    return $data;
}
function set_path(&$data, $path, $val) {
    $ref = &$data;
    foreach (explode('.', $path) as $k) { if (!isset($ref[$k]) || !is_array($ref[$k])) { if (!isset($ref[$k])) $ref[$k] = array(); } $ref = &$ref[$k]; }
    $ref = $val;
}
function clean_text($v, $max = 2000) {
    $v = trim(str_replace("\r\n", "\n", strip_tags((string)$v)));
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}
function clean_img($v) {
    $v = trim((string)$v);
    if ($v === '') return '';
    if (!preg_match('#^/(img|uploads)/[A-Za-z0-9._\-/]+\.(jpe?g|png|webp|gif)$#i', $v) || strpos($v, '..') !== false) return '';
    return $v;
}
function clean_url($v) {
    $v = trim((string)$v);
    if ($v === '') return '';
    if (preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $v) && !preg_match('#^//#', $v)) return $v;
    if (preg_match('#^[a-z0-9.-]+\.[a-z]{2,}#i', $v)) return 'https://' . $v;
    return '';
}
function clean_field($f, $raw) {
    $type = $f[1]; $opt = isset($f[3]) ? $f[3] : array(); $name = $f[0];
    if ($type === 'text') {
        $v = clean_text($raw, 400);
        if (preg_match('/(url|link)$/', $name) || $name === 'url') $v = clean_url($v);
        if ($name === 'kontak.wa_nomor') { $v = preg_replace('/\D+/', '', $v); if (strpos($v, '0') === 0) $v = '62' . substr($v, 1); }
        if ($name === 'kode') $v = slugify($v);
        return $v;
    }
    if ($type === 'textarea') return clean_text($raw, 3000);
    if ($type === 'image') return clean_img($raw);
    if ($type === 'select') { $c = resolve_choices(arr($opt, 'choices', array())); $v = (string)$raw; return array_key_exists($v, $c) ? (ctype_digit($v) && $name === 'artikel.tampil_di_beranda' ? (int)$v : $v) : (string)key($c); }
    if ($type === 'strings') { $out = array(); foreach ((array)$raw as $s) { $s = clean_text($s, 120); if ($s !== '') $out[] = $s; } return $out; }
    if ($type === 'images') { $out = array(); foreach ((array)$raw as $s) { $s = clean_img($s); if ($s !== '') $out[] = $s; } return $out; }
    if ($type === 'list') {
        $out = array();
        foreach ((array)$raw as $row) {
            if (!is_array($row)) continue;
            $item = array(); $empty = true;
            foreach ($opt['fields'] as $sf) {
                $v = clean_field($sf, arr($row, $sf[0], $sf[1] === 'images' ? array() : ''));
                $item[$sf[0]] = $v;
                if ($sf[1] !== 'select' && $v !== '' && $v !== array()) $empty = false;
            }
            if (!$empty) $out[] = $item;
        }
        if (!empty($opt['max'])) $out = array_slice($out, 0, (int)$opt['max']);
        return $out;
    }
    return '';
}

/* ---------- unggah gambar ---------- */
function handle_upload($file) {
    if (!is_array($file) || !isset($file['error'])) return array(false, 'Tidak ada file.');
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) return array(false, 'File terlalu besar untuk server. Kecilkan dulu (maksimal sekitar 8 MB).');
    if ($file['error'] !== UPLOAD_ERR_OK) return array(false, 'Upload gagal. Coba lagi.');
    if ($file['size'] > 10 * 1024 * 1024) return array(false, 'File terlalu besar. Maksimal 10 MB.');
    $info = @getimagesize($file['tmp_name']);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) $types[IMAGETYPE_WEBP] = 'webp';
    if (!$info || !isset($types[$info[2]])) return array(false, 'Hanya gambar JPG, PNG, WebP, atau GIF.');
    $ext = $types[$info[2]];
    if (!is_dir(PCL_UPLOADS)) mkdir(PCL_UPLOADS, 0755, true);
    $base = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME));
    $base = substr($base, 0, 40) ?: 'gambar';
    $name = date('Ym') . '-' . $base . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = PCL_UPLOADS . '/' . $name;
    $done = false;
    /* kecilkan foto besar supaya situs tetap cepat */
    if ($ext !== 'gif' && function_exists('imagecreatetruecolor') && max($info[0], $info[1]) > 1800) {
        $src = null;
        if ($ext === 'jpg' && function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($file['tmp_name']);
        if ($ext === 'png' && function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($file['tmp_name']);
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($file['tmp_name']);
        if ($src) {
            $r = 1800 / max($info[0], $info[1]);
            $w = (int)round($info[0] * $r); $h = (int)round($info[1] * $r);
            $dst = imagecreatetruecolor($w, $h);
            if ($ext !== 'jpg') { imagealphablending($dst, false); imagesavealpha($dst, true); }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
            if ($ext === 'jpg') $done = imagejpeg($dst, $dest, 84);
            elseif ($ext === 'png') $done = imagepng($dst, $dest, 7);
            elseif ($ext === 'webp' && function_exists('imagewebp')) $done = imagewebp($dst, $dest, 82);
            imagedestroy($src); imagedestroy($dst);
        }
    }
    if (!$done && !move_uploaded_file($file['tmp_name'], $dest)) return array(false, 'Tidak bisa menyimpan file. Cek izin folder uploads.');
    @chmod($dest, 0644);
    return array(true, '/uploads/' . $name);
}

function media_list() {
    $out = array();
    foreach (array('uploads', 'img') as $dir) {
        foreach ((glob(PCL_ROOT . '/' . $dir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE) ?: array()) as $f) {
            $out[] = array('src' => '/' . $dir . '/' . basename($f), 't' => filemtime($f), 'up' => $dir === 'uploads');
        }
    }
    usort($out, function ($a, $b) { return $b['t'] - $a['t']; });
    return $out;
}

/* ---------- alur ---------- */
$p = isset($_GET['p']) ? preg_replace('/[^a-z_]/', '', $_GET['p']) : 'dash';
$auth = auth_data();
$method = $_SERVER['REQUEST_METHOD'];
$err = '';

if (!$auth) {
    /* pemasangan pertama: wajib kode setup dari File Manager */
    if ($method === 'POST') {
        check_csrf();
        $code = is_file(SETUP_FILE) ? trim(file_get_contents(SETUP_FILE)) : '';
        $u = trim((string)arr($_POST, 'user', '')); $pw = (string)arr($_POST, 'pass', ''); $pw2 = (string)arr($_POST, 'pass2', '');
        if ($code === '' ) $err = 'File data/kode-setup.txt tidak ditemukan. Upload ulang paket CMS.';
        elseif (!hash_equals($code, trim((string)arr($_POST, 'code', '')))) $err = 'Kode setup salah. Buka file data/kode-setup.txt di File Manager dan salin isinya.';
        elseif (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $u)) $err = 'Nama pengguna 3–40 huruf atau angka, tanpa spasi.';
        elseif (strlen($pw) < 10) $err = 'Password minimal 10 karakter.';
        elseif ($pw !== $pw2) $err = 'Ulangi password tidak sama.';
        elseif (!save_auth($u, $pw)) $err = 'Tidak bisa menyimpan. Pastikan folder data bisa ditulis (izin 755).';
        else {
            @unlink(SETUP_FILE);
            session_regenerate_id(true);
            $_SESSION['uid'] = $u; $_SESSION['seen'] = time();
            flash('Admin siap dipakai. Simpan password-mu baik-baik.');
            go('dash');
        }
    }
    render_auth('setup', $err);
    exit;
}

if ($p === 'logout' && $method === 'POST') { check_csrf(); $_SESSION = array(); session_destroy(); header('Location: ./'); exit; }

if (!logged_in()) {
    if ($method === 'POST' && $p === 'login') {
        check_csrf();
        $wait = locked_for();
        if ($wait > 0) $err = 'Terlalu banyak percobaan. Coba lagi dalam ' . ceil($wait / 60) . ' menit.';
        else {
            $u = trim((string)arr($_POST, 'user', '')); $pw = (string)arr($_POST, 'pass', '');
            if (hash_equals((string)$auth['user'], $u) && password_verify($pw, $auth['hash'])) {
                clear_fail();
                if (password_needs_rehash($auth['hash'], PASSWORD_DEFAULT)) save_auth($u, $pw);
                session_regenerate_id(true);
                $_SESSION['uid'] = $u; $_SESSION['seen'] = time();
                go('dash');
            }
            note_fail(); usleep(600000);
            $err = 'Nama pengguna atau password salah.';
        }
    }
    render_auth('login', $err);
    exit;
}

/* ---------- sudah masuk ---------- */
$sections = admin_sections();

if ($p === 'upload' && $method === 'POST') {
    check_csrf();
    header('Content-Type: application/json');
    list($ok, $res) = handle_upload(isset($_FILES['file']) ? $_FILES['file'] : null);
    echo json_encode($ok ? array('ok' => true, 'src' => $res) : array('ok' => false, 'error' => $res));
    exit;
}
if ($p === 'media_json') {
    header('Content-Type: application/json');
    echo json_encode(media_list());
    exit;
}
if ($p === 'media_hapus' && $method === 'POST') {
    check_csrf();
    $src = clean_img(arr($_POST, 'src', ''));
    if ($src && strpos($src, '/uploads/') === 0 && is_file(PCL_ROOT . $src)) { unlink(PCL_ROOT . $src); flash('Gambar dihapus.'); }
    go('media');
}

if (isset($sections[$p]) && $method === 'POST') {
    check_csrf();
    $data = read_json(PCL_DATA . '/site.json');
    $post = isset($_POST['f']) && is_array($_POST['f']) ? $_POST['f'] : array();
    foreach ($sections[$p]['fields'] as $f) {
        $raw = get_path($post, $f[0]);
        if ($raw === null) $raw = ($f[1] === 'list' || $f[1] === 'strings' || $f[1] === 'images') ? array() : '';
        set_path($data, $f[0], clean_field($f, $raw));
    }
    backup_site();
    if (write_json(PCL_DATA . '/site.json', $data)) flash('Perubahan disimpan. Buka situs untuk melihat hasilnya.');
    else flash('Gagal menyimpan. Pastikan folder data bisa ditulis.', 'err');
    go($p);
}

if ($p === 'artikel_simpan' && $method === 'POST') {
    check_csrf();
    $old = (string)arr($_POST, 'old_slug', '');
    $judul = clean_text(arr($_POST, 'judul', ''), 200);
    $slug = slugify(arr($_POST, 'slug', '') !== '' ? arr($_POST, 'slug', '') : $judul);
    if ($judul === '') { flash('Judul artikel wajib diisi.', 'err'); go('artikel_edit', $old ? '&slug=' . rawurlencode($old) : ''); }
    if ($slug !== $old && is_file(article_path($slug))) $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 3);
    $tgl = (string)arr($_POST, 'tanggal', '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) $tgl = date('Y-m-d');
    $prev = ($old !== '' && valid_slug($old)) ? load_article($old) : null;
    $a = array(
        'judul' => $judul, 'slug' => $slug, 'tanggal' => $tgl,
        'ringkasan' => clean_text(arr($_POST, 'ringkasan', ''), 400),
        'sampul' => clean_img(arr($_POST, 'sampul', '')),
        'isi' => clean_html(arr($_POST, 'isi', '')),
        'terbit' => !empty($_POST['terbit']),
        'judul_seo' => clean_text(arr($_POST, 'judul_seo', ''), 120),
        'deskripsi_seo' => clean_text(arr($_POST, 'deskripsi_seo', ''), 300),
        'dibuat' => $prev ? arr($prev, 'dibuat', date('c')) : date('c'),
        'diubah' => date('c'),
    );
    if (write_json(article_path($slug), $a)) {
        if ($prev && $old !== $slug) @unlink(article_path($old));
        flash($a['terbit'] ? 'Artikel disimpan dan sudah tampil di situs.' : 'Artikel disimpan sebagai draf. Belum tampil di situs.');
    } else flash('Gagal menyimpan artikel. Pastikan folder data/articles bisa ditulis.', 'err');
    go('artikel_edit', '&slug=' . rawurlencode($slug));
}
if ($p === 'artikel_hapus' && $method === 'POST') {
    check_csrf();
    $s = (string)arr($_POST, 'slug', '');
    if (valid_slug($s) && is_file(article_path($s))) { unlink(article_path($s)); flash('Artikel dihapus.'); }
    go('artikel');
}
if ($p === 'pulihkan' && $method === 'POST') {
    check_csrf();
    $f = basename((string)arr($_POST, 'file', ''));
    $path = PCL_DATA . '/backup/' . $f;
    if (preg_match('/^site-[0-9-]+-[a-f0-9]+\.json$/', $f) && is_file($path) && is_array(read_json($path, null))) {
        backup_site(); copy($path, PCL_DATA . '/site.json'); flash('Isi situs dikembalikan ke versi itu.');
    } else flash('Versi tidak ditemukan.', 'err');
    go('riwayat');
}
if ($p === 'password' && $method === 'POST') {
    check_csrf();
    $cur = (string)arr($_POST, 'cur', ''); $n1 = (string)arr($_POST, 'new1', ''); $n2 = (string)arr($_POST, 'new2', '');
    if (!password_verify($cur, $auth['hash'])) flash('Password lama salah.', 'err');
    elseif (strlen($n1) < 10) flash('Password baru minimal 10 karakter.', 'err');
    elseif ($n1 !== $n2) flash('Ulangi password baru tidak sama.', 'err');
    elseif (save_auth($auth['user'], $n1)) flash('Password diganti.');
    else flash('Gagal menyimpan password.', 'err');
    go('password');
}

render_admin($p, $sections);
exit;

/* ================= tampilan ================= */

function admin_head($title, $body_class = '') {
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Admin <?= e(arr(arr(site(), 'umum', array()), 'nama', '')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?>">
</head>
<body class="<?= e($body_class) ?>">
<?php
}

function render_auth($mode, $err) {
    $logo = arr(arr(site(), 'umum', array()), 'logo', '/img/logo.png');
    admin_head($mode === 'setup' ? 'Pasang admin' : 'Masuk', 'auth');
    ?>
<main class="auth-box">
  <img src="<?= e($logo) ?>" alt="" width="64" height="64">
  <?php if ($mode === 'setup'): ?>
  <h1>Pasang admin</h1>
  <p class="muted">Sekali saja. Buka <b>data/kode-setup.txt</b> di File Manager hosting, salin isinya ke kolom kode setup, lalu buat akun admin.</p>
  <?php else: ?>
  <h1>Masuk admin</h1>
  <?php endif; ?>
  <?php if ($err): ?><p class="alert err" role="alert"><?= e($err) ?></p><?php endif; ?>
  <form method="post" action="./?p=<?= $mode === 'setup' ? 'setup' : 'login' ?>">
    <?= csrf_field() ?>
    <?php if ($mode === 'setup'): ?>
    <label for="code">Kode setup</label><input id="code" name="code" autocomplete="off" required>
    <?php endif; ?>
    <label for="user">Nama pengguna</label><input id="user" name="user" autocomplete="username" required value="<?= e(arr($_POST, 'user', '')) ?>">
    <label for="pass">Password</label><input id="pass" name="pass" type="password" autocomplete="<?= $mode === 'setup' ? 'new-password' : 'current-password' ?>" required>
    <?php if ($mode === 'setup'): ?>
    <label for="pass2">Ulangi password</label><input id="pass2" name="pass2" type="password" autocomplete="new-password" required>
    <p class="help">Minimal 10 karakter.</p>
    <?php endif; ?>
    <button class="b primary" type="submit"><?= $mode === 'setup' ? 'Buat akun admin' : 'Masuk' ?></button>
  </form>
  <a class="muted small" href="/">Kembali ke situs</a>
</main>
</body></html>
<?php
}

function render_admin($p, $sections) {
    $titles = array('dash' => 'Beranda admin', 'artikel' => 'Artikel', 'artikel_edit' => 'Tulis artikel', 'media' => 'Gambar', 'riwayat' => 'Riwayat perubahan', 'password' => 'Ganti password');
    foreach ($sections as $k => $s) $titles[$k] = $s['judul'];
    if (!isset($titles[$p])) $p = 'dash';
    admin_head($titles[$p], 'app');
    $fl = arr($_SESSION, 'flash', null); unset($_SESSION['flash']);
    $menu = array(
        'Halaman utama' => array('hero' => 'Bagian atas', 'layanan' => 'Layanan', 'bahan' => 'Bahan', 'karya' => 'Karya', 'klien' => 'Klien', 'cara' => 'Cara pesan', 'kontak' => 'Kontak dan footer'),
        'Artikel' => array('artikel' => 'Semua artikel', 'artikel_edit' => 'Tulis artikel baru', 'artikel_set' => 'Pengaturan artikel'),
        'Lainnya' => array('umum' => 'Umum dan kontak', 'media' => 'Gambar', 'riwayat' => 'Riwayat perubahan', 'password' => 'Ganti password'),
    );
    ?>
<div class="shell">
  <aside class="side" id="side">
    <a class="sb-brand" href="./"><img src="<?= e(arr(arr(site(), 'umum', array()), 'logo', '/img/logo.png')) ?>" alt="" width="34" height="34"><span>Admin</span></a>
    <nav>
      <a class="<?= $p === 'dash' ? 'on' : '' ?>" href="./">Beranda admin</a>
      <?php foreach ($menu as $grp => $items): ?>
      <p class="grp"><?= e($grp) ?></p>
      <?php foreach ($items as $k => $label): $on = ($p === $k && !($k === 'artikel_edit' && isset($_GET['slug']))); ?><a class="<?= $on ? 'on' : '' ?>" href="./?p=<?= e($k) ?>"><?= e($label) ?></a><?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sb-foot">
      <a class="b ghost" href="/" target="_blank" rel="noopener">Lihat situs</a>
      <form method="post" action="./?p=logout"><?= csrf_field() ?><button class="b ghost" type="submit">Keluar</button></form>
    </div>
  </aside>
  <main class="main">
    <header class="top"><button class="b ghost menu-btn" type="button" id="menuBtn" aria-controls="side" aria-expanded="false">Menu</button><h1><?= e($titles[$p]) ?></h1></header>
    <?php if ($fl): ?><p class="alert <?= e($fl[1]) ?>" role="status"><?= e($fl[0]) ?></p><?php endif; ?>
    <?php
    if (isset($sections[$p])) render_section($p, $sections[$p]);
    elseif ($p === 'artikel') render_articles();
    elseif ($p === 'artikel_edit') render_article_edit();
    elseif ($p === 'media') render_media();
    elseif ($p === 'riwayat') render_history();
    elseif ($p === 'password') render_password();
    else render_dash($sections);
    ?>
  </main>
</div>
<?php render_picker(); ?>
<script>window.PCL={csrf:<?= json_encode(csrf()) ?>};</script>
<script src="admin.js?v=<?= @filemtime(__DIR__ . '/admin.js') ?>"></script>
</body></html>
<?php
}

function render_dash($sections) {
    $arts = all_articles(false);
    $pub = 0; foreach ($arts as $a) if (!empty($a['terbit'])) $pub++;
    ?>
<p class="lead">Pilih bagian yang mau diubah. Setiap simpan langsung tampil di situs, dan versi sebelumnya tersimpan di Riwayat perubahan.</p>
<div class="cards">
  <a class="card accent" href="./?p=artikel_edit"><b>Tulis artikel baru</b><span><?= $pub ?> artikel tampil, <?= count($arts) - $pub ?> draf.</span></a>
  <?php foreach ($sections as $k => $s): ?>
  <a class="card" href="./?p=<?= e($k) ?>"><b><?= e($s['judul']) ?></b><span><?= e($s['ket']) ?></span></a>
  <?php endforeach; ?>
  <a class="card" href="./?p=media"><b>Gambar</b><span>Upload foto baru dan lihat semua gambar situs.</span></a>
</div>
<?php
}

function render_section($p, $sec) {
    $data = read_json(PCL_DATA . '/site.json');
    ?>
<p class="lead"><?= e($sec['ket']) ?></p>
<form method="post" action="./?p=<?= e($p) ?>" class="form" id="mainForm">
  <?= csrf_field() ?>
  <?php foreach ($sec['fields'] as $f) render_field($f, get_path($data, $f[0]), 'f[' . str_replace('.', '][', $f[0]) . ']'); ?>
  <div class="savebar"><button class="b primary" type="submit">Simpan perubahan</button><span class="dirty" hidden>Ada perubahan yang belum disimpan.</span></div>
</form>
<?php
}

function render_field($f, $val, $name, $idp = '') {
    $type = $f[1]; $label = $f[2]; $opt = isset($f[3]) ? $f[3] : array();
    $id = 'fld' . substr(md5($name . $idp), 0, 10) . ($idp === '__ID__' ? '__ID__' : '');
    $help = arr($opt, 'help', '');
    echo '<div class="fld fld-' . e($type) . '">';
    if ($type === 'text') {
        echo '<label for="' . $id . '">' . e($label) . '</label><input id="' . $id . '" name="' . e($name) . '" value="' . e($val) . '">';
    } elseif ($type === 'textarea') {
        echo '<label for="' . $id . '">' . e($label) . '</label><textarea id="' . $id . '" name="' . e($name) . '" rows="3">' . e($val) . '</textarea>';
    } elseif ($type === 'select') {
        $c = resolve_choices(arr($opt, 'choices', array()));
        echo '<label for="' . $id . '">' . e($label) . '</label><select id="' . $id . '" name="' . e($name) . '">';
        foreach ($c as $k => $v) echo '<option value="' . e($k) . '"' . ((string)$k === (string)$val ? ' selected' : '') . '>' . e($v) . '</option>';
        echo '</select>';
    } elseif ($type === 'image') {
        echo '<span class="lbl">' . e($label) . '</span>';
        img_widget($name, (string)$val);
    } elseif ($type === 'images') {
        echo '<span class="lbl">' . e($label) . '</span><div class="imgs" data-name="' . e($name) . '[]">';
        foreach ((array)$val as $src) echo '<div class="imgs-it" draggable="true"><img src="' . e($src) . '" alt=""><input type="hidden" name="' . e($name) . '[]" value="' . e($src) . '"><button type="button" class="x" aria-label="Hapus gambar ini">×</button></div>';
        echo '<button type="button" class="imgs-add" data-pick="multi">+ Tambah foto</button></div>';
    } elseif ($type === 'strings') {
        echo '<span class="lbl">' . e($label) . '</span><div class="strs" data-name="' . e($name) . '[]">';
        foreach ((array)$val as $s) echo '<div class="str-it"><input name="' . e($name) . '[]" value="' . e($s) . '" aria-label="' . e($label) . '"><button type="button" class="x" aria-label="Hapus">×</button></div>';
        echo '<button type="button" class="b small str-add">+ Tambah</button></div>';
    } elseif ($type === 'list') {
        $rows = array_values((array)$val);
        $max = (int)arr($opt, 'max', 0);
        echo '<span class="lbl">' . e($label) . '</span><div class="list" data-max="' . $max . '"' . (arr($opt, 'add_top', false) ? ' data-top="1"' : '') . '>';
        if (arr($opt, 'add_top', false)) echo '<button type="button" class="b small list-add">+ Tambah ' . e(strtolower(arr($opt, 'item_label', 'baris'))) . ' di atas</button>';
        foreach ($rows as $i => $row) list_row($opt, $name, 'r' . $i, $row);
        echo '<template>';
        list_row($opt, $name, '__ID__', array());
        echo '</template>';
        if (!arr($opt, 'add_top', false)) echo '<button type="button" class="b small list-add">+ Tambah ' . e(strtolower(arr($opt, 'item_label', 'baris'))) . '</button>';
        echo '</div>';
    }
    if ($help) echo '<p class="help">' . e($help) . '</p>';
    echo '</div>';
}

function list_row($opt, $name, $key, $row) {
    $tf = arr($opt, 'title_field', '');
    $title = $tf ? arr($row, $tf, '') : '';
    $open = $key === '__ID__' ? ' open' : '';
    $thumb = '';
    foreach ($opt['fields'] as $sf) {
        $v = arr($row, $sf[0], '');
        if ($sf[1] === 'image' && $v) { $thumb = $v; break; }
        if ($sf[1] === 'images' && is_array($v) && $v) { $thumb = $v[0]; break; }
    }
    echo '<details class="row"' . $open . '><summary><span class="handle" aria-hidden="true">⋮⋮</span>' . ($thumb ? '<img class="rthumb" src="' . e($thumb) . '" alt="">' : '') . '<span class="rt" data-tf="' . e($tf) . '">' . e($title !== '' ? $title : arr($opt, 'item_label', 'Baris')) . '</span>';
    echo '<span class="row-act"><button type="button" class="up" aria-label="Naikkan">↑</button><button type="button" class="down" aria-label="Turunkan">↓</button><button type="button" class="del" aria-label="Hapus">Hapus</button></span></summary><div class="row-body">';
    foreach ($opt['fields'] as $sf) render_field($sf, arr($row, $sf[0], $sf[1] === 'images' ? array() : ''), $name . '[' . $key . '][' . $sf[0] . ']', $key);
    echo '</div></details>';
}

function img_widget($name, $val) {
    echo '<div class="img1"><div class="img1-prev">' . ($val ? '<img src="' . e($val) . '" alt="">' : '<span>Belum ada gambar</span>') . '</div>';
    echo '<input type="hidden" name="' . e($name) . '" value="' . e($val) . '">';
    echo '<div class="img1-act"><button type="button" class="b small" data-pick="one">Pilih atau upload</button><button type="button" class="b small ghost img1-clear"' . ($val ? '' : ' hidden') . '>Kosongkan</button></div></div>';
}

function render_articles() {
    $arts = all_articles(false);
    ?>
<p class="lead">Artikel membantu situsmu muncul di Google. Artikel draf tidak tampil di situs.</p>
<p><a class="b primary" href="./?p=artikel_edit">Tulis artikel baru</a></p>
<?php if (!$arts): ?>
<p class="empty">Belum ada artikel.</p>
<?php else: ?>
<table class="tbl">
  <thead><tr><th>Judul</th><th>Tanggal</th><th>Status</th><th><span class="sr">Aksi</span></th></tr></thead>
  <tbody>
  <?php foreach ($arts as $a): ?>
    <tr>
      <td><a href="./?p=artikel_edit&amp;slug=<?= e(rawurlencode($a['slug'])) ?>"><?= e(arr($a, 'judul', '')) ?></a></td>
      <td><?= tanggal_id(arr($a, 'tanggal', '')) ?></td>
      <td><span class="pill <?= !empty($a['terbit']) ? 'on' : '' ?>"><?= !empty($a['terbit']) ? 'Tampil' : 'Draf' ?></span></td>
      <td class="acts"><?php if (!empty($a['terbit'])): ?><a href="/artikel/<?= e($a['slug']) ?>" target="_blank" rel="noopener">Lihat</a><?php endif; ?>
        <form method="post" action="./?p=artikel_hapus" data-confirm="Hapus artikel &quot;<?= e(arr($a, 'judul', '')) ?>&quot;? Tidak bisa dibatalkan."><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($a['slug']) ?>"><button class="linkbtn danger" type="submit">Hapus</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif;
}

function render_article_edit() {
    $slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
    $a = $slug !== '' ? load_article($slug) : null;
    if ($slug !== '' && !$a) { echo '<p class="alert err">Artikel tidak ditemukan.</p>'; return; }
    $a = $a ?: array('judul' => '', 'slug' => '', 'tanggal' => date('Y-m-d'), 'ringkasan' => '', 'sampul' => '', 'isi' => '', 'terbit' => false, 'judul_seo' => '', 'deskripsi_seo' => '');
    ?>
<form method="post" action="./?p=artikel_simpan" class="form art-form" id="mainForm">
  <?= csrf_field() ?>
  <input type="hidden" name="old_slug" value="<?= e(arr($a, 'slug', '')) ?>">
  <div class="fld"><label for="aj">Judul</label><input id="aj" name="judul" value="<?= e(arr($a, 'judul', '')) ?>" required></div>
  <div class="fld"><label for="as">Alamat artikel</label><div class="slugrow"><span>/artikel/</span><input id="as" name="slug" value="<?= e(arr($a, 'slug', '')) ?>" placeholder="terisi otomatis dari judul" data-auto="<?= arr($a, 'slug', '') === '' ? '1' : '0' ?>"></div><p class="help">Huruf kecil dan tanda hubung. Sebaiknya tidak diubah setelah artikel tampil.</p></div>
  <div class="two">
    <div class="fld"><label for="at">Tanggal</label><input id="at" type="date" name="tanggal" value="<?= e(arr($a, 'tanggal', date('Y-m-d'))) ?>"></div>
    <div class="fld check"><label><input type="checkbox" name="terbit" value="1"<?= !empty($a['terbit']) ? ' checked' : '' ?>> Tampilkan di situs</label><p class="help">Biarkan kosong untuk menyimpan sebagai draf.</p></div>
  </div>
  <div class="fld"><label for="ar">Ringkasan</label><textarea id="ar" name="ringkasan" rows="2"><?= e(arr($a, 'ringkasan', '')) ?></textarea><p class="help">Satu atau dua kalimat. Tampil di daftar artikel.</p></div>
  <div class="fld"><span class="lbl">Gambar sampul</span><?php img_widget('sampul', arr($a, 'sampul', '')); ?></div>
  <div class="fld"><span class="lbl">Isi artikel</span><div id="editor" class="editor"><?= clean_html(arr($a, 'isi', '')) ?></div><textarea name="isi" id="isi" hidden><?= e(arr($a, 'isi', '')) ?></textarea></div>
  <details class="seo"><summary>Pengaturan Google (opsional)</summary>
    <div class="fld"><label for="ajs">Judul di Google</label><input id="ajs" name="judul_seo" value="<?= e(arr($a, 'judul_seo', '')) ?>" placeholder="Sama dengan judul kalau dikosongkan"></div>
    <div class="fld"><label for="ads">Deskripsi di Google</label><textarea id="ads" name="deskripsi_seo" rows="2" placeholder="Diambil dari ringkasan kalau dikosongkan"><?= e(arr($a, 'deskripsi_seo', '')) ?></textarea></div>
  </details>
  <div class="savebar"><button class="b primary" type="submit">Simpan artikel</button><?php if (!empty($a['terbit']) && arr($a, 'slug', '') !== ''): ?><a class="b ghost" href="/artikel/<?= e($a['slug']) ?>" target="_blank" rel="noopener">Lihat di situs</a><?php endif; ?><span class="dirty" hidden>Ada perubahan yang belum disimpan.</span></div>
</form>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<?php
}

function render_media() {
    $list = media_list();
    ?>
<p class="lead">Semua gambar situs. Gambar dari folder uploads bisa dihapus; gambar bawaan di folder img tidak.</p>
<div class="upzone"><input type="file" id="mediaUp" accept="image/jpeg,image/png,image/webp,image/gif" multiple><label for="mediaUp" class="b primary">Upload gambar</label><span class="muted">JPG, PNG, WebP, atau GIF. Foto besar otomatis dikecilkan.</span></div>
<div class="mgrid">
<?php foreach ($list as $m): ?>
  <figure><img src="<?= e($m['src']) ?>" alt="" loading="lazy"><figcaption><span title="<?= e($m['src']) ?>"><?= e(basename($m['src'])) ?></span>
  <?php if ($m['up']): ?><form method="post" action="./?p=media_hapus" data-confirm="Hapus gambar ini? Bagian situs yang memakainya akan kosong."><?= csrf_field() ?><input type="hidden" name="src" value="<?= e($m['src']) ?>"><button class="linkbtn danger" type="submit">Hapus</button></form><?php endif; ?></figcaption></figure>
<?php endforeach; ?>
</div>
<?php
}

function render_history() {
    $files = glob(PCL_DATA . '/backup/site-*.json') ?: array();
    rsort($files);
    ?>
<p class="lead">Setiap kali kamu menyimpan, isi sebelumnya disimpan di sini (30 terakhir). Pulihkan kalau ada yang salah ubah. Artikel tidak termasuk.</p>
<?php if (!$files): ?><p class="empty">Belum ada riwayat. Riwayat muncul setelah kamu menyimpan perubahan pertama.</p><?php else: ?>
<table class="tbl"><thead><tr><th>Disimpan</th><th><span class="sr">Aksi</span></th></tr></thead><tbody>
<?php foreach ($files as $f): $b = basename($f); $t = filemtime($f); ?>
<tr><td><?= tanggal_id(date('Y-m-d', $t)) ?>, <?= date('H:i', $t) ?></td><td class="acts"><form method="post" action="./?p=pulihkan" data-confirm="Kembalikan isi situs ke versi ini? Isi sekarang tetap tersimpan di riwayat."><?= csrf_field() ?><input type="hidden" name="file" value="<?= e($b) ?>"><button class="linkbtn" type="submit">Pulihkan versi ini</button></form></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif;
}

function render_password() {
    ?>
<form method="post" action="./?p=password" class="form narrow">
  <?= csrf_field() ?>
  <div class="fld"><label for="pc">Password lama</label><input id="pc" type="password" name="cur" autocomplete="current-password" required></div>
  <div class="fld"><label for="pn">Password baru</label><input id="pn" type="password" name="new1" autocomplete="new-password" required><p class="help">Minimal 10 karakter.</p></div>
  <div class="fld"><label for="pn2">Ulangi password baru</label><input id="pn2" type="password" name="new2" autocomplete="new-password" required></div>
  <div class="savebar"><button class="b primary" type="submit">Ganti password</button></div>
</form>
<?php
}

function render_picker() {
    ?>
<dialog id="picker" class="picker" aria-labelledby="pkT">
  <div class="pk-head"><h2 id="pkT">Pilih gambar</h2><button type="button" class="b ghost small" data-close>Tutup</button></div>
  <div class="upzone"><input type="file" id="pkUp" accept="image/jpeg,image/png,image/webp,image/gif" multiple><label for="pkUp" class="b primary small">Upload dari komputer</label><span class="muted pk-status" aria-live="polite"></span></div>
  <div class="pk-grid" id="pkGrid"><p class="muted">Memuat gambar…</p></div>
  <div class="pk-foot"><span class="muted pk-sel"></span><button type="button" class="b primary" id="pkOk" disabled>Pakai gambar</button></div>
</dialog>
<?php
}

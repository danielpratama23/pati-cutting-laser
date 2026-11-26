<?php
/* Apa saja yang bisa diubah di admin. Tiap field: [jalur di site.json, jenis, label, opsi].
   Jenis: text, textarea, image, images, select, strings, list. */

function admin_sections() {
    $icons = icon_choices();
    return array(
        'umum' => array('judul' => 'Umum dan kontak', 'ket' => 'Nama usaha, logo, nomor WhatsApp, media sosial, dan teks yang tampil di Google.', 'fields' => array(
            array('umum.nama', 'text', 'Nama usaha'),
            array('umum.logo', 'image', 'Logo (bulat, persegi)'),
            array('kontak.wa_nomor', 'text', 'Nomor WhatsApp', array('help' => 'Pakai format internasional tanpa + dan spasi, contoh 6281325418181.')),
            array('kontak.wa_tampil', 'text', 'Nomor WhatsApp yang ditampilkan', array('help' => 'Contoh 0813 2541 8181.')),
            array('kontak.pesan_order', 'text', 'Pesan otomatis tombol pesan', array('help' => 'Teks yang langsung terisi di WhatsApp pelanggan.')),
            array('kontak.instagram_url', 'text', 'Link Instagram'),
            array('kontak.instagram_nama', 'text', 'Nama akun Instagram'),
            array('kontak.facebook_url', 'text', 'Link Facebook'),
            array('kontak.facebook_nama', 'text', 'Nama halaman Facebook'),
            array('kontak.lokasi', 'text', 'Lokasi'),
            array('umum.judul_seo', 'text', 'Judul halaman di Google', array('help' => 'Paling bagus 50–60 karakter.')),
            array('umum.deskripsi_seo', 'textarea', 'Deskripsi di Google', array('help' => 'Paling bagus 120–155 karakter.')),
        )),
        'hero' => array('judul' => 'Bagian atas', 'ket' => 'Judul besar, kalimat pembuka, tombol, dan tiga foto melayang.', 'fields' => array(
            array('hero.pembuka', 'text', 'Teks kecil di atas judul'),
            array('hero.judul', 'text', 'Judul besar', array('help' => 'Kalau isinya tetap "Laser Cutting", judul tampil dengan animasi laser. Teks lain tampil sebagai judul biasa.')),
            array('hero.deskripsi', 'textarea', 'Kalimat pembuka'),
            array('hero.tombol_utama', 'text', 'Tombol WhatsApp'),
            array('hero.tombol_kedua', 'text', 'Tombol ke bagian karya'),
            array('hero.bukti', 'text', 'Kalimat kepercayaan', array('help' => 'Kosongkan untuk menyembunyikan.')),
            array('hero.foto', 'list', 'Foto melayang (maksimal 3)', array('max' => 3, 'item_label' => 'Foto', 'fields' => array(
                array('gambar', 'image', 'Gambar'),
                array('keterangan', 'text', 'Label di foto', array('help' => 'Hanya tampil di foto pertama dan kedua.')),
                array('alt', 'text', 'Deskripsi gambar untuk Google'),
            ))),
            array('hero.teks_berjalan', 'strings', 'Teks berjalan di bawah'),
        )),
        'layanan' => array('judul' => 'Layanan', 'ket' => 'Kotak-kotak produk. Tiap kotak membuka WhatsApp dengan pesan yang kamu isi.', 'fields' => array(
            array('layanan.judul', 'text', 'Judul bagian'),
            array('layanan.intro', 'textarea', 'Teks pengantar'),
            array('layanan.label_tombol', 'text', 'Teks tombol di tiap kotak'),
            array('layanan.item', 'list', 'Daftar layanan', array('item_label' => 'Layanan', 'title_field' => 'judul', 'fields' => array(
                array('ikon', 'select', 'Ikon', array('choices' => $icons)),
                array('judul', 'text', 'Nama layanan'),
                array('teks', 'textarea', 'Keterangan'),
                array('pesan_wa', 'text', 'Pesan WhatsApp otomatis'),
            ))),
        )),
        'bahan' => array('judul' => 'Bahan', 'ket' => 'Tiga panel bahan. Teksturnya tetap, teksnya bisa diubah.', 'fields' => array(
            array('bahan.judul', 'text', 'Judul bagian'),
            array('bahan.intro', 'textarea', 'Teks pengantar'),
            array('bahan.akrilik.nama', 'text', 'Panel 1: nama'),
            array('bahan.akrilik.teks', 'textarea', 'Panel 1: keterangan'),
            array('bahan.kayu.nama', 'text', 'Panel 2: nama'),
            array('bahan.kayu.teks', 'textarea', 'Panel 2: keterangan'),
            array('bahan.kulit.nama', 'text', 'Panel 3: nama'),
            array('bahan.kulit.teks', 'textarea', 'Panel 3: keterangan'),
        )),
        'karya' => array('judul' => 'Karya', 'ket' => 'Galeri hasil pesanan. Yang paling atas tampil paling depan.', 'fields' => array(
            array('karya.judul', 'text', 'Judul bagian'),
            array('karya.intro', 'textarea', 'Teks pengantar'),
            array('karya.item', 'list', 'Daftar karya', array('item_label' => 'Karya', 'title_field' => 'judul', 'add_top' => true, 'fields' => array(
                array('judul', 'text', 'Judul'),
                array('kategori', 'select', 'Kategori', array('choices' => 'karya_kategori')),
                array('tanggal', 'text', 'Tanggal', array('help' => 'Contoh 12 Nov 2026.')),
                array('deskripsi', 'textarea', 'Keterangan'),
                array('klien', 'text', 'Untuk klien (boleh kosong)'),
                array('foto', 'images', 'Foto (foto pertama jadi sampul)'),
                array('link', 'text', 'Link postingan Instagram (boleh kosong)'),
            ))),
            array('karya.kategori', 'list', 'Kategori filter', array('item_label' => 'Kategori', 'title_field' => 'label', 'fields' => array(
                array('kode', 'text', 'Kode (huruf kecil, tanpa spasi)'),
                array('label', 'text', 'Nama yang tampil'),
            ))),
            array('karya.ajakan_judul', 'text', 'Kotak ajakan: judul'),
            array('karya.ajakan_teks', 'textarea', 'Kotak ajakan: teks'),
            array('karya.ajakan_tombol', 'text', 'Kotak ajakan: tombol'),
            array('karya.ajakan_pesan_wa', 'text', 'Kotak ajakan: pesan WhatsApp otomatis'),
        )),
        'klien' => array('judul' => 'Klien', 'ket' => 'Kartu klien dengan foto pesanan mereka.', 'fields' => array(
            array('klien.judul', 'text', 'Judul bagian'),
            array('klien.intro', 'textarea', 'Teks pengantar'),
            array('klien.item', 'list', 'Daftar klien', array('item_label' => 'Klien', 'title_field' => 'nama', 'fields' => array(
                array('nama', 'text', 'Nama klien'),
                array('teks', 'textarea', 'Keterangan pesanan'),
                array('gambar', 'image', 'Foto'),
                array('url', 'text', 'Link situs klien (boleh kosong)'),
            ))),
        )),
        'cara' => array('judul' => 'Cara pesan', 'ket' => 'Langkah-langkah memesan di samping video.', 'fields' => array(
            array('cara_pesan.judul', 'text', 'Judul bagian'),
            array('cara_pesan.keterangan_video', 'text', 'Keterangan video'),
            array('cara_pesan.langkah', 'list', 'Langkah', array('item_label' => 'Langkah', 'title_field' => 'judul', 'fields' => array(
                array('ikon', 'select', 'Ikon', array('choices' => $icons)),
                array('judul', 'text', 'Judul langkah'),
                array('teks', 'textarea', 'Keterangan'),
            ))),
        )),
        'kontak' => array('judul' => 'Kontak dan footer', 'ket' => 'Kotak hijau ajakan di bawah, dan baris paling bawah situs.', 'fields' => array(
            array('kontak_bagian.judul', 'text', 'Judul kotak kontak'),
            array('kontak_bagian.teks', 'textarea', 'Teks kotak kontak'),
            array('kontak_bagian.tombol', 'text', 'Tombol kotak kontak'),
            array('footer.hak_cipta', 'text', 'Teks hak cipta di footer'),
            array('footer.link', 'list', 'Link di footer', array('item_label' => 'Link', 'title_field' => 'label', 'fields' => array(
                array('label', 'text', 'Teks link'),
                array('url', 'text', 'Alamat link'),
            ))),
        )),
        'artikel_set' => array('judul' => 'Pengaturan artikel', 'ket' => 'Judul halaman artikel dan berapa artikel tampil di beranda.', 'fields' => array(
            array('artikel.judul', 'text', 'Judul halaman artikel'),
            array('artikel.intro', 'textarea', 'Teks pengantar'),
            array('artikel.tampil_di_beranda', 'select', 'Artikel terbaru di beranda', array('choices' => array('0' => 'Tidak tampil', '3' => '3 artikel', '6' => '6 artikel'))),
        )),
    );
}

function resolve_choices($c) {
    if (is_array($c)) return $c;
    if ($c === 'karya_kategori') {
        $out = array();
        foreach ((array)arr(arr(site(), 'karya', array()), 'kategori', array()) as $k) {
            $kode = arr($k, 'kode', '');
            if ($kode !== '') $out[$kode] = arr($k, 'label', $kode);
        }
        return $out;
    }
    return array();
}

(function () {
'use strict';
var d = document;
var $ = function (s, c) { return (c || d).querySelector(s); };
var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };
var uid = 0;

/* ---------- menu di HP ---------- */
var mb = $('#menuBtn'), side = $('#side');
if (mb && side) mb.addEventListener('click', function () { var on = !side.classList.contains('on'); side.classList.toggle('on', on); mb.setAttribute('aria-expanded', on ? 'true' : 'false'); });

/* ---------- konfirmasi ---------- */
d.addEventListener('submit', function (e) {
  var f = e.target;
  if (f.dataset && f.dataset.confirm && !window.confirm(f.dataset.confirm)) e.preventDefault();
}, true);

/* ---------- tanda belum disimpan ---------- */
var form = $('#mainForm'), dirty = false;
function markDirty() { if (!form) return; dirty = true; var s = $('.dirty', form); if (s) s.hidden = false; }
if (form) {
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
}

/* ---------- daftar berulang ---------- */
function listCount(list) { return $$(':scope > details.row', list).length; }
function updateMax(list) {
  var max = parseInt(list.dataset.max || '0', 10), btn = $(':scope > .list-add', list);
  if (btn) btn.hidden = max > 0 && listCount(list) >= max;
}
d.addEventListener('click', function (e) {
  var t = e.target;
  var add = t.closest('.list-add');
  if (add) {
    var list = add.parentNode, tpl = $(':scope > template', list);
    var html = tpl.innerHTML.split('__ID__').join('n' + Date.now().toString(36) + (uid++));
    var wrap = d.createElement('div'); wrap.innerHTML = html;
    var row = wrap.firstElementChild;
    if (list.dataset.top === '1') list.insertBefore(row, add.nextSibling); else list.insertBefore(row, add);
    updateMax(list); markDirty();
    var first = $('input:not([type=hidden]),textarea,select', row); if (first) first.focus();
    return;
  }
  var row = t.closest('details.row');
  if (row && t.closest('.row-act')) {
    e.preventDefault();
    var lst = row.parentNode;
    if (t.closest('.del')) { if (window.confirm('Hapus baris ini?')) { row.remove(); updateMax(lst); markDirty(); } }
    else if (t.closest('.up') && row.previousElementSibling && row.previousElementSibling.matches('details.row')) { lst.insertBefore(row, row.previousElementSibling); markDirty(); }
    else if (t.closest('.down') && row.nextElementSibling && row.nextElementSibling.matches('details.row')) { lst.insertBefore(row.nextElementSibling, row); markDirty(); }
    return;
  }
  var sa = t.closest('.str-add');
  if (sa) {
    var box = sa.parentNode, it = d.createElement('div');
    it.className = 'str-it';
    it.innerHTML = '<input aria-label="Teks"><button type="button" class="x" aria-label="Hapus">×</button>';
    $('input', it).name = box.dataset.name;
    box.insertBefore(it, sa); $('input', it).focus(); markDirty();
    return;
  }
  if (t.closest('.str-it .x')) { t.closest('.str-it').remove(); markDirty(); return; }
  if (t.closest('.imgs-it .x')) { t.closest('.imgs-it').remove(); markDirty(); return; }
  if (t.closest('.img1-clear')) {
    var w = t.closest('.img1'); $('input[type=hidden]', w).value = '';
    $('.img1-prev', w).innerHTML = '<span>Belum ada gambar</span>'; t.closest('.img1-clear').hidden = true; markDirty();
    return;
  }
  var pk = t.closest('[data-pick]');
  if (pk) {
    var multi = pk.dataset.pick === 'multi';
    openPicker(multi, function (srcs) {
      if (multi) {
        var box2 = pk.parentNode;
        srcs.forEach(function (src) {
          var it2 = d.createElement('div'); it2.className = 'imgs-it'; it2.draggable = true;
          it2.innerHTML = '<img alt=""><input type="hidden"><button type="button" class="x" aria-label="Hapus gambar ini">×</button>';
          $('img', it2).src = src; var inp = $('input', it2); inp.name = box2.dataset.name; inp.value = src;
          box2.insertBefore(it2, pk);
        });
      } else {
        var w2 = pk.closest('.img1');
        $('input[type=hidden]', w2).value = srcs[0];
        var prev = $('.img1-prev', w2); prev.innerHTML = '<img alt="">'; $('img', prev).src = srcs[0];
        $('.img1-clear', w2).hidden = false;
      }
      markDirty();
    });
  }
});
$$('.list').forEach(updateMax);

/* judul baris ikut berubah */
d.addEventListener('input', function (e) {
  var row = e.target.closest('details.row'); if (!row) return;
  var rt = $('.rt', row), tf = rt && rt.dataset.tf;
  if (tf && e.target.name && e.target.name.slice(-(tf.length + 2)) === '[' + tf + ']') rt.textContent = e.target.value || 'Baris';
});

/* urutkan foto dengan seret */
var dragEl = null;
d.addEventListener('dragstart', function (e) { var it = e.target.closest && e.target.closest('.imgs-it'); if (it) { dragEl = it; it.classList.add('drag'); e.dataTransfer.effectAllowed = 'move'; } });
d.addEventListener('dragend', function () { if (dragEl) dragEl.classList.remove('drag'); dragEl = null; });
d.addEventListener('dragover', function (e) {
  if (!dragEl) return; var over = e.target.closest && e.target.closest('.imgs-it');
  if (!over || over === dragEl || over.parentNode !== dragEl.parentNode) return;
  e.preventDefault();
  var r = over.getBoundingClientRect();
  over.parentNode.insertBefore(dragEl, (e.clientX - r.left) > r.width / 2 ? over.nextSibling : over);
  markDirty();
});

/* ---------- upload ---------- */
function upload(file) {
  var fd = new FormData(); fd.append('file', file); fd.append('csrf', window.PCL.csrf);
  return fetch('./?p=upload', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .catch(function () { return { ok: false, error: 'Koneksi terputus. Coba lagi.' }; });
}
function uploadMany(files, status) {
  var list = Array.prototype.slice.call(files), done = [], errs = [];
  var next = function () {
    if (!list.length) return Promise.resolve({ done: done, errs: errs });
    var f = list.shift();
    if (status) status.textContent = 'Mengupload ' + f.name + '…';
    return upload(f).then(function (r) { if (r.ok) done.push(r.src); else errs.push(f.name + ': ' + r.error); return next(); });
  };
  return next();
}
var mediaUp = $('#mediaUp');
if (mediaUp) mediaUp.addEventListener('change', function () {
  var lab = $('label[for=mediaUp]'); lab.textContent = 'Mengupload…';
  uploadMany(mediaUp.files).then(function (r) { if (r.errs.length) window.alert(r.errs.join('\n')); window.location.reload(); });
});

/* ---------- pemilih gambar ---------- */
var dlg = $('#picker'), grid = $('#pkGrid'), okBtn = $('#pkOk'), pkUp = $('#pkUp'), pkStatus = $('.pk-status'), pkSel = $('.pk-sel');
var pickMulti = false, pickCb = null, chosen = [];
function renderGrid(items) {
  grid.innerHTML = '';
  if (!items.length) { grid.innerHTML = '<p class="muted">Belum ada gambar. Upload dulu.</p>'; return; }
  items.forEach(function (m) {
    var b = d.createElement('button'); b.type = 'button'; b.className = 'pk-it'; b.dataset.src = m.src;
    b.innerHTML = '<img alt="" loading="lazy">'; $('img', b).src = m.src; b.title = m.src.split('/').pop();
    if (chosen.indexOf(m.src) >= 0) b.classList.add('on');
    grid.appendChild(b);
  });
}
function refreshSel() {
  okBtn.disabled = !chosen.length;
  pkSel.textContent = chosen.length ? (chosen.length + ' gambar dipilih') : '';
}
function loadMedia() {
  return fetch('./?p=media_json', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(renderGrid)
    .catch(function () { grid.innerHTML = '<p class="muted">Gagal memuat gambar. Tutup lalu coba lagi.</p>'; });
}
function openPicker(multi, cb) {
  pickMulti = multi; pickCb = cb; chosen = []; refreshSel();
  okBtn.textContent = multi ? 'Tambahkan gambar' : 'Pakai gambar';
  $('#pkT').textContent = multi ? 'Pilih satu atau beberapa gambar' : 'Pilih gambar';
  pkStatus.textContent = '';
  if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', '');
  loadMedia();
}
function closePicker() { if (dlg.close) dlg.close(); else dlg.removeAttribute('open'); }
if (dlg) {
  grid.addEventListener('click', function (e) {
    var b = e.target.closest('.pk-it'); if (!b) return;
    var src = b.dataset.src, i = chosen.indexOf(src);
    if (!pickMulti) { chosen = [src]; $$('.pk-it.on', grid).forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); }
    else if (i >= 0) { chosen.splice(i, 1); b.classList.remove('on'); }
    else { chosen.push(src); b.classList.add('on'); }
    refreshSel();
  });
  grid.addEventListener('dblclick', function (e) { if (!pickMulti && e.target.closest('.pk-it')) okBtn.click(); });
  okBtn.addEventListener('click', function () { if (!chosen.length) return; var c = chosen.slice(); closePicker(); if (pickCb) pickCb(c); });
  $('[data-close]', dlg).addEventListener('click', closePicker);
  pkUp.addEventListener('change', function () {
    uploadMany(pkUp.files, pkStatus).then(function (r) {
      pkStatus.textContent = r.errs.length ? r.errs.join(' · ') : (r.done.length ? r.done.length + ' gambar terupload dan dipilih.' : '');
      if (pickMulti) chosen = chosen.concat(r.done); else if (r.done.length) chosen = [r.done[r.done.length - 1]];
      refreshSel(); pkUp.value = ''; return loadMedia();
    });
  });
}

/* ---------- artikel ---------- */
var aj = $('#aj'), as = $('#as');
function slugify(s) {
  return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
}
if (aj && as && as.dataset.auto === '1') {
  var touched = false;
  as.addEventListener('input', function () { touched = true; });
  aj.addEventListener('input', function () { if (!touched) as.value = slugify(aj.value); });
}
var ed = $('#editor');
if (ed && window.Quill) {
  var q = new window.Quill(ed, {
    theme: 'snow',
    placeholder: 'Tulis isi artikel di sini…',
    modules: { toolbar: { container: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], ['link', 'blockquote'], [{ list: 'ordered' }, { list: 'bullet' }], ['image'], ['clean']],
      handlers: { image: function () { var range = q.getSelection(true); openPicker(false, function (srcs) { q.insertEmbed(range ? range.index : q.getLength(), 'image', srcs[0], 'user'); }); } } } }
  });
  q.on('text-change', markDirty);
  form.addEventListener('submit', function () { $('#isi').value = q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML; });
} else if (ed) {
  /* editor gagal dimuat: pakai kotak teks biasa */
  var ta = $('#isi'); ta.hidden = false; ta.rows = 16; ed.remove();
}
})();

(function(){
'use strict';
var d=document, root=d.documentElement;
var $=function(s,c){return (c||d).querySelector(s)};
var $$=function(s,c){return Array.prototype.slice.call((c||d).querySelectorAll(s))};
var mm=function(q){return !!(window.matchMedia&&window.matchMedia(q).matches)};
var reduce=mm('(prefers-reduced-motion: reduce)');
var fine=mm('(hover:hover) and (pointer:fine)');
var hasIO='IntersectionObserver' in window;
var EASE='cubic-bezier(.22,1,.36,1)';

/* ---- menu ---- */
var nav=$('#nav'), burger=$('#burger'), mmenu=$('#mmenu'), menuOpen=false;
function setMenu(on){menuOpen=on;mmenu.classList.toggle('on',on);burger.setAttribute('aria-expanded',on?'true':'false');burger.setAttribute('aria-label',on?'Tutup menu':'Buka menu');root.style.overflow=on?'hidden':'';if(on)nav.classList.remove('hide');}
burger.addEventListener('click',function(){setMenu(!menuOpen)});

/* ---- tautan dalam halaman ---- */
d.addEventListener('click',function(e){
  var a=e.target.closest('a[href^="#"]'); if(!a) return;
  var t=d.getElementById(a.getAttribute('href').slice(1)); if(!t) return;
  e.preventDefault(); if(menuOpen) setMenu(false);
  t.scrollIntoView({behavior:reduce?'auto':'smooth',block:'start'});
});

/* ---- indikator menu ---- */
var linksBox=$('.links'), ind=$('.ind'), navLinks=$$('.links a'), hovering=false;
function moveInd(a){ if(!a){ind.style.opacity=0;return} ind.style.opacity=1; ind.style.width=a.offsetWidth+'px'; ind.style.transform='translateX('+a.offsetLeft+'px)'; }
navLinks.forEach(function(a){a.addEventListener('pointerenter',function(){hovering=true;moveInd(a)})});
linksBox.addEventListener('pointerleave',function(){hovering=false;moveInd($('.links a.on'))});

/* ---- gulir: progres, menu, paralaks ---- */
var bar=$('.progress i'), hero=$('.hero'), stack=$('.stack'), secs=$$('main section[id]');
var lastY=window.scrollY, acc=0, ticking=false, activeId='';
function onScroll(){
  ticking=false;
  var y=window.scrollY, max=root.scrollHeight-window.innerHeight;
  bar.style.width=(max>0?Math.min(1,Math.max(0,y/max))*100:0)+'%'; bar.style.opacity=y>4?1:0;
  var dy=y-lastY; if((dy>0)!==(acc>0)) acc=0; acc+=dy; lastY=y;
  if(!menuOpen){ if(y<=420||acc<-40) nav.classList.remove('hide'); else if(acc>90) nav.classList.add('hide'); }
  if(!reduce){ var sr=stack.getBoundingClientRect(), vh=window.innerHeight; if(sr.bottom>-200&&sr.top<vh+200){ var v=(vh/2-(sr.top+sr.height/2))/vh; hero.style.setProperty('--sy',Math.max(-1,Math.min(1,v)).toFixed(3)); } }
  var cur='', line=window.innerHeight*.4;
  secs.forEach(function(s){ if(s.id!=='top'&&s.getBoundingClientRect().top<=line) cur=s.id });
  if(cur!==activeId){ activeId=cur; navLinks.forEach(function(a){a.classList.toggle('on',a.getAttribute('href')==='#'+cur)}); if(!hovering) moveInd($('.links a.on')); }
}
window.addEventListener('scroll',function(){ if(!ticking){ticking=true;requestAnimationFrame(onScroll)} },{passive:true});
onScroll();

/* ---- hero: paralaks kursor ---- */
if(fine&&!reduce){(function(){
  var tx=0,ty=0,cx=0,cy=0,gx=.72,gy=.28,tgx=.72,tgy=.28,raf=0;
  function loop(){
    cx+=(tx-cx)*.07; cy+=(ty-cy)*.07; gx+=(tgx-gx)*.09; gy+=(tgy-gy)*.09;
    hero.style.setProperty('--mx',cx.toFixed(4)); hero.style.setProperty('--my',cy.toFixed(4));
    hero.style.setProperty('--gx',(gx*100).toFixed(2)+'%'); hero.style.setProperty('--gy',(gy*100).toFixed(2)+'%');
    raf=(Math.abs(tx-cx)+Math.abs(ty-cy)+Math.abs(tgx-gx)+Math.abs(tgy-gy)>.002)?requestAnimationFrame(loop):0;
  }
  hero.addEventListener('pointermove',function(e){var r=hero.getBoundingClientRect(); tgx=(e.clientX-r.left)/r.width; tgy=(e.clientY-r.top)/r.height; tx=tgx-.5; ty=tgy-.5; if(!raf)raf=requestAnimationFrame(loop)});
  hero.addEventListener('pointerleave',function(){tx=0;ty=0;tgx=.72;tgy=.28;if(!raf)raf=requestAnimationFrame(loop)});
})();}

/* ---- hero: judul dipotong laser ---- */
(function(){
  var svg=$('#ltype'); if(!svg||reduce||!svg.querySelector('.c').getTotalLength) return;
  var spark=$('#spark'), segs=[], total=0;
  $$('.gl',svg).forEach(function(g){ var cs=$$('.c',g); cs.forEach(function(el,i){ var len=el.getTotalLength(); el.style.strokeDasharray=len+' '+len; el.style.strokeDashoffset=len; segs.push({el:el,len:len,g:g,last:i===cs.length-1,fin:false}); total+=len; }); });
  svg.classList.add('arm');
  var INTRO=4300, GAP=45, IDLE=24000, WAIT=1500, t=0;
  segs.forEach(function(s){ s.t0=t; s.dur=s.len/total*INTRO; t+=s.dur+GAP; });
  var introEnd=t, start=null, pausedAt=null, visible=true, running=false, idle=false, active=null;
  function place(s,p){ var pt=s.el.getPointAtLength(s.len*p); spark.setAttribute('transform','translate('+pt.x.toFixed(2)+' '+pt.y.toFixed(2)+')'); }
  function finish(s){ s.fin=true; s.el.style.strokeDashoffset=0; if(s.last) s.g.classList.add('done'); }
  function frame(ts){
    if(!visible){ running=false; pausedAt=ts; return; }
    if(start===null) start=ts+650;
    if(pausedAt!==null){ start+=ts-pausedAt; pausedAt=null; }
    var el=ts-start;
    if(el>=0&&el<introEnd){
      for(var i=0;i<segs.length;i++){ var s=segs[i]; if(s.fin) continue; var p=(el-s.t0)/s.dur; if(p<=0) break;
        if(p>=1){ finish(s); continue; }
        s.el.classList.add('on'); s.el.style.strokeDashoffset=s.len*(1-p); place(s,p); spark.style.opacity=1; }
    } else if(el>=introEnd){
      if(!idle){ idle=true; segs.forEach(function(s){ if(!s.fin) finish(s); s.el.classList.remove('on'); }); spark.style.opacity=0; }
      var it=el-introEnd-WAIT;
      if(it>=0){
        var lap=it%IDLE, a=0, curS=segs[segs.length-1], p2=1;
        for(var j=0;j<segs.length;j++){ var dd=segs[j].len/total*IDLE; if(lap<a+dd){ curS=segs[j]; p2=(lap-a)/dd; break; } a+=dd; }
        if(curS!==active){ if(active){ active.el.classList.remove('trail'); } active=curS; active.el.classList.add('trail'); }
        var head=curS.len*p2, tail=Math.max(0,head-Math.min(30,curS.len*.45));
        curS.el.style.strokeDasharray=(head-tail).toFixed(2)+' '+(curS.len+10).toFixed(2); curS.el.style.strokeDashoffset=(-tail).toFixed(2);
        place(curS,p2); spark.style.opacity=.9;
      }
    }
    requestAnimationFrame(frame);
  }
  function finishAll(){ if(idle) return; segs.forEach(function(s){ if(!s.fin) finish(s); s.el.classList.remove('on'); }); }
  if(d.hidden){ finishAll(); }
  setTimeout(finishAll, 7000);
  function run(){ if(!running){ running=true; requestAnimationFrame(frame); } }
  if(hasIO){ new IntersectionObserver(function(es){ visible=es[es.length-1].isIntersecting; if(visible) run(); }).observe(svg); }
  run();
})();

/* ---- muncul saat digulir (keadaan diam tetap terlihat) ---- */
if(hasIO&&!reduce){(function(){
  var first=true;
  var io=new IntersectionObserver(function(es){
    var k=0;
    es.forEach(function(e){ if(!e.isIntersecting) return; var el=e.target; io.unobserve(el); if(first) return;
      el.style.setProperty('--d',(k++*90)+'ms'); el.classList.add('rv-in');
      el.addEventListener('animationend',function(ev){ if(ev.target===el) el.classList.remove('rv-in'); });
    });
    first=false;
  },{rootMargin:'0px 0px 60px 0px'});
  $$('[data-rv]').forEach(function(el){io.observe(el)});
})();}

/* ---- bahan ---- */
var mats=$$('.mat');
function setMat(m){ mats.forEach(function(x){ var on=x===m; x.classList.toggle('on',on); x.setAttribute('aria-expanded',on?'true':'false'); }); }
mats.forEach(function(m){ m.addEventListener('click',function(){setMat(m)}); m.addEventListener('focus',function(){setMat(m)}); if(fine) m.addEventListener('pointerenter',function(){setMat(m)}); });

/* ---- karya: saring ---- */
var gal=$('#gallery'), items=$$('.item',gal), tile=$('#tile'), fbox=$('#filters'), find=$('.find',fbox), fbtns=$$('button',fbox);
function cols(){ return getComputedStyle(gal).gridTemplateColumns.split(' ').length; }
function fitTile(){ var n=items.filter(function(i){return !i.hidden}).length, c=cols(), r=n%c; tile.style.gridColumn='span '+(r===0?c:c-r); }
function moveFind(instant){ var b=$('button.on',fbox); if(instant) find.style.transition='none'; find.style.width=b.offsetWidth+'px'; find.style.height=b.offsetHeight+'px'; find.style.transform='translate('+b.offsetLeft+'px,'+b.offsetTop+'px)'; if(instant){ void find.offsetWidth; find.style.transition=''; } }
var runId=0;
function applyFilter(f){
  var run=++runId;
  var show=function(it){return f==='all'||it.dataset.cat===f};
  var all=items.concat([tile]);
  all.forEach(function(it){ if(it.getAnimations) it.getAnimations().forEach(function(a){a.cancel()}); it.classList.remove('rv-in'); });
  if(reduce||!gal.animate){ items.forEach(function(it){it.hidden=!show(it)}); fitTile(); return; }
  var first=new Map(), h0=gal.offsetHeight;
  all.forEach(function(it){ if(!it.hidden) first.set(it,it.getBoundingClientRect()); });
  var out=items.filter(function(it){return !it.hidden&&!show(it)});
  out.forEach(function(it){ it.animate([{opacity:1,transform:'none'},{opacity:0,transform:'scale(.93)'}],{duration:240,easing:'ease-in',fill:'forwards'}); });
  setTimeout(function(){
    if(run!==runId) return;
    out.forEach(function(it){ it.getAnimations().forEach(function(a){a.cancel()}); });
    items.forEach(function(it){it.hidden=!show(it)}); fitTile();
    var h1=gal.offsetHeight;
    if(h0!==h1) gal.animate([{height:h0+'px'},{height:h1+'px'}],{duration:800,easing:EASE});
    var k=0;
    items.forEach(function(it){ if(it.hidden) return; var a=first.get(it), b=it.getBoundingClientRect();
      if(a&&out.indexOf(it)<0){ var dx=a.left-b.left, dy=a.top-b.top; if(dx||dy) it.animate([{transform:'translate('+dx+'px,'+dy+'px)'},{transform:'none'}],{duration:800,easing:EASE}); }
      else { it.animate([{opacity:0,transform:'translateY(24px) scale(.95)'},{opacity:1,transform:'none'}],{duration:700,easing:EASE,delay:120+(k++)*60,fill:'backwards'}); }
    });
    tile.animate([{opacity:0,transform:'translateY(18px)'},{opacity:1,transform:'none'}],{duration:800,easing:EASE,delay:160,fill:'backwards'});
  }, out.length?230:0);
}
fbox.addEventListener('click',function(e){
  var b=e.target.closest('button'); if(!b||b.classList.contains('on')) return;
  fbtns.forEach(function(x){ var on=x===b; x.classList.toggle('on',on); x.setAttribute('aria-pressed',on?'true':'false'); });
  moveFind(); applyFilter(b.dataset.f);
});
fitTile(); moveFind(true);

/* ---- lightbox ---- */
var lb=$('#lb'), lbImg=$('#lbImg'), lbT=$('#lbT'), lbN=$('#lbN'), lbIg=$('#lbIg'), stage=$('.lb-stage',lb), slides=[], si=0, lastFocus=null, closeT=0;
function buildSlides(){ slides=[]; items.forEach(function(it){ if(it.hidden) return; var imgs=it.dataset.imgs.split(','); imgs.forEach(function(src,k){ slides.push({src:src,title:it.dataset.title,ig:it.dataset.ig,k:k+1,n:imgs.length,it:it}); }); }); }
function paint(){ var s=slides[si]; lbImg.src=s.src; lbImg.alt=s.title; lbT.textContent=s.title; lbN.textContent=s.n>1?('Foto '+s.k+' dari '+s.n):''; if(s.ig){ lbIg.href=s.ig; lbIg.hidden=false; } else { lbIg.removeAttribute('href'); lbIg.hidden=true; } var nx=slides[(si+1)%slides.length]; if(nx){ var im=new Image(); im.src=nx.src; } }
function ready(fn){ if(lbImg.decode) lbImg.decode().then(fn,fn); else fn(); }
function go(dir){
  si=(si+dir+slides.length)%slides.length;
  if(reduce||!lbImg.animate){ paint(); return; }
  lbImg.animate([{opacity:1,transform:'none'},{opacity:0,transform:'translateX('+(-dir*46)+'px)'}],{duration:190,easing:'ease-in',fill:'forwards'}).finished.then(function(){
    paint(); ready(function(){ lbImg.getAnimations().forEach(function(a){a.cancel()}); lbImg.animate([{opacity:0,transform:'translateX('+(dir*46)+'px)'},{opacity:1,transform:'none'}],{duration:560,easing:EASE}); });
  },function(){});
}
function openLB(it){ buildSlides(); si=0; for(var i=0;i<slides.length;i++){ if(slides[i].it===it){ si=i; break; } } lastFocus=d.activeElement; clearTimeout(closeT); lb.hidden=false; paint(); void lb.offsetWidth; lb.classList.add('on'); root.style.overflow='hidden'; $('.lb-x',lb).focus({preventScroll:true}); }
function closeLB(){ lb.classList.remove('on'); root.style.overflow=''; closeT=setTimeout(function(){lb.hidden=true},reduce?0:500); if(lastFocus&&lastFocus.focus) lastFocus.focus({preventScroll:true}); }
gal.addEventListener('click',function(e){ var b=e.target.closest('.ph'); if(b) openLB(b.closest('.item')); });
lb.addEventListener('click',function(e){ if(e.target.closest('.nx')) go(1); else if(e.target.closest('.pv')) go(-1); else if(e.target===lb||e.target.closest('.lb-x')) closeLB(); });
d.addEventListener('keydown',function(e){
  if(e.key==='Escape'&&menuOpen){ setMenu(false); return; }
  if(lb.hidden) return;
  if(e.key==='Escape') closeLB(); else if(e.key==='ArrowRight') go(1); else if(e.key==='ArrowLeft') go(-1);
  else if(e.key==='Tab'){ var f=$$('button,a[href]',lb), a=f[0], z=f[f.length-1]; if(e.shiftKey&&d.activeElement===a){ e.preventDefault(); z.focus(); } else if(!e.shiftKey&&d.activeElement===z){ e.preventDefault(); a.focus(); } }
});
var px=null;
stage.addEventListener('pointerdown',function(e){px=e.clientX});
stage.addEventListener('pointerup',function(e){ if(px===null) return; var dx=e.clientX-px; px=null; if(Math.abs(dx)>40) go(dx<0?1:-1); });
stage.addEventListener('pointercancel',function(){px=null});

/* ---- langkah pesan ---- */
(function(){
  var w=$('.stepw'); if(!w||reduce||!hasIO) return;
  var list=$('.steps',w), steps=$$('.step',w), rail=$('.rail i',w);
  function upd(){ var on=steps.filter(function(s){return s.classList.contains('on')}); var last=on[on.length-1]; rail.style.setProperty('--h',(last?last.offsetTop:0)+'px'); }
  list.classList.add('live'); upd();
  var q=0;
  var io=new IntersectionObserver(function(es){ es.forEach(function(e){ if(!e.isIntersecting) return; io.unobserve(e.target); var i=steps.indexOf(e.target);
    setTimeout(function(){ steps.forEach(function(s,k){ if(k<=i) s.classList.add('on') }); upd(); q=Math.max(0,q-1); }, (q++)*260); }); },{threshold:.85});
  steps.forEach(function(s){io.observe(s)});
  window.addEventListener('resize',upd);
})();

/* ---- video ---- */
(function(){
  var v=$('.vid video'), b=$('.vbtn'); if(!v) return; var userPaused=false;
  function sync(){ b.classList.toggle('paused',v.paused); b.setAttribute('aria-label',v.paused?'Putar video':'Jeda video'); }
  b.addEventListener('click',function(){ if(v.paused){ userPaused=false; var p=v.play(); if(p&&p.catch) p.catch(function(){}); } else { userPaused=true; v.pause(); } });
  v.addEventListener('play',sync); v.addEventListener('pause',sync);
  if(hasIO&&!reduce){ new IntersectionObserver(function(es){ var e=es[es.length-1]; if(e.isIntersecting){ if(!userPaused){ var p=v.play(); if(p&&p.catch) p.catch(function(){}); } } else v.pause(); },{threshold:.3}).observe(v); }
})();

/* ---- tombol magnet ---- */
if(fine&&!reduce){ $$('.mag').forEach(function(el){
  el.addEventListener('pointermove',function(e){ var r=el.getBoundingClientRect(); el.style.transform='translate('+((e.clientX-r.left-r.width/2)*.2).toFixed(1)+'px,'+((e.clientY-r.top-r.height/2)*.3).toFixed(1)+'px)'; });
  el.addEventListener('pointerleave',function(){ el.style.transform=''; });
}); }

/* ---- ukur ulang ---- */
var rt=0;
window.addEventListener('resize',function(){ clearTimeout(rt); rt=setTimeout(function(){ fitTile(); moveFind(true); moveInd($('.links a.on')); },120); });
if(d.fonts&&d.fonts.ready) d.fonts.ready.then(function(){ moveFind(true); moveInd($('.links a.on')); });
})();

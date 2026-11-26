(function(){
'use strict';
var d=document, root=d.documentElement;
var burger=d.getElementById('burger'), mmenu=d.getElementById('mmenu'), nav=d.getElementById('nav'), open=false;
function setMenu(on){open=on;mmenu.classList.toggle('on',on);burger.setAttribute('aria-expanded',on?'true':'false');burger.setAttribute('aria-label',on?'Tutup menu':'Buka menu');root.style.overflow=on?'hidden':'';}
if(burger) burger.addEventListener('click',function(){setMenu(!open)});
d.addEventListener('keydown',function(e){ if(e.key==='Escape'&&open) setMenu(false); });
var bar=d.querySelector('.progress i'), lastY=window.scrollY, acc=0, t=false;
function onScroll(){ t=false; var y=window.scrollY, max=root.scrollHeight-window.innerHeight;
  if(bar){ bar.style.width=(max>0?Math.min(1,Math.max(0,y/max))*100:0)+'%'; bar.style.opacity=y>4?1:0; }
  var dy=y-lastY; if((dy>0)!==(acc>0)) acc=0; acc+=dy; lastY=y;
  if(!open&&nav){ if(y<=200||acc<-40) nav.classList.remove('hide'); else if(acc>90) nav.classList.add('hide'); } }
window.addEventListener('scroll',function(){ if(!t){t=true;requestAnimationFrame(onScroll)} },{passive:true});
onScroll();
})();

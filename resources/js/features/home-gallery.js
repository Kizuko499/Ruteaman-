const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
const ROOT_SELECTOR='[data-home-gallery]';
export function mountHomeGallery(){
 const roots=[...document.querySelectorAll(ROOT_SELECTOR)];if(!roots.length)return;
 const reduce=matchMedia(uiText("(prefers-reduced-motion: reduce)"));const paused=new Map(roots.map(r=>[r.dataset.homeGallery,reduce.matches]));let timer,busy=false,delay=60000;
 function render(data){let nearest=60000;
  for(const root of roots){const g=data.groups[root.dataset.homeGallery];if(!g)continue;const buttons=[...root.querySelectorAll('.ra-project__shot')];const invalid=buttons.some(b=>!g.pool_tokens.includes(b.dataset.photoToken));
   const blocked=paused.get(g.key)||root.matches(':hover')||root.contains(document.activeElement);
   if(!blocked||invalid||root.dataset.galleryVersion!==String(g.version))g.frames.forEach((frame,i)=>{const b=buttons[i];if(!b)return;const img=b.querySelector('img');if(img.getAttribute('src')!==frame.url)img.src=frame.url;img.alt=frame.alt;b.dataset.photoToken=frame.token;b.setAttribute('aria-label',frame.alt);});
   root.dataset.galleryVersion=String(g.version);root.dataset.galleryMode=g.mode;root.dataset.galleryOrientation=g.orientation;const toggle=root.querySelector('[data-gallery-pause]');if(toggle){toggle.hidden=g.mode!=='rotate';toggle.textContent=paused.get(g.key)?uiText("Lanjutkan pergantian"):uiText("Jeda pergantian");toggle.setAttribute('aria-pressed',String(Boolean(paused.get(g.key))));}
   const note=root.querySelector('[data-gallery-note]');if(note)note.textContent=g.frames.some(f=>f.source==='verified')?uiText("Foto laporan terverifikasi dan koleksi ilustrasi. Kondisi dapat berubah."):uiText("Foto ilustrasi dari koleksi admin; bukan bukti kondisi terkini.");
   if(g.mode==='rotate'&&g.next_change_at){const ms=Date.parse(g.next_change_at)-Date.parse(data.server_time);nearest=Math.min(nearest,Math.max(1000,ms));}
  }delay=nearest;
 }
 async function refresh(){if(busy)return;busy=true;try{const response=await fetch(roots[0].dataset.galleryFeed,{headers:{Accept:'application/json'},cache:'no-store'});if(response.ok)render(await response.json());else delay=15000;}catch{delay=15000;}finally{busy=false;clearTimeout(timer);timer=setTimeout(()=>{if(document.hidden){delay=15000;timer=setTimeout(refresh,delay);}else refresh();},delay);}}
 roots.forEach(root=>{root.querySelector('[data-gallery-pause]')?.addEventListener('click',()=>{const key=root.dataset.homeGallery;paused.set(key,!paused.get(key));refresh();});root.querySelectorAll('.ra-project__shot img').forEach(img=>img.addEventListener('error',()=>{if(img.src!==new URL(root.dataset.galleryFallback,location.href).href)img.src=root.dataset.galleryFallback;}));});
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});reduce.addEventListener?.('change',()=>{if(reduce.matches)roots.forEach(root=>paused.set(root.dataset.homeGallery,true));refresh();});refresh();
}

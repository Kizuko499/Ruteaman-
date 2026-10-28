const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import {cardElement,photoFallback} from '../conditions/shared';
const FLASK = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg>';

// One drift-free timeout; pause reasons compose rather than overwriting one another.
export class RotationClock {
 constructor(fire, interval, env={}){this.fire=fire;this.interval=interval;this.now=env.now||(()=>performance.now());this.set=env.set||((fn,ms)=>globalThis.setTimeout(fn,ms));this.clear=env.clear||(id=>globalThis.clearTimeout(id));this.reasons=new Set();this.timer=null;this.remaining=interval;this.deadline=0;this.destroyed=false;}
 start(delay=this.interval){this.clear(this.timer);this.timer=null;this.remaining=delay;if(this.destroyed||this.reasons.size)return;this.deadline=this.now()+delay;this.timer=this.set(()=>{this.timer=null;this.remaining=0;this.fire();},Math.max(0,this.deadline-this.now()));}
 pause(reason){if(!this.reasons.size&&this.timer!==null)this.remaining=Math.max(0,this.deadline-this.now());this.reasons.add(reason);this.clear(this.timer);this.timer=null;}
 resume(reason){this.reasons.delete(reason);if(!this.reasons.size)this.start(this.remaining);}
 stop(){this.destroyed=true;this.clear(this.timer);this.timer=null;}
}
export async function fetchNext(fetcher,url,slug){const request=()=>fetcher(url,{headers:{Accept:'application/json'},cache:'no-cache'}).then(r=>{if(!r.ok)throw Error('feed');return r.json();});let data=await request();if(data.total_active>1&&data.hazard?.public_slug===slug)data=await request();if(data.total_active>1&&data.hazard?.public_slug===slug)throw Error('unchanged');return data;}
export function mountFeatured(root){
 const card=root.querySelector('[data-featured-card]'),prev=root.querySelector('[data-prev]'),next=root.querySelector('[data-next]'),position=root.querySelector('[data-position]'),announce=root.querySelector('[data-featured-announcement]');
 let slug=root.dataset.slug,busy=false,failures=0,generation=0;const clock=new RotationClock(()=>turn(false),Number(root.dataset.interval)*1000);
 const segments=root.querySelector('[data-segments]');
 function syncSegments(pos,total){if(!segments)return;if(segments.children.length!==total){segments.replaceChildren();for(let i=0;i<total;i++)segments.append(document.createElement('span'));}for(let i=0;i<segments.children.length;i++)segments.children[i].classList.toggle('on',i===pos-1);}
 async function preload(url){if(!url)return;await Promise.race([new Promise(resolve=>{const image=new Image();image.onload=image.onerror=resolve;image.src=url;}),new Promise(resolve=>setTimeout(resolve,3000))]);}
 async function turn(manual,backward=false){if(busy)return;busy=true;next.disabled=true;if(prev)prev.disabled=true;const started=performance.now(),token=generation;
  try{const url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('school',root.dataset.school);if(slug)url.searchParams.set(backward?'before':'after',slug);const data=await fetchNext(fetch,url,slug);await preload(data.hazard?.photo_url);if(token!==generation)return;
   if(!manual&&clock.reasons.size){clock.remaining=0;return;} // hidden/focused requests never swap the user's focused card
   if(data.hazard){const same=data.hazard.public_slug===slug;card.replaceChildren(cardElement(data.hazard,true));if(!same&&!matchMedia(uiText("(prefers-reduced-motion: reduce)")).matches){card.classList.remove('featured-card-changing');void card.offsetWidth;card.classList.add('featured-card-changing');}slug=data.hazard.public_slug;
    if(manual)announce.textContent=uiText("Menampilkan kondisi: :v0:", [data.hazard.title]);
   }else{slug='';const box=document.createElement('div');box.className='condition-empty';const h=document.createElement('h3');h.textContent=uiText("Belum ada laporan yang masuk.");const p=document.createElement('p');p.textContent=uiText("Ini bukan jaminan kondisi aman.");const a=document.createElement('a');a.href='/map';a.className=uiText("btn btn-dark");a.textContent=uiText("Buka peta");const report=document.createElement('a');report.href='/hazards/create';report.className='condition-back';report.textContent=uiText("Lapor kondisi");box.append(h,p,a,report);card.replaceChildren(box);if(manual)announce.textContent=h.textContent;}
   position.textContent=uiText(":v0: dari :v1:", [data.position, data.total_active]);syncSegments(data.position,data.total_active);root.querySelector('[data-total]').textContent=data.total_active;if(data.summary){const chips=root.querySelector('[data-category-counts]');chips.replaceChildren();Object.entries(data.summary.categories).forEach(([code,c])=>{const a=document.createElement('a');const url=new URL('/kondisi',location.href);url.searchParams.set('school',root.dataset.school);url.searchParams.set('kategori',code);a.href=url.href;a.textContent=uiText(":v0: · :v1:", [c.label, c.count]);chips.append(a);});}const time=root.querySelector('[data-generated]');time.dateTime=data.generated_at;time.textContent=new Date(data.generated_at).toLocaleTimeString('id-ID',{timeZone:root.dataset.timezone||'Asia/Jakarta'});clock.interval=data.rotate_in_seconds*1000;failures=0;
  }catch{failures=Math.min(failures+1,4);if(manual)announce.textContent=uiText("Belum dapat diperbarui. Kondisi terakhir tetap ditampilkan.");}
  finally{busy=false;next.disabled=false;if(prev)prev.disabled=false;const delay=clock.interval*2**failures;clock.start(manual?delay:Math.max(0,delay-(performance.now()-started)));}
 }
 card.addEventListener('pointerenter',()=>clock.pause('hover'));card.addEventListener('pointerleave',()=>clock.resume('hover'));
 card.addEventListener('focusin',()=>clock.pause('focus'));card.addEventListener('focusout',()=>queueMicrotask(()=>{if(!card.contains(document.activeElement))clock.resume('focus');}));
 const visibility=()=>document.hidden?clock.pause('hidden'):clock.resume('hidden');document.addEventListener('visibilitychange',visibility);
 if(prev)prev.addEventListener('click',()=>turn(true,true));next.addEventListener('click',()=>turn(true));
 const summary=root.querySelector('details');if(innerWidth<640)summary.open=false;photoFallback(root);clock.start();if(document.hidden)clock.pause('hidden');
 addEventListener('pagehide',()=>{generation++;clock.stop();});addEventListener('pageshow',e=>{if(e.persisted){clock.destroyed=false;clock.start();visibility();}});return clock;
}

// Panel "Ringkasan area": ganti institusi tanpa memuat ulang halaman.
// Berbagi konteks dengan rotator featured (root.dataset.school yang sama),
// jadi kartu unggulan ikut berpindah area. Gagal jaringan -> kirim formulir
// seperti biasa (halaman dimuat ulang), tidak pernah lebih buruk dari sebelumnya.
export function mountAreaSummary(root) {
  const form = root.querySelector('[data-area-form]');
  if (!form) return;
  const hidden = form.querySelector('[data-area-search-value]');
  const submit = form.querySelector('[type="submit"]');
  const list = root.querySelector('[data-area-list]');
  const total = root.querySelector('[data-total]');
  const chips = root.querySelector('[data-category-counts]');
  const time = root.querySelector('[data-generated]');
  const all = root.querySelector('[data-area-all]');
  const announce = root.querySelector('[data-featured-announcement]');

  function row(h) {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.className = 'area-hazard';
    a.href = h.detail_url;
    const main = document.createElement('span');
    main.className = 'area-hazard__main';
    const strong = document.createElement('strong');
    strong.textContent = h.type_label;
    main.append(strong);
    if (h.location_label) {
      const loc = document.createElement('span');
      loc.className = 'area-hazard__loc';
      loc.textContent = h.location_label;
      main.append(loc);
    }
    const meta = document.createElement('span');
    meta.className = 'area-hazard__meta';
    meta.textContent = `${h.status_label} · ${h.age_text} · ${h.confirmations_count} ${uiText('konfirmasi')}`;
    if (h.is_demo) {
      const badge = document.createElement('span');
      badge.className = 'data-demo data-demo--icon';
      badge.setAttribute('role', 'img');
      badge.setAttribute('aria-label', uiText('Data demo'));
      badge.title = uiText('Data demo — bukan laporan nyata');
      badge.innerHTML = FLASK;
      meta.append(' ', badge);
    }
    const dot = document.createElement('span');
    dot.className = `area-hazard__dot area-hazard__dot--${h.status || 'new'}`;
    dot.setAttribute('aria-hidden', 'true');
    const content = document.createElement('span');
    content.className = 'area-hazard__content';
    content.append(main, meta);
    a.append(dot, content);
    li.append(a);
    return li;
  }

  // Search bar institusi: ketik nama -> pilih hasil -> slug tersembunyi terisi
  // -> formulir dikirim ulang memakai alur yang sama seperti tombol.
  const searchBox = form.querySelector('[data-area-search]');
  const searchInput = form.querySelector('[data-area-search-input]');
  const searchResults = form.querySelector('[data-area-search-results]');
  if (searchBox && searchInput && searchResults && hidden) {
    let timer = null;
    let items = [];
    let activeIdx = -1;
    const closeResults = () => {
      searchResults.hidden = true;
      searchInput.setAttribute('aria-expanded', 'false');
      activeIdx = -1;
    };
    const pick = (item) => {
      hidden.value = item.slug;
      searchInput.value = item.name;
      closeResults();
      form.requestSubmit();
    };
    const render = () => {
      searchResults.replaceChildren();
      items.forEach((item) => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'ra-area-summary__result-btn';
        const n = document.createElement('span');
        n.textContent = item.name;
        b.append(n);
        if (item.meta) {
          const m = document.createElement('small');
          m.textContent = item.meta;
          b.append(m);
        }
        b.addEventListener('click', () => pick(item));
        li.append(b);
        searchResults.append(li);
      });
      searchResults.hidden = items.length === 0;
      searchInput.setAttribute('aria-expanded', String(items.length > 0));
    };
    searchInput.addEventListener('input', () => {
      hidden.value = '';
      const q = searchInput.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { items = []; render(); return; }
      timer = setTimeout(async () => {
        try {
          const u = new URL(searchBox.dataset.searchEndpoint, location.href);
          u.searchParams.set('q', q);
          const r = await fetch(u, {headers: {Accept: 'application/json'}});
          if (!r.ok) return;
          items = await r.json();
          render();
        } catch { /* jaringan gagal: hasil lama dibiarkan */ }
      }, 250);
    });
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { closeResults(); return; }
      if (e.key === 'ArrowDown' && items.length) { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); return; }
      if (e.key === 'ArrowUp' && items.length) { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); return; }
      if (e.key === 'Enter' && !searchResults.hidden && items.length) { e.preventDefault(); pick(items[Math.max(0, activeIdx)]); }
    });
    document.addEventListener('click', (e) => { if (!searchBox.contains(e.target)) closeResults(); });
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const slug = hidden?.value;
    if (!slug) { form.submit(); return; }
    if (submit) submit.disabled = true;
    root.setAttribute('aria-busy', 'true');
    try {
      const url = new URL(form.dataset.areaEndpoint, location.href);
      url.searchParams.set('school', slug);
      let response = await fetch(url, {headers: {Accept: 'application/json'}, cache: 'no-cache'}).catch(() => null);
      if (!response || !response.ok) {
        await new Promise((resolve) => setTimeout(resolve, 800));
        response = await fetch(url, {headers: {Accept: 'application/json'}, cache: 'no-cache'});
      }
      if (!response.ok) throw Error('area');
      const data = await response.json();
      root.dataset.school = data.school?.slug || slug;
      if (total && data.summary) total.textContent = data.summary.total;
      if (chips && data.summary) {
        chips.replaceChildren();
        Object.entries(data.summary.categories).forEach(([code, c]) => {
          const a = document.createElement('a');
          const u = new URL('/kondisi', location.href);
          u.searchParams.set('school', root.dataset.school);
          u.searchParams.set('kategori', code);
          a.href = u.href;
          a.textContent = `${c.label} · ${c.count}`;
          chips.append(a);
        });
      }
      if (time && data.generated_at) {
        time.dateTime = data.generated_at;
        time.textContent = new Date(data.generated_at).toLocaleTimeString('id-ID', {timeZone: root.dataset.timezone || 'Asia/Jakarta'});
      }
      if (list) {
        list.replaceChildren();
        if (!data.hazards?.length) {
          const li = document.createElement('li');
          li.className = 'area-hazard-empty';
          li.textContent = uiText('Belum ada hazard aktif di area ini.');
          list.append(li);
        } else {
          data.hazards.forEach((h) => list.append(row(h)));
        }
      }
      if (all) {
        const showAll = (data.summary?.total ?? 0) > (data.hazards?.length ?? 0);
        all.hidden = !showAll;
        if (showAll) {
          const u = new URL('/kondisi', location.href);
          u.searchParams.set('school', root.dataset.school);
          all.href = u.href;
        }
      }
      const stats = root.querySelector('.ra-area-summary__stats');
      if (stats) {
        stats.classList.remove('area-updated');
        void stats.offsetWidth;
        stats.classList.add('area-updated');
        setTimeout(() => stats.classList.remove('area-updated'), 1900);
      }
      root.querySelector('.ra-area-summary')?.scrollIntoView({block: 'nearest', behavior: 'smooth'});
      const here = new URL(location.href);
      here.searchParams.set('school', root.dataset.school);
      history.pushState(null, '', here);
      if (announce && data.school?.name) announce.textContent = uiText('Area diperbarui: :v0:', [data.school.name]);
    } catch {
      form.submit();
    } finally {
      if (submit) submit.disabled = false;
      root.removeAttribute('aria-busy');
    }
  });
}

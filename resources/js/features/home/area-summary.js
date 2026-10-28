const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
const FLASK = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg>';

// Panel "Ringkasan area": ganti institusi tanpa memuat ulang halaman.
// Berbagi konteks dengan rotator featured (root.dataset.school yang sama),
// jadi kartu unggulan ikut berpindah area. Gagal jaringan -> kirim formulir
// seperti biasa (halaman dimuat ulang), tidak pernah lebih buruk dari sebelumnya.
export function mountAreaSummary(root) {
  const form = root.querySelector('[data-area-form]');
  if (!form) return;
  const select = form.querySelector('select[name="school"]');
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
    a.append(main, meta);
    li.append(a);
    return li;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const slug = select?.value;
    if (!slug) { form.submit(); return; }
    if (submit) submit.disabled = true;
    root.setAttribute('aria-busy', 'true');
    try {
      const url = new URL(form.dataset.areaEndpoint, location.href);
      url.searchParams.set('school', slug);
      const response = await fetch(url, {headers: {Accept: 'application/json'}, cache: 'no-cache'});
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

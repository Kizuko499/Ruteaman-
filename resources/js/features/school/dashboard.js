export function mountSchoolDashboard(root){
 if(!root||root.dataset.glassReady)return;
 root.dataset.glassReady='true';
 const reduced=matchMedia('(prefers-reduced-motion: reduce)'),transparency=matchMedia('(prefers-reduced-transparency: reduce)'),contrast=matchMedia('(prefers-contrast: more)'),forced=matchMedia('(forced-colors: active)');
 // SVG backdrop filter URLs are enabled only on the tested Chromium family.
 // Other browsers keep CSS blur; text/actions never depend on either effect.
 const supports=globalThis.CSS?.supports('backdrop-filter','url("#ra-school-dashboard-refraction")')&&/Chrome|Chromium|Edg\//.test(navigator.userAgent);
 const coarse=matchMedia('(pointer: coarse)');
 const select=()=>root.dataset.glassEngine=[reduced,transparency,contrast,forced].some(m=>m.matches)?'solid':(supports&&!coarse.matches)?'svg':'blur';
 const media=[reduced,transparency,contrast,forced,coarse];let active=true;const apply=()=>{if(active)select();};const listen=()=>media.forEach(m=>m.addEventListener('change',apply));listen();select();
 addEventListener('pagehide',()=>{active=false;media.forEach(m=>m.removeEventListener('change',apply));});
 addEventListener('pageshow',()=>{active=true;listen();select();});
}

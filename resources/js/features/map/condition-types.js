const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
import {escapeHtml,safeDetailUrl} from './marker-state.js';
// Central visual catalog. Backend category codes are mapped here, not in individual markers.
const svg = paths => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
export const conditionTypes = Object.freeze({
 accident:{label:'Kecelakaan',color:'#B63E45',icon:svg('<path d="m3 16 1-5 5-1 3 3v5H3Z M14 16v-5l6-1 1 6Z M11 3l2 3 3-2-1 4 M6 5l2 2"/><circle cx="6" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/>')},
 road_damage:{label:uiText("Jalan rusak"),color:'#AD591C',icon:svg('<path d="M6 3 3 21 M18 3l3 18 M12 3v4l-3 3 6 3-5 4 2 4 M5 14h2 M17 14h2"/>')},
 flood:{label:'Banjir',color:'#236DA3',icon:svg('<path d="M3 14q2-3 4 0t4 0 4 0 4 0 M3 19q2-3 4 0t4 0 4 0 4 0 M12 3c-2 3-4 5-4 7a4 4 0 0 0 8 0c0-2-2-4-4-7Z"/>')},
 fallen_tree:{label:uiText("Pohon tumbang"),color:'#37744B',icon:svg('<path d="m3 20 16-8 M12 13l-3-5 4-1-1-4 5 1 4 3-2 5-4-1 M5 18l-1-4 M8 17l2 3"/>')},
 road_closed:{label:uiText("Jalan ditutup"),color:'#8D4670',icon:svg('<rect x="3" y="7" width="18" height="9" rx="1"/><path d="M6 16v5 M18 16v5 M4 12l4-4 M10 15l7-7 M17 15l3-3"/>')},
 traffic:{label:'Kemacetan',color:'#956817',icon:svg('<path d="m3 9 1-5h6l1 5 M13 18l1-5h6l1 5"/><rect x="2" y="9" width="10" height="5" rx="1"/><rect x="12" y="18" width="10" height="4" rx="1"/><path d="M4 14v2 M10 14v2 M14 22v1 M20 22v1 M18 3v5 M15 5l3 3 3-3"/>')},
 street_light:{label:uiText("Lampu jalan mati"),color:'#6457A3',icon:svg('<path d="M7 21V7a4 4 0 0 1 4-4h4 M13 5h6l-1 4h-4Z M4 21h6 M14 13l6 6 M20 13l-6 6"/>')},
 warning:{label:uiText("Bahaya umum"),color:'#B34420',icon:svg('<path d="m12 3 10 18H2Z M12 9v5 M12 17h.01"/>')},
 obstruction:{label:'Hambatan',color:'#736143',icon:svg('<rect x="4" y="8" width="16" height="12" rx="1"/><path d="m4 8 8-5 8 5 M12 8v12 M4 14h16"/>')},
 slippery:{label:uiText("Jalan licin"),color:'#257D81',icon:svg('<path d="m6 8 3-5 5 3 1 5 M5 13h13l2 4H8Z M3 20q3-2 6 0t6 0 6 0 M15 3l2 2"/>')},
 damaged_sidewalk:{label:uiText("Trotoar rusak"),color:'#775C96',icon:svg('<path d="M3 5h18v15H3Z M3 10h18 M3 15h8 M15 15h6 M8 5v5 M16 5v5 M9 10l4 3-3 4 4 3"/>')},
 handled:{label:'Tertangani',color:'#1F7669',icon:svg('<circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/>')},
});
const aliases = {pothole:'road_damage',damaged_road:'road_damage',poor_lighting:'street_light',road_closure:'road_closed',general_danger:'warning',safe:'handled',resolved:'handled'};
const registered=new Map();
export function registerConditionTypes(items){registered.clear();if(!Array.isArray(items))return;for(const row of items){if(row&&typeof row.code==='string'&&/^[a-z][a-z0-9_]{0,49}$/.test(row.code))registered.set(row.code,{label:String(row.label||row.code),icon_key:row.icon_key,icon_url:row.icon_url});}}
export function resolveConditionType(code,metadata=null){
 const config=metadata||registered.get(code);const requested=config?.icon_key;
 const key=Object.hasOwn(conditionTypes,requested)?requested:(Object.hasOwn(conditionTypes,code)?code:aliases[code]||'warning');
 const visual={key,...conditionTypes[key]};if(config?.label)visual.label=String(config.label);
 const url=safeDetailUrl(config?.icon_url);if(url&&/^\/media\/condition-types\/\d+$/.test(new URL(url).pathname))visual.icon=`<img class="condition-custom-icon" src="${escapeHtml(url)}" width="24" height="24" alt="" aria-hidden="true">`;
 return visual;
}
export function visualType(data){return data.status==='resolved'?resolveConditionType('handled',{}):resolveConditionType(data.type,data.type_visual);}


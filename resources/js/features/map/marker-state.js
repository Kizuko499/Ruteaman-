const uiText = (text, values = []) => {let result = globalThis.ruteamanTranslate?.(text) ?? text; values.forEach((value, i) => {result = result.split(`:v${i}:`).join(String(value));}); return result;};
// Pure functions: data -> marker state. They never mount, hide, move or delete a marker.
export function parseReportTime(value){
 if(typeof value!=='string'||!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})$/.test(value))return null;
 const [year,month,day,hour,minute]=value.match(/\d+/g).slice(0,5).map(Number);if(month<1||month>12||day<1||day>new Date(Date.UTC(year,month,0)).getUTCDate()||hour>23||minute>59)return null;
 const t=Date.parse(value);return Number.isFinite(t)?t:null;
}
export function calculateMarkerAge(createdAt,now=Date.now()){
 const time=typeof createdAt==='number'?createdAt:parseReportTime(createdAt);return time===null||!Number.isFinite(time)?null:Math.max(0,(now-time)/60000);
}
const stops=[[0,1,1,1],[5,.94,.98,.75],[15,.86,.96,.45],[30,.76,.94,.25],[60,.65,.92,.08],[180,.48,.88,0]];
export function calculateMarkerIntensity(minutes){
 if(minutes===null||!Number.isFinite(minutes))return {opacity:.48,brightness:.88,glow:0,pulse:0,recency:'unknown'};
 const age=Math.max(0,minutes);let a=stops[stops.length-1],b=a;
 for(let i=1;i<stops.length;i++)if(age<stops[i][0]){a=stops[i-1];b=stops[i];break;}
 const x=a===b?0:Math.max(0,Math.min(1,(age-a[0])/(b[0]-a[0]))),ease=x*x*(3-2*x);
 const interpolate=index=>a[index]+(b[index]-a[index])*ease;
 return {opacity:interpolate(1),brightness:interpolate(2),glow:interpolate(3),pulse:age<15?(1-age/15)*.35:0,recency:age<5?'bright':age<30?'normal':age<180?'dim':'persistent'};
}
export function ageLabel(minutes){if(minutes===null)return uiText("Waktu laporan tidak tersedia");if(minutes<1)return uiText("Baru dilaporkan");if(minutes<60)return uiText(":v0: menit lalu", [Math.floor(minutes)]);if(minutes<1440)return uiText(":v0: jam lalu", [Math.floor(minutes/60)]);return uiText(":v0: hari lalu", [Math.floor(minutes/1440)]);}
export function adaptCondition(raw){
 if(!raw||typeof raw!=='object')return null;
 const p=raw.type==='Feature'?raw.properties:raw,c=raw.type==='Feature'?raw.geometry?.coordinates:null;
 if(!p)return null;
 const latitude=c?.[1]??p.latitude??p.lat,longitude=c?.[0]??p.longitude??p.lng,id=p.id??p.public_slug??p.slug;
 if(typeof id!=='string'||!id.trim()||id.length>100||latitude===null||longitude===null||latitude===''||longitude==='')return null;
 if(!['number','string'].includes(typeof latitude)||!['number','string'].includes(typeof longitude))return null;
 if([latitude,longitude].some(v=>typeof v==='string'&&!/^[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?$/.test(v.trim())))return null;
 const lat=Number(latitude),lng=Number(longitude);if(!Number.isFinite(lat)||!Number.isFinite(lng)||Math.abs(lat)>90||Math.abs(lng)>180)return null;
 const createdAt=p.created_at??p.first_reported_at??null;
 return {id,latitude:lat,longitude:lng,type:String(p.type??p.type_code??'warning'),type_label:String(p.type_label??p.category??''),type_visual:p.type_visual&&typeof p.type_visual==='object'?{icon_key:String(p.type_visual.icon_key??''),icon_url:typeof p.type_visual.icon_url==='string'?p.type_visual.icon_url:null}:null,created_at:createdAt,createdMs:parseReportTime(createdAt),
 status:String(p.status??'unknown'),status_label:String(p.status_label??uiText("Status belum tersedia")),title:String(p.title??p.type_label??p.category??uiText("Kondisi")),
 description:String(p.description??'').slice(0,300),location:String(p.location??p.location_label??''),severity:p.severity??null,reports_count:Number(p.reports_count??p.reports??0)||0,confirmations_count:Number(p.confirmations_count??p.confirmations??0)||0,
 historical:Boolean(p.historical),is_demo:Boolean(p.is_demo),url:typeof p.url==='string'?p.url:null};
}
export function safeDetailUrl(value){if(typeof value!=='string'||!value.trim())return null;try{const u=new URL(value,globalThis.location?.origin??'https://ruteaman.invalid');return /^https?:$/.test(u.protocol)&&u.origin===(globalThis.location?.origin??'https://ruteaman.invalid')?u.href:null;}catch{return null;}}
export function escapeHtml(value){return String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}

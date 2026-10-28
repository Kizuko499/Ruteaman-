import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../../public/js/admin-school-picker.js',import.meta.url),'utf8');
function mount({reports=true,items=[],confirm=true,registerStatus=200,registerData={id:2,name:'Sekolah Nasional QA',npsn:'10101019',source:'registered'},outage=false}={}){
 const node=()=>({events:{},children:[],textContent:'',style:{},disabled:false,hidden:false,addEventListener(type,fn){this.events[type]=fn;},append(...es){this.children.push(...es);},replaceChildren(){this.children=[];},focus(){this.focused=true;}});
 const query=node(),search=node(),select=node(),results=node(),message=node();query.value='Sekolah';select.options=[{value:'1',text:'Sekolah Terdaftar'}];select.value='1';select.add=o=>select.options.push(o);select.dispatchEvent=()=>{};
 const nodes={'[data-school-query]':query,'[data-school-search]':search,'[data-school-value]':select,'[data-school-results]':results,'[data-school-message]':message};let submitted=0;const form={requestSubmit(){submitted++;}};
 const root={dataset:{pickerContext:reports?'reports':'',searchUrl:'/admin/school-picker/search',registerUrl:'/admin/school-picker/register'},querySelector:s=>nodes[s],closest:()=>form};const calls=[],events={},prompts=[];let reauth=0;
 vm.runInNewContext(source,{URL,Event:class{},Option:class{constructor(text,value){this.text=text;this.value=value;}},location:{origin:'https://ruteaman.test'},confirm:text=>{prompts.push(text);return confirm;},document:{querySelectorAll:s=>s==='[data-school-picker]'?[root]:[],createElement:node,addEventListener:(name,fn)=>events[name]=fn,querySelector:s=>s==='meta[name="csrf-token"]'?{content:'synthetic-csrf'}:s==='[data-open-reauth]'?{click(){reauth++;}}:null},fetch:async(url,options={})=>{calls.push({url:String(url),options});if(options.method==='POST')return{status:registerStatus,ok:registerStatus===200,json:async()=>({data:registerData,message:'Sekolah dijeda; tinjau pin dan fitur.'})};return{ok:true,json:async()=>({data:items,online_count:items.filter(i=>i.source==='directory').length,message:outage?'Direktori nasional belum dapat dihubungi.':null})};}});
 return {query,search,select,results,message,calls,events,prompts,submitted:()=>submitted,reauth:()=>reauth};
}
test('report page registered choice navigates to that school, not just changes dropdown',async()=>{
 const s=mount({items:[{id:2,name:'Sekolah Dua',npsn:'20202020',source:'registered'}]});await s.search.events.click();const card=s.results.children[0];assert.equal(card.children[2].textContent,'Tampilkan laporan');card.children[2].events.click();assert.equal(s.select.value,'2');assert.equal(s.submitted(),1);assert.equal(s.calls.length,1);
});
test('account picker still changes selection without submitting account changes',async()=>{
 const s=mount({reports:false,items:[{id:2,name:'Sekolah Dua',source:'registered'}]});await s.search.events.click();s.results.children[0].children[2].events.click();assert.equal(s.select.value,'2');assert.equal(s.submitted(),0);
});
test('online search never registers a school automatically and cancel keeps database untouched',async()=>{
 const s=mount({confirm:false,items:[{name:'Sekolah Nasional QA',npsn:'10101019',source:'directory'}]});await s.search.events.click();const b=s.results.children[0].children[2];assert.equal(b.textContent,'Daftarkan & tampilkan');assert.equal(s.calls.length,1);b.events.click();assert.equal(s.calls.length,1);assert.equal(s.submitted(),0);assert.match(s.prompts[0],/Tidak ada akun atau laporan/);
});
test('explicit online registration sends only NPSN and navigates to server school ID',async()=>{
 const s=mount({items:[{name:'Nama dari hasil pencarian',npsn:'10101019',source:'directory'}]});await s.search.events.click();s.results.children[0].children[2].events.click();await new Promise(setImmediate);assert.equal(s.calls[1].options.method,'POST');assert.deepEqual(JSON.parse(s.calls[1].options.body),{npsn:'10101019'});assert.equal(s.select.value,'2');assert.equal(s.submitted(),1);assert.match(s.message.textContent,/dijeda/);
});
test('minimum query length avoids an online request',async()=>{
 const s=mount();s.query.value='MA';await s.search.events.click();assert.equal(s.calls.length,0);assert.match(s.message.textContent,/minimal 3/);
});
test('outage keeps existing choices and shows an actionable message',async()=>{
 const s=mount({outage:true});await s.search.events.click();assert.equal(s.select.options.length,1);assert.match(s.message.textContent,/belum dapat dihubungi/);assert.equal(s.submitted(),0);
});
test('directory strings are displayed as text, not HTML',async()=>{
 const s=mount({items:[{id:2,name:'<img src=x onerror=alert(1)>',source:'registered'}]});await s.search.events.click();const title=s.results.children[0].children[0];assert.equal(title.textContent,'<img src=x onerror=alert(1)>');assert.equal(title.innerHTML,undefined);
});
test('reauth requirement does not select or navigate before confirmation',async()=>{
 const s=mount({registerStatus:428,items:[{name:'Sekolah Nasional',npsn:'10101019',source:'directory'}]});await s.search.events.click();s.results.children[0].children[2].events.click();await new Promise(setImmediate);assert.equal(s.submitted(),0);assert.equal(s.select.value,'1');assert.equal(s.reauth(),1);
});

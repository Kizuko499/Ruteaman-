import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../../public/js/admin-back.js',import.meta.url),'utf8');
function mount({referrer='',href='https://ruteaman.my.id/admin/users/create',length=2}={}){
 const events={},link={href:'https://ruteaman.my.id/admin',addEventListener:(type,fn)=>events[type]=fn};let calls=0;
 vm.runInNewContext(source,{URL,document:{referrer,querySelectorAll:()=>[link]},location:{href},window:{history:{length,back:()=>calls++}}});
 return {link,events,calls:()=>calls};
}
test('same-origin admin previous page preserves filters and calls history back',()=>{
 const state=mount({referrer:'https://ruteaman.my.id/admin/users?role=school_admin&page=2'});let prevented=false;
 assert.equal(state.link.href,'https://ruteaman.my.id/admin/users?role=school_admin&page=2');
 state.events.click({button:0,preventDefault:()=>prevented=true});assert.equal(state.calls(),1);assert.equal(prevented,true);
});
test('direct opening without referrer keeps Ringkasan fallback',()=>{
 const state=mount();assert.equal(state.link.href,'https://ruteaman.my.id/admin');assert.equal(state.events.click,undefined);
});
test('external referrer cannot become a return destination',()=>{
 for(const referrer of ['https://evil.example/admin/users','javascript:alert(1)','not a URL']){const s=mount({referrer});assert.equal(s.link.href,'https://ruteaman.my.id/admin');assert.equal(s.events.click,undefined);}
});
test('login or public page referrer keeps fallback inside console',()=>{
 for(const referrer of ['https://ruteaman.my.id/login','https://ruteaman.my.id/lapor','https://ruteaman.my.id/logout'])assert.equal(mount({referrer}).link.href,'https://ruteaman.my.id/admin');
});
test('new tab with no previous history keeps fallback',()=>{
 assert.equal(mount({referrer:'https://ruteaman.my.id/admin/users',length:1}).link.href,'https://ruteaman.my.id/admin');
});
test('same-page or hash-only reference does not loop to current page',()=>{
 assert.equal(mount({referrer:'https://ruteaman.my.id/admin/users/create#section'}).link.href,'https://ruteaman.my.id/admin');
});
test('modified clicks remain native links without history manipulation',()=>{
 for(const modified of [{button:1},{button:0,ctrlKey:true},{button:0,metaKey:true},{button:0,shiftKey:true},{button:0,altKey:true}]){const s=mount({referrer:'https://ruteaman.my.id/admin/users'});s.events.click({...modified,preventDefault:()=>assert.fail('Modified click intercepted')});assert.equal(s.calls(),0);}
});

import test from 'node:test';import assert from 'node:assert/strict';
import {ConditionMarkerManager} from '../../resources/js/features/map/condition-marker-manager.js';
const activeTimers=new Set();let timerId=0,removed=0;
globalThis.setInterval=()=>{const id=++timerId;activeTimers.add(id);return id;};globalThis.clearInterval=id=>activeTimers.delete(id);
globalThis.window={matchMedia:()=>({matches:false}),addEventListener(){},removeEventListener(){}};globalThis.document={hidden:false,addEventListener(){},removeEventListener(){}};globalThis.location={origin:'https://ruteaman.invalid'};
const L={divIcon:x=>x,marker:(coords)=>({coords,addTo(){return this},bindPopup(){return this},bindTooltip(){return this},on(){return this},off(){return this},getElement(){return null},getLatLng(){return this.coords},setLatLng(c){this.coords=c},setIcon(){},isPopupOpen(){return false},remove(){removed++}})};
const map={on(){},off(){},getContainer:()=>({style:{setProperty(){}},addEventListener(){},removeEventListener(){}}),getZoom:()=>14,getBounds:()=>({pad(){return this},getWest:()=>106,getEast:()=>107,getSouth:()=>-7,getNorth:()=>-6,contains:()=>true})};
const row=(id,days=0)=>({id,type:'flood',latitude:-6.17,longitude:106.82,created_at:new Date(Date.now()-days*86400000).toISOString(),status:'confirmed'});
test('one centralized timer for 1000 markers; age updates never remove; duplicate/malformed data preserves known marker; destroy releases timer',()=>{
 const m=new ConditionMarkerManager(L,map);m.setSchool('1',Array.from({length:1000},(_,i)=>row('row-'+i,30)));assert.equal(activeTimers.size,1);assert.equal(m.markers.size,1000);
 const first=m.markers.get('row-0');m.offset=86400000*365;for(let i=0;i<5;i++)m.updateAllConditionMarkers();assert.equal(m.markers.size,1000);assert.equal(removed,0);assert.equal(m.markers.get('row-0'),first);
 m.sync([row('one',30),row('one',7)]);assert.equal(m.markers.size,1);const one=m.markers.get('one');m.sync([{...row('one'),latitude:null}]);assert.equal(m.markers.get('one'),one);m.destroy();assert.equal(activeTimers.size,0);assert.equal(m.markers.size,0);m.destroy();
});
test('failed API and incomplete pagination preserve previous authoritative marker set',async()=>{
 const m=new ConditionMarkerManager(L,map,{endpoint:'/api/map/conditions'});m.setSchool('1',[row('keep',30)]);globalThis.fetch=async()=>({ok:false,status:503});await m.refresh();assert.ok(m.markers.has('keep'));
 let requests=0;globalThis.fetch=async()=>{if(requests++)throw Error('offline');return {ok:true,json:async()=>({school_id:1,items:[row('new')],next_cursor:'second-page',generated_at:new Date().toISOString()})}};await m.refresh();assert.ok(m.markers.has('keep'));assert.ok(!m.markers.has('new'));m.destroy();assert.equal(activeTimers.size,0);
});
test('successful paginated snapshot merges stable IDs and updated status without duplication',async()=>{
 const m=new ConditionMarkerManager(L,map,{endpoint:'/api/map/conditions'});m.setSchool('1',[row('keep',30)]);let requests=0;globalThis.fetch=async()=>({ok:true,json:async()=>requests++===0?{school_id:1,items:[row('keep',30)],next_cursor:'page-2'}:{school_id:1,items:[{...row('keep',30),status:'resolved'},row('new',7)],next_cursor:null}});await m.refresh();assert.equal(m.markers.size,2);assert.equal(m.markers.get('keep').data.status,'resolved');m.destroy();assert.equal(activeTimers.size,0);
});
test('automatic complete feed does not use bbox and frames only the first full snapshot',async()=>{
 let frames=0;const urls=[];const m=new ConditionMarkerManager(L,{...map,fitBounds(){frames++;}},{endpoint:'/api/map/conditions'});m.setSchool('1',[]);
 globalThis.fetch=async url=>{urls.push(url);return{ok:true,json:async()=>({school_id:1,items:[row('near'),{...row('far'),latitude:-6.75,longitude:111.48}],next_cursor:null})}};
 await m.refresh();assert.equal(m.markers.size,2);assert.equal(frames,1);assert.ok(urls.every(u=>!u.searchParams.has('bbox')));await m.refresh();assert.equal(frames,1);m.destroy();
});
test('interacting during startup cancels camera frame, not the conditions',async()=>{
 let frames=0,release;const m=new ConditionMarkerManager(L,{...map,fitBounds(){frames++;}},{endpoint:'/api/map/conditions'});m.setSchool('1',[]);globalThis.fetch=()=>new Promise(resolve=>{release=resolve});const pending=m.refresh();m.onUserCamera();release({ok:true,json:async()=>({school_id:1,items:[row('arrives')],next_cursor:null})});await pending;assert.equal(m.markers.size,1);assert.equal(frames,0);m.destroy();
});

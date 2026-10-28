import test from 'node:test';import assert from 'node:assert/strict';import {retainedBoundsKey} from '../../resources/js/features/map/retained-bounds.js';
const bounds=(w,s,e,n)=>({getWest:()=>w,getEast:()=>e,getSouth:()=>s,getNorth:()=>n});
test('zoom-in query still encloses cached reports outside the camera without changing pin coordinates',()=>{
 const camera=bounds(111.45048,-6.6948,111.45345,-6.69231),points=[{lat:-6.7091124,lng:111.4492828},{lat:-6.7533642,lng:111.4842861}],copy=structuredClone(points);
 const [w,s,e,n]=retainedBoundsKey(camera,points).split(',').map(Number);for(const p of points){assert.ok(p.lng>=w&&p.lng<=e);assert.ok(p.lat>=s&&p.lat<=n);}assert.deepEqual(points,copy);
});
test('dateline keeps a short crossing interval and clamps latitude',()=>{const [w,s,e,n]=retainedBoundsKey(bounds(179,-91,181,91),[{lat:0,lng:-179.5}]).split(',').map(Number);assert.equal(w,179);assert.equal(e,-179);assert.equal(s,-90);assert.equal(n,90);});
test('world view stays full-width and invalid points are ignored',()=>{assert.equal(retainedBoundsKey(bounds(-180,-85,180,85),[{lat:NaN,lng:0}]),'-180.00000,-85.00000,180.00000,85.00000');});

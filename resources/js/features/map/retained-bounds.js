// Enclose the camera AND already loaded pins. Re-query this area each refresh so
// public revocation still removes a pin; zoom-in alone is not a deletion event.
export function retainedBoundsKey(bounds,points=[]){
 let west=bounds.getWest(),east=bounds.getEast(),south=bounds.getSouth(),north=bounds.getNorth();
 const center=(west+east)/2,halo=.00002;
 for(const point of points){const lat=Array.isArray(point)?point[0]:point.lat,lng=Array.isArray(point)?point[1]:point.lng;
  if(!Number.isFinite(lat)||!Number.isFinite(lng))continue;
  const nearest=lng+360*Math.round((center-lng)/360);
  west=Math.min(west,nearest-halo);east=Math.max(east,nearest+halo);south=Math.min(south,lat-halo);north=Math.max(north,lat+halo);
 }
 const norm=v=>((v+180)%360+360)%360-180,wide=east-west>=360;
 return [wide?-180:norm(west),Math.max(-90,south),wide?180:norm(east),Math.min(90,north)].map(v=>v.toFixed(5)).join(',');
}

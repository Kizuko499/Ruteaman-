import L from 'leaflet';

// leaflet.markercluster adalah UMD yang membaca L dari global —
// modul ini memastikan window.L terisi SEBELUM plugin dievaluasi.
window.L = L;
globalThis.L = L;

export default L;

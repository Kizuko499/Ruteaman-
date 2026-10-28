<?php
// Trusted SVG shapes only; administrators cannot submit SVG markup.
return [
 'accident'=>['label'=>'Kecelakaan','color'=>'#B63E45','shape'=>'<path d="m3 16 1-5 5-1 3 3v5H3Z M14 16v-5l6-1 1 6Z M11 3l2 3 3-2-1 4 M6 5l2 2"/><circle cx="6" cy="18" r="1.5"/><circle cx="18" cy="18" r="1.5"/>'],
 'road_damage'=>['label'=>'Jalan rusak','color'=>'#AD591C','shape'=>'<path d="M6 3 3 21 M18 3l3 18 M12 3v4l-3 3 6 3-5 4 2 4 M5 14h2 M17 14h2"/>'],
 'flood'=>['label'=>'Banjir','color'=>'#236DA3','shape'=>'<path d="M3 14q2-3 4 0t4 0 4 0 4 0 M3 19q2-3 4 0t4 0 4 0 4 0 M12 3c-2 3-4 5-4 7a4 4 0 0 0 8 0c0-2-2-4-4-7Z"/>'],
 'fallen_tree'=>['label'=>'Pohon tumbang','color'=>'#37744B','shape'=>'<path d="m3 20 16-8 M12 13l-3-5 4-1-1-4 5 1 4 3-2 5-4-1 M5 18l-1-4 M8 17l2 3"/>'],
 'road_closed'=>['label'=>'Jalan ditutup','color'=>'#8D4670','shape'=>'<rect x="3" y="7" width="18" height="9" rx="1"/><path d="M6 16v5 M18 16v5 M4 12l4-4 M10 15l7-7 M17 15l3-3"/>'],
 'traffic'=>['label'=>'Kemacetan','color'=>'#956817','shape'=>'<path d="m3 9 1-5h6l1 5 M13 18l1-5h6l1 5"/><rect x="2" y="9" width="10" height="5" rx="1"/><rect x="12" y="18" width="10" height="4" rx="1"/><path d="M4 14v2 M10 14v2 M14 22v1 M20 22v1 M18 3v5 M15 5l3 3 3-3"/>'],
 'street_light'=>['label'=>'Lampu jalan mati','color'=>'#6457A3','shape'=>'<path d="M7 21V7a4 4 0 0 1 4-4h4 M13 5h6l-1 4h-4Z M4 21h6 M14 13l6 6 M20 13l-6 6"/>'],
 'warning'=>['label'=>'Bahaya umum','color'=>'#B34420','shape'=>'<path d="m12 3 10 18H2Z M12 9v5 M12 17h.01"/>'],
 'obstruction'=>['label'=>'Hambatan','color'=>'#736143','shape'=>'<rect x="4" y="8" width="16" height="12" rx="1"/><path d="m4 8 8-5 8 5 M12 8v12 M4 14h16"/>'],
 'slippery'=>['label'=>'Jalan licin','color'=>'#257D81','shape'=>'<path d="m6 8 3-5 5 3 1 5 M5 13h13l2 4H8Z M3 20q3-2 6 0t6 0 6 0 M15 3l2 2"/>'],
 'damaged_sidewalk'=>['label'=>'Trotoar rusak','color'=>'#775C96','shape'=>'<path d="M3 5h18v15H3Z M3 10h18 M3 15h8 M15 15h6 M8 5v5 M16 5v5 M9 10l4 3-3 4 4 3"/>'],
 'handled'=>['label'=>'Tertangani','color'=>'#1F7669','shape'=>'<circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/>'],
];

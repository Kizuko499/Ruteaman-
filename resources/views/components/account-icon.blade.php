@props(['name'=>'user'])
@php($paths = [
'home'=>'M3 3h8v9H3z M13 3h8v5h-8z M13 10h8v11h-8z M3 14h8v7H3z',
'map'=>'M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z M15 5.764v15 M9 3.236v15',
'plus'=>'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20 M8 12h8 M12 8v8',
'reports'=>'M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2 M9 2h6a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1 M12 11h4 M12 16h4 M8 11h.01 M8 16h.01',
'user'=>'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8',
'bell'=>'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4',
'settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z',
'shield'=>'M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6z M12 8v5 M12 17h.01',
'lock'=>'M6 10h12v11H6z M8 10V7a4 4 0 0 1 8 0v3',
'help'=>'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0 M9 9a3 3 0 0 1 6 0c0 2-3 2-3 5 M12 17h.01',
'check'=>'M4 12l5 5 11-11',
'dashboard'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
'logout'=>'M9 3H4v18h5 M10 12h11 M17 8l4 4-4 4',
'close'=>'M6 6l12 12 M18 6L6 18',
'chevron'=>'M9 5l7 7-7 7'
])
<svg class="account-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['user'] }}"/></svg>

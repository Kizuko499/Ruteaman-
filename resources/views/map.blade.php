@extends('layouts.app')
@section('title',__('Peta dan alternatif rute'))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/campus-map-v5.css') }}?v=recovery-auto-v8">
<link rel="stylesheet" href="{{ asset('css/map-auto-v7.css') }}?v=recovery-auto-v8">
@endpush
@section('content')
<style>
/* === Desain gabungan panel peta: kartu langkah + analisis graph (v10) === */
.step-card{border-radius:26px}
.step-head{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.step-badge{background:#1F6E70;color:#fff;width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;flex:none}
.step-head h2{font-size:1.06rem;margin:0;color:#173F41}
.locked-institution{background:rgba(231,242,240,.75);border-radius:16px;padding:12px 14px;margin-bottom:12px}
.graph-card{background:linear-gradient(145deg,rgba(255,255,255,.68),rgba(255,250,235,.42));border-radius:28px;padding:18px;margin:14px 0}
.graph-card>summary{cursor:pointer;list-style:none;display:flex;align-items:center;gap:10px}
.graph-card>summary::-webkit-details-marker{display:none}
.graph-card>summary::before{content:"";width:42px;height:42px;flex:none;border-radius:12px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.4'%3E%3Cpath d='M4 19V9m5.5 10V5M15 19v-8m5 8V7'/%3E%3C/svg%3E"),linear-gradient(120deg,#1F6E70,#359FA0);background-size:22px,cover;background-position:center,center;background-repeat:no-repeat,no-repeat}
.graph-card .g-title{font-weight:800;font-size:1.08rem;color:#173F41}
.graph-card .g-chip{margin-left:auto;background:#E7F2F0;color:#1F6E70;font-size:.68rem;font-weight:800;letter-spacing:.6px;padding:5px 11px;border-radius:999px}
.graph-card>summary::after{content:"⌄";font-size:1.3rem;color:#1F6E70;transition:transform .2s}
.graph-card[open ]>summary::after{transform:rotate(180deg)}
.graph-card>p{font-size:.86rem;color:#5d6f6d;margin-top:8px;line-height:1.5}
.graph-card .route-search{background:transparent;border:0;box-shadow:none;padding:0;margin:0}
.gdest{display:flex;align-items:center;gap:9px;background:rgba(231,242,240,.8);border-radius:14px;padding:9px 12px;margin-top:12px;font-size:.9rem;color:#33514f}
.gdest i{font-style:normal;background:#1F6E70;color:#fff;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex:none}
.gstep{display:flex;align-items:center;gap:10px;font-size:1.02rem;font-weight:800;color:#173F41;margin:16px 0 9px}
.gstep i{font-style:normal;background:#1F6E70;color:#fff;width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;flex:none}
.graph-card .origin-field{display:block}
.graph-card #origin-label{width:100%;box-sizing:border-box;padding:13px 15px;border:1.6px solid #BFD9D5;border-radius:18px;background:rgba(255,255,255,.88);font-size:.95rem;color:#20302f}
.graph-card #origin-label:focus{outline:2px solid #359FA0;border-color:#359FA0}
.origin-results{background:#fff;border-radius:16px;box-shadow:0 10px 26px rgba(18,56,58,.16);margin-top:8px;overflow:hidden}
.origin-results button{display:flex;width:100%;gap:10px;align-items:flex-start;text-align:left;background:none;border:0;border-bottom:1px solid #EDF2F0;padding:11px 14px;cursor:pointer}
.origin-results button:last-child{border-bottom:0}
.origin-results button:hover{background:#F2F8F7}
.origin-results .op-name{font-weight:700;color:#20302f;font-size:.92rem;display:block}
.origin-results .op-addr{color:#71827d;font-size:.78rem;display:block;margin-top:1px}
.origin-results .op-dist{margin-left:auto;color:#1F6E70;font-weight:800;font-size:.8rem;white-space:nowrap}
.origin-results .op-empty{padding:12px 14px;color:#71827d;font-size:.85rem}
.g-or{display:flex;align-items:center;gap:12px;color:#71827d;font-size:.8rem;font-weight:700;margin:12px 0 10px}
.g-or::before,.g-or::after{content:"";flex:1;height:1.5px;background:rgba(31,110,112,.25)}
.graph-card .origin-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.graph-card #use-location{background:#E7F2F0;color:#1F6E70;border:1.5px solid #BFD9D5;border-radius:999px;padding:12px;font-weight:800;font-size:.92rem;cursor:pointer}
.graph-card #pick-map{background:linear-gradient(90deg,#1F6E70,#359FA0);color:#fff;border:0;border-radius:999px;padding:12px;font-weight:800;font-size:.92rem;cursor:pointer;text-decoration:none}
.graph-card .mode-label{display:none!important}
.mode-pills{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
.mode-pills button{border:1.6px solid #BFD9D5;background:rgba(255,255,255,.75);color:#1F6E70;border-radius:999px;padding:11px 2px;font-weight:800;font-size:.82rem;cursor:pointer}
.mode-pills button.active{background:#1F6E70;border-color:#1F6E70;color:#fff}
.graph-card #compare-routes{width:100%;border-radius:999px;padding:14px;font-size:1rem}
.graph-card #compare-routes:not(:disabled){background:linear-gradient(90deg,#1F6E70,#359FA0)}
.ghint{font-size:.8rem;color:#8a6d1f;background:#FFF7DB;border-radius:10px;padding:8px 11px;margin-top:9px}
.graph-card:has(#compare-routes:not(:disabled)) .ghint{display:none}
.gnote{font-size:.8rem;color:#71827d;margin-top:8px}
/* === Penyatuan sisa bar kiri: judul, GPS, disclaimer, kondisi === */
.graph-panel .eyebrow{display:inline-flex;align-items:center;gap:7px;background:#E7F2F0;color:#1F6E70;font-weight:800;font-size:.7rem;letter-spacing:1.2px;padding:6px 12px;border-radius:999px;margin:0}
.graph-panel .eyebrow::before{content:"";width:8px;height:8px;border-radius:50%;background:#1F6E70;flex:none}
.graph-map-page .graph-panel h1{font-size:26px;line-height:1.16;margin:10px 0 6px}
.graph-panel .route-intro{font-size:.9rem;margin:0 0 14px}
.graph-panel .location-help{border:1px solid rgba(255,255,255,.55);border-radius:22px;background:linear-gradient(145deg,rgba(255,255,255,.68),rgba(255,250,235,.42));box-shadow:0 14px 34px rgba(18,56,58,.13);padding:18px;color:#183f40}
.graph-panel .location-help-title{display:flex;align-items:center;gap:10px;font-size:1.02rem;font-weight:800;margin:0 0 8px}
.graph-panel .location-help-title span{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:#E7F2F0;color:#1F6E70;font-size:1.15rem;flex:none}
.graph-panel .location-help details>summary{display:block;background:#E7F2F0;color:#1F6E70;font-weight:800;border-radius:999px;padding:10px 14px;cursor:pointer;list-style:none;margin-top:10px}
.graph-panel .location-help details>summary::-webkit-details-marker{display:none}
.graph-panel .disclaimer{border-radius:18px;text-align:center;padding:12px 14px}
.graph-panel h2.subheading{font-size:1.18rem;line-height:1.3}
.graph-panel h2.subheading::before{content:"";display:inline-block;vertical-align:-9px;margin-right:10px;width:38px;height:38px;border-radius:11px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.6'%3E%3Cpath d='M12 6v8'/%3E%3Ccircle cx='12' cy='17.4' r='1.4' fill='%23ffffff' stroke='none'/%3E%3C/svg%3E"),linear-gradient(120deg,#1F6E70,#359FA0);background-size:22px,cover;background-position:center,center;background-repeat:no-repeat,no-repeat}
.graph-panel .condition-list-tools .btn{width:100%;background:linear-gradient(90deg,#1F6E70,#359FA0);color:#fff;border:0;border-radius:999px;padding:13px;font-weight:800;font-size:.95rem;cursor:pointer}
.graph-panel .condition-list-tools p{font-size:.82rem;color:#5d6f6d;line-height:1.5}
.graph-panel .condition-sync-status{font-size:.82rem;color:#71827d}
.graph-panel .mini-hazard{border-radius:18px}
/* === Kartu gabungan: tujuan + analisis satu kartu, aux di bawah === */
.graph-map-page .route-status.liquid-glass{margin:0 2px 12px;border-radius:20px;padding:16px 18px;font-size:.93rem;line-height:1.55;color:#37473f;background:linear-gradient(135deg,rgba(255,241,205,.9),rgba(255,255,255,.5));border:1px solid rgba(255,255,255,.65);box-shadow:0 12px 28px rgba(120,100,20,.13)}
.merged-intro{font-size:.85rem;color:#5d6f6d;margin:0 0 6px}
.merged-steps{display:grid;gap:10px}
.merged-aux{border-top:2px solid rgba(31,110,112,.14);margin-top:16px;padding-top:12px;display:grid;gap:8px}
.merged-aux .map-campus-discovery{margin-top:4px}
/* === Unified Search (autocomplete combobox) === */
.unified-search{position:relative;margin-bottom:12px}
.unified-search label{display:block;font-size:13px;font-weight:600;color:#1f6e70;margin-bottom:6px}
.us-bar{position:relative;display:flex;align-items:center}
.us-icon{position:absolute;left:12px;width:18px;height:18px;color:#8aa3a4;pointer-events:none;flex-shrink:0}
.us-bar input{width:100%;padding:12px 40px 12px 38px;border:1.5px solid #d3deda;border-radius:14px;font-size:15px;background:#fff;color:#16292a;outline:none;transition:border-color .15s,box-shadow .15s}
.us-bar input:focus{border-color:#1f6e70;box-shadow:0 0 0 3px rgba(31,110,112,.12)}
.us-bar input[readonly]{background:#f4f8f7;font-weight:600}
.us-clear{position:absolute;right:8px;width:26px;height:26px;border-radius:50%;border:0;background:#e3eaea;color:#5a6e6f;font-size:17px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.us-clear:hover{background:#cfd9d9;color:#1a2e2f}
.us-results{position:absolute;top:100%;left:0;right:0;z-index:50;margin-top:6px;background:#fff;border:1px solid #e6edec;border-radius:16px;box-shadow:0 12px 32px rgba(18,56,58,.14);max-height:320px;overflow-y:auto;padding:6px}
.us-group{padding:8px 12px 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#8aa3a4}
.us-item{display:flex;align-items:center;gap:10px;width:100%;padding:11px 12px;border:0;border-radius:12px;background:transparent;cursor:pointer;text-align:left;transition:background .12s}
.us-item:hover{background:#f0f6f5}
.us-item-main{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px}
.us-item-main small{font-size:11.5px;font-weight:650;letter-spacing:.05em;color:#62797a}
.us-item-main b{font-size:14.5px;font-weight:600;color:#16292a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.us-item-main small{font-size:12px;color:#8aa3a4}
.us-badge{flex-shrink:0;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:20px;letter-spacing:.03em}
.us-badge.is-school{background:#e6f4ea;color:#1e7e34}
.us-badge.is-campus{background:#e8f0fe;color:#1a56db}
.us-badge.is-ror{background:#fef3e2;color:#b7791f}
.us-arrow{flex-shrink:0;font-size:18px;color:#b8c8c8;font-weight:300}
.us-item:hover .us-arrow{color:#1f6e70;transform:translateX(2px)}
.us-status{font-size:13px;color:#6b7f80;margin:6px 2px 0;min-height:18px}
.locked-institution{margin-bottom:12px}.locked-institution label{display:block;font-size:13px;font-weight:600;color:#1f6e70;margin-bottom:6px}.locked-institution .us-bar{background:#f0f4f4}.locked-institution input{background:transparent;border:0;flex:1;font-size:14px;color:#2a3b3b;font-weight:600}
/* Mobile */
@media(max-width:640px){
 .us-bar input{font-size:16px;padding:13px 42px 13px 40px}
 .us-results{max-height:280px}
 .us-item{padding:13px 12px}
 .us-item-main b{font-size:15px}
}
/* === CSS asli aplikasi yang dipulihkan (dulu dirujuk via push-Blade yang tidak ter-render di server): discovery kampus dari campus-map-v5.css + daftar kondisi & status tile dari map-auto-v7.css === */
.map-campus-discovery{border-top:1px solid #d8e3db;padding-top:16px;min-width:0;width:100%;box-sizing:border-box}.map-campus-directory-link{display:flex;align-items:center;gap:12px;min-height:60px;width:100%;min-width:0;box-sizing:border-box;padding:12px;border:1px solid #c5dbd5;border-radius:12px;background:#edf5ef;text-decoration:none;color:#195b5e}.map-campus-directory-link>span:nth-child(2){flex:1;min-width:0;overflow-wrap:anywhere}.map-campus-directory-link strong{display:block;font-size:15px;line-height:1.5;font-weight:700}.map-campus-directory-link small{display:block;font-size:14px;line-height:1.5;color:#496568;margin-top:2px}.map-campus-link-icon{display:grid;place-items:center;flex:0 0 32px;height:32px}.map-campus-link-icon svg{width:28px;height:28px}.map-campus-directory-link>span:last-child{flex:0 0 16px;font-size:18px}.map-campus-discovery p{font-size:14px;line-height:1.65;color:#496568;margin:12px 0 0;overflow-wrap:anywhere}.map-campus-directory-link:hover{background:#e2eee7;border-color:#8eaaa6}.graph-map-page .campus-options{border:1px solid #cfddd4;border-radius:12px;background:#f7f8ef;padding:16px;margin:16px 0}.graph-map-page .campus-options>summary{font-size:15px;line-height:1.55;min-height:44px;display:list-item;padding-right:8px;overflow-wrap:anywhere}.graph-map-page .campus-options p{font-size:14px;line-height:1.65}.graph-map-page .campus-options input{box-sizing:border-box;border:1px solid #9db7b4;border-radius:10px;padding:12px;background:#fff;min-height:48px;width:100%;min-width:0}.graph-map-page .campus-options .btn{white-space:normal;overflow-wrap:anywhere;min-height:44px}.graph-map-page .campus-options .graph-search{padding:16px;margin-top:12px}.map-campus-directory-link:focus-visible,.campus-options summary:focus-visible{outline:3px solid #237c80;outline-offset:3px}@media(max-width:700px){.graph-map-page .saved-route-search{padding:16px}.map-campus-directory-link{gap:8px;padding:12px}.graph-map-page .campus-options .graph-search{padding:12px}.campus-link-filter{display:grid;grid-template-columns:1fr}.campus-link-filter button{min-height:44px}.campus-link-source h2{font-size:22px}.campus-link-options>label{padding:12px}}
.condition-list-tools{display:grid;gap:8px;margin:16px 0}.condition-list-tools .btn{min-height:44px;white-space:normal}.condition-list-tools p{font-size:14px;line-height:1.65;color:#496568;margin:0}.condition-list-row{display:grid;grid-template-columns:minmax(0,1fr) 44px;gap:8px;align-items:center;margin-bottom:12px;min-width:0}.condition-list-row .mini-hazard{margin:0;min-width:0;overflow-wrap:anywhere}.condition-list-row .mini-hazard>div{min-width:0}.condition-list-row .mini-hazard small{font-size:14px}.condition-focus-button{display:grid;place-items:center;width:44px;height:44px;min-height:44px;border:1px solid #9db7b4;border-radius:10px;background:#edf5ef;color:#195b5e;cursor:pointer;padding:10px}.condition-focus-button svg{width:24px;height:24px}.condition-focus-button:hover{background:#dceee4}.condition-focus-button:focus-visible{outline:3px solid #237c80;outline-offset:3px}.condition-sync-status{overflow-wrap:anywhere}.map-tile-status{position:absolute;z-index:550;top:128px;right:16px;max-width:300px;margin:0;background:#fff7dc;border:1px solid #c1a464;border-radius:10px;padding:12px;font-size:14px;line-height:1.6;color:#53452a;box-shadow:0 2px 8px #0001;pointer-events:none}.map-tile-status[hidden]{display:none!important}@media(max-width:900px){.map-tile-status{top:88px;right:12px;max-width:calc(100% - 80px)}.condition-list-row{gap:6px}}

/* Blok peramping panel untuk layar sempit */
@media (max-width:640px){
.map-panel{padding:12px}
.graph-card{padding:14px 13px;border-radius:22px;margin:10px 0}
.graph-card>summary{gap:8px}
.graph-card>summary::before{width:36px;height:36px;border-radius:10px}
.graph-card .g-title{font-size:.99rem}
.graph-card .g-chip{font-size:.62rem;padding:4px 9px}
.graph-card>p{font-size:.82rem;margin-top:6px}
.merged-intro{font-size:.82rem}
.gstep{font-size:.94rem;margin:13px 0 7px;gap:8px}
.gstep i{width:26px;height:26px;font-size:.78rem}
.us-bar input{font-size:14.5px;padding:11px 38px 11px 36px;border-radius:12px}
.graph-card #origin-label{font-size:.9rem;padding:11px 13px;border-radius:14px}
.graph-card .origin-actions{gap:8px}
.graph-card #use-location,.graph-card #pick-map{padding:10px 8px;font-size:.86rem}
.mode-pills{gap:5px}
.mode-pills button{font-size:.76rem;padding:9px 1px;white-space:nowrap}
.graph-card #compare-routes{padding:12px;font-size:.94rem}
.graph-card .btn{padding:11px;font-size:.9rem;min-height:42px}
#route-status{padding:12px 14px;font-size:.86rem;border-radius:16px}
.ghint{font-size:.76rem}
.g-or{margin:10px 0 8px}
.map-campus-directory-link{min-height:52px;padding:10px}
.map-campus-directory-link strong{font-size:14px}
.map-campus-directory-link small{font-size:12.5px}
}
</style>
<style>
/* === Panel peta melayang berhalaman (redesain modern minimalis) === */
.map-page.graph-map-page .graph-panel .eyebrow{display:none}
.mp-head{display:flex;align-items:center;justify-content:space-between;gap:10px}
.mp-brand{display:inline-flex;align-items:center;gap:8px;font-family:Poppins,Inter,sans-serif;font-weight:700;font-size:.95rem;color:var(--teal-dark,#1F6E70)}
.mp-brand i{width:9px;height:9px;border-radius:50%;background:var(--teal,#359FA0);flex:none}
.mp-segs{display:flex;gap:5px}
.mp-seg{width:26px;height:5px;border-radius:99px;border:0;background:rgba(20,52,58,.13);cursor:pointer;padding:0}
.mp-seg.is-done{background:rgba(53,159,160,.45)}
.mp-seg.is-on{background:var(--teal,#359FA0)}
.mp-seg:disabled{cursor:default;opacity:.5}
.map-page.graph-map-page [data-mp-page]{display:none}
.map-page.graph-map-page [data-mp-page].is-active{display:block}
.mp-h1{font-family:Poppins,Inter,sans-serif;font-size:1.32rem;font-weight:700;color:#14343A;margin:14px 0 2px;line-height:1.2}
.mp-ptitle{font-family:Poppins,Inter,sans-serif;font-size:1.2rem;font-weight:700;color:#14343A;margin:14px 0 4px;line-height:1.25}
.mp-psub{font-size:.87rem;color:#5B6F70;line-height:1.55;margin:0 0 8px}
.mp-lbl{font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#687C7E}
.mp-pempty{font-size:.86rem;color:#687C7E;line-height:1.5}
.map-page.graph-map-page .graph-panel .route-status{border-radius:14px;padding:11px 13px;font-size:.85rem;margin:10px 0 2px}
.map-page.graph-map-page .graph-card{background:none;border:0;box-shadow:none;padding:0;margin:2px 0 0;border-radius:0}
.map-page.graph-map-page .graph-card>summary{display:none}
.map-page.graph-map-page .gstep{font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#687C7E;margin:18px 0 8px}
.map-page.graph-map-page .gstep i{display:none}
.map-page.graph-map-page .unified-search{margin-bottom:2px}
.map-page.graph-map-page .unified-search label{display:none}
.map-page.graph-map-page .us-bar input{border-radius:14px;background:#F4F8F6;border:1.5px solid rgba(20,52,58,.08);padding:13px 40px 13px 38px}
.map-page.graph-map-page .us-bar input[readonly]{background:#EDF4F1}
.map-page.graph-map-page .us-results{border-radius:16px;max-height:264px;box-shadow:0 18px 36px rgba(18,56,58,.18)}
.map-page.graph-map-page .locked-institution{border-radius:14px}
.map-page.graph-map-page #school-info-card{border-radius:14px}
.map-page.graph-map-page #show-saved-routes{width:100%;border-radius:13px;margin-top:10px;background:#EDF4F1;color:var(--teal-dark,#1F6E70);border:1px solid rgba(20,52,58,.06);box-shadow:none}
.map-page.graph-map-page .graph-card #origin-label{background:#F4F8F6;border:1.5px solid rgba(20,52,58,.08);border-radius:14px;padding:13px 15px}
.map-page.graph-map-page .graph-card #origin-label:focus{outline:2px solid var(--teal,#359FA0);border-color:var(--teal,#359FA0)}
.map-page.graph-map-page .graph-card .origin-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.map-page.graph-map-page .graph-card #use-location,.map-page.graph-map-page .graph-card #pick-map{background:#EDF4F1;color:var(--teal-dark,#1F6E70);border:0;border-radius:13px;padding:12px;font-weight:700}
.map-page.graph-map-page .mode-pills{display:flex;background:#EDF3F0;border-radius:13px;padding:4px;gap:4px}
.map-page.graph-map-page .mode-pills button{flex:1;border:0;background:none;border-radius:10px;padding:9px 0;color:#687C7E;font-weight:600;font-size:.84rem;white-space:nowrap}
.map-page.graph-map-page .mode-pills button.active{background:#fff;color:#14343A;font-weight:700;box-shadow:0 3px 10px rgba(18,56,58,.13)}
.map-page.graph-map-page .graph-card #compare-routes{border-radius:15px;padding:14px;margin-top:2px}
.map-page.graph-map-page .mp-alt-label{font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#687C7E;margin:20px 0 2px}
.map-page.graph-map-page .route-card{border:1.5px solid rgba(20,52,58,.09);border-radius:16px;padding:14px;margin:10px 0;box-shadow:none;background:#fff}
.map-page.graph-map-page .route-card.selected{border-color:var(--teal,#359FA0);background:rgba(53,159,160,.05)}
.map-page.graph-map-page .route-letter{width:30px;height:30px;font-size:.85rem}
.map-page.graph-map-page .route-card h2{font-size:1.3rem;margin:8px 0 2px}
.map-page.graph-map-page .analysis-summary{border-radius:14px;margin-top:12px}
.map-page.graph-map-page .mp-cond-head{display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin:20px 0 2px}
.mp-seeall{border:0;background:none;color:var(--teal-dark,#1F6E70);font-weight:600;font-size:.83rem;cursor:pointer;padding:0}
.map-page.graph-map-page #mp-cond-preview .condition-list-row{margin-bottom:4px}
.map-page.graph-map-page .graph-panel h2.subheading{font-family:Poppins,Inter,sans-serif;font-size:1.2rem;font-weight:700;margin:14px 0 4px;color:#14343A}
.map-page.graph-map-page .graph-panel h2.subheading::before{display:none}
.map-page.graph-map-page .merged-aux{border-top:0;margin-top:2px;padding-top:0}
.mp-back{display:inline-flex;align-items:center;gap:7px;border:0;background:none;color:var(--teal-dark,#1F6E70);font-weight:600;font-size:.9rem;cursor:pointer;padding:0;font-family:Inter,sans-serif}
.mp-route-name{font-family:Poppins,Inter,sans-serif;font-size:1.35rem;font-weight:700;margin:10px 0 0;color:#14343A;line-height:1.2}
.mp-stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:14px}
.mp-stat{background:#F4F8F6;border-radius:13px;padding:10px 11px}
.mp-stat b{display:block;font-family:Poppins,Inter,sans-serif;font-weight:700;font-size:.95rem;color:#14343A}
.mp-stat span{font-size:.64rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#687C7E}
.mp-warn{border-radius:13px;padding:11px 12px;font-size:.84rem;line-height:1.5;margin-top:12px;background:#FFF4E8;border:1px solid rgba(255,140,82,.35);color:#7A4416}
.mp-warn.is-clear{background:rgba(53,159,160,.08);border-color:rgba(53,159,160,.3);color:var(--teal-dark,#1F6E70)}
.mp-d-hazard{display:flex;gap:10px;align-items:center;padding:9px 0;border-bottom:1px solid #E3EFE9;text-decoration:none;color:#14343A;font-size:.9rem;font-weight:500}
.mp-turns{list-style:none;margin:4px 0 0;padding:0}
.mp-turns li{display:flex;gap:11px;padding:8px 0;border-bottom:1px solid #E3EFE9;font-size:.88rem;color:#14343A}
.mp-turns li:last-child{border-bottom:0}
.mp-turns .n{flex:none;width:9px;height:9px;border-radius:50%;border:2.5px solid var(--teal,#359FA0);margin-top:5px;background:#fff}
.mp-turns .d{margin-left:auto;color:#687C7E;font-size:.79rem;white-space:nowrap}
.mp-cta{width:100%;border:0;border-radius:15px;padding:14px;background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;font-family:Poppins,Inter,sans-serif;font-weight:600;font-size:.95rem;cursor:pointer;margin-top:16px;box-shadow:0 12px 24px rgba(31,110,112,.26)}
.mp-ticket{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #E3EFE9}
.mp-ticket .tn{font-weight:600;font-size:.9rem;color:#14343A}
.mp-ticket .tm{font-size:.78rem;color:#687C7E;margin-top:1px}
.mp-ticket button{margin-left:auto;flex:none;border:1px solid rgba(20,52,58,.14);background:#fff;border-radius:999px;padding:7px 13px;font-weight:600;font-size:.8rem;color:var(--teal-dark,#1F6E70);cursor:pointer}
.mp-flash{animation:mpflash 1.2s ease}
@keyframes mpflash{0%{box-shadow:0 0 0 3px rgba(53,159,160,.55)}100%{box-shadow:0 0 0 0 rgba(53,159,160,0)}}
@media(min-width:901px){
 .map-page.graph-map-page{display:block;position:relative;height:100vh;height:100dvh;overflow:hidden}
 .map-page.graph-map-page .map-art{position:absolute;inset:0}
 .map-page.graph-map-page .leaflet-shell #route-map{height:100%;min-height:0}
 .map-page.graph-map-page .graph-panel{position:absolute;z-index:650;top:94px;left:18px;bottom:18px;width:402px;max-width:calc(100vw - 36px);overflow-y:auto;overscroll-behavior:contain;background:rgba(255,255,255,.97);border-radius:24px;box-shadow:0 24px 48px rgba(18,56,58,.22);padding:20px 20px 22px;scrollbar-width:thin;scrollbar-color:rgba(53,159,160,.5) transparent}
 .map-page.graph-map-page .graph-panel::-webkit-scrollbar{width:5px}
 .map-page.graph-map-page .graph-panel::-webkit-scrollbar-thumb{background:rgba(53,159,160,.45);border-radius:99px}
 .map-page.graph-map-page .map-legend{left:auto;right:18px;bottom:96px}
}
@media(max-width:900px){
 .map-page.graph-map-page{display:block;position:relative;height:100dvh;overflow:hidden}
 .map-page.graph-map-page .map-art{position:absolute;inset:0}
 .map-page.graph-map-page .leaflet-shell #route-map{height:100%;min-height:0}
 .map-page.graph-map-page .graph-panel{position:absolute;z-index:650;left:0;right:0;bottom:0;top:auto;width:auto;max-height:64dvh;overflow-y:auto;overscroll-behavior:contain;background:rgba(255,255,255,.98);border-radius:24px 24px 0 0;box-shadow:0 -16px 36px rgba(18,56,58,.22);padding:16px 16px 22px;scrollbar-width:thin}
 .map-page.graph-map-page .graph-panel::before{content:"";display:block;width:44px;height:5px;border-radius:99px;background:rgba(20,52,58,.18);margin:0 auto 12px}
 .map-page.graph-map-page .leaflet-shell .report-fab{top:14px;right:14px;bottom:auto}
 .map-page.graph-map-page .map-tile-status{top:70px}
}
</style>


<style>
/* === Koreksi geometri panel + peramping halaman 1 + info halaman 2 === */
.map-page.graph-map-page .graph-panel .route-status{padding:9px 12px;font-size:.83rem;line-height:1.45;margin:8px 0 0}
.map-page.graph-map-page #show-saved-routes{padding:10px;font-size:.9rem;margin-top:8px}
.map-page.graph-map-page #pick-school{margin:8px auto 0;padding:7px 15px;font-size:.82rem}
.map-page.graph-map-page .ghint{padding:7px 10px;font-size:.76rem;margin-top:7px}
.map-page.graph-map-page .gnote{display:none}
.map-page.graph-map-page .gstep{margin:14px 0 7px}
.map-page.graph-map-page .graph-panel .location-help{padding:13px 14px;border-radius:18px}
.map-page.graph-map-page .graph-panel .location-help-title{font-size:.95rem;margin-bottom:5px}
.map-page.graph-map-page .graph-panel .location-help p{font-size:.84rem;line-height:1.5}
.map-page.graph-map-page .graph-panel .disclaimer{font-size:.76rem;padding:9px 11px;line-height:1.45}
.mp-info{border:1px solid #E3EFE9;border-radius:16px;padding:13px 14px;margin-top:12px;background:#fff}
.mp-info h3{font-family:Poppins,Inter,sans-serif;font-weight:600;font-size:.92rem;margin:0 0 9px;color:#14343A}
.mp-steps{list-style:none;margin:0;padding:0;display:grid;gap:9px}
.mp-steps li{display:flex;gap:10px;font-size:.86rem;line-height:1.5;color:#43595A}
.mp-steps .n{flex:none;width:22px;height:22px;border-radius:50%;background:rgba(53,159,160,.13);color:var(--teal-dark,#1F6E70);font-weight:700;font-size:.78rem;display:grid;place-items:center;margin-top:1px}
.mp-check{list-style:none;margin:0;padding:0;display:grid;gap:7px;font-size:.86rem;line-height:1.5;color:#43595A}
.mp-check li{display:flex;gap:9px}
.mp-check li::before{content:"\2713";color:var(--teal-dark,#1F6E70);font-weight:700;flex:none}
.mp-status-rows{display:grid;gap:8px;font-size:.86rem;line-height:1.5;color:#43595A}
.mp-status-rows p{margin:0}
.mp-status-rows b{color:#14343A}
@media(min-width:901px){
 .map-page.graph-map-page .graph-panel{position:fixed;top:94px;left:18px;bottom:18px;height:auto;max-height:none}
}
@media(max-width:900px){
 .map-page.graph-map-page{height:100vh;height:100dvh}
 .map-page.graph-map-page .graph-panel{position:fixed;top:auto;left:10px;right:10px;bottom:10px;width:auto;height:66vh;height:66dvh;max-height:calc(100dvh - 84px);border-radius:26px}
}
</style>

<style>
/* === Penyamaan visual panel dengan mockup yang disetujui === */
.mp-nolabel{display:none!important}
.map-page.graph-map-page .graph-panel{padding:22px 22px 24px}
.mp-h1{font-size:1.55rem;margin-top:12px}
.mp-brand{font-size:1rem}
.map-page.graph-map-page .unified-search{background:#F4F8F6;border:1px solid rgba(20,52,58,.05);border-radius:16px;padding:11px 13px 12px;margin-bottom:0}
.map-page.graph-map-page .unified-search label{display:block;font-size:.66rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#687C7E;margin:0 0 3px}
.map-page.graph-map-page .us-bar input{background:transparent;border:0;border-radius:0;padding:3px 30px 3px 27px;font-size:.95rem;box-shadow:none}
.map-page.graph-map-page .us-bar input:focus{border-color:transparent;box-shadow:none}
.map-page.graph-map-page .us-bar input[readonly]{font-family:Poppins,Inter,sans-serif;font-weight:600;color:#14343A;background:transparent}
.map-page.graph-map-page .us-icon{left:1px;color:#1F6E70}
.map-page.graph-map-page .us-bar:not(:has(input[readonly]))::after{content:"";position:absolute;right:3px;top:50%;width:8px;height:8px;border-right:2px solid #9db8b5;border-bottom:2px solid #9db8b5;transform:translateY(-70%) rotate(45deg);pointer-events:none}
.map-page.graph-map-page .us-clear{right:-2px;background:rgba(20,52,58,.07)}
.map-page.graph-map-page .us-results{margin-top:10px}
.map-page.graph-map-page .us-status{margin:5px 1px 0;font-size:.79rem;min-height:0}
.map-page.graph-map-page .locked-institution{background:#F4F8F6;border:1px solid rgba(20,52,58,.05);border-radius:16px;padding:11px 13px 12px}
.map-page.graph-map-page .locked-institution label{font-size:.66rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#687C7E;margin:0 0 3px}
.map-page.graph-map-page .locked-institution .us-bar{background:transparent}
.map-page.graph-map-page .locked-institution input{font-family:Poppins,Inter,sans-serif;font-weight:600;color:#14343A;font-size:.95rem;padding:3px 0 3px 27px}
.map-page.graph-map-page #school-info-card{background:#F4F8F6!important;border:0!important;border-radius:14px!important;box-shadow:none!important}
.map-page.graph-map-page #show-saved-routes{display:inline-block;width:auto;background:none;border:0;color:#1F6E70;font-weight:600;font-size:.85rem;padding:4px 0;margin:10px 16px 0 0;box-shadow:none}
.map-page.graph-map-page .graph-card #pick-school{display:inline-flex;background:none;border:0;box-shadow:none;color:#1F6E70;font-weight:600;font-size:.85rem;padding:4px 0;margin:10px 0 0;border-radius:0}
.map-page.graph-map-page .origin-field{position:relative;display:block;border-bottom:1px solid #E3EFE9;padding-bottom:2px}
.map-page.graph-map-page .origin-field::before{content:"";position:absolute;left:1px;top:7px;width:20px;height:20px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231F6E70' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11Z'/%3E%3Ccircle cx='12' cy='10' r='2.6'/%3E%3C/svg%3E") center/contain no-repeat;pointer-events:none}
.map-page.graph-map-page .graph-card #origin-label{background:transparent;border:0;border-radius:0;padding:8px 2px 9px 30px;font-size:.95rem}
.map-page.graph-map-page .graph-card #origin-label:focus{outline:none;border:0}
.map-page.graph-map-page .origin-results{margin-top:8px}
.map-page.graph-map-page .g-or{display:none}
.map-page.graph-map-page .graph-card .origin-actions{display:flex;flex-direction:row-reverse;justify-content:flex-start;gap:20px;margin-top:9px}
.map-page.graph-map-page .graph-card #use-location,.map-page.graph-map-page .graph-card #pick-map{background:none;border:0;border-radius:0;color:#1F6E70;font-weight:600;font-size:.88rem;padding:2px 0;box-shadow:none}
.map-page.graph-map-page .mode-pills{margin-top:2px}
.map-page.graph-map-page .graph-card #compare-routes{font-family:Poppins,Inter,sans-serif;letter-spacing:.01em}
.map-page.graph-map-page .route-card{border:0;border-bottom:1px solid #E3EFE9;border-radius:0;padding:13px 0 15px;margin:0;background:transparent;box-shadow:none}
.map-page.graph-map-page .route-card.selected{border-bottom-color:#E3EFE9;background:rgba(53,159,160,.05);border-radius:14px;padding-left:11px;padding-right:11px}
.map-page.graph-map-page .route-letter{width:26px;height:26px;font-size:.78rem}
.map-page.graph-map-page .load{font-size:.71rem;font-weight:700;padding:5px 10px}
.map-page.graph-map-page .load.light{background:rgba(53,159,160,.12);color:#1F6E70}
.map-page.graph-map-page .load.moderate{background:#FFF1D6;color:#8A6414}
.map-page.graph-map-page .load.heavy{background:#FDE3DC;color:#A03A1E}
.map-page.graph-map-page .route-primary{margin-top:9px}
.map-page.graph-map-page .route-primary h2{font-family:Inter,sans-serif;font-size:1.04rem;font-weight:700;margin:0}
.map-page.graph-map-page .route-primary b{font-size:.88rem;color:#687C7E;font-weight:600}
.map-page.graph-map-page .route-card p{font-size:.8rem;line-height:1.5}
.map-page.graph-map-page .route-hazards{font-size:.79rem}
.map-page.graph-map-page .route-card details summary{font-size:.83rem}
.map-page.graph-map-page .condition-list-row{grid-template-columns:minmax(0,1fr) 34px;gap:8px;align-items:center;margin-bottom:0;border-bottom:1px solid #E3EFE9;padding:1px 0}
.map-page.graph-map-page .condition-list-row .mini-hazard{display:flex;align-items:center;gap:11px;border:0;padding:9px 0;background:none;box-shadow:none}
.map-page.graph-map-page .condition-list-row .mini-hazard>span{width:9px;height:9px;border-radius:50%;background:currentColor;font-size:0;flex:none;display:block}
.map-page.graph-map-page .condition-list-row .mini-hazard>span svg{display:none}
.map-page.graph-map-page .condition-list-row .mini-hazard>i{display:none}
.map-page.graph-map-page .condition-list-row .mini-hazard b{font-size:.92rem}
.map-page.graph-map-page .condition-list-row .mini-hazard small{font-size:.79rem}
.map-page.graph-map-page .condition-focus-button{width:34px;height:34px;min-height:34px;border-radius:50%;padding:0;border:1px solid rgba(20,52,58,.15);background:#fff}
.map-page.graph-map-page .condition-focus-button svg{width:16px;height:16px}
@media(max-width:900px){
 .map-page.graph-map-page .graph-panel{padding:18px 18px 20px}
}
</style>

<style>
/* === Konsolidasi panel hasil rombak (gaya mockup, tanpa kelas warisan) === */
.mp2-label{font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#687C7E;margin:16px 0 6px}
.map-page.graph-map-page #route-status{background:transparent;border:0;box-shadow:none;padding:2px 2px 0;margin:10px 0 0;font-size:.8rem;line-height:1.45;color:#687C7E;border-radius:0}
.map-page.graph-map-page #us-status-institusi{display:none}
.map-page.graph-map-page #school-info-card{display:none!important}
.map-page.graph-map-page .ghint{display:none!important}
.map-page.graph-map-page #show-saved-routes{display:inline-block;background:none;border:0;color:#1F6E70;font-weight:600;font-size:.85rem;padding:4px 0;margin:10px 16px 0 0;cursor:pointer;font-family:Inter,sans-serif;vertical-align:middle}
.map-page.graph-map-page #pick-school{display:inline-flex;align-items:center;gap:7px;background:none;border:0;box-shadow:none!important;color:#1F6E70;font-weight:600;font-size:.85rem;padding:4px 0;margin:10px 0 0;cursor:pointer;font-family:Inter,sans-serif;vertical-align:middle}
.map-page.graph-map-page .origin-field{position:relative;display:block;border-bottom:1px solid #E3EFE9;padding-bottom:2px}
.map-page.graph-map-page .origin-field::before{content:"";position:absolute;left:1px;top:7px;width:20px;height:20px;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231F6E70' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11Z'/%3E%3Ccircle cx='12' cy='10' r='2.6'/%3E%3C/svg%3E") center/contain no-repeat;pointer-events:none}
.map-page.graph-map-page #origin-label{width:100%;box-sizing:border-box;background:transparent;border:0;border-radius:0;padding:8px 2px 9px 30px;font-size:.95rem;color:#14343A;font-family:Inter,sans-serif}
.map-page.graph-map-page #origin-label:focus{outline:none}
.map-page.graph-map-page .origin-actions{display:flex;flex-direction:row;justify-content:flex-end;gap:20px;margin-top:9px}
.map-page.graph-map-page .origin-actions>*{width:auto;flex:none}
.map-page.graph-map-page #pick-map{order:1}
.map-page.graph-map-page #use-location{order:2}
.map-page.graph-map-page #use-location,.map-page.graph-map-page #pick-map{background:none;border:0;color:#1F6E70;font-weight:600;font-size:.88rem;padding:2px 0;cursor:pointer;font-family:Inter,sans-serif}
.map-page.graph-map-page #compare-routes{width:100%;border:0;border-radius:15px;padding:14px;font-size:.95rem;font-family:Poppins,Inter,sans-serif;font-weight:600;cursor:pointer;background:rgba(31,110,112,.45);color:#fff;margin-top:12px}
.map-page.graph-map-page #compare-routes:not(:disabled){background:linear-gradient(135deg,#2A8C8E,#1F6E70);box-shadow:0 12px 24px rgba(31,110,112,.22)}
.map-page.graph-map-page .unified-search{background:transparent;border:0;box-shadow:none;padding:0}
.map-page.graph-map-page .unified-search label{font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#687C7E;margin:0 0 7px 2px}
.map-page.graph-map-page .us-bar{background:#fff;border:1px solid #DFE9E4;border-radius:16px;height:54px;padding:0 14px 0 16px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.05);transition:border-color .15s,box-shadow .15s}
.map-page.graph-map-page .us-bar:focus-within{border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13),0 10px 24px rgba(18,56,58,.05)}
.map-page.graph-map-page .us-icon{left:16px;width:20px;height:20px;color:#1F6E70}
.map-page.graph-map-page .us-bar input{height:100%;padding:0 34px 0 32px;font-size:.95rem;color:#14343A}
.map-page.graph-map-page .us-bar input::placeholder{color:#8AA0A2}
.map-page.graph-map-page .us-bar:not(:has(input[readonly]))::after{right:16px;border-color:#B9CCCA;width:7px;height:7px}
.map-page.graph-map-page .us-clear{right:10px;width:24px;height:24px;background:rgba(20,52,58,.06)}
.map-page.graph-map-page .us-results{margin-top:8px;border:1px solid #E6EDEC;border-radius:16px;box-shadow:0 16px 40px rgba(18,56,58,.13)}
.map-page.graph-map-page .us-item{border-radius:12px}
.map-page.graph-map-page .us-item:hover{background:#F2F7F5}
.map-page.graph-map-page .locked-institution{background:transparent;border:0;box-shadow:none;padding:0}
.map-page.graph-map-page .locked-institution label{font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#687C7E;margin:0 0 7px 2px}
.map-page.graph-map-page .locked-institution .us-bar{background:#fff;border:1px solid #DFE9E4;border-radius:16px;height:54px;padding:0 14px 0 16px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.05);align-items:center}
.map-page.graph-map-page .locked-institution .us-icon{left:16px;width:20px;height:20px;color:#1F6E70}
.map-page.graph-map-page .locked-institution input{height:100%;padding:0 0 0 32px;font-size:.95rem}
.map-page.graph-map-page .map-panel{transition:transform .38s cubic-bezier(.32,.72,.24,1)}
.map-page.graph-map-page .mp-hide{width:30px;height:30px;flex:none;display:grid;place-items:center;border:0;border-radius:50%;background:rgba(20,52,58,.05);color:#49696B;cursor:pointer}
.map-page.graph-map-page .mp-hide:hover{background:rgba(20,52,58,.1)}
.map-page.graph-map-page.mp-collapsed .map-panel{transform:translateX(calc(-100% - 26px));pointer-events:none}
.map-page.graph-map-page .mp-show{display:none;position:fixed;top:auto;bottom:18px;left:18px;z-index:70;align-items:center;gap:8px;border:1px solid #E1EAE5;border-radius:999px;background:#fff;color:#14343A;font-family:Poppins,Inter,sans-serif;font-weight:600;font-size:.88rem;padding:10px 16px 10px 13px;box-shadow:0 10px 28px rgba(18,56,58,.16);cursor:pointer}
.map-page.graph-map-page .mp-show i{width:9px;height:9px;border-radius:50%;background:var(--teal,#359FA0);flex:none}
.map-page.graph-map-page.mp-collapsed .mp-show{display:inline-flex}
.map-page.graph-map-page .condition-list-row .mini-hazard>span.mp2-ico{width:32px;height:32px;border-radius:0!important;flex:none;display:grid;place-items:center;background:transparent!important;border:0!important}
.map-page.graph-map-page .condition-list-row .mini-hazard>span.mp2-ico svg{width:26px!important;height:26px!important;display:block}
.map-page.graph-map-page .condition-list-row .mini-hazard>span.mp2-ico img{width:26px;height:26px;object-fit:contain;display:block}
.map-page.graph-map-page .graph-panel .location-help{margin-top:16px}
@media(max-width:900px){
 .map-page.graph-map-page .graph-panel .route-results{display:grid;overflow:visible}
 .map-page.graph-map-page .graph-route-card{min-width:0}
 .map-page.graph-map-page #show-saved-routes{margin-right:12px}
 .map-page.graph-map-page #show-saved-routes,.map-page.graph-map-page #pick-school{white-space:nowrap;font-size:.82rem}
 .map-page.graph-map-page .us-bar input{font-size:16px}
 .map-page.graph-map-page.mp-collapsed .map-panel{transform:translateY(calc(100% + 22px))}
 .map-page.graph-map-page .mp-show{top:auto;bottom:104px;left:10px}
}
</style>
@if(($school->status??'active')==='paused')<div class="school-paused-banner" role="status">{{ __('Pemantauan sekolah ini sedang dijeda. Peta tetap dapat dibaca.') }}</div>@endif
<div class="map-page graph-map-page liquid-root" data-liquid-root id="route-app" data-endpoint="{{ route('routes.compare') }}" data-saved-endpoint="{{ route('routes.saved') }}" data-feed-endpoint="{{ route('map.conditions') }}" data-campus-endpoint="{{ route('campus-directory.search') }}" data-map-school-endpoint="{{ route('school-directory.search-nationwide') }}" data-school-detail-endpoint="{{ route('school-directory.detail') }}" data-resolve-endpoint="{{ route('school-directory.resolve-coordinates') }}" data-provision-endpoint="{{ route('school-directory.provision') }}" data-buffer-meters="{{ config('ruteaman.route_buffer_meters') }}">
 @include('partials.nav')
 <aside class="map-panel graph-panel liquid-root" data-liquid-root>
  <div class="mp-head"><span class="mp-brand"><i aria-hidden="true"></i>RuteAman</span><div class="mp-segs" role="group" aria-label="{{ __('Halaman panel peta') }}"><button type="button" class="mp-seg is-on" data-mp-go="1" aria-label="{{ __('Halaman 1: Rencanakan rute') }}"></button><button type="button" class="mp-seg" data-mp-go="2" aria-label="{{ __('Halaman 2: Institusi') }}"></button><button type="button" class="mp-seg" data-mp-go="3" aria-label="{{ __('Halaman 3: Laporan kondisi di peta') }}"></button><button type="button" class="mp-seg" data-mp-go="4" aria-label="{{ __('Halaman 4: Detail rute') }}" disabled></button></div><button type="button" id="mp-hide" class="mp-hide" aria-label="{{ __('Sembunyikan panel') }}" title="{{ __('Sembunyikan panel') }}" aria-expanded="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button></div>
  <section data-mp-page="1" class="is-active">
  <h1 class="mp-h1">{{ __('Rencanakan rute') }}</h1>
<div class="route-status liquid-glass" data-glass-variant="status" id="route-status" role="status" aria-live="polite">{{ __('Memuat kondisi rute sekolah…') }}</div>
  <div class="mp2-planner">
   @auth
    @if(auth()->user()->school_id && auth()->user()->school)
     <div class="locked-institution">
      <label>{{ __('Institusi kamu (terkunci)') }}</label>
      <div class="us-bar">
       <svg class="us-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-4h6v4"/></svg>
       <input type="text" value="{{ auth()->user()->school->name }}" readonly aria-label="{{ __('Institusi terkunci') }}">
      </div>
      <p class="us-status">{{ __('Wilayah pemantauan terkunci sesuai akun dan tidak dapat diubah.') }}</p>
     </div>
    @else
<div class="unified-search" id="search-institusi">
    <label for="us-input-institusi">{{ __('Tujuan') }}</label>
    <div class="us-bar">
     <svg class="us-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-4h6v4"/></svg>
     <input id="us-input-institusi" type="search" autocomplete="off" placeholder="{{ __('Ketik nama / singkatan...') }}" aria-label="{{ __('Cari institusi') }}">
     <button type="button" id="us-clear-institusi" class="us-clear" aria-label="{{ __('Hapus pilihan') }}" hidden>&times;</button>
    </div>
    <div id="us-results-institusi" class="us-results" role="listbox" aria-label="{{ __('Hasil pencarian sekolah dan kampus') }}" hidden></div>
    <p id="us-status-institusi" class="us-status" role="status" aria-live="polite"></p>
   </div>
    @endif
   @else
<div class="unified-search" id="search-institusi">
    <label for="us-input-institusi">{{ __('Tujuan') }}</label>
    <div class="us-bar">
     <svg class="us-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-4h6v4"/></svg>
     <input id="us-input-institusi" type="search" autocomplete="off" placeholder="{{ __('Ketik nama / singkatan...') }}" aria-label="{{ __('Cari institusi') }}">
     <button type="button" id="us-clear-institusi" class="us-clear" aria-label="{{ __('Hapus pilihan') }}" hidden>&times;</button>
    </div>
    <div id="us-results-institusi" class="us-results" role="listbox" aria-label="{{ __('Hasil pencarian sekolah dan kampus') }}" hidden></div>
    <p id="us-status-institusi" class="us-status" role="status" aria-live="polite"></p>
   </div>
   @endauth
   <div id="school-info-card" hidden style="background:#fff;border:1px solid #e0e0e0;border-radius:12px;padding:16px;margin-top:12px;box-shadow:0 2px 8px rgba(0,0,0,.08)">
    <div style="display:flex;justify-content:space-between;align-items:start">
     <div><h3 id="sic-name" style="margin:0 0 4px;font-size:16px"></h3><p id="sic-meta" style="margin:0;font-size:13px;color:#666"></p></div>
     <button type="button" id="sic-close" style="background:none;border:0;font-size:20px;cursor:pointer;color:#999">×</button>
    </div>
   </div>
   <select id="school-select" hidden>@include('partials.institution-options',['institutions'=>$schools,'selectedId'=>$school?->id])</select>
   <button type="button" id="show-saved-routes">{{ __('Perbarui rute tersimpan') }}</button>
   <button type="button" id="pick-school"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.6"/></svg><span>{{ __('Pilih institusi lewat peta') }}</span></button>
   <div class="graph-search merged-steps">
    <p class="mp2-label">{{ __('Titik awal') }}</p>
    <label class="origin-field"><span style="position:absolute;left:-9999px">{{ __('Titik awal') }}</span><input id="origin-label" type="text" placeholder="{{ __('Ketik nama tempat…') }}" autocomplete="off"></label>
    <div id="origin-results" class="origin-results" hidden></div>
    <p class="g-or"><span>{{ __('atau pakai cara lain') }}</span></p>
    <div class="origin-actions"><button type="button" id="use-location">{{ __('◎ GPS saya') }}</button><button type="button" id="pick-map">{{ __('Pilih di peta') }}</button></div>
    <input type="hidden" id="origin-lat"><input type="hidden" id="origin-lng">
    <label class="mode-label" style="display:none">{{ __('Moda perjalanan') }}<select id="route-profile"><option value="foot-walking">{{ __('Jalan kaki') }}</option><option value="cycling-regular">{{ __('Sepeda') }}</option><option value="driving-motorcycle">{{ __('Motor — jalur kendaraan') }}</option><option value="driving-car">{{ __('Mobil') }}</option></select></label>
    <div class="mode-pills" id="mode-pills" role="radiogroup" aria-label="{{ __('Pilihan mode') }}"><button type="button" data-profile="foot-walking" class="active">{{ __('Jalan kaki') }}</button><button type="button" data-profile="cycling-regular">{{ __('Sepeda') }}</button><button type="button" data-profile="driving-motorcycle">{{ __('Motor') }}</button><button type="button" data-profile="driving-car">{{ __('Mobil') }}</button></div>
    <button class="btn btn-dark" type="button" id="compare-routes" disabled>{{ __('Analisis alternatif rute') }}</button><p class="ghint">{{ __('Tombol aktif setelah titik awal dipilih — ketik nama tempat, pakai GPS, atau klik “Pilih di peta”.') }}</p><p class="gnote">{{ __('Hasil muncul pada daftar rute di dalam kartu ini.') }}</p>
   </div>
   <p class="mp-alt-label" id="mp-alt-label" hidden>{{ __('Alternatif rute') }}</p>
   <div id="route-results" class="route-results" aria-live="polite"></div>
   <div class="analysis-summary liquid-glass" data-glass-variant="status" id="analysis-summary" hidden></div>

  </div>
  <details class="graph-options campus-options" hidden><summary>{{ __('Tujuan perjalanan lain (opsional)') }}</summary>
   <p>{{ __('Cari institusi di ROR atau tentukan lokasi kampus secara manual. Direktori ini belum mencakup semua kampus Indonesia/dunia; koordinat direktori adalah referensi kota, bukan gerbang kampus.') }}</p>
   <div class="route-search graph-search"><label for="campus-country">{{ __('Kode negara (ID = Indonesia; kosong = seluruh dunia)') }}</label><input id="campus-country" value="ID" maxlength="2" autocomplete="off">
   <label for="campus-query">{{ __('Nama kampus') }}</label><input id="campus-query" type="search" minlength="3" maxlength="150" autocomplete="off" placeholder="{{ __('Contoh: Universitas Diponegoro') }}">
   <button type="button" id="campus-search" class="btn btn-soft">{{ __('Cari kampus') }}</button><div id="campus-results" class="campus-results"></div>
   <button type="button" id="campus-more" class="btn btn-soft" hidden>{{ __('Hasil berikutnya') }}</button>
   <button type="button" id="campus-pin" class="btn btn-dark">{{ __('Tentukan pin tujuan kampus di peta') }}</button><p id="campus-status" role="status" aria-live="polite">{{ __('Pilih pin tujuan yang tepat sebelum menganalisis rute ke kampus. Tidak mengubah sekolah/akun atau hak administrator.') }}</p>
   </div>
  </details>
  <div class="mp-cond-head"><span class="mp-lbl">{{ __('Kondisi di sekitar tujuan') }}</span><button type="button" class="mp-seeall" data-mp-go="3">{{ __('Lihat semua') }}</button></div>
  <div id="mp-cond-preview"><p class="mp-pempty">{{ __('Memuat kondisi…') }}</p></div>
  @include('partials.location-help')
  <p class="disclaimer liquid-glass" data-glass-variant="notice">{{ __('Informasi komunitas, bukan jaminan kondisi jalan. Bandingkan jarak dan Beban Hazard tanpa rekomendasi keamanan.') }}</p>
  </section>
  <section data-mp-page="2">
  <h2 class="mp-ptitle">{{ __('Institusi') }}</h2>
  <p class="mp-psub">{{ __('Sekolah atau kampusmu belum muncul saat dicari di halaman 1? Usulkan di sini supaya bisa ditambahkan ke peta.') }}</p>
   <div class="merged-aux">
   <style>
   .aux-bar{margin-top:14px;background:#fff;border:1px solid var(--line,#e5dcb8);border-radius:16px;box-shadow:0 2px 10px rgba(18,56,58,.05);overflow:hidden}
   .aux-row{display:flex;align-items:center;gap:12px;width:100%;padding:12px 14px;background:none;border:0;font:inherit;text-align:left;cursor:pointer;color:#12383a}
   .aux-row:hover{background:#f4faf8}
   .aux-row + .aux-row{border-top:1px solid #eef2ec}
   .aux-ic{flex-shrink:0;width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#e8f5f1,#d9efe9);color:var(--teal-dark,#1f6e70);display:flex;align-items:center;justify-content:center}
   .aux-label{flex:1;font-size:14.5px;font-weight:600}
   #btn-suggest .aux-label{font-weight:700;color:var(--teal-dark,#1f6e70)}
   .aux-arrow{color:#9db8b5;font-size:20px;line-height:1}
   #check-box{padding:2px 14px 12px}
   #check-ticket{flex:1;padding:9px 12px;border:1px solid var(--line,#e5dcb8);border-radius:10px;font-size:13.5px;background:#fffdf5;color:#12383a}
   #check-go{padding:9px 18px;border:0;border-radius:10px;background:linear-gradient(135deg,var(--teal,#359fa0),var(--teal-dark,#1f6e70));color:#fff;font-size:13.5px;font-weight:700;cursor:pointer}
   #check-result{padding:0 14px}
   #check-result:empty{display:none}
   #pick-school{display:flex;align-items:center;gap:8px;width:fit-content;margin:12px auto 2px;padding:9px 18px;background:linear-gradient(135deg,#eef7f4,#e0f0ea);border:1px solid #cde4dc;border-radius:999px;color:var(--teal-dark,#1f6e70);font-size:13.5px;font-weight:700;text-decoration:none;cursor:pointer;box-shadow:0 1px 4px rgba(18,56,58,.05)}
   #pick-school:hover{background:linear-gradient(135deg,#e0f0ea,#d2e8df)}
   </style>
   <div class="aux-bar">
   <button type="button" id="btn-suggest" class="aux-row"><span class="aux-ic" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M2 21h20"/><path d="M12 9v6M9 12h6"/></svg></span><span class="aux-label">{{ __('Usulkan Institusi') }}</span><span class="aux-arrow" aria-hidden="true">›</span></button>
   <button type="button" id="btn-check" class="aux-row"><span class="aux-ic" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><path d="M13 7v2M13 11v2M13 15v2"/></svg></span><span class="aux-label">{{ __('Cek status usulan') }}</span><span class="aux-arrow" aria-hidden="true">›</span></button>
   <div id="check-box" hidden style="margin-top:8px;display:flex;gap:8px">
    <input id="check-ticket" placeholder="Nomor tiket (cth: SRN-...)">
    <button type="button" id="check-go">{{ __('Cek') }}</button>
   </div>
   <p id="check-result" role="status" style="font-size:13px;margin:8px 0 0"></p>
   </div>
   <div class="map-campus-discovery">
    <a class="map-campus-directory-link" href="{{ route('campus-catalog.index',['country'=>'ID']) }}"><span class="map-campus-link-icon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m2 8 10-5 10 5-10 5L2 8Z"/><path d="M6 11v7c4 3 8 3 12 0v-7M22 9v9"/></svg></span><span><strong>{{ __('Jelajahi direktori kampus') }}</strong><small>{{ __('Indonesia & dunia · cari nama atau kode PT') }}</small></span><span aria-hidden="true">↗</span></a>
    <p>{{ __('Belum ada di pilihan? Temukan namanya di katalog. Wilayah pemantauan perlu dihubungkan atau diaktifkan oleh pengelola.') }}</p>
   </div>
   </div>
  <div id="mp-my-tickets" hidden>
   <p class="mp-lbl" style="margin-top:18px">{{ __('Tiket usulan tersimpan di perangkat ini') }}</p>
   <div id="mp-ticket-list"></div>
  </div>
  <div class="mp-info">
   <h3>{{ __('Cara kerja usulan') }}</h3>
   <ol class="mp-steps">
    <li><span class="n">1</span><span>{{ __('Kirim usulan lewat tombol Usulkan Institusi dan simpan nomor tiket yang kamu terima.') }}</span></li>
    <li><span class="n">2</span><span>{{ __('Pengelola memeriksa nama, alamat, dan titik lokasi institusi yang kamu usulkan.') }}</span></li>
    <li><span class="n">3</span><span>{{ __('Bila diterima, institusi muncul di peta dan bisa dipilih sebagai tujuan di halaman 1.') }}</span></li>
   </ol>
  </div>
  <div class="mp-info">
   <h3>{{ __('Siapkan sebelum mengusulkan') }}</h3>
   <ul class="mp-check">
    <li>{{ __('Nama resmi institusi sesuai papan nama atau dokumen sekolah/kampus.') }}</li>
    <li>{{ __('NPSN untuk sekolah/madrasah, bila ada.') }}</li>
    <li>{{ __('Alamat lengkap: jalan, desa/kelurahan, kecamatan, kabupaten/kota.') }}</li>
    <li>{{ __('Perkiraan titik lokasi di peta, supaya pengelola mudah memverifikasi.') }}</li>
   </ul>
  </div>
  <div class="mp-info">
   <h3>{{ __('Arti status usulan') }}</h3>
   <div class="mp-status-rows">
    <p><b>{{ __('Menunggu') }}</b> — {{ __('usulanmu sudah masuk dan menunggu ditinjau pengelola.') }}</p>
    <p><b>{{ __('Diterima') }}</b> — {{ __('institusi sudah ditambahkan ke direktori dan bisa dipilih sebagai tujuan.') }}</p>
    <p><b>{{ __('Ditolak') }}</b> — {{ __('pengelola memberi catatan alasannya; baca lewat Cek status usulan dengan nomor tiketmu.') }}</p>
   </div>
  </div>
  </section>
  <section data-mp-page="3">
  <h2 class="subheading">{{ __('Laporan kondisi di peta') }}<small style="display:block;font-size:.875rem;font-weight:400;margin-top:8px">{{ __('Peta menampilkan laporan kondisi dari semua wilayah, tidak hanya dari institusi yang dipilih. Saat menghitung beban rute, yang dihitung hanya kondisi yang masih aktif dan berada di sepanjang jalur rute.') }}</small></h2><div class="condition-list-tools"><button type="button" class="btn btn-soft" id="fit-condition-markers">{{ __('Tampilkan kondisi') }}</button></div><p class="condition-sync-status" id="condition-sync-status" role="status"></p><div class="hazard-text-list" id="school-hazard-list">@forelse($hazards as $hazard)<a class="mini-hazard liquid-glass" data-glass-variant="row" href="{{ route('hazards.public',$hazard) }}"><span>!</span><div><b>{{ $hazard->type->label }}</b><small>{{ $hazard->last_confirmed_at?->diffForHumans() ?? $hazard->first_reported_at->diffForHumans() }} · {{ $hazard->confirmations_count }} {{ __('konfirmasi') }}</small></div><i>›</i></a>@empty<p>{{ __('Belum ada laporan aktif di area ini. Ini bukan jaminan kondisi jalan.') }}</p>@endforelse</div>
  </section>
  <section data-mp-page="4" id="mp-detail">
   <button type="button" class="mp-back" id="mp-detail-back"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m14.5 5.5-6.5 6.5 6.5 6.5"/></svg>{{ __('Semua alternatif') }}</button>
   <h2 class="mp-route-name" id="mp-d-name">{{ __('Detail rute') }}</h2>
   <div class="mp-stats"><div class="mp-stat"><b id="mp-d-dur">&ndash;</b><span>{{ __('Durasi') }}</span></div><div class="mp-stat"><b id="mp-d-dist">&ndash;</b><span>{{ __('Jarak') }}</span></div><div class="mp-stat"><b id="mp-d-load">&ndash;</b><span>{{ __('Beban hazard') }}</span></div></div>
   <div class="mp-warn" id="mp-d-warn" hidden></div>
   <p class="mp-lbl" style="margin-top:18px">{{ __('Kondisi di rute ini') }}</p>
   <div id="mp-d-hazards"></div>
   <p class="mp-lbl" style="margin-top:18px">{{ __('Petunjuk arah ringkas') }}</p>
   <ol class="mp-turns" id="mp-d-steps"></ol>
   <button type="button" class="mp-cta" id="mp-d-use">{{ __('Gunakan rute ini') }}</button>
  </section>
 </aside>
 <button type="button" id="mp-show" class="mp-show" aria-label="{{ __('Tampilkan panel') }}" aria-expanded="false"><i aria-hidden="true"></i>{{ __('Rencanakan rute') }}<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg></button>
 <style data-ruteaman-fabroutes="v1">
/* Tombol Lapor ramping berkesan kaca cair + kartu alternatif rute berbentuk kartu */
.leaflet-shell .report-fab.liquid-glass{display:inline-flex;align-items:center;gap:8px;padding:8px 16px 8px 9px;font-family:Poppins,Inter,sans-serif;font-size:14px;font-weight:600;letter-spacing:.01em;color:#4A2C14;text-decoration:none;background:linear-gradient(135deg,rgba(255,176,128,.80),rgba(255,140,82,.68));border:1px solid rgba(255,255,255,.65);box-shadow:inset 0 1px 0 rgba(255,255,255,.55),0 1px 2px rgba(146,64,14,.16),0 10px 24px rgba(255,140,82,.30);backdrop-filter:blur(14px) saturate(150%);-webkit-backdrop-filter:blur(14px) saturate(150%);transition:transform .15s ease,box-shadow .15s ease}
.leaflet-shell .report-fab.liquid-glass:active{transform:scale(.97)}
.report-fab .fab-plus{display:grid;place-items:center;width:24px;height:24px;border-radius:50%;background:#E86A24;color:#fff;flex:none;box-shadow:inset 0 1px 0 rgba(255,255,255,.35)}
.map-page.graph-map-page .route-card.graph-route-card{border:1px solid #E3EFE9;border-radius:18px;background:#fff;padding:15px 16px;margin:0;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.07)}
.map-page.graph-map-page .route-card.graph-route-card.selected{border-color:rgba(53,159,160,.65);background:rgba(53,159,160,.045);box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(31,110,112,.12)}
.map-page.graph-map-page .route-results{gap:12px}
.graph-route-card .route-letter{width:32px;height:32px;font-size:.88rem}
.graph-route-card .load{font-size:11px;padding:5px 10px;font-weight:700}
.graph-route-card .route-primary{margin-top:10px;align-items:baseline}
.map-page.graph-map-page .route-primary h2{font-size:1.5rem;font-family:Poppins,Inter,sans-serif;font-weight:600;margin:0}
.graph-route-card .route-primary b{font-size:.82rem;color:#496365;font-weight:600}
.graph-route-card>p{font-size:.8rem;color:#496365;line-height:1.5;margin:6px 0 0}
.graph-route-card .route-hazards{margin-top:9px;gap:6px}
.graph-route-card .route-hazards a,.graph-route-card .route-hazards>span{display:block;background:#F7FAF8;border:1px solid #EAF1ED;border-radius:11px;padding:8px 11px;font-size:.78rem;line-height:1.5;color:#34565A;text-decoration:none}
.graph-route-card details{margin-top:11px;border-top:1px solid #EAF1ED;padding-top:9px}
.graph-route-card details summary{font-size:.82rem;font-weight:600}
.graph-route-card .direction-list li{font-size:.8rem}
.graph-route-card .route-load-details table{font-size:.75rem}
</style>
<style data-ruteaman-legend="v2">
/* Legenda peta: tab vertikal ramping di tepi kanan peta (storyboard user);
   diketuk, kartu meluncur terbuka dari kanan. HP: tab di bawah tombol
   Lapor. Desktop: tab di kanan jauh, di atas tombol Lapor. */
.map-legend.condition-map-legend{border-radius:16px;padding:9px 12px 10px}
.condition-route-key{display:flex;gap:8px 12px;flex-wrap:nowrap}
.condition-route-key>span{white-space:nowrap}
.condition-route-key .legend-a,.condition-route-key .legend-b{width:20px}
.condition-route-key .legend-school{width:10px;height:10px}
.condition-map-legend details{margin-top:4px}
.condition-map-legend summary{font-size:13px;min-height:26px;display:flex;align-items:center;list-style:none;cursor:pointer}
.condition-map-legend summary::-webkit-details-marker{display:none}
.condition-map-legend summary::after{content:"";margin-left:auto;width:8px;height:8px;flex:none;border-right:2px solid #34565A;border-bottom:2px solid #34565A;transform:rotate(-45deg);transition:transform .25s ease}
.condition-map-legend .condition-legend-grid{gap:6px 12px;max-height:176px;padding:6px 0 2px}
.condition-map-legend .condition-legend-item{display:flex;align-items:center;font-size:13px;gap:8px}
.condition-map-legend .condition-legend-item i{width:20px;height:20px}
.condition-map-legend .condition-legend-item svg{width:20px;height:20px}
.condition-map-legend .condition-legend-note{font-size:11.5px;line-height:1.45;margin-top:6px;max-width:none}
.condition-map-legend .condition-legend-scroll-note{font-size:11.5px}
.map-legend.condition-map-legend:has(details[open]){width:min(320px,calc(100% - 24px));max-width:min(320px,calc(100% - 24px));animation:ra-legend-in .28s cubic-bezier(.32,.72,.24,1)}
@keyframes ra-legend-in{from{opacity:0;transform:translateX(14px)}to{opacity:1;transform:translateX(0)}}
.map-legend.condition-map-legend:not(:has(details[open])){width:40px;padding:0;border-radius:999px;background:rgba(255,255,255,.96)!important;border:1px solid #DFE9E4;box-shadow:0 1px 2px rgba(18,56,58,.06),0 10px 24px rgba(18,56,58,.14)}
.map-legend.condition-map-legend:not(:has(details[open])) .condition-route-key{display:none}
.map-legend.condition-map-legend:not(:has(details[open])) details{margin-top:0}
.map-legend.condition-map-legend:not(:has(details[open])) summary{flex-direction:column;justify-content:center;gap:10px;min-height:84px;padding:12px 0;font-size:0}
.map-legend.condition-map-legend:not(:has(details[open])) summary::before{content:"";width:17px;height:17px;flex:none;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231F6E70' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='M9 6h11M9 12h11M9 18h11'/%3E%3Ccircle cx='4.5' cy='6' r='1.7' fill='%231F6E70' stroke='none'/%3E%3Ccircle cx='4.5' cy='12' r='1.7' fill='%231F6E70' stroke='none'/%3E%3Ccircle cx='4.5' cy='18' r='1.7' fill='%231F6E70' stroke='none'/%3E%3C/svg%3E") center/contain no-repeat}
.map-legend.condition-map-legend:not(:has(details[open])) summary::after{margin:0;transform:rotate(135deg)}
.map-page.graph-map-page .map-legend.condition-map-legend{left:auto;right:18px;top:auto;bottom:84px}
@media (max-width:900px){
 .map-page.graph-map-page .map-legend.condition-map-legend{left:auto;right:12px;top:68px;bottom:auto}
 .map-legend.condition-map-legend:has(details[open]) .condition-legend-grid{max-height:120px}
}
@media (prefers-reduced-motion:reduce){
 .map-legend.condition-map-legend:has(details[open]){animation:none}
 .condition-map-legend summary::after{transition:none}
}
</style>
<div class="map-art leaflet-shell liquid-root" data-liquid-root><p id="map-tile-status" class="map-tile-status" role="status" hidden></p><div id="route-map" aria-label="{{ __('Peta titik awal, sekolah tujuan, alternatif rute, kondisi dan riwayat laporan') }}"></div><div class="map-legend condition-map-legend liquid-glass" data-glass-variant="legend"><div class="condition-route-key"><span><i class="legend-a"></i> {{ __('Rute A') }}</span><span><i class="legend-b"></i> {{ __('Alternatif') }}</span><span><i class="legend-school"></i> {{ __('Institusi') }}</span></div><details><summary>{{ __('Icon kondisi & riwayat') }}</summary><div class="condition-legend-grid" id="condition-legend" tabindex="0" role="region" aria-label="{{ __('Daftar ikon kondisi') }}"></div><small class="condition-legend-scroll-note">{{ __('Geser daftar untuk ikon lainnya.') }}</small><small class="condition-legend-note">{{ __('Terang → redup menunjukkan kebaruan laporan, bukan tingkat bahaya. Marker lama tetap ada.') }}</small></details></div>@auth @if(auth()->user()->school&&app(\App\Services\PermissionService::class)->feature(auth()->user()->school,'reporting',true))<a class="report-fab liquid-glass" data-glass-variant="action" href="{{ route('hazards.create') }}"><span class="fab-plus" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span> {{ __('Lapor') }}</a>@endif @else<a class="report-fab liquid-glass" data-glass-variant="action" href="{{ route('guest-reports.create',['school'=>$school?->id]) }}"><span class="fab-plus" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span> {{ __('Lapor') }}</a>@endauth</div>
</div>
<script type="application/json" id="school-map-data">{!! json_encode($schools->map(fn($s)=>['id'=>$s->id,'institution_type'=>$s->institution_type??'school','country_code'=>$s->country_code??'ID','name'=>$s->name,'lat'=>(float)$s->latitude,'lng'=>(float)$s->longitude,'catchment'=>null,'shared_layer'=>(bool)(json_decode($s->features??'{}',true)['shared_layer']??app(\App\Services\GlobalFeatureSettings::class)->enabled('shared_layer'))]),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<script type="application/json" id="hazard-map-data">{!! json_encode($markerPage['items'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>

<script>
(function(){
 const root=document.getElementById('route-app');
 if(!root)return;
 // Fallback: definisikan __ruteamanMap minimal jika app.js lama (tanpa API)
 if(!window.__ruteamanMap){
  window.__ruteamanMap={
   _map:null,
   getMap(){
    if(this._map)return this._map;
    try{
     const el=document.getElementById('route-map');
     // Cari instance Leaflet yang sudah ada
     if(el&&el._leaflet_id&&window.L){
      // Leaflet menyimpan maps di L.Map instances, coba cari
      for(const k in window){try{if(window[k] instanceof window.L.Map){this._map=window[k];break;}}catch(e){}}
     }
    }catch(e){}
    return this._map;
   },
   moveTo(lat,lng,name,zoom=14){
    lat=Number(lat);lng=Number(lng);
    if(!isFinite(lat)||!isFinite(lng))return false;
    const m=this.getMap();
    if(!m||!window.L)return false;
    try{
     if(window.__rmMarker){try{m.removeLayer(window.__rmMarker);}catch(e){}}
     const marker=window.L.circleMarker([lat,lng],{radius:14,color:'#fff',weight:3,fillColor:'#7b2d8b',fillOpacity:1}).addTo(m);
     if(name)marker.bindPopup('<b>'+String(name).replace(/</g,'&lt;')+'</b>').openPopup();
     window.__rmMarker=marker;
     // Paksa pindah + zoom dengan flyTo, fallback setView
     try{m.flyTo([lat,lng],zoom,{duration:1.2});}catch(e){m.setView([lat,lng],zoom);}
     // Pastikan zoom akhir benar setelah animasi
     setTimeout(()=>{try{m.setView([lat,lng],zoom,{animate:false});m.invalidateSize();}catch(e){}},1600);
     return true;
    }catch(e){console.warn('moveTo fallback gagal:',e);return false;}
   },
   selectSchoolById(id){
    const sel=document.getElementById('school-select');
    if(sel){sel.value=String(id);sel.dispatchEvent(new Event('change',{bubbles:true}));return true;}
    return false;
   }
  };
 }
 // Sinkronisasi: jika satu terkunci, yang lain dinonaktifkan (anti-nabrak)
 function syncLock(){
  const sInput=document.getElementById('us-input-sekolah'),kInput=document.getElementById('us-input-kampus');
  const sLocked=sInput&&sInput.hasAttribute('readonly'),kLocked=kInput&&kInput.hasAttribute('readonly');
  if(sInput){sInput.disabled=kLocked;document.getElementById('search-sekolah').style.opacity=kLocked?'.5':'1';}
  if(kInput){kInput.disabled=sLocked;document.getElementById('search-kampus').style.opacity=sLocked?'.5':'1';}
 }
 const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
 const api=()=>window.__ruteamanMap;
 // Pindahkan peta dengan berbagai fallback (untuk kompatibilitas app.js lama)
 function moveMapRobust(lat,lng,name,zoom){
  lat=Number(lat);lng=Number(lng);
  if(!isFinite(lat)||!isFinite(lng))return false;
  // Cara 1: API global baru
  const mApi=api();
  if(mApi&&mApi.moveTo){
   try{if(mApi.moveTo(lat,lng,name,zoom))return true;}catch(e){}
  }
  // Cara 2: Dispatch event untuk app.js lama
  try{
   document.dispatchEvent(new CustomEvent('school-selected',{detail:{name:name,latitude:lat,longitude:lng,npsn:'',_kind:'sekolah'}}));
   // Anggap berhasil jika event terkirim (app.js lama akan handle)
   return true;
  }catch(e){}
  // Cara 3: Cari instance Leaflet langsung
  try{
   const container=document.getElementById('route-map');
   // Leaflet menyimpan instance di _leaflet_id, cari via L
   if(window.L&&container){
    // Coba akses map yang sudah ada
    const maps=[];
    for(const key in container){if(key.startsWith('_leaflet'))maps.push(key);}
   }
  }catch(e){}
  return false;
 }
 // Fallback geocode via Nominatim
 async function geocode(q){
  try{
   const u=new URL('https://nominatim.openstreetmap.org/search');
   u.searchParams.set('q',q);u.searchParams.set('format','json');u.searchParams.set('limit','1');u.searchParams.set('countrycodes','id');
   const r=await fetch(u,{headers:{'Accept':'application/json'}});const d=await r.json();
   if(d&&d[0]&&d[0].lat)return{lat:parseFloat(d[0].lat),lng:parseFloat(d[0].lon)};
  }catch(e){}
  return null;
 }
 function getLocalData(){
  try{return JSON.parse(document.getElementById('school-map-data')?.textContent||'[]');}catch(e){return [];}
 }
 // ===== PENCARIAN TERPADU SEKOLAH + KAMPUS (lokal DB) =====
 function acronymOf(name){
  const paren=(name||'').match(/\(([A-Za-z][A-Za-z0-9]{1,9})\)/);
  if(paren)return paren[1].toUpperCase();
  const stop={dan:1,di:1,ke:1,dari:1,untuk:1,yang:1,the:1,of:1,for:1,and:1};
  const words=(name||'').replace(/[^A-Za-z0-9 ]/g,' ').split(/\s+/).filter(w=>w&&!stop[w.toLowerCase()]);
  if(words.length<2)return '';
  const ac=words.map(w=>w.charAt(0).toUpperCase()).join('');
  return ac.length>=2?ac:'';
 }
 function initUnifiedSearch(){
  const input=document.getElementById('us-input-institusi'),clearBtn=document.getElementById('us-clear-institusi'),
        results=document.getElementById('us-results-institusi'),status=document.getElementById('us-status-institusi'),
        wrap=document.getElementById('search-institusi');
  if(!input)return;
  let timer=null,locked=false;
  const setStatus=t=>{status.textContent=t;};
  function lock(name){locked=true;input.value=name;input.setAttribute('readonly','readonly');clearBtn.hidden=false;results.hidden=true;results.innerHTML='';}
  function unlock(){locked=false;input.value='';input.removeAttribute('readonly');clearBtn.hidden=true;results.hidden=true;results.innerHTML='';setStatus('');const sel=document.getElementById('school-select');if(sel)sel.selectedIndex=0;const card=document.getElementById('school-info-card');if(card)card.hidden=true;input.focus();}
  clearBtn.addEventListener('click',unlock);
  window.__ruteamanReflectSchoolSearch=function(item){
   if(!item||!item.name)return;
   lock(item.name);
   setStatus('Dipilih: '+item.name+'. Peta otomatis pindah ke lokasi.');
   const card=document.getElementById('school-info-card');
   if(card){
    document.getElementById('sic-name').textContent=item.name||'';
    const meta=[];
    if(item.npsn)meta.push('NPSN '+item.npsn);
    meta.push((item.institution_type||'school')==='campus'?'Kampus':'Sekolah');
    document.getElementById('sic-meta').textContent=meta.join(' \u00B7 ');
    card.hidden=false;
   }
  };
  function choose(item){
   // Hanya institusi terdaftar (koordinat selalu tersedia dari database)
   const isCampus=(item.institution_type||'school')==='campus';
   const sel=document.getElementById('school-select');
   if(sel){sel.value=String(item.id);sel.dispatchEvent(new Event('change',{bubbles:true}));}
   lock(item.name);
   setStatus('Dipilih: '+item.name+'. Peta otomatis pindah ke lokasi.');
   const card=document.getElementById('school-info-card');
   if(card){
    document.getElementById('sic-name').textContent=item.name||'';
    const meta=[];
    if(item.npsn)meta.push('NPSN '+item.npsn);
    meta.push(isCampus?'Kampus':'Sekolah');
    document.getElementById('sic-meta').textContent=meta.join(' \u00B7 ');
    card.hidden=false;
   }
  }
  (function(){
   const cc=document.getElementById('sic-close'),card=document.getElementById('school-info-card');
   if(cc&&card)cc.addEventListener('click',()=>{card.hidden=true;});
  })();
  function doSearch(){
   if(locked)return;
   const q=input.value.trim();results.innerHTML='';results.hidden=true;
   if(!q.length){setStatus('');return;}
   setStatus('Mencari...');
   const ql=q.toLowerCase(),qu=q.toUpperCase(),data=getLocalData();
   let hits=[];
   if(q.length>=2){
    hits=data.filter(x=>{const ac=acronymOf(x.name);return ac&&(ac===qu||ac.indexOf(qu)===0);});
   }
   if(q.length>=3){
    const seen={};hits.forEach(x=>{seen[x.id]=1;});
    hits=hits.concat(data.filter(x=>(x.name||'').toLowerCase().includes(ql)&&!seen[x.id]));
   }
   hits=hits.slice(0,10);
   if(hits.length){
    let nS=0,nK=0;
    hits.forEach(item=>{
     const isCampus=(item.institution_type||'school')==='campus';
     if(isCampus)nK++;else nS++;
     const b=document.createElement('button');b.type='button';b.className='us-item';
     const ac=acronymOf(item.name);
     b.innerHTML='<span class="us-item-main"><b>'+esc(item.name)+'</b>'+(ac?'<small>'+esc(ac)+'</small>':'')+'</span><span class="us-badge '+(isCampus?'is-campus':'is-school')+'">'+(isCampus?'Kampus':'Sekolah')+'</span><span class="us-arrow">\u203A</span>';
     b.addEventListener('click',()=>choose(item));results.append(b);
    });
    results.hidden=false;
    const parts=[];
    if(nS)parts.push(nS+' sekolah');
    if(nK)parts.push(nK+' kampus');
    setStatus(parts.join(' & ')+' ditemukan. Pilih salah satu.');
   }else{
    results.hidden=true;
    setStatus(q.length<3?'Ketik minimal 3 karakter, atau singkatan kampus (mis. ITB).':'Tidak ditemukan di data terdaftar. Hubungi admin untuk menambahkan.');
   }
  }
  input.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(doSearch,400);});
  input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();clearTimeout(timer);doSearch();}});
  input.addEventListener('focus',()=>{if(results.children.length&&!locked)results.hidden=false;});
  document.addEventListener('click',e=>{if(!wrap.contains(e.target))results.hidden=true;});
 }
 initUnifiedSearch();
})();
</script>

<div id="suggest-modal" hidden style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px">
 <div style="background:#fff;border-radius:12px;max-width:480px;width:100%;max-height:90vh;overflow:auto;padding:24px">
  <h3 style="margin:0 0 16px">{{ __('Usulkan Institusi') }}</h3><p style="font-size:13px;color:#666;margin:-8px 0 16px">{{ __('Institusi belum terdaftar? Usulkan di sini.') }}</p>
  <form id="suggest-form">
   <label style="display:block;margin-bottom:12px">{{ __('Kategori') }}
    <select name="category" required style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;margin-top:4px">
     <option value="new_school">{{ __('Institusi belum terdaftar') }}</option>
     <option value="wrong_name">{{ __('Nama institusi salah') }}</option>
     <option value="wrong_address">{{ __('Alamat tidak sesuai') }}</option>
     <option value="wrong_location">{{ __('Pin lokasi salah tempat') }}</option>
     <option value="closed">{{ __('Institusi sudah tutup') }}</option>
     <option value="duplicate">{{ __('Institusi duplikat') }}</option>
     <option value="other">{{ __('Lainnya') }}</option>
    </select>
   </label>
   <label style="display:block;margin-bottom:12px">{{ __('Nama institusi') }}
    <input name="proposed_name" required maxlength="255" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;margin-top:4px">
   </label>
   <label style="display:block;margin-bottom:12px">{{ __('NPSN (opsional)') }}
    <input name="npsn" maxlength="20" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;margin-top:4px">
   </label>
   <label style="display:block;margin-bottom:12px">{{ __('Deskripsi') }}
    <textarea name="description" rows="3" maxlength="2000" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;margin-top:4px"></textarea>
   </label>
   <label style="display:block;margin-bottom:16px">{{ __('Email (opsional, untuk tindak lanjut)') }}
    <input name="contact_email" type="email" maxlength="255" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;margin-top:4px">
   </label>
   <p id="suggest-status" role="status" style="font-size:13px;margin:0 0 12px"></p>
   <div style="display:flex;gap:8px;justify-content:flex-end">
    <button type="button" id="suggest-cancel" style="padding:10px 20px;border:1px solid #ccc;border-radius:8px;background:#fff">{{ __('Batal') }}</button>
    <button type="submit" style="padding:10px 20px;border:0;border-radius:8px;background:#1f6e70;color:#fff">{{ __('Kirim') }}</button>
   </div>
  </form>
 </div>
</div>
<script>
(function(){
 const modal=document.getElementById('suggest-modal'),btn=document.getElementById('btn-suggest'),
       cancel=document.getElementById('suggest-cancel'),form=document.getElementById('suggest-form'),
       status=document.getElementById('suggest-status');
 if(!modal||!btn)return;
 btn.addEventListener('click',()=>{modal.hidden=false;status.textContent='';});
 cancel.addEventListener('click',()=>{modal.hidden=true;});
 modal.addEventListener('click',e=>{if(e.target===modal)modal.hidden=true;});
 form.addEventListener('submit',async e=>{
  e.preventDefault();
  status.textContent='Mengirim...';
  const fd=new FormData(form),data={};
  fd.forEach((v,k)=>{if(v)data[k]=v;});
  try{
   const r=await fetch('/api/suggestions',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify(data)});
   const d=await r.json();
   if(r.ok){status.textContent='Berhasil! Nomor tiket: '+d.data.ticket_number+'. '+d.data.message;form.reset();}
   else{status.textContent='Gagal: '+(d.message||'Periksa isian form.');}
  }catch(err){status.textContent='Gagal mengirim. Periksa koneksi internet.';}
 });
})();
</script>


<script>
(function(){
 const btn=document.getElementById('btn-check'),box=document.getElementById('check-box'),
       input=document.getElementById('check-ticket'),go=document.getElementById('check-go'),
       result=document.getElementById('check-result');
 if(!btn)return;
 btn.addEventListener('click',()=>{box.hidden=!box.hidden;result.textContent='';});
 go.addEventListener('click',async()=>{
  const t=input.value.trim();
  if(!t){result.textContent='Masukkan nomor tiket.';return;}
  result.textContent='Mencari...';
  try{
   const r=await fetch('/api/suggestions/check?ticket_number='+encodeURIComponent(t),{headers:{'Accept':'application/json'}});
   const d=await r.json();
   if(r.ok){result.innerHTML='<strong>'+d.data.ticket_number+'</strong> ('+d.data.proposed_name+')<br>Status: <strong>'+d.data.status+'</strong>'+(d.data.review_notes?'<br>Catatan: '+d.data.review_notes:'');}
   else{result.textContent=d.message||'Tidak ditemukan.';}
  }catch(e){result.textContent='Gagal. Periksa koneksi.';}
 });
})();
</script>


<style data-ruteaman-originfield="v1">
/* Kolom titik awal jadi kartu putih bersih (menyamai kartu tujuan di atasnya):
   tanpa garis bawah era lama, fokus ring teal lembut; panel saran ikut dipoles
   (baris membulat tanpa garis pemisah, jarak jadi pil lembut, pin ganda dibuang). */
.map-page.graph-map-page .graph-search label.origin-field{border:0;padding:0}
.map-page.graph-map-page .graph-search .origin-field::before{left:15px;top:50%;transform:translateY(-50%);z-index:2}
.map-page.graph-map-page .graph-search #origin-label{background:#fff;border:1.5px solid #DFE9E4;border-radius:16px;padding:14px 15px 14px 44px;font-size:15px;font-weight:500;color:#14343A;box-shadow:0 1px 2px rgba(18,56,58,.05),0 8px 20px rgba(18,56,58,.06);transition:border-color .18s ease,box-shadow .18s ease}
.map-page.graph-map-page .graph-search #origin-label::placeholder{color:#8AA39C;font-weight:400}
.map-page.graph-map-page .graph-search #origin-label:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13),0 8px 20px rgba(18,56,58,.06)}
.map-page.graph-map-page .origin-results{border:1px solid #E3EFE9;border-radius:16px;box-shadow:0 2px 4px rgba(18,56,58,.05),0 16px 36px rgba(18,56,58,.13);padding:6px;margin-top:10px;background:#fff;overflow:hidden}
.map-page.graph-map-page .origin-results button{border:0;border-radius:12px;padding:10px 12px;gap:10px;align-items:center}
.map-page.graph-map-page .origin-results button:hover{background:#EAF4EE}
.map-page.graph-map-page .origin-results button>span:first-child{display:none}
.map-page.graph-map-page .origin-results .op-name{font-size:14.5px;font-weight:600;color:#14343A}
.map-page.graph-map-page .origin-results .op-addr{font-size:12px;color:#5F7A76;margin-top:1px}
.map-page.graph-map-page .origin-results .op-dist{margin-left:auto;background:#EAF4EE;color:#1F6E70;font-size:11.5px;font-weight:700;padding:4px 9px;border-radius:999px;white-space:nowrap}
.map-page.graph-map-page .origin-results .op-empty{padding:12px;font-size:13px;color:#5F7A76}
</style>
<script>
(function(){
  // Jembatan dari form hero beranda: parameter ?from=&from_lat=&from_lng=
  // mengisi titik awal panel rute lewat pencarian tempat milik panel sendiri.
  // Bila asal dipilih dari saran di beranda (ada koordinat), hasil pencarian
  // panel yang namanya cocok diklik otomatis; bila hanya diketik, daftar
  // sarannya dibuka agar pengguna mengetuk sendiri pilihannya.
  var p=new URLSearchParams(location.search);
  var from=(p.get('from')||'').trim();
  if(!from){return;}
  var hasCoords=isFinite(parseFloat(p.get('from_lat')))&&isFinite(parseFloat(p.get('from_lng')));
  var firstSeg=from.split(',')[0].trim();
  var filled=false,picked=false,observed=false,dispatches=0,mo=null;
  function unhidePanel(){
    var rootEl=document.getElementById('route-app');
    if(rootEl&&rootEl.classList.contains('mp-collapsed')){
      rootEl.classList.remove('mp-collapsed');
      try{localStorage.setItem('ra-mp-hidden','0');}catch(e){}
      var hb=document.getElementById('mp-hide'),sb=document.getElementById('mp-show');
      if(hb){hb.setAttribute('aria-expanded','true');}
      if(sb){sb.setAttribute('aria-expanded','false');}
    }
  }
  if(hasCoords){
    mo=new MutationObserver(function(){
      if(picked){return;}
      var box=document.querySelector('#origin-results');
      if(!box){return;}
      var btns=box.querySelectorAll('button');
      for(var i=0;i<btns.length;i++){
        var nm=btns[i].querySelector('.op-name');
        if(nm&&nm.textContent.trim()===firstSeg){picked=true;btns[i].click();if(mo){mo.disconnect();}break;}
      }
    });
  }
  var iv=setInterval(function(){
    var label=document.querySelector('#origin-label');
    var box=document.querySelector('#origin-results');
    if(!label){if(++dispatches>16){clearInterval(iv);}return;}
    unhidePanel();
    if(mo&&box&&!observed){observed=true;mo.observe(box,{childList:true,subtree:true});}
    if(picked||(box&&box.querySelector('button'))){clearInterval(iv);return;}
    if(!filled&&!label.value){label.value=from;filled=true;}
    if(label.value===from){label.dispatchEvent(new Event('input',{bubbles:true}));}
    if(++dispatches>16){clearInterval(iv);if(mo){setTimeout(function(){mo.disconnect();},5000);}}
  },700);
})();
</script>
<script>
(function(){
 const panel=document.querySelector('.graph-panel');if(!panel)return;
 const pages=panel.querySelectorAll('[data-mp-page]');
 const segs=panel.querySelectorAll('.mp-seg');
 const seg4=panel.querySelector('.mp-seg[data-mp-go="4"]');
 let currentCard=null;
 const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
 function go(n){
  pages.forEach(p=>p.classList.toggle('is-active',p.dataset.mpPage===String(n)));
  segs.forEach(sg=>{const k=Number(sg.dataset.mpGo);sg.classList.toggle('is-on',k===n);sg.classList.toggle('is-done',k<n);});
  try{panel.scrollTo({top:0});}catch(e){panel.scrollTop=0;}
 }
 panel.addEventListener('click',e=>{
  const g=e.target.closest('[data-mp-go]');
  if(g&&!g.disabled)go(Number(g.dataset.mpGo));
 });
 const results=document.getElementById('route-results'),altLabel=document.getElementById('mp-alt-label');
 if(results&&altLabel){new MutationObserver(()=>{altLabel.hidden=!results.children.length;}).observe(results,{childList:true});}
 function openDetail(card){
  currentCard=card;
  const letter=(card.querySelector('.route-letter')?.textContent||'').trim();
  document.getElementById('mp-d-name').textContent=letter?('Rute '+letter):'Detail rute';
  const h2=card.querySelector('.route-primary h2'),b=card.querySelector('.route-primary b');
  const h2t=h2?h2.textContent.trim():'',bt=b?b.textContent.trim():'';
  let dur='\u2013',dist='\u2013';
  if(/menit/.test(h2t)){dur=h2t;dist=bt.replace(/\s*\([^)]*\)\s*$/,'')||'\u2013';}
  else if(h2t){dist=h2t;}
  document.getElementById('mp-d-dur').textContent=dur;
  document.getElementById('mp-d-dist').textContent=dist;
  const load=card.querySelector('.load');
  document.getElementById('mp-d-load').textContent=load?load.textContent.replace(/^Beban\s+/,'').trim():'\u2013';
  const links=[...card.querySelectorAll('.route-hazards a')];
  const warn=document.getElementById('mp-d-warn');
  warn.hidden=false;
  if(links.length){warn.classList.remove('is-clear');warn.textContent='Rute ini melewati '+links.length+' kondisi aktif.';}
  else{warn.classList.add('is-clear');warn.textContent='Tidak ada kondisi aktif di sepanjang rute ini.';}
  document.getElementById('mp-d-hazards').innerHTML=links.length?links.map(a=>'<a class="mp-d-hazard" href="'+esc(a.getAttribute('href')||'#')+'"><span aria-hidden="true">\u26A0</span><span>'+esc(a.textContent.replace(/^\u26A0\s*/,''))+'</span></a>').join(''):'<p class="mp-pempty">Belum ada laporan aktif di rute ini.</p>';
  const steps=[...card.querySelectorAll('.direction-list li')].slice(0,6).map(li=>{
   const sp=li.querySelector('span');if(!sp)return '';
   const d=sp.querySelector('small')?.textContent||'';
   const ins=(sp.childNodes[0]?.textContent||'').trim()||sp.textContent.replace(d,'').trim();
   return '<li><span class="n" aria-hidden="true"></span><span>'+esc(ins)+'</span>'+(d?'<span class="d">'+esc(d)+'</span>':'')+'</li>';
  }).join('');
  document.getElementById('mp-d-steps').innerHTML=steps||'<li><span class="n" aria-hidden="true"></span><span>Petunjuk langkah tidak tersedia dari provider.</span></li>';
  if(seg4)seg4.disabled=false;
  go(4);
 }
 if(results){
  results.addEventListener('click',e=>{
   const card=e.target.closest('.graph-route-card');
   if(!card||e.target.closest('a,button,summary,details'))return;
   openDetail(card);
  });
  results.addEventListener('keydown',e=>{
   const card=e.target.closest('.graph-route-card');
   if(card&&e.target===card&&(e.key==='Enter'||e.key===' ')){e.preventDefault();openDetail(card);}
  });
 }
 document.getElementById('mp-detail-back')?.addEventListener('click',()=>go(1));
 document.getElementById('mp-d-use')?.addEventListener('click',()=>{
  go(1);
  if(currentCard){currentCard.scrollIntoView({block:'nearest',behavior:'smooth'});currentCard.classList.add('mp-flash');setTimeout(()=>currentCard.classList.remove('mp-flash'),1300);}
 });
 function mpDots(scope){
  (scope||document).querySelectorAll('.condition-list-row .mini-hazard>span').forEach(sp=>{
   if(sp.classList.contains('mp2-ico')||!sp.style.color)return;
   sp.classList.add('mp2-ico');
  });
 }
 const rootEl=document.getElementById('route-app'),hideBtn=document.getElementById('mp-hide'),showBtn=document.getElementById('mp-show');
 const setCollapsed=v=>{if(!rootEl)return;rootEl.classList.toggle('mp-collapsed',v);if(hideBtn)hideBtn.setAttribute('aria-expanded',String(!v));if(showBtn)showBtn.setAttribute('aria-expanded',String(v));try{localStorage.setItem('ra-mp-hidden',v?'1':'0')}catch(e){}};
 hideBtn?.addEventListener('click',()=>setCollapsed(true));
 showBtn?.addEventListener('click',()=>setCollapsed(false));
 try{if(localStorage.getItem('ra-mp-hidden')==='1')setCollapsed(true)}catch(e){}
 const cmpBtn=document.getElementById('compare-routes'),ghEl=document.querySelector('.ghint');
 if(cmpBtn&&ghEl){const syncGh=()=>{ghEl.hidden=!cmpBtn.disabled;};new MutationObserver(syncGh).observe(cmpBtn,{attributes:true,attributeFilter:['disabled']});syncGh();}
 const srcList=document.getElementById('school-hazard-list'),preview=document.getElementById('mp-cond-preview');
 if(srcList&&preview){
  const renderPreview=()=>{mpDots(srcList);
   const rows=srcList.querySelectorAll('.condition-list-row');
   if(rows.length){preview.innerHTML='';[...rows].slice(0,3).forEach(r=>preview.append(r.cloneNode(true)));}
   else{const p=srcList.querySelector('p');preview.innerHTML='<p class="mp-pempty">'+esc(p?p.textContent:'Belum ada kondisi publik yang dimuat.')+'</p>';}
  };
  new MutationObserver(renderPreview).observe(srcList,{childList:true});
  renderPreview();
  preview.addEventListener('click',e=>{
   const btn=e.target.closest('[data-focus-condition]');if(!btn)return;
   const orig=srcList.querySelector('[data-focus-condition="'+btn.dataset.focusCondition+'"]');
   if(orig)orig.click();
  });
 }
 const TKEY='ra-mp-tickets';
 const tWrap=document.getElementById('mp-my-tickets'),tList=document.getElementById('mp-ticket-list');
 function loadTickets(){try{return JSON.parse(localStorage.getItem(TKEY)||'[]');}catch(e){return[];}}
 function saveTickets(a){try{localStorage.setItem(TKEY,JSON.stringify(a.slice(0,5)));}catch(e){}}
 function renderTickets(){
  if(!tWrap||!tList)return;
  const items=loadTickets();
  tWrap.hidden=!items.length;
  tList.innerHTML=items.map(t=>'<div class="mp-ticket"><div><div class="tn">'+esc(t.name||'Institusi')+'</div><div class="tm">'+esc(t.ticket)+' \u00B7 '+esc(t.date||'')+'</div></div><button type="button" data-mp-ticket="'+esc(t.ticket)+'">Cek status</button></div>').join('');
 }
 if(tList){
  renderTickets();
  tList.addEventListener('click',e=>{
   const b=e.target.closest('[data-mp-ticket]');if(!b)return;
   const input=document.getElementById('check-ticket'),box=document.getElementById('check-box'),goBtn=document.getElementById('check-go');
   if(!input||!goBtn)return;
   if(box&&box.hidden)document.getElementById('btn-check')?.click();
   input.value=b.dataset.mpTicket;
   goBtn.click();
   setTimeout(()=>document.getElementById('check-result')?.scrollIntoView({block:'nearest',behavior:'smooth'}),350);
  });
  const sStatus=document.getElementById('suggest-status');
  if(sStatus){new MutationObserver(()=>{
   const m=sStatus.textContent.match(/Nomor tiket:\s*(\S+)/);
   if(!m)return;
   const name=document.querySelector('#suggest-form [name="proposed_name"]')?.value||'Institusi';
   const items=loadTickets().filter(t=>t.ticket!==m[1]);
   items.unshift({ticket:m[1],name:name,date:new Date().toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'})});
   saveTickets(items);renderTickets();
  }).observe(sStatus,{childList:true,characterData:true,subtree:true});}
 }
})();
</script>

@endsection

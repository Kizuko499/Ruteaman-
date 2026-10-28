<style>
/* === Polesan modern minimalis form Lapor (tamu + login) — hanya tampilan === */
#guest-report-app,#hazard-report-app{max-width:760px;padding:34px 22px 56px}
#guest-report-app .back,#hazard-report-app .back{display:inline-flex;align-items:center;gap:6px;font-size:.88rem;font-weight:600;color:var(--teal-dark,#1F6E70);text-decoration:none;padding:8px 14px 8px 11px;background:rgba(255,255,255,.65);border:1px solid #E1EAE5;border-radius:999px}
#guest-report-app .eyebrow,#hazard-report-app .eyebrow{font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--teal,#359FA0);margin:20px 0 0}
#guest-report-app h1,#hazard-report-app h1{font-family:Poppins,Inter,sans-serif;font-size:1.8rem;font-weight:700;letter-spacing:-.01em;color:#14343A;margin:8px 0 14px}
#guest-report-app .disclaimer,#hazard-report-app .disclaimer{background:#FFF9E9;border:1px solid rgba(193,164,100,.28);border-radius:14px;padding:12px 14px;font-size:.85rem;line-height:1.55;color:#5C5540;margin:0 0 8px}
#guest-report-app .error,#hazard-report-app .error{background:#FDECEA;border:1px solid rgba(179,68,32,.25);color:#8C3A1B;border-radius:14px;padding:12px 14px;font-size:.9rem;line-height:1.5}
#guest-report-app section,#hazard-report-app section{background:#fff;border:1px solid #E3EFE9;border-radius:20px;padding:22px;box-shadow:0 1px 2px rgba(18,56,58,.04),0 14px 30px rgba(18,56,58,.05);margin-top:18px}
#guest-report-app section h2,#hazard-report-app section h2{font-family:Poppins,Inter,sans-serif;font-size:1.13rem;font-weight:600;color:#14343A;margin:0 0 8px}
#guest-report-app section h2 small,#hazard-report-app section h2 small{font-size:.76rem;font-weight:600;color:#8AA0A2}
#guest-report-app section>p,#hazard-report-app section>p{font-size:.89rem;color:#5F7476;line-height:1.6;margin:0 0 12px}
#guest-report-app label,#hazard-report-app label{font-size:.84rem;font-weight:600;color:#34565A}
#guest-report-app input:not([type=radio]):not([type=checkbox]):not([type=file]),#hazard-report-app input:not([type=radio]):not([type=checkbox]):not([type=file]),#guest-report-app select,#hazard-report-app select,#guest-report-app textarea,#hazard-report-app textarea{width:100%;box-sizing:border-box;height:52px;border:1px solid #DFE9E4;border-radius:14px;padding:0 14px;font-size:.95rem;font-family:Inter,sans-serif;color:#14343A;background:#fff;margin-top:7px;transition:border-color .15s,box-shadow .15s}
#guest-report-app textarea,#hazard-report-app textarea{height:auto;min-height:104px;padding:12px 14px;resize:vertical;line-height:1.55}
#guest-report-app input:not([type=radio]):not([type=checkbox]):not([type=file]):focus,#hazard-report-app input:not([type=radio]):not([type=checkbox]):not([type=file]):focus,#guest-report-app select:focus,#hazard-report-app select:focus,#guest-report-app textarea:focus,#hazard-report-app textarea:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
#guest-report-app input::placeholder,#hazard-report-app input::placeholder,#guest-report-app textarea::placeholder,#hazard-report-app textarea::placeholder{color:#8AA0A2}
#guest-report-app small,#hazard-report-app small{color:#7D9291}
#guest-report-app .guest-school-search-row{display:flex;gap:10px;align-items:stretch}
#guest-report-app .guest-school-search-row input{flex:1;margin-top:0}
#guest-report-app .guest-school-search-row .btn{height:52px;padding:0 24px;border-radius:14px;flex:none}
#guest-report-app #guest-school-search-help,#guest-report-app #guest-school-search-status{font-size:.82rem;color:#7D9291;line-height:1.55}
#guest-report-app .guest-school-results{margin-top:10px}
#guest-report-app .guest-school-results:empty{display:none}
#guest-report-app .catalog-entry-link{display:inline-block;margin:12px 0 4px;font-weight:600;color:var(--teal-dark,#1F6E70)}
#guest-report-app .report-location-map,#hazard-report-app .report-location-map{height:320px;border:1px solid #E3EFE9;border-radius:18px;overflow:hidden;margin:14px 0}
#guest-report-app .location-help,#hazard-report-app .location-help{background:#F4F8F6;border:1px solid #E3EFE9;border-radius:16px;padding:14px 16px;margin:14px 0}
#guest-report-app .btn-soft,#hazard-report-app .btn-soft{background:#fff;border:1px solid #CFE0D9;color:var(--teal-dark,#1F6E70);border-radius:999px;padding:10px 18px;font-weight:600;box-shadow:none}
#guest-report-app .btn-soft:hover,#hazard-report-app .btn-soft:hover{background:#F2F7F5}
#guest-report-app .btn-dark,#hazard-report-app .btn-dark{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;border-radius:15px;color:#fff;font-family:Poppins,Inter,sans-serif;font-weight:600;box-shadow:0 10px 22px rgba(31,110,112,.2)}
#guest-report-submit,#report-submit{width:100%;padding:15px;font-size:1rem;margin-top:8px}
#guest-report-app details,#hazard-report-app details{margin-top:14px}
#guest-report-app details summary,#hazard-report-app details summary{font-size:.88rem;font-weight:600;color:var(--teal-dark,#1F6E70);cursor:pointer}
#guest-report-app .two-fields,#hazard-report-app .two-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px}
#guest-report-app .type-grid,#hazard-report-app .type-grid{gap:12px}
#guest-report-app .type-grid label,#hazard-report-app .type-grid label{border:1.5px solid #E3EFE9;border-radius:18px;min-height:118px;padding:16px 10px 13px;gap:8px;background:#fff;box-shadow:0 1px 2px rgba(18,56,58,.03);transition:border-color .15s,background .15s,transform .15s,box-shadow .15s}
#guest-report-app .type-grid label:hover,#hazard-report-app .type-grid label:hover{border-color:#BFD9D2;transform:translateY(-1px)}
#guest-report-app .type-grid label:has(input:checked),#hazard-report-app .type-grid label:has(input:checked){border-color:#1F6E70;background:rgba(53,159,160,.08);box-shadow:0 8px 18px rgba(31,110,112,.1)}
#guest-report-app .type-grid .report-type-icon,#hazard-report-app .type-grid .report-type-icon{color:#49696B;width:34px;height:34px}
#guest-report-app .type-grid .report-type-icon svg,#hazard-report-app .type-grid .report-type-icon svg{width:32px;height:32px}
#guest-report-app .type-grid label:has(input:checked) .report-type-icon,#hazard-report-app .type-grid label:has(input:checked) .report-type-icon{color:#1F6E70}
#guest-report-app .type-grid b,#hazard-report-app .type-grid b{font-size:.92rem;font-weight:600;color:#22393C}
#guest-report-app input[type=file],#hazard-report-app input[type=file]{width:100%;box-sizing:border-box;border:1.5px dashed #C9DCD4;border-radius:16px;background:#FAFCFB;padding:12px 14px;margin-top:7px;font-size:.9rem;font-family:Inter,sans-serif;color:#49696B}
#guest-report-app input[type=file]::file-selector-button,#hazard-report-app input[type=file]::file-selector-button{border:0;background:#E7F2EE;color:#1F6E70;border-radius:999px;padding:8px 15px;font-family:Inter,sans-serif;font-weight:600;font-size:.87rem;margin-right:12px;cursor:pointer}
#guest-report-app .check{display:flex;gap:11px;align-items:flex-start;background:#F7FAF8;border:1px solid #E3EFE9;border-radius:14px;padding:14px;font-size:.9rem;font-weight:500;line-height:1.55;color:#34565A;margin-top:14px}
#guest-report-app .check input{width:19px;height:19px;flex:none;margin:2px 0 0;accent-color:#1F6E70}
#guest-report-app .text-link,#hazard-report-app .text-link{color:var(--teal-dark,#1F6E70);font-weight:600}
@media(max-width:640px){
 #guest-report-app,#hazard-report-app{padding:24px 14px 44px}
 #guest-report-app section,#hazard-report-app section{padding:18px 16px;border-radius:18px}
 #guest-report-app h1,#hazard-report-app h1{font-size:1.55rem}
 #guest-report-app .report-location-map,#hazard-report-app .report-location-map{height:260px}
 #guest-report-app .two-fields,#hazard-report-app .two-fields{grid-template-columns:1fr}
 #guest-report-app input:not([type=radio]):not([type=checkbox]):not([type=file]),#hazard-report-app input:not([type=radio]):not([type=checkbox]):not([type=file]),#guest-report-app select,#hazard-report-app select{font-size:16px}
}
</style>

<style>
.cvt-ov {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(17,24,39,.55);
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
    overflow-y: auto;
}
.cvt-ov.show { display: flex; }
.cvt-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    max-height: calc(100vh - 32px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    margin: auto;
}
.cvt-head { padding:18px 22px; background:linear-gradient(135deg,#0f172a,#16a34a); color:#fff; flex-shrink:0; }
.cvt-head h3 { margin:0; font-size:16px; font-weight:800; color:#fff !important; }
.cvt-head p { margin:4px 0 0; font-size:12px; opacity:.9; color:#fff !important; }
.cvt-body { padding:18px 22px; overflow-y:auto; flex:1; background:#fff; }
.cvt-tabs { display:flex; gap:8px; margin-bottom:16px; }
.cvt-tab { flex:1; padding:10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; font-size:12px; font-weight:700; cursor:pointer; text-align:center; color:#374151; }
.cvt-tab.active { background:#ede9fe; border-color:#7c5cfc; color:#5b3fd9; }
.cvt-panel { display:none; }
.cvt-panel.active { display:block; }
.cvt-label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; display:block; }
.cvt-field { margin-bottom:12px; }
.cvt-field input,
.cvt-field select {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    padding: 9px 11px;
    font-size: 13px;
    box-sizing: border-box;
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827;
}
.cvt-field input::placeholder { color: #9ca3af !important; -webkit-text-fill-color: #9ca3af; }
.cvt-field select option { background: #fff; color: #111827; }
.cvt-search-results { border:1px solid #e5e7eb; border-radius:10px; max-height:160px; overflow-y:auto; margin-top:6px; display:none; background:#fff; }
.cvt-search-item { padding:10px 12px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:13px; color:#374151; background:#fff; }
.cvt-search-item:hover { background:#ede9fe; }
.cvt-selected { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:10px 12px; font-size:13px; margin-top:8px; display:none; color:#111827; }
.cvt-hint { font-size:11px; color:#6b7280; margin-top:5px; line-height:1.4; }
.cvt-hint strong { color:#16a34a; }
.cvt-foot { padding:14px 22px; border-top:1px solid #f3f4f6; display:flex; gap:10px; justify-content:flex-end; flex-shrink:0; background:#fff; }
.cvt-btn { border:none; border-radius:9px; padding:10px 18px; font-size:13px; font-weight:700; cursor:pointer; }
.cvt-btn.cancel { background:#f3f4f6; color:#6b7280; }
.cvt-btn.save { background:#16a34a; color:#fff; }
.cvt-btn:disabled { opacity:.6; cursor:not-allowed; }
.cvt-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }

/* Success popup */
.cvt-success-modal { max-width: 420px; text-align: center; }
.cvt-success-body { padding: 32px 28px 20px; background: #fff; }
.cvt-success-icon { margin: 0 auto 16px; width: 72px; height: 72px; }
.cvt-success-icon svg { display: block; }
.cvt-success-circle {
    stroke: #16a34a;
    stroke-width: 2;
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    animation: cvtStroke .5s cubic-bezier(.65,0,.45,1) forwards;
}
.cvt-success-check {
    stroke: #16a34a;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: cvtStroke .35s .4s cubic-bezier(.65,0,.45,1) forwards;
}
@keyframes cvtStroke { to { stroke-dashoffset: 0; } }
.cvt-success-title { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #111827; }
.cvt-success-msg { margin: 0; font-size: 14px; line-height: 1.55; color: #6b7280; }
#cvtSuccessModal .cvt-foot { justify-content: center; }
#cvtSuccessModal .cvt-btn { min-width: 140px; }

/* Beat dark theme overrides on form controls */
body.theme-admin-pro .cvt-ov .cvt-field input,
body.theme-admin-pro .cvt-ov .cvt-field select,
body.theme-admin-pro #cvtModal .cvt-field input,
body.theme-admin-pro #cvtModal .cvt-field select {
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    border-color: #d1d5db !important;
}

@media(max-width:520px){
    .cvt-ov { padding: 0; align-items: stretch; }
    .cvt-modal { max-width: none; max-height: 100vh; height: 100vh; border-radius: 0; margin: 0; }
    .cvt-grid { grid-template-columns: 1fr; }
    .cvt-foot { flex-direction: column-reverse; }
    .cvt-btn { width: 100%; }
}
</style>

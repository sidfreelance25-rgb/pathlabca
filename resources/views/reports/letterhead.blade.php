<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap">
<style>
/* =========================================================
   BASE
   ========================================================= */
@page{size:A4;margin:0}
*{box-sizing:border-box;margin:0;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact}
[hidden]{display:none!important}
body{font-family:Poppins,system-ui,sans-serif;background:#fff;color:#14181f}
svg{display:block}

.stage{padding:0}

/* ---------- A4 sheet ---------- */
.sheet{--fh:calc(var(--bh) + 29mm);position:relative;width:210mm;height:296.5mm;margin:8mm auto;background:#fff;overflow:hidden;box-shadow:0 2px 14px #0003;break-after:page}
.sheet{display:block}

.lh-head,.lh-foot{position:absolute;left:0;right:0;z-index:3}
.lh-head{top:0;height:var(--hh)}
.lh-foot{bottom:0;height:var(--fh)}
.lh-head>*,.lh-foot>*{position:absolute}
.lh-body{position:absolute;left:14mm;right:14mm;top:calc(var(--hh) + 3mm);bottom:calc(var(--fh) + 1mm);z-index:2;font-size:9.5pt;line-height:1.45;overflow:hidden}

.wm{position:absolute;inset:0;z-index:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8mm;pointer-events:none}
.wm svg{width:80mm;height:80mm;opacity:.05}
.wm b{font:800 15mm Montserrat,sans-serif;text-transform:uppercase;color:#000;opacity:.045;white-space:nowrap}

.f1{fill:var(--c1)}.f2{fill:var(--c2)}.f3{fill:var(--c3)}
.s1{fill:none;stroke:var(--c1)}.s2{fill:none;stroke:var(--c2)}
.deco{left:0;width:210mm}
.ico{width:6.6mm;height:6.6mm;border-radius:50%;display:grid;place-items:center;color:#fff;background:var(--c1);flex:none}
.ico svg{width:56%;height:56%;fill:currentColor}
.line{display:flex;align-items:center;gap:2.4mm;white-space:nowrap}
.lg-img{object-fit:contain}

/* ---------- footer: technician | barcode | doctor ---------- */
.lh-foot .sigrow{left:14mm;right:14mm;bottom:calc(var(--bh) + 1mm);height:27mm;display:flex;align-items:flex-end;justify-content:space-between;gap:6mm;color:#14181f}
.tech,.doc{flex:1 1 0;min-width:0}
.doc{text-align:right}
.sigrow b{display:block;font:700 4.4mm/1.25 Poppins,sans-serif}
.sigrow small{display:block;font:500 3.2mm/1.3 Poppins,sans-serif;color:#444}
.bc{flex:none;text-align:center}
.bc svg{margin:0 auto}
.bc small{margin-top:.8mm;font-size:2.6mm;letter-spacing:.3mm;color:#111}
.signbox{height:13mm;margin-bottom:1mm;display:flex;justify-content:flex-end;align-items:flex-end;cursor:pointer}
.signbox img{max-height:13mm;max-width:100%;object-fit:contain}
.fnote{white-space:nowrap;font-size:3.2mm}

/* ---------- patient box (inside report) ---------- */
.rep h2{font:700 13pt Poppins;text-align:center;margin-bottom:3mm}
.rep .pt{display:grid;grid-template-columns:1fr 1fr 21mm;gap:1mm 8mm;border:.3mm solid #b8bec8;padding:3mm 4mm;margin-bottom:4mm;font-size:9pt;align-items:center}
.qrbox{grid-column:3;grid-row:1/4;width:20mm;height:20mm;justify-self:end}
.qrbox svg{width:100%;height:100%}
.qrbox path{fill:#000}
.qrfb{display:grid;place-items:center;width:100%;height:100%;border:.3mm dashed #999;font-size:3mm;color:#888}
.rep table{width:100%;border-collapse:collapse;font-size:9pt}
.rep th,.rep td{padding:1.6mm 2mm;border-bottom:.2mm solid #cfd4dc;text-align:left}
.rep th{border-bottom:.4mm solid #14181f}
.rep .hi{font-weight:700;color:#b3261e}
.rep p{margin-top:4mm;font-size:8.5pt;color:#444}

/* ---------- show / hide switches ---------- */
.hide-qr .qrbox{display:none}
.hide-barcode .bc{display:none}
.hide-sign .signbox{display:none}
.hide-tech .tech{visibility:hidden}
.hide-tagline .tagline-el{display:none}
.hide-iso .iso-el{display:none}
.hide-contact .contact-el{display:none}
.hide-note .fnote{display:none}
.hide-wm .wm{display:none}

/* ---------- edit affordances (never printed) ---------- */
@media screen{
  [contenteditable]:hover{outline:1px dashed #3b82f6aa;outline-offset:1px}
  [contenteditable]:focus{outline:2px solid #3b82f6;outline-offset:1px}
  .signbox.empty{border:.3mm dashed #aab;color:#8a93a3;font-size:2.6mm;justify-content:center;align-items:center;width:100%}
  .signbox.empty::after{content:"Click to add signature"}
}
@media print{
  body{background:#fff}
  #panel{display:none}
  .stage{margin:0;padding:0}
  .sheet{margin:0;box-shadow:none}
}

/* =========================================================
   T1  GREEN
   ========================================================= */
.t1{--c1:#0a7a3e;--c2:#05935f;--c3:#033a31;--hh:52mm;--bh:16mm}
.t1 .logo{left:15mm;top:23mm;width:15mm;height:20mm;color:var(--c3)}
.t1 .brand{left:36mm;top:21mm}
.t1 h1{font:800 9.6mm/1 Montserrat;color:var(--c1);text-transform:uppercase;white-space:nowrap}
.t1 .brand p{margin-top:1.6mm;font:700 4.3mm Montserrat;color:var(--c3);text-transform:uppercase;letter-spacing:.2mm}
.t1 .info{right:8mm;top:30mm;display:grid;gap:2.2mm;font-size:3.9mm}
.t1 .addr{left:19mm;top:44mm;font-size:3.9mm}
.t1 .fnote{left:14mm;right:14mm;bottom:2mm;text-align:center;color:#fff}

/* =========================================================
   T2  BLUE
   ========================================================= */
.t2{--c1:#06306b;--c2:#38b6ff;--c3:#063a68;--hh:44mm;--bh:14mm}
.t2 .logo{left:15mm;top:19mm;width:15mm;height:20mm;color:var(--c2)}
.t2 .brand{left:36mm;top:17mm}
.t2 h1{font:800 9.6mm/1 Montserrat;color:var(--c1);text-transform:uppercase;white-space:nowrap}
.t2 .brand p{margin-top:1.4mm;font:700 4.3mm Montserrat;color:#033a31;text-transform:uppercase}
.t2 .info{right:8mm;top:21mm;display:grid;gap:2.2mm;font-size:3.9mm}
.t2 .addr{left:19mm;top:37mm;font-size:3.9mm}
.t2 .fnote{left:14mm;width:105mm;bottom:1.8mm;color:var(--c1);font-weight:600}

/* =========================================================
   T3  NAVY + ORANGE
   ========================================================= */
.t3{--c1:#ff7a00;--c2:#e2b53a;--c3:#002d6a;--hh:46mm;--bh:10mm}
.t3 .banner{left:25mm;right:0;top:12mm;height:22mm;background:var(--c3);border-radius:11mm 0 0 11mm}
.t3 .badge{left:8mm;top:11mm;width:26mm;height:26mm;border-radius:50%;background:#fff;display:grid;place-items:center}
.t3 .logo{width:16mm;height:16mm;color:var(--c1)}
.t3 .brand{left:40mm;top:15mm}
.t3 h1{font:800 9.4mm/1 Montserrat;color:#fff;text-transform:uppercase;white-space:nowrap}
.t3 h1 span+span{color:var(--c1)}
.t3 .brand p{margin-top:1.6mm;font:500 4.2mm Montserrat;color:#fff;letter-spacing:.3mm}
.t3 .strip{left:25mm;right:0;top:36.5mm;height:6.2mm;background:var(--c3);border-radius:3.1mm 0 0 3.1mm;display:flex;align-items:center;gap:8mm;padding-left:15mm;color:#fff;font-size:3.6mm;white-space:nowrap}
.t3 .gold{position:absolute;left:3mm;top:-1mm;width:8mm;height:8mm;border-radius:50%;background:var(--c2);border:.6mm solid #fff}
.t3 .strip .ico{background:transparent;border:.3mm solid var(--c1);width:5mm;height:5mm;color:var(--c1)}
.t3 .fbar{left:0;right:0;bottom:0;height:9mm;background:var(--c3);border-top:1.2mm solid var(--c1);box-sizing:content-box}
.t3 .fnote{left:14mm;right:14mm;bottom:2.2mm;text-align:center;color:#fff}
.t3 .wm svg{color:var(--c1);width:100mm;height:100mm;opacity:.07}

/* =========================================================
   T4  PINK + BLUE
   ========================================================= */
.t4{--c1:#ff2878;--c2:#0a9bff;--c3:#14181f;--hh:56mm;--bh:12mm}
.t4 .logo{left:36mm;top:10mm;width:15mm;height:15mm}
.t4 h1{left:14mm;top:27mm;font:700 9.2mm/1 Poppins;color:var(--c3);text-transform:uppercase;white-space:nowrap}
.t4 .tag{left:22mm;top:40mm;font:600 3.7mm Poppins;letter-spacing:.2mm}
.t4 .iso{right:12mm;top:4mm;display:flex;align-items:center;gap:2mm;font-size:3.6mm}
.t4 .iso svg{width:12mm;height:6mm;color:#21a67a}
.t4 .info{right:12mm;top:26mm;display:grid;gap:2.2mm;font-size:3.7mm;justify-items:end}
.t4 .ico{width:5.2mm;height:5.2mm}
.t4 .info .a{white-space:normal;text-align:right;max-width:76mm;line-height:1.3}
.t4 .fnote{left:14mm;right:14mm;bottom:1.6mm;text-align:center;color:#333}
.t4 .wm svg{color:var(--c1)}

/* =========================================================
   T5  BLUE BAR + STRIPES
   ========================================================= */
.t5{--c1:#0b4fb3;--c2:#80a4d6;--c3:#004aad;--hh:37mm;--bh:10mm}
.t5 .logo{left:22mm;top:10mm;width:9mm;height:13mm;color:var(--c1)}
.t5 h1{left:34.5mm;top:9mm;font:800 7.4mm/1 Montserrat;text-transform:uppercase;white-space:nowrap}
.t5 h1 span+span{color:var(--c1)}
.t5 .tag{left:34.5mm;top:18mm;font:500 4.2mm Montserrat;white-space:pre}
.t5 .addr{left:34.5mm;top:24mm;font-size:3.2mm}
.t5 .ico{width:4.4mm;height:4.4mm}
.t5 .info{right:8mm;top:10mm;display:grid;gap:1.6mm;font-size:3.8mm}
.t5 .bar{top:30mm}
.t5 .fnote{left:110mm;right:10mm;bottom:3mm;text-align:right;color:#fff}
.t5 .wm svg{color:#000;width:70mm;height:90mm;opacity:.08}

/* =========================================================
   T6  HEXAGONS
   ========================================================= */
.t6{--c1:#0b4fb3;--c2:#00c2cb;--c3:#0b2a6b;--hh:62mm;--bh:14mm}
.t6 .logo{left:17mm;top:15mm;width:20mm;height:27mm;color:#4a5a66}
.t6 .brand{left:44mm;top:14mm}
.t6 h1{font:800 11mm/1 Poppins;color:#000;white-space:nowrap}
.t6 .n2{font:400 8.2mm/1.1 Poppins;text-transform:uppercase;white-space:nowrap}
.t6 .brand p{font:400 4.6mm Poppins;margin-top:1mm;white-space:pre}
.t6 .cert{left:121mm;top:14mm;display:flex;align-items:center;gap:1mm;color:#0aa3f2}
.t6 .cert svg{width:14mm;height:12mm}
.t6 .cert b{background:#0aa3f2;color:#14181f;font:700 4mm Poppins;padding:2mm 3mm;letter-spacing:.4mm}
.t6 .iso{left:120mm;top:31mm;width:36mm;text-align:center;font:400 3.3mm/1.3 Poppins;color:var(--c3)}
.t6 .addr{left:44mm;top:40mm;font-size:3.5mm}
.t6 .info{left:44mm;top:46mm;display:flex;gap:6mm;font-size:3.5mm}
.t6 .ico{width:4.4mm;height:4.4mm}
.t6 .fnote{left:72mm;right:10mm;bottom:2.4mm;text-align:center;color:#fff}
.t6 .wm svg{display:none}
.t6 .wm b{opacity:.04}

.sheet{margin:0 auto;box-shadow:none}
</style>
</head>
@php
    $hideClass = '';
    foreach (($design['hide'] ?? []) as $key => $hidden) {
        if ($hidden) { $hideClass .= ' hide-'.$key; }
    }
    $colorStyle = '';
    $chosen = $design['colors'][$theme] ?? [];
    foreach (['c1','c2','c3'] as $slot) {
        $value = $chosen[$slot] ?? '';
        if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            $colorStyle .= '--'.$slot.':'.$value.';';
        }
    }
@endphp
<body class="{{ $hideClass }}" data-view="{{ $theme }}">
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-scope" viewBox="0 0 64 64"><rect x="25" y="4" width="13" height="27" rx="2" transform="rotate(-38 31 18)" fill="currentColor"/><path d="M17 27A19 19 0 0 0 41 55" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><rect x="33" y="40" width="23" height="4" rx="2" fill="currentColor"/><path d="M14 60 25 47H43L51 60Z" fill="currentColor"/></symbol>
  <symbol id="i-flask" viewBox="0 0 64 64"><path fill="currentColor" d="M25 5h14v5h-3v14l15 25a7 7 0 0 1-6 11H19a7 7 0 0 1-6-11l15-25V10h-3z"/></symbol>
  <symbol id="i-mol" viewBox="0 0 64 64"><g stroke="#cfd8e6" stroke-width="3"><path d="M32 32 32 6M32 32 8 22M32 32 56 22M32 32 14 52M32 32 50 52"/></g><circle cx="32" cy="32" r="10" fill="#ff2878"/><circle cx="32" cy="32" r="3" fill="#0a9bff"/><circle cx="32" cy="6" r="5" fill="#ff2878"/><circle cx="8" cy="22" r="5" fill="#0a9bff"/><circle cx="56" cy="22" r="5" fill="#0a9bff"/><circle cx="14" cy="52" r="5" fill="#ff2878"/><circle cx="50" cy="52" r="5" fill="#0a9bff"/></symbol>
  <symbol id="i-phone" viewBox="0 0 24 24"><path d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11 11 0 0 0 3.6.6 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.6 3.6a1 1 0 0 1-.25 1z"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="1.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 4.4a2.6 2.6 0 1 1 0 5.2 2.6 2.6 0 0 1 0-5.2z"/></symbol>
  <symbol id="i-shield" viewBox="0 0 48 24"><path d="M2 3c6 0 9-1 11-3 2 2 5 3 11 3v9c0 6-5 10-11 12C7 22 2 18 2 12z" fill="currentColor"/><path d="m7 11 5 5 11-12" fill="none" stroke="#fff" stroke-width="3"/></symbol>
</svg>
@if($theme === 't1')
<section style="{{ $colorStyle }}" class="sheet t1" data-t="t1">
  <div class="wm"><svg><use href="#i-scope"/></svg><b>{{ $t['name'] }}</b></div>
  <header class="lh-head">
    <svg class="deco" style="top:0" width="210mm" height="21mm" viewBox="0 0 210 21" preserveAspectRatio="none"><path class="f2" d="M0 0H210V20.5H170L6 9.2 0 10Z"/><path class="f3" d="M40 0H210V19.5L172 20.5Z"/></svg>
    @if($logo !== '')<img class="logo lg-img" src="{{ $logo }}" alt="">@else<svg class="logo lg"><use href="#i-scope"/></svg>@endif
    <div class="brand"><h1>{{ $t['name'] }}</h1><p class="tagline-el">{{ $t['tagline'] }}</p></div>
    <div class="info contact-el">
      <div class="line"><span class="ico"><svg><use href="#i-phone"/></svg></span><span>{{ $t['phone'] }}</span></div>
      <div class="line"><span class="ico"><svg><use href="#i-mail"/></svg></span><span>{{ $t['email'] }}</span></div>
    </div>
    <div class="line addr" data-fit="165"><span class="ico"><svg><use href="#i-pin"/></svg></span><span>{{ $t['address'] }}</span></div>
  </header>
  <main class="lh-body">@include('reports.partials.letterhead-body')</main>
  <footer class="lh-foot">
    <svg class="deco" style="bottom:0" width="210mm" height="16mm" viewBox="0 0 210 16" preserveAspectRatio="none"><path class="f3" d="M0 1.5 21 0 210 9V16H0Z"/><path class="f2" d="M21 0 210 8.2V11.5L70 4.5Z"/></svg>
    <div class="fnote">{{ $t['footerNote'] }}</div>
    <div class="sigrow">
      <div class="tech"><b>{{ $t['techName'] }}</b><small>{{ $t['techRole'] }}</small></div>
      <div class="bc"><img src="{{ $barcode }}" alt="barcode" style="height:9.5mm"><small>{{ $sampleId }}</small></div>
      <div class="doc">@if($sign)<div class="signbox"><img src="{{ $sign }}" alt="signature"></div>@endif<b>{{ $t['doctor'] }}</b><small>{{ $t['qual'] }}</small></div>
    </div>
  </footer>
</section>

<!-- =========================================================
     T2 · BLUE
     ========================================================= -->
@endif
@if($theme === 't2')
<section style="{{ $colorStyle }}" class="sheet t2" data-t="t2">
  <div class="wm"><svg><use href="#i-scope"/></svg><b>{{ $t['name'] }}</b></div>
  <header class="lh-head">
    <svg class="deco" style="top:0" width="210mm" height="19mm" viewBox="0 0 210 19" preserveAspectRatio="none"><path class="f3" d="M0 0H62L97 4.5 0 14.4Z"/><path class="f2" d="M62 0H210V19L97 4.5Z"/></svg>
    @if($logo !== '')<img class="logo lg-img" src="{{ $logo }}" alt="">@else<svg class="logo lg"><use href="#i-scope"/></svg>@endif
    <div class="brand"><h1>{{ $t['name'] }}</h1><p class="tagline-el">{{ $t['tagline'] }}</p></div>
    <div class="info contact-el">
      <div class="line"><span class="ico"><svg><use href="#i-phone"/></svg></span><span>{{ $t['phone'] }}</span></div>
      <div class="line"><span class="ico"><svg><use href="#i-mail"/></svg></span><span>{{ $t['email'] }}</span></div>
    </div>
    <div class="line addr" data-fit="165"><span class="ico"><svg><use href="#i-pin"/></svg></span><span>{{ $t['address'] }}</span></div>
  </header>
  <main class="lh-body">@include('reports.partials.letterhead-body')</main>
  <footer class="lh-foot">
    <svg class="deco" style="bottom:0" width="210mm" height="14mm" viewBox="0 0 210 14" preserveAspectRatio="none"><path class="f2" d="M0 0 125 9 165 14H0Z"/><path class="f3" d="M125 9 210 3V14H165Z"/></svg>
    <div class="fnote">{{ $t['footerNote'] }}</div>
    <div class="sigrow">
      <div class="tech"><b>{{ $t['techName'] }}</b><small>{{ $t['techRole'] }}</small></div>
      <div class="bc"><img src="{{ $barcode }}" alt="barcode" style="height:9.5mm"><small>{{ $sampleId }}</small></div>
      <div class="doc">@if($sign)<div class="signbox"><img src="{{ $sign }}" alt="signature"></div>@endif<b>{{ $t['doctor'] }}</b><small>{{ $t['qual'] }}</small></div>
    </div>
  </footer>
</section>

<!-- =========================================================
     T3 · NAVY + ORANGE
     ========================================================= -->
@endif
@if($theme === 't3')
<section style="{{ $colorStyle }}" class="sheet t3" data-t="t3">
  <div class="wm"><svg><use href="#i-flask"/></svg><b>{{ $t['name'] }}</b></div>
  <header class="lh-head">
    <div class="banner"></div>
    <div class="badge">@if($logo !== '')<img class="logo lg-img" src="{{ $logo }}" alt="">@else<svg class="logo lg"><use href="#i-flask"/></svg>@endif</div>
    <div class="brand"><h1 data-fit="160"><span>{{ $t['n1'] }}</span> <span>{{ $t['n2'] }}</span></h1><p class="tagline-el">{{ $t['tagline'] }}</p></div>
    <div class="strip" data-fit="auto">
      <i class="gold iso-el"></i>
      <span class="iso-el">{{ $t['iso'] }}</span>
      <span class="line contact-el"><span class="ico"><svg><use href="#i-mail"/></svg></span><span>{{ $t['email'] }}</span></span>
      <span class="line contact-el"><span class="ico"><svg><use href="#i-phone"/></svg></span><span>{{ $t['phone'] }}</span></span>
    </div>
  </header>
  <main class="lh-body">@include('reports.partials.letterhead-body')</main>
  <footer class="lh-foot">
    <div class="fbar"></div>
    <div class="fnote">{{ $t['footerNote'] }}</div>
    <div class="sigrow">
      <div class="tech"><b>{{ $t['techName'] }}</b><small>{{ $t['techRole'] }}</small></div>
      <div class="bc"><img src="{{ $barcode }}" alt="barcode" style="height:9.5mm"><small>{{ $sampleId }}</small></div>
      <div class="doc">@if($sign)<div class="signbox"><img src="{{ $sign }}" alt="signature"></div>@endif<b>{{ $t['doctor'] }}</b><small>{{ $t['qual'] }}</small></div>
    </div>
  </footer>
</section>

<!-- =========================================================
     T4 · PINK + BLUE
     ========================================================= -->
@endif
@if($theme === 't4')
<section style="{{ $colorStyle }}" class="sheet t4" data-t="t4">
  <div class="wm"><svg><use href="#i-mol"/></svg><b>{{ $t['name'] }}</b></div>
  <header class="lh-head">
    <svg class="deco" style="top:0" width="210mm" height="56mm" viewBox="0 0 210 56" preserveAspectRatio="none">
      <path class="f1" d="M129.5 13.7H210V23.5H124Z"/>
      <path class="s1" stroke-width="1" d="M0 50.9H101L124.5 24.5H210"/>
      <path class="s2" stroke-width="1" d="M0 53.5H102.2L126 26.2H210"/>
    </svg>
    @if($logo !== '')<img class="logo lg-img" src="{{ $logo }}" alt="">@else<svg class="logo lg"><use href="#i-mol"/></svg>@endif
    <h1>{{ $t['name'] }}</h1>
    <div class="tag tagline-el">{{ $t['tagline'] }}</div>
    <div class="iso iso-el"><svg><use href="#i-shield"/></svg><span>{{ $t['iso'] }}</span></div>
    <div class="info contact-el">
      <div class="line"><span>{{ $t['phone'] }}</span><span class="ico"><svg><use href="#i-phone"/></svg></span></div>
      <div class="line"><span>{{ $t['email'] }}</span><span class="ico"><svg><use href="#i-mail"/></svg></span></div>
      <div class="line a"><span>{{ $t['address'] }}</span><span class="ico"><svg><use href="#i-pin"/></svg></span></div>
    </div>
  </header>
  <main class="lh-body">@include('reports.partials.letterhead-body')</main>
  <footer class="lh-foot">
    <svg class="deco" style="bottom:0" width="210mm" height="12mm" viewBox="0 0 210 12" preserveAspectRatio="none"><path class="s1" stroke-width="1" d="M0 2.2H210"/><path class="s2" stroke-width="1" d="M0 4.6H210"/></svg>
    <div class="fnote">{{ $t['footerNote'] }}</div>
    <div class="sigrow">
      <div class="tech"><b>{{ $t['techName'] }}</b><small>{{ $t['techRole'] }}</small></div>
      <div class="bc"><img src="{{ $barcode }}" alt="barcode" style="height:9.5mm"><small>{{ $sampleId }}</small></div>
      <div class="doc">@if($sign)<div class="signbox"><img src="{{ $sign }}" alt="signature"></div>@endif<b>{{ $t['doctor'] }}</b><small>{{ $t['qual'] }}</small></div>
    </div>
  </footer>
</section>

<!-- =========================================================
     T5 · BLUE BAR + STRIPES
     ========================================================= -->
@endif
@if($theme === 't5')
<section style="{{ $colorStyle }}" class="sheet t5" data-t="t5">
  <div class="wm"><svg><use href="#i-scope"/></svg><b>{{ $t['name'] }}</b></div>
  <header class="lh-head">
    @if($logo !== '')<img class="logo lg-img" src="{{ $logo }}" alt="">@else<svg class="logo lg"><use href="#i-scope"/></svg>@endif
    <h1 data-fit="104"><span>{{ $t['n1'] }}</span> <span>{{ $t['n2'] }}</span></h1>
    <div class="tag tagline-el">{{ $t['taglineWide'] }}</div>
    <div class="line addr" data-fit="110"><span class="ico"><svg><use href="#i-pin"/></svg></span><span>{{ $t['address'] }}</span></div>
    <div class="info contact-el">
      <div class="line"><span class="ico"><svg><use href="#i-phone"/></svg></span><span>{{ $t['phone'] }}</span></div>
      <div class="line"><span class="ico"><svg><use href="#i-mail"/></svg></span><span>{{ $t['email'] }}</span></div>
    </div>
    <svg class="deco bar" width="210mm" height="7mm" viewBox="0 0 210 7" preserveAspectRatio="none"><path class="f2" d="M0 0H88L84 7H0Z"/><path fill="#000" d="M90 0h2.6l3.4 7h-2.6zM96 0h2.6l3.4 7h-2.6zM102 0h2.6l3.4 7h-2.6zM108 0h2.6l3.4 7h-2.6z"/><path class="f3" d="M112 0H210V7H116Z"/></svg>
  </header>
  <main class="lh-body">@include('reports.partials.letterhead-body')</main>
  <footer class="lh-foot">
    <svg class="deco" style="bottom:0" width="210mm" height="10mm" viewBox="0 0 210 10" preserveAspectRatio="none"><path class="f2" d="M0 0H48L56 10H0Z"/><path fill="#000" d="M62 0h5l8 10h-5zM72 0h5l8 10h-5zM82 0h5l8 10h-5z"/><path class="f3" d="M96 0H210V10H104Z"/></svg>
    <div class="fnote">{{ $t['footerNote'] }}</div>
    <div class="sigrow">
      <div class="tech"><b>{{ $t['techName'] }}</b><small>{{ $t['techRole'] }}</small></div>
      <div class="bc"><img src="{{ $barcode }}" alt="barcode" style="height:9.5mm"><small>{{ $sampleId }}</small></div>
      <div class="doc">@if($sign)<div class="signbox"><img src="{{ $sign }}" alt="signature"></div>@endif<b>{{ $t['doctor'] }}</b><small>{{ $t['qual'] }}</small></div>
    </div>
  </footer>
</section>

<!-- =========================================================
     T6 · HEXAGONS
     ========================================================= -->
@endif
@if($theme === 't6')
<section style="{{ $colorStyle }}" class="sheet t6" data-t="t6">
  <div class="wm"><svg><use href="#i-scope"/></svg><b>{{ $t['name'] }}</b></div>
  <header class="lh-head">
    <svg class="deco" style="top:0" width="210mm" height="62mm" viewBox="0 0 210 62">
      <path class="f3" d="M0 0H143L137 11.6H0Z"/>
      <path class="f2" d="M161.1 0 181.9 0 192.3 16 181.9 32 161.1 32 150.7 16Z"/>
      <path fill="#38b6ff" d="M168.8 37.9H182.5L189.4 49.9 182.5 61.9H168.8L161.9 49.9Z"/>
      <path class="f1" d="M189.3 35 197 23H210V47H197Z"/>
      <path fill="none" stroke="#777" stroke-width=".8" d="M175.5 20.3H192.3L200.8 35 192.3 49.7H175.5L167 35Z"/>
      <path class="s1" stroke-width=".5" d="M0 53.5H158"/>
    </svg>
    @if($logo !== '')<img class="logo lg-img" src="{{ $logo }}" alt="">@else<svg class="logo lg"><use href="#i-scope"/></svg>@endif
    <div class="brand"><h1>{{ $t['n1'] }}</h1><div class="n2">{{ $t['n2'] }}</div><p class="tagline-el">{{ $t['taglineWide'] }}</p></div>
    <div class="cert iso-el"><svg><use href="#i-shield"/></svg><b>CERTIFIED</b></div>
    <div class="iso iso-el">{{ $t['iso'] }}</div>
    <div class="line addr" data-fit="106"><span class="ico"><svg><use href="#i-pin"/></svg></span><span>{{ $t['address'] }}</span></div>
    <div class="info contact-el" data-fit="106">
      <div class="line"><span class="ico"><svg><use href="#i-phone"/></svg></span><span>{{ $t['phone'] }}</span></div>
      <div class="line"><span class="ico"><svg><use href="#i-mail"/></svg></span><span>{{ $t['email'] }}</span></div>
    </div>
  </header>
  <main class="lh-body">@include('reports.partials.letterhead-body')</main>
  <footer class="lh-foot">
    <svg class="deco" style="bottom:0" width="210mm" height="14mm" viewBox="0 0 210 14" preserveAspectRatio="none">
      <path class="f2" d="M18 4H38L45 14H11Z"/>
      <path fill="none" stroke="#777" stroke-width=".7" d="M36 1.5H48L54 8.5 48 14.5H36L30 8.5Z"/>
      <path class="f3" d="M59 4H210V14H66Z"/>
    </svg>
    <div class="fnote">{{ $t['footerNote'] }}</div>
    <div class="sigrow">
      <div class="tech"><b>{{ $t['techName'] }}</b><small>{{ $t['techRole'] }}</small></div>
      <div class="bc"><img src="{{ $barcode }}" alt="barcode" style="height:9.5mm"><small>{{ $sampleId }}</small></div>
      <div class="doc">@if($sign)<div class="signbox"><img src="{{ $sign }}" alt="signature"></div>@endif<b>{{ $t['doctor'] }}</b><small>{{ $t['qual'] }}</small></div>
    </div>
  </footer>
</section>
@endif

</body>
</html>

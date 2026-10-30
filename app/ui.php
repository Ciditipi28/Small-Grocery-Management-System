<?php
declare(strict_types=1);

function asset_url(string $file): string {
    static $versions=[];
    $path=dirname(__DIR__).'/public/assets/'.$file;
    $versions[$file]??=is_file($path)?substr(hash_file('sha256',$path),0,10):'1';
    return 'assets/'.$file.'?v='.$versions[$file];
}

function ui_head(string $title): void {
    echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light dark"><title>'.e($title).' · Grocery</title>';
    // Blocking external script restores the theme before the stylesheet paints; CSP stays intact.
    echo '<script src="'.e(asset_url('theme.js')).'"></script><link rel="stylesheet" href="'.e(asset_url('app.css')).'"><script src="'.e(asset_url('app.js')).'" defer></script>';
}

function ui_icon(string $name): string {
    $paths=[
        'store'=>'<path d="M3 10h18l-2-6H5l-2 6Zm1 0v10h16V10M9 20v-6h6v6M3 10c0 3 4 3 4 0 0 3 5 3 5 0 0 3 5 3 5 0 0 3 4 3 4 0"/>',
        'overview'=>'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'cart'=>'<path d="M3 4h2l2 12h12l2-8H6M9 20h.01M18 20h.01"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
        'receipt'=>'<path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3ZM9 7h6M9 11h6M9 15h3"/>',
        'box'=>'<path d="m12 3 9 5-9 5-9-5 9-5ZM3 8v10l9 4 9-4V8M12 13v9M7 5l10 6"/>',
        'stock'=>'<path d="M4 4h16v16H4zM4 10h16M10 10v10M14 4v6"/>',
        'movement'=>'<path d="M3 7h16m-4-4 4 4-4 4M21 17H5m4-4-4 4 4 4"/>',
        'truck'=>'<path d="M3 5h11v12H3V5Zm11 5h4l3 4v3h-7M6 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',
        'return'=>'<path d="m8 4-5 5 5 5M3 9h11a6 6 0 0 1 0 12h-3"/>',
        'adjust'=>'<path d="M4 7h16M4 17h16M8 4v6M16 14v6"/>',
        'chart'=>'<path d="M4 3v17h17M8 16v-5M13 16V7M18 16V4"/>',
        'users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5"/>',
        'activity'=>'<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'tag'=>'<path d="M3 3h8l10 10-8 8L3 11V3Z"/><circle cx="7" cy="7" r="1"/>',
        'ruler'=>'<path d="m3 16 13-13 5 5L8 21l-5-5Zm5-5 2 2m2-6 2 2m2-6 2 2"/>',
        'supplier'=>'<path d="M4 21V7l8-4 8 4v14M3 21h18M8 9h1m6 0h1M8 13h1m6 0h1M10 21v-4h4v4"/>',
        'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
        'moon'=>'<path d="M21 13a9 9 0 0 1-10-10 9 9 0 1 0 10 10Z"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close'=>'<path d="m6 6 12 12M6 18 18 6"/>',
        'logout'=>'<path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h13"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'arrow'=>'<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'eye'=>'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'chevron'=>'<path d="m9 5 7 7-7 7"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.($paths[$name]??$paths['box']).'</svg>';
}

function theme_toggle(): void {
    echo '<button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false"><span class="theme-icon-dark">'.ui_icon('moon').'</span><span class="theme-icon-light">'.ui_icon('sun').'</span><span data-theme-label>Dark mode</span></button>';
}

function auth_header(): void {
    echo '<header class="auth-topbar"><a class="sidebar-brand" href="'.e(url('login')).'"><span class="brand-mark">'.ui_icon('store').'</span><span class="brand-copy">Grocery<span>Inventory &amp; sales</span></span></a>';
    theme_toggle();
    echo '</header>';
}

function password_field(string $name,string $label,string $id,string $autocomplete='new-password',string $extra=''): void {
    echo '<label for="'.e($id).'">'.e($label).'</label><div class="password-field"><input id="'.e($id).'" name="'.e($name).'" type="password" autocomplete="'.e($autocomplete).'" required '.$extra.'><button class="password-toggle" type="button" data-password-toggle aria-controls="'.e($id).'" aria-label="Show password" aria-pressed="false">'.ui_icon('eye').'</button></div>';
}

function shelf_illustration(): void {
    echo '<svg class="shelf-illustration" viewBox="0 0 420 270" fill="none" aria-hidden="true"><g stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M35 116h350M35 222h350M51 116v126m318-126v126M62 222v20m296-20v20"/><rect x="68" y="57" width="46" height="59" rx="3"/><path d="M77 57v-9h28v9M80 79h22M80 86h14"/><path d="M143 116V61l8-14h35l8 14v55M151 47v-7h35v7M143 67h51M153 86h30m-22 8h14"/><rect x="217" y="72" width="58" height="44" rx="3"/><path d="M217 82h58m-40 13h21"/><path d="M306 116V70l8-12V42h20v16l8 12v46M314 42v-8h20v8M306 81h36m-36 23h36"/><rect x="77" y="155" width="65" height="67" rx="3"/><path d="M77 168h65m-52 15h39M91 194h22"/><path d="M167 222v-51l7-19h27l7 19v51m-34-70v-7h27v7m-34 33h41m-41 23h41"/><rect x="236" y="174" width="40" height="48" rx="3"/><path d="M236 183h40m-30 13h20m-20 8h12"/><path d="M303 222v-55l7-7h27l7 7v55m-41-55h41m-31 20h21m-21 8h14"/><path d="M37 246h348" stroke-dasharray="2 6"/></g></svg>';
}

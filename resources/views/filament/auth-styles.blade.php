{{-- Login em 2 colunas com a identidade preto/laranja do Carlos Menegatti FC.
     Escopo restrito às telas de autenticação (.fi-simple-layout). --}}
@php
    // Usa a logo REAL da pelada se ela existir em public/logo-pelada.png;
    // caso contrário, usa o brasão recriado em SVG (logo-crest.svg).
    $crest = file_exists(public_path('logo-pelada.png'))
        ? asset('logo-pelada.png')
        : asset('logo-crest.svg');
@endphp
<style>
    .fi-simple-layout {
        min-height: 100vh;
        background: #f4f4f5;
    }
    .dark .fi-simple-layout { background: #09090b; }

    @media (min-width: 1024px) {
        .fi-simple-layout { padding-left: 50%; }
    }

    /* ---------- Painel-herói (esquerda): preto com brasa laranja ---------- */
    .pcm-hero { display: none; }

    @media (min-width: 1024px) {
        .pcm-hero {
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 50%;
            padding: 3.5rem 4.5rem;
            color: #fff;
            background:
                radial-gradient(680px 460px at 26% 30%, rgba(249,115,22,.32), transparent 62%),
                radial-gradient(760px 520px at 108% 112%, rgba(194,65,12,.6), transparent 55%),
                radial-gradient(520px 420px at -5% -10%, rgba(251,146,60,.22), transparent 60%),
                #0a0a0a;
            overflow: hidden;
        }
        /* anel de "fogo" sutil, ecoando o brasão */
        .pcm-hero::after {
            content: "";
            position: absolute;
            right: -160px; bottom: -160px;
            width: 420px; height: 420px;
            border-radius: 9999px;
            border: 2px solid rgba(249,115,22,.18);
            box-shadow: 0 0 120px 20px rgba(249,115,22,.15) inset;
        }
        .pcm-hero > * { position: relative; z-index: 1; }
    }

    .pcm-hero-logo {
        width: auto; max-width: 62%; max-height: 190px;
        margin-bottom: 2rem;
        filter: drop-shadow(0 8px 30px rgba(249,115,22,.35));
    }
    .pcm-hero h1 {
        font-size: 2.05rem; font-weight: 800; line-height: 1.14;
        letter-spacing: -.02em; margin-bottom: 1rem;
    }
    .pcm-hero h1 span { color: #fb923c; }
    .pcm-hero-sub {
        color: rgba(255,255,255,.68); font-size: 1.02rem;
        line-height: 1.55; max-width: 36ch; margin-bottom: 2.25rem;
    }
    .pcm-hero ul { display: flex; flex-direction: column; gap: .85rem; }
    .pcm-hero li {
        display: flex; align-items: center; gap: .65rem;
        color: rgba(255,255,255,.9); font-weight: 500;
    }
    .pcm-hero li svg { width: 1.35rem; height: 1.35rem; color: #fb923c; flex: none; }

    /* ---------- Card do formulário (direita) ---------- */
    .fi-simple-main {
        position: relative;
        background: #ffffff !important;
        border: 1px solid rgba(24, 24, 27, .08);
        border-radius: 1.25rem !important;
        box-shadow: 0 24px 50px -24px rgba(0, 0, 0, .35);
        padding: 2.75rem 2.5rem 2.5rem !important;
        overflow: hidden;
    }
    .fi-simple-main::before {
        content: "";
        position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #fb923c, #f97316, #c2410c);
    }
    .dark .fi-simple-main {
        background: #18181b !important;
        border-color: rgba(255, 255, 255, .08);
    }

    /* Botão submit (Filament 4 usa fi-bg-color-* / fi-text-color-*). */
    .fi-simple-main .fi-btn {
        background-color: transparent !important;
        background-image: linear-gradient(180deg, #f97316, #ea580c) !important;
        border-color: transparent !important;
        box-shadow: 0 10px 22px -10px rgba(234, 88, 12, .85);
    }
    .fi-simple-main .fi-btn:hover {
        background-image: linear-gradient(180deg, #ea580c, #c2410c) !important;
    }
    .fi-simple-main .fi-btn,
    .fi-simple-main .fi-btn .fi-btn-label,
    .fi-simple-main .fi-btn span,
    .fi-simple-main .fi-btn svg {
        color: #ffffff !important;
        --tw-text-opacity: 1 !important;
    }

    /* Rodapé */
    .pcm-footer {
        position: fixed;
        bottom: 1rem; right: 0; left: 0;
        text-align: center;
        color: rgba(24, 24, 27, .45);
        font-size: .72rem; letter-spacing: .03em;
        pointer-events: none;
    }
    @media (min-width: 1024px) { .pcm-footer { left: 50%; } }
    .dark .pcm-footer { color: rgba(255, 255, 255, .4); }
</style>

<div class="pcm-hero">
    <img src="{{ $crest }}" alt="Carlos Menegatti FC" class="pcm-hero-logo">
    <h1>Gestão do time,<br><span>no controle.</span></h1>
    <p class="pcm-hero-sub">Mensalidades, diárias e o caixa do Carlos Menegatti FC — tudo em um só lugar, com cobrança via Pix.</p>
    <ul>
        <li>
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 10a1 1 0 1 1 1.4-1.4l3.1 3.09 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            Mensalidades e diárias automáticas
        </li>
        <li>
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 10a1 1 0 1 1 1.4-1.4l3.1 3.09 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            Fluxo de caixa: receitas e despesas
        </li>
        <li>
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 10a1 1 0 1 1 1.4-1.4l3.1 3.09 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            Cobrança por Pix e relatórios
        </li>
    </ul>
</div>

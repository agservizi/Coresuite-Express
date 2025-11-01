<?php
declare(strict_types=1);

/**
 * @var array<string, string> $values
 * @var array<string, string> $defaults
 * @var array<string, string> $formats
 * @var array<string, string> $orientations
 * @var array<string, array<string, string>> $themes
 * @var array<int, array<string, mixed>> $designs
 */

$pageTitle = 'Design Studio Offerte';

$designerTemplates = [
    [
        'id' => 'aurora_spotlight',
        'name' => 'Aurora Spotlight',
        'description' => 'Hero a tutta pagina con badge e lista punti forza.',
        'format' => 'A4',
        'orientation' => 'portrait',
        'html' => trim(<<<'HTML'
<section class="brochure-hero">
    <div class="brochure-hero__badge">Nuova offerta premium</div>
    <h1 class="brochure-hero__title">Connetti il tuo store con performance da prima classe.</h1>
    <p class="brochure-hero__subtitle">Dashboard omnicanale, assistenza proattiva e attivazioni flash in un unico ecosistema.</p>
    <div class="brochure-hero__cta">€ 79,90 / mese · Attiva ora</div>
    <ul class="brochure-hero__features">
        <li>Analytics predittiva su vendite e stock</li>
        <li>Team coaching con playbook digitali</li>
        <li>Supporto 7/7 con escalation prioritaria</li>
    </ul>
</section>
HTML),
        'css' => trim(<<<'CSS'
body { background: linear-gradient(135deg,#312e81,#1d4ed8); color: #0f172a; font-family: 'Helvetica', 'Arial', sans-serif; }
.brochure-hero { max-width: 820px; margin: 0 auto; padding: 64px 72px; border-radius: 32px; background: rgba(255,255,255,0.94); box-shadow: 0 34px 80px rgba(15,23,42,0.25); display: flex; flex-direction: column; gap: 20px; }
.brochure-hero__badge { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; padding: 8px 16px; border-radius: 999px; background: rgba(59,130,246,0.12); color: #1d4ed8; }
.brochure-hero__title { font-size: 44px; line-height: 1.08; margin: 0; color: #0f172a; }
.brochure-hero__subtitle { font-size: 18px; line-height: 1.6; color: rgba(15,23,42,0.75); margin: 0; }
.brochure-hero__cta { display: inline-flex; align-items: center; justify-content: center; padding: 14px 26px; border-radius: 999px; background: linear-gradient(135deg,#3b82f6,#9333ea); color: #fff; font-weight: 700; letter-spacing: 0.03em; box-shadow: 0 22px 40px rgba(59,130,246,0.32); }
.brochure-hero__features { list-style: none; display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 16px; margin: 10px 0 0; padding: 0; }
.brochure-hero__features li { background: rgba(59,130,246,0.08); border-radius: 18px; padding: 16px; font-weight: 600; color: #1e40af; border: 1px solid rgba(59,130,246,0.18); box-shadow: 0 18px 32px rgba(15,23,42,0.12); }
CSS),
    ],
    [
        'id' => 'sunset_showcase',
        'name' => 'Sunset Showcase',
        'description' => 'Layout metà immagine, metà contenuti con callout.',
        'format' => 'A3',
        'orientation' => 'landscape',
        'html' => trim(<<<'HTML'
<section class="showcase">
    <div class="showcase__media" style="background-image:url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80');"></div>
    <div class="showcase__content">
        <h2>Retail Sunset Pack</h2>
        <p>Esperienza premium per catene retail: storytelling visivo, promo automatiche e analisi di convergenza.</p>
        <div class="showcase__grid">
            <article>
                <h3>Attivazioni omnicanale</h3>
                <p>Integrazione con CRM, campagne dinamiche e customer journey orchestrata.</p>
            </article>
            <article>
                <h3>Insight immediati</h3>
                <p>KPI evoluti con alert predittivi per stock, offerte e redemption.</p>
            </article>
            <article>
                <h3>Story Studio</h3>
                <p>Template interattivi per vetrine, schermi e campagne social coordinate.</p>
            </article>
        </div>
        <div class="showcase__cta">Prenota una demo immersiva</div>
    </div>
</section>
HTML),
        'css' => trim(<<<'CSS'
body { margin: 0; font-family: 'Helvetica','Arial',sans-serif; background: #0f172a; color: #0f172a; }
.showcase { display: grid; grid-template-columns: 1.15fr 1fr; min-height: 520px; background: linear-gradient(135deg,#fb7185,#f97316); border-radius: 32px; overflow: hidden; box-shadow: 0 42px 90px rgba(15,23,42,0.35); }
.showcase__media { background-size: cover; background-position: center; }
.showcase__content { padding: 48px 56px; display: flex; flex-direction: column; gap: 22px; background: rgba(255,255,255,0.92); }
.showcase__content h2 { font-size: 42px; margin: 0; color: #9a3412; }
.showcase__content > p { font-size: 18px; line-height: 1.6; color: rgba(15,23,42,0.72); margin: 0; }
.showcase__grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 16px; }
.showcase__grid article { background: rgba(249,115,22,0.08); padding: 18px; border-radius: 18px; border: 1px solid rgba(251,146,60,0.3); box-shadow: 0 18px 36px rgba(15,23,42,0.16); }
.showcase__grid h3 { margin: 0 0 8px; font-size: 18px; color: #be123c; }
.showcase__grid p { margin: 0; font-size: 14px; line-height: 1.55; color: rgba(190,18,60,0.85); }
.showcase__cta { display: inline-flex; align-items: center; justify-content: center; align-self: flex-start; padding: 12px 22px; border-radius: 14px; font-weight: 700; color: #fff; background: linear-gradient(135deg,#ec4899,#f97316); box-shadow: 0 20px 38px rgba(236,72,153,0.32); }
CSS),
    ],
];

$designerConfig = [
    'designs' => $designs,
    'templates' => $designerTemplates,
    'formats' => $formats,
    'orientations' => $orientations,
    'themes' => $themes,
    'defaults' => [
        'name' => 'Brochure personalizzata',
        'format' => $values['format'] ?? 'A4',
        'orientation' => $values['orientation'] ?? 'portrait',
        'theme' => $values['theme'] ?? 'aurora',
    ],
    'endpoints' => [
        'list' => 'index.php?page=offers_designer&action=list_designs',
        'load' => 'index.php?page=offers_designer&action=load_design',
        'save' => 'index.php?page=offers_designer&action=save_design',
        'delete' => 'index.php?page=offers_designer&action=delete_design',
        'generate' => 'index.php?page=offers_designer&action=generate_canvas_pdf',
    ],
];

$designerConfigJson = json_encode($designerConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($designerConfigJson === false) {
    $designerConfigJson = '{}';
}
?>
<link rel="stylesheet" href="https://unpkg.com/grapesjs@0.22.4/dist/css/grapes.min.css">
<section class="page">
    <header class="page__header">
        <div class="page__header-top">
            <h2>Design Studio Offerte</h2>
            <p>Genera presentazioni premium in PDF scegliendo tra form guidato o editor drag & drop.</p>
        </div>
        <div class="page__actions">
            <div class="action-list action-list--header">
                <article class="action-list__item action-list__item--info">
                    <div class="action-list__content">
                        <span class="action-list__label">Classic builder + visual designer con template salvabili.</span>
                        <span class="action-list__motivation">Scarica il PDF finale in alta risoluzione con un click.</span>
                    </div>
                </article>
            </div>
        </div>
    </header>

    <div class="designer-shell" data-designer-root>
        <div class="designer-tabs" role="tablist">
            <button type="button" class="designer-tab designer-tab--active" data-designer-tab="classic" aria-selected="true">Form guidato</button>
            <button type="button" class="designer-tab" data-designer-tab="canvas" aria-selected="false">Editor avanzato</button>
        </div>

        <div class="designer-panels">
            <div class="designer-panel designer-panel--active" data-designer-panel="classic">
                <div class="design-layout">
                    <div class="design-layout__form">
                        <form method="post" class="form" enctype="application/x-www-form-urlencoded">
                            <input type="hidden" name="action" value="generate_brochure">
                            <div class="form__grid">
                                <div class="form__group">
                                    <label for="title">Titolo offerta</label>
                                    <input type="text" id="title" name="title" maxlength="120" value="<?= htmlspecialchars($values['title'] ?? '') ?>" required>
                                    <small class="muted">Esempio: "Piano Elite Retail"</small>
                                </div>
                                <div class="form__group">
                                    <label for="subtitle">Sottotitolo</label>
                                    <input type="text" id="subtitle" name="subtitle" maxlength="160" value="<?= htmlspecialchars($values['subtitle'] ?? '') ?>">
                                </div>
                                <div class="form__group">
                                    <label for="price">Prezzo / payoff</label>
                                    <input type="text" id="price" name="price" maxlength="80" value="<?= htmlspecialchars($values['price'] ?? '') ?>">
                                </div>
                                <div class="form__group">
                                    <label for="cta">Call to action</label>
                                    <input type="text" id="cta" name="cta" maxlength="90" value="<?= htmlspecialchars($values['cta'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form__group">
                                <label for="description">Descrizione</label>
                                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form__grid">
                                <div class="form__group">
                                    <label for="highlights">Punti di forza (uno per riga)</label>
                                    <textarea id="highlights" name="highlights" rows="4"><?= htmlspecialchars($values['highlights'] ?? '') ?></textarea>
                                </div>
                                <div class="form__group">
                                    <label for="contacts">Contatti</label>
                                    <textarea id="contacts" name="contacts" rows="4"><?= htmlspecialchars($values['contacts'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form__grid">
                                <div class="form__group">
                                    <label for="hero_image">Immagine hero (URL opzionale)</label>
                                    <input type="url" id="hero_image" name="hero_image" value="<?= htmlspecialchars($values['hero_image'] ?? '') ?>" placeholder="https://cdn.example.com/hero.jpg">
                                    <small class="muted">L'immagine viene ritagliata automaticamente in un riquadro panoramico.</small>
                                </div>
                                <div class="form__group">
                                    <label for="format">Formato pagina</label>
                                    <select id="format" name="format">
                                        <?php foreach ($formats as $formatKey => $formatLabel): ?>
                                            <option value="<?= htmlspecialchars($formatKey) ?>" <?= ($values['format'] ?? 'A4') === $formatKey ? 'selected' : '' ?>><?= htmlspecialchars($formatLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form__group">
                                    <label for="orientation">Orientamento</label>
                                    <select id="orientation" name="orientation">
                                        <?php foreach ($orientations as $orientationKey => $orientationLabel): ?>
                                            <option value="<?= htmlspecialchars($orientationKey) ?>" <?= ($values['orientation'] ?? 'portrait') === $orientationKey ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($orientationLabel)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <fieldset class="form__group">
                                <legend>Tema cromatico</legend>
                                <div class="theme-grid">
                                    <?php foreach ($themes as $themeKey => $themeData): ?>
                                        <?php
                                            $isChecked = ($values['theme'] ?? 'aurora') === $themeKey;
                                            $primary = $themeData['primary'] ?? '#2563eb';
                                            $secondary = $themeData['secondary'] ?? '#9333ea';
                                            $accent = $themeData['accent'] ?? '#f97316';
                                        ?>
                                        <label class="theme-card" data-theme="<?= htmlspecialchars($themeKey) ?>">
                                            <input type="radio" name="theme" value="<?= htmlspecialchars($themeKey) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                            <span class="theme-card__preview" style="--primary: <?= htmlspecialchars($primary) ?>; --secondary: <?= htmlspecialchars($secondary) ?>; --accent: <?= htmlspecialchars($accent) ?>;"></span>
                                            <span class="theme-card__label"><?= htmlspecialchars($themeData['name'] ?? ucfirst($themeKey)) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>

                            <button type="submit" class="btn btn--primary btn--full">Genera PDF</button>
                        </form>
                    </div>
                    <aside class="design-layout__tips">
                        <section class="card">
                            <header class="card__header">
                                <h3>Consigli creativi</h3>
                            </header>
                            <ul class="card__list">
                                <li>Utilizza frasi brevi e incisive per i punti di forza.</li>
                                <li>Carica immagini in alta risoluzione (minimo 1600px di larghezza).</li>
                                <li>Sfrutta il formato A3 per affissioni o display in negozio.</li>
                                <li>Inserisci i contatti diretti del referente commerciale.</li>
                            </ul>
                        </section>
                        <section class="card">
                            <header class="card__header">
                                <h3>Workflow suggerito</h3>
                            </header>
                            <div class="card__meta">1. Compila il form &nbsp;•&nbsp; 2. Scarica il PDF &nbsp;•&nbsp; 3. Condividi con il cliente</div>
                        </section>
                    </aside>
                </div>
            </div>

            <div class="designer-panel" data-designer-panel="canvas" hidden>
                <div class="canvas-explain">
                    <div>
                        <h3>Editor drag &amp; drop</h3>
                        <p>Costruisci layout tipo Canva con blocchi pronti, salva i progetti e condividili con il team.</p>
                    </div>
                    <button type="button" class="btn btn--ghost" data-designer-action="new">Nuovo layout vuoto</button>
                </div>
                <div class="canvas-layout">
                    <aside class="canvas-sidebar">
                        <div class="canvas-sidebar__section">
                            <header class="canvas-sidebar__header">
                                <h4>I tuoi layout</h4>
                                <button type="button" class="btn btn--ghost" data-designer-action="refresh">Aggiorna</button>
                            </header>
                            <ul class="canvas-design-list" data-design-list>
                                <?php foreach ($designs as $design): ?>
                                    <li class="canvas-design-card" data-design-id="<?= htmlspecialchars($design['id']) ?>">
                                        <div class="canvas-design-card__main">
                                            <strong><?= htmlspecialchars($design['name'] ?? 'Layout') ?></strong>
                                            <span><?= htmlspecialchars(strtoupper((string) ($design['format'] ?? 'A4'))) ?> · <?= htmlspecialchars($design['orientation'] ?? 'portrait') ?></span>
                                        </div>
                                        <div class="canvas-design-card__actions">
                                            <button type="button" class="canvas-design-card__action" data-designer-action="load-design">Apri</button>
                                            <button type="button" class="canvas-design-card__action canvas-design-card__action--danger" data-designer-action="delete-design">Elimina</button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="muted canvas-sidebar__hint">I layout salvati restano associati al tuo account.</p>
                        </div>
                        <div class="canvas-sidebar__section">
                            <h4>Template premium</h4>
                            <div class="canvas-template-grid">
                                <?php foreach ($designerTemplates as $template): ?>
                                    <button type="button" class="canvas-template" data-template="<?= htmlspecialchars($template['id']) ?>">
                                        <span class="canvas-template__name"><?= htmlspecialchars($template['name']) ?></span>
                                        <span class="canvas-template__desc"><?= htmlspecialchars($template['description']) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </aside>
                    <div class="canvas-main">
                        <div class="canvas-toolbar">
                            <div class="canvas-toolbar__group">
                                <label for="designer-name">Nome layout</label>
                                <input type="text" id="designer-name" placeholder="Brochure premium retail">
                            </div>
                            <div class="canvas-toolbar__group">
                                <label for="designer-format">Formato</label>
                                <select id="designer-format">
                                    <?php foreach ($formats as $formatKey => $formatLabel): ?>
                                        <option value="<?= htmlspecialchars($formatKey) ?>"><?= htmlspecialchars($formatLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="canvas-toolbar__group">
                                <label for="designer-orientation">Orientamento</label>
                                <select id="designer-orientation">
                                    <?php foreach ($orientations as $orientationKey => $orientationLabel): ?>
                                        <option value="<?= htmlspecialchars($orientationKey) ?>"><?= htmlspecialchars(ucfirst($orientationLabel)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="canvas-toolbar__actions">
                                <button type="button" class="btn btn--ghost" data-designer-action="snapshot">Anteprima</button>
                                <button type="button" class="btn btn--ghost" data-designer-action="reset">Reset</button>
                                <button type="button" class="btn btn--primary" data-designer-action="save">Salva</button>
                                <button type="button" class="btn btn--accent" data-designer-action="export">Esporta PDF</button>
                            </div>
                        </div>
                        <div class="canvas-editor" data-canvas-wrapper>
                            <div id="designer-canvas" class="designer-canvas"></div>
                        </div>
                        <div class="canvas-notes">
                            <label for="designer-notes">Note interne</label>
                            <textarea id="designer-notes" rows="2" placeholder="Annota commenti o istruzioni per il tuo team"></textarea>
                        </div>
                    </div>
                </div>
                <form id="designer-export-form" method="post" action="index.php?page=offers_designer&action=generate_canvas_pdf" target="_blank" hidden>
                    <input type="hidden" name="canvas_payload" value="">
                </form>
            </div>
        </div>
    </div>
</section>
<script>
window.offerDesigner = <?= $designerConfigJson ?>;
</script>
<script src="https://unpkg.com/grapesjs@0.22.4/dist/grapes.min.js"></script>
<script src="https://unpkg.com/grapesjs-preset-webpage@1.0.2/dist/grapesjs-preset-webpage.min.js"></script>
<script defer src="assets/js/offers-designer.js"></script>

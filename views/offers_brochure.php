<?php
declare(strict_types=1);

/**
 * @var array<string, string> $values
 * @var array<string, string> $defaults
 * @var array<string, string> $formats
 * @var array<string, string> $orientations
 * @var array<string, array<string, string>> $themes
 */

$pageTitle = 'Design Studio Offerte';
?>
<section class="page">
    <header class="page__header">
        <div class="page__header-top">
            <h2>Design Studio Offerte</h2>
            <p>Genera presentazioni PDF in formato A3/A4 con layout immersivo per le tue offerte commerciali.</p>
        </div>
        <div class="page__actions">
            <div class="action-list action-list--header">
                <article class="action-list__item action-list__item--info">
                    <div class="action-list__content">
                        <span class="action-list__label">Personalizza grafica, colori e contenuti in pochi minuti.</span>
                        <span class="action-list__motivation">Il PDF viene creato con componenti vettoriali ottimizzati per la stampa professionale.</span>
                    </div>
                </article>
            </div>
        </div>
    </header>

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
</section>

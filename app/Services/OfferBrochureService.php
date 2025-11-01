<?php
declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

final class OfferBrochureService
{
    public const FORMATS = [
        'A4' => 'A4',
        'A3' => 'A3',
    ];

    public const ORIENTATIONS = [
        'portrait' => 'portrait',
        'landscape' => 'landscape',
    ];

    public const THEMES = [
        'aurora' => [
            'name' => 'Aurora',
            'primary' => '#3b82f6',
            'secondary' => '#9333ea',
            'accent' => '#f97316',
            'background' => 'linear-gradient(135deg, rgba(59,130,246,0.9), rgba(147,51,234,0.9))',
        ],
        'sunset' => [
            'name' => 'Sunset',
            'primary' => '#fb7185',
            'secondary' => '#f97316',
            'accent' => '#facc15',
            'background' => 'linear-gradient(135deg, rgba(251,113,133,0.9), rgba(249,115,22,0.9))',
        ],
        'emerald' => [
            'name' => 'Emerald',
            'primary' => '#10b981',
            'secondary' => '#14b8a6',
            'accent' => '#22d3ee',
            'background' => 'linear-gradient(135deg, rgba(16,185,129,0.9), rgba(14,165,233,0.9))',
        ],
    ];

    /**
     * @param array<string, mixed> $payload
     * @return array{filename: string, content: string, mime: string}
     */
    public function generate(array $payload): array
    {
        $format = strtoupper((string) ($payload['format'] ?? 'A4'));
        if (!isset(self::FORMATS[$format])) {
            $format = 'A4';
        }

        $orientation = strtolower((string) ($payload['orientation'] ?? 'portrait'));
        if (!isset(self::ORIENTATIONS[$orientation])) {
            $orientation = 'portrait';
        }

        $themeKey = strtolower((string) ($payload['theme'] ?? 'aurora'));
        $theme = self::THEMES[$themeKey] ?? self::THEMES['aurora'];

        $title = $this->sanitizeText($payload['title'] ?? 'Nuova offerta business');
        $subtitle = $this->sanitizeText($payload['subtitle'] ?? 'Soluzione completa per il tuo punto vendita');
        $price = $this->sanitizeText($payload['price'] ?? '€ 49,90 / mese');
        $description = $this->sanitizeText($payload['description'] ?? "Attiva subito il pacchetto che integra connettività, assistenza premium e strumenti di gestione per il tuo negozio.");
        $cta = $this->sanitizeText($payload['cta'] ?? 'Prenota una consulenza gratuita');
        $contacts = $this->sanitizeText($payload['contacts'] ?? "Telefono: +39 080 123 45 67\nEmail: commerciale@coresuite.it");

        $rawHighlights = (string) ($payload['highlights'] ?? "Connessioni 5G illimitate\nDashboard di controllo centralizzata\nSupporto dedicato 7/7");
        $highlightItems = [];
        $highlightLines = preg_split("/[\r\n]+/", $rawHighlights) ?: [];
        foreach ($highlightLines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $highlightItems[] = $trimmed;
            }
        }
        if ($highlightItems === []) {
            $highlightItems = ['Prestazioni elevate', 'Sicurezza enterprise', 'Supporto dedicato'];
        }

        $heroImageUrl = $this->sanitizeUrl($payload['hero_image'] ?? '');

        $html = $this->buildHtml([
            'title' => $title,
            'subtitle' => $subtitle,
            'price' => $price,
            'description' => $description,
            'cta' => $cta,
            'contacts' => $contacts,
            'highlights' => $highlightItems,
            'theme' => $theme,
            'orientation' => $orientation,
            'hero_image' => $heroImageUrl,
        ]);

        return $this->renderPdfDocument($html, $format, $orientation, 'brochure');
    }

    /**
     * @param array<string, mixed> $options
     * @return array{filename: string, content: string, mime: string}
     */
    public function generateFromCustomLayout(string $html, array $options = []): array
    {
        $trimmedHtml = trim($html);
        if ($trimmedHtml === '') {
            $trimmedHtml = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:Helvetica,Arial,sans-serif;font-size:32px;color:#1f2937;">Nessun contenuto disponibile</div>';
        }

        $css = isset($options['css']) ? (string) $options['css'] : '';
        $format = isset($options['format']) ? strtoupper((string) $options['format']) : 'A4';
        if (!isset(self::FORMATS[$format])) {
            $format = 'A4';
        }
        $orientation = isset($options['orientation']) ? strtolower((string) $options['orientation']) : 'portrait';
        if (!isset(self::ORIENTATIONS[$orientation])) {
            $orientation = 'portrait';
        }
        $meta = isset($options['meta']) && is_array($options['meta']) ? $options['meta'] : [];
        $documentTitle = isset($meta['title']) && is_string($meta['title']) && $meta['title'] !== ''
            ? $meta['title']
            : 'Brochure personalizzata';
        $filenamePrefix = isset($meta['filename_prefix']) && is_string($meta['filename_prefix']) && $meta['filename_prefix'] !== ''
            ? preg_replace('/[^a-zA-Z0-9_-]+/', '_', $meta['filename_prefix'])
            : 'brochure_canvas';

        $stylesheet = $css !== '' ? "\n" . $css : '';
        $documentHtml = <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>{$this->escape($documentTitle)}</title>
<style>
@page { margin: 0; }
body { margin: 0; }
{$stylesheet}
</style>
</head>
<body>
{$trimmedHtml}
</body>
</html>
HTML;

        return $this->renderPdfDocument($documentHtml, $format, $orientation, $filenamePrefix);
    }

    /**
     * @return array{filename: string, content: string, mime: string}
     */
    private function renderPdfDocument(string $html, string $format, string $orientation, string $filenamePrefix): array
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($format, $orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $pdfOutput = $dompdf->output();
        $filename = sprintf('%s_%s.pdf', $filenamePrefix, date('Ymd_His'));

        return [
            'filename' => $filename,
            'content' => $pdfOutput,
            'mime' => 'application/pdf',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildHtml(array $data): string
    {
        $theme = $data['theme'];
        $highlights = $data['highlights'];
        $heroImage = $data['hero_image'];

        $highlightBlocks = '';
        foreach ($highlights as $index => $item) {
            $delay = $index * 40;
            $highlightBlocks .= sprintf('<li class="feature feature--%d">%s</li>', $index + 1, htmlspecialchars($item, ENT_QUOTES, 'UTF-8'));
        }

        $heroBlock = '';
        if ($heroImage !== null) {
            $heroBlock = sprintf('<div class="hero__image" style="background-image:url(%s);"></div>', htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8'));
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Brochure Offerta</title>
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 0;
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #0f172a;
        background: #f8fafc;
    }
    .brochure {
        position: relative;
        min-height: 100vh;
        padding: 48px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: #0f172a;
    }
    .brochure::before,
    .brochure::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 36px;
        background: {$theme['background']};
        opacity: 0.92;
        z-index: 0;
    }
    .brochure::after {
        background: radial-gradient(circle at top right, rgba(255,255,255,0.25), transparent 60%);
        mix-blend-mode: screen;
    }
    .brochure__content {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 3fr 2fr;
        gap: 40px;
        align-items: stretch;
        color: #0f172a;
    }
    .header {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding: 32px;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.25);
    }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(255,255,255,0.6);
        color: {$theme['primary']};
        box-shadow: inset 0 0 0 1px rgba(15,23,42,0.08);
        width: fit-content;
    }
    .title {
        font-size: 42px;
        font-weight: 700;
        line-height: 1.1;
        margin: 0;
        color: #0f172a;
    }
    .subtitle {
        font-size: 22px;
        font-weight: 500;
        margin: 0;
        color: rgba(15, 23, 42, 0.78);
    }
    .description {
        font-size: 15px;
        line-height: 1.6;
        color: rgba(15, 23, 42, 0.82);
        margin: 12px 0 0;
    }
    .price-tag {
        display: inline-block;
        margin-top: 20px;
        padding: 16px 28px;
        border-radius: 18px;
        background: linear-gradient(135deg, {$theme['primary']}, {$theme['secondary']});
        color: #fff;
        font-size: 24px;
        font-weight: 700;
        box-shadow: 0 18px 35px rgba(59,130,246,0.35);
    }
    .cta {
        margin-top: 24px;
        display: inline-block;
        padding: 14px 24px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #fff;
        background: {$theme['accent']};
        box-shadow: 0 18px 34px rgba(249,115,22,0.35);
    }
    .feature-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin: 32px 0;
        padding: 0;
        list-style: none;
    }
    .feature {
        background: rgba(255,255,255,0.88);
        border-radius: 22px;
        padding: 16px 18px;
        font-weight: 600;
        color: {$theme['primary']};
        box-shadow: 0 20px 40px rgba(15,23,42,0.18);
        border: 1px solid rgba(15,23,42,0.05);
        position: relative;
    }
    .feature::before {
        content: '';
        position: absolute;
        top: -20px;
        left: 18px;
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: linear-gradient(135deg, {$theme['primary']}, {$theme['secondary']});
        opacity: 0.25;
        filter: blur(6px);
    }
    .hero {
        display: flex;
        flex-direction: column;
        gap: 18px;
        background: rgba(15, 23, 42, 0.45);
        border-radius: 28px;
        padding: 28px;
        color: #f8fafc;
        position: relative;
        overflow: hidden;
    }
    .hero::before {
        content: '';
        position: absolute;
        inset: -60px;
        background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.25), transparent 60%);
        z-index: 0;
    }
    .hero__headline {
        position: relative;
        z-index: 1;
        font-size: 26px;
        font-weight: 700;
        line-height: 1.3;
    }
    .hero__image {
        position: relative;
        z-index: 1;
        flex: 1;
        border-radius: 22px;
        background-size: cover;
        background-position: center;
        min-height: 240px;
        box-shadow: 0 24px 48px rgba(15,23,42,0.45);
    }
    .contact {
        position: relative;
        z-index: 1;
        margin-top: auto;
        padding: 18px;
        border-radius: 18px;
        background: rgba(15, 23, 42, 0.65);
        font-size: 13px;
        line-height: 1.6;
        color: rgba(226, 232, 240, 0.92);
    }
    .footer {
        position: relative;
        z-index: 1;
        margin-top: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: rgba(15,23,42,0.75);
        font-size: 12px;
    }
    .footer__brand {
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(15,23,42,0.6);
    }
</style>
</head>
<body>
    <main class="brochure">
        <div class="brochure__content">
            <section class="header">
                <span class="badge">Offerta esclusiva</span>
                <h1 class="title">{$this->escape($data['title'])}</h1>
                <h2 class="subtitle">{$this->escape($data['subtitle'])}</h2>
                <p class="description">{$this->escape($data['description'])}</p>
                <span class="price-tag">{$this->escape($data['price'])}</span>
                <span class="cta">{$this->escape($data['cta'])}</span>
                <ul class="feature-list">
                    {$highlightBlocks}
                </ul>
            </section>
            <aside class="hero">
                <h3 class="hero__headline">Trasforma il tuo store in un hub digitale connesso e performante.</h3>
                {$heroBlock}
                <div class="contact">
                    <strong>Contatti rapidi</strong><br>
                    {$this->escape(nl2br($data['contacts']))}
                </div>
            </aside>
        </div>
        <footer class="footer">
            <span class="footer__brand">Coresuite Express</span>
            <span>Visione. Performance. Risultati.</span>
        </footer>
    </main>
</body>
</html>
HTML;
    }

    private function sanitizeText(mixed $value): string
    {
        $text = trim((string) $value);
        return $text !== '' ? $text : '';
    }

    private function sanitizeUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return null;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

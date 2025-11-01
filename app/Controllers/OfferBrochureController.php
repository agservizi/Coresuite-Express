<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\OfferBrochureService;

final class OfferBrochureController
{
    public function __construct(private OfferBrochureService $brochureService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function formDefaults(): array
    {
        return [
            'title' => 'Piano Elite Retail',
            'subtitle' => 'La soluzione omnicanale definitiva per il tuo store',
            'price' => '€ 79,90 / mese',
            'description' => "Un pacchetto completo per potenziare vendite, marketing e customer care con un'unica piattaforma.",
            'cta' => 'Richiedi una demo',
            'contacts' => "Telefono: +39 080 123 45 67\nEmail: commerciale@coresuite.it",
            'highlights' => "Dashboard in tempo reale\nAnalytics predittiva\nSupporto premium 7/7\nOnboarding dedicato",
            'format' => 'A4',
            'orientation' => 'portrait',
            'theme' => 'aurora',
            'hero_image' => '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{filename: string, content: string, mime: string}
     */
    public function generatePdf(array $input): array
    {
        return $this->brochureService->generate($input);
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(?array $oldInput = null): array
    {
        $defaults = $this->formDefaults();
        $values = $defaults;
        if (is_array($oldInput)) {
            foreach ($defaults as $key => $value) {
                if (array_key_exists($key, $oldInput)) {
                    $values[$key] = is_string($oldInput[$key]) ? $oldInput[$key] : $value;
                }
            }
        }

        return [
            'values' => $values,
            'defaults' => $defaults,
            'formats' => OfferBrochureService::FORMATS,
            'orientations' => OfferBrochureService::ORIENTATIONS,
            'themes' => OfferBrochureService::THEMES,
        ];
    }
}

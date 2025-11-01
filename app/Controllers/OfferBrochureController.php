<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\OfferBrochureService;
use App\Services\OfferDesignService;

final class OfferBrochureController
{
    public function __construct(
        private OfferBrochureService $brochureService,
        private OfferDesignService $designService
    )
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
    public function viewData(?array $oldInput = null, ?int $userId = null): array
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
            'designs' => $userId !== null ? $this->designService->listForUser($userId) : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDesigns(int $userId): array
    {
        return $this->designService->listForUser($userId);
    }

    public function loadDesign(int $userId, string $designId): ?array
    {
        return $this->designService->findByPublicId($designId, $userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool, message:string, errors?:array<int,string>, design?:array<string,mixed>}
     */
    public function saveDesign(int $userId, array $payload): array
    {
        return $this->designService->save($payload, $userId);
    }

    public function deleteDesign(int $userId, string $designId): array
    {
        return $this->designService->delete($designId, $userId);
    }

    public function markDesignUsed(int $userId, string $designId): void
    {
        $this->designService->touchLastUsed($designId, $userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{filename: string, content: string, mime: string}
     */
    public function generateFromCanvas(array $payload): array
    {
        $html = (string) ($payload['html'] ?? '');
        $css = (string) ($payload['css'] ?? '');
        $format = isset($payload['format']) ? (string) $payload['format'] : 'A4';
        $orientation = isset($payload['orientation']) ? (string) $payload['orientation'] : 'portrait';
        $meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];

        return $this->brochureService->generateFromCustomLayout($html, [
            'css' => $css,
            'format' => $format,
            'orientation' => $orientation,
            'meta' => $meta,
        ]);
    }
}

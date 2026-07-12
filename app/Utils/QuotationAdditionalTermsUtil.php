<?php

namespace App\Utils;

class QuotationAdditionalTermsUtil
{
    /**
     * Section definitions (title, default content, placeholder).
     */
    public static function sectionDefinitions(): array
    {
        $defaults = config('constants.default_quotation_additional_terms', []);

        return [
            'artwork_preproduction' => [
                'title' => 'ARTWORK, PRE-PRODUCTION & SAMPLE CHARGES',
                'default' => $defaults['artwork_preproduction'] ?? '',
                'placeholder' => '',
                'rows' => 8,
            ],
            'delivery' => [
                'title' => 'DELIVERY',
                'default' => $defaults['delivery'] ?? '',
                'placeholder' => 'Custom',
                'rows' => 3,
            ],
            'installation' => [
                'title' => 'INSTALLATION',
                'default' => $defaults['installation'] ?? '',
                'placeholder' => 'Custom',
                'rows' => 3,
            ],
            'additional_accessories' => [
                'title' => 'ADDITIONAL ACCESSORIES',
                'default' => $defaults['additional_accessories'] ?? '',
                'placeholder' => 'Custom',
                'rows' => 3,
            ],
        ];
    }

    /**
     * Merge saved JSON with defaults for form display.
     */
    public static function mergeWithSaved(?string $json): array
    {
        $saved = [];
        if (! empty($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $saved = $decoded;
            }
        }

        $merged = [];
        foreach (self::sectionDefinitions() as $key => $section) {
            $merged[$key] = array_key_exists($key, $saved)
                ? (string) $saved[$key]
                : (string) $section['default'];
        }

        return $merged;
    }

    /**
     * Encode request input into JSON for storage.
     */
    public static function encodeFromInput(array $input): ?string
    {
        if (! array_key_exists('quotation_additional_terms', $input)) {
            return null;
        }

        $raw = $input['quotation_additional_terms'];
        if (! is_array($raw)) {
            return null;
        }

        $encoded = [];
        foreach (self::sectionDefinitions() as $key => $section) {
            $encoded[$key] = trim((string) ($raw[$key] ?? ''));
        }

        return json_encode($encoded, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Sections to render on PDF (non-empty content only).
     *
     * @return array<int, array{title: string, paragraphs: array<int, string>}>
     */
    public static function sectionsForPdf(?string $json): array
    {
        $values = self::mergeWithSaved($json);
        $sections = [];

        foreach (self::sectionDefinitions() as $key => $definition) {
            $content = trim((string) ($values[$key] ?? ''));
            if ($content === '') {
                continue;
            }

            $paragraphs = preg_split("/\r\n\r\n|\n\n|\r\r/", $content) ?: [];
            $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), fn ($p) => $p !== ''));

            if (empty($paragraphs)) {
                continue;
            }

            $sections[] = [
                'title' => $definition['title'],
                'paragraphs' => $paragraphs,
            ];
        }

        return $sections;
    }

    /**
     * Default JSON for new quotations.
     */
    public static function defaultJson(): string
    {
        $defaults = [];
        foreach (self::sectionDefinitions() as $key => $section) {
            $defaults[$key] = (string) $section['default'];
        }

        return json_encode($defaults, JSON_UNESCAPED_UNICODE);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentTemplateBrandingTest extends TestCase
{
    public function test_pdf_templates_use_current_ministry_branding(): void
    {
        $legacyAcronym = 'MS'.'AS';
        $legacyActionPublique = 'action '.'publique';
        $legacyActionSociale = 'action '.'sociale';

        foreach (glob(resource_path('views/demandes/pdf/*.blade.php')) as $templatePath) {
            $template = file_get_contents($templatePath);

            $this->assertStringNotContainsString($legacyAcronym, $template, $templatePath);
            $this->assertStringNotContainsStringIgnoringCase($legacyActionPublique, $template, $templatePath);
            $this->assertStringNotContainsStringIgnoringCase($legacyActionSociale, $template, $templatePath);
        }
    }

    public function test_pdf_templates_use_masculine_director_wording(): void
    {
        foreach (glob(resource_path('views/demandes/pdf/*.blade.php')) as $templatePath) {
            $template = file_get_contents($templatePath);

            $this->assertStringNotContainsStringIgnoringCase('soussignée', $template, $templatePath);
            $this->assertStringNotContainsStringIgnoringCase('Madame le Directeur', $template, $templatePath);
            $this->assertStringNotContainsStringIgnoringCase('Madame la Directrice', $template, $templatePath);
            $this->assertStringNotContainsStringIgnoringCase('Directrice', $template, $templatePath);
        }
    }

    public function test_pdf_templates_use_double_form_for_contractual_status(): void
    {
        foreach (glob(resource_path('views/demandes/pdf/*.blade.php')) as $templatePath) {
            $template = file_get_contents($templatePath);

            $this->assertDoesNotMatchRegularExpression('/^\s*contractuel,\s*$/mi', $template, $templatePath);
        }
    }

    public function test_pdf_templates_avoid_breakable_administrative_fragments(): void
    {
        foreach (glob(resource_path('views/demandes/pdf/*.blade.php')) as $templatePath) {
            $template = file_get_contents($templatePath);

            $this->assertStringNotContainsString('matricule de solde n° <strong>{{ $demande->matricule }},</strong>', $template, $templatePath);
            $this->assertStringNotContainsString('n&nbsp;°', $template, $templatePath);
            $this->assertDoesNotMatchRegularExpression('/<strong>[^<]*[,.;:!?]\s*<\/strong>/', $template, $templatePath);
            $this->assertStringContainsString('administrative-paragraph keep-together', $template, $templatePath);
        }
    }

    public function test_pdf_layout_defines_typographic_keep_together_classes(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/document.blade.php'));

        $this->assertStringContainsString('.nowrap', $layout);
        $this->assertStringContainsString('white-space: nowrap;', $layout);
        $this->assertStringContainsString('.keep-together', $layout);
        $this->assertStringContainsString('page-break-inside: avoid;', $layout);
        $this->assertStringContainsString('signature-block keep-together', $layout);
        $this->assertStringContainsString('class="numero nowrap"', $layout);
        $this->assertStringContainsString('font-size: 19pt;', $layout);
        $this->assertStringContainsString('margin-bottom: 40px;', $layout);
        $this->assertStringContainsString('margin-top: 0;', $layout);
    }
}

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
}

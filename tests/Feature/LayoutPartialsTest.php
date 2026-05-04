<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutPartialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_footer_displays_updated_contact_information(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Fann Résidence, Rue Aimé Césaire')
            ->assertSee('drh@sante.gouv.sn')
            ->assertSee('+221 33 869 42 27')
            ->assertSee('République du Sénégal')
            ->assertSee('Un peuple, Un But, Une Foi');
    }

    public function test_authenticated_footer_only_displays_copyright_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertDontSee('Fann Résidence, Rue Aimé Césaire')
            ->assertDontSee('drh@sante.gouv.sn')
            ->assertDontSee('+221 33 869 42 27')
            ->assertSee('Tous droits réservés.')
            ->assertSee('Portail de dématérialisation des actes administratifs.');
    }
}

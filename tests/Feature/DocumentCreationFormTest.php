<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DocumentCreationFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_does_not_require_a_project_and_shows_live_totals_preview(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::create([
            'username' => 'invoice_admin',
            'name' => 'Invoice Admin',
            'email' => 'invoice@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertViewIs('documents.create')
            ->assertDontSee('name="project_id"', false)
            ->assertSee('id="calculation-preview"', false)
            ->assertSee('Total DPP sebelum diskon')
            ->assertSee('DPP setelah diskon')
            ->assertSee('Total (DPP + PPN)')
            ->assertSee('id="discount"', false);
    }
}

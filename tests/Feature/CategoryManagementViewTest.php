<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CategoryManagementViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $this->admin = User::create([
            'username' => 'coa_admin',
            'name' => 'COA Admin',
            'email' => 'coa-admin@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_coa_page_renders_dashboard_style_header_filters_and_tax_categories(): void
    {
        $this->actingAs($this->admin)
            ->get(route('categories.index', ['per_page' => 25]))
            ->assertOk()
            ->assertSee('Chart of Accounts & Kategori Pajak', false)
            ->assertSee('Akun Sesuai Filter')
            ->assertSee('Cari & Filter Akun', false)
            ->assertSee('Status Fiskal SPT Tahunan')
            ->assertSee('modal-dialog-scrollable', false)
            ->assertSee('coa-action-stack', false)
            ->assertSee('Baris per halaman')
            ->assertSee('Beban Jamuan &amp; Entertainment', false);
    }

    public function test_coa_search_and_type_filters_are_reflected_in_results(): void
    {
        $this->actingAs($this->admin)
            ->get(route('categories.index', [
                'type' => 'expense',
                'search' => 'Beban Gaji',
            ]))
            ->assertOk()
            ->assertSee('5-101')
            ->assertSee('Beban Gaji')
            ->assertDontSee('4-100')
            ->assertDontSee('Pendapatan Penjualan');
    }

    public function test_coa_pagination_preserves_search_filters_and_page_size(): void
    {
        $this->actingAs($this->admin)
            ->get(route('categories.index', [
                'type' => 'expense',
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('name="type" value="expense"', false)
            ->assertSee('name="per_page"', false)
            ->assertSee('value="10" selected', false)
            ->assertSee('page=1', false)
            ->assertSee('type=expense', false)
            ->assertSee('per_page=10', false);
    }

    public function test_coa_page_size_can_be_changed_without_losing_filters(): void
    {
        $this->actingAs($this->admin)
            ->get(route('categories.index', [
                'type' => 'expense',
                'search' => 'Beban',
                'per_page' => 25,
            ]))
            ->assertOk()
            ->assertSee('value="25" selected', false)
            ->assertSee('name="search" value="Beban"', false)
            ->assertSee('name="type" value="expense"', false);
    }
}

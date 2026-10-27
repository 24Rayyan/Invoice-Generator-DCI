<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
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
            ->assertSee('class="form-select customer-select smooth-dropdown"', false)
            ->assertSee('data-placeholder="Pilih customer..."', false)
            ->assertSee('name="item_type[]"', false)
            ->assertSee('Barang (A)')
            ->assertSee('Jasa (B)')
            ->assertSee('id="calculation-preview"', false)
            ->assertSee('Total DPP sebelum diskon')
            ->assertSee('DPP setelah diskon')
            ->assertSee('Total (DPP + PPN)')
            ->assertSee('id="discount"', false)
            ->assertSee('class="col-md-3 invoice-only"', false)
            ->assertSee('class="col-12 invoice-only"', false)
            ->assertSee('data-invoice-number="001/INV/', false)
            ->assertSee('data-po-number="001/PO/', false);

        $this->actingAs($user)
            ->get(route('documents.create', ['type' => 'PO']))
            ->assertOk()
            ->assertSee('value="PO" selected', false)
            ->assertSee('class="col-md-3 invoice-only d-none"', false)
            ->assertSee('class="card border bg-light mt-3 invoice-only d-none"', false);
    }

    public function test_purchase_order_can_be_created_without_item_price_or_unit(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::create([
            'username' => 'po_admin',
            'name' => 'PO Admin',
            'email' => 'po@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'PO Customer']);

        $this->actingAs($user)
            ->post(route('documents.store'), [
                'customer_id' => $customer->id,
                'type' => 'PO',
                'document_number' => '001/PO-TEST/X/2026',
                'item_desc' => ['Laptop'],
                'item_notes' => ['RAM 16 GB, SSD 1 TB'],
                'item_qty' => [3],
            ])
            ->assertRedirect(route('purchase-orders.index'));

        $document = Document::where('document_number', '001/PO-TEST/X/2026')->firstOrFail();
        $item = DocumentItem::where('document_id', $document->id)->firstOrFail();

        $this->assertSame('PO', $document->type);
        $this->assertSame('RAM 16 GB, SSD 1 TB', $item->notes);
        $this->assertEquals(3, (float) $item->qty);
        $this->assertEquals(0, (float) $item->price);
        $this->assertEquals(0, (float) $item->subtotal);
        $this->assertEquals(0, (float) $document->total_amount);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DocumentPurchaseOrderConversionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Konversi PO diuji hanya pada database SQLite sementara.');
        }

        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_purchase_order_items_are_carried_into_a_linked_invoice(): void
    {
        $admin = User::create([
            'username' => 'po_convert_admin',
            'name' => 'PO Convert Admin',
            'email' => 'po-convert@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
            'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'PT PO ke Invoice']);
        $purchaseOrder = Document::create([
            'customer_id' => $customer->id,
            'type' => 'PO',
            'document_number' => '001/PO-TEST/XI/2026',
            'date' => '2026-11-15',
            'status' => 'DRAFT',
            'total_amount' => 0,
        ]);
        DocumentItem::create([
            'document_id' => $purchaseOrder->id,
            'description' => 'Wireless router',
            'qty' => 3,
            'unit' => 'Box',
            'price' => 0,
            'subtotal' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee(route('purchase-orders.create-invoice', $purchaseOrder), false);

        $this->actingAs($admin)
            ->get(route('purchase-orders.create-invoice', $purchaseOrder))
            ->assertOk()
            ->assertSee('Invoice dari PO 001/PO-TEST/XI/2026')
            ->assertSee('name="source_po_id" value="'.$purchaseOrder->id.'"', false)
            ->assertSee('value="Wireless router"', false)
            ->assertSee('name="item_qty[]" class="form-control form-control-sm border-0 bg-light item-qty" value="3"', false)
            ->assertSee('value="'.$customer->id.'" data-tax-code=', false);

        $this->actingAs($admin)
            ->post(route('documents.store'), [
                'source_po_id' => $purchaseOrder->id,
                'customer_id' => $customer->id,
                'type' => 'INVOICE',
                'document_number' => '001/INV-FROM-PO/XI/2026',
                'price_mode' => 'exclusive',
                'item_desc' => ['Wireless router'],
                'item_type' => ['A'],
                'item_qty' => [3],
                'item_unit' => ['Box'],
                'item_price' => [125000],
            ])
            ->assertRedirect(route('documents.index'))
            ->assertSessionHas('success', 'Dokumen berhasil dibuat');

        $invoice = Document::where('document_number', '001/INV-FROM-PO/XI/2026')->firstOrFail();
        $this->assertSame($purchaseOrder->id, $invoice->source_po_id);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertDatabaseHas('document_items', [
            'document_id' => $invoice->id,
            'description' => 'Wireless router',
            'qty' => 3,
            'unit' => 'Box',
            'price' => 125000,
            'subtotal' => 375000,
        ]);

        $this->actingAs($admin)
            ->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee(route('documents.show', $invoice), false);

        $this->actingAs($admin)
            ->post(route('documents.store'), [
                'source_po_id' => $purchaseOrder->id,
                'customer_id' => $customer->id,
                'type' => 'INVOICE',
                'document_number' => '002/INV-FROM-PO/XI/2026',
                'price_mode' => 'exclusive',
                'item_desc' => ['Wireless router'],
                'item_type' => ['A'],
                'item_qty' => [3],
                'item_unit' => ['Box'],
                'item_price' => [125000],
            ])
            ->assertSessionHasErrors('source_po_id');
        $this->assertDatabaseCount('documents', 2);

        $this->actingAs($admin)
            ->get(route('purchase-orders.create-invoice', $purchaseOrder))
            ->assertRedirect(route('documents.show', $invoice))
            ->assertSessionHas('success', 'PO ini sudah memiliki Invoice.');
    }
}

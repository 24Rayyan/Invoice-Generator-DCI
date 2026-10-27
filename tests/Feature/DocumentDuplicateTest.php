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

class DocumentDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_link_opens_the_duplicate_document_form(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::create([
            'username' => 'document_admin',
            'name' => 'Document Admin',
            'email' => 'document@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'PT Dokumen Sukses',
        ]);

        $document = Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => '001/INV-DCI/X/2026',
            'date' => '2026-10-01',
            'status' => 'DRAFT',
            'total_amount' => 100000,
            'discount' => 5000,
        ]);

        DocumentItem::create([
            'document_id' => $document->id,
            'description' => 'Layanan konsultasi',
            'item_type' => 'B',
            'qty' => 2,
            'unit' => 'Hari',
            'price' => 50000,
            'subtotal' => 100000,
        ]);

        $this->actingAs($user)
            ->get(route('documents.duplicate', $document))
            ->assertOk()
            ->assertViewIs('documents.create_duplicate')
            ->assertViewHas('sourceDoc', fn (Document $sourceDoc) => $sourceDoc->is($document))
            ->assertDontSee('name="project_id"', false)
            ->assertSee('value="B" selected', false)
            ->assertSee('id="calculation-preview"', false)
            ->assertSee('value="5000"', false)
            ->assertSee('Total DPP sebelum diskon')
            ->assertSee('DPP setelah diskon')
            ->assertSee('Total (DPP + PPN)')
            ->assertSee('data-invoice-number="001/INV/', false)
            ->assertSee('data-po-number="001/PO/', false);
    }

    public function test_duplicate_form_hides_invoice_fields_for_purchase_orders(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::create([
            'username' => 'po_duplicate_admin',
            'name' => 'PO Duplicate Admin',
            'email' => 'po-duplicate@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'PO Customer']);
        $document = Document::create([
            'customer_id' => $customer->id,
            'type' => 'PO',
            'document_number' => '001/PO-ORIGINAL/X/2026',
            'date' => '2026-10-01',
            'status' => 'DRAFT',
            'total_amount' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('documents.duplicate', $document))
            ->assertOk()
            ->assertSee('class="col-md-3 invoice-only d-none"', false)
            ->assertSee('name="tax_transaction_code" id="tax_transaction_code" class="form-select fw-semibold" disabled', false)
            ->assertSee('class="col-12 invoice-only d-none"', false)
            ->assertSee('name="payment_note"', false);
    }

    public function test_document_index_filters_invoice_and_purchase_order_lists_separately(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::create([
            'username' => 'document_filter_admin',
            'name' => 'Document Filter Admin',
            'email' => 'document-filter@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'Filter Customer']);
        Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-FILTER-001',
            'date' => now()->toDateString(),
            'status' => 'DRAFT',
            'total_amount' => 100,
        ]);
        Document::create([
            'customer_id' => $customer->id,
            'type' => 'PO',
            'document_number' => 'PO-FILTER-001',
            'date' => now()->toDateString(),
            'status' => 'DRAFT',
            'total_amount' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee('PO-FILTER-001')
            ->assertDontSee('INV-FILTER-001')
            ->assertSee('Kelola Purchase Order (PO)');

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('INV-FILTER-001')
            ->assertDontSee('PO-FILTER-001');
    }
}

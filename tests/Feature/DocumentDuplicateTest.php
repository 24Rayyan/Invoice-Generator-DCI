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
            ->assertSee('id="calculation-preview"', false)
            ->assertSee('value="5000"', false)
            ->assertSee('Total DPP sebelum diskon')
            ->assertSee('DPP setelah diskon')
            ->assertSee('Total (DPP + PPN)');
    }
}

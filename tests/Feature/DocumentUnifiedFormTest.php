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

class DocumentUnifiedFormTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'username' => 'form_admin',
            'name' => 'Form Admin',
            'email' => 'form@example.test',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    private function invoice(string $status = 'DRAFT'): Document
    {
        $customer = Customer::create(['name' => 'PT Form Terpadu']);
        $document = Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => '007/INV-DCI/X/2026',
            'date' => '2026-10-01',
            'status' => $status,
            'total_amount' => 100000,
            'discount' => 0,
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

        return $document;
    }

    public function test_create_form_renders_with_readonly_number_and_static_type(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertViewIs('documents.create')
            ->assertSee('id="document-form"', false)
            ->assertSee('Simpan Dokumen')
            ->assertSee('id="item-row-template"', false)
            ->assertSee('readonly', false)
            ->assertSee('name="type" id="document_type" value="INVOICE"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="project_id"', false);

        $this->actingAs($admin)
            ->get(route('documents.create', ['type' => 'PO']))
            ->assertOk()
            ->assertSee('name="type" id="document_type" value="PO"', false)
            ->assertSee('class="col-md-3 invoice-only d-none"', false);
    }

    public function test_document_number_input_is_readonly_in_every_mode(): void
    {
        $admin = $this->admin();
        $document = $this->invoice();

        foreach ([
            route('documents.create'),
            route('documents.duplicate', $document),
            route('documents.edit', $document),
        ] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            preg_match('/<input[^>]*id="document_number"[^>]*>/s', $html, $m);
            $this->assertNotEmpty($m, "Input nomor dokumen tidak ditemukan di {$url}");
            $this->assertStringContainsString('readonly', $m[0], "Nomor dokumen harus read-only di {$url}");
        }
    }

    public function test_edit_form_prefills_document_status_and_items(): void
    {
        $document = $this->invoice('SENT');

        $this->actingAs($this->admin())
            ->get(route('documents.edit', $document))
            ->assertOk()
            ->assertViewIs('documents.edit')
            ->assertSee('Perbarui Dokumen')
            ->assertSee('007/INV-DCI/X/2026')
            ->assertSee('name="status"', false)
            ->assertSee('value="SENT" selected', false)
            ->assertSee('value="B" selected', false)
            ->assertSee('value="Layanan konsultasi"', false)
            ->assertSee('name="tax_invoice_date"', false)
            ->assertSee('name="project_id"', false);
    }

    public function test_duplicate_form_uses_same_partial_and_prefills_items(): void
    {
        $document = $this->invoice();

        $this->actingAs($this->admin())
            ->get(route('documents.duplicate', $document))
            ->assertOk()
            ->assertViewIs('documents.create_duplicate')
            ->assertSee('Simpan Salinan Dokumen Baru')
            ->assertSee('Menyalin rincian barang dari dokumen referensi:')
            ->assertSee('value="Layanan konsultasi"', false)
            ->assertSee('data-invoice-number="001/INV/', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_failed_validation_keeps_entered_item_rows(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('documents.create'))
            ->post(route('documents.store'), [
                'type' => 'INVOICE',
                'document_number' => '',
                'item_desc' => ['Barang A', 'Barang B'],
                'item_qty' => [1, 3],
                'item_unit' => ['Pcs', 'Box'],
                'item_price' => [1000, 2000],
                'item_type' => ['A', 'B'],
            ])
            ->assertSessionHasErrors();

        $this->actingAs($admin)
            ->withSession(['_old_input' => [
                'type' => 'INVOICE',
                'item_desc' => ['Barang A', 'Barang B'],
                'item_qty' => [1, 3],
                'item_unit' => ['Pcs', 'Box'],
                'item_price' => [1000, 2000],
                'item_type' => ['A', 'B'],
            ]])
            ->get(route('documents.create'))
            ->assertOk()
            ->assertSee('value="Barang A"', false)
            ->assertSee('value="Barang B"', false)
            ->assertSee('value="Box"', false);
    }

    public function test_document_list_exposes_confirmation_and_download_hooks(): void
    {
        $document = $this->invoice('PAID');

        $this->actingAs($this->admin())
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('data-current="PAID"', false)
            ->assertSee('data-docnum="007/INV-DCI/X/2026"', false)
            ->assertSee('data-confirm-title="Hapus Invoice?"', false)
            ->assertSee('Transaksi pemasukan kas hasil pelunasan ikut dihapus')
            ->assertSee('data-download', false)
            ->assertSee('window.ErpUI', false);
    }

    public function test_native_browser_confirm_is_no_longer_used(): void
    {
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($views as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertStringNotContainsString(
                    'return confirm(',
                    file_get_contents($file->getPathname()),
                    $file->getFilename().' masih memakai confirm() bawaan browser'
                );
            }
        }
    }
}

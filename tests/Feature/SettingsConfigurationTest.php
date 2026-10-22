<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $this->admin = User::create([
            'username' => 'settings_admin',
            'name' => 'Settings Admin',
            'email' => 'settings-admin@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_settings_page_uses_npwp16_and_omits_removed_company_fields(): void
    {
        $setting = Setting::getSetting();

        $this->assertNull($setting->company_name);
        $this->assertNull($setting->company_city);
        $this->assertNull($setting->company_bank_account);
        $this->assertSame('INV', $setting->invoice_prefix);
        $this->assertNull($setting->signatory_name);
        $this->assertNull($setting->signatory_position);
        $this->assertNull($setting->signatory_city);
        $this->assertTrue($setting->stamp_invoice_enabled);
        $this->assertTrue($setting->stamp_delivery_note_enabled);
        $this->assertFalse($setting->stamp_receipt_enabled);

        $this->actingAs($this->admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('NPWP 16 Digit Penjual')
            ->assertSee('Stempel Perusahaan')
            ->assertSee('placeholder="Contoh: PT Sinar Abadi"', false)
            ->assertSee('Bank: [Nama Bank]')
            ->assertSee('placeholder="Nama lengkap pejabat"', false)
            ->assertSee('name="invoice_prefix"', false)
            ->assertSee('placeholder="INV"', false)
            ->assertDontSee('name="company_tagline"', false)
            ->assertDontSee('name="company_website"', false);
    }

    public function test_settings_update_saves_stamp_upload_and_per_document_toggles(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'company_name' => 'PT DCI',
                'company_npwp16' => '0123456789012345',
                'invoice_prefix' => 'INV-TEST',
                'po_prefix' => 'PO-TEST',
                'delivery_note_prefix' => 'SJ-TEST',
                'kwitansi_prefix' => 'KWT-TEST',
                'default_tax_rate' => 11,
                'company_stamp' => UploadedFile::fake()->image('stamp.png'),
                'stamp_delivery_note_enabled' => '1',
                'stamp_receipt_enabled' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = Setting::firstOrFail();
        $this->assertSame('0123456789012345', $setting->company_npwp16);
        $this->assertFalse($setting->stamp_invoice_enabled);
        $this->assertTrue($setting->stamp_delivery_note_enabled);
        $this->assertTrue($setting->stamp_receipt_enabled);
        Storage::disk('public')->assertExists($setting->company_stamp);
    }

    public function test_invoice_creation_generates_delivery_note_and_receipt_numbers_and_uses_receipt_note_default(): void
    {
        $setting = Setting::getSetting();
        $setting->update([
            'invoice_prefix' => 'INV-TEST',
            'po_prefix' => 'PO-TEST',
            'delivery_note_prefix' => 'SJ-TEST',
            'kwitansi_prefix' => 'KWT-TEST',
            'receipt_footer_notes' => 'Configured receipt note',
        ]);
        $customer = Customer::create(['name' => 'Customer Test']);
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];
        $periodSuffix = $romanMonths[now()->month].'/'.now()->year;

        $this->actingAs($this->admin)
            ->post(route('documents.store'), [
                'customer_id' => $customer->id,
                'type' => 'INVOICE',
                'document_number' => '001/INV-TEST/'.$periodSuffix,
                'item_desc' => ['Service'],
                'item_qty' => [1],
                'item_unit' => ['Pcs'],
                'item_price' => [1000],
            ])
            ->assertRedirect(route('documents.index'))
            ->assertSessionHas('success');

        $document = Document::firstOrFail();
        $this->assertSame('001/SJ-TEST/'.$periodSuffix, $document->delivery_note_number);
        $this->assertSame('001/KWT-TEST/'.$periodSuffix, $document->receipt_number);
        $this->assertSame('Configured receipt note', $document->payment_note);

        $this->actingAs($this->admin)
            ->get(route('documents.delivery_note', $document))
            ->assertOk()
            ->assertDownload('SJ_001-SJ-TEST-'.str_replace('/', '-', $periodSuffix).'.pdf');
        $this->actingAs($this->admin)
            ->get(route('documents.kwitansi', $document))
            ->assertOk()
            ->assertDownload('KWITANSI_001-KWT-TEST-'.str_replace('/', '-', $periodSuffix).'.pdf');

        $this->actingAs($this->admin)
            ->post(route('documents.store'), [
                'customer_id' => $customer->id,
                'type' => 'INVOICE',
                'document_number' => '002/INV-TEST/'.$periodSuffix,
                'item_desc' => ['Service'],
                'item_qty' => [1],
                'item_unit' => ['Pcs'],
                'item_price' => [1000],
            ])
            ->assertRedirect(route('documents.index'));

        $this->assertSame('002/SJ-TEST/'.$periodSuffix, Document::orderByDesc('id')->firstOrFail()->delivery_note_number);
        $this->assertSame('002/KWT-TEST/'.$periodSuffix, Document::orderByDesc('id')->firstOrFail()->receipt_number);
    }

    public function test_stamp_is_rendered_only_in_company_signature_areas_when_enabled(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/stamp.png', 'test-image-content');

        $setting = Setting::getSetting();
        $setting->update([
            'company_stamp' => 'settings/stamp.png',
            'stamp_invoice_enabled' => true,
            'stamp_delivery_note_enabled' => true,
            'stamp_receipt_enabled' => false,
        ]);

        $customer = Customer::create(['name' => 'Customer Test']);
        $document = Document::create([
            'customer_id' => $customer->id,
            'type' => 'INVOICE',
            'document_number' => '001/INV-TEST/X/2026',
            'delivery_note_number' => '001/SJ-TEST/X/2026',
            'receipt_number' => '001/KWT-TEST/X/2026',
            'date' => Carbon::today()->toDateString(),
            'status' => 'SENT',
            'total_amount' => 1000,
        ])->load(['customer', 'items']);

        $invoiceHtml = view('exports.invoice_pdf', compact('document', 'setting'))->render();
        $deliveryNoteHtml = view('exports.delivery_note_pdf', compact('document', 'setting'))->render();
        $receiptHtml = view('exports.kwitansi_pdf', compact('document', 'setting'))->render();

        $this->assertStringContainsString('class="company-stamp"', $invoiceHtml);
        $this->assertStringContainsString('class="company-stamp"', $deliveryNoteHtml);
        $this->assertStringNotContainsString('class="company-stamp"', $receiptHtml);
        $this->assertStringContainsString($document->delivery_note_number, $deliveryNoteHtml);
        $this->assertStringContainsString($document->receipt_number, $receiptHtml);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Project;
use App\Models\Transaction;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_populates_linked_financial_data_idempotently(): void
    {
        $this->seed(DemoDataSeeder::class);

        $initialCounts = [
            'categories' => Category::count(),
            'customers' => Customer::count(),
            'projects' => Project::count(),
            'documents' => Document::count(),
            'items' => DocumentItem::count(),
            'transactions' => Transaction::count(),
        ];

        $this->assertGreaterThanOrEqual(18, $initialCounts['categories']);
        $this->assertSame(6, $initialCounts['customers']);
        $this->assertSame(4, $initialCounts['projects']);
        $this->assertSame(10, $initialCounts['documents']);
        $this->assertGreaterThan(10, $initialCounts['items']);
        $this->assertGreaterThan(10, $initialCounts['transactions']);

        $projectInvoice = Document::where('document_number', 'INV-DEMO/001/2026')->firstOrFail();
        $this->assertNotNull($projectInvoice->project_id);
        $this->assertSame(
            (float) $projectInvoice->total_amount,
            (float) $projectInvoice->items()->sum('subtotal')
        );
        $this->assertTrue($projectInvoice->transactions()->where('type', 'income')->exists());
        $this->assertTrue($projectInvoice->directExpenses()->exists());

        $this->assertDatabaseHas('documents', [
            'document_number' => 'INV-DEMO/008/2026',
            'project_id' => null,
        ]);
        $this->assertDatabaseHas('transactions', [
            'transaction_number' => 'DEMO-CASH-OUT-0007',
            'project_id' => null,
            'invoice_id' => null,
        ]);

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($initialCounts, [
            'categories' => Category::count(),
            'customers' => Customer::count(),
            'projects' => Project::count(),
            'documents' => Document::count(),
            'items' => DocumentItem::count(),
            'transactions' => Transaction::count(),
        ]);
    }
}

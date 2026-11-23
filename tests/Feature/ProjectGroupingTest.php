<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProjectMarginService;
use App\Services\TransactionService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $this->admin = User::create([
            'username' => 'project_group_admin',
            'name' => 'Project Group Admin',
            'email' => 'project-group-admin@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'name' => 'Project Client Ltd',
            'email' => 'client@example.test',
        ]);
    }

    private function makeProject(string $name = 'ERP Rollout'): Project
    {
        return Project::create([
            'project_name' => $name,
            'client_id' => $this->customer->id,
            'status' => 'active',
        ]);
    }

    private function makeInvoice(Project $project, string $number = 'INV-PROJECT/001'): Document
    {
        return Document::create([
            'customer_id' => $this->customer->id,
            'project_id' => $project->id,
            'type' => 'INVOICE',
            'document_number' => $number,
            'date' => now()->toDateString(),
            'status' => 'SENT',
            'total_amount' => 2500000,
        ]);
    }

    private function makeExpense(?int $projectId, float $amount): Transaction
    {
        $transaction = Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber('expense'),
            'type' => 'expense',
            'category_id' => Category::where('type', 'expense')->firstOrFail()->id,
            'amount' => $amount,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'Bank Transfer',
            'description' => 'Project implementation cost',
        ]);

        if ($projectId) {
            $transaction->projects()->attach($projectId, ['allocated_amount' => $amount]);
        }

        return $transaction;
    }

    public function test_projects_are_assigned_to_invoices_after_document_creation(): void
    {
        $project = $this->makeProject();

        $this->actingAs($this->admin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Manajemen Project')
            ->assertSee('ERP Rollout');

        $this->actingAs($this->admin)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertDontSee('name="project_id"', false)
            ->assertDontSee('ERP Rollout');

        $documentInput = [
            'customer_id' => $this->customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-PROJECT/FORM',
            'item_desc' => ['Implementation milestone'],
            'item_type' => ['B'],
            'item_qty' => [1],
            'item_unit' => ['Lot'],
            'item_price' => [2000000],
        ];

        $this->actingAs($this->admin)
            ->post(route('documents.store'), $documentInput + ['project_id' => $project->id])
            ->assertSessionHasErrors(['project_id']);

        $this->assertDatabaseMissing('documents', ['document_number' => 'INV-PROJECT/FORM']);

        $this->actingAs($this->admin)
            ->post(route('documents.store'), $documentInput)
            ->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('documents', [
            'document_number' => 'INV-PROJECT/FORM',
            'project_id' => null,
        ]);

        $invoice = Document::where('document_number', 'INV-PROJECT/FORM')->firstOrFail();
        $this->actingAs($this->admin)
            ->post(route('projects.attach-invoices', $project), [
                'invoice_ids' => [$invoice->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($project->id, $invoice->fresh()->project_id);
        $invoice->update(['status' => 'PAID']);
        app(TransactionService::class)->syncInvoicePaidTransaction($invoice->load('customer'));
        $this->assertDatabaseHas('transactions', [
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
            'type' => 'income',
        ]);
    }

    public function test_cash_creation_and_edit_forms_leave_project_cost_allocation_to_project_margin(): void
    {
        $project = $this->makeProject();
        $category = Category::where('type', 'expense')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('transactions.create', ['type' => 'expense']))
            ->assertOk()
            ->assertDontSee('Relasikan ke Project')
            ->assertDontSee('Relasikan ke Invoice')
            ->assertDontSee('ERP Rollout');

        $this->actingAs($this->admin)
            ->post(route('transactions.store', ['type' => 'expense']), [
                'type' => 'expense',
                'category_id' => $category->id,
                'project_id' => $project->id,
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Rejected project assignment',
            ])
            ->assertSessionHasErrors('project_id');

        $this->actingAs($this->admin)
            ->post(route('transactions.store', ['type' => 'expense']), [
                'type' => 'expense',
                'category_id' => $category->id,
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Implementation cost',
            ])
            ->assertRedirect(route('transactions.index'));

        $transaction = Transaction::where('description', 'Implementation cost')->firstOrFail();
        $this->assertNull($transaction->project_id);

        $this->actingAs($this->admin)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertDontSee('Relasikan ke Project')
            ->assertDontSee('name="project_id"', false);

        $this->actingAs($this->admin)
            ->put(route('transactions.update', $transaction), [
                'category_id' => $category->id,
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Implementation cost',
            ])
            ->assertRedirect(route('transactions.index'));

        $this->assertNull($transaction->fresh()->project_id);
        $this->actingAs($this->admin)
            ->put(route('transactions.update', $transaction), [
                'category_id' => $category->id,
                'project_id' => $project->id,
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Implementation cost',
            ])
            ->assertSessionHasErrors('project_id');

        $this->actingAs($this->admin)
            ->post(route('projects.attach-expenses', $project), [
                'transaction_ids' => [$transaction->id],
                'allocations' => [$transaction->id => 250000],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(250000, (float) $transaction->fresh()->projects()->first()->pivot->allocated_amount);
    }

    public function test_project_detail_margin_attach_and_detach_and_report_are_consistent(): void
    {
        $project = $this->makeProject();
        $invoice = $this->makeInvoice($project);
        $linkedExpense = $this->makeExpense($project->id, 500000);
        $unallocatedExpense = $this->makeExpense(null, 250000);
        $invoiceLinkedExpense = $this->makeExpense(null, 150000);
        $invoiceLinkedExpense->update(['invoice_id' => $invoice->id]);
        $otherProject = $this->makeProject('Other project');
        $alreadyLinkedExpense = $this->makeExpense(null, 300000);
        $fullAllocationExpense = $this->makeExpense(null, 400000);

        $this->actingAs($this->admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Total Revenue')
            ->assertSee('2.500.000')
            ->assertSee('500.000')
            ->assertSee($invoice->document_number)
            ->assertSee('Alokasikan Kas')
            ->assertSee('project-attach-modal-content', false)
            ->assertSee('projectExpenseSearch')
            ->assertSee('projectExpenseDateFrom')
            ->assertSee('projectExpenseDateTo')
            ->assertSee('data-project-expense-search', false)
            ->assertSee('Sudah dialokasikan')
            ->assertSee('Alokasi proyek ini')
            ->assertSee('Hubungkan Invoice')
            ->assertSee('projectInvoiceSearch')
            ->assertSee('projectInvoiceDateFrom')
            ->assertSee('projectInvoiceDateTo')
            ->assertSee($invoiceLinkedExpense->transaction_number);

        $this->actingAs($this->admin)
            ->post(route('projects.attach-expenses', $project), [
                'transaction_ids' => [$unallocatedExpense->id, $alreadyLinkedExpense->id, $invoiceLinkedExpense->id],
                'allocations' => [
                    $unallocatedExpense->id => 250000,
                    $alreadyLinkedExpense->id => 150000,
                    $invoiceLinkedExpense->id => 50000,
                ],
            ])
            ->assertSessionHas('error');

        $this->assertFalse($unallocatedExpense->fresh()->projects()->exists());
        $this->assertFalse($alreadyLinkedExpense->fresh()->projects()->exists());
        $this->assertNull($invoiceLinkedExpense->fresh()->project_id);
        $this->assertSame($invoice->id, $invoiceLinkedExpense->fresh()->invoice_id);

        $this->actingAs($this->admin)
            ->post(route('projects.attach-expenses', $project), [
                'transaction_ids' => [$unallocatedExpense->id, $alreadyLinkedExpense->id, $fullAllocationExpense->id],
                'allocations' => [
                    $unallocatedExpense->id => 250000,
                    $alreadyLinkedExpense->id => 150000,
                    $fullAllocationExpense->id => '',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(250000, (float) $unallocatedExpense->fresh()->projects()->whereKey($project->id)->first()->pivot->allocated_amount);
        $this->assertSame(150000, (float) $alreadyLinkedExpense->fresh()->projects()->whereKey($project->id)->first()->pivot->allocated_amount);
        $this->assertSame(400000, (float) $fullAllocationExpense->fresh()->projects()->whereKey($project->id)->first()->pivot->allocated_amount);

        $this->actingAs($this->admin)
            ->post(route('projects.attach-expenses', $project), [
                'transaction_ids' => [$alreadyLinkedExpense->id],
                'allocations' => [$alreadyLinkedExpense->id => 200000],
            ])
            ->assertSessionHasErrors("allocations.{$alreadyLinkedExpense->id}");

        $this->actingAs($this->admin)
            ->post(route('projects.attach-expenses', $otherProject), [
                'transaction_ids' => [$alreadyLinkedExpense->id],
                'allocations' => [$alreadyLinkedExpense->id => ''],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(150000, (float) $alreadyLinkedExpense->fresh()->projects()->whereKey($otherProject->id)->first()->pivot->allocated_amount);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_ATTACH,
            'description' => 'User project_group_admin menghubungkan 3 transaksi kas ke project "ERP Rollout".',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.projects', ['search' => 'ERP Rollout', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Project Margin Overview')
            ->assertSee('ERP Rollout')
            ->assertSee($this->customer->name)
            ->assertSee('1.450.000');

        $this->actingAs($this->admin)
            ->post(route('projects.detach-expense', [$project, $linkedExpense]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($linkedExpense->fresh()->projects()->whereKey($project->id)->exists());
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_DETACH,
            'description' => 'User project_group_admin melepas transaksi kas "'.$linkedExpense->transaction_number.'" dari project "ERP Rollout".',
        ]);
    }

    public function test_existing_unassigned_invoices_can_be_bulk_attached_to_a_project(): void
    {
        $project = $this->makeProject();
        $unassignedInvoiceA = Document::create([
            'customer_id' => $this->customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-UNASSIGNED/A',
            'date' => now()->subDays(3)->toDateString(),
            'status' => 'SENT',
            'total_amount' => 1000000,
        ]);
        $unassignedInvoiceB = Document::create([
            'customer_id' => $this->customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-UNASSIGNED/B',
            'date' => now()->subDay()->toDateString(),
            'status' => 'DRAFT',
            'total_amount' => 2000000,
        ]);
        $invoiceAttachedElsewhere = $this->makeInvoice($this->makeProject('Other group'), 'INV-ATTACHED/OTHER');

        $this->actingAs($this->admin)
            ->post(route('projects.attach-invoices', $project), [
                'invoice_ids' => [$unassignedInvoiceA->id, $unassignedInvoiceB->id, $invoiceAttachedElsewhere->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($project->id, $unassignedInvoiceA->fresh()->project_id);
        $this->assertSame($project->id, $unassignedInvoiceB->fresh()->project_id);
        $this->assertNotSame($project->id, $invoiceAttachedElsewhere->fresh()->project_id);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_ATTACH,
            'description' => 'User project_group_admin menghubungkan 2 invoice ke project "ERP Rollout".',
        ]);

        $this->actingAs($this->admin)
            ->post(route('projects.detach-invoice', [$project, $unassignedInvoiceA]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNull($unassignedInvoiceA->fresh()->project_id);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_DETACH,
            'description' => 'User project_group_admin melepas invoice "'.$unassignedInvoiceA->document_number.'" dari project "ERP Rollout".',
        ]);
    }

    public function test_project_margin_report_groups_multiple_invoices_and_excludes_unassigned_invoices(): void
    {
        $project = $this->makeProject('Grouped milestone project');
        $this->makeInvoice($project, 'INV-GROUPED/ONE');
        $this->makeInvoice($project, 'INV-GROUPED/TWO')->update(['total_amount' => 1500000]);
        $this->makeExpense($project->id, 400000);
        $unassignedInvoice = Document::create([
            'customer_id' => $this->customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-OUTSIDE/GROUP',
            'date' => now()->toDateString(),
            'status' => 'SENT',
            'total_amount' => 9000000,
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.project_margin'))
            ->assertOk()
            ->assertSee('Grouped milestone project')
            ->assertSee('4.000.000')
            ->assertSee('400.000')
            ->assertSee('3.600.000')
            ->assertDontSee('INV-GROUPED/ONE')
            ->assertDontSee('INV-GROUPED/TWO')
            ->assertSee($unassignedInvoice->document_number)
            ->assertSee('9.000.000')
            ->assertSee('13.000.000');

        $this->actingAs($this->admin)
            ->get(route('reports.projects'))
            ->assertOk()
            ->assertSee('Grouped milestone project')
            ->assertSee($unassignedInvoice->document_number)
            ->assertSee(route('projects.index', ['invoice_id' => $unassignedInvoice->id]))
            ->assertSee('13.000.000');
    }

    public function test_invoice_expenses_follow_an_invoice_when_it_is_attached_to_a_project(): void
    {
        $project = $this->makeProject('Project with prior invoice costs');
        $invoice = Document::create([
            'customer_id' => $this->customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-PRIOR-COST/001',
            'date' => now()->toDateString(),
            'status' => 'SENT',
            'total_amount' => 2500000,
        ]);
        $expense = $this->makeExpense(null, 350000);
        $expense->projects()->attach($project->id, ['allocated_amount' => 350000]);
        $expense->update(['invoice_id' => $invoice->id]);
        $sharedProjectExpense = $this->makeExpense(null, 500000);
        $sharedProjectExpense->projects()->attach($project->id, ['allocated_amount' => 500000]);

        $this->actingAs($this->admin)
            ->post(route('projects.attach-invoices', $project), [
                'invoice_ids' => [$invoice->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $projectRow = app(ProjectMarginService::class)
            ->projectOverview()
            ->firstWhere('type', 'project');
        $this->assertSame($project->id, $projectRow['project']->id);
        $this->assertSame(850000.0, $projectRow['direct_expense']);
        $this->assertSame(1650000.0, $projectRow['net_profit']);

        $this->actingAs($this->admin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Nominal Keuntungan')
            ->assertSee('850.000')
            ->assertSee('1.650.000');

        $response = $this->actingAs($this->admin)
            ->get(route('projects.show', $project))
            ->assertOk();
        $response
            ->assertSee('Alokasi Kas Keluar')
            ->assertDontSee('Kas Terhubung melalui Invoice')
            ->assertSee($expense->transaction_number)
            ->assertSee('Invoice: '.$invoice->document_number)
            ->assertSee('Ringkasan keuangan proyek')
            ->assertSee('850.000,00')
            ->assertSee('350.000,00');
        $this->assertSame(1, substr_count($response->getContent(), $expense->transaction_number));

        $this->actingAs($this->admin)
            ->get(route('documents.show', $invoice))
            ->assertOk()
            ->assertSee('aria-label="Ringkasan keuangan proyek"', false)
            ->assertSee('Total alokasi kas dan pengeluaran invoice proyek.')
            ->assertSee('850.000')
            ->assertSee('1.650.000');
    }

    public function test_project_creation_can_link_existing_invoice_and_carry_its_attached_expenses(): void
    {
        $invoice = Document::create([
            'customer_id' => $this->customer->id,
            'type' => 'INVOICE',
            'document_number' => 'INV-NEW-PROJECT/001',
            'date' => now()->toDateString(),
            'status' => 'SENT',
            'total_amount' => 2500000,
        ]);
        $expense = $this->makeExpense(null, 350000);
        $expense->update(['invoice_id' => $invoice->id]);

        $this->actingAs($this->admin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee($invoice->document_number)
            ->assertSee('Kas tertaut')
            ->assertSee('850.000,00');

        $this->actingAs($this->admin)
            ->get(route('projects.index', ['invoice_id' => $invoice->id]))
            ->assertOk()
            ->assertSee('value="'.$invoice->id.'"', false)
            ->assertSee('value="'.$this->customer->id.'" selected', false);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), [
                'project_name' => 'Project created from invoice',
                'client_id' => $this->customer->id,
                'status' => 'active',
                'invoice_ids' => [$invoice->id],
            ])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'Project berhasil dibuat dan 1 invoice langsung dihubungkan.');

        $project = Project::where('project_name', 'Project created from invoice')->firstOrFail();
        $this->assertSame($project->id, $invoice->fresh()->project_id);

        $projectRow = app(ProjectMarginService::class)
            ->projectOverview()
            ->first(fn ($row) => $row['type'] === 'project' && $row['project']->id === $project->id);
        $this->assertSame(2500000.0, $projectRow['revenue']);
        $this->assertSame(350000.0, $projectRow['direct_expense']);

        $this->actingAs($this->admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee($invoice->document_number)
            ->assertSee($expense->transaction_number)
            ->assertSee('2.500.000')
            ->assertSee('350.000,00');
    }

    public function test_project_expenses_can_be_allocated_from_the_margin_report(): void
    {
        $project = $this->makeProject('Report allocation project');
        $expense = $this->makeExpense(null, 900000);
        $attachUrl = route('reports.projects.attach-expenses', $project);

        $this->actingAs($this->admin)
            ->get(route('reports.projects'))
            ->assertOk()
            ->assertSee('Alokasikan Kas')
            ->assertSee('data-submit-url="'.$attachUrl.'"', false);

        $this->actingAs($this->admin)
            ->post($attachUrl, [
                'report_project_id' => $project->id,
                'transaction_ids' => [$expense->id],
                'allocations' => [$expense->id => 400000],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(400000.0, (float) $expense->fresh()->projects()->whereKey($project->id)->first()->pivot->allocated_amount);
        $projectRow = app(ProjectMarginService::class)
            ->projectOverview()
            ->firstWhere('type', 'project');
        $this->assertSame(400000.0, $projectRow['direct_expense']);
    }

    public function test_project_and_client_deletion_nulls_foreign_keys(): void
    {
        $project = $this->makeProject();
        $invoice = $this->makeInvoice($project, 'INV-PROJECT/DELETE');
        $expense = $this->makeExpense($project->id, 100000);

        $project->delete();

        $this->assertNull($invoice->fresh()->project_id);
        $this->assertNull($expense->fresh()->project_id);

        $project = $this->makeProject('Client relation test');
        $customerId = $this->customer->id;
        $this->customer->delete();
        $this->assertNull($project->fresh()->client_id);
        $this->assertDatabaseMissing('customers', ['id' => $customerId]);
        $this->assertDatabaseMissing('documents', ['id' => $invoice->id]);
    }

    public function test_project_management_exposes_edit_and_delete_actions_and_deletes_without_deleting_related_records(): void
    {
        $project = $this->makeProject();
        $invoice = $this->makeInvoice($project, 'INV-PROJECT/DELETE-ACTION');
        $expense = $this->makeExpense($project->id, 125000);

        $this->actingAs($this->admin)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee(route('projects.show', $project).'#project-settings')
            ->assertSee(route('projects.destroy', $project))
            ->assertSee('project-row-actions', false)
            ->assertSee('btn-with-label', false);

        $this->actingAs($this->admin)
            ->put(route('projects.update', $project), [
                'project_name' => 'Updated ERP Rollout',
                'client_id' => $this->customer->id,
                'status' => 'completed',
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'project_name' => 'Updated ERP Rollout',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_CREATE,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_UPDATE,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_DELETE,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
        $this->assertDatabaseHas('documents', ['id' => $invoice->id, 'project_id' => null]);
        $this->assertDatabaseHas('transactions', ['id' => $expense->id, 'project_id' => null]);
    }
}

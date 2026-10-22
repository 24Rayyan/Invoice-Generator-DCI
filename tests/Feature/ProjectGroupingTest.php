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
        return Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber('expense'),
            'type' => 'expense',
            'category_id' => Category::where('type', 'expense')->firstOrFail()->id,
            'project_id' => $projectId,
            'amount' => $amount,
            'transaction_date' => now()->toDateString(),
            'payment_method' => 'Bank Transfer',
            'description' => 'Project implementation cost',
        ]);
    }

    public function test_projects_can_be_created_and_assigned_to_invoices(): void
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

        $this->actingAs($this->admin)
            ->post(route('documents.store'), [
                'customer_id' => $this->customer->id,
                'project_id' => $project->id,
                'type' => 'INVOICE',
                'document_number' => 'INV-PROJECT/FORM',
                'item_desc' => ['Implementation milestone'],
                'item_qty' => [1],
                'item_unit' => ['Lot'],
                'item_price' => [2000000],
            ])
            ->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('documents', [
            'document_number' => 'INV-PROJECT/FORM',
            'project_id' => $project->id,
        ]);

        $invoice = Document::where('document_number', 'INV-PROJECT/FORM')->firstOrFail();
        $invoice->update(['status' => 'PAID']);
        app(TransactionService::class)->syncInvoicePaidTransaction($invoice->load('customer'));
        $this->assertDatabaseHas('transactions', [
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
            'type' => 'income',
        ]);
    }

    public function test_cash_form_creates_and_updates_optional_project_assignment(): void
    {
        $project = $this->makeProject();
        $category = Category::where('type', 'expense')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('transactions.create', ['type' => 'expense']))
            ->assertOk()
            ->assertSee('Relasikan ke Project')
            ->assertSee('ERP Rollout');

        $this->actingAs($this->admin)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'category_id' => $category->id,
                'project_id' => $project->id,
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Implementation cost',
            ])
            ->assertRedirect(route('transactions.index'));

        $transaction = Transaction::where('description', 'Implementation cost')->firstOrFail();
        $this->assertSame($project->id, $transaction->project_id);

        $otherProject = $this->makeProject('Another project');
        $this->actingAs($this->admin)
            ->put(route('transactions.update', $transaction), [
                'category_id' => $category->id,
                'project_id' => $otherProject->id,
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Implementation cost',
            ])
            ->assertSessionHasErrors('invoice_id');

        $this->assertSame($project->id, $transaction->fresh()->project_id);

        $this->actingAs($this->admin)
            ->put(route('transactions.update', $transaction), [
                'category_id' => $category->id,
                'project_id' => '',
                'amount' => 400000,
                'transaction_date' => now()->toDateString(),
                'payment_method' => 'Cash',
                'description' => 'Implementation cost',
            ])
            ->assertRedirect(route('transactions.index'));

        $this->assertNull($transaction->fresh()->project_id);
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
        $alreadyLinkedExpense = $this->makeExpense($otherProject->id, 300000);

        $this->actingAs($this->admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Total Revenue')
            ->assertSee('2.500.000')
            ->assertSee('500.000')
            ->assertSee($invoice->document_number)
            ->assertSee('Hubungkan Kas')
            ->assertSee('project-attach-modal-content', false)
            ->assertSee('projectExpenseSearch')
            ->assertSee('projectExpenseDateFrom')
            ->assertSee('projectExpenseDateTo')
            ->assertSee('data-project-expense-search', false)
            ->assertSee('Hubungkan Invoice')
            ->assertSee('projectInvoiceSearch')
            ->assertSee('projectInvoiceDateFrom')
            ->assertSee('projectInvoiceDateTo')
            ->assertDontSee($invoiceLinkedExpense->transaction_number);

        $this->actingAs($this->admin)
            ->post(route('projects.attach-expenses', $project), [
                'transaction_ids' => [$unallocatedExpense->id, $alreadyLinkedExpense->id, $invoiceLinkedExpense->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($project->id, $unallocatedExpense->fresh()->project_id);
        $this->assertSame($otherProject->id, $alreadyLinkedExpense->fresh()->project_id);
        $this->assertNull($invoiceLinkedExpense->fresh()->project_id);
        $this->assertSame($invoice->id, $invoiceLinkedExpense->fresh()->invoice_id);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => 'User project_group_admin menghubungkan 1 transaksi kas ke project "ERP Rollout".',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.projects', ['search' => 'ERP Rollout', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Project Margin Overview')
            ->assertSee('ERP Rollout')
            ->assertSee($this->customer->name)
            ->assertSee('1.750.000');

        $this->actingAs($this->admin)
            ->post(route('projects.detach-expense', [$project, $linkedExpense]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($linkedExpense->fresh()->project_id);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_UPDATE,
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
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => 'User project_group_admin menghubungkan 2 invoice ke project "ERP Rollout".',
        ]);

        $this->actingAs($this->admin)
            ->post(route('projects.detach-invoice', [$project, $unassignedInvoiceA]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNull($unassignedInvoiceA->fresh()->project_id);
        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Project Management',
            'action' => ActivityLog::ACTION_UPDATE,
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
            ->assertSee('13.000.000');
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
            ->assertSee(route('projects.destroy', $project));

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

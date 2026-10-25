<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSummaryKpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_net_profit_card_shows_its_amount_and_changes_color_for_a_loss(): void
    {
        $user = $this->createReportUser();
        [$incomeCategory, $expenseCategory] = $this->createCategories();

        $income = $this->createTransaction($incomeCategory, 'income', 200);
        $this->createTransaction($expenseCategory, 'expense', 300);

        $this->actingAs($user)
            ->get(route('reports.financial_statement', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertSee('position-absolute top-0 start-0 bottom-0 bg-danger', false)
            ->assertSee('bg-danger-subtle text-danger', false)
            ->assertSee('report-kpi-value fw-bold text-dark mb-1', false)
            ->assertSee('Net Loss')
            ->assertSee('Rp -100');

        $income->update(['amount' => 400]);

        $this->actingAs($user)
            ->get(route('reports.financial_statement', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertSee('position-absolute top-0 start-0 bottom-0 bg-primary', false)
            ->assertSee('bg-primary-subtle text-primary', false)
            ->assertSee('Net Profit')
            ->assertSee('Rp 100');
    }

    private function createReportUser(): User
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();

        return User::create([
            'username' => 'financial_summary_admin',
            'name' => 'Financial Summary Admin',
            'email' => 'financial-summary@example.test',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{Category, Category}
     */
    private function createCategories(): array
    {
        $incomeCategory = Category::create([
            'code' => '4-100',
            'name' => 'Pendapatan Jasa',
            'type' => 'income',
            'is_tax_deductible' => false,
        ]);
        $expenseCategory = Category::create([
            'code' => '6-100',
            'name' => 'Beban Operasional',
            'type' => 'expense',
            'is_tax_deductible' => true,
        ]);

        return [$incomeCategory, $expenseCategory];
    }

    private function createTransaction(Category $category, string $type, int $amount): Transaction
    {
        static $number = 0;
        $number++;

        return Transaction::create([
            'transaction_number' => "FIN-SUMMARY-{$number}",
            'type' => $type,
            'category_id' => $category->id,
            'amount' => $amount,
            'transaction_date' => '2026-01-15',
            'payment_method' => 'Bank Transfer',
            'description' => 'Financial summary KPI test',
        ]);
    }
}

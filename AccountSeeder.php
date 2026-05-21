<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\DetailType;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical 5-digit Chart of Accounts hierarchy per §3.4.
 *
 * Execution order: 3rd (after AccountTypeSeeder and AccountDetailTypeSeeder).
 * All seeded accounts are marked is_system = true (cannot be deleted).
 */
class AccountSeeder extends Seeder
{
    /**
     * Resolved lookups (cached during run).
     */
    private array $accountTypes = [];
    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetailType> */
    private \Illuminate\Database\Eloquent\Collection $detailTypes;
    private array $createdAccounts = [];

    public function __construct()
    {
        $this->detailTypes = new \Illuminate\Database\Eloquent\Collection();
    }

    public function run(): void
    {
        // Pre-load lookups
        $this->accountTypes = AccountType::pluck('id', 'name')->toArray();
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetailType> $detailTypes */
        $detailTypes = DetailType::with('accountType')->get();
        $this->detailTypes = $detailTypes;

        // Seed in hierarchical order (parents before children)
        $this->seedAssets();
        $this->seedLiabilities();
        $this->seedEquity();
        $this->seedIncome();
        $this->seedCostOfSales();
        $this->seedExpenses();
        $this->seedOtherIncomeExpenses();
    }

    // ══════════════════════════════════════════════════════════
    //  ASSETS (1xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedAssets(): void
    {
        // Top-level
        $this->seed('10000', 'Assets',                        'Cash',                 'Header',               false, false, false, null);
        $this->seed('11000', 'Current Assets',                'Cash',                 'Header',               false, false, false, '10000');

        // Cash & Bank (11100)
        $this->seed('11100', 'Cash & Bank',                   'Cash',                 'Header',               false, false, false, '11000');
        $this->seed('11110', 'Cash on Hand',                  'Cash',                 'Petty Cash',           false, false, false, '11100');
        $this->seed('11120', 'Petty Cash',                    'Cash',                 'Petty Cash',           false, false, false, '11100');
        $this->seed('11130', 'Bank Accounts',                 'Cash',                 'Checking',             false, false, false, '11100');
        $this->seed('11131', 'CBE Bank',                      'Cash',                 'Checking',             false, true,  false, '11130');
        $this->seed('11132', 'Awash Bank',                    'Cash',                 'Checking',             false, true,  false, '11130');
        $this->seed('11133', 'Telebirr',                      'Cash',                 'Checking',             false, true,  false, '11130');
        $this->seed('11134', 'Bank of Abyssinia',             'Cash',                 'Checking',             false, true,  false, '11130');
        $this->seed('11135', 'COOP',                          'Cash',                 'Checking',             false, true,  false, '11130');

        // Accounts Receivable (11200)
        $this->seed('11200', 'Accounts Receivable',           'Accounts Receivable',  'Header',               false, false, false, '11000');
        $this->seed('11210', 'Trade Receivables',             'Accounts Receivable',  'Trade Receivables',    false, false, false, '11200');
        $this->seed('11220', 'Allowance for Doubtful Accounts', 'Accounts Receivable', 'Allowance',            true,  false, false, '11200');
        $this->seed('11230', 'VAT Input (Purchase Tax)',       'Accounts Receivable',  'VAT Receivable',       false, false, false, '11200');
        $this->seed('11240', 'WHT Receivable — 3%',           'Accounts Receivable',  'WHT Receivable',       false, false, false, '11200');
        $this->seed('11250', 'WHT Receivable — Income Tax Credit', 'Accounts Receivable', 'WHT Receivable',   false, false, false, '11200');
        $this->seed('11260', 'Advance Income Tax (Quarterly)', 'Accounts Receivable',  'Tax Advance',          false, false, false, '11200');

        // Inventory (11300)
        $this->seed('11300', 'Inventory',                     'Inventory',            'Header',               false, false, false, '11000');
        $this->seed('11310', 'Raw Materials & Supplies',      'Inventory',            'Raw Materials',        false, false, false, '11300');
        $this->seed('11320', 'Work in Progress',              'Inventory',            'Work in Progress',     false, false, false, '11300');
        $this->seed('11330', 'Inventory Reserve / Obsolescence', 'Inventory',           'Inventory Reserve',    true,  false, false, '11300');

        // Other Current Assets (11400)
        $this->seed('11400', 'Other Current Assets',          'Other Current Assets', 'Header',               false, false, false, '11000');
        $this->seed('11410', 'Prepaid Rent',                  'Other Current Assets', 'Prepayments',          false, false, false, '11400');
        $this->seed('11420', 'Prepaid Insurance',             'Other Current Assets', 'Prepayments',          false, false, false, '11400');
        $this->seed('11430', 'Office Supplies',               'Other Current Assets', 'Supplies',             false, false, false, '11400');
        $this->seed('11440', 'Prepayments to Suppliers',      'Other Current Assets', 'Prepayments',          false, false, false, '11400');
        $this->seed('11450', 'VAT Government Voucher Credit', 'Other Current Assets', 'VAT Receivable',       false, false, false, '11400');

        // Fixed Assets (12xxx)
        $this->seed('12000', 'Fixed Assets',                  'Fixed Assets',         'Header',               false, false, false, '10000');
        $this->seed('12100', 'Land',                          'Fixed Assets',         'Land',                 false, false, false, '12000');
        $this->seed('12200', 'Buildings',                     'Fixed Assets',         'Buildings',            false, false, false, '12000');
        $this->seed('12210', 'Accum. Deprec. — Buildings',    'Fixed Assets',         'Accumulated Depreciation', true, false, false, '12200');
        $this->seed('12300', 'Machinery & Equipment',         'Fixed Assets',         'Machinery',            false, false, false, '12000');
        $this->seed('12310', 'Accum. Deprec. — Machinery & Equipment', 'Fixed Assets', 'Accumulated Depreciation', true, false, false, '12300');
        $this->seed('12400', 'Computers & Electronics',       'Fixed Assets',         'Computers',            false, false, false, '12000');
        $this->seed('12410', 'Accum. Deprec. — Computers & Electronics', 'Fixed Assets', 'Accumulated Depreciation', true, false, false, '12400');
        $this->seed('12500', 'Furniture & Fixtures',          'Fixed Assets',         'Furniture',            false, false, false, '12000');
        $this->seed('12510', 'Accum. Deprec. — Furniture & Fixtures', 'Fixed Assets', 'Accumulated Depreciation', true, false, false, '12500');
        $this->seed('12600', 'Office Equipment',              'Fixed Assets',         'Office Equipment',     false, false, false, '12000');
        $this->seed('12610', 'Accum. Deprec. — Office Equipment', 'Fixed Assets',     'Accumulated Depreciation', true, false, false, '12600');
        $this->seed('12700', 'Vehicles',                      'Fixed Assets',         'Vehicles',             false, false, false, '12000');
        $this->seed('12710', 'Accum. Deprec. — Vehicles',     'Fixed Assets',         'Accumulated Depreciation', true, false, false, '12700');
        $this->seed('12800', 'Leasehold Improvements',        'Fixed Assets',         'Leasehold',            false, false, false, '12000');
        $this->seed('12810', 'Accum. Deprec. — Leasehold Improvements', 'Fixed Assets', 'Accumulated Depreciation', true, false, false, '12800');
        $this->seed('12900', 'Accumulated Depreciation — Control', 'Fixed Assets',    'Accumulated Depreciation', true, false, false, '12000');

        // Other Assets (13xxx)
        $this->seed('13000', 'Other Assets',                  'Other Assets',         'Header',               false, false, false, '10000');
        $this->seed('13100', 'Intangible Assets',             'Other Assets',         'Intangibles',          false, false, false, '13000');
        $this->seed('13110', 'Accumulated Amortization',      'Other Assets',         'Accumulated Amortization', true, false, false, '13100');
        $this->seed('13200', 'Long-term Investments',         'Other Assets',         'Investments',          false, false, false, '13000');
        $this->seed('13300', 'Bond Issue Costs',              'Other Assets',         'Bond Costs',           false, false, false, '13000');
    }

    // ══════════════════════════════════════════════════════════
    //  LIABILITIES (2xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedLiabilities(): void
    {
        $this->seed('20000', 'Liabilities',                   'Accounts Payable',          'Header',           false, false, false, null);
        $this->seed('21000', 'Current Liabilities',           'Accounts Payable',          'Header',           false, false, false, '20000');

        // Accounts Payable (21100)
        $this->seed('21100', 'Accounts Payable',              'Accounts Payable',          'Header',           false, false, false, '21000');
        $this->seed('21110', 'Trade Payables',                'Accounts Payable',          'Trade Payables',   false, false, false, '21100');
        $this->seed('21120', 'Accrued Purchases',             'Accounts Payable',          'Accrued',          false, false, false, '21100');
        $this->seed('21130', 'Accrued Expenses',              'Accounts Payable',          'Accrued',          false, false, false, '21100');

        // Tax Payable (21200)
        $this->seed('21200', 'Tax Payable',                   'Tax Payable',               'Header',           false, false, false, '21000');
        $this->seed('21210', 'VAT Output (Sales Tax)',        'Tax Payable',               'VAT',              false, false, false, '21200');
        $this->seed('21220', 'Reverse VAT Output (Imported Services)', 'Tax Payable',     'VAT',              false, false, false, '21200');
        $this->seed('21230', 'Income Tax Payable',            'Tax Payable',               'Income Tax',       false, false, false, '21200');
        $this->seed('21240', 'Quarterly Advance Income Tax Payable', 'Tax Payable',       'Income Tax',       false, false, false, '21200');
        $this->seed('21250', 'WHT Payable — 3% (Goods & Services)', 'Tax Payable',       'WHT',              false, false, false, '21200');
        $this->seed('21260', 'WHT Payable — 30% (Non-TIN Suppliers)', 'Tax Payable',     'WHT',              false, false, false, '21200');

        // Other Current Liabilities (21300)
        $this->seed('21300', 'Other Current Liabilities',     'Other Current Liabilities', 'Header',           false, false, false, '21000');
        $this->seed('21310', 'Net Salaries Payable',          'Other Current Liabilities', 'Salaries Payable', false, false, false, '21300');
        $this->seed('21320', 'Pension Payable — Employee 7%', 'Other Current Liabilities', 'Pension Payable',  false, false, false, '21300');
        $this->seed('21330', 'Pension Payable — Employer 11%', 'Other Current Liabilities', 'Pension Payable',  false, false, false, '21300');
        $this->seed('21340', 'SHI Payable — Employee 1.5%',  'Other Current Liabilities', 'SHI Payable',      false, false, false, '21300');
        $this->seed('21350', 'SHI Payable — Employer 1.5%',  'Other Current Liabilities', 'SHI Payable',      false, false, false, '21300');
        $this->seed('21360', 'Other Payroll Deductions Payable', 'Other Current Liabilities', 'Deductions',    false, false, false, '21300');
        $this->seed('21370', 'Customer Deposits',             'Other Current Liabilities', 'Deposits',         false, false, false, '21300');

        // Long Term Debt (22xxx)
        $this->seed('22000', 'Long Term Debt',                'Long Term Debt',            'Header',           false, false, false, '20000');
        $this->seed('22100', 'Bank Loan',                     'Long Term Debt',            'Bank Loan',        false, false, false, '22000');
        $this->seed('22200', 'Bonds Payable',                 'Long Term Debt',            'Bonds',            false, false, false, '22000');
        $this->seed('22300', 'Discount on Bonds Payable',     'Long Term Debt',            'Bond Discount',    true,  false, false, '22000');
    }

    // ══════════════════════════════════════════════════════════
    //  EQUITY (3xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedEquity(): void
    {
        $this->seed('30000', 'Equity',                  'Equity',            'Header',            false, false, false, null);
        $this->seed('31000', 'Owner\'s Capital',        'Equity',            'Capital',           false, false, false, '30000');
        $this->seed('32000', 'Owner\'s Drawings',       'Equity',            'Drawings',          false, false, false, '30000');
        $this->seed('33000', 'Retained Earnings',       'Retained Earnings', 'Retained Earnings', false, false, false, '30000');
        $this->seed('34000', 'Income Summary',          'Income Summary',    'Income Summary',    false, false, false, '30000');
        $this->seed('38000', 'Opening Balance Equity',  'Equity',            'Opening Balance',   false, false, false, '30000');
        $this->seed('39000', 'Current Year Earnings',   'Income Summary',    'Income Summary',    false, false, false, '30000');
    }

    // ══════════════════════════════════════════════════════════
    //  INCOME (4xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedIncome(): void
    {
        $this->seed('40000', 'Revenue',                     'Income',         'Header',        false, false, false, null);
        $this->seed('41000', 'Standard Printing Revenue',   'Income',         'Product Sales', false, false, false, '40000');
        $this->seed('42000', 'Custom Printing Revenue',     'Income',         'Product Sales', false, false, false, '40000');
        $this->seed('43000', 'Service/Fee Income',          'Service Income', 'Services',      false, false, false, '40000');
        $this->seed('44000', 'Other Operating Revenue',     'Income',         'Other Revenue', false, false, false, '40000');
        $this->seed('49000', 'Sales Returns & Allowances',  'Income',         'Returns',       true,  false, false, '40000');
        $this->seed('49100', 'Sales Discounts',             'Income',         'Discounts',     true,  false, false, '40000');
    }

    // ══════════════════════════════════════════════════════════
    //  COST OF SALES (5xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedCostOfSales(): void
    {
        $this->seed('50000', 'Cost of Sales',              'Cost of Goods Sold', 'Header',      false, false, false, null);
        $this->seed('51000', 'Cost of Materials',          'Cost of Goods Sold', 'Materials',   false, false, false, '50000');
        $this->seed('52000', 'Direct Labor',               'Cost of Goods Sold', 'Labor',       false, false, false, '50000');
        $this->seed('53000', 'Freight In',                 'Cost of Goods Sold', 'Freight',     false, false, false, '50000');
        $this->seed('54000', 'Inventory Adjustments',      'Cost of Goods Sold', 'Adjustments', false, false, false, '50000');
        $this->seed('55000', 'Inventory Write-Down Expense', 'Cost of Goods Sold', 'Write-Down', false, false, false, '50000');
        $this->seed('59000', 'Purchase Returns',           'Cost of Goods Sold', 'Returns',     true,  false, false, '50000');
        $this->seed('59100', 'Purchase Discounts',         'Cost of Goods Sold', 'Discounts',   true,  false, false, '50000');
        $this->seed('59200', 'Purchase Allowances',        'Cost of Goods Sold', 'Allowances',  true,  false, false, '50000');
    }

    // ══════════════════════════════════════════════════════════
    //  EXPENSES (6xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedExpenses(): void
    {
        // Top-level
        $this->seed('60000', 'Operating Expenses',             'Expenses', 'Header',        false, false, false, null);

        // General Operating (61xxx)
        $this->seed('61000', 'Rent or Lease Expense',          'Expenses', 'Rent',           false, false, false, '60000');
        $this->seed('61100', 'Utilities Expense',              'Expenses', 'Header',         false, false, false, '60000');
        $this->seed('61110', 'Electricity Expense',            'Expenses', 'Utilities',      false, false, false, '61100');
        $this->seed('61120', 'Internet Expense',               'Expenses', 'Utilities',      false, false, false, '61100');
        $this->seed('61130', 'Water Expense',                  'Expenses', 'Utilities',      false, false, false, '61100');
        $this->seed('61200', 'Communication Expense',          'Expenses', 'Communication',  false, false, false, '60000');
        $this->seed('61300', 'Insurance Expense',              'Expenses', 'Insurance',      false, false, false, '60000');
        $this->seed('61400', 'Office Supplies & Stationery',   'Expenses', 'Supplies',       false, false, false, '60000');
        $this->seed('61500', 'Repairs & Maintenance',          'Expenses', 'Maintenance',    false, false, false, '60000');
        $this->seed('61600', 'Depreciation Expense',           'Expenses', 'Depreciation',   false, false, false, '60000');
        $this->seed('61700', 'Bank Service Charges',           'Expenses', 'Bank Fees',      false, false, false, '60000');
        $this->seed('61800', 'Licenses & Permits',             'Expenses', 'Licenses',       false, false, false, '60000');
        $this->seed('61900', 'Miscellaneous Expense',          'Expenses', 'Miscellaneous',  false, false, false, '60000');

        // Payroll & Benefits (62xxx)
        $this->seed('62000', 'Payroll & Benefits',             'Expenses', 'Header',         false, false, false, '60000');
        $this->seed('62100', 'Salaries & Wages Expense',       'Expenses', 'Salaries',       false, false, false, '62000');
        $this->seed('62200', 'Overtime Expense',               'Expenses', 'Overtime',       false, false, false, '62000');
        $this->seed('62300', 'Bonus Expense',                  'Expenses', 'Bonus',          false, false, false, '62000');
        $this->seed('62400', 'Employer Pension Expense (11%)', 'Expenses', 'Pension',        false, false, false, '62000');
        $this->seed('62500', 'Field Transport Allowance Expense', 'Expenses', 'Transport',     false, false, false, '62000');
        $this->seed('62600', 'Housing Allowance Expense',      'Expenses', 'Housing',        false, false, false, '62000');
        $this->seed('62700', 'Transport Allowance Expense',    'Expenses', 'Transport',      false, false, false, '62000');
        $this->seed('62800', 'Per Diem Expense',               'Expenses', 'Per Diem',       false, false, false, '62000');
        $this->seed('62900', 'Per Diem (Hotel) Expense',       'Expenses', 'Per Diem',       false, false, false, '62000');
        $this->seed('62910', 'Employer SHI Expense (1.5%)',    'Expenses', 'SHI',            false, false, false, '62000');
        $this->seed('62920', 'Payroll Tax Expense',            'Expenses', 'Payroll Tax',    false, false, false, '62000');
        $this->seed('62930', 'Income Tax Expense',             'Expenses', 'Income Tax',     false, false, false, '62000');

        // Sales & Marketing (63xxx)
        $this->seed('63000', 'Advertising & Marketing',        'Expenses', 'Marketing',      false, false, false, '60000');
        $this->seed('63100', 'Travel & Entertainment',         'Expenses', 'Travel',          false, false, false, '60000');
    }

    // ══════════════════════════════════════════════════════════
    //  OTHER INCOME & EXPENSES (7xxxx–9xxxx)
    // ══════════════════════════════════════════════════════════

    private function seedOtherIncomeExpenses(): void
    {
        // Other Income (9xxxx)
        $this->seed('90000', 'Other Income',                'Other Income',  'Header',    false, false, false, null);
        $this->seed('91000', 'Interest Earned',             'Other Income',  'Interest',  false, false, false, '90000');
        $this->seed('92000', 'Inventory Write-Down Reversal', 'Other Income', 'Reversals', false, false, false, '90000');

        // Other Expense (93xxx)
        $this->seed('93000', 'Other Expense',               'Other Expense', 'Header',    false, false, false, null);
        $this->seed('93100', 'Interest Expense',            'Other Expense', 'Interest',  false, false, false, '93000');
        $this->seed('93200', 'Exchange Gain/Loss',          'Other Expense', 'Exchange',  false, false, false, '93000');
    }

    // ══════════════════════════════════════════════════════════
    //  HELPER
    // ══════════════════════════════════════════════════════════

    /**
     * Create or find a single account record.
     */
    private function seed(
        string $code,
        string $name,
        string $accountTypeName,
        string $detailTypeName,
        bool   $isContra,
        bool   $isBank,
        bool   $isBankAccount, // unused flag kept for alignment — actual is_bank flag used
        ?string $parentCode,
    ): void {
        $accountTypeId = $this->accountTypes[$accountTypeName] ?? null;

        if (! $accountTypeId) {
            $this->command?->warn("Account Type '{$accountTypeName}' not found for account {$code}.");
            return;
        }

        $detailType = $this->detailTypes
            ->where('account_type_id', $accountTypeId)
            ->where('name', $detailTypeName)
            ->first();

        if (! $detailType) {
            $this->command?->warn("Detail Type '{$detailTypeName}' not found under '{$accountTypeName}' for account {$code}.");
            return;
        }

        $parentId = null;
        if ($parentCode !== null) {
            $parentId = $this->createdAccounts[$parentCode] ?? null;
            if ($parentId === null) {
                $this->command?->warn("Parent code '{$parentCode}' not yet created for account {$code}.");
            }
        }

        $account = Account::firstOrCreate(
            ['code' => $code],
            [
                'account_type_id' => $accountTypeId,
                'detail_type_id'  => $detailType->id,
                'code'            => $code,
                'name'            => $name,
                'parent_id'       => $parentId,
                'is_contra'       => $isContra,
                'is_bank'         => $isBank,
                'is_system'       => true,
                'status'          => 'active',
            ],
        );

        $this->createdAccounts[$code] = $account->id;
    }
}

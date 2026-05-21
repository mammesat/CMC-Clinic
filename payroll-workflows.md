# Payroll Workflows — Complete Architecture Reference

> **Source**: Deep analysis of `mammesat/AcctApp` (Laravel + Filament ERP)
> **Jurisdiction**: Ethiopian Labor & Tax Law (Proclamation No. 979/2016, 2025 Amendment)
> **Last Analyzed**: 2026-05-21

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Data Model Architecture](#2-data-model-architecture)
3. [Payroll Settings & Configuration](#3-payroll-settings--configuration)
4. [Ethiopian Statutory Rules Engine](#4-ethiopian-statutory-rules-engine)
5. [Employee Payroll Profile Management](#5-employee-payroll-profile-management)
6. [Pay Types & Earnings Structure](#6-pay-types--earnings-structure)
7. [Deductions Architecture](#7-deductions-architecture)
8. [Variable Inputs & Overtime](#8-variable-inputs--overtime)
9. [Allowance Exemption Engine](#9-allowance-exemption-engine)
10. [Payroll Calculation Engine](#10-payroll-calculation-engine)
11. [Payroll Run Lifecycle](#11-payroll-run-lifecycle)
12. [GL Journal Entry Posting](#12-gl-journal-entry-posting)
13. [Payment Disbursement Workflow](#13-payment-disbursement-workflow)
14. [Voiding & Reversal](#14-voiding--reversal)
15. [Audit Trail & History Tracking](#15-audit-trail--history-tracking)
16. [Statutory Reporting](#16-statutory-reporting)
17. [Payroll Setup Service (Auto-Seeding)](#17-payroll-setup-service-auto-seeding)
18. [Account Mapping & Chart of Accounts](#18-account-mapping--chart-of-accounts)
19. [Configuration Validation](#19-configuration-validation)
20. [Filament UI Layer](#20-filament-ui-layer)
21. [Security & Permissions](#21-security--permissions)
22. [Compliance Features](#22-compliance-features)

---

## 1. System Overview

The AcctApp payroll module is a full-cycle, Ethiopian-compliant payroll processing system embedded within a multi-tenant Laravel ERP. It handles everything from employee salary configuration to statutory deduction calculation, GL journal posting, and payment disbursement.

### Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│                 Filament UI Layer                    │
│  (Resources, Clusters, Pages, Widgets, Infolists)   │
├─────────────────────────────────────────────────────┤
│              Service Layer (Business Logic)          │
│  PayrollCalculationService  │  PayrollJournalEntry   │
│  PayrollPaymentService      │  PayrollReportService  │
│  StatutoryDeductionEngine   │  PayrollSetupService   │
│  PayrollValidationService   │  PayrollRetroactive    │
│  CashComplianceService      │  ConfigGovernance      │
├─────────────────────────────────────────────────────┤
│              Observer Layer (Audit Trail)            │
│  PayrollProfileObserver  │  PayrollInputObserver     │
│  EmployeeRecurringComponentObserver                  │
├─────────────────────────────────────────────────────┤
│              Model & Policy Layer                    │
│  16+ Eloquent Models  │  8+ Policy Classes           │
├─────────────────────────────────────────────────────┤
│              Database Layer                          │
│  30+ Migrations  │  Multi-tenant (BelongsToTenant)   │
└─────────────────────────────────────────────────────┘
```

### Multi-Tenancy

Every payroll model uses the `BelongsToTenant` concern, which automatically scopes all queries to the authenticated user's company. All `withoutGlobalScopes()` calls in services bypass this for cross-tenant reads during background jobs or seeding.

---

## 2. Data Model Architecture

### Entity Relationship Diagram

```
Company (1)
 ├── PayrollSetting (1:1)
 ├── PayrollAccountMapping (1:1)
 ├── PayrollStatutoryRule (1:N) ── [income_tax, pension, shi]
 ├── PayrollTaxBracket (1:N)
 ├── PayrollOvertimeRule (1:1)
 ├── PayrollAllowanceExemption (1:N)
 ├── PayrollPayType (1:N) ── expense_account_id → Account
 ├── PayrollDeduction (1:N) ── liability_account_id → Account
 ├── PayrollEmployerContribution (1:N) ── expense + liability Accounts
 ├── PayrollPaymentMethod (1:N) ── bank_account_id → Account
 ├── PayrollPeriod (1:N)
 │    └── PayrollRun (1:N)
 │         ├── PayrollRunLine (1:N per employee)
 │         ├── JournalEntry (FK: journal_entry_id)
 │         └── JournalEntry (FK: payment_journal_entry_id)
 ├── Employee (1:N)
 │    ├── EmployeePayrollProfile (1:1)
 │    │    ├── EmployeeRecurringComponent (1:N) ── payroll_pay_type_id
 │    │    └── PayrollProfileHistory (1:N)
 │    └── PayrollInput (1:N) ── variable pay per period
 └── SettingCompany (1:1) ── SHI toggle, secondary employment config
```

### Core Models Summary

| Model | Table | Purpose |
|-------|-------|---------|
| `PayrollSetting` | `payroll_settings` | Global payroll config per company |
| `PayrollPayType` | `payroll_pay_types` | Earnings & deduction type definitions |
| `PayrollDeduction` | `payroll_deductions` | Statutory deduction definitions |
| `PayrollEmployerContribution` | `payroll_employer_contributions` | Employer-side contribution rules |
| `PayrollStatutoryRule` | `payroll_statutory_rules` | Income Tax / Pension / SHI config (JSON) |
| `PayrollTaxBracket` | `payroll_tax_brackets` | Normalized PAYE tax brackets |
| `PayrollOvertimeRule` | `payroll_overtime_rules` | OT multiplier configuration |
| `PayrollAllowanceExemption` | `payroll_allowance_exemptions` | Tax/pension exemption rules |
| `PayrollAccountMapping` | `payroll_account_mappings` | GL account mapping for JE posting |
| `PayrollPaymentMethod` | `payroll_payment_methods` | Bank/Cash/Cheque methods |
| `PayrollPeriod` | `payroll_periods` | Monthly pay periods |
| `PayrollRun` | `payroll_runs` | Per-period processing batch |
| `PayrollRunLine` | `payroll_run_lines` | Per-employee calculation results |
| `PayrollInput` | `payroll_inputs` | Variable pay entries (OT, bonus) |
| `EmployeePayrollProfile` | `employee_payroll_profiles` | Employee salary configuration |
| `EmployeeRecurringComponent` | `employee_recurring_components` | Recurring salary/deduction items |
| `PayrollProfileHistory` | `payroll_profile_history` | Full audit snapshots |

---

## 3. Payroll Settings & Configuration

### PayrollSetting Model

Central configuration per company, stored in `payroll_settings`:

```
┌──────────────────────────────────────────────────────────┐
│                  GENERAL SETTINGS                        │
├──────────────────────────────────────────────────────────┤
│ payroll_enabled        │ Boolean on/off toggle           │
│ default_frequency      │ 'monthly' (default)             │
│ payroll_currency       │ 'ETB' (Ethiopian Birr)          │
│ payroll_start_date     │ Date payroll system begins      │
├──────────────────────────────────────────────────────────┤
│                  PERIOD MANAGEMENT                       │
├──────────────────────────────────────────────────────────┤
│ period_lock_rule       │ 'manual' or 'auto'              │
│ auto_lock_days         │ Days after period end to lock    │
│ period_generation      │ 'auto' or 'manual'              │
│ period_start_day       │ Day of month (default: 1)        │
│ pay_day_offset         │ Days after period for pay date   │
│ grace_period_days      │ Grace period for late inputs     │
├──────────────────────────────────────────────────────────┤
│                  CALCULATION SETTINGS                    │
├──────────────────────────────────────────────────────────┤
│ tax_rounding           │ 'nearest', 'up', 'down', 'none' │
│ pension_rounding       │ 'nearest', 'up', 'down', 'none' │
│ minimum_wage           │ Statutory minimum (decimal:2)    │
│ working_days_per_month │ Default: 22                      │
│ working_hours_per_day  │ Default: 8                       │
├──────────────────────────────────────────────────────────┤
│                  APPROVAL WORKFLOW                       │
├──────────────────────────────────────────────────────────┤
│ require_approval_for_runs  │ Boolean                     │
│ approval_threshold         │ Amount threshold for auto    │
│ approver_role              │ Role who can approve         │
├──────────────────────────────────────────────────────────┤
│                  ADVANCED OPTIONS                        │
├──────────────────────────────────────────────────────────┤
│ allow_backdated_runs       │ Allow past-date runs         │
│ enable_department_split    │ Split by department           │
│ deduct_unpaid_leave        │ Leave integration             │
│ log_payroll_changes        │ All changes logged            │
│ require_change_reason      │ Force reason on edits         │
│ default_payment_method_id  │ FK → PayrollPaymentMethod     │
│ default_report_format      │ Export format                  │
│ bank_file_format           │ Bank file type                 │
└──────────────────────────────────────────────────────────┘
```

### Accessor Methods

- `active_tax_rule` → Returns the currently active income_tax `PayrollStatutoryRule`
- `active_pension_rule` → Returns the currently active pension `PayrollStatutoryRule`
- `active_allowance_exemption_rule` → Returns the active allowance_exemptions rule

---

## 4. Ethiopian Statutory Rules Engine

### Architecture: Strategy Pattern

The statutory deduction system uses a **Strategy Pattern** via the `StatutoryDeductionEngine`:

```
StatutoryRuleContract (Interface)
 ├── IncomeTaxRule    → code: 'PAYE'
 ├── PensionRule      → code: 'PENSION'
 └── SHIRule          → code: 'SHI'

StatutoryDeductionEngine
 ├── register(StatutoryRuleContract $rule)
 └── executeAll(context, profile, companyId, date, rounding...)
      → Returns: array indexed by rule code
```

Each rule implements:
```php
interface StatutoryRuleContract {
    public function getCode(): string;           // e.g. 'PAYE'
    public function getName(): string;           // e.g. 'Income Tax (PAYE)'
    public function isApplicable(...): bool;      // Should this rule run?
    public function calculate(...): array;        // Compute deductions
}
```

### Return Structure per Rule

```php
[
    'employee_deduction'    => float,  // Amount deducted from employee
    'employer_contribution' => float,  // Amount employer pays
    'rule_details'          => string, // e.g. "EE 7% | ER 11%"
    'rule_id'               => ?int,
    'base'                  => float,  // The base amount used
    'name'                  => string,
    'ee_name'               => string, // Display name for employee portion
    'er_name'               => string, // Display name for employer portion
]
```

---

### 4.1 Income Tax Rule (PAYE)

**Ethiopian Progressive Tax Brackets (2025 Amendment, effective July 1, 2025):**

| Bracket | Min (ETB) | Max (ETB) | Rate | Deduction |
|---------|-----------|-----------|------|-----------|
| 1       | 0         | 2,000     | 0%   | 0         |
| 2       | 2,001     | 4,000     | 15%  | 300       |
| 3       | 4,001     | 7,000     | 20%  | 500       |
| 4       | 7,001     | 10,000    | 25%  | 850       |
| 5       | 10,001    | 14,000    | 30%  | 1,350     |
| 6       | 14,001    | ∞         | 35%  | 2,050     |

**Formula**: `Tax = (Taxable Income × Rate%) − Deduction`

**Bonus Tax Spreading**: For bonuses with `performance_months > 1`:
1. Calculate standard tax on base salary
2. Simulate tax on (base salary + monthly bonus portion)
3. Marginal difference × performance months = bonus tax
4. Total tax = Standard tax + Bonus tax

**Secondary Employment**: If `is_secondary_employment` flag is set on the profile, applies a flat rate (default 35%) instead of progressive brackets. Controlled by `SettingCompany.payroll_secondary_employment_enabled` and `payroll_secondary_employment_rate`.

### 4.2 Pension Rule

**Ethiopian Pension (Proclamation standards):**

| Component | Rate | Base |
|-----------|------|------|
| Employee  | 7%   | Pensionable Earnings |
| Employer  | 11%  | Pensionable Earnings |

Configuration loaded from `PayrollStatutoryRule` where `rule_type = 'pension'`:
```json
{
    "employee_rate": 7.0,
    "employer_rate": 11.0,
    "is_mandatory": true
}
```

### 4.3 Social Health Insurance (SHI) Rule

**Ethiopian SHI (effective 2026-01-01):**

| Component | Rate | Base |
|-----------|------|------|
| Employee  | 1.5% | Base Salary |
| Employer  | 1.5% | Base Salary |

Controlled by `SettingCompany.shi_enabled` toggle. If disabled at company level, the entire SHI rule is skipped.

Configuration from `PayrollStatutoryRule` where `rule_type = 'shi'`:
```json
{
    "enabled": true,
    "total_rate": 3.0,
    "employee_rate": 1.5,
    "employer_rate": 1.5
}
```

---

## 5. Employee Payroll Profile Management

### Auto-Creation

When an `Employee` is created, the `booted()` method automatically creates an `EmployeePayrollProfile`:
- `is_active` = false (must be manually activated after configuration)
- `payment_method_id` = Default payment method (looks for `is_default = true`, fallback to `BANK` code)

### Profile Fields

| Field | Type | Description |
|-------|------|-------------|
| `is_active` | Boolean | Must be true to include in payroll runs |
| `is_secondary_employment` | Boolean | Triggers flat-rate tax |
| `payroll_frequency` | String | 'monthly' |
| `payment_method_id` | FK | How employee gets paid |
| `bank_account_number` | String | Bank details |
| `bank_name` | String | Bank name |
| `tax_identification_number` | String | TIN for tax reporting |
| `pension_account_number` | String | Pension ID |

### Recurring Components (Salary Structure)

Each profile has N `EmployeeRecurringComponent` records:

```
EmployeePayrollProfile (1)
 ├── Earnings Components (salary_component usage, non-deduction categories)
 │    ├── Basic Salary (base_salary category, taxable, pensionable)
 │    ├── Housing Allowance (housing category)
 │    ├── Transport Allowance (transport_regular category)
 │    └── Communication Allowance (communication category)
 │
 └── Deduction Components (salary_component usage, deduction/loan/penalty categories)
      ├── Loan Repayment
      └── Court Order Deduction
```

Each component links to a `PayrollPayType` via `payroll_pay_type_id` and has an `amount` field.

### Scope Methods

- `earnings()` → Recurring components where `payType.usage = 'salary_component'` AND category NOT IN ('deduction', 'loan', 'penalty')
- `deductions()` → Recurring components where `payType.usage = 'salary_component'` AND category IN ('deduction', 'loan', 'penalty')

### History Tracking

- `getProfileAsOf(Carbon $date)` → Returns the `PayrollProfileHistory` snapshot as of a date
- `getSalaryHistory()` → All history where `change_type` IN ('created', 'salary_adjustment', 'salary_increase', 'promotion')
- `getLatestChange()` → Most recent history record

---

## 6. Pay Types & Earnings Structure

### PayrollPayType Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | String | Display name (e.g., "Basic Salary") |
| `code` | String | Unique code (e.g., "BASIC") |
| `category` | String | Classification (see below) |
| `usage` | String | `salary_component` or `variable_input` |
| `is_taxable` | Boolean | Subject to income tax? |
| `is_pensionable` | Boolean | Subject to pension? |
| `expense_account_id` | FK → Account | GL expense account for JE |
| `calculation_method` | String | 'fixed' or 'hours-based' |
| `default_amount` | Decimal | Default amount |
| `is_system` | Boolean | System-managed, cannot delete |
| `is_active` | Boolean | Active toggle |

### Categories

| Category | Usage | Taxable | Pensionable | Examples |
|----------|-------|---------|-------------|----------|
| `base_salary` | salary_component | ✅ | ✅ | Basic Salary |
| `housing` | salary_component | ✅ | ❌ | Housing Allowance |
| `transport_regular` | salary_component | ✅* | ❌ | Transport Allowance |
| `transport_special` | variable_input | ✅* | ❌ | Field Transport |
| `overtime` | variable_input | ✅ | ❌ | Overtime |
| `bonus` | variable_input | ✅ | ❌ | Bonus |
| `per_diem_no_receipt` | variable_input | ✅* | ❌ | Per Diem |
| `per_diem_with_receipt` | variable_input | ✅* | ❌ | Per Diem (Hotel) |
| `communication` | salary_component | ✅ | ❌ | Communication Allow. |
| `deduction` | salary_component | — | — | Custom deductions |
| `loan` | salary_component | — | — | Loan repayments |
| `penalty` | salary_component | — | — | Penalties |

> *Categories marked with * may have allowance exemptions that reduce the taxable portion

### SeededPayType Codes

`BASIC`, `HOUSING`, `TRANSPORT`, `FIELD_TRANSPORT`, `OVERTIME`, `BONUS`, `PER_DIEM`, `PER_DIEM_HOTEL`, `COMM_ALLOW`

---

## 7. Deductions Architecture

### Three-Layer Deduction Model

```
Layer 1: Statutory Deductions (PayrollDeduction model)
 ├── PAYE (Income Tax) → liability_account: 21230
 ├── EE_PENSION (Employee Pension 7%) → liability_account: 21320
 └── EE_SHI (Employee SHI 1.5%) → liability_account: 21340

Layer 2: Profile Deductions (EmployeeRecurringComponent with deduction categories)
 └── Recurring deductions configured per employee

Layer 3: Variable Deductions (PayrollInput with deduction category PayTypes)
 └── One-off penalty, loan payment, etc.
```

### PayrollDeduction Fields

| Field | Type | Description |
|-------|------|-------------|
| `code` | String | 'PAYE', 'EE_PENSION', 'EE_SHI' |
| `is_statutory` | Boolean | True for government-mandated |
| `calculation_method` | String | 'bracketed', 'percentage' |
| `liability_account_id` | FK → Account | GL liability for JE credits |
| `priority_order` | Integer | Processing order |
| `default_rate` | Decimal | Rate (e.g., 0.07 for 7%) |
| `is_system` | Boolean | Cannot be deleted |

### Employer Contributions

| Code | Name | Rate | Base | Expense Account | Liability Account |
|------|------|------|------|-----------------|-------------------|
| `ER_PENSION` | Employer Pension (11%) | 11.0% | gross_pensionable | 62400 | 21330 |
| `ER_SHI` | Employer SHI (1.5%) | 1.5% | gross_salary | 62910 | 21350 |

Base calculation methods: `percentage_basic`, `gross_pensionable`, `percentage_gross`, `fixed`

---

## 8. Variable Inputs & Overtime

### PayrollInput Model

Variable pay entries (overtime, bonuses, one-off deductions) submitted per employee per period:

| Field | Type | Description |
|-------|------|-------------|
| `employee_id` | FK | Target employee |
| `payroll_pay_type_id` | FK | Type of variable pay |
| `payroll_run_id` | FK | Set when processed |
| `date` | Date | Date of the input |
| `amount` | Decimal | Calculated or entered amount |
| `description` | String | Notes |
| `status` | String | 'pending' → 'approved' → 'processed' |
| `overtime_type` | String | 'standard', 'night', 'weekend', 'holiday' |
| `hours_worked` | Decimal | OT hours |
| `hourly_rate` | Decimal | Rate per hour (auto or manual) |
| `overtime_multiplier` | Decimal | Applied multiplier |
| `performance_months` | Integer | For bonus tax spreading |

### Status Lifecycle

```
pending → approved → processed (linked to PayrollRun)
                   → If run is deleted/voided → reverted back to 'approved'
```

### Overtime Calculation

When `overtime_type` and `hours_worked` are provided:

1. Look up `PayrollOvertimeRule` for the company
2. Determine multiplier based on type:

| Type | Default Multiplier |
|------|--------------------|
| `standard` | 1.50× |
| `night` | 1.75× |
| `weekend` | 2.00× |
| `holiday` | 2.50× |

3. **Formula**: `Amount = Hours × Hourly Rate × Multiplier`
4. Hourly rate derived from: `Fixed Gross Salary / (Working Days × Working Hours)`

### Maximum Overtime Cap

`max_overtime_hours_per_month`: 48 hours (configured in PayrollOvertimeRule)

---

## 9. Allowance Exemption Engine

### PayrollAllowanceExemption Model

Controls which allowances are partially or fully exempt from tax and pension:

| Allowance Type | Tax Exempt | Pension Exempt | Max Amount | Max % of Basic |
|----------------|------------|----------------|------------|----------------|
| `transport_regular` | ✅ | ✅ | 600 ETB | — |
| `transport_special` | ✅ | ✅ | 2,200 ETB | 25% |
| `per_diem_no_receipt` | ✅ | ✅ | — | 4% |
| `per_diem_with_receipt` | ✅ | ✅ | — | 2.5% |

### Exemption Calculation Logic

```
1. Look up PayrollAllowanceExemption for the pay type's category
2. If found:
   a. Calculate applicable limit = MIN(max_exempt_amount, basic_salary × max_exempt_percentage)
   b. exempt_portion = MIN(allowance_amount, applicable_limit)
   c. taxable_portion = allowance_amount - exempt_portion
3. If not found:
   a. Use pay type's is_taxable flag directly
```

### Tax Treatment Labels (stored in earnings JSON)

- `fully_taxable` — Entire amount is taxed
- `partially_exempt` — Only amount above exemption limit is taxed
- `fully_exempt` — Entire amount is tax-free
- `not_pensionable` / `partially_exempt` / `fully_pensionable` — Pension treatment

---

## 10. Payroll Calculation Engine

### PayrollCalculationService

The core engine that processes all employees for a given period.

### Full Calculation Pipeline

```
calculatePayroll(PayrollPeriod, companyId)
 │
 ├─ 1. Configuration Governance Check
 │    └── assertModuleReady('payroll') — ensures payroll is properly configured
 │
 ├─ 2. Handle Existing Run (idempotent)
 │    ├── Delete existing draft/calculated run lines
 │    └── Reset PayrollInputs to 'pending'
 │
 ├─ 3. Load Active Profiles
 │    └── EmployeePayrollProfile.where(is_active: true)
 │
 ├─ 4. For Each Employee Profile:
 │    │
 │    ├─ 4a. Load Settings & Rules
 │    │    ├── PayrollSetting
 │    │    ├── PayrollPayType (active, keyed by ID)
 │    │    ├── PayrollAllowanceExemption (active, keyed by type)
 │    │    └── PayrollEmployerContribution (active)
 │    │
 │    ├─ 4b. Aggregate Recurring Earnings
 │    │    ├── profile.earnings() → base salary + allowances
 │    │    └── Calculate Fixed Gross Salary & Base Salary
 │    │
 │    ├─ 4c. Minimum Wage Check
 │    │    └── base_salary >= setting.minimum_wage (throws RuntimeException)
 │    │
 │    ├─ 4d. Calculate Hourly Rate
 │    │    └── Fixed Gross / (working_days × working_hours)
 │    │
 │    ├─ 4e. Process Variable Inputs
 │    │    ├── PayrollInput.where(employee, period dates, status: pending/approved)
 │    │    ├── Auto-calculate overtime amounts if type + hours provided
 │    │    ├── Classify as earnings or variable deductions
 │    │    └── Mark inputs as 'processed', link to run
 │    │
 │    ├─ 4f. Apply Exemption Logic (per earning)
 │    │    ├── Calculate taxable vs non-taxable portions
 │    │    ├── Apply allowance exemption rules
 │    │    ├── Identify bonus tax spreading candidates
 │    │    └── Enrich earnings with tax_treatment labels
 │    │
 │    ├─ 4g. Calculate Pensionable Earnings
 │    │    └── Sum of pay types where is_pensionable = true (minus exemptions)
 │    │
 │    ├─ 4h. Statutory Deduction Engine
 │    │    ├── PAYE (Income Tax) → IncomeTaxRule
 │    │    ├── PENSION → PensionRule
 │    │    └── SHI → SHIRule (if enabled)
 │    │
 │    ├─ 4i. Profile & Variable Deductions
 │    │    ├── Recurring deductions from profile
 │    │    └── Variable deductions from inputs
 │    │
 │    ├─ 4j. Employer Contributions (Dynamic)
 │    │    └── For each contribution: calculate based on base_calculation method
 │    │
 │    ├─ 4k. Cash Compliance Check
 │    │    └── If payment method is 'cash', enforce employee cash limit
 │    │
 │    └─ 4l. Create PayrollRunLine
 │         └── earnings_json, deductions_json, contributions_json, all calculated fields
 │
 ├─ 5. Update Run Totals
 │    └── employee_count, total_gross, total_deductions, total_employer_contributions, total_net_pay
 │
 └─ 6. Approval Workflow Logic
      ├── If require_approval = false → auto 'approved'
      ├── If threshold > 0 and total <= threshold → auto 'approved'
      └── Otherwise → remains 'calculated' (requires manual approval)
```

### PayrollRunLine Structure (per employee)

| Field | Type | Source |
|-------|------|--------|
| `earnings_json` | JSON array | Enriched earnings with tax/pension treatment |
| `deductions_json` | JSON array | Statutory + profile + variable deductions |
| `contributions_json` | JSON array | Employer contributions breakdown |
| `gross_pay` | Decimal | Base + taxable allowances + non-taxable + bonuses |
| `taxable_income` | Decimal | Base salary + taxable allowances |
| `income_tax` | Decimal | From PAYE rule |
| `employee_pension` | Decimal | From Pension rule (EE portion) |
| `employer_pension` | Decimal | From Pension rule (ER portion) |
| `total_deductions` | Decimal | Tax + EE pension + SHI + other |
| `total_employer_contributions` | Decimal | ER pension + ER SHI + custom |
| `net_pay` | Decimal | gross_pay − total_deductions |

---

## 11. Payroll Run Lifecycle

### Status State Machine

```
                    ┌──────────┐
                    │  draft   │ (created, no calculation yet)
                    └────┬─────┘
                         │ calculatePayroll()
                         ▼
                    ┌──────────┐
              ┌────►│calculated│◄──── (recalculation resets here)
              │     └────┬─────┘
              │          │ approve() [manual or auto]
              │          ▼
              │     ┌──────────┐
              │     │ approved │
              │     └────┬─────┘
              │          │ generateJournalEntry()
              │          ▼
              │     ┌──────────┐
              │     │  posted  │ (JE created, linked)
              │     └────┬─────┘
              │          │ recordPayment()
              │          ▼
              │     ┌──────────┐
              │     │   paid   │ (payment JE created)
              │     └────┬─────┘
              │          │ void()
              │          ▼
              └─────┌──────────┐
                    │  voided  │ (reversed, inputs freed)
                    └──────────┘
```

### Key Constraints

- **Idempotent Calculation**: Re-running `calculatePayroll()` for the same period deletes existing non-posted lines and resets inputs
- **Posted runs cannot be deleted**: `deleting` hook only fires for non-posted runs
- **Voiding is available from**: calculated, approved, posted, or paid status
- **Run Number**: Auto-generated via `DocumentSequenceService` or fallback format `PR-YYYY-MM-NNN`

### PayrollRun Model Traits

- `BelongsToTenant` — Multi-tenant scoping
- `Auditable` — Audit logging via `AuditLog` model
- `VoidableTransaction` — Standard void workflow with hooks

---

## 12. GL Journal Entry Posting

### PayrollJournalEntryService

Generates a balanced double-entry journal when a run is approved.

### Journal Entry Structure

```
═══════════════════════════════════════════════════════════
                    DEBIT (Expenses)
═══════════════════════════════════════════════════════════

1. Earnings by Pay Type (aggregated across all employees)
   DR  62100 Salaries & Wages Expense ......... (total basic)
   DR  62600 Housing Allowance Expense ........ (total housing)
   DR  62700 Transport Allowance Expense ...... (total transport)
   DR  62200 Overtime Expense ................. (total overtime)
   DR  62300 Bonus Expense .................... (total bonuses)
   ... etc. (each PayType.expense_account_id)

2. Employer Contributions
   DR  62400 Employer Pension Expense (11%) ... (total ER pension)
   DR  62910 Employer SHI Expense (1.5%) ...... (total ER SHI)

═══════════════════════════════════════════════════════════
                   CREDIT (Liabilities)
═══════════════════════════════════════════════════════════

3. Statutory Liabilities
   CR  21230 Income Tax Payable (PAYE) ........ (total PAYE)
   CR  21320 Pension Payable - Employee (7%) .. (total EE pension)
   CR  21330 Pension Payable - Employer (11%) . (total ER pension)
   CR  21340 SHI Payable - Employee (1.5%) .... (total EE SHI)
   CR  21350 SHI Payable - Employer (1.5%) .... (total ER SHI)

4. Other Deduction Liabilities (dynamic per type)
   CR  21360 Other Payroll Deductions ......... (loans, penalties)

5. Net Salaries Payable (Clearing)
   CR  21310 Net Salaries Payable (Accrual) ... (total net pay)

═══════════════════════════════════════════════════════════
          BALANCE: Total Debits = Total Credits
═══════════════════════════════════════════════════════════
```

### Pre-Posting Validation

1. **PayrollAccountMappingValidator.validate()** ensures:
   - All used PayTypes have `expense_account_id` mapped
   - `PayrollAccountMapping` exists with all required fields set
   - SHI accounts are mapped if SHI is enabled
   - All referenced accounts exist in the Chart of Accounts

2. **PostingGuardService.assertCanPost()** — Ensures the accounting period is open

3. **Rounding Adjustment** — If total debits/credits differ by ≤ 0.05, the last credit line is adjusted to balance

### Post-Posting

- Run status → `'posted'`
- `journal_entry_id` → FK linked to new JournalEntry
- `posted_at` → Timestamp

---

## 13. Payment Disbursement Workflow

### PayrollPaymentService

Records the actual salary payment after the payroll JE is posted.

### Payment Flow

```
recordPayment(PayrollRun, bankAccountId, paymentDate, reference)
 │
 ├─ 1. Validate run status = 'posted'
 ├─ 2. Validate no existing payment JE
 │
 ├─ 3. Cash Compliance Check
 │    ├── For each employee paid by cash method:
 │    │    └── CashComplianceService.assertEmployeeCashLimit()
 │    └── Blocks if any employee exceeds statutory cash limit
 │
 ├─ 4. Aggregate Disbursements by Bank Account
 │    ├── Group net pay by employee's PaymentMethod.bank_account_id
 │    └── Fallback to provided bankAccountId if no method configured
 │
 └─ 5. Create Payment Journal Entry
      │
      ├── DR  21310 Net Salaries Payable (Total) ... (clearing the accrual)
      │
      ├── CR  11131 CBE Bank ....................... (bank transfer portion)
      ├── CR  11110 Cash on Hand ................... (cash portion, if any)
      └── CR  [Other Bank] ......................... (multi-bank, if configured)

 → Run status → 'paid'
 → payment_journal_entry_id → FK to payment JE
 → paid_at → timestamp
```

### Multi-Bank Support

The system supports paying different employees from different bank accounts:
- Each `PayrollPaymentMethod` can be linked to a different `Account` (bank_account_id)
- The payment JE credits each bank account proportionally

---

## 14. Voiding & Reversal

### VoidableTransaction Trait

The `PayrollRun` model uses the `VoidableTransaction` concern which provides a standard void workflow.

### Void Process

```
void(PayrollRun)
 │
 ├─ 1. validateVoiding()
 │    ├── Cannot void if already 'voided'
 │    └── Must be in: calculated, approved, posted, or paid
 │
 ├─ 2. Set status → 'voided'
 │
 └─ 3. afterVoided()
      │
      ├── If payment JE exists (was 'paid'):
      │    └── JournalEntryService.reverse(paymentJE) — creates reversal JE
      │
      ├── Reset all PayrollInputs:
      │    └── status → 'approved', payroll_run_id → null
      │
      └── Stamp audit fields:
           ├── voided_at → now()
           ├── voided_by → auth user
           └── void_reason → from request
```

### On Delete (not void)

If a PayrollRun is deleted (not voided):
1. All `PayrollInput` records are reset to `status: 'approved'`, `payroll_run_id: null`
2. The linked `JournalEntry` (if any) is also deleted

---

## 15. Audit Trail & History Tracking

### Three Observer System

#### PayrollProfileObserver

Triggers on `EmployeePayrollProfile` create/update/delete:
- Records a full snapshot including all recurring components (as JSON)
- Detects significant changes: `is_active`, `payroll_frequency`, `payment_method_id`, bank details, TIN, pension number
- Determines change type: `created`, `activated`, `deactivated`, `profile_update`

#### EmployeeRecurringComponentObserver

Triggers on `EmployeeRecurringComponent` create/update/delete:
- Records when salary components are added, modified, or removed
- Captures amount changes with old/new values
- Full profile snapshot (earnings + deductions JSON) on every event
- Change types: `component_added`, `component_updated`, `component_removed`

#### PayrollInputObserver

Triggers on `PayrollInput` create/update/delete:
- Records variable pay additions, modifications, removals
- Links to the specific `payroll_input_id` and `payroll_pay_type_id`
- Captures variable amount and type
- Change types: `variable_pay_added`, `variable_pay_updated`, `variable_pay_removed`

### PayrollProfileHistory Model

Each history record contains:

```
┌───────────────────────────────────────────────────────┐
│                   IDENTITY FIELDS                     │
├───────────────────────────────────────────────────────┤
│ company_id, employee_payroll_profile_id, employee_id  │
│ changed_by_user_id, change_type, effective_date       │
│ change_reason, source_page                            │
├───────────────────────────────────────────────────────┤
│                   SNAPSHOT FIELDS                     │
├───────────────────────────────────────────────────────┤
│ is_active, payroll_frequency, payment_method_id       │
│ bank_account_number, bank_name                        │
│ tax_identification_number, pension_account_number     │
├───────────────────────────────────────────────────────┤
│                   PAY STRUCTURE                       │
├───────────────────────────────────────────────────────┤
│ pay_components_json (full earnings snapshot)           │
│ deduction_components_json (full deductions snapshot)   │
│ total_earnings (calculated)                            │
│ total_deductions (calculated with approx tax/pension)  │
│ estimated_net_pay (calculated)                         │
├───────────────────────────────────────────────────────┤
│                   VARIABLE INPUT                     │
├───────────────────────────────────────────────────────┤
│ payroll_input_id, payroll_pay_type_id                  │
│ variable_amount, variable_type                         │
└───────────────────────────────────────────────────────┘
```

### Estimated Net Pay Calculation (History)

The `calculateTotals()` method on `PayrollProfileHistory` uses an **approximation** of Ethiopian tax brackets for the history snapshot (hardcoded brackets from Proclamation No. 979/2016 as fallback):

```
Earnings = Sum of pay_components_json amounts + variable earnings
Pension = pensionable_income × 7%
Income Tax = approximateIncomeTax(taxable_income) using bracket formula
Other Deductions = Sum of deduction_components_json amounts
Total Deductions = pension + tax + other
Estimated Net Pay = earnings - total_deductions
```

---

## 16. Statutory Reporting

### StatutoryReportService

Two key reports for Ethiopian government agencies:

#### POESSA (Pension Report)

For the Private Organization Employees' Social Security Agency:

| Column | Source |
|--------|--------|
| Full Name | employee.full_name |
| TIN | profile.tax_identification_number |
| Basic Salary | Sum of earnings where category = 'base_salary' |
| Employee Share (7%) | payrollRunLine.employee_pension |
| Employer Share (11%) | payrollRunLine.employer_pension |
| Total Contribution | employee_pension + employer_pension |

#### ERCA (Tax Report)

For the Ethiopian Revenues & Customs Authority:

| Column | Source |
|--------|--------|
| Full Name | employee.full_name |
| TIN | profile.tax_identification_number |
| Gross Taxable Income | payrollRunLine.taxable_income |
| Tax Amount | payrollRunLine.income_tax |

### PDF Reports

Via `PayrollReportService`:
- **Individual Payslip** — Per employee, A4 portrait
- **Payroll Summary** — All employees, A4 landscape
- Both use DomPDF with `payroll.reports.payslip` and `payroll.reports.payroll_summary` Blade views
- Runtime enrichment for old payroll runs that may have missing `name` fields in JSON

---

## 17. Payroll Setup Service (Auto-Seeding)

### PayrollSetupService.setup()

Called when a company is created (from `CreateCompany` Filament page). Seeds the entire payroll infrastructure in order:

```
setup(Company)
 │
 ├─ 1. seedPayrollAccounts()
 │    ├── Create 11 Expense accounts (codes 61200-62910)
 │    ├── Create 7 Liability accounts (codes 21230-21360)
 │    └── Ensure all PayTypes have mapped expense accounts
 │
 ├─ 2. seedTaxBrackets()
 │    └── 6 Ethiopian progressive PAYE brackets (2025 amendment)
 │
 ├─ 3. seedStatutoryRules()
 │    ├── Income Tax rule (brackets JSON)
 │    ├── Pension rule (7% EE / 11% ER)
 │    └── SHI rule (1.5% / 1.5%)
 │
 ├─ 4. seedPayTypes()
 │    └── 9 default pay types (BASIC, HOUSING, TRANSPORT, etc.)
 │
 ├─ 5. seedDeductions()
 │    └── 3 statutory deductions (PAYE, EE_PENSION, EE_SHI)
 │
 ├─ 6. seedEmployerContributions()
 │    └── 2 employer contributions (ER_PENSION 11%, ER_SHI 1.5%)
 │
 ├─ 7. seedPaymentMethods()
 │    └── 3 methods: Bank Transfer (default), Cash, Cheque
 │
 ├─ 8. seedOvertimeRules()
 │    └── 4 multipliers + 48hr monthly cap
 │
 ├─ 9. seedAllowanceExemptions()
 │    └── 4 rules: transport_regular, transport_special, per_diem×2
 │
 └─ 10. seedDefaultSettings()
      ├── PayrollSetting (monthly, ETB, standard defaults)
      └── PayrollAccountMapping (all 13+ account mappings)
```

All seeding uses `updateOrCreate` to be idempotent.

---

## 18. Account Mapping & Chart of Accounts

### PayrollAccountMapping (13 GL Account Links)

| Field | Account Code | Account Name | Side |
|-------|-------------|---------------|------|
| `salaries_expense_account_id` | 62100 | Salaries & Wages Expense | Debit |
| `employer_pension_expense_account_id` | 62400 | Employer Pension Expense (11%) | Debit |
| `shi_expense_account_id` | 62910 | Employer SHI Expense (1.5%) | Debit |
| `housing_allowance_expense_account_id` | 62600 | Housing Allowance Expense | Debit |
| `transport_allowance_expense_account_id` | 62700 | Transport Allowance Expense | Debit |
| `overtime_expense_account_id` | 62200 | Overtime Expense | Debit |
| `net_salaries_payable_account_id` | 21310 | Net Salaries Payable | Credit |
| `income_tax_payable_account_id` | 21230 | Income Tax Payable (PAYE) | Credit |
| `employee_pension_payable_account_id` | 21320 | Pension Payable - Employee 7% | Credit |
| `employer_pension_payable_account_id` | 21330 | Pension Payable - Employer 11% | Credit |
| `shi_employee_payable_account_id` | 21340 | SHI Payable - Employee 1.5% | Credit |
| `shi_employer_payable_account_id` | 21350 | SHI Payable - Employer 1.5% | Credit |
| `default_bank_account_id` | 11131 | CBE Bank | Credit (payment) |

### Dynamic Account Assignment

If a new PayType is created without an expense account, the `ensureAllPayTypesHaveAccounts()` method in `PayrollSetupService` auto-creates an expense account in the 61xxx range with the name pattern: `{PayType Name} Expense`.

---

## 19. Configuration Validation

### PayrollValidationService

Two-layer validation system:

#### Full Validation (validatePayrollConfiguration)

Checks 5 areas and returns errors + warnings:

1. **General Settings** — PayrollSetting exists and is enabled
2. **Account Mappings** — All 7 required GL accounts mapped; SHI accounts if SHI enabled
3. **Statutory Rules** — Active income_tax and pension rules exist
4. **Pay Types** — BASIC pay type exists and is active; orphaned exemption warnings
5. **Deductions** — PAYE deduction exists and is active

Returns:
```php
[
    'is_valid'  => bool,
    'errors'    => [...],    // Blocking issues
    'warnings'  => [...],    // Non-blocking flags
    'status'    => 'ready' | 'incomplete'
]
```

#### Configuration Status (getConfigurationStatus)

Progress tracker returning completion percentage:

```php
[
    'steps' => [
        'general'        => true/false,
        'accounts'       => true/false,
        'pay_types'      => true/false,
        'deductions'     => true/false,
        'statutory'      => true/false,
        'shi_compliance' => true/false,
    ],
    'percentage'  => 0-100,
    'is_complete' => bool,
]
```

### ConfigurationGovernanceService

Pre-flight check before any payroll run: `assertModuleReady($companyId, 'payroll', 'Payroll run')` — throws an exception if configuration is incomplete.

---

## 20. Filament UI Layer

### Navigation Structure

```
Payroll (Cluster)
 ├── Settings
 │    ├── Pay Types (PayrollPayTypeResource)
 │    ├── Payment Methods (PayrollPaymentMethodResource)
 │    ├── Tax Brackets (PayrollTaxBracketResource)
 │    ├── Deductions (PayrollDeductionResource)
 │    ├── Employer Contributions
 │    ├── Allowance Exemptions
 │    ├── Overtime Rules
 │    ├── Statutory Rules
 │    ├── Account Mappings (ManagePayrollAccountMappings page)
 │    └── General Settings (ManagePayrollSettings page)
 │
 ├── Operations
 │    ├── Employees (EmployeeResource)
 │    ├── Payroll Profiles (PayrollProfileResource)
 │    │    ├── Edit Profile (salary components, deductions repeater)
 │    │    ├── View Profile (with HistoryRelationManager)
 │    │    └── List Profiles (with search, filters)
 │    ├── Variable Inputs (PayrollInputResource)
 │    │    ├── Create Input (OT, bonus, deduction)
 │    │    ├── Edit Input
 │    │    └── List Inputs (with status filters)
 │    ├── Payroll Periods (PayrollPeriodResource)
 │    ├── Payroll Runs (PayrollRunResource)
 │    │    ├── Create Run (select period, calculate)
 │    │    ├── View Run (with PayrollRunLinesTable widget)
 │    │    ├── Approve/Reject actions
 │    │    ├── Post to GL action
 │    │    ├── Record Payment action
 │    │    ├── Void action
 │    │    └── Download Payslips / Summary PDF
 │    └── Payroll Sheets (PayrollSheetResource - read-only view)
 │
 ├── Reports
 │    ├── Profile History (PayrollProfileHistoryResource)
 │    ├── Pension Report (POESSA)
 │    └── Tax Report (ERCA)
 │
 └── Widgets
      ├── PayrollKpiWidget (dashboard)
      └── ManagerApproverKpiWidget
```

### Filament Resources Identified

| Resource | Model | Features |
|----------|-------|----------|
| `PayrollRunResource` | PayrollRun | CRUD, View with lines, approve/reject/post/pay/void actions |
| `PayrollInputResource` | PayrollInput | CRUD with OT/bonus forms |
| `PayrollPeriodResource` | PayrollPeriod | CRUD with date management |
| `PayrollProfileResource` | EmployeePayrollProfile | Edit with salary repeater, view with history |
| `PayrollPayTypeResource` | PayrollPayType | CRUD under Payroll cluster |
| `PayrollPaymentMethodResource` | PayrollPaymentMethod | CRUD under Payroll cluster |
| `PayrollTaxBracketResource` | PayrollTaxBracket | Manage page under Payroll cluster |
| `PayrollDeductionResource` | PayrollDeduction | CRUD under Payroll cluster |
| `PayrollProfileHistoryResource` | PayrollProfileHistory | Read-only under Payroll cluster |
| `PayrollSheetResource` | PayrollRunLine | Read-only view |
| `EmployeeResource` | Employee | Full employee management |

---

## 21. Security & Permissions

### Policy Classes

| Policy | Model | Controls |
|--------|-------|----------|
| `PayrollRunPolicy` | PayrollRun | Create, view, edit, delete, approve, reject, post, pay, void |
| `PayrollPeriodPolicy` | PayrollPeriod | CRUD on periods |
| `PayrollInputPolicy` | PayrollInput | CRUD on variable inputs |
| `EmployeePayrollProfilePolicy` | Profile | View/edit salary profiles |
| `PayrollPayTypePolicy` | PayType | Settings management |
| `PayrollPaymentMethodPolicy` | PaymentMethod | Settings management |
| `PayrollTaxBracketPolicy` | TaxBracket | Settings management |
| `PayrollDeductionPolicy` | Deduction | Settings management |
| `PayrollEmployerContributionPolicy` | ER Contribution | Settings management |
| `PayrollAllowanceExemptionPolicy` | Exemption | Settings management |
| `PayrollProfileHistoryPolicy` | History | View-only audit data |
| `EmployeePolicy` | Employee | Full employee CRUD |

### Role Catalog Entries

`payroll_admin`, `payroll_run_approver`, and standard ERP roles (`admin`, `owner`, `accountant`) control access.

---

## 22. Compliance Features

### Cash Compliance

`CashComplianceService.assertEmployeeCashLimit()` is called at two points:
1. During payroll calculation (for employees paid by cash method)
2. During payment recording (for all cash disbursements)

Enforces Ethiopian statutory limits on cash payments for tax deductibility.

### Period Posting Guard

`PostingGuardService.assertCanPost()` ensures payroll journal entries can only be posted to open accounting periods.

### Minimum Wage Enforcement

During calculation, if an employee's base salary is below `PayrollSetting.minimum_wage`, a `RuntimeException` is thrown, halting the entire payroll run.

### Secondary Employment Tax

If `SettingCompany.payroll_secondary_employment_enabled` is true and an employee's profile has `is_secondary_employment = true`, the system applies a flat income tax rate (default 35%) instead of progressive brackets.

### Retroactive Adjustment

`PayrollRetroactiveAdjustmentService.recalculateFromJuly2025()` handles the Ethiopian July 2025 tax bracket transition by recalculating historic payroll runs using the new brackets. Returns:
```php
['runs' => int, 'lines' => int, 'total_tax_delta' => float]
```

### Configuration Governance

Before any payroll run starts, `ConfigurationGovernanceService.assertModuleReady('payroll')` validates that all required configuration is in place. This prevents partial or incorrect payroll processing.

---

## Summary: End-to-End Payroll Flow

```
1. SETUP (one-time per company)
   PayrollSetupService.setup() → Seeds accounts, rules, types, settings

2. CONFIGURE (admin)
   ├── Verify PayrollValidationService.getConfigurationStatus()
   ├── Customize tax brackets, pay types, exemptions
   └── Map GL accounts in PayrollAccountMapping

3. EMPLOYEE ONBOARD
   ├── Create Employee → Auto-creates EmployeePayrollProfile (inactive)
   ├── Activate profile, set payment method, bank details
   └── Add recurring components (base salary, allowances, deductions)

4. PERIOD PREPARATION
   ├── Create/auto-generate PayrollPeriod
   └── Enter PayrollInputs (overtime, bonuses, penalties)

5. PAYROLL RUN
   ├── calculatePayroll() → Status: 'calculated'
   ├── Review payslips and run summary
   ├── Approve → Status: 'approved'
   ├── generateJournalEntry() → Status: 'posted' (JE created)
   └── recordPayment() → Status: 'paid' (payment JE created)

6. REPORTING
   ├── Download individual payslips (PDF)
   ├── Download payroll summary (PDF)
   ├── Generate POESSA pension report
   └── Generate ERCA tax report

7. ERROR CORRECTION
   ├── Void run → reverses JEs, frees inputs
   └── Recalculate → new run for same period
```

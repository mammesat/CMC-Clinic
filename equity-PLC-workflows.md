# PLC Equity Module — Architectural Workflows & Statutory Compliance Reference

> **Source:** Live codebase (models, services, migrations, Filament resources)
> **Scope:** Private Limited Company (PLC), Single-Member PLC, and Share Company (SC)
> **Purpose:** Integration reference for equity lifecycle implementation

---

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [Company Type Gating & Entity Classification](#2-company-type-gating--entity-classification)
3. [Ethiopian Statutory Compliance Framework](#3-ethiopian-statutory-compliance-framework)
4. [Chart of Accounts — Equity Ledger Map](#4-chart-of-accounts--equity-ledger-map)
5. [Data Model Topology](#5-data-model-topology)
6. [Service Layer Architecture](#6-service-layer-architecture)
7. [Workflow 1: Share Class Configuration](#7-workflow-1-share-class-configuration)
8. [Workflow 2: Shareholder Registration](#8-workflow-2-shareholder-registration)
9. [Workflow 3: Share Issuance (Primary Market)](#9-workflow-3-share-issuance-primary-market)
10. [Workflow 4: Share Transfer (Secondary Market)](#10-workflow-4-share-transfer-secondary-market)
11. [Workflow 5: Treasury Stock (Buyback & Reissue)](#11-workflow-5-treasury-stock-buyback--reissue)
12. [Workflow 6: Dividend Lifecycle](#12-workflow-6-dividend-lifecycle)
13. [Workflow 7: Period Close & Legal Reserve Appropriation](#13-workflow-7-period-close--legal-reserve-appropriation)
14. [Workflow 8: Equity Compliance Dashboard Alerts](#14-workflow-8-equity-compliance-dashboard-alerts)
15. [Workflow 9: Statement of Changes in Equity](#15-workflow-9-statement-of-changes-in-equity)
16. [Workflow 10: Retained Earnings Statement](#16-workflow-10-retained-earnings-statement)
17. [Filament UI Surface Map](#17-filament-ui-surface-map)
18. [Security & Permission Model](#18-security--permission-model)

---

## 1. System Architecture Overview

The Equity Module operates as a **dual-ledger** system:

```
┌─────────────────────────────────────────────────────────────────┐
│                     EQUITY MODULE ARCHITECTURE                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────────────────────────────┐  │
│  │  Share Ledger │    │  General Ledger (Double-Entry)       │  │
│  │  (Qty-based)  │    │  (Amount-based)                      │  │
│  │              │    │                                      │  │
│  │  ShareLedger  │◄──►│  JournalEntry + JournalEntryLine    │  │
│  │  model        │    │  via ShareTransactionService         │  │
│  └──────┬───────┘    └──────────────┬───────────────────────┘  │
│         │                           │                           │
│         ▼                           ▼                           │
│  ┌──────────────┐    ┌──────────────────────────────────────┐  │
│  │  Cap Table    │    │  EquityMovement                      │  │
│  │  (Ownership)  │    │  (Statement of Changes in Equity)    │  │
│  └──────────────┘    └──────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  EquityComplianceService (Ethiopian Commercial Code)      │  │
│  │  → Minimum Capital · Capital Adequacy · Legal Reserve     │  │
│  │  → Nominee Check · SC Board · SC Audit · SC Shareholders │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

**Control Flow Pipeline — every equity-altering action follows this path:**

```
User Action → Filament Page → Service Layer → PostingGuardService.assertCanPost()
                                            → DB::transaction {
                                                ShareLedger (qty)
                                                JournalEntry + Lines (money)
                                                EquityMovement (reporting)
                                                ShareClass.increment/decrement
                                              }
                                            → EquityComplianceService.runAll()
```

**Compliance Check Trigger Points — `EquityComplianceService.runAll()` runs automatically after:**
- Share issuance (cash or bonus)
- Share buyback
- Dividend approval and payment
- Journal entry posting (for share-based companies)
- Period close

---

## 2. Company Type Gating & Entity Classification

### Entity Types (2021 Ethiopian Commercial Code)

| Company Type | Equity Module | Share-Based | Statutory Basis |
|---|---|---|---|
| `Sole Proprietorship` | Owner's Capital (30100/30200) | ❌ No | N/A |
| `Private Limited Company` (PLC) | Full Equity Module | ✅ Yes | Art. 510 et seq. |
| `Single-Member PLC` | Full Equity + Nominee Enforcement | ✅ Yes | Art. 510 (single-member variant) |
| `Share Company` (SC) | Full Equity + SC Compliance Suite | ✅ Yes | Art. 304 et seq. |

### Gating Methods on `Company` Model

| Method | Purpose | Returns `true` For |
|---|---|---|
| `isShareBased()` | **Canonical equity gating check** — controls all Equity nav/page visibility | PLC, Single-Member PLC, SC |
| `isPLC()` | Checks PLC family membership (excludes SC) | PLC, Private Limited Company, Single-Member PLC |
| `isSC()` | Checks Share Company status | SC only |
| `isSingleMemberPLC()` | Single-member variant check | Single-Member PLC only |
| `requiresBoardOfDirectors()` | SC governance requirement (Art. 338) | SC only |
| `requiresExternalAudit()` | SC audit requirement (Art. 381) | SC only |
| `requiresIFRS()` | Full IFRS compliance (AABE classification) | SC only |
| `needsNomineeInfo()` | Single-Member PLC nominee check | Single-Member PLC with empty `nominee_name` |

### Sole Proprietorship — Separate Service Layer

Sole Proprietorship uses `OwnerCapitalService` (not the PLC equity services):

| Operation | Journal Entry | Accounts |
|---|---|---|
| Capital Contribution | DR Bank, CR Owner's Capital (30100) | PostingGuard enforced |
| Drawing/Withdrawal | DR Owner's Drawings (30200), CR Bank | PostingGuard enforced |
| Year-End Close | DR Owner's Capital (30100), CR Owner's Drawings (30200) | Closes drawings to capital |

---

## 3. Ethiopian Statutory Compliance Framework

### 3.1 Master Compliance Matrix — All Integrated Articles

The following Ethiopian legal requirements are **actively enforced** in the codebase through automated validations & dashboard alerts:

| # | Legal Requirement | Article / Proclamation | Enforcement Point | Applies To |
|---|---|---|---|---|
| 1 | **Minimum Capital — PLC** | **Art. 510, Commercial Code 2021** | `EquityComplianceService::checkMinimumCapital()` — Dashboard Alert | PLC, SM-PLC |
| 2 | **Minimum Capital — SC** | **Art. 304, Commercial Code 2021** | `EquityComplianceService::checkMinimumCapital()` — Dashboard Alert | SC |
| 3 | **Capital Adequacy / EGM Trigger** | **Art. 473, Commercial Code 2021** | `EquityComplianceService::checkCapitalAdequacy()` — Dashboard Alert + Dividend block | PLC, SC |
| 4 | **Legal Reserve Appropriation** | **Art. 452, Commercial Code 2021** | `PeriodCloseService::applyLegalReserveAppropriation()` — Automated at year-end | PLC, SC |
| 5 | **Legal Reserve Fulfillment** | **Art. 452, Commercial Code 2021** | `EquityComplianceService::checkLegalReserveStatus()` — Dashboard Alert + Dividend block | PLC, SC |
| 6 | **Minimum Par Value** | **Art. 452, Commercial Code 2021** | `ShareTransactionService::validateShareIssuance()` — Hard block on issuance | PLC, SC |
| 7 | **No Below-Par Issuance** | **Art. 452, Commercial Code 2021** | `ShareTransactionService::validateShareIssuance()` — Hard block on issuance | PLC, SC |
| 8 | **Single-Member PLC Nominee** | **Commercial Code 2021 (SM-PLC provisions)** | `EquityComplianceService::checkNomineeRequirement()` — Dashboard Alert | SM-PLC |
| 9 | **Single-Member PLC — One Shareholder** | **Commercial Code 2021 (SM-PLC provisions)** | `ShareTransactionService::validateShareIssuance()` — Hard block on issuance | SM-PLC |
| 10 | **SC Minimum Shareholders (≥ 5)** | **Art. 304, Commercial Code 2021** | `EquityComplianceService::checkShareholderCount()` — Dashboard Alert | SC |
| 11 | **SC Board of Directors (3-13 members)** | **Art. 338-340, Commercial Code 2021** | `EquityComplianceService::checkBoardComposition()` — Dashboard Alert | SC |
| 12 | **SC Non-Shareholder Director Ratio (≤ 1/3)** | **Art. 338-340, Commercial Code 2021** | `EquityComplianceService::checkBoardComposition()` — Dashboard Alert | SC |
| 13 | **SC External Audit Requirement** | **Art. 381, Commercial Code 2021** | `EquityComplianceService::checkExternalAuditRequirement()` — Dashboard Alert | SC |
| 14 | **SC IFRS Compliance** | **AABE Directive (Public Interest Entity classification)** | `Company::requiresIFRS()` — Reporting classification gate | SC |
| 15 | **Dividend WHT (10%)** | **Income Tax Proclamation No. 979/2016, Art. 53** | `DividendPaymentService::calculateShareholderDividends()` — Auto-calculated | PLC, SC |
| 16 | **Tax Clearance Before Dividend** | **Income Tax Proclamation No. 979/2016** | `DividendValidationService::validateDeclaration()` check #8 — Dividend block | PLC, SC |
| 17 | **Solvency Test Before Dividend** | **Commercial Code 2021 (general solvency provisions)** | `DividendValidationService::performSolvencyTest()` — Dividend block | PLC, SC |
| 18 | **Distributable Profit Restriction** | **Commercial Code 2021 (realized profit provisions)** | `DividendValidationService::getDistributableProfit()` — Dividend block | PLC, SC |
| 19 | **Undistributed Profit Tax** | **Income Tax Proclamation No. 979/2016, Art. 61** | `UndistributedProfitAssessment` model — Tax tracking | PLC, SC |

### 3.2 Minimum Capital Thresholds

| Company Type | Minimum Capital (ETB) | Article | Default in System | Configurable Via |
|---|---|---|---|---|
| PLC / Single-Member PLC | **15,000** | Art. 510 | `EquityConfigurationService::getThresholds()` | `SettingCompany.minimum_capital` |
| Share Company (SC) | **50,000** | Art. 304 | `EquityConfigurationService::getThresholds()` | `SettingCompany.minimum_capital` |

### 3.3 Capital Adequacy — Art. 473

| Condition | Trigger | Required Action |
|---|---|---|
| Accumulated losses > **50%** of share capital | Dashboard **DANGER** alert | Extraordinary General Meeting (EGM) must be convened |
| *While breached:* | Dividend declaration **BLOCKED** | Directors face legal liability if no EGM held |

**Implementation:** `EquityComplianceService::checkCapitalAdequacy()` computes accumulated losses as `max(0, abs(min(0, RE_balance)))` and compares against `share_capital × threshold%`.

**EGM Recording:** `EgmLog` model captures `meeting_date`, `agenda_type`, `outcome`, `attendees_count`, `resolution_text`, and `board_resolution_number`.

### 3.4 Legal Reserve — Art. 452

| Parameter | Value | Configurable |
|---|---|---|
| Annual appropriation rate | **5%** of annual net profit | `SettingCompany.legal_reserve_rate` |
| Target cap | **10%** of share capital | `SettingCompany.legal_reserve_cap_rate` |
| Minimum par value per share | **ETB 100** | `SettingCompany.minimum_par_value` |

**Automated Enforcement:**
- **At year-end close:** `PeriodCloseService::applyLegalReserveAppropriation()` automatically calculates and posts the reserve transfer journal entry: `DR Retained Earnings (35000), CR Legal Reserve (32000)`
- **Dashboard Alert:** `EquityComplianceService::checkLegalReserveStatus()` shows a **WARNING** alert with progress percentage until the 10% target is reached
- **Dividend Block:** `DividendValidationService` check #3 blocks dividend declarations if the legal reserve obligation has not been met

### 3.5 Share Company (SC) Specific Compliance

#### Art. 304 — Minimum Shareholders

| Check | Threshold | Enforcement |
|---|---|---|
| Active shareholders with positive share balance | **≥ 5** (configurable via `shareholder_min_count`) | Dashboard **DANGER** alert via `checkShareholderCount()` |

**Calculation:** Queries `ShareLedger` grouped by `shareholder_id`, filters for `SUM(credit_shares - debit_shares) > 0`.

#### Art. 338-340 — Board of Directors

| Check | Threshold | Enforcement |
|---|---|---|
| Total directors | **3 to 13** (configurable via `board_min_size` / `board_max_size`) | Dashboard **WARNING** alert |
| Non-shareholder director ratio | **≤ 33%** (configurable via `non_shareholder_director_max_ratio`) | Dashboard **WARNING** alert |

**Data Source:** `BoardMember` model with `active()` and `directors()` scopes. Distinguishes `shareholder_director` vs `non_shareholder_director` member types.

#### Art. 381 — External Audit

| Check | Threshold | Enforcement |
|---|---|---|
| Time since last external audit | **≤ 16 months** | Dashboard **WARNING** alert |
| No audit on record | Missing `last_external_audit_date` | Dashboard **WARNING** alert |

**Data Source:** `SettingCompany.last_external_audit_date`. Share Companies are classified as Public Interest Entities per AABE directive.

### 3.6 Dividend Withholding Tax — Income Tax Proclamation No. 979/2016

| Parameter | Value | Source |
|---|---|---|
| Standard WHT rate | **10%** on gross dividend | Art. 53, Proclamation 979/2016 |
| WHT per shareholder | `gross_amount × withholding_tax_rate` | `DividendPaymentService::calculateShareholderDividends()` |
| Net payout | `gross_amount − withholding_tax_amount` | Stored on each `DividendPayment` record |

**Journal Entries:**
- Declaration: `DR Retained Earnings (35000), CR Dividends Payable (21500) [net], CR WHT Payable (22400) [tax]`
- Remittance: `DR WHT Payable (22400), CR Cash/Bank`

### 3.7 Undistributed Profit Tax — Income Tax Proclamation No. 979/2016, Art. 61

| Field | Description |
|---|---|
| `origin_tax_year` | Year the profit was earned |
| `undistributed_profit_amount` | Amount of profit not distributed as dividend |
| `deadline_date` | Deadline for distribution or reinvestment |
| `tax_rate` / `tax_amount` | Applicable tax rate and computed obligation |
| `status` | `open` → `assessed` → `paid` / `reinvested_at` / `distributed_at` |

**Model:** `UndistributedProfitAssessment` with `scopeOpen()` and `scopeExpired()` for tracking overdue obligations.

### 3.8 Eight-Point Dividend Validation Gate

**Source:** `DividendValidationService::validateDeclaration()` — All 8 checks must pass before a dividend can be approved:

| # | Validation | Statutory Basis | Fail Action |
|---|---|---|---|
| 1 | Retained Earnings must be **positive** | Commercial Code 2021 — realized profits only | Hard block |
| 2 | Dividend ≤ **Distributable Profit** (RE − Legal Reserve − Revaluation Reserve − AOCI) | Commercial Code 2021 | Hard block |
| 3 | **Legal Reserve** obligation fulfilled (account 32000 ≥ 10% of account 31000) | **Art. 452**, Commercial Code 2021 | Hard block |
| 4 | **Solvency Test** passes (Total Assets − Dividend ≥ Total Liabilities) | Commercial Code 2021 | Hard block |
| 5 | No **Unpaid Share Capital** exists (issuances without bank_account_id) | Commercial Code 2021 | Hard block |
| 6 | Total Equity ≥ **Minimum Capital Floor** (ETB 15K PLC / ETB 50K SC) | **Art. 510 / Art. 304**, Commercial Code 2021 | Hard block |
| 7 | Accumulated losses **< 50%** of share capital (capital adequacy) | **Art. 473**, Commercial Code 2021 | Hard block |
| 8 | No outstanding **Tax Obligations** with `status='due'` past due date | **Income Tax Proclamation No. 979/2016** | Hard block |

**Override Path:** If all checks fail but board/management authorizes:
```
ComplianceOverrideService.record(
  context: 'dividend_approval',
  reason: <user-supplied justification>,
  violations: [array of failed check messages],
  subject_type: Dividend::class,
  subject_id: dividend->id,
  requested_by_user_id: auth()->id(),
  approved_by_user_id: auth()->id()
)
```

---

## 4. Chart of Accounts — Equity Ledger Map

### PLC/SC Equity Accounts (auto-created by `ChartOfAccountsService::createDefaultAccounts()`)

| Code | Account Name | Normal Balance | Detail Type | System | Condition |
|---|---|---|---|---|---|
| `31000` | Common Stock | Credit | Common Stock | No | PLC/SC/SM-PLC |
| `31100` | Preferred Stock | Credit | Preferred Stock | No | PLC/SC/SM-PLC |
| `31200` | Share Premium – Common | Credit | Paid-in Capital or Surplus | No | PLC/SC/SM-PLC |
| `31210` | Share Premium – Preferred | Credit | Paid-in Capital or Surplus | No | PLC/SC/SM-PLC |
| `31300` | Additional Paid-in Capital (APIC) | Credit | Paid-in Capital or Surplus | No | PLC/SC/SM-PLC |
| `31500` | Treasury Stock | **Debit** (Contra-Equity) | Treasury Stock | No | PLC/SC/SM-PLC |
| `31600` | Accumulated Other Comprehensive Income (AOCI) | Credit | AOCI | No | PLC/SC/SM-PLC |
| `31700` | Dividend Distribution | Credit | Dividends Paid / Owner Draw | No | PLC/SC/SM-PLC |
| `32000` | Legal Reserve | Credit | Retained Earnings | No | PLC/SC/SM-PLC |
| `35000` | Retained Earnings | Credit | Retained Earnings | ✅ Yes | All types |
| `39000` | Income Summary | Credit | Income Summary | ✅ Yes | All types |
| `39900` | Opening Balance Equity | Credit | Opening Balance Equity | ✅ Yes | All types |
| `21500` | Dividends Payable | Credit | Dividends Payable | ✅ Yes | PLC/SC/SM-PLC |
| `22400` | Dividend Withholding Tax Payable | Credit | Dividend WHT Payable | ✅ Yes | PLC/SC/SM-PLC |

### Sole Proprietorship Equity Accounts

| Code | Account Name | Normal Balance | Condition |
|---|---|---|---|
| `30100` | Owner's Equity/Capital | Credit | SP only |
| `30200` | Owner's Drawings | **Debit** (Contra-Equity) | SP only |

### Configurable Account Resolution

**Source:** `EquityConfigurationService::account()` — three-tier fallback:

```
1. SettingCompany field (e.g., equity_share_capital_account_id)
2. Account::where('account_code', defaultCode)->where('company_id', companyId)->first()
3. Exception if not found
```

| Role Key | Settings Field | Default Code |
|---|---|---|
| `share_capital` | `equity_share_capital_account_id` | `31000` |
| `legal_reserve` | `equity_legal_reserve_account_id` | `32000` |
| `retained_earnings` | `equity_retained_earnings_account_id` | `35000` |
| `treasury` | `equity_treasury_account_id` | `31500` |
| `dividends_payable` | `equity_dividends_payable_account_id` | `21500` |
| `withholding_tax_payable` | `equity_withholding_tax_account_id` | `22400` |

### Configurable Compliance Thresholds

**Source:** `EquityConfigurationService::getThresholds()`

| Parameter | PLC Default | SC Default | Settings Override | Statutory Basis |
|---|---|---|---|---|
| `minimum_capital` | ETB 15,000 | ETB 50,000 | `minimum_capital` | Art. 510 / Art. 304 |
| `capital_adequacy_threshold` | 50% | 50% | `capital_adequacy_threshold` | Art. 473 |
| `legal_reserve_rate` | 5% | 5% | `legal_reserve_rate` | Art. 452 |
| `legal_reserve_cap_rate` | 10% | 10% | `legal_reserve_cap_rate` | Art. 452 |
| `minimum_par_value` | ETB 100 | ETB 100 | `minimum_par_value` | Art. 452 |

---

## 5. Data Model Topology

### Core Models & Relationships

```
ShareClass (share_classes)
├── class_name, class_type [common|preferred]
├── par_value, authorized_shares, issued_shares
├── has_voting_rights, dividend_rate (preferred only)
├── share_capital_account_id FK → Account
├── share_premium_account_id FK → Account
├── treasury_shares_account_id FK → Account
├── certificate_prefix, next_certificate_number
├── hasMany: ShareTransaction, ShareLedger, Dividend, TreasuryStock
└── verifyConsistency() → compares issued_shares vs ShareLedger SUM

Shareholder (shareholders)
├── shareholder_name, shareholder_type [individual|institutional|corporate]
├── tax_id, email, phone, address, is_active
├── hasMany: ShareTransaction, ShareLedger, DividendPayment
├── currentBalance(ShareClass $sc) → ShareLedger net balance per class
├── total_shares_owned → aggregated across all classes
└── ownership_percentage → dynamic % based on all classes

ShareTransaction (share_transactions)
├── transaction_type: initial_issue|issuance|buyback|transfer|stock_split|
│                     reverse_split|conversion|bonus_issue|treasury_reissue
├── number_of_shares, price_per_share, total_amount
├── journal_entry_id FK → JournalEntry
├── status: draft → approved → posted
├── bank_account_id, is_bonus_issue, source_reserve_account_id
└── hasMany: ShareLedger, ShareCertificate

ShareLedger (share_ledger)          ← AUTHORITATIVE source of truth for ownership
├── shareholder_id, share_class_id, company_id
├── date, description
├── debit_shares (shares OUT), credit_shares (shares IN)
└── share_transaction_id FK → ShareTransaction

ShareTransfer (share_transfers)
├── from_shareholder_id, to_shareholder_id
├── share_class_id, number_of_shares
├── transfer_date, approval_status, approved_by
└── board_resolution_number, attachments

ShareCertificate (share_certificates)
├── certificate_number (auto-generated: PREFIX-0001)
├── shareholder_id, share_class_id, share_transaction_id
├── issue_date, shares, status: active|cancelled|replaced

Dividend (dividends)
├── share_class_id, declaration_date, record_date, payment_date
├── dividend_per_share, shares_outstanding, total_amount
├── dividend_type: cash|stock|property
├── status: declared → approved → paid
├── declaration_entry_id FK → JournalEntry (Dr RE, Cr Payable)
├── payment_entry_id FK → JournalEntry (Dr Payable, Cr Cash)
├── board_resolution_number, board_meeting_date
├── solvency_test_passed, total_assets_at_test, total_liabilities_at_test
├── approved_by_user_id, approved_at
└── boot(): auto-generates dividend_number via DocumentSequenceService

DividendPayment (dividend_payments)
├── dividend_id, shareholder_id
├── shares_held, dividend_per_share
├── gross_amount, withholding_tax_rate, withholding_tax_amount, net_amount
├── status: pending → paid
├── paid_at, paid_by_user_id, payment_date, payment_reference
└── journal_entry_line_id FK → JournalEntryLine

TreasuryStock (treasury_stocks)
├── share_class_id, shareholder_id
├── transaction_type: buyback|reissue
├── shares, cost_per_share, total_cost
├── reissue_price, gain_loss_amount (reissue only)
├── payment_method, reference, attachments
├── journal_entry_id FK → JournalEntry
├── status: posted
└── getTreasuryBalance(companyId, classId) → static helper

EquityMovement (equity_movements)
├── fiscal_period_id, equity_component, movement_type
├── debit_amount, credit_amount, narrative
├── journal_entry_id, share_transaction_id
├── equity_component: common_stock|preferred_stock|share_premium|
│                     retained_earnings|treasury_stock|aoci
├── movement_type: beginning_balance|net_income|net_loss|
│                  other_comprehensive_income|dividends|share_issuance|
│                  share_buyback|share_split|prior_period_adjustment
└── Feeds: ShareholdersEquityService → Statement of Changes in Equity

EquityComplianceAlert (equity_compliance_alerts)
├── alert_type, severity: danger|warning|info
├── title, message, context (JSON — includes article references)
├── is_resolved, resolved_at
└── Upserted by EquityComplianceService.runAll()

ComplianceOverride (compliance_overrides)
├── company_id, context (e.g., 'dividend_approval')
├── subject_id, subject_type (polymorphic to Dividend, etc.)
├── reason, violations (JSON array)
├── requested_by_user_id, approved_by_user_id
└── Audit-logged bypass for statutory blocks

BoardMember (board_members)          ← SC compliance (Art. 338-340)
├── member_type: shareholder_director|non_shareholder_director|...
├── scopes: active(), directors()
└── Used by checkBoardComposition()

EgmLog (egm_logs)                   ← Extraordinary General Meetings (Art. 473)
├── meeting_date, agenda_type, outcome
├── attendees_count, resolution_text
├── board_resolution_number
└── Required when capital adequacy alert is active

UndistributedProfitAssessment        ← Income Tax Proc. 979/2016, Art. 61
├── origin_tax_year, undistributed_profit_amount
├── deadline_date, tax_rate, tax_amount
├── status: open → assessed → paid
├── reinvested_at, distributed_at
└── scopeOpen(), scopeExpired()
```

---

## 6. Service Layer Architecture

### Dependency Graph

```
LedgerPostingService
├── PostingGuardService
├── ShareTransactionService
│   ├── PostingGuardService
│   ├── EquityConfigurationService
│   ├── EquityComplianceService
│   └── DividendPaymentService
│       ├── DividendValidationService
│       │   └── EquityConfigurationService
│       ├── PostingGuardService
│       ├── EquityConfigurationService
│       ├── EquityComplianceService
│       └── ComplianceOverrideService
└── EquityComplianceService
    └── EquityConfigurationService

TreasuryStockService
├── PostingGuardService
└── EquityConfigurationService

PeriodCloseService
├── FinancialReportingService
├── RetainedEarningsService
├── PolicyVersionResolver
├── EquityConfigurationService
└── EquityComplianceService (post-close checks)

ShareholdersEquityService  ← Statement of Changes in Equity (read-only)
RetainedEarningsService    ← RE Statement (read-only)
OwnerCapitalService        ← SP equity only (PostingGuard enforced)
```

### Service Responsibilities

| Service | Responsibility |
|---|---|
| `LedgerPostingService` | Entry router — dispatches `ShareTransaction` to correct method by `transaction_type` |
| `ShareTransactionService` | Core: `issueShares()`, `transferShares()`, `buybackShares()`, `issueBonusShares()`, `declareDividend()` |
| `TreasuryStockService` | Treasury buyback/reissue with Weighted Average Cost gain/loss logic |
| `DividendPaymentService` | Approve declaration → Calculate shareholders → Execute batch payment → Remit WHT |
| `DividendValidationService` | 8-check statutory validation gate (Section 3.8) |
| `EquityComplianceService` | 7 persistent dashboard compliance alerts (Section 14) |
| `EquityConfigurationService` | Threshold/account resolution with request-level caching |
| `ComplianceOverrideService` | Records authorized compliance overrides with full audit trail |
| `ShareholdersEquityService` | Generates Statement of Changes in Equity report matrix |
| `RetainedEarningsService` | Retained Earnings statement (PLC) or Owner's Equity statement (SP) |
| `PeriodCloseService` | Revenue/Expense → Income Summary → Retained Earnings → Legal Reserve (Art. 452) |
| `OwnerCapitalService` | SP only: capital contributions, drawings, year-end close |

---

## 7. Workflow 1: Share Class Configuration

**Navigation:** Equity → Share Classes

### Data Requirements

| Field | Validation | Statutory Basis |
|---|---|---|
| `class_name` | Required (e.g., "Ordinary Shares") | — |
| `class_type` | `common` or `preferred` | — |
| `par_value` | ≥ ETB 100 (configurable `minimum_par_value`) | **Art. 452, Commercial Code 2021** |
| `authorized_shares` | > 0 (maximum issuable ceiling) | — |
| `share_capital_account_id` | FK → Account (e.g., 31000) | Required for journal entries |
| `share_premium_account_id` | FK → Account (e.g., 31200) | Required for premium booking |
| `treasury_shares_account_id` | FK → Account (e.g., 31500) | Required for buyback/reissue |

### Integrity Check

`ShareClass::verifyConsistency()` — compares `issued_shares` counter against `ShareLedger SUM(credit_shares - debit_shares)`. Called before every issuance and buyback.

---

## 8. Workflow 2: Shareholder Registration

**Navigation:** Equity → Shareholders

### Data Captured

| Field | Purpose |
|---|---|
| `shareholder_name` | Legal name (required) |
| `shareholder_type` | `individual`, `institutional`, or `corporate` |
| `tax_id` | TIN — essential for WHT reporting (Proc. 979/2016) |
| `email`, `phone`, `address` | Contact information |
| `is_active` | Default `true`; inactive shareholders excluded from new issuance |

### Single-Member PLC Enforcement

When `Company::isSingleMemberPLC()` is `true`, `ShareTransactionService::validateShareIssuance()` enforces exactly 1 shareholder by querying both `ShareLedger` and posted `ShareTransaction` records for any other shareholder.

---

## 9. Workflow 3: Share Issuance (Primary Market)

**Navigation:** Equity → Issue Shares (3-step wizard)

### User Flow

```
Step 1 (Details): Shareholder, Share Class, Type (Cash/Bonus), Shares, Price, Bank Account
Step 2 (Preview): Live journal entry debit/credit rendering
Step 3 (Confirm): Summary review → Submit
```

### Processing Pipeline

```
IssueShares.processIssuance(data)
├── DB::beginTransaction()
├── Validate: authorized_shares - issued_shares >= requested
├── ShareTransaction::create(status: 'approved')
├── LedgerPostingService.postShareTransaction(transaction)
│   ├── PostingGuardService.assertCanPost()
│   └── match transaction_type:
│       'issuance'    → ShareTransactionService.issueShares()
│       'bonus_issue' → ShareTransactionService.issueBonusShares()
├── ShareCertificate::create(auto-numbered)
├── DB::commit()
└── EquityComplianceService.runAll(company) [auto]
```

### Cash Issuance — `issueShares()`

**Pre-Checks (Art. 452 enforcement):**
- `ShareClass.verifyConsistency()` — integrity gate
- Available authorized shares check
- Par value ≥ ETB 100 (minimum par value)
- Issue price ≥ par value (no below-par issuance)
- Single-Member PLC: single shareholder enforcement

**Atomic Transaction:**

```
1. ShareClass.increment('issued_shares', qty)
2. JournalEntry: "Share Issuance"
   ├── DR  Cash/Bank .................. total_amount
   ├── CR  Share Capital (31000) ...... par_value × shares
   └── CR  Share Premium (31200) ...... premium (price − par) × shares [if any]
3. EquityMovement::create(
     equity_component: common_stock|preferred_stock,
     movement_type: share_issuance
   )
   └── If premium > 0: additional EquityMovement(equity_component: share_premium)
4. ShareLedger::create(credit_shares: qty)
5. ShareTransaction.status → 'posted'
```

### Bonus Issuance — `issueBonusShares()`

Capitalises existing reserves into share capital (no cash movement):

```
Journal Entry:
  ├── DR  Retained Earnings (35000) ... par_value × shares
  └── CR  Share Capital (31000) ....... par_value × shares
```

---

## 10. Workflow 4: Share Transfer (Secondary Market)

**Source:** `ShareTransactionService::transferShares()`

### Key Characteristics

- **No GL impact** — no journal entry created
- **Share Ledger only** — updates cap table ownership
- Company total capital unchanged

### Processing

```
1. Validate seller balance: ShareLedger SUM(credit - debit) >= qty
2. ShareLedger::create(debit_shares: qty) → seller (OUT)
3. ShareLedger::create(credit_shares: qty) → buyer (IN)
4. ShareTransaction.status → 'posted'
```

### Approval Workflow

`ShareTransfer` model supports board approval:
- `from_shareholder_id`, `to_shareholder_id`
- `approval_status`, `approved_by`, `board_resolution_number`

---

## 11. Workflow 5: Treasury Stock (Buyback & Reissue)

**Navigation:** Equity → Treasury Stock (tab-based: Buyback | Reissue)

### 11.1 Share Buyback

**Source:** `TreasuryStockService::processBuyback()`

```
Pre-Checks:
  ├── ShareClass.verifyConsistency()
  └── PostingGuardService.assertCanPost()

Atomic Transaction:
  1. TreasuryStock::create(type: 'buyback', status: 'posted')
  2. ShareTransaction::create(type: 'buyback', number_of_shares: -qty)
  3. ShareLedger::create(debit_shares: qty) → removes from seller
  4. JournalEntry:
     ├── DR  Treasury Stock (31500) ... total_cost
     └── CR  Cash/Bank ............... total_cost
  5. EquityMovement::create(
       equity_component: treasury_stock,
       movement_type: share_buyback,
       debit_amount: total_cost
     )
```

### 11.2 Treasury Reissue

**Source:** `TreasuryStockService::processReissue()`

```
Cost Calculation (Weighted Average Cost method):
  ├── WAC = (total_buyback_cost − total_reissued_cost) / remaining_shares
  ├── cost_basis = shares × WAC
  ├── proceeds = shares × reissue_price
  └── gain_loss = proceeds − cost_basis

Atomic Transaction:
  1. TreasuryStock::create(type: 'reissue')
  2. ShareTransaction::create(type: 'treasury_reissue')
  3. ShareLedger::create(credit_shares: qty) → gives shares to buyer
  4. JournalEntry:
     ├── DR  Cash/Bank ............... proceeds
     ├── CR  Treasury Stock (31500) .. cost_basis
     ├── CR  Share Premium (31200) ... gain (if gain > 0)
     └── DR  Retained Earnings (35000) or Share Premium .. |loss| (if loss < 0)
  5. EquityMovement::create(
       equity_component: treasury_stock
     )
```

### Gain/Loss Routing Rules

| Scenario | Debit | Credit |
|---|---|---|
| Reissue at gain | Cash | Treasury Stock + Share Premium (gain) |
| Reissue at loss (RE available) | Cash + Retained Earnings (loss) | Treasury Stock |
| Reissue at loss (no RE) | Cash + Share Premium (loss) | Treasury Stock |

### Live Financial Preview

The `ManageTreasuryStock` Reissue form includes a real-time financial analysis section showing:
- Weighted Average Cost per share
- Total reissue proceeds
- Estimated Gain/Loss with color-coded display (green for gain, red for loss)

---

## 12. Workflow 6: Dividend Lifecycle

### State Machine

```
declared → [approveDeclaration()] → approved → [calculateShareholderDividends()]
                                                     ↓
                                   → [executeBatchPayment()] → paid
                                                     ↓
                                   → [remitWithholdingTax()] → (WHT cleared)
```

### 12.1 Declaration & Approval

**Source:** `DividendPaymentService::approveDeclaration()`

**Pre-Approval:** All 8 statutory checks from `DividendValidationService::validateDeclaration()` must pass (see Section 3.8).

**Processing:**

```
1. Sync shares_outstanding from ShareLedger at record_date
2. Recalculate total_amount = dividend_per_share × actual_shares

Journal Entry:
  ├── DR  Retained Earnings (35000) ......... total_amount (gross)
  ├── CR  Dividends Payable (21500) ......... net_amount (total − WHT)
  └── CR  WHT Payable (22400) ............... withholding_tax_amount

3. EquityMovement::create(
     equity_component: retained_earnings,
     movement_type: dividends,
     debit_amount: total_amount
   )
4. Dividend.status → 'approved'
```

### 12.2 Shareholder Calculation

**Source:** `DividendPaymentService::calculateShareholderDividends()`

```
1. Query ShareLedger at record_date:
   SUM(credit_shares − debit_shares) WHERE date ≤ record_date
   GROUP BY shareholder_id — excludes treasury shares

2. For each shareholder with shares > 0:
   ├── gross_amount = shares_held × dividend_per_share
   ├── withholding_tax = gross × WHT_rate (10%, Proc. 979/2016)
   ├── net_amount = gross − withholding_tax
   └── DividendPayment::create(status: 'pending')
```

### 12.3 Batch Payment

**Source:** `DividendPaymentService::executeBatchPayment()`

```
Pre-Checks:
  ├── Pending DividendPayment records exist
  ├── declaration_entry_id exists
  └── PostingGuardService.assertCanPost()

Journal Entry:
  ├── For each shareholder:
  │   └── DR  Dividends Payable (21500) ... net_amount
  └── CR  Cash/Bank ................... SUM(all net_amounts)

Updates:
  ├── Each DividendPayment.status → 'paid'
  └── Dividend.status → 'paid'
```

### 12.4 WHT Remittance

**Source:** `DividendPaymentService::remitWithholdingTax()`

```
Pre-Check: Dividend.status must be 'paid'

Journal Entry:
  ├── DR  WHT Payable (22400) ... total_tax
  └── CR  Cash/Bank ............ total_tax
```

---

## 13. Workflow 7: Period Close & Legal Reserve Appropriation

**Source:** `PeriodCloseService`

### Period Close Pipeline

```
1. validatePeriodCloseable()
   ├── Not already closed/locked
   ├── No unposted/pending-approval journal entries
   ├── All posted JEs are balanced
   ├── Previous period must be closed (sequential enforcement)
   ├── Fiscal PolicyVersion must exist
   └── Accounts 35000 (RE) and 39000 (IS) must exist

2. calculateNetIncomeForPeriod()
   └── Revenue(credit−debit) − Expenses(debit−credit) from JournalEntryLines

3. createClosingEntries()
   ├── Close Revenue → Income Summary (CLOSE-REV-*)
   ├── Close Expenses → Income Summary (CLOSE-EXP-*)
   └── Transfer Income Summary → Retained Earnings (CLOSE-*)
       ├── Profit: DR Income Summary, CR Retained Earnings
       └── Loss:   DR Retained Earnings, CR Income Summary

4. RetainedEarningsService.recordMovement(net_income | net_loss)

5. EquityMovement::create(movement_type: net_income | net_loss)

6. updateFiscalYearRetainedEarnings()
   └── closing_retained_earnings = opening + YTD net income
```

### Legal Reserve — Automated Year-End Appropriation (Art. 452)

**Triggered only at fiscal year-end close** (`isFiscalYearEndPeriod() === true`):

```
applyLegalReserveAppropriation():
  ├── Condition: annual_net_income > 0
  ├── Condition: legal_reserve_balance < target (10% of share_capital)
  ├── Transfer = min(income × 5%, target − current_reserve)
  └── Journal Entry:
      ├── DR  Retained Earnings (35000) ... transfer
      └── CR  Legal Reserve (32000) ....... transfer
```

### Post-Close Compliance

```
runEquityComplianceChecks():
  └── For share-based companies: EquityComplianceService.runAll(company)
      → Re-evaluates all 7 compliance alerts after the close
```

---

## 14. Workflow 8: Equity Compliance Dashboard Alerts

**Source:** `EquityComplianceService` — 7 persistent compliance checks, stored as `EquityComplianceAlert` records.

### Alert System Architecture

```
EquityComplianceService.runAll(company)
  ├── checkMinimumCapital()         ← PLC + SC
  ├── checkCapitalAdequacy()        ← PLC + SC
  ├── checkLegalReserveStatus()     ← PLC + SC
  ├── checkNomineeRequirement()     ← SM-PLC only
  └── For SC only:
      ├── checkShareholderCount()
      ├── checkBoardComposition()
      └── checkExternalAuditRequirement()
```

### Complete Alert Catalog

#### Alert 1: Minimum Capital Threshold Breached

| Property | Value |
|---|---|
| **Alert Type** | `minimum_capital` |
| **Severity** | 🔴 `danger` |
| **Statutory Basis** | **Art. 452** (PLC: ETB 15,000) / **Art. 304** (SC: ETB 50,000) — Commercial Code 2021 |
| **Trigger** | Total equity (all equity accounts, posted JEs) < configurable `minimum_capital` |
| **Calculation** | `SUM(credit − debit)` across all accounts in group `Equity` where JE status = `posted` |
| **Alert Context (JSON)** | `total_equity`, `minimum_required`, `shortfall`, `article` |
| **Auto-Resolves** | ✅ When total equity ≥ threshold |

#### Alert 2: Capital Adequacy / EGM Required

| Property | Value |
|---|---|
| **Alert Type** | `capital_adequacy` |
| **Severity** | 🔴 `danger` |
| **Statutory Basis** | **Art. 473, Commercial Code 2021** |
| **Trigger** | Accumulated losses > 50% of share capital (configurable `capital_adequacy_threshold`) |
| **Calculation** | `accumulated_loss = max(0, abs(min(0, RE_balance)))` vs `share_capital × threshold%` |
| **Required Action** | Convene Extraordinary General Meeting → record in `EgmLog` |
| **Alert Context (JSON)** | `accumulated_loss`, `share_capital`, `threshold_pct`, `threshold_amount`, `article`, `action_required` |
| **Side Effect** | Blocks dividend declaration via `DividendValidationService` check #7 |
| **Auto-Resolves** | ✅ When losses ≤ threshold |

#### Alert 3: Legal Reserve Not Fulfilled

| Property | Value |
|---|---|
| **Alert Type** | `legal_reserve_due` |
| **Severity** | 🟡 `warning` |
| **Statutory Basis** | **Art. 452, Commercial Code 2021** |
| **Trigger** | Legal Reserve (32000) balance < 10% of Share Capital (31000) |
| **Calculation** | `target = share_capital × legal_reserve_cap_rate%`; shows progress percentage |
| **Alert Context (JSON)** | `legal_reserve_balance`, `target_reserve`, `share_capital`, `progress_pct`, `article` |
| **Side Effect** | Blocks dividend declaration via `DividendValidationService` check #3 |
| **Auto-Resolves** | ✅ When reserve ≥ target |

#### Alert 4: Single-Member PLC Nominee Missing

| Property | Value |
|---|---|
| **Alert Type** | `nominee_missing` |
| **Severity** | 🟡 `warning` |
| **Statutory Basis** | **Commercial Code 2021, Single-Member PLC provisions** |
| **Trigger** | `Company.nominee_name` is empty for SM-PLC |
| **Required Action** | Navigate to Settings → Legal Identity → fill Nominee section |
| **Alert Context (JSON)** | `action_required` |
| **Auto-Resolves** | ✅ When nominee info is provided |

#### Alert 5: SC Minimum Shareholder Count Breached

| Property | Value |
|---|---|
| **Alert Type** | `sc_shareholder_count` |
| **Severity** | 🔴 `danger` |
| **Statutory Basis** | **Art. 304, Commercial Code 2021** |
| **Trigger** | Active shareholders (with positive share balance in `ShareLedger`) < 5 |
| **Calculation** | `ShareLedger GROUP BY shareholder_id HAVING SUM(credit_shares − debit_shares) > 0` |
| **Alert Context (JSON)** | `active_shareholders`, `minimum_required`, `article` |
| **Auto-Resolves** | ✅ When shareholder count ≥ minimum |

#### Alert 6: SC Board Composition Non-Compliant

| Property | Value |
|---|---|
| **Alert Type** | `sc_board_composition` |
| **Severity** | 🟡 `warning` |
| **Statutory Basis** | **Art. 338-340, Commercial Code 2021** |
| **Trigger 1** | Total directors < 3 or > 13 (configurable `board_min_size` / `board_max_size`) |
| **Trigger 2** | Non-shareholder directors > 33% of total (configurable `non_shareholder_director_max_ratio`) |
| **Data Source** | `BoardMember` model → `active()->directors()` → `member_type` analysis |
| **Alert Context (JSON)** | `total_directors`, `non_shareholder_directors`, `board_min`, `board_max` |
| **Auto-Resolves** | ✅ When board size and ratio comply |

#### Alert 7: SC External Audit Required

| Property | Value |
|---|---|
| **Alert Type** | `sc_external_audit` |
| **Severity** | 🟡 `warning` |
| **Statutory Basis** | **Art. 381, Commercial Code 2021** |
| **Trigger** | `last_external_audit_date` is null OR > 16 months ago |
| **Data Source** | `SettingCompany.last_external_audit_date` |
| **Alert Context (JSON)** | `last_audit_date`, `months_since_audit` |
| **Auto-Resolves** | ✅ When audit date is within 16 months |

### Alert Persistence & Resolution

```php
// Upsert: create or update existing unresolved alert
EquityComplianceAlert::updateOrCreate(
  ['company_id' => $id, 'alert_type' => $type, 'is_resolved' => false],
  ['severity' => $severity, 'title' => $title, 'message' => $msg, 'context' => $ctx]
);

// Auto-resolve when condition clears
EquityComplianceAlert::where('company_id', $id)
  ->where('alert_type', $type)
  ->where('is_resolved', false)
  ->update(['is_resolved' => true, 'resolved_at' => now()]);

// Query active alerts (ordered: danger → warning → info)
EquityComplianceAlert::where('company_id', $id)
  ->where('is_resolved', false)
  ->orderByRaw("FIELD(severity, 'danger', 'warning', 'info')")
  ->get();

// Summary counts
['total' => count, 'danger' => count, 'warning' => count, 'info' => count]
```

---

## 15. Workflow 9: Statement of Changes in Equity

**Source:** `ShareholdersEquityService::generateStatement()`

### Report Matrix Structure

**Columns (equity_component):**

| Column | Account Source |
|---|---|
| Common Stock | `31000` |
| Preferred Stock | `31100` |
| Share Premium | `31200` / `31210` |
| Retained Earnings | `35000` |
| Treasury Stock | `31500` |
| AOCI | `31600` |
| **Total** | Sum of all columns |

**Rows (movement_type):**

| Row | Data Source |
|---|---|
| Beginning Balance | `SUM(credit − debit)` where JE date < period start |
| Share Issuance | `EquityMovement.movement_type = 'share_issuance'` |
| Net Income | `EquityMovement.movement_type = 'net_income'` |
| Net Loss | `EquityMovement.movement_type = 'net_loss'` |
| Other Comprehensive Income | `EquityMovement.movement_type = 'other_comprehensive_income'` |
| Share Buyback | `EquityMovement.movement_type = 'share_buyback'` |
| Dividends | `EquityMovement.movement_type = 'dividends'` |
| Share Split | `EquityMovement.movement_type = 'share_split'` |
| Prior Period Adjustments | `EquityMovement.movement_type = 'prior_period_adjustment'` |
| **Ending Balance** | Beginning + all movements |

---

## 16. Workflow 10: Retained Earnings Statement

**Source:** `RetainedEarningsService`

### PLC Flow

```
Opening Balance  = Account 35000 balance as of day before start_date
+ Net Income     = EquityMovement(RE, net_income|net_loss)
− Dividends      = EquityMovement(RE, dividends|dividends_declared|dividends_paid)
± Adjustments    = EquityMovement(RE, prior_period_adjustment)
± Other          = EquityMovement(RE, other)
= Ending Balance
```

### Sole Proprietorship Flow (Owner's Equity Statement)

```
Opening Balance  = Accounts 30100 + 30200 + 35000 day before start
+ Net Income     = FinancialReportingService.calculateNetIncome()
+ Contributions  = Account 30100 credits in period
− Drawings       = Account 30200 debits in period
= Ending Balance
```

---

## 17. Filament UI Surface Map

### Navigation Group: "Equity" (visible only when `Company::isShareBased()`)

| Page/Resource | Type | Visibility Gate | Description |
|---|---|---|---|
| Share Classes | Resource (CRUD) | `isShareBased()` | Define share classes with par values & account links |
| Shareholders | Resource (CRUD) | `isShareBased()` | Register individuals/entities with TIN |
| Share Transactions | Resource (CRUD) | `isShareBased()` | View all transaction history |
| Issue Shares | Page (3-Step Wizard) | `isShareBased()` + `equity.share_issuance.index` | Cash & Bonus issuance with journal preview |
| Treasury Stock | Page (Tab Form) | `isShareBased()` + `equity.treasury_stock.index` | Buyback & Reissue with WAC analysis |
| Dividends | Resource (CRUD) | `isShareBased()` | Declare, approve, manage dividends |
| Dividend Payment | Page (Table) | Hidden — via Dividend actions | Batch payment processing |
| Equity Reports | Page (Hub) | Hidden — via Reports Dashboard | Widget-based report navigation |
| Owner's Capital | Resource (CRUD) | SP only (not share-based) | Capital contributions & drawings |

### Equity Reports

| Report | Data Source |
|---|---|
| Statement of Changes in Equity | `ShareholdersEquityService` |
| Dividend Distribution Report | `DividendPayment` queries |
| Dividend Payment History | `DividendPayment` queries |
| Dividend Tax Report | WHT data from DividendPayments |
| Share Authorization Report | `ShareClass` data |
| Share Register Report | `ShareLedger` data |

### Dashboard Widgets

| Widget | Purpose |
|---|---|
| `EquitySummaryOverview` | Total equity stats |
| `EquityReportsNavigationWidget` | Quick-nav cards to reports |
| `DividendByShareClassChart` | Chart: dividends per share class |
| `DividendPaymentStats` | Stats: paid/pending totals |
| `DividendPaymentStatusChart` | Pie chart: payment status breakdown |
| `TaxRemittanceTracker` | WHT remittance tracking |

---

## 18. Security & Permission Model

### Permission Keys (Filament Shield)

| Permission | Controls |
|---|---|
| `equity.share_issuance.index` | Issue Shares page access |
| `equity.treasury_stock.index` | Treasury Stock page access |
| `equity.dividend.index` | Dividend pages access |
| `equity.shareholder.index` | Shareholder resource + Equity Reports access |

### Posting Controls

| Control | Implementation |
|---|---|
| Period Lock | `PostingGuardService.assertCanPost(companyId, date, context)` |
| Fiscal Period | Date must fall within an open/unlocked `AccountingPeriod` |
| Sequential Close | Cannot post to a closed/locked period |
| Compliance Override | `ComplianceOverrideService.record()` — audit-logged bypass |

### Compliance Override Audit Trail

Every override records:
- `company_id`, `context` (e.g., 'dividend_approval')
- `subject_id`, `subject_type` (polymorphic reference)
- `reason` (user-supplied justification)
- `violations` (JSON array of all bypassed checks)
- `requested_by_user_id`, `approved_by_user_id`

---

*All content derived from live codebase analysis — models, services, migrations, Filament pages, and Chart of Accounts definitions.*

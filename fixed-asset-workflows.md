# Fixed Asset Workflows — Complete Architecture Reference

> **Source**: Deep analysis of `mammesat/AcctApp` (Laravel + Filament ERP)
> **Jurisdiction**: Ethiopian Tax Law — Income Tax Proclamation No. 979/2016 (Art. 25), Regulation No. 410/2017
> **Last Analyzed**: 2026-05-21

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Data Model Architecture](#2-data-model-architecture)
3. [Ethiopian Statutory Asset Categories](#3-ethiopian-statutory-asset-categories)
4. [Asset Category Configuration](#4-asset-category-configuration)
5. [Asset Pool Architecture](#5-asset-pool-architecture)
6. [Fixed Asset Model (Core)](#6-fixed-asset-model-core)
7. [Depreciation Service Engine](#7-depreciation-service-engine)
8. [Asset Acquisition & Purchase Entry](#8-asset-acquisition--purchase-entry)
9. [Monthly Depreciation Posting](#9-monthly-depreciation-posting)
10. [Pool Depreciation Posting](#10-pool-depreciation-posting)
11. [Asset Disposal Workflow](#11-asset-disposal-workflow)
12. [Pool Asset Disposal](#12-pool-asset-disposal)
13. [Lifecycle Event Tracking](#13-lifecycle-event-tracking)
14. [GL Journal Entry Integration](#14-gl-journal-entry-integration)
15. [Chart of Accounts Mapping](#15-chart-of-accounts-mapping)
16. [Ethiopian Asset Categories Setup Service](#16-ethiopian-asset-categories-setup-service)
17. [Asset Status State Machine](#17-asset-status-state-machine)
18. [Filament UI Layer](#18-filament-ui-layer)
19. [Reporting Suite](#19-reporting-suite)
20. [Security & Permissions](#20-security--permissions)
21. [Dashboard KPIs](#21-dashboard-kpis)

---

## 1. System Overview

The AcctApp fixed asset module is a full-lifecycle, Ethiopian-compliant asset management system embedded within a multi-tenant Laravel ERP. It handles asset acquisition, capitalization with VAT/landed costs, statutory depreciation (individual and pooled), disposal with gain/loss recognition, and comprehensive GL journal posting.

### Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│                 Filament UI Layer                    │
│  FixedAssetResource  │  AssetPoolResource            │
│  AssetCategoryResource  │  PostMonthlyDepreciation   │
│  5× Asset Report Pages  │  AssetManagerKpiWidget     │
├─────────────────────────────────────────────────────┤
│              Service Layer (Business Logic)          │
│  DepreciationService (SL/DV calculations)            │
│  EthiopianAssetCategoriesSetupService (auto-seeding) │
├─────────────────────────────────────────────────────┤
│              Model Layer (Domain Logic)              │
│  FixedAsset (purchase, depreciate, dispose)           │
│  AssetPool (pool-level depreciation)                  │
│  AssetCategory (statutory rates & GL links)           │
│  FixedAssetLifecycleEvent (audit trail)               │
│  DepreciationLog / PoolDepreciationLog                │
├─────────────────────────────────────────────────────┤
│              Policy Layer (Authorization)            │
│  FixedAssetPolicy  │  BasePolicy                     │
├─────────────────────────────────────────────────────┤
│              Database Layer                          │
│  Multi-tenant (BelongsToTenant)  │  Auditable trait  │
└─────────────────────────────────────────────────────┘
```

### Multi-Tenancy

Every asset model uses the `BelongsToTenant` concern. All queries are automatically scoped to the authenticated user's company.

### Key Design Decisions

- **Rate-based depreciation**: All calculations use statutory percentage rates (not `useful_life_years`). Life is informational only.
- **Dual depreciation modes**: Individual assets use per-asset depreciation logs; pooled assets use pool-level depreciation with pro-rata share allocation on disposal.
- **Automatic GL posting**: Every financial event (purchase, depreciation, disposal) auto-generates balanced journal entries.
- **Ethiopian statutory compliance**: Six mandatory asset categories auto-seeded per Proclamation 979/2016 with locked rates.

---

## 2. Data Model Architecture

### Entity Relationship Diagram

```
Company (1)
 ├── AssetCategory (1:N) ── Ethiopian statutory types
 │    ├── asset_account_id → Account (Fixed Assets)
 │    ├── accumulated_depreciation_account_id → Account
 │    ├── depreciation_expense_account_id → Account
 │    ├── FixedAsset (1:N)
 │    └── AssetPool (1:N)
 │
 ├── AssetPool (1:N) ── Pooled depreciation groups
 │    ├── asset_account_id → Account
 │    ├── accumulated_depreciation_account_id → Account
 │    ├── depreciation_expense_account_id → Account
 │    ├── FixedAsset (1:N members)
 │    └── PoolDepreciationLog (1:N)
 │
 ├── FixedAsset (1:N)
 │    ├── asset_account_id → Account
 │    ├── accumulated_depreciation_account_id → Account
 │    ├── depreciation_expense_account_id → Account
 │    ├── paid_from_account_id → Account (Bank/Cash)
 │    ├── DepreciationLog (1:N) ── per-month postings
 │    └── FixedAssetLifecycleEvent (1:N) ── audit trail
 │
 └── DepreciationLog / PoolDepreciationLog
      └── journal_entry_id → JournalEntry
```

### Core Models Summary

| Model | Table | Purpose |
|-------|-------|---------|
| `FixedAsset` | `fixed_assets` | Core asset record with cost, depreciation, status |
| `AssetCategory` | `asset_categories` | Ethiopian statutory classification + rates + GL links |
| `AssetPool` | `asset_pools` | Grouping mechanism for diminishing-value pooling |
| `FixedAssetLifecycleEvent` | `fixed_asset_lifecycle_events` | Full audit trail per asset |
| `DepreciationLog` | `depreciation_logs` | Per-asset monthly depreciation records |
| `PoolDepreciationLog` | `pool_depreciation_logs` | Per-pool monthly depreciation records |

---

## 3. Ethiopian Statutory Asset Categories

### Legal Basis

Per **Income Tax Proclamation No. 979/2016 (Article 25)** and **Council of Ministers Regulation No. 410/2017**, depreciable assets in Ethiopia are classified into six mandatory categories:

| Ethiopian Type | Category Name | SL Rate | DV Rate | Method Locked | Poolable |
|----------------|---------------|---------|---------|---------------|----------|
| `buildings` | Buildings & Structural Improvements | 5% | — | SL only ✅ | ❌ |
| `intangibles` | Intangible Assets | 10% | — | SL only ✅ | ❌ |
| `greenhouses` | Greenhouses | 10% | — | SL only ✅ | ❌ |
| `computers_software` | Computers, Software & Data Storage | 20% | 25% | ❌ | ✅ |
| `mining_petroleum` | Mining & Petroleum Assets | 25% | 30% | ❌ | ✅ |
| `all_other` | All Other Depreciable Assets | 15% | 20% | ❌ | ✅ |

### Key Rules

- **Buildings, Intangibles, Greenhouses**: Straight-Line only (method is locked). Cannot be pooled. Must be depreciated individually.
- **Computers, Mining, All Other**: Can use either Straight-Line or Diminishing Value. Can be pooled for DV depreciation under statutory pooling rules.
- **Buildings**: Depreciation must not start before the **Certificate of Completion** date.

---

## 4. Asset Category Configuration

### AssetCategory Model Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | String | Display name (e.g., "Buildings & Structural Improvements") |
| `ethiopian_category_type` | String | Statutory type identifier (see constants above) |
| `depreciation_method` | String | `straight_line` or `diminishing_value` |
| `depreciation_rate_sl` | Decimal | Annual Straight-Line rate (e.g., 5.0 for 5%) |
| `depreciation_rate_dv` | Decimal | Annual Diminishing-Value rate (e.g., 25.0 for 25%) |
| `allow_diminishing_value` | Boolean | Whether DV method is permitted |
| `is_poolable` | Boolean | Whether assets in this category can be pooled |
| `is_system_default` | Boolean | Auto-seeded by setup service |
| `asset_account_id` | FK → Account | GL account for asset cost (`12xxx` / `13xxx`) |
| `accumulated_depreciation_account_id` | FK → Account | GL contra-asset (`12xx0` / `13xx0`) |
| `depreciation_expense_account_id` | FK → Account | GL expense (`61600`) |

### Rate Accessors

```php
$category->getEffectiveRateSl()  // Returns the SL rate as float
$category->getEffectiveRateDv()  // Returns the DV rate as float
```

### Boolean Helpers

```php
$category->isStatutoryLocked()        // True if SL-only (buildings, intangibles, greenhouses)
$category->allowsDiminishingValue()   // True if DV is permitted
$category->isPoolable()               // True if pooling is allowed
$category->isSystemDefault()          // True if auto-seeded
```

### Category → Asset Inheritance

When a `FixedAsset` is saved with a category, the `saving` boot hook syncs:
1. `depreciation_method` ← category default (if not explicitly set on asset)
2. `asset_account_id` ← category account (if not set)
3. `accumulated_depreciation_account_id` ← category account (if not set)
4. `depreciation_expense_account_id` ← category account (if not set)

> **Note**: `useful_life_years` is NOT synced — depreciation uses rates only.

---

## 5. Asset Pool Architecture

### Purpose

Asset pools implement Ethiopian statutory **Diminishing-Value pooling**, where assets of the same category are grouped and depreciated collectively on a reducing-balance basis.

### AssetPool Model Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | String | Pool name (e.g., "IT Equipment Pool") |
| `asset_category_id` | FK → AssetCategory | Category this pool belongs to |
| `depreciation_method` | String | `diminishing_value` or `straight_line` |
| `depreciation_rate_sl` | Decimal | Pool-level SL rate override |
| `depreciation_rate_dv` | Decimal | Pool-level DV rate override |
| `accumulated_depreciation` | Decimal | Running total of pool depreciation |
| `asset_account_id` | FK → Account | Pool GL asset account |
| `accumulated_depreciation_account_id` | FK → Account | Pool GL accum. deprec. |
| `depreciation_expense_account_id` | FK → Account | Pool GL expense |

### Computed Attributes

```
total_cost       = SUM(purchase_price) of active/fully_depreciated assets in pool
pool_book_value  = MAX(0, total_cost − accumulated_depreciation)
```

### Pool Depreciation Logic

```
Pool DV Formula:
  Annual Charge = pool_book_value × (rate_dv / 100)
  Monthly Charge = Annual Charge / 12

Pool SL Formula (rare):
  Annual Charge = total_cost × (rate_sl / 100)
  Monthly Charge = Annual Charge / 12
```

> **Note**: Business-use percentage is NOT applied at pool level per Ethiopian statutory pooling rules. Individual asset business-use percentages are tracked at asset level only.

---

## 6. Fixed Asset Model (Core)

### FixedAsset Fields

```
┌──────────────────────────────────────────────────────────┐
│                  IDENTITY FIELDS                          │
├──────────────────────────────────────────────────────────┤
│ name                    │ Asset name                      │
│ reference_number        │ Asset tag (e.g., FA-2026-001)   │
│ description             │ Free text                       │
│ asset_category_id       │ FK → AssetCategory              │
│ asset_pool_id           │ FK → AssetPool (nullable)       │
│ status                  │ Enum: FixedAssetStatus          │
├──────────────────────────────────────────────────────────┤
│                  FINANCIAL FIELDS                         │
├──────────────────────────────────────────────────────────┤
│ purchase_price          │ Cost excl. VAT (Decimal:2)      │
│ vat_amount              │ Calculated VAT (Decimal:2)      │
│ vat_treatment           │ standard / zero_rated / exempt  │
│ salvage_value           │ Residual value (Decimal:2)      │
│ accumulated_depreciation│ Running total (Decimal:2)       │
│ business_use_percentage │ 0–100 (Decimal:2)               │
├──────────────────────────────────────────────────────────┤
│                  IMPORTED ASSET FIELDS                    │
├──────────────────────────────────────────────────────────┤
│ is_imported             │ Boolean                         │
│ customs_duty            │ Landed cost component           │
│ freight                 │ Landed cost component           │
│ insurance               │ Landed cost component           │
├──────────────────────────────────────────────────────────┤
│                  DATE FIELDS                              │
├──────────────────────────────────────────────────────────┤
│ purchase_date           │ Date of acquisition             │
│ ready_for_use_date      │ When asset entered service      │
│ depreciation_start_date │ Auto-derived from ready date    │
│ certificate_of_completion_date │ Buildings only           │
│ certificate_reference   │ Buildings only                  │
├──────────────────────────────────────────────────────────┤
│                  GL ACCOUNT LINKS                         │
├──────────────────────────────────────────────────────────┤
│ asset_account_id                    │ DR on purchase      │
│ accumulated_depreciation_account_id │ CR on depreciation  │
│ depreciation_expense_account_id     │ DR on depreciation  │
│ paid_from_account_id                │ CR on purchase      │
├──────────────────────────────────────────────────────────┤
│                  DEPRECIATION                             │
├──────────────────────────────────────────────────────────┤
│ depreciation_method     │ straight_line / diminishing_value│
└──────────────────────────────────────────────────────────┘
```

### Computed Attributes

```php
$asset->net_book_value  // = MAX(0, purchase_price − accumulated_depreciation)
$asset->isInPool()      // = asset_pool_id !== null
```

### Boot Hooks

**`saving`**:
1. Syncs GL accounts + depreciation method from category (null-coalescing — won't overwrite explicit values)
2. Auto-derives `depreciation_start_date` from `ready_for_use_date` if not set

**`deleting`**:
1. Deletes all associated `DepreciationLog` records and their journal entries
2. Deletes disposal journal entry (`DISPOSAL-{id}`)
3. Safety net: deletes any remaining depreciation JEs (`DEPR-{id}-*`)

---

## 7. Depreciation Service Engine

### DepreciationService

The centralized calculation service delegates all depreciation math. No model contains calculation logic directly.

### Straight-Line (Individual Asset)

```
Formula:
  Annual Charge = cost × (rate_sl / 100)
  Monthly = (Annual / 12) × (business_use_pct / 100)

Constraints:
  - Depreciable Base = cost − salvage_value
  - remaining = depreciable_base − accumulated_depreciation
  - Monthly is capped: MIN(monthly, remaining × biz_pct/100)
  - Returns 0 if cost ≤ 0 or rate ≤ 0

Example:
  Cost = 100,000 ETB, Salvage = 5,000, Rate = 5%, Biz = 100%
  Annual = 100,000 × 0.05 = 5,000 ETB
  Monthly = 5,000 / 12 = 416.67 ETB
```

### Diminishing Value (Individual Asset)

```
Formula:
  Annual Charge = opening_book_value × (rate_dv / 100)
  Monthly = (Annual / 12) × (business_use_pct / 100)

Constraints:
  - max_allowed = opening_book_value − salvage_value
  - Monthly = MIN(monthly, max_allowed)
  - Returns 0 if book_value ≤ 0 or rate ≤ 0

Example:
  Book Value = 80,000 ETB, Salvage = 5,000, Rate = 25%, Biz = 100%
  Annual = 80,000 × 0.25 = 20,000 ETB
  Monthly = 20,000 / 12 = 1,666.67 ETB
```

### Pool Diminishing Value

```
Formula:
  Annual Charge = pool_book_value × (rate_dv / 100)
  Monthly = Annual / 12

Note: Business-use % is NOT applied at pool level
      (per Ethiopian statutory pooling rules)
```

### Pool Straight-Line (Rare)

```
Formula:
  Annual Charge = total_cost × (rate_sl / 100)
  Monthly = Annual / 12
```

### Helper Methods

```php
$service->isFullyDepreciated($cost, $accumulated, $salvage)
// Returns true if accumulated >= (cost − salvage)

$service->netBookValue($cost, $accumulated)
// Returns MAX(0, cost − accumulated)

$service->buildAnnualSchedule($method, $cost, $salvage, $rate, $bizPct, $maxYears)
// Returns [{year, opening, annual_depreciation, closing}] projection
```

---

## 8. Asset Acquisition & Purchase Entry

### Trigger

When a FixedAsset is created via the Filament UI, `CreateFixedAsset::afterCreate()` automatically calls `$record->postPurchaseEntry()`.

### Purchase Entry Flow

```
postPurchaseEntry()
 │
 ├─ 1. Validate GL Accounts
 │    └── asset_account_id AND paid_from_account_id must exist
 │
 ├─ 2. Calculate Costs
 │    ├── landed_cost = customs_duty + freight + insurance
 │    ├── asset_cost = purchase_price + landed_cost
 │    ├── vat_rate = based on vat_treatment (standard → 15%, else → 0%)
 │    ├── vat_amount = purchase_price × vat_rate (on purchase price only)
 │    └── total_amount = asset_cost + vat_amount
 │
 ├─ 3. Create Journal Entry
 │    ├── Reference: FA-PURCHASE-{id padded to 5 digits}
 │    ├── Date: purchase_date
 │    └── Status: posted
 │
 ├─ 4. Journal Lines
 │    ├── DR  Asset Account .............. asset_cost
 │    │       (purchase price + landed costs)
 │    ├── DR  VAT Input (11230*) ........ vat_amount
 │    │       (only if vat_amount > 0 and VAT account exists)
 │    └── CR  Paid From Account ......... total_amount
 │            (bank/cash account)
 │
 └─ 5. Record Lifecycle Events
      ├── 'acquisition' event (amount = purchase_price)
      └── 'capitalization' event (amount = asset_cost incl. landed)
```

> \*Note: The underlying code in `FixedAsset.php` currently searches for VAT Input using code `11100` (which per `AccountSeeder.php` is **Cash & Bank** Header). **This is a known bug** and should be corrected to search for `11230` (VAT Input (Purchase Tax)) to align with the canonical Chart of Accounts.

### Imported Asset Cost Build-Up

```
┌──────────────────────────────────┐
│     Purchase Price (excl. VAT)   │ ← DR Asset Account
│   + Customs Duty                 │
│   + Freight                      │
│   + Insurance                    │
│   ────────────────────────────   │
│   = Total Capitalizable Cost     │
│                                  │
│   + VAT (on purchase price only) │ ← DR VAT Input Account
│   ────────────────────────────   │
│   = Total Payment Amount         │ ← CR Paid From Account
└──────────────────────────────────┘
```

---

## 9. Monthly Depreciation Posting

### Individual Asset Depreciation

```
postMonthlyDepreciation(date)
 │
 ├─ 1. Guards
 │    ├── Prevent double posting for same month
 │    │    └── Check: depreciationLogs WHERE posting_date LIKE '{YYYY-MM}%'
 │    ├── Status must be 'active'
 │    ├── Date must be ≥ ready_for_use_date
 │    └── Date must be ≥ depreciation_start_date
 │
 ├─ 2. Fully Depreciated Check
 │    └── If isFullyDepreciated → update status to 'fully_depreciated', return null
 │
 ├─ 3. Calculate Amount
 │    └── calculateMonthlyDepreciation() → delegates to DepreciationService
 │         ├── SL: calculateMonthlySL(cost, salvage, rate, accumulated, bizPct)
 │         └── DV: calculateMonthlyDV(bookValue, salvage, rate, bizPct)
 │
 ├─ 4. Cap Final Instalment
 │    └── remaining = cost − accumulated
 │        If (remaining − amount) < salvage → amount = remaining − salvage
 │
 ├─ 5. Validate GL Accounts
 │    └── depreciation_expense_account_id AND accumulated_depreciation_account_id required
 │
 ├─ 6. Create Journal Entry (within DB transaction)
 │    ├── Reference: DEPR-{asset_id}-{YYYYMM}
 │    ├── DR  Depreciation Expense Account ..... amount
 │    └── CR  Accumulated Depreciation Account . amount
 │
 ├─ 7. Create DepreciationLog
 │    └── posting_date, amount, journal_entry_id
 │
 ├─ 8. Increment accumulated_depreciation on asset
 │
 ├─ 9. Record Lifecycle Event ('monthly_depreciation')
 │    └── meta: { method, posting_month }
 │
 └─ 10. Auto-Status Transition
      └── If now fully depreciated → status = 'fully_depreciated'
```

---

## 10. Pool Depreciation Posting

### Pool-Level Depreciation

```
AssetPool::postMonthlyDepreciation(date)
 │
 ├─ 1. Guard: Prevent double posting for same month
 │
 ├─ 2. Calculate Amount
 │    └── calculateMonthlyDepreciation()
 │         ├── DV: calculatePoolMonthlyDV(pool)
 │         │    └── Annual = pool_book_value × (rate / 100); Monthly = Annual / 12
 │         └── SL: calculatePoolMonthlySL(pool)
 │              └── Annual = total_cost × (rate / 100); Monthly = Annual / 12
 │
 ├─ 3. Create Journal Entry
 │    ├── Reference: POOL-DEPR-{pool_id}-{YYYYMM}
 │    ├── DR  Pool Depreciation Expense Account ..... amount
 │    └── CR  Pool Accumulated Depreciation Account . amount
 │
 ├─ 4. Create PoolDepreciationLog
 │    └── posting_date, amount, journal_entry_id
 │
 └─ 5. Increment pool's accumulated_depreciation
```

### Batch Posting Page (PostMonthlyDepreciation)

The `PostMonthlyDepreciation` Filament page provides a UI for batch processing:

```
PostMonthlyDepreciation Page
 │
 ├─ Form Inputs
 │    ├── posting_date (default: end of current month)
 │    ├── assets[] (multi-select: active standalone assets)
 │    └── pools[] (multi-select: all company pools)
 │
 └─ postDepreciation()
      ├── For each selected standalone asset → postMonthlyDepreciation(date)
      ├── For each selected pool → postMonthlyDepreciation(date)
      ├── Success notification: "{N} asset entries + {M} pool entries posted"
      └── Error notification: lists individual failures
```

---

## 11. Asset Disposal Workflow

### Individual Asset Disposal

```
dispose(date, disposalPrice, notes)
 │
 ├─ If asset is in pool → delegate to disposeFromPool()
 │
 ├─ 1. Validate GL Accounts
 │    └── asset_account_id AND accumulated_depreciation_account_id required
 │
 ├─ 2. Calculate Gain/Loss
 │    ├── book_value = net_book_value (cost − accumulated_depreciation)
 │    └── gain_loss = disposal_price − book_value
 │
 ├─ 3. Create Journal Entry
 │    ├── Reference: DISPOSAL-{asset_id}
 │    ├── Date: disposal date
 │    └── Status: posted
 │
 ├─ 4. Journal Lines
 │    ├── DR  Accumulated Depreciation Account .. accumulated_depreciation
 │    │       (reversing the contra-asset balance)
 │    ├── CR  Asset Account .................... purchase_price
 │    │       (removing the original cost)
 │    ├── DR  Paid From Account ................ disposal_price
 │    │       (if proceeds > 0, receiving cash/bank)
 │    └── DR/CR Gain/Loss on Disposal Account
 │            ├── If loss: DR abs(gain_loss)
 │            └── If gain: CR gain_loss
 │
 ├─ 5. Gain/Loss Account Lookup
 │    └── Search: Account WHERE name LIKE '%Gain/Loss on Disposal%'
 │        Fallback: depreciation_expense_account_id
 │
 ├─ 6. Update Status → 'disposed'
 │
 └─ 7. Record Lifecycle Event ('disposal')
      └── meta: { notes, mode: 'individual' }
          fields: proceeds, gain_loss, book_value
```

### Disposal Journal Entry Structure

```
═══════════════════════════════════════════════════════════
            DISPOSAL JOURNAL ENTRY
═══════════════════════════════════════════════════════════

Example: Asset cost 100,000 | Accum. Deprec. 60,000 | Sold for 50,000

  DR  Accumulated Depreciation ......  60,000  (reversing contra)
  DR  Bank / Cash Account ...........  50,000  (disposal proceeds)
  CR  Fixed Asset Account ........... 100,000  (removing cost)
  CR  Gain on Disposal ..............  10,000  (gain = 50,000 − 40,000 NBV)

═══════════════════════════════════════════════════════════

Example: Asset cost 100,000 | Accum. Deprec. 60,000 | Scrapped (0 proceeds)

  DR  Accumulated Depreciation ......  60,000
  DR  Loss on Disposal ..............  40,000  (loss = 0 − 40,000 NBV)
  CR  Fixed Asset Account ........... 100,000

═══════════════════════════════════════════════════════════
```

### UI Disposal Action

The `ViewFixedAsset` page exposes a **"Dispose Asset"** header action:

| Field | Type | Default |
|-------|------|---------|
| `disposal_date` | DatePicker | Today |
| `disposal_type` | Select | `sale`, `scrap`, `write_off` |
| `disposal_price` | MoneyInput | 0 |
| `notes` | TextInput | — |

Visibility: Only when status is `active` or `fully_depreciated`, and user has `asset.fixed_asset.update` permission.

---

## 12. Pool Asset Disposal

When an asset in a pool is disposed, the system calculates a **pro-rata share** of the pool's accumulated depreciation.

```
disposeFromPool(date, disposalPrice, notes)
 │
 ├─ 1. Calculate Pro-Rata Share
 │    ├── cost = asset purchase_price
 │    ├── pool_total_cost = pool.total_cost
 │    ├── pool_accum = pool.accumulated_depreciation
 │    ├── share = pool_accum × (cost / pool_total_cost)
 │    ├── book_value = cost − share
 │    └── gain_loss = disposal_price − book_value
 │
 ├─ 2. Journal Lines
 │    ├── DR  Pool Accum. Depreciation Account .. share
 │    ├── CR  Pool Asset Account ............... cost
 │    ├── DR  Paid From Account ................ disposal_price (if > 0)
 │    └── DR/CR  Gain/Loss Account ............. gain_loss
 │
 ├─ 3. Update Pool
 │    └── DECREMENT pool.accumulated_depreciation by share
 │
 ├─ 4. Update Asset
 │    ├── status → 'disposed'
 │    └── asset_pool_id → null (detach from pool)
 │
 └─ 5. Record Lifecycle Event ('disposal')
      └── meta: { notes, mode: 'pool', pool_accumulated_share }
```

---

## 13. Lifecycle Event Tracking

### FixedAssetLifecycleEvent Model

Every significant financial event on an asset is recorded via `recordLifecycleEvent()`:

| Field | Type | Description |
|-------|------|-------------|
| `fixed_asset_id` | FK | The asset |
| `journal_entry_id` | FK | Linked JE for drill-down |
| `event_type` | String | Type of event |
| `transaction_date` | Date | When the event occurred |
| `amount` | Decimal | Financial amount |
| `accumulated_depreciation_after` | Decimal | Snapshot after event |
| `book_value_after` | Decimal | NBV snapshot after event |
| `proceeds` | Decimal | Disposal proceeds (nullable) |
| `gain_loss` | Decimal | Gain/loss amount (nullable) |
| `description` | String | Human-readable description |
| `metadata` | JSON | Extra context (method, costs, etc.) |
| `created_by` | FK → User | Who performed the action |

### Event Types

| Event Type | Trigger | Amount Represents |
|------------|---------|-------------------|
| `acquisition` | `postPurchaseEntry()` | Purchase price |
| `capitalization` | `postPurchaseEntry()` | Asset cost (incl. landed) |
| `monthly_depreciation` | `postMonthlyDepreciation()` | Monthly depreciation charge |
| `disposal` | `dispose()` | Book value at disposal |
| `transfer` | (future) | Transfer amount |
| `revaluation` | (future) | Revaluation adjustment |
| `impairment` | (future) | Impairment loss |

### Idempotency

Events use `updateOrCreate` with a composite key of `[fixed_asset_id, event_type, transaction_date, journal_entry_id]` to prevent duplicate entries.

---

## 14. GL Journal Entry Integration

### Journal Entry References

| Event | Reference Format | Description |
|-------|-----------------|-------------|
| Purchase | `FA-PURCHASE-{id:05d}` | Asset acquisition |
| Depreciation | `DEPR-{asset_id}-{YYYYMM}` | Monthly individual depreciation |
| Pool Depreciation | `POOL-DEPR-{pool_id}-{YYYYMM}` | Monthly pool depreciation |
| Disposal | `DISPOSAL-{asset_id}` | Individual asset disposal |
| Pool Disposal | `DISPOSAL-POOL-{asset_id}` | Pool asset disposal |

### All JEs are auto-posted (status = 'posted') upon creation.

---

## 15. Chart of Accounts Mapping

### Canonical Account Mapping (AccountSeeder.php)

The system's canonical Chart of Accounts defined in `AccountSeeder.php` provides the following fixed asset structures (`12xxx` series) and intangible assets (`13xxx` series), which the `EthiopianAssetCategoriesSetupService` maps to for the mandatory Ethiopian statutory types:

| Ethiopian Type | Canonical Asset Account | Canonical Accum. Deprec. Account |
|----------------|-------------------------|--------------------------------|
| Computers & Software | `12400` (Computers & Electronics) | `12410` |
| Buildings | `12200` (Buildings) | `12210` |
| Intangibles | `13100` (Intangible Assets) | `13110` |
| Mining & Petroleum | `12300` (Machinery & Equipment) | `12310` |
| Greenhouses | `12800` (Leasehold Improvements)* | `12810` |
| All Other | `12500` (Furniture) or `12600` (Office)* | `12510` / `12610` |

> *Note: For categories like "Greenhouses" and "All Other", there is not an exact 1:1 match in the seeder, so they map to the closest applicable `12xxx` sub-account (e.g., Leasehold Improvements or Furniture & Fixtures).*

### Shared Depreciation Expense Account

All categories share a single depreciation expense account:
- **Code**: `61600` — Depreciation Expense (from `AccountSeeder.php`)

### AccountSeeder Fixed Asset Accounts (canonical 5-digit)

| Code | Name | Type |
|------|------|------|
| `12000` | Fixed Assets (Header) | Fixed Assets |
| `12100` | Land | Fixed Assets |
| `12200` | Buildings | Fixed Assets |
| `12210` | Accum. Deprec. — Buildings | Contra (Fixed Assets) |
| `12300` | Machinery & Equipment | Fixed Assets |
| `12310` | Accum. Deprec. — Machinery & Equipment | Contra |
| `12400` | Computers & Electronics | Fixed Assets |
| `12410` | Accum. Deprec. — Computers & Electronics | Contra |
| `12500` | Furniture & Fixtures | Fixed Assets |
| `12510` | Accum. Deprec. — Furniture & Fixtures | Contra |
| `12600` | Office Equipment | Fixed Assets |
| `12610` | Accum. Deprec. — Office Equipment | Contra |
| `12700` | Vehicles | Fixed Assets |
| `12710` | Accum. Deprec. — Vehicles | Contra |
| `12800` | Leasehold Improvements | Fixed Assets |
| `12810` | Accum. Deprec. — Leasehold Improvements | Contra |
| `12900` | Accumulated Depreciation — Control | Contra |
| `13100` | Intangible Assets | Other Assets |
| `13110` | Accumulated Amortization | Contra (Other Assets) |
| `61600` | Depreciation Expense | Expenses |

> **⚠️ Important Note on Legacy Mapping**: The codebase (`EthiopianAssetCategoriesSetupService.php`) previously generated un-seeded `15xxx` branch accounts at runtime instead of mapping to the canonical `12xxx`/`13xxx` branches. This documentation reflects the canonical unified hierarchy defined by `AccountSeeder.php`.

---

## 16. Ethiopian Asset Categories Setup Service

### EthiopianAssetCategoriesSetupService.setup()

Called during company creation to auto-seed the six mandatory Ethiopian asset categories.

```
setup(Company)
 │
 ├─ 1. Resolve Account Types
 │    ├── Fixed Assets type ID
 │    ├── Accumulated Depreciation type ID
 │    └── Expenses type ID (for depreciation expense)
 │
 ├─ 2. Find/Create Depreciation Expense Account
 │    └── Looks for codes: 61600, 60600, or 60000
 │        Falls back to creating 61600 if not found
 │
 ├─ 3. For Each of 6 Ethiopian Category Types:
 │    ├── Skip if category already exists for company
 │    ├── Find/Create Asset Account (canonical 12xxx/13xxx codes)
 │    │    └── Search by name → search by code → create
 │    ├── Find/Create Accumulated Depreciation Account (canonical 12xx0/13xx0 codes)
 │    │    └── Search by name → search by code → create
 │    └── Create AssetCategory record
 │         ├── ethiopian_category_type, name, method
 │         ├── rates (sl, dv)
 │         ├── flags (allow_dv, is_poolable, is_system_default)
 │         └── GL account FKs
 │
 └─ Returns: count of categories created
```

### ensureAllCategoriesHaveAccounts(Company)

A repair utility that:
1. Iterates all existing categories for a company
2. For any with missing GL accounts, creates/links them
3. Includes name-based re-validation to fix mislinked accounts
4. Handles Mining & Petroleum correction (was previously mapped to Furniture → now Machinery & Equipment `12300`)
5. For custom (non-statutory) categories, auto-assigns appropriate available codes in the `12xxx` range

---

## 17. Asset Status State Machine

### FixedAssetStatus Enum

```
┌──────────┐                    ┌──────────────────┐
│  Active  │───── depreciate ──►│ Fully Depreciated │
│          │      (automatic)   │                  │
└────┬─────┘                    └────────┬─────────┘
     │                                   │
     │         dispose()                 │  dispose()
     │                                   │
     ▼                                   ▼
┌──────────┐                    ┌──────────┐
│ Disposed │                    │ Disposed │
└──────────┘                    └──────────┘

    ┌──────┐
    │ Idle │  (manual — asset not in service)
    └──────┘
```

| Status | Value | Color | Description |
|--------|-------|-------|-------------|
| `Active` | `active` | Success (green) | In service, depreciating |
| `FullyDepreciated` | `fully_depreciated` | Info (blue) | At salvage value |
| `Disposed` | `disposed` | Danger (red) | Removed from register |
| `Idle` | `idle` | Warning (yellow) | Temporarily out of service |

### Active Statuses

```php
FixedAssetStatus::activeStatuses()  // [Active, Idle]
```

### Transitions

- **Active → Fully Depreciated**: Automatic when `accumulated_depreciation >= (cost − salvage)`
- **Active → Disposed**: Manual via disposal action
- **Fully Depreciated → Disposed**: Manual via disposal action
- **Active ↔ Idle**: Manual status change (no JE impact)

---

## 18. Filament UI Layer

### Navigation Structure

```
Accounting (navigation group)
 ├── Fixed Assets (FixedAssetResource)
 │    ├── List (table with status, category, cost, NBV)
 │    ├── Create (4-step wizard)
 │    │    ├── Step 1: Classification (category, name, reference)
 │    │    ├── Step 2: Financial Setup (price, VAT, salvage, imported costs)
 │    │    ├── Step 3: Usage & Depreciation (dates, pool, certificate, preview)
 │    │    └── Step 4: Payment (paid from account)
 │    ├── View (infolist + financial summary + depreciation schedule)
 │    │    ├── Classification section
 │    │    ├── Usage & Depreciation section
 │    │    ├── Financial Details section (with imported cost breakdown)
 │    │    ├── Depreciation Schedule section (annual projection)
 │    │    ├── Lifecycle Events relation manager
 │    │    └── Header Actions: Edit, Dispose Asset, Delete
 │    └── Edit (same wizard schema)
 │
 ├── Asset Pools (AssetPoolResource)
 │    ├── List (table with category, method, rate, total cost, accum, balance)
 │    ├── Create / Edit (form schema)
 │    └── View
 │
 ├── Asset Categories (AssetCategoryResource)
 │    └── CRUD for categories with statutory type selection
 │
 ├── Post Monthly Depreciation (standalone page)
 │    ├── Select posting date
 │    ├── Multi-select standalone assets
 │    ├── Multi-select pools
 │    └── Batch post button
 │
 └── Reports (under AssetReports cluster)
      ├── Asset Register Report
      ├── Depreciation Schedule Report
      ├── Asset Movement Report
      ├── Disposal Report
      └── Asset Audit Trail Report
```

### Create Wizard — Live Depreciation Preview

Step 3 of the create wizard includes a real-time depreciation preview placeholder that calculates:
- Monthly depreciation amount
- Annual depreciation amount
- Net Book Value after Year 1
- Applied rate label (e.g., "SL 5%" or "DV 25%")

Updates live as the user changes cost, salvage, business-use %, and category.

### Pool Assignment Logic

The pool selector in Step 3:
- Only visible when the selected category `isPoolable()`
- Filters pools to those matching the selected `asset_category_id`
- Placeholder: "None (depreciate individually)"

### Buildings: Certificate of Completion

For buildings category:
- A collapsible section appears with `certificate_of_completion_date` and `certificate_reference`
- Validation rule: depreciation start date must be ≥ certificate date

---

## 19. Reporting Suite

### 5 Asset Reports (under `Reports > Assets` cluster)

All reports support:
- **Period filtering**: Presets (YTD, last year, quarterly) + custom date range
- **Export formats**: PDF (DomPDF), Excel (OpenSpout XLSX), CSV
- **Permission-gated access**
- **Drill-down links** to asset view pages and journal entries

---

#### 1. Asset Register Report

| Permission | `reports.assets.asset_register.view` |
|---|---|
| **Purpose** | Static snapshot of all fixed assets as of a date |
| **Columns** | Asset, Reference, Category, Purchase Date, Cost, Accum. Deprec., NBV |
| **Totals** | Total Cost, Total Accumulated, Total NBV |

---

#### 2. Depreciation Schedule Report

| Permission | `reports.assets.depreciation_schedule.view` |
|---|---|
| **Purpose** | Periodic depreciation postings by asset with JE drill-down |
| **Data Source** | `DepreciationLog` table |
| **Columns** | Posting Date, Asset, Reference, Category, Amount, Running Accumulated |
| **Totals** | Total Depreciation |

---

#### 3. Asset Movement Report

| Permission | `reports.assets.asset_movement.view` |
|---|---|
| **Purpose** | Aggregated additions, transfers, and value changes over a period |
| **Data Source** | `FixedAssetLifecycleEvent` table |
| **Summary** | Additions (acquisition + capitalization), Transfers, Changes (revaluation + impairment + depreciation) |
| **Columns** | Date, Event Type, Asset, Reference, Amount, Description |

---

#### 4. Disposal Report

| Permission | `reports.assets.disposal_report.view` |
|---|---|
| **Purpose** | Disposed assets with proceeds and gain/loss analysis |
| **Data Source** | `FixedAssetLifecycleEvent WHERE event_type = 'disposal'` |
| **Columns** | Date, Asset, Reference, Book Value, Proceeds, Gain/Loss, Description |
| **Totals** | Total Book Value, Total Proceeds, Total Gain/Loss |

---

#### 5. Asset Audit Trail Report

| Permission | `reports.assets.asset_audit_trail.view` |
|---|---|
| **Purpose** | Full traceability of asset lifecycle with source entry links |
| **Data Source** | `FixedAssetLifecycleEvent` (all types) |
| **Columns** | Date, Asset, Reference, Event, Amount, Book Value After, Created By, Description |
| **Drill-down** | Links to asset view page + journal entry view |

---

## 20. Security & Permissions

### FixedAssetPolicy

Extends `BasePolicy` with module `asset` and resource `fixed_asset`:

| Permission | Controls |
|-----------|----------|
| `asset.fixed_asset.index` | View list |
| `asset.fixed_asset.view` | View single asset |
| `asset.fixed_asset.create` | Create new asset |
| `asset.fixed_asset.update` | Edit + Dispose |
| `asset.fixed_asset.delete` | Delete asset |
| `asset.asset_pool.index` | View pools list |
| `asset.depreciation.process` | Access Post Monthly Depreciation page |

### Report Permissions

| Report | Permission |
|--------|-----------|
| Asset Register | `reports.assets.asset_register.view` |
| Depreciation Schedule | `reports.assets.depreciation_schedule.view` |
| Asset Movement | `reports.assets.asset_movement.view` |
| Disposal Report | `reports.assets.disposal_report.view` |
| Asset Audit Trail | `reports.assets.asset_audit_trail.view` |

---

## 21. Dashboard KPIs

### AssetManagerKpiWidget

Three KPI stats on the dashboard (cached for 10 minutes per company per month):

| Stat | Value | Description |
|------|-------|-------------|
| **Asset NBV** | `money(gross_cost − accumulated_dep)` | With subtitle showing gross cost |
| **Accum. Depreciation** | `money(accumulated_dep)` | With subtitle showing total asset count |
| **Asset Lifecycle** | `Active: {count}` | With subtitle showing disposed asset count |

All stats link to the Fixed Assets list page.

---

## Summary: End-to-End Fixed Asset Flow

```
1. SETUP (one-time per company)
   EthiopianAssetCategoriesSetupService.setup()
   → Seeds 6 statutory categories + GL accounts + rates

2. CONFIGURE (admin)
   ├── Review auto-seeded categories
   ├── Create additional custom categories if needed
   ├── Create Asset Pools for poolable categories
   └── Verify GL account mappings

3. ASSET ACQUISITION
   ├── Create Fixed Asset (4-step wizard)
   │    ├── Select category → inherits method, rates, GL accounts
   │    ├── Enter cost, VAT treatment, salvage value
   │    ├── Enter dates (purchase, ready-for-use, cert if building)
   │    ├── Optionally assign to pool
   │    └── Select payment source (bank/cash)
   └── afterCreate() → postPurchaseEntry() → JE auto-posted

4. MONTHLY DEPRECIATION
   ├── Navigate to Post Monthly Depreciation page
   ├── Select posting date (default: end of month)
   ├── Select standalone assets and/or pools
   └── Click "Post Depreciation"
        ├── Individual assets: postMonthlyDepreciation() per asset
        ├── Pools: postMonthlyDepreciation() per pool
        └── JEs auto-posted; accumulated_depreciation incremented

5. STATUS TRANSITIONS
   ├── Active → Fully Depreciated (automatic when accum ≥ cost − salvage)
   ├── Active / Fully Depreciated → Disposed (manual via Dispose action)
   └── Active ↔ Idle (manual status change)

6. DISPOSAL
   ├── Click "Dispose Asset" on View page
   ├── Enter disposal date, type, proceeds, notes
   └── System calculates gain/loss, posts disposal JE
        ├── Individual: direct JE with accum reversal + cost removal
        └── Pool: pro-rata share calculation, pool decremented

7. REPORTING
   ├── Asset Register (snapshot of all assets + NBV)
   ├── Depreciation Schedule (all postings with JE drill-down)
   ├── Asset Movement (additions, transfers, changes)
   ├── Disposal Report (proceeds, gain/loss analysis)
   └── Asset Audit Trail (full lifecycle traceability)
```

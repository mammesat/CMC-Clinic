# CMC Clinic — Enterprise Workflow Architecture

## Clinic Accounting, Pharmacy & Human Resource Management System (CAPHRMS)

---

## 1. Executive Architecture Overview

### 1.1 System Philosophy

The architecture follows a **"Hub-and-Spoke" financial model** with the **Billing & Cashier module** as the central revenue hub. All revenue-generating activities flow through this hub, while the **Accounting module** serves as the ledger of record. The **Pharmacy module** operates as both a revenue center (dispensing) and a cost center (inventory), requiring tight integration with billing and accounting. The **Human Resource module** manages the clinic's workforce lifecycle, attendance, leave, and payroll computation, with payroll outputs flowing directly into the General Ledger as controlled salary expenses.

The **Financial Manager** serves as the supervisory control layer between operational execution and strategic governance, owning fiscal period management, payroll approval, mid-tier financial authorization, and month-end financial integrity verification. The **Board President** retains ultimate authority over strategic policy, high-value approvals, fiscal year configuration, and executive oversight.

### 1.2 Core Design Principles

| Principle                        | Implementation                                                                                                                                                    |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Single Source of Truth** | PostgreSQL with strict referential integrity; HR payroll data flows directly to Accounting GL without manual re-entry                                             |
| **Financial Immutability** | Posted transactions cannot be edited; payroll reversals require Financial Manager approval with full audit trail                                                  |
| **Four-Eyes Control**      | Segregation across operational (Receptionist/Pharmacist/HR), supervisory (Manager/Accountant), control (Financial Manager), and executive (Board President) tiers |
| **Real-time Visibility**   | Dashboards refresh transactionally; Financial Manager sees live cash position and payroll status                                                                  |
| **Defensive Design**       | Business rules enforced at database, application, workflow, and approval levels                                                                                   |

### 1.3 Role Authority Matrix

| Function                        |  Receptionist  |   Pharmacist   |    HR    | Accountant |     Manager     |   Financial Manager   | Board President |
| ------------------------------- | :------------: | :-------------: | :-------: | :---------: | :--------------: | :-------------------: | :-------------: |
| **Patient Registration**  | ✓ Create/Edit |       ✗       |    ✗    |     ✗     |      ✓ All      |          ✗          |       ✗       |
| **Invoice Creation**      |  ✓ POS/Draft  |       ✗       |    ✗    |     ✗     |      ✓ All      |        ✓ View        |       ✗       |
| **Payment Collection**    |  ✓ Cash/Card  |       ✗       |    ✗    |     ✗     |   ✓ Override   |          ✗          |       ✗       |
| **Medicine Dispensing**   |       ✗       |     ✓ Full     |    ✗    |     ✗     |     ✓ View     |          ✗          |       ✗       |
| **Inventory Adjustments** |       ✗       | ✓ ≤ ETB 5,000 |    ✗    |     ✗     |  ✓ > ETB 5,000  |        ✓ View        |       ✗       |
| **Employee Records**      |       ✗       |       ✗       |  ✓ Full  |     ✗     |     ✓ View     |        ✓ View        |     ✓ View     |
| **Attendance & Leave**    |       ✗       |       ✗       | ✓ Manage |     ✗     |    ✓ Approve    |          ✗          |       ✗       |
| **Payroll Input**         |       ✗       |       ✗       | ✓ Enter |  ✓ Assist  |        ✗        |    ✓ Approve/Post    |     ✓ View     |
| **Journal Entries**       |       ✗       |       ✗       |    ✗    |  ✓ Create  |        ✗        |    ✓ Post/Approve    |     ✓ View     |
| **Expense Record**        |       ✗       |       ✗       |    ✗    |  ✓ Record  | ✓ ≤ ETB 10,000 | ✓ ETB 10,000–50,000 | ✓ > ETB 50,000 |
| **Expense Approval**      |       ✗       |       ✗       |    ✗    |     ✗     | ✓ ≤ ETB 10,000 | ✓ ETB 10,000–50,000 | ✓ > ETB 50,000 |
| **Financial Reports**     |       ✗       |       ✗       |    ✗    | ✓ Standard |  ✓ Operational  |  ✓ All + Management  |  ✓ Executive  |
| **System Settings**       |       ✗       |       ✗       |    ✗    |     ✗     |  ✓ Operational  |  ✓ Fiscal/Financial  |  ✓ Strategic  |
| **User Management**       |       ✗       |       ✗       |    ✗    |     ✗     |     ✓ Staff     |          ✗          |    ✓ Roles    |
| **Equity Operations**     |       ✗       |       ✗       |    ✗    | ✓ Record   |     ✓ View      |    ✓ Approve/Post    |    ✓ View/EGM  |

---

## 2. Complete Dashboard Navigation Menu Structure

```
CMC Clinic System
│
├── 📊 Dashboard [All Roles — Contextual]
│   ├── Executive Overview [Board President, Financial Manager]
│   ├── Financial Management [Financial Manager]
│   ├── Cashier Dashboard [Receptionist]
│   ├── Pharmacy Dashboard [Pharmacist]
│   ├── HR Operations [HR]
│   ├── Accounting Dashboard [Accountant]
│   └── Manager Operations [Manager]
│
├── 🏥 Patient Management [Receptionist, Manager]
│   ├── Patient Directory
│   ├── New Patient Registration
│   ├── Patient Visits History
│   └── Patient Search
│
├── 💰 Billing & Cashier [Receptionist, Manager, Financial Manager (view)]
│   ├── Point of Sale (Quick Invoice)
│   ├── Invoice Management
│   │   ├── All Invoices
│   │   ├── Draft Invoices
│   │   ├── Pending Payments
│   │   └── Overdue Invoices
│   ├── Payment Collection
│   ├── Daily Cash Register
│   └── Receipt Reprint
│
├── 💊 Pharmacy [Pharmacist, Manager]
│   ├── Dispensing Station
│   │   ├── New Dispensation
│   │   ├── Pending Prescriptions
│   │   └── Dispensing History
│   ├── Medicine Catalog
│   │   ├── All Medicines
│   │   ├── Categories
│   │   └── Price Management
│   ├── Inventory Management
│   │   ├── Current Stock Levels
│   │   ├── Batch Tracking
│   │   ├── Stock Adjustments
│   │   └── Expiry Alerts
│   └── Purchase Orders
│       ├── Create PO
│       ├── Pending Orders
│       ├── Goods Receipt
│       └── PO History
│
├── 📦 Inventory & Suppliers [Manager, Pharmacist (view), Financial Manager (view)]
│   ├── Supplier Directory
│   ├── Supplier Performance
│   ├── Inventory Valuation
│   ├── Reorder Recommendations
│   └── Stock Movement History
│
├── 📒 Accounting [Accountant, Financial Manager, Board President (read)]
│   ├── Chart of Accounts
│   ├── Journal Entries
│   │   ├── Create Entry
│   │   ├── Draft Entries
│   │   ├── Posted Entries
│   │   └── Entry Templates
│   ├── Accounts Receivable
│   │   ├── Customer Aging
│   │   ├── Invoice Reconciliation
│   │   └── Collection Tracking
│   ├── Accounts Payable
│   │   ├── Supplier Aging
│   │   ├── PO Reconciliation
│   │   └── Payment Scheduling
│   ├── Bank & Cash
│   │   ├── Cash Book
│   │   ├── Bank Reconciliation
│   │   └── Petty Cash
│   └── Expense Management
│       ├── Record Expense
│       ├── Expense Categories
│       ├── Recurring Expenses
│       └── Expense Approval [Manager, Financial Manager, Board President]
│
├── 🏢 Fixed Assets [Accountant, Financial Manager]
│   ├── Asset Directory
│   ├── Asset Pools
│   ├── Statutory Categories
│   ├── Depreciation Processing
│   │   ├── Monthly Depreciation Review
│   │   └── Post Depreciation
│   └── Asset History
│       ├── Event Logs
│       └── Disposals
│
├── 📊 Equity [PLC/SC/SM-PLC — visible only when Company::isShareBased()]
│   ├── Share Classes
│   ├── Shareholders
│   ├── Share Transactions
│   ├── Issue Shares (3-Step Wizard)
│   ├── Treasury Stock (Tab: Buyback | Reissue)
│   ├── Dividends
│   │   ├── Dividend Declarations
│   │   ├── Dividend Payments (Batch)
│   │   └── WHT Remittance
│   ├── Compliance Dashboard
│   │   └── Equity Compliance Alerts
│   └── Board & Governance [SC only]
│       ├── Board Members
│       └── EGM Log
│
├── 👥 Human Resource [HR, Manager, Financial Manager, Board President (view)]
│   ├── Employee Records
│   │   ├── Employee Directory
│   │   ├── Contracts & Documents
│   │   └── Employment History
│   ├── Attendance & Time
│   │   ├── Daily Attendance
│   │   ├── Timesheet Review
│   │   └── Shift Scheduling
│   ├── Leave Management
│   │   ├── Leave Requests
│   │   ├── Leave Balances
│   │   └── Leave Calendar
│   └── Payroll [Financial Manager, HR (input), Accountant (input), Manager (view)]
│       ├── Payroll Profiles
│       ├── Payroll Periods
│       ├── Payroll Inputs
│       │   ├── Variable Earnings
│       │   └── Variable Deductions
│       ├── Payroll Sheets
│       ├── Payroll Runs
│       │   ├── Compute Draft
│       │   ├── Review & Approve
│       │   ├── Post Payroll
│       │   └── Statutory Reports
│       │       ├── Payslip Report
│       │       ├── Pension Report
│       │       └── Tax Report
│       └── Payroll History
│           ├── Posted Payrolls
│           └── Reversals
│
├── 📈 Reports & Analytics [Financial Manager, Board President, Manager, Accountant (selected)]
│   ├── Financial Reports
│   │   ├── Income Statement (P&L)
│   │   ├── Balance Sheet
│   │   ├── Cash Flow Statement
│   │   ├── Trial Balance
│   │   └── General Ledger Detail
│   ├── Pharmacy Reports
│   │   ├── Dispensing Summary
│   │   ├── Stock Valuation (FIFO)
│   │   ├── Expiry Report
│   │   └── Fast/Slow Moving Items
│   ├── Sales & Revenue
│   │   ├── Daily Sales Summary
│   │   ├── Revenue by Category
│   │   ├── Payment Method Analysis
│   │   └── Outstanding Debtors
│   ├── Inventory Reports
│   │   ├── Stock Status
│   │   ├── Purchase Analysis
│   │   ├── Supplier Ledger
│   │   └── Inventory Turnover
│   ├── Equity Reports [visible when isShareBased()]
│   │   ├── Statement of Changes in Equity
│   │   ├── Retained Earnings Statement
│   │   ├── Dividend Distribution Report
│   │   ├── Dividend Payment History
│   │   ├── Dividend Tax Report (WHT)
│   │   ├── Share Authorization Report
│   │   └── Share Register Report
│   └── Executive Dashboards
│       ├── KPI Overview
│       ├── Revenue Trends
│       ├── Expense Breakdown
│       ├── Profitability Analysis
│       └── Comparative Periods
│
├── ⚙️ Administration [Manager, Board President, Financial Manager (fiscal)]
│   ├── User Management
│   │   ├── Staff Directory
│   │   ├── Role Permissions
│   │   └── Access Logs
│   ├── Clinic Settings
│   │   ├── Business Profile
│   │   ├── Business Rules
│   │   │   ├── Default Accounts
│   │   │   ├── Account Mapping
│   │   │   ├── VAT Percentage
│   │   │   └── Payroll Rules
│   │   ├── Fiscal Year
│   │   │   └── Fiscal Periods/Months
│   │   ├── Payment Terms
│   │   ├── Approval Thresholds
│   │   ├── Invoice Templates
│   │   ├── System Preferences
│   │   ├── Equity Configuration
│   │   └── Compliance Thresholds
│   ├── Audit Trail
│   │   ├── System Logs
│   │   ├── Financial Audit
│   │   └── Inventory Audit
│   └── Data Management
│       ├── Backup Status
│       └── Data Export
│
└── 👤 Profile [All Roles]
    ├── My Account
    ├── Change Password
    └── Activity Log
```

---

## 3. Module Interaction Architecture

### 3.1 System Integration Map

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         EXECUTIVE DASHBOARD LAYER                        │
│         (Board President — Strategic | Financial Manager — Tactical)      │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        ▼                           ▼                           ▼
┌───────────────┐         ┌───────────────┐         ┌───────────────────┐
│   REPORTING   │◄────────│   ACCOUNTING  │◄────────│   BILLING &       │
│   & ANALYTICS │         │   (GL/AR/AP)  │         │   CASHIER         │
│               │         │               │         │   (Revenue Hub)   │
└───────────────┘         └───────┬───────┘         └─────────┬─────────┘
        ▲                         │                           │
        │                         ▼                           ▼
        │                 ┌───────────────┐         ┌───────────────┐
        │                 │  BANK & CASH    │         │  PATIENT MGMT │
        │                 │  (Cash Book)    │         │               │
        │                 └───────────────┘         └───────────────┘
        │                                                 │
        │                         ┌───────────────────────┘
        │                         ▼
        │                 ┌───────────────┐         ┌───────────────┐
        │                 │   PHARMACY    │◄───────►│   INVENTORY   │
        │                 │  (Dispensing)   │         │   & SUPPLIERS │
        │                 └───────────────┘         └───────────────┘
        │                         ▲
        │                         │
        │                 ┌───────┴───────┐         ┌───────────────┐
        │                 │  HUMAN RESOURCE │         │    EQUITY     │
        │                 │    (Payroll)    │         │  (Statutory)  │
        │                 └───────────────┘         └───────┬───────┘
        │                         │                         │
        └─────────────────────────┴─────────────────────────┘
              (Payroll expense flows to GL; Equity transactions alter Cap Table & GL)
```

### 3.2 Data Flow Principles

1. **Patient Registration** → Creates master data used by all service modules
2. **Billing & Cashier** → Generates invoices and payments; feeds revenue to GL
3. **Pharmacy** → Consumes inventory, generates dispensing records linked to invoices
4. **Human Resource** → Manages employee master data, attendance, leave; feeds payroll computation
5. **Equity** → Manages ownership cap table, enforces statutory compliance, feeds dividends and share issuances directly into GL
6. **Accounting** → Consolidates all financial transactions; GL is the authoritative ledger
7. **Financial Manager** → Reviews and approves payroll, mid-tier expenses, journal entries, and fiscal period controls
8. **Reporting** → Aggregates from all modules in real-time via materialized views and controlled summaries

---

## 4. Detailed Operational Workflows

### 4.1 PATIENT MANAGEMENT WORKFLOW

#### Process: New Patient Registration

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Receptionist│────►│ System      │────►│ Patient     │
│         │     │ greets,     │     │ validates   │     │ Card        │
│         │     │ requests ID │     │ uniqueness  │     │ generated   │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
                    ┌────────────────────────────────────────────┘
                    ▼
           ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
           │ Registration│────►│ Card fee    │────►│ Patient     │
           │ fee invoice │     │ collected   │     │ record      │
           │ auto-created│     │ (Cashier)   │     │ active      │
           └─────────────┘     └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Receptionist** initiates "New Patient Registration" from Patient Management menu
2. System auto-checks for existing patient using ID card, phone number, and fuzzy name matching
3. If unique: Patient master record created with `status = active`, `registration_date = today`
4. System auto-generates **Registration Fee Invoice** linked to `invoice_type = 'registration'`
5. Receptionist collects payment via "Payment Collection" screen; links payment to invoice
6. Patient card printed; patient becomes eligible for all clinical services

**Data Integrity Rules:**

- Patient cannot be hard-deleted if any invoice, visit, or dispensing record exists (soft delete only)
- Patient ID is immutable after creation
- Registration fee invoice must be paid or marked as "waived" (requires Manager override) before clinical services are rendered

---

### 4.2 BILLING & CASHIER WORKFLOW

#### 4.2.1 Point of Sale (Quick Invoice) — Primary Revenue Process

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  START  │────►│ Receptionist│────►│ Select      │────►│ Add service │
│         │     │ opens POS   │     │ patient     │     │ items:      │
│         │     │ screen      │     │ (search/scan│     │ - Clinical  │
│         │     │             │     │  card)      │     │ - Lab       │
│         │     │             │     │             │     │ - Pharmacy  │
│         │     │             │     │             │     │ - Misc      │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ System      │────►│ Receptionist│────►│ Patient     │────►│ Invoice     │
│ calculates  │     │ reviews &   │     │ pays        │     │ status:     │
│ totals,     │     │ confirms    │     │ (cash/card) │     │ 'paid'      │
│ applies VAT │     │ invoice     │     │             │     │ Receipt     │
│ (if config) │     │             │     │             │     │ generated   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
        │
        ▼
┌─────────────┐     ┌─────────────┐
│ If pharmacy │────►│ Dispensing  │
│ items exist:│     │ queue auto- │
│ create      │     │ created     │
│ dispensing  │     │ (Pharmacist │
│ request     │     │ notified)   │
└─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Receptionist** opens "Point of Sale" screen; system defaults to today's date
2. Patient selected via card scan, phone search, or walk-in (creates temporary patient record if needed)
3. Service items added from predefined catalog:
   - **Clinical Services:** Consultation, procedure codes (linked to `service_catalog`)
   - **Laboratory Services:** Lab test codes (linked to `lab_services`)
   - **Pharmacy Items:** Medicine codes (linked to `medicine_catalog`; real-time stock check)
   - **Miscellaneous:** Custom line items with manual description and price
4. System validates:
   - Pharmacy items: stock availability check (sufficient quantity?)
   - Prices: pulled from catalog (Manager can override with full audit trail)
   - VAT: calculated per business rules settings
5. Invoice status set to **'draft'** — editable by creator only
6. Receptionist clicks "Confirm & Collect Payment"
7. Payment recorded in `payments` table; linked to invoice; invoice status → **'paid'**
8. If pharmacy items included: auto-create dispensing request in Pharmacy module with status **'pending'**
9. Receipt printed; patient directed to pharmacy or clinical area

**Financial Transaction Lifecycle:**

```
Invoice Created (draft)
    ↓
Payment Recorded (cash/card)
    ↓
Cash Register Updated (daily tally)
    ↓
End-of-Day Reconciliation (Manager verifies)
    ↓
Accounting Sync (journal entry auto-created: Debit Cash, Credit Revenue)
```

#### 4.2.2 Invoice Management Sub-Workflows

**Draft Invoices:**

- Drafts auto-expire after 24 hours (configurable in Clinic Settings)
- Only creator can edit; others can view
- Conversion to final invoice requires payment or credit approval

**Pending Payments (Credit/Partial Pay):**

- Receptionist can mark invoice as "partial payment" with amount received
- Balance tracked in `accounts_receivable` (Accounting module auto-updates)
- Overdue calculated from `payment_terms` (e.g., Net 7, Net 30)
- Daily reminder list generated for Receptionist dashboard

**Overdue Invoices:**

- Auto-flagged after payment term expiration
- Manager receives notification
- Collection workflow: Receptionist calls → Manager escalates → Board President approves write-off

---

### 4.3 PHARMACY WORKFLOW

#### 4.3.1 Dispensing Station — Core Process

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Pharmacist  │────►│ System      │────►│ Pharmacist  │
│         │     │ views       │     │ displays    │     │ verifies    │
│         │     │ "Pending    │     │ pending     │     │ prescription│
│         │     │ Prescrip-   │     │ prescriptions│     │ against     │
│         │     │ tions"      │     │ (from POS)  │     │ invoice     │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ System      │────►│ Pharmacist  │────►│ Patient     │────►│ Dispensing  │
│ checks stock│     │ confirms    │     │ receives    │     │ record      │
│ (sufficient?│     │ quantities  │     │ medicines   │     │ posted;     │
│ expired?)   │     │ & batch     │     │ & signs     │     │ stock       │
│             │     │ selection   │     │ receipt     │     │ deducted    │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
        │
        ▼
┌─────────────┐
│ If stock    │
│ insufficient:│
│ - Flag for  │
│   reorder   │
│ - Notify    │
│   Manager   │
└─────────────┘
```

**Operational Steps:**

1. **Pharmacist** opens "Dispensing Station" → "Pending Prescriptions" tab
2. Queue shows: Patient name, Invoice #, Items requested, Priority (normal/urgent)
3. Pharmacist selects prescription; system displays:
   - Invoice details (read-only)
   - Required medicines with requested quantities
   - Available stock by batch (FIFO auto-suggested, manual override allowed)
   - Expiry dates (warning if less than 3 months)
4. **Stock Validation:**
   - If sufficient: proceed to dispensing
   - If insufficient: system flags "Stock Shortage"; auto-creates reorder recommendation; notifies Manager
   - If expired: blocks dispensing; requires stock adjustment (write-off) before proceeding
5. Pharmacist selects batches (FIFO enforced by default); system calculates exact cost of goods sold (COGS) per batch
6. Patient signs digital or paper receipt; dispensing status → **'completed'**
7. Stock automatically deducted from `inventory_batches`; `stock_movements` record created
8. Dispensing record linked to invoice for revenue recognition and COGS posting

**Batch Tracking (FIFO):**

```
Inventory Received: Batch A (100 units @ ETB 250) → Batch B (100 units @ ETB 300)
Dispensing 150 units:
  - 100 units from Batch A (COGS = ETB 25,000)
  - 50 units from Batch B (COGS = ETB 15,000)
Total COGS = ETB 40,000 → Auto-posted to Accounting (Debit COGS, Credit Inventory Asset)
```

#### 4.3.2 Inventory Management Sub-Workflows

**Stock Adjustments:**

- **Pharmacist** can adjust ≤ ETB 5,000 value (spoilage, breakage, expiry)
- **Manager** approval required for > ETB 5,000 or > 10 units
- Adjustment creates `stock_movement` record with mandatory reason code
- Accounting auto-sync: Debit Adjustment Expense, Credit Inventory Asset

**Expiry Alerts:**

- Daily automated scan: items expiring within 30/60/90 days
- Pharmacist receives notification; can initiate:
  - Return to supplier (if within return policy)
  - Discounted sale (Manager approval required)
  - Write-off (stock adjustment with reason = 'expired')

**Reorder Recommendations:**

- System calculates reorder point = (Average daily usage × Lead time) + Safety stock
- When stock ≤ reorder point: auto-generate recommendation
- Pharmacist reviews → converts to Purchase Order or dismisses

---

### 4.4 INVENTORY & SUPPLIERS WORKFLOW

#### 4.4.1 Purchase Order Lifecycle

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Manager/    │────►│ System      │────►│ Manager     │
│         │     │ Pharmacist  │     │ generates   │     │ reviews &   │
│         │     │ initiates   │     │ PO from     │     │ approves PO │
│         │     │ "Create PO" │     │ reorder     │     │ (or manual) │
│         │     │             │     │ recommendations│   │             │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ PO sent to  │────►│ Supplier    │────►│ Goods       │────►│ Manager/    │
│ supplier    │     │ delivers;   │     │ Receipt     │     │ Pharmacist  │
│ (email/print│     │ invoice     │     │ recorded;   │     │ verifies    │
│ /phone)     │     │ attached    │     │ stock added │     │ quantities  │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
        │                                                         │
        ▼                                                         ▼
┌─────────────┐                                           ┌─────────────┐
│ Supplier    │                                           │ AP Invoice  │
│ invoice     │                                           │ created in  │
│ recorded in │◄──────────────────────────────────────────│ Accounting  │
│ AP module   │                                           │ (auto-sync) │
└─────────────┘                                           └─────────────┘
```

**Operational Steps:**

1. **Reorder Trigger:** System recommendation OR manual creation by Manager/Pharmacist
2. **PO Creation:** Select supplier (from approved supplier directory); add items; system pulls last purchase price as reference
3. **Approval:**
   - PO ≤ ETB 25,000: Manager approval
   - PO ETB 25,000–100,000: Financial Manager approval
   - PO > ETB 100,000: Board President approval
4. **Issuance:** PO status → 'sent'; supplier notified
5. **Goods Receipt:**
   - Pharmacist receives delivery; opens "Goods Receipt" screen
   - Verifies quantities, batch numbers, expiry dates against PO
   - If discrepancies: record partial receipt or reject; notify supplier
   - If accepted: stock added to `inventory_batches`; status → 'received'
6. **AP Invoice Creation:** Supplier invoice linked to PO; auto-creates Accounts Payable entry
7. **Payment Scheduling:** Accountant schedules payment based on terms; Financial Manager approves if above threshold

**Supplier Performance Tracking:**

- Metrics: On-time delivery percentage, quantity accuracy percentage, price variance, quality (expiry issues)
- Quarterly review by Manager; poor performers flagged for replacement

---

### 4.5 ACCOUNTING WORKFLOW

#### 4.5.1 Chart of Accounts Structure (Simplified for Clinic)

```text
ASSETS (1xxxx)
├── 11000 Current Assets
│   ├── 11100 Cash & Bank
│   │   ├── 11110 Cash on Hand
│   │   ├── 11120 Petty Cash
│   │   └── 11130 Bank Accounts
│   ├── 11200 Accounts Receivable
│   │   ├── 11210 Trade Receivables
│   │   ├── 11220 Allowance for Doubtful Accounts
│   │   ├── 11230 VAT Input (Purchase Tax)
│   │   ├── 11240 WHT Receivable — 3%
│   │   └── 11260 Advance Income Tax (Quarterly)
│   ├── 11300 Inventory
│   │   ├── 11310 Raw Materials & Medical Supplies
│   │   ├── 11320 Work in Progress
│   │   └── 11330 Inventory Reserve
│   └── 11400 Other Current Assets
│       ├── 11410 Prepaid Rent
│       ├── 11420 Prepaid Insurance
│       ├── 11430 Office Supplies
│       └── 11440 Prepayments to Suppliers
└── 12000 Fixed Assets
    ├── 12100 Land
    ├── 12200 Buildings
    ├── 12210 Accum. Deprec. — Buildings
    ├── 12300 Machinery & Equipment (Medical)
    ├── 12310 Accum. Deprec. — Machinery
    ├── 12400 Computers & Electronics
    └── 12500 Furniture & Fixtures

LIABILITIES (2xxxx)
├── 21000 Current Liabilities
│   ├── 21100 Accounts Payable
│   │   └── 21110 Trade Payables
│   ├── 21200 Taxes & Statutory Payable
│   │   ├── 21210 VAT Output (Sales Tax)
│   │   ├── 21220 WHT Payable
│   │   ├── 21230 Income Tax Payable (PAYE)
│   │   └── 21240 Dividend Withholding Tax Payable
│   ├── 21300 Other Current Liabilities
│   │   ├── 21310 Net Salaries Payable (Accrual)
│   │   ├── 21320 Pension Payable (Employee 7%)
│   │   ├── 21330 Pension Payable (Employer 11%)
│   │   ├── 21340 SHI Payable (Employee 1.5%)
│   │   ├── 21350 SHI Payable (Employer 1.5%)
│   │   ├── 21360 Other Payroll Deductions Payable
│   │   └── 21370 Patient Deposits / Advances
│   └── 21500 Dividends Payable
└── 22000 Long Term Debt
    └── 22100 Bank Loan

EQUITY (3xxxx)
├── 30000 Equity
│   │
│   ├── [Sole Proprietorship Only]
│   │   ├── 31000 Owner's Capital
│   │   └── 32000 Owner's Drawings (Contra-Equity)
│   │
│   ├── [PLC / SC / Single-Member PLC Only — Share-Based]
│   │   ├── 34000 Common Stock
│   │   ├── 34100 Preferred Stock
│   │   ├── 34200 Share Premium – Common
│   │   ├── 34210 Share Premium – Preferred
│   │   ├── 34300 Additional Paid-in Capital (APIC)
│   │   ├── 34500 Treasury Stock (Contra-Equity)
│   │   ├── 34600 Legal Reserve
│   │   ├── 34700 Dividend Distribution
│   │   └── 34800 Accumulated Other Comprehensive Income (AOCI)
│   │
│   └── [All Company Types]
│       ├── 33000 Retained Earnings
│       ├── 39000 Current Year Earnings (Income Summary)
│       └── 39900 Opening Balance Equity

REVENUE (4xxxx)
├── 40000 Revenue
│   ├── 41000 Clinical Services Revenue
│   ├── 42000 Laboratory Services Revenue
│   ├── 43000 Pharmacy Sales Revenue
│   ├── 44000 Registration & Fee Income
│   ├── 49000 Sales Returns & Allowances
│   └── 49100 Sales Discounts

COST OF SALES (5xxxx)
├── 50000 Cost of Sales
│   ├── 51000 Cost of Materials (Pharmacy COGS)
│   ├── 52000 Direct Labor
│   ├── 54000 Inventory Adjustments
│   └── 55000 Inventory Write-Down Expense

EXPENSES (6xxxx)
├── 60000 Operating Expenses
│   ├── 61000 Rent or Lease Expense
│   ├── 61100 Utilities Expense
│   │   ├── 61110 Electricity Expense
│   │   ├── 61120 Internet Expense
│   │   └── 61130 Water Expense
│   ├── 61200 Communication Expense
│   ├── 61400 Office Supplies & Stationery
│   ├── 61500 Repairs & Maintenance
│   ├── 61600 Depreciation Expense
│   ├── 62000 Payroll & Benefits
│   │   ├── 62100 Salaries & Wages Expense
│   │   ├── 62200 Overtime Expense
│   │   ├── 62300 Bonus Expense
│   │   ├── 62400 Employer Pension Expense (11%)
│   │   ├── 62600 Housing Allowance Expense
│   │   ├── 62700 Transport Allowance Expense
│   │   └── 62910 Employer SHI Expense (1.5%)
│   └── 63000 Advertising & Marketing

OTHER (9xxxx)
├── 90000 Other Income
│   └── 91000 Interest Earned
└── 93000 Other Expense
    ├── 93100 Interest Expense
    └── 93200 Exchange Gain/Loss
```

#### 4.5.2 Journal Entry Workflow

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Accountant  │────►│ Select      │────►│ Enter       │
│         │     │ creates     │     │ template or │     │ debit/credit│
│         │     │ journal     │     │ manual entry│     │ lines       │
│         │     │ entry       │     │             │     │             │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ System      │────►│ Accountant  │────►│ Approver    │────►│ Entry       │
│ validates   │     │ saves as    │     │ reviews &   │     │ posted to   │
│ balance     │     │ 'draft' or  │     │ approves    │     │ General     │
│ (debits =   │     │ 'pending'   │     │ (tiered)    │     │ Ledger      │
│ credits)    │     │             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Accountant** opens "Journal Entries" → "Create Entry"
2. Select from templates (recurring entries) or manual entry
3. Enter transaction lines; system enforces double-entry (total debits = total credits)
4. Save as **Draft** (editable) or **Pending** (awaiting approval)
5. **Approval Rules:**
   - Entry ≤ ETB 5,000: Accountant can post directly
   - Entry ETB 5,000–20,000: Manager approval required
   - Entry ETB 20,000–100,000: Financial Manager approval required
   - Entry > ETB 100,000: Board President approval required
6. Once approved: status → **Posted**; immutable; GL updated; reversal requires contra-entry

**Auto-Generated Journal Entries (System Integration):**

| Source Module       | Auto-Journal Entry                                                 | Frequency       |
| ------------------- | ------------------------------------------------------------------ | --------------- |
| POS Payment         | Debit Cash / Credit Revenue Account                                | Real-time       |
| Pharmacy Dispensing | Debit COGS / Credit Inventory                                      | Real-time       |
| Goods Receipt       | Debit Inventory / Credit AP                                        | Real-time       |
| Expense Recorded    | Debit Expense / Credit Cash or AP                                  | Real-time       |
| Stock Adjustment    | Debit Adjustment Expense / Credit Inventory                        | On approval     |
| Payroll Posted      | Debit Salaries Expense / Credit Bank, Tax Payable, Pension Payable | Per payroll run |
| End-of-Day Cash     | Debit Bank / Credit Cash (deposit)                                 | Daily           |
| Share Issuance      | Debit Cash/Bank / Credit Common Stock, Share Premium               | On approval     |
| Dividend Declaration| Debit Retained Earnings / Credit Div. Payable, WHT Payable         | On approval     |
| Dividend Payment    | Debit Dividends Payable / Credit Bank                              | On batch payment|
| Dividend WHT Remit  | Debit WHT Payable / Credit Bank                                    | On remittance   |
| Treasury Buyback    | Debit Treasury Stock / Credit Bank                                 | On approval     |
| Treasury Reissue    | Debit Cash / Credit Treasury Stock, Share Premium/Retained Earnings| On approval     |
| Legal Reserve Trans.| Debit Retained Earnings / Credit Legal Reserve                     | Fiscal year-end |

#### 4.5.3 Accounts Receivable Workflow

**From Billing Module:**

- Unpaid or partially paid invoices auto-create AR entries
- **Customer Aging:** 0-30 days, 31-60 days, 61-90 days, >90 days
- **Collection Tracking:** Receptionist logs collection attempts; Manager reviews weekly
- **Invoice Reconciliation:** Payments matched to invoices; unapplied payments tracked as patient deposits

#### 4.5.4 Accounts Payable Workflow

**From Inventory Module:**

- Approved supplier invoices create AP entries
- **Supplier Aging:** Tracks payment terms compliance
- **PO Reconciliation:** Three-way match (PO quantity vs. Receipt quantity vs. Invoice quantity)
- **Payment Scheduling:** Accountant schedules payment based on terms; Financial Manager approves if above threshold

#### 4.5.5 Bank & Cash Workflow

**Cash Book:**

- Daily cash register reconciliation by Receptionist
- Manager verifies end-of-day cash count vs. system total
- Cash deposit to bank recorded by Accountant

**Bank Reconciliation:**

- Monthly import of bank statement (CSV/manual entry)
- System auto-matches transactions; unmatched items flagged
- Accountant investigates discrepancies; Financial Manager reviews

**Petty Cash:**

- Fixed float amount (e.g., ETB 10,000)
- Expenses recorded with receipts; replenishment triggered when low
- Manager approves replenishment; Accountant processes

---

### 4.6 EXPENSE MANAGEMENT WORKFLOW

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Accountant  │────►│ Select      │────►│ Attach      │
│         │     │ or Manager  │     │ expense     │     │ receipt/    │
│         │     │ records     │     │ category    │     │ invoice     │
│         │     │ expense     │     │             │     │             │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ System      │────►│ Expense     │────►│ Payment     │────►│ Expense     │
│ checks      │     │ status:     │     │ processed   │     │ posted to   │
│ approval    │     │ 'approved'  │     │ (cash/bank) │     │ GL          │
│ threshold   │     │ or 'pending'│     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

**Approval Thresholds (Configurable in Clinic Settings):**

| Expense Amount       | Approval Required                 |
| -------------------- | --------------------------------- |
| ≤ ETB 5,000         | Accountant records; auto-approved |
| ETB 5,000 – 10,000  | Manager approval                  |
| ETB 10,000 – 50,000 | Financial Manager approval        |
| > ETB 50,000         | Board President approval          |

**Recurring Expenses:**

- Monthly rent, utilities, salaries set up as templates
- Auto-generate on schedule; await approval before posting

---

### 4.7 HUMAN RESOURCE WORKFLOW

#### 4.7.1 Employee Records Management

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ HR creates  │────►│ System      │────►│ Employee    │
│         │     │ new employee│     │ validates   │     │ record      │
│         │     │ record      │     │ uniqueness  │     │ active      │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                 │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Upload      │────►│ Manager     │────►│ Payroll     │
│ contract &  │     │ reviews &   │     │ Profile     │
│ documents   │     │ confirms    │     │ auto-created│
└─────────────┘     └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **HR** initiates "New Employee" from Employee Directory
2. Enters personal details, emergency contacts, qualifications, job title, department, start date
3. Uploads contract document, ID copy, credentials
4. System validates employee ID uniqueness; creates master record
5. **Manager** reviews and confirms employment details
6. System auto-generates **Payroll Profile** with default salary structure (base salary, allowances, tax code, pension tier)
7. Employee becomes eligible for attendance tracking, leave, and payroll

**Data Integrity Rules:**

- Employee cannot be hard-deleted if any payroll run, attendance record, or expense claim exists
- Employment status controls system access (active, on-leave, suspended, terminated)
- Termination triggers final payroll computation and leave encashment workflow

#### 4.7.2 Attendance & Time Management

**Operational Steps:**

1. **HR** opens "Daily Attendance"; records check-in/check-out per employee
2. System calculates hours worked, overtime eligibility, late arrivals
3. **Shift Scheduling:** HR defines weekly/monthly rosters; employees assigned to shifts
4. **Timesheet Review:** HR reviews and locks weekly timesheets before payroll input
5. Attendance data feeds directly into **Payroll Inputs** as variable earnings (overtime hours)

#### 4.7.3 Leave Management

**Operational Steps:**

1. Employee (or HR on behalf) submits leave request with type (annual, sick, maternity, unpaid)
2. System checks leave balance availability
3. **Manager** approves or rejects request
4. If approved: leave balance deducted; attendance marked accordingly
5. Unpaid leave automatically feeds into **Payroll Inputs** as deduction for the period

---

### 4.8 PAYROLL WORKFLOW

#### 4.8.1 Payroll Module Architecture

**Payroll Configuration Layer**

- **Settings:** Global rules including standard working days per month (default 26 days or 208 hours), minimum wage constraints, and statutory overtime multipliers (1.5x up to 2.5x).
- **Payment Types:** Dictionaries for Earnings (Basic Salary, Overtime, Bonus), Allowances (Transport, Housing, Telecommunication - complete with exemption limits), and Employer Contributions (Pension 11%, SHI).

**Payroll Profiles**

- Master compensation record per employee, linked one-to-one with Employee Records.
- **Fields:** Complete compensation structure including base salary, fixed allowances, exact tax codes, pension tier eligibility (7% Employee / 11% Employer), and bank details per payment method.
- **Access Control:** HR initiates profile setups; changes to base parameters require Financial Manager authorization to enforce separation of duties.

**Payroll Periods**

- **Definition:** Exact fiscal period constraints governed by Ethiopian taxation periods.
- **State Machine:** `draft` → `calculated` → `approved` → `posted` → `paid` (with options for `voided`). Only one period can be actively processing.
- **Integrity Rule:** Idempotent calculation engine ensures strict ledger lock prior to processing.

**Payroll Inputs & Time Management**

- **Variable Earnings:** Overtime tracking based on timesheets, shift premiums (night/weekend), bonus allocations, and field transport per diem inputs.
- **Variable Deductions:** One-off penalties, loans, or advances configured for automatic scheduled deduction.
- **Access:** HR enters time/variable data; input window locks the moment the period transitions past `draft`.

#### 4.8.2 The Computation Engine & Statutory Rules Engine

The system features an automated, deterministic calculation pipeline conforming to Ethiopian Labor & Tax Law driven by the **Ethiopian Statutory Rules Engine**.

##### 4.8.2.1 Statutory Rules Engine (Strategy Pattern)
The statutory deduction logic implements a decoupled **Strategy Pattern** integrating securely into the computation pipeline:

###### Income Tax Rule (PAYE)
Evaluates `Taxable Income` against the progressive 2025 Ethiopian Income Tax Brackets and applies exact deduction thresholds per tier.

**Ethiopian Progressive Tax Brackets (2025 Amendment, effective July 1, 2025):**

| Bracket | Min (ETB) | Max (ETB) | Rate | Deduction |
|---------|-----------|-----------|------|-----------|
| 1       | 0         | 2,000     | 0%   | 0         |
| 2       | 2,001     | 4,000     | 15%  | 300       |
| 3       | 4,001     | 7,000     | 20%  | 500       |
| 4       | 7,001     | 10,000    | 25%  | 850       |
| 5       | 10,001    | 14,000    | 30%  | 1,350     |
| 6       | 14,001    | ∞         | 35%  | 2,050     |

**Formula:** `Tax = (Taxable Income × Rate%) − Deduction`

**Bonus Tax Spreading:** Includes bespoke computational logic spreading anomalous bonuses algorithmically across performance months to prevent anomalous tax tier jumps.

###### Pension & SHI Rules
* **Pension Rule:** Executes an assessment of `Pensionable Earnings`. Enforces statutorily fixed definitions of 7% for Employee deduction and 11% for Employer expense mapped to respective Pension Payables.
* **SHI Rule (Social Health Insurance):** Governed by an administrative configuration toggle; yields a flat 1.5% Employee and 1.5% Employer assessment against `Base Salary`.

##### 4.8.2.2 Calculation Pipeline Steps

**Step 1: Gross Pay Calculation**
`Gross Pay = (Prorated Base Salary) + Fixed Allowances + Variable Earnings (Overtime) + Bonuses`

**Step 2: Taxable Income Derivation**
`Taxable Income = Gross Pay - Exemption Engine limits (e.g. transport limits)`

**Step 3: Statutory Tax Computation (PAYE)**
`Tax = (Taxable Income × Applicable Bracket Rate%) − Bracket Deduction`

**Step 4: Statutory Deductions**
`Employee Pension (7%)` AND `SHI (1.5% if active)`

**Step 5: Net Pay Calculation**
`Net Pay = Gross Pay - PAYE Tax - Employee Pension - Employee SHI - Post-tax Deductions - Repayments`

#### 4.8.3 Payroll Process Lifecycle

```
HR/Accountant Input
    ├── Verify/Update Employee Payroll Profiles
    ├── Initialize Payroll Period (`draft`)
    └── Variable inputs entered (timesheets, deductions, advances)

         ▼

System Computes Payroll Sheet (`calculatePayroll`)
    ├── Engine executes (Gross → Exemption Logic → Taxable → PAYE → Pensions → Net)
    ├── Allowance Exemption Engine determines non-tax portions automatically
    └── System halts on zero/negative Net Pay

         ▼

Financial Manager Review (Status → 'calculated')
    ├── Analyzes the unified Payroll Grid
    ├── Validates variances and cash availability constraints
    └── Approves payroll execution based on Configuration Settings

         ▼

Post Payment (upon approval) (Status → 'approved' → 'posted' → 'paid')
    ├── Period locks against data mutation
    ├── Multi-bank or cash disbursement initiated (compliant with limits)
    └── Financial execution triggers actual auto-GL posting
```

#### 4.8.4 Comprehensive Accounting Integration

Upon posting, the Payroll engine generates canonical `JournalEntry` records, mapping payroll liabilities securely to the structured Chart of Accounts.

**GL Journal Formulation Matrix:**

| Line Type             | Expected Canonical Code            | Debit/Credit Action                 | Auto-Generated |
| --------------------- | ---------------------------------- | ----------------------------------- | -------------- |
| **Salary Expense**    | `62100` Basic Salaries & Wages       | **Debit** (Total Basic Pay)         | Yes            |
| **Allowance Exp.**    | `62600`/`62700` Housing/Transport    | **Debit** (Total Fixed Allowances)  | Yes            |
| **Pension Exp.**      | `62400` Employer Pension Expense     | **Debit** (Employer 11% Portion)    | Yes            |
| **Overtime Exp.**     | `62200` Overtime Pay Expense         | **Debit** (Total Overtime Earned)   | Yes            |
| **Tax Liability**     | `21230` Income Tax Payable (PAYE)    | **Credit** (Total Deducted PAYE)    | Yes            |
| **Pension Liab.**     | `21320`/`21330` Pension Payables     | **Credit** (Employee 7% + Emp 11%)  | Yes            |
| **Asset Recovery**    | `21360` Other Payroll Deductions     | **Credit** (Repayment Deductions)   | Yes            |
| **Net Pay Accrual**   | `21310` Net Salaries Payable         | **Credit** (Total Net Pay Escrowed) | Yes            |

*Next Step: A secondary Payment Journal handles the `Debit Net Salaries Payable` to `Credit Bank / Cash on Hand`.*

#### 4.8.5 Advance & Loan Lifecycle Workflow

| HR Event               | Accounting Impact                                                  | Workflow Action                               |
| ---------------------- | ------------------------------------------------------------------ | --------------------------------------------- |
| Salary Advance Issued  | Debit Employee Receivable / Credit Cash                            | Financial Manager approves & issues advance   |
| Disbursed Repayment    | Debit Cash / Credit Employee Receivable                            | Auto-deducted directly via computation engine |
| End-of-Service Benefit | Debit Provision for Gratuity / Credit Bank                         | Manual (requires Financial Manager approval)  |
| Payroll Reversals      | Contra-entries corresponding exact prior ledger posting records.   | Secured `Void` functionality by FinManager    |

---

### 4.9 FIXED ASSET WORKFLOW

#### 4.9.1 Fixed Asset Module Architecture

The Fixed Asset module is a full-lifecycle, Ethiopian-compliant asset management system. It securely handles asset acquisition, capitalization, statutory depreciation (individual and pooled), and disposal with comprehensive GL journal posting mapped strictly to the `12000` Fixed Asset ledger tier.

**Asset Categories & Pools**
- **Categories:** Auto-seeded Ethiopian statutory categories mapping directly to the canonical 5-digit Chart of Accounts (`12200` Buildings, `12300` Machinery, `12400` Computers). Defines statutory rates (Straight-Line & Diminishing Value).
- **Pools:** Grouping mechanism enabling Ethiopian Diminishing-Value (DV) pooling, where assets of the same category are grouped and depreciated collectively on a reducing-balance basis.

**Fixed Assets**
- **Master Record:** Contains purchase details, exact VAT / landed costs, specific COA mapping, and business-use percentage.
- **Audit Logging:** Every financial modification records an immutable `FixedAssetLifecycleEvent`.

#### 4.9.2 Ethiopian Statutory Asset Categories

Per **Income Tax Proclamation No. 979/2016 (Article 25)** and **Council of Ministers Regulation No. 410/2017**, depreciable assets in Ethiopia are classified into six mandatory categories. The respective canonical GL account links are enforced automatically.

| Ethiopian Type | Category Name | SL Rate | DV Rate | Method Locked | Poolable | Target GL Mapping |
|----------------|---------------|---------|---------|---------------|----------|-------------------|
| `buildings` | Buildings & Structural Improvements | 5% | — | SL only ✅ | ❌ | `12200` (Cost) / `12210` (Accum. Deprec) |
| `intangibles` | Intangible Assets | 10% | — | SL only ✅ | ❌ | `12xxx` General Fixed Assets |
| `greenhouses` | Greenhouses | 10% | — | SL only ✅ | ❌ | `12xxx` General Fixed Assets |
| `computers_software` | Computers, Software & Data Storage | 20% | 25% | ❌ | ✅ | `12400` (Cost) / `12410` (Accum. Deprec) |
| `mining_petroleum` | Mining & Petroleum Assets | 25% | 30% | ❌ | ✅ | `12xxx` General Fixed Assets |
| `all_other` | All Other Depreciable Assets (e.g., Medical Machinery) | 15% | 20% | ❌ | ✅ | `12300` (Cost) / `12310` (Accum. Deprec) |

**Key Statutory Rules:**
- Buildings, Intangibles, and Greenhouses must be depreciated individually via **Straight-Line**.
- Computers and Machinery can leverage pooled **Diminishing Value** tracking.

#### 4.9.3 Depreciation Engine & GL Posting

Asset depreciation calculates utilizing either the global **Straight-Line (SL)** formula or **Diminishing-Value (DV)** pooling logic. Depreciation is locked and securely posted at month-end.

**GL Journal Auto-Posting Template:**
| Account Type | Description | Action |
|--------------|-------------|--------|
| `61600` Depreciation Expense | Month's designated depreciation cost | **Debit** |
| `12x10` Accumulated Depreciation | Contra-asset corresponding to the targeted asset pool/category | **Credit** |

#### 4.9.4 Asset Lifecycle Workflows

1. **Acquisition:** Accountant records purchase details, logging invoice date and capitalizing VAT (Debit `12xxx` Fixed Asset, Credit `11100`/`21100` Bank/AP).
2. **Monthly Depreciation:** Financial Manager executes an automatic period-end process ensuring exact statutory percentages are exhausted against active `12xxx` book values per month.
3. **Disposal:** Whether sold, scrapped, or lost, system unlinks the exact `Asset Book Value` logic, reversing the accumulated contra-asset (Debit `12x10`) and original asset ledger (Credit `12xxx`), immediately recognizing Gain/Loss from Asset Disposal.

---

### 4.10 EQUITY MODULE WORKFLOW

### 4.10.1 System Architecture Overview

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

### 4.10.2 Company Type Gating & Entity Classification

### Entity Types (2021 Ethiopian Commercial Code)

| Company Type | Equity Module | Share-Based | Statutory Basis |
|---|---|---|---|
| `Sole Proprietorship` | Owner's Capital (31000/32000) | ❌ No | N/A |
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
| Capital Contribution | DR Bank, CR Owner's Capital (31000) | PostingGuard enforced |
| Drawing/Withdrawal | DR Owner's Drawings (32000), CR Bank | PostingGuard enforced |
| Year-End Close | DR Owner's Capital (31000), CR Owner's Drawings (32000) | Closes drawings to capital |

---

### 4.10.3 Ethiopian Statutory Compliance Framework

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
- **At year-end close:** `PeriodCloseService::applyLegalReserveAppropriation()` automatically calculates and posts the reserve transfer journal entry: `DR Retained Earnings (33000), CR Legal Reserve (34600)`
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
- Declaration: `DR Retained Earnings (33000), CR Dividends Payable (21500) [net], CR WHT Payable (22400) [tax]`
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
| 3 | **Legal Reserve** obligation fulfilled (account 34600 ≥ 10% of account 34000) | **Art. 452**, Commercial Code 2021 | Hard block |
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

### 4.10.4 Chart of Accounts — Equity Ledger Map

### PLC/SC Equity Accounts (auto-created by `ChartOfAccountsService::createDefaultAccounts()`)

| Code | Account Name | Normal Balance | Detail Type | System | Condition |
|---|---|---|---|---|---|
| `34000` | Common Stock | Credit | Common Stock | No | PLC/SC/SM-PLC |
| `34100` | Preferred Stock | Credit | Preferred Stock | No | PLC/SC/SM-PLC |
| `34200` | Share Premium – Common | Credit | Paid-in Capital or Surplus | No | PLC/SC/SM-PLC |
| `34210` | Share Premium – Preferred | Credit | Paid-in Capital or Surplus | No | PLC/SC/SM-PLC |
| `34300` | Additional Paid-in Capital (APIC) | Credit | Paid-in Capital or Surplus | No | PLC/SC/SM-PLC |
| `34500` | Treasury Stock | **Debit** (Contra-Equity) | Treasury Stock | No | PLC/SC/SM-PLC |
| `34800` | Accumulated Other Comprehensive Income (AOCI) | Credit | AOCI | No | PLC/SC/SM-PLC |
| `34700` | Dividend Distribution | Credit | Dividends Paid / Owner Draw | No | PLC/SC/SM-PLC |
| `34600` | Legal Reserve | Credit | Retained Earnings | No | PLC/SC/SM-PLC |
| `33000` | Retained Earnings | Credit | Retained Earnings | ✅ Yes | All types |
| `39000` | Income Summary | Credit | Income Summary | ✅ Yes | All types |
| `39900` | Opening Balance Equity | Credit | Opening Balance Equity | ✅ Yes | All types |
| `21500` | Dividends Payable | Credit | Dividends Payable | ✅ Yes | PLC/SC/SM-PLC |
| `22400` | Dividend Withholding Tax Payable | Credit | Dividend WHT Payable | ✅ Yes | PLC/SC/SM-PLC |

### Sole Proprietorship Equity Accounts

| Code | Account Name | Normal Balance | Condition |
|---|---|---|---|
| `31000` | Owner's Equity/Capital | Credit | SP only |
| `32000` | Owner's Drawings | **Debit** (Contra-Equity) | SP only |

### Configurable Account Resolution

**Source:** `EquityConfigurationService::account()` — three-tier fallback:

```
1. SettingCompany field (e.g., equity_share_capital_account_id)
2. Account::where('account_code', defaultCode)->where('company_id', companyId)->first()
3. Exception if not found
```

| Role Key | Settings Field | Default Code |
|---|---|---|
| `share_capital` | `equity_share_capital_account_id` | `34000` |
| `legal_reserve` | `equity_legal_reserve_account_id` | `34600` |
| `retained_earnings` | `equity_retained_earnings_account_id` | `33000` |
| `treasury` | `equity_treasury_account_id` | `34500` |
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

### 4.10.5 Data Model Topology

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

### 4.10.6 Service Layer Architecture

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

### 4.10.7 Share Class Configuration

**Navigation:** Equity → Share Classes

### Data Requirements

| Field | Validation | Statutory Basis |
|---|---|---|
| `class_name` | Required (e.g., "Ordinary Shares") | — |
| `class_type` | `common` or `preferred` | — |
| `par_value` | ≥ ETB 100 (configurable `minimum_par_value`) | **Art. 452, Commercial Code 2021** |
| `authorized_shares` | > 0 (maximum issuable ceiling) | — |
| `share_capital_account_id` | FK → Account (e.g., 34000) | Required for journal entries |
| `share_premium_account_id` | FK → Account (e.g., 34200) | Required for premium booking |
| `treasury_shares_account_id` | FK → Account (e.g., 34500) | Required for buyback/reissue |

### Integrity Check

`ShareClass::verifyConsistency()` — compares `issued_shares` counter against `ShareLedger SUM(credit_shares - debit_shares)`. Called before every issuance and buyback.

---

### 4.10.8 Shareholder Registration

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

### 4.10.9 Share Issuance (Primary Market)

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
   ├── CR  Share Capital (34000) ...... par_value × shares
   └── CR  Share Premium (34200) ...... premium (price − par) × shares [if any]
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
  ├── DR  Retained Earnings (33000) ... par_value × shares
  └── CR  Share Capital (34000) ....... par_value × shares
```

---

### 4.10.10 Share Transfer (Secondary Market)

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

### 4.10.11 Treasury Stock (Buyback & Reissue)

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
     ├── DR  Treasury Stock (34500) ... total_cost
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
     ├── CR  Treasury Stock (34500) .. cost_basis
     ├── CR  Share Premium (34200) ... gain (if gain > 0)
     └── DR  Retained Earnings (33000) or Share Premium .. |loss| (if loss < 0)
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

### 4.10.12 Dividend Lifecycle

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
  ├── DR  Retained Earnings (33000) ......... total_amount (gross)
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

### 4.10.13 Period Close & Legal Reserve Appropriation

**Source:** `PeriodCloseService`

### Period Close Pipeline

```
1. validatePeriodCloseable()
   ├── Not already closed/locked
   ├── No unposted/pending-approval journal entries
   ├── All posted JEs are balanced
   ├── Previous period must be closed (sequential enforcement)
   ├── Fiscal PolicyVersion must exist
   └── Accounts 33000 (RE) and 39000 (IS) must exist

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
      ├── DR  Retained Earnings (33000) ... transfer
      └── CR  Legal Reserve (34600) ....... transfer
```

### Post-Close Compliance

```
runEquityComplianceChecks():
  └── For share-based companies: EquityComplianceService.runAll(company)
      → Re-evaluates all 7 compliance alerts after the close
```

---

### 4.10.14 Equity Compliance Dashboard Alerts

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
| **Trigger** | Legal Reserve (34600) balance < 10% of Share Capital (34000) |
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

### 4.10.15 Statement of Changes in Equity

**Source:** `ShareholdersEquityService::generateStatement()`

### Report Matrix Structure

**Columns (equity_component):**

| Column | Account Source |
|---|---|
| Common Stock | `34000` |
| Preferred Stock | `34100` |
| Share Premium | `34200` / `34210` |
| Retained Earnings | `33000` |
| Treasury Stock | `34500` |
| AOCI | `34800` |
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

### 4.10.16 Retained Earnings Statement

**Source:** `RetainedEarningsService`

### PLC Flow

```
Opening Balance  = Account 33000 balance as of day before start_date
+ Net Income     = EquityMovement(RE, net_income|net_loss)
− Dividends      = EquityMovement(RE, dividends|dividends_declared|dividends_paid)
± Adjustments    = EquityMovement(RE, prior_period_adjustment)
± Other          = EquityMovement(RE, other)
= Ending Balance
```

### Sole Proprietorship Flow (Owner's Equity Statement)

```
Opening Balance  = Accounts 31000 + 32000 + 33000 day before start
+ Net Income     = FinancialReportingService.calculateNetIncome()
+ Contributions  = Account 31000 credits in period
− Drawings       = Account 32000 debits in period
= Ending Balance
```

---

### 4.10.17 Filament UI Surface Map

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

### 4.10.18 Security & Permission Model

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


## 5. Financial Transaction Lifecycle Architecture

### 5.1 Revenue Recognition Flow

```
Patient Service Requested
         │
         ▼
┌─────────────────┐
│ Invoice Created │
│ (status: draft) │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌───────┐ ┌─────────┐
│ Paid  │ │ Credit  │
│ Cash  │ │ (AR)    │
└───┬───┘ └────┬────┘
    │          │
    ▼          ▼
┌─────────────────┐     ┌─────────────────┐
│ Payment Recorded │     │ AR Record Created│
│ (Cash/Bank ↑)   │     │ (AR ↑)           │
└────────┬────────┘     └────────┬────────┘
         │                        │
         └────────┬───────────────┘
                  ▼
         ┌─────────────────┐
         │ Revenue Recognized│
         │ (Revenue ↑)      │
         │                  │
         │ If pharmacy:     │
         │ COGS recognized  │
         │ (COGS ↑, Inv ↓) │
         └─────────────────┘
                  │
                  ▼
         ┌─────────────────┐
         │ GL Entry Posted │
         │ (auto-sync)      │
         └─────────────────┘
```

### 5.2 Inventory Cost Flow (FIFO)

```
Supplier Delivery
         │
         ▼
┌─────────────────┐
│ Goods Receipt    │
│ (Inventory ↑ @   │
│  actual cost)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Batch Recorded   │
│ (batch_id, qty,  │
│  unit_cost, exp) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Dispensing Event │
│ (FIFO selection) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ COGS Calculated  │
│ = qty × batch    │
│   unit_cost      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ GL Entry:        │
│ Debit COGS       │
│ Credit Inventory │
└─────────────────┘
```

### 5.3 Payroll Expense Flow

```
Payroll Period Opened
         │
         ▼
┌─────────────────┐
│ HR/Accountant   │
│ enters variable │
│ inputs          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ System computes │
│ Payroll Sheet   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Financial       │
│ Manager reviews │
│ & approves      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Payroll Posted  │
│ (period closed) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Auto GL Entry:   │
│ Debit Salaries   │
│ Expense          │
│ Credit Tax       │
│ Payable          │
│ Credit Pension   │
│ Payable          │
│ Credit Bank      │
│ (Net Pay)        │
└─────────────────┘
```

### 5.4 Equity Transaction Flow

```
Shareholder Approved Matrix
         │
         ▼
┌─────────────────┐
│ Share Issuance  │
│ Created         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Financial       │
│ Manager approves│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Journal Posted: │
│ Debit Cash      │
│ Credit Share    │
│ Capital/Premium │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Share Ledger    │
│ Updates (Cap    │
│ Table +1)       │
└─────────────────┘
```

### 5.5 Dividend Payment Flow

```
Board of Directors
         │
         ▼
┌─────────────────┐
│ Declaration     │
│ (Subject to     │
│ 8-Point Gate)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Journal Posted: │
│ Debit Retained  │
│ Earnings        │
│ Credit Div Pay  │
│ Credit WHT Pay  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Batch Share-    │
│ holder Payouts  │
│ Generated       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Bank Payment    │
│ clears payable  │
└─────────────────┘
```

---

## 6. Reporting & Executive Oversight Workflows

### 6.1 Dashboard Hierarchy

#### 6.1.1 Board President — Executive Overview

**Focus:** Governance, strategic financial health, policy compliance

**Widgets:**

- KPI Overview (revenue, margin, cash runway)
- Monthly and quarterly trend analysis
- Audit trail exceptions (unusual journal entries, overrides)
- Pending Board-level approvals (>ETB 50,000 expenses, fiscal year changes, write-offs)

#### 6.1.2 Financial Manager — Financial Management

**Focus:** Tactical financial control, cash management, payroll oversight, month-end integrity

**Widgets:**

- Live Cash Position (all accounts consolidated)
- Pending Approvals Queue (journal entries, payroll, POs, expenses)
- Monthly Close Status (which periods are open/closed)
- Payroll Next Run (days remaining, draft status)
- AR/AP Exception Report (overdue > 60 days)
- Budget Burn Rate (actual YTD vs historical trend)
- Equity Compliance Alerts (Statutory limit checks, EGM needs, WHT tracking)

**Quick Actions:**

- Approve Payroll Run
- Review Bank Reconciliation
- Open/Close Fiscal Period
- Generate Management Pack (P&L + BS + CF + KPIs)

#### 6.1.3 Manager — Operations Dashboard

**Focus:** Clinic operations, staff oversight, inventory, patient flow

**Widgets:**

- Today's Revenue (real-time POS)
- Pending Operational Approvals (stock adjustments, small POs, expenses ≤ETB 10,000)
- Staff Attendance Overview (from HR)
- Pharmacy Queue + Inventory Alerts
- Cash Register Status

#### 6.1.4 Accountant — Accounting Dashboard

**Focus:** Transaction processing, reconciliation, bookkeeping accuracy

**Widgets:**

- Unreconciled Items (bank, cash, AP, AR)
- Pending Journal Entries (drafts to submit)
- Payroll Input Status (awaiting variable data entry)
- VAT/Tax Summary (if applicable)
- Period Close Checklist

#### 6.1.5 HR — HR Operations Dashboard

**Focus:** Workforce management, attendance compliance, payroll preparation

**Widgets:**

- Today's Attendance Summary (present/absent/late)
- Pending Leave Requests (awaiting Manager approval)
- Payroll Input Deadline (countdown to cut-off)
- Employee Count by Status (active/on-leave/terminated)
- Contract Expiry Alerts (next 30/60/90 days)

#### 6.1.6 Receptionist — Cashier Dashboard

**Focus:** Fast patient service, accurate cash handling

**Widgets:**

- Quick POS Launch
- Today's Transactions (count + value)
- Pending Overdue Collections
- Cash Register: Opening / Current / Expected Closing

#### 6.1.7 Pharmacist — Pharmacy Dashboard

**Focus:** Dispensing accuracy, inventory health

**Widgets:**

- Pending Prescriptions Queue
- Today's Dispensing Count
- Stock Alerts (low, expiry, out-of-stock)
- Quick Medicine Search

### 6.2 Report Generation Workflows

**Financial Reports:**

1. **Income Statement:** Revenue accounts minus Expense accounts equals Net Profit

   - Generated from GL by period; supports comparative (current vs. prior)
   - Drill-down from summary to transaction detail
2. **Balance Sheet:** Assets = Liabilities + Equity

   - Snapshot as of date; auto-calculated from GL balances
3. **Cash Flow:** Operating, Investing, Financing activities

   - Derived from cash/bank GL accounts with categorization
4. **Trial Balance:** All accounts with debit/credit balances

   - Verification tool; used before month-end close

**Pharmacy Reports:**

1. **Dispensing Summary:** By period, by medicine, by pharmacist
2. **Stock Valuation (FIFO):** Current inventory at latest cost
3. **Expiry Report:** Items grouped by expiry horizon
4. **Fast/Slow Moving:** Turnover ratio by medicine category

**Sales & Revenue:**

1. **Daily Sales Summary:** POS transactions aggregated
2. **Revenue by Category:** Clinical, Lab, Pharmacy, Registration, Other
3. **Payment Method Analysis:** Cash vs. Card vs. Mobile vs. Credit
4. **Outstanding Debtors:** AR aging with collection status

**Executive Dashboards:**

1. **KPI Overview:** Customizable metrics with targets
2. **Revenue Trends:** Multi-period comparison
3. **Profitability Analysis:** By service line, by department
4. **Comparative Periods:** Year-over-year, quarter-over-quarter

**HR Reports:**

1. **Attendance Summary:** By employee, by department, by period
2. **Leave Utilization:** Annual leave balance vs. days taken
3. **Payroll Summary:** Gross, deductions, net by department

---

## 7. Approval & Verification Architecture

### 7.1 Approval Matrix

| Transaction Type    | Threshold             | Approver                            | Notification         |
| ------------------- | --------------------- | ----------------------------------- | -------------------- |
| Price Override      | Any amount            | Manager                             | Auto + Audit log     |
| Credit Invoice      | Any amount            | Manager                             | Auto + Daily summary |
| Expense Record      | ≤ ETB 5,000          | Auto-approved                       | None                 |
| Expense Record      | ETB 5,000 – 10,000   | Manager                             | In-app + Email       |
| Expense Record      | ETB 10,000 – 50,000  | Financial Manager                   | In-app + Email       |
| Expense Record      | > ETB 50,000          | Board President                     | In-app + Email       |
| Stock Adjustment    | ≤ ETB 5,000          | Auto-approved                       | Daily summary        |
| Stock Adjustment    | > ETB 5,000           | Manager                             | In-app               |
| Purchase Order      | ≤ ETB 25,000         | Manager                             | In-app               |
| Purchase Order      | ETB 25,000 – 100,000 | Financial Manager                   | In-app + Email       |
| Purchase Order      | > ETB 100,000         | Board President                     | In-app + Email       |
| Journal Entry       | ≤ ETB 5,000          | Auto-posted                         | None                 |
| Journal Entry       | ETB 5,000 – 20,000   | Manager                             | In-app               |
| Journal Entry       | ETB 20,000 – 100,000 | Financial Manager                   | In-app + Email       |
| Journal Entry       | > ETB 100,000         | Board President                     | In-app + Email       |
| Payroll Run         | Any amount            | Financial Manager                   | In-app + Email       |
| Patient Refund      | Any amount            | Manager                             | Auto + Audit log     |
| Invoice Write-off   | Any amount            | Board President                     | In-app + Email       |
| Share Issuance      | Any amount            | Board President                     | In-app + Email       |
| Dividend Declaration| Any amount            | Board President                     | In-app + Email       |
| Dividend Payment    | Any amount            | Financial Manager                   | In-app + Email       |
| Compliance Override | N/A                   | Board President                     | In-app + Email       |
| Fiscal Period Close | N/A                   | Financial Manager + Board President | In-app               |

### 7.2 Verification Procedures

**End-of-Day (Receptionist):**

1. Count physical cash + card slips + mobile money confirmations
2. System generates "Expected Cash" report from POS
3. Receptionist enters actual count; system calculates variance
4. If variance ≠ 0: explanation required; Manager notified
5. Cash deposit slip prepared; Accountant receives next day

**End-of-Week (Manager):**

1. Review all pending operational approvals
2. Verify stock adjustments and small POs
3. Check exception reports (price overrides, refunds)
4. Review staff attendance anomalies from HR

**End-of-Month (Accountant + Financial Manager):**

1. **Days 1–2 (Accountant):**

   - Bank reconciliation completion
   - AP and AR aging review
   - Inventory physical count vs. system (cycle count verification)
   - Journal entry review and submission
   - Payroll variable input completion (HR/Accountant)
2. **Days 3–4 (Financial Manager):**

   - Reviews trial balance for integrity
   - Approves payroll run
   - Reviews variance analysis and management reports
   - Verifies cash position and liquidity
   - Approves period close request
3. **Day 5 (Board President):**

   - Receives executive summary P&L, Balance Sheet, Cash Flow
   - Reviews strategic KPI dashboard
   - Approves any Board-level items flagged by Financial Manager

---

## 8. Audit & Compliance Architecture

### 8.1 Audit Trail Coverage

**Spatie Laravel-Activitylog captures:**

- Who created/modified/deleted what record, when, from which IP
- Before/after values for financial records
- Login/logout events, failed login attempts

**Financial Audit Trail:**

- Every invoice change logged (creation, payment, modification, void)
- Every journal entry logged (creation, approval, posting, reversal)
- Every inventory movement logged (receipt, dispensing, adjustment)
- Every price change logged (old price, new price, reason, approver)
- Every payroll change logged (profile edits, input changes, run approvals)

**Inventory Audit Trail:**

- Batch-level tracking: who received, who dispensed, remaining quantity
- Expiry tracking: when flagged, action taken, disposal verification
- Stock adjustment: reason code mandatory, before/after quantities

**HR Audit Trail:**

- Employee record changes: who edited, what changed, when
- Attendance corrections: original vs. corrected timestamp, approver
- Leave approval workflow: requester, approver, dates, balance impact
- Payroll access: who viewed, who edited inputs, who approved run

**Equity Audit Trail:**

- Every share transaction logged (issuance, buyback, reissue, transfer)
- Every dividend declaration and payment logged
- Every compliance override recorded with exact bypassed rules and justification
- EGM (Extraordinary General Meeting) outcomes securely logged

### 8.2 Data Retention & Backup

**Backup Schedule (Spatie Laravel-Backup):**

- **Daily:** Database dump to S3-compatible storage (retain 14 days)
- **Weekly:** Full backup including uploads (retain 8 weeks)
- **Monthly:** Archive backup (retain 12 months)
- **On-demand:** Pre-update backup triggered by Manager

**Data Export:**

- Accountant can export GL detail, invoices, inventory to CSV/Excel
- Financial Manager can export management reports to PDF
- HR can export attendance and payroll data (with access logging)
- Board President can export executive reports to PDF
- All exports logged with user, timestamp, data range

---

## 9. System Navigation & UX Workflow

### 9.1 Role-Based Landing Pages

**After Login:**

```
IF role = Receptionist → Cashier Dashboard (quick POS access)
IF role = Pharmacist   → Pharmacy Dashboard (pending prescriptions)
IF role = HR           → HR Operations (attendance + pending leave + payroll input status)
IF role = Accountant   → Accounting Dashboard (unreconciled items + payroll input)
IF role = Manager      → Manager Operations (pending approvals + alerts)
IF role = Financial Manager → Financial Management (cash position + approvals + payroll)
IF role = Board President → Executive Overview (KPIs + trends)
```

### 9.2 Menu Access Control

**Dynamic Menu Rendering:**

- Menu items filtered by `Spatie Permission` gates
- Sub-menu items hidden if no access to any child item
- "Quick Actions" buttons on dashboards bypass menu navigation for common tasks

**Cross-Module Navigation:**

- From Patient Directory: "Create Invoice" → jumps to POS with patient pre-selected
- From Pending Prescriptions: "View Invoice" → read-only invoice details
- From Invoice: "View Dispensing" → jumps to pharmacy record
- From Stock Alert: "Create PO" → jumps to Purchase Orders with item pre-filled
- From AP Aging: "Pay Supplier" → jumps to Payment Scheduling
- From Employee Directory: "View Payroll Profile" → jumps to HR Payroll Profiles
- From Attendance: "Add Payroll Input" → jumps to Payroll Inputs with employee pre-filled

### 9.3 Notification Architecture (Filament Database Notifications)

**Real-time Notifications:**

- Pharmacist: New prescription from POS
- Manager: Pending approval request (expense, PO, leave, stock adjustment)
- HR: Leave request submitted for Manager approval
- Accountant: End-of-day cash reconciliation submitted; payroll input deadline approaching
- Financial Manager: Payroll run awaiting approval; mid-tier expense awaiting approval; period close checklist
- Board President: High-value PO awaiting approval; monthly reports ready; fiscal period close request

**Notification Actions:**

- Click notification → navigate directly to relevant record
- Approve/reject from notification panel (for simple approvals)
- Mark as read; unread count on bell icon

---

## 10. Simplified Enterprise Workflow Structure

### 10.1 Daily Operational Rhythm

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         DAILY WORKFLOW (Clinic Hours)                    │
├─────────────────────────────────────────────────────────────────────────┤
│  RECEPTIONIST    PHARMACIST      HR              ACCOUNTANT    MANAGER   │
│  ────────────    ──────────      ──              ──────────    ───────  │
│  08:00 Open      08:00 Check     08:00 Mark      08:00 Review  08:00    │
│        cash      expiry & low    attendance       prior day GL  Review   │
│        register  stock alerts    for all staff    sync          daily    │
│  08:30 Patient   08:30 Prepare   08:30 Process    08:30 AP/AR  08:30    │
│        registra- dispensing      leave requests   processing    Check    │
│        tions     queue           & timesheets                 overnight  │
│  ...   POS       ...   Dispen-   ...   Attendance ...   Normal  ...   Floor│
│        transac-        sing from  corrections,    accounting    rounds  │
│        tions           queue      payroll input               + approve │
│  17:00 EOD       17:00 EOD       17:00 Finalize  17:00 Payroll 17:00   │
│        reconcili-      stock      attendance,     input (if     Approve │
│        ation           check +    verify leave    period open)   pending │
│                        reorder    balances                      items   │
│        recommendations                                              18:00│
│                                                                     Review│
│                                                                     reports│
└─────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │ FINANCIAL       │
                    │ MANAGER         │
                    │ ─────────────── │
                    │ Daily: Review   │
                    │ cash position;  │
                    │ monitor excep-  │
                    │ tions; review   │
                    │ payroll sheet   │
                    │ (if due)        │
                    │                 │
                    │ Weekly: Review  │
                    │ management pack │
                    │ approve accumu- │
                    │ lated mid-tier  │
                    │ transactions    │
                    │                 │
                    │ Monthly: Approve│
                    │ payroll; close  │
                    │ period; review  │
                    │ GL integrity    │
                    └─────────────────┘
```

### 10.2 Weekly Operational Rhythm

| Day                         | Receptionist            | Pharmacist                  | HR                                 | Accountant              | Manager                     | Financial Manager                                  |
| --------------------------- | ----------------------- | --------------------------- | ---------------------------------- | ----------------------- | --------------------------- | -------------------------------------------------- |
| **Monday**            | Normal ops              | Stock deep-check            | Attendance lock & payroll prep     | AP payment run          | Staff meeting, PO approvals | Review week-start cash position                    |
| **Tuesday–Thursday** | Normal ops              | Normal dispensing           | Leave processing, timesheet review | Normal accounting       | Floor supervision           | Exception monitoring                               |
| **Friday**            | Normal ops              | Expiry review, reorder prep | Weekly attendance summary          | Week-end reconciliation | Weekly report review        | Review accumulated approvals; management pack prep |
| **Saturday**          | (If open) Reduced hours | (If open) Reduced hours     | (If open) Reduced hours            | Backup verification     | (If open) Supervision       | (If open) Oversight                                |

### 10.3 Monthly/Quarterly Rhythm

**Month-End (Days 1–5):**

1. Accountant completes reconciliations, inputs, and preliminary reports
2. HR finalizes attendance and payroll variable inputs
3. Financial Manager reviews trial balance, approves payroll, closes period, generates management reports
4. Board President reviews executive pack prepared by Financial Manager

**Quarterly:**

1. Supplier performance review (Manager + Financial Manager)
2. Pricing review (service prices, medicine markups, salary structure adjustments)
3. Inventory turnover and expiry analysis
4. System backup archive verification

**Annually:**

1. Fiscal year close (configurable in Clinic Settings)
2. Full inventory physical count
3. Employee contract and payroll profile audit
4. Audit preparation (export all data, verify audit trails)
5. New fiscal year setup (periods, opening balances, defaults)

---

## 11. Data Model Relationships

```
patients
    ├── invoices (one-to-many)
    │       ├── invoice_items (one-to-many)
    │       └── payments (one-to-many)
    └── visits (one-to-many)

medicines
    ├── inventory_batches (one-to-many)
    │       └── stock_movements (one-to-many)
    └── dispensing_items (one-to-many)
            └── linked to invoice_items

suppliers
    └── purchase_orders (one-to-many)
            └── goods_receipts (one-to-many)
                    └── supplier_invoices (one-to-many)
                            └── accounts_payable (one-to-one)

chart_of_accounts
    └── journal_entries (one-to-many)
            └── journal_entry_lines (one-to-many)
                    └── linked to transactions (polymorphic)

employees
    ├── employment_contracts (one-to-many)
    ├── attendance_records (one-to-many)
    ├── leave_requests (one-to-many)
    └── payroll_profiles (one-to-one)
            └── payroll_runs (one-to-many)
                    ├── payroll_run_details (one-to-many)
                    │       └── linked to GL journal entries
                    └── statutory_reports
                            ├── payslips
                            ├── pension_schedules
                            └── tax_schedules

users (staff)
    ├── roles (Spatie)
    ├── permissions (Spatie)
    └── activity_logs (Spatie)

share_classes
    ├── share_transactions (one-to-many)
    │       └── share_ledger
    ├── dividends (one-to-many)
    │       └── dividend_payments
    └── treasury_stocks (one-to-many)

shareholders
    ├── share_ledger (one-to-many)
    └── dividend_payments (one-to-many)
```

---

## 12. Risk Mitigation & Business Rules

### 12.1 Financial Integrity Rules

- **No deletion of posted transactions** — only reversal entries with full audit trail
- **Immutable invoice after payment** — modifications require credit note + new invoice
- **Mandatory receipt reference** — every payment must have sequential receipt number
- **Dual control on cash** — Receptionist counts, Manager verifies, Accountant records deposit
- **Payroll immutability** — once posted, only reversible via approved contra-entry

### 12.2 Inventory Integrity Rules

- **Negative stock blocked** — system prevents dispensing below zero
- **FIFO enforced** — default batch selection; override requires documented reason
- **Expiry blocking** — expired items cannot be dispensed; must be adjusted out
- **Cost freeze** — once batch is received, cost is fixed; no retroactive changes

### 12.3 HR & Payroll Safeguards

- **Employee ID uniqueness** — national ID or system-generated unique identifier
- **Payroll period lock** — only one open period; prevents duplicate payments
- **Anomaly detection** — system flags payroll variance > 20% vs. prior month
- **Statutory compliance** — PAYE and pension calculations validated against configured tax tables

### 12.4 Operational Safeguards

- **Session timeout** — 30 minutes inactivity (configurable in Clinic Settings)
- **Concurrent edit detection** — warn if another user modifies same record
- **Daily backup reminder** — Manager dashboard shows backup status
- **Audit log immutability** — activity logs stored in separate table; no user can delete

### 12.5 Equity & Statutory Safeguards

- **Minimum Capital** — hard alert if equity drops below ETB 15K (PLC) / 50K (SC)
- **Legal Reserve Automation** — 5% deducted from net income automatically until 10% threshold reached
- **Dividend Validation Gate** — 8-point automated check blocks non-compliant declarations
- **Single-Member Verification** — automatically blocks secondary ownership for SM-PLC
- **Capital Adequacy Protection** — forces EGM logging if accumulated losses exceed 50% of share capital

---

---

This architecture provides CMC Clinic with an enterprise-grade operational framework covering **Patient Services, Revenue Management, Pharmacy & Inventory, Accounting, Human Resources, Equity Management, and Strategic Financial Oversight** — while maintaining the lightweight, practical design required for a small clinic environment. The seven-role authority matrix ensures clear segregation of duties, financial immutability, and operational efficiency without introducing bureaucratic friction.

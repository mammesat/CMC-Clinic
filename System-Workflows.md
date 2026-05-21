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
│   │   └── System Preferences
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
        │                 ┌───────┴───────┐
        │                 │  HUMAN RESOURCE │
        │                 │    (Payroll)    │
        │                 └───────────────┘
        │                         │
        └─────────────────────────┘
              (Payroll expense flows to GL; HR master data feeds Payroll)
```

### 3.2 Data Flow Principles

1. **Patient Registration** → Creates master data used by all service modules
2. **Billing & Cashier** → Generates invoices and payments; feeds revenue to GL
3. **Pharmacy** → Consumes inventory, generates dispensing records linked to invoices
4. **Human Resource** → Manages employee master data, attendance, leave; feeds payroll computation
5. **Accounting** → Consolidates all financial transactions; GL is the authoritative ledger
6. **Financial Manager** → Reviews and approves payroll, mid-tier expenses, journal entries, and fiscal period controls
7. **Reporting** → Aggregates from all modules in real-time via materialized views and controlled summaries

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

```
ASSETS (1xxx)
├── 11000 Cash on Hand (Cash Register)
├── 12000 Bank Account
├── 13000 Petty Cash
├── 14000 Accounts Receivable
├── 15000 Inventory (Medicines)
└── 16000 Prepaid Expenses

LIABILITIES (2xxx)
├── 21000 Accounts Payable
├── 22000 Accrued Expenses
├── 23000 Patient Deposits/Advances
├── 24000 Tax Payable (PAYE)
└── 25000 Pension Payable

EQUITY (3xxx)
├── 31000 Capital
└── 32000 Retained Earnings

REVENUE (4xxx)
├── 41000 Clinical Services Revenue
├── 42000 Laboratory Services Revenue
├── 43000 Pharmacy Sales Revenue
├── 44000 Registration Fees Revenue
└── 49000 Other Revenue

EXPENSES (5xxx)
├── 51000 Cost of Goods Sold (Pharmacy)
├── 52000 Salaries & Wages
├── 53000 Rent & Utilities
├── 54000 Medical Supplies
├── 55000 Administrative Expenses
└── 56000 Inventory Adjustments/Write-offs
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

**Payroll Profiles**

- Master compensation record per employee
- Fields: base salary, housing/transport/medical allowances, tax code (PAYE table), pension tier (employee percentage / employer percentage), bank details, payment method
- Linked to Employee Records module (one-to-one)
- **Access:** HR creates initial profile; Financial Manager reviews and approves salary structure changes

**Payroll Periods**

- Monthly cycle definition (e.g., Meskerem 1–30, Tikimt 1–30)
- Status workflow: `Open` → `Processing` → `Closed`
- Only one period open at a time; prevents duplicate runs
- Cut-off date configurable (e.g., inputs freeze on 25th of month)

**Payroll Inputs**

- **Variable Earnings:** Overtime hours (auto-rate from profile), night shift premiums, bonuses, commissions, back-pay
- **Variable Deductions:** Salary advances, loan repayments, unpaid leave days, excess pension contributions, statutory levies
- **Access:** HR enters variable data; Accountant can assist with complex calculations; input window closes when period status → `Processing`

**Payroll Sheets**

- Live computation grid showing all employees
- Columns: Gross Pay, Taxable Income, PAYE Tax, Employee Pension, Other Deductions, Net Pay
- Anomaly highlighting: variance > threshold vs prior month; negative net pay blocked
- **Action:** Export to Excel for external review; print preview

**Payroll Runs**

- **Compute Draft:** First pass calculation; creates provisional GL impact preview
- **Review & Approve:** Financial Manager reviews; can rollback to draft if errors found
- **Post Payroll:** Finalizes; auto-generates GL batch; period status → `Closed`
- **Statutory Reports:**
  - *Payslip Report:* Individual PDFs with YTD accumulators
  - *Pension Report:* Consolidated remittance schedule by pension fund administrator
  - *Tax Report:* PAYE schedule for tax authority remittance

**Payroll History**

- Immutable archive; supports prior period lookup and YTD reporting
- **Reversals:** Requires Financial Manager approval; creates contra-GL entries; allows re-processing in new period

#### 4.8.2 Payroll Process Lifecycle

```
HR/Accountant Input
    ├── Payroll Profiles (master data verified)
    ├── Payroll Period opened
    └── Variable inputs entered (overtime, deductions, leave)

         ▼

System computes Payroll Sheet
    ├── Auto-calculates gross, taxable, PAYE, pension, net
    └── Flags anomalies (vs previous month variance > 20%)

         ▼

Financial Manager Review
    ├── Reviews Payroll Sheet
    ├── Verifies statutory calculation accuracy
    ├── Checks cash availability for net payroll
    └── Approves or rejects with comments

         ▼

Post Payroll (upon approval)
    ├── Period locked
    ├── Auto-generates GL entries:
    │   ├── Debit: Salaries Expense (gross)
    │   ├── Debit: Pension Expense (employer portion)
    │   ├── Credit: Tax Payable (PAYE)
    │   ├── Credit: Pension Payable (employee + employer)
    │   ├── Credit: Salary Advance / Deductions Payable
    │   └── Credit: Bank / Cash (net pay)
    ├── Payslips released to employees
    └── Statutory reports generated for remittance
```

#### 4.8.3 HR Integration with Accounting

| HR Event               | Accounting Impact                                                  | Auto-Generated                               |
| ---------------------- | ------------------------------------------------------------------ | -------------------------------------------- |
| Monthly Payroll Posted | Debit Salaries Expense / Credit Bank, Tax Payable, Pension Payable | Yes                                          |
| Salary Advance Issued  | Debit Employee Receivable / Credit Cash                            | Yes (if tracked via Payroll Inputs)          |
| Leave Without Pay      | Reduced salary expense in payroll run                              | Yes (via deduction input)                    |
| End-of-Service Benefit | Debit Provision for Gratuity / Credit Bank                         | Manual (requires Financial Manager approval) |

---

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

---

---

This architecture provides CMC Clinic with an enterprise-grade operational framework covering **Patient Services, Revenue Management, Pharmacy & Inventory, Accounting, Human Resources, and Strategic Financial Oversight** — while maintaining the lightweight, practical design required for a small clinic environment. The seven-role authority matrix ensures clear segregation of duties, financial immutability, and operational efficiency without introducing bureaucratic friction.

## Clinic Accounting, Pharmacy & Human Resource Management System (CAPHRMS)

---

## 1. Executive Architecture Overview

### 1.1 System Philosophy

The architecture follows a **"Hub-and-Spoke" financial model** with the **Billing & Cashier module** as the central revenue hub. All revenue-generating activities flow through this hub, while the **Accounting module** serves as the ledger of record. The **Pharmacy module** operates as both a revenue center (dispensing, OTC sales) and a cost center (inventory), requiring tight integration with billing and accounting. The **Human Resource module** manages the clinic's workforce lifecycle, attendance, leave, and payroll computation, with payroll outputs flowing directly into the General Ledger as controlled salary expenses.

The **Financial Manager** serves as the supervisory control layer between operational execution and strategic governance, owning fiscal period management, payroll approval, mid-tier financial authorization, and month-end financial integrity verification. The **Board President** retains ultimate authority over strategic policy, high-value approvals, fiscal year configuration, and executive oversight.

### 1.2 Core Design Principles

| Principle                        | Implementation                                                                                                                                                    |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Single Source of Truth** | PostgreSQL with strict referential integrity; HR payroll data flows directly to Accounting GL without manual re-entry                                             |
| **Financial Immutability** | Posted transactions cannot be edited; payroll reversals require Financial Manager approval with full audit trail                                                  |
| **Four-Eyes Control**      | Segregation across operational (Receptionist/Pharmacist/HR), supervisory (Manager/Accountant), control (Financial Manager), and executive (Board President) tiers |
| **Real-time Visibility**   | Dashboards refresh transactionally; Financial Manager sees live cash position and payroll status                                                                  |
| **Defensive Design**       | Business rules enforced at database, application, workflow, and approval levels                                                                                   |

### 1.3 Role Authority Matrix (v2.0 — Corrected)

| Function                                |  Receptionist  |      Pharmacist      |    HR    |          Accountant          |              Manager              |      Financial Manager      |   Board President   |
| --------------------------------------- | :------------: | :------------------: | :-------: | :--------------------------: | :--------------------------------: | :--------------------------: | :-----------------: |
| **Patient Registration**          | ✓ Create/Edit |          ✗          |    ✗    |              ✗              |               ✓ All               |              ✗              |         ✗         |
| **Invoice Creation**              |  ✓ POS/Draft  |          ✗          |    ✗    |              ✗              |               ✓ All               |           ✓ View           |         ✗         |
| **Payment Collection**            |  ✓ Cash/Card  |   ✓ Pharmacy POS   |    ✗    |              ✗              |            ✓ Override            |              ✗              |         ✗         |
| **Credit Note / Refund**          |  ✓ Initiate  |          ✗          |    ✗    |              ✗              |        ✓ Approve / Execute        |           ✓ View           |         ✗         |
| **Medicine Dispensing**           |       ✗       |       ✓ Full       |    ✗    |              ✗              |              ✓ View              |              ✗              |         ✗         |
| **Pharmacy POS (OTC)**            |       ✗       |       ✓ Full       |    ✗    |              ✗              |              ✓ View              |              ✗              |         ✗         |
| **Inventory Adjustments**         |       ✗       |   ✓ Request Only   |    ✗    |              ✗              |           ✓ Approve All           |           ✓ View           |         ✗         |
| **Purchase Orders**               |       ✗       | ✓ Initiate ≤ETB 5k |    ✗    |              ✗              |        ✓ Approve ≤ETB 25k        |    ✓ Approve ≤ETB 100k    |  ✓ > ETB 100,000  |
| **Employee Records**              |       ✗       |          ✗          |  ✓ Full  |              ✗              |              ✓ View              |           ✓ View           |       ✓ View       |
| **Attendance & Leave**            |       ✗       |          ✗          | ✓ Manage |              ✗              |             ✓ Approve             |              ✗              |         ✗         |
| **Payroll Input**                 |       ✗       |          ✗          | ✓ Enter |          ✓ Assist          |                 ✗                 |       ✓ Approve/Post       |       ✓ View       |
| **Journal Entries**               |       ✗       |          ✗          |    ✗    | ✓ Create / Post ≤ETB 5,000 | ✓ Approve ETB 5k–20k (No Create) |   ✓ Post/Approve >ETB 5k   |       ✓ View       |
| **Expense Record**                |       ✗       |          ✗          |    ✗    |          ✓ Record          |      ✓ Record ≤ ETB 5,000*      | ✓ Approve ETB 5,000–50,000 |   ✓ > ETB 50,000   |
| **Expense Approval**              |       ✗       |          ✗          |    ✗    |              ✗              | ✓ Approve ≤ ETB 5,000 (non-self) |     ✓ ETB 5,000–50,000     |   ✓ > ETB 50,000   |
| **Financial Reports**             |       ✗       |          ✗          |    ✗    |         ✓ Standard         |           ✓ Operational           |     ✓ All + Management     |    ✓ Executive    |
| **System Settings**               |       ✗       |          ✗          |    ✗    |              ✗              |           ✓ Operational           |     ✓ Fiscal/Financial     |    ✓ Strategic    |
| **User Management**               |       ✗       |          ✗          |    ✗    |              ✗              |              ✓ Staff              |              ✗              |      ✓ Roles      |
| **Equity — Share Issuance**      |       ✗       |          ✗          |    ✗    |          ✓ Record          |              ✓ View              |      ✓ Post / Execute      | ✓**Approve** |
| **Equity — Dividend**            |       ✗       |          ✗          |    ✗    |          ✓ Record          |                 ✗                 |     ✓ Calculate / Post     | ✓**Approve** |
| **Equity — Compliance Override** |       ✗       |          ✗          |    ✗    |              ✗              |                 ✗                 |              ✗              |     ✓ Approve     |

*\*Manager expenses ≤ETB 5,000 are auto-processed but flagged for periodic Financial Manager review; Manager cannot approve their own expenses.*

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
│   ├── Receipt Reprint
│   ├── Credit Notes & Refunds
│   └── Invoice Write-off
│
├── 💊 Pharmacy [Pharmacist, Manager]
│   ├── Pharmacy POS (OTC & Prescription Payment)
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
│   ├── Stock Movement History
│   └── Physical Inventory Count
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
│   ├── Expense Management
│   │   ├── Record Expense
│   │   ├── Expense Categories
│   │   ├── Recurring Expenses
│   │   └── Expense Approval [Manager, Financial Manager, Board President]
│   └── Period Close
│       ├── Month-End Close
│       └── Year-End Close
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
│       │   ├── Variable Deductions
│       │   └── Bonus & Commissions
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

1. **Patient Registration** → Creates master data used by all service modules. **Privacy consent captured at registration.**
2. **Billing & Cashier** → Generates invoices and payments; feeds revenue to GL. **Credit invoices require Manager approval.**
3. **Pharmacy** → Consumes inventory via dispensing, generates dispensing records linked to invoices. **Pharmacy POS handles OTC and prescription co-payments directly.**
4. **Human Resource** → Manages employee master data, attendance, leave; feeds payroll computation.
5. **Equity** → Manages ownership cap table, enforces statutory compliance, feeds dividends and share issuances directly into GL. **Board President approves strategic equity events; Financial Manager executes posting.**
6. **Accounting** → Consolidates all financial transactions; GL is the authoritative ledger. **Period-end close is workflow-driven.**
7. **Financial Manager** → Reviews and approves payroll, mid-tier expenses, journal entries, fiscal period controls, and **statutory remittances**.
8. **Reporting** → Aggregates from all modules in real-time via materialized views and controlled summaries.

---

## 4. Detailed Operational Workflows

### 4.1 PATIENT MANAGEMENT WORKFLOW

#### Process: New Patient Registration

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Receptionist│────►│ System      │────►│ Patient     │
│         │     │ greets,     │     │ validates   │     │ Consent     │
│         │     │ requests ID │     │ uniqueness  │     │ & Card      │
│         │     │ + consent   │     │ + consent   │     │ generated   │
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
3. **Privacy & Consent:** Receptionist captures informed consent for data processing; consent record linked to patient master
4. If unique: Patient master record created with `status = active`, `registration_date = today`
5. System auto-generates **Registration Fee Invoice** linked to `invoice_type = 'registration'`
6. Receptionist collects payment via "Payment Collection" screen; links payment to invoice
7. Patient card printed; patient becomes eligible for all clinical services

**Data Integrity Rules:**

- Patient cannot be hard-deleted if any invoice, visit, or dispensing record exists (soft delete only)
- Patient ID is immutable after creation
- Registration fee invoice must be paid or marked as "waived" (requires Manager override) before clinical services are rendered
- **Role-based data scope:** Receptionists see only patients registered at their station/session unless granted broader access by Manager

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
   - **Clinical Services:** Consultation, procedure codes (linked to `service_catalog`) — *service rendered is confirmed by clinician sign-off or check-in record before invoice finalization*
   - **Laboratory Services:** Lab test codes (linked to `lab_services`) — *results recorded externally; billed via POS*
   - **Pharmacy Items:** Medicine codes (linked to `medicine_catalog`; real-time stock check)
   - **Miscellaneous:** Custom line items with manual description and price
4. System validates:
   - Pharmacy items: stock availability check (sufficient quantity?)
   - Prices: pulled from catalog (Manager can override with full audit trail; override flagged for Financial Manager sampling)
   - VAT: calculated per business rules settings
5. Invoice status set to **'draft'** — editable by creator only
6. **Credit Invoice Check:** If patient is on credit terms or payment is not immediate, system requires Manager approval before invoice status can become 'pending' or 'credit'
7. Receptionist clicks "Confirm & Collect Payment"
8. Payment recorded in `payments` table; linked to invoice; invoice status → **'paid'**
9. If pharmacy items included: auto-create dispensing request in Pharmacy module with status **'pending'**
10. Receipt printed; patient directed to pharmacy or clinical area

**Financial Transaction Lifecycle:**

```
Invoice Created (draft)
    ↓
Payment Recorded (cash/card) [or Credit Approved by Manager]
    ↓
Cash Register Updated (daily tally)
    ↓
End-of-Day Reconciliation (Manager verifies + variance to 93300)
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
- **Credit Limit:** Patients exceeding credit limit require Manager override

**Overdue Invoices:**

- Auto-flagged after payment term expiration
- Manager receives notification
- Collection workflow: Receptionist calls → Manager escalates → Board President approves write-off

#### 4.2.3 Credit Note & Patient Refund Workflow

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Receptionist│────►│ Select      │────►│ Reason      │
│         │     │ initiates   │     │ original    │     │ & amount    │
│         │     │ Credit Note │     │ invoice     │     │ validated   │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                  │
                    ┌────────────────────────────────────────────┘
                    ▼
           ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
           │ Manager     │────►│ Credit Note │────►│ Patient     │
           │ reviews &   │     │ posted;     │     │ refund      │
           │ approves    │     │ Revenue     │     │ issued      │
           │             │     │ reversed    │     │ (if paid)   │
           └─────────────┘     └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Receptionist** opens "Credit Notes & Refunds"; selects original invoice
2. System validates: invoice exists, was paid or partially paid, and is within return period (configurable)
3. Receptionist enters reason code (price error, service not rendered, patient return, duplicate charge)
4. System calculates maximum credit amount (up to invoice total minus prior credits)
5. **Manager approval required for all credit notes and refunds**
6. Upon approval:
   - **Credit Note** created (contra-revenue to 49000 Sales Returns & Allowances)
   - If refund due: **Payment Reversal** recorded; cash refund executed by Receptionist with Manager oversight
   - Original invoice marked as 'credit_issued'; linked to credit note
   - If pharmacy items returned: **Stock Return** workflow triggered (inventory batch restocked or written off per expiry status)
7. GL auto-posting:
   - Debit: `49000` Sales Returns & Allowances
   - Credit: `11110` Cash on Hand (if refund) or `11210` Trade Receivables (if credit applied to AR)

#### 4.2.4 Invoice Write-off & Bad Debt Workflow

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Accountant  │────►│ Manager     │────►│ Board President│
│         │     │ flags as    │     │ reviews &   │     │ approves if   │
│         │     │ bad debt    │     │ recommends  │     │ > ETB 5,000   │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                  │
                    ┌────────────────────────────────────────────┘
                    ▼
           ┌─────────────┐     ┌─────────────┐
           │ Write-off   │────►│ GL Posting: │
           │ posted;     │     │ DR Bad Debt │
           │ AR reduced  │     │ CR AR       │
           └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Accountant** reviews AR Aging; identifies uncollectible invoice (>90 days, collection attempts exhausted)
2. Accountant initiates "Invoice Write-off" with justification and collection log
3. **Manager reviews** and confirms uncollectibility
4. **Board President approval required if write-off > ETB 5,000** (or any amount per clinic policy)
5. Upon approval:
   - Invoice status → 'written_off'
   - GL auto-entry:
     - Debit: `61900` Bad Debt Expense
     - Credit: `11210` Trade Receivables
   - Update `Allowance for Doubtful Accounts (11220)` if provision method used

---

### 4.3 PHARMACY WORKFLOW

#### 4.3.0 Pharmacy POS (Over-the-Counter & Prescription Payment)

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Pharmacist  │────►│ Select      │────►│ Scan / add  │
│         │     │ opens       │     │ patient or  │     │ OTC items   │
│         │     │ Pharmacy POS│     │ walk-in     │     │ or Rx co-pay│
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                  │
        ┌────────────────────────────────────────────────────────┘
        ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ System      │────►│ Pharmacist  │────►│ Patient     │────►│ Invoice +   │
│ validates   │     │ collects    │     │ pays        │     │ Dispensing  │
│ stock &     │     │ payment     │     │ (cash/card) │     │ posted      │
│ price       │     │             │     │             │     │ (OTC auto-  │
│             │     │             │     │             │     │ deducted)   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Pharmacist** opens "Pharmacy POS" from Pharmacy menu
2. Selects patient (search/scan) or walk-in (no patient record required for OTC)
3. Adds items:
   - **OTC Items:** Direct from medicine catalog; stock check enforced
   - **Prescription Co-pay:** Links to pending prescription from Billing POS; patient pays balance/differential
   - **Misc Pharmacy:** Non-stock items (syringes, consumables)
4. System calculates total, applies any discounts (Manager override required)
5. Pharmacist collects payment (cash/card); system generates receipt and invoice
6. **OTC items:** Auto-dispensed; stock deducted immediately from `inventory_batches`; dispensing record created
7. **Prescription items:** If full payment collected, prescription status → 'paid'; dispensing queue updated
8. End-of-day: Pharmacy POS cash count reconciled independently by **Manager** (not Pharmacist); variance posted to `93300` Cash Shortage/Overage

**Segregation Control:** Pharmacist can sell, dispense, and collect cash for pharmacy items only. Daily cash reconciliation is performed by Manager/Accountant. Pharmacy POS transactions are isolated from main cashier register but feed the same GL revenue accounts (`43000` Pharmacy Sales Revenue).

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
2. Queue shows: Patient name, Invoice #, Items requested, Priority (normal/urgent), **Payment Status (paid / credit / partial)**
3. Pharmacist selects prescription; system displays:
   - Invoice details (read-only)
   - Required medicines with requested quantities
   - Available stock by batch (FIFO auto-suggested, manual override allowed with reason logged)
   - Expiry dates (warning if less than 3 months)
   - **Payment Status Alert:** If invoice is unpaid or overdue, system warns; dispensing blocked if overdue > credit limit (Manager override required)
4. **Stock Validation:**
   - If sufficient: proceed to dispensing
   - If insufficient: system flags "Stock Shortage"; auto-creates reorder recommendation; notifies Manager
   - If expired: blocks dispensing; requires stock adjustment (write-off) before proceeding
5. Pharmacist selects batches (FIFO enforced by default); system calculates exact cost of goods sold (COGS) per batch
6. Patient signs digital or paper receipt; dispensing status → **'completed'**
7. Stock automatically deducted from `inventory_batches`; `stock_movements` record created
8. Dispensing record linked to invoice for revenue recognition and COGS posting

**Unclaimed Prescription Handling:**

- Prescriptions pending > 24 hours trigger alert to Receptionist/Manager
- After 48 hours (configurable), system auto-cancels dispensing request, reverses inventory reservation, and marks invoice for refund/credit note processing

**Patient Return of Dispensed Medicine:**

- Patient returns unused medicine within return period (e.g., 7 days)
- Pharmacist inspects condition and expiry; if acceptable, initiates return
- Manager approval required for all returns
- Stock restocked to original batch (if unopened and valid) or written off to `61800` Inventory Shrinkage
- Credit note issued against original invoice (see §4.2.3)

#### 4.3.2 Inventory Management Sub-Workflows

**Stock Adjustments:**

- **Pharmacist** can **request** adjustments (spoilage, breakage, expiry, recount)
- **Manager approval required for ALL adjustments with value impact** (> ETB 0 or > 0 units)
- System auto-approves **quantity-only recount corrections = 0 value** with mandatory reason code
- Adjustment creates `stock_movement` record with mandatory reason code
- Accounting auto-sync:
  - If adjustment is COGS-related (batch cost error): Debit `54000` / Credit `11310`
  - If adjustment is shrinkage/spoilage: Debit `61800` Inventory Shrinkage & Spoilage Expense / Credit `11310`

**Expiry Alerts:**

- Daily automated scan: items expiring within 30/60/90 days
- Pharmacist receives notification; can initiate:
  - Return to supplier (if within return policy)
  - Discounted sale (Manager approval required)
  - Write-off (stock adjustment with reason = 'expired' → `61800`)

**Reorder Recommendations:**

- System calculates reorder point = (Average daily usage × Lead time) + Safety stock
- When stock ≤ reorder point: auto-generate recommendation
- Pharmacist reviews → converts to Purchase Order or dismisses (reason logged)

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
6. **Supplier Return:** If goods are damaged or incorrect, Pharmacist initiates "Supplier Return" request; Manager approves; stock reversed; debit note recorded against AP
7. **AP Invoice Creation:** Supplier invoice linked to PO; auto-creates Accounts Payable entry
8. **Payment Scheduling:** Accountant schedules payment based on terms; Financial Manager approves if above threshold

**Supplier Performance Tracking:**

- Metrics: On-time delivery percentage, quantity accuracy percentage, price variance, quality (expiry issues)
- Quarterly review by Manager; poor performers flagged for replacement

#### 4.4.2 Physical Inventory Count

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Pharmacist  │────►│ System      │────►│ Manager     │
│         │     │ counts      │     │ generates   │     │ reviews     │
│         │     │ physical    │     │ count sheet │     │ variances   │
│         │     │ stock       │     │ by batch    │     │ & approves  │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                  │
                    ┌────────────────────────────────────────────┘
                    ▼
           ┌─────────────┐     ┌─────────────┐
           │ Variance    │────►│ GL Posting: │
           │ posted to   │     │ DR 61800    │
           │ shrinkage   │     │ CR 11310    │
           │ or batch    │     │ (if loss)   │
           │ corrected   │     │             │
           └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Manager** schedules count (full annual or cycle count by ABC category)
2. **Pharmacist** counts physical stock by batch; enters quantities into system
3. System compares physical vs. perpetual; generates variance report
4. **Manager reviews** variances; investigates significant discrepancies
5. Upon approval:
   - If loss: Debit `61800` Inventory Shrinkage / Credit `11310` Inventory
   - If gain: Debit `11310` / Credit `61800` (or `90000` Other Income if immaterial)
   - Batch records updated; `stock_movements` logged with reason = 'physical_count'

---

### 4.5 ACCOUNTING WORKFLOW

#### 4.5.1 Chart of Accounts Structure (Corrected for Clinic)

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
│   │   ├── 11260 Advance Income Tax (Quarterly)
│   │   └── 11270 Dividend Withholding Tax Receivable
│   ├── 11300 Inventory
│   │   ├── 11310 Raw Materials & Medical Supplies
│   │   └── 11330 Inventory Reserve
│   ├── 11400 Other Current Assets
│   │   ├── 11410 Prepaid Rent
│   │   ├── 11420 Prepaid Insurance
│   │   ├── 11430 Office Supplies
│   │   ├── 11440 Prepayments to Suppliers
│   │   ├── 11450 Suspense & Clearing Account
│   │   └── 11460 Employee Receivables & Advances
│   └── 11500 Fixed Assets Held for Disposal
└── 12000 Fixed Assets
    ├── 12100 Land
    ├── 12200 Buildings
    ├── 12210 Accum. Deprec. — Buildings
    ├── 12300 Machinery & Equipment (Medical)
    ├── 12310 Accum. Deprec. — Machinery
    ├── 12400 Computers & Electronics
    ├── 12410 Accum. Deprec. — Computers
    ├── 12500 Furniture & Fixtures
    ├── 12510 Accum. Deprec. — Furniture
    └── 12600 Gain / Loss on Asset Disposal

LIABILITIES (2xxxx)
├── 21000 Current Liabilities
│   ├── 21100 Accounts Payable
│   │   └── 21110 Trade Payables
│   ├── 21200 Taxes & Statutory Payable
│   │   ├── 21210 VAT Output (Sales Tax)
│   │   ├── 21220 WHT Payable
│   │   ├── 21230 Income Tax Payable (PAYE)
│   │   ├── 21240 Dividend Withholding Tax Payable
│   │   └── 21250 Other Statutory Payables
│   ├── 21300 Other Current Liabilities
│   │   ├── 21310 Net Salaries Payable (Accrual)
│   │   ├── 21320 Pension Payable (Employee 7%)
│   │   ├── 21330 Pension Payable (Employer 11%)
│   │   ├── 21340 SHI Payable (Employee 1.5%)
│   │   ├── 21350 SHI Payable (Employer 1.5%)
│   │   ├── 21360 Other Payroll Deductions Payable
│   │   ├── 21370 Patient Deposits / Advances
│   │   └── 21380 Deferred Revenue
│   ├── 21400 Accrued Expenses
│   ├── 21500 Dividends Payable
│   └── 21900 Provision for Gratuity / End-of-Service Benefits
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
│   ├── 54000 Inventory Adjustments — COGS Related
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
│   ├── 61800 Inventory Shrinkage & Spoilage Expense
│   ├── 61900 Bad Debt Expense
│   ├── 61910 Write-off Expense — Inventory / Asset
│   ├── 62000 Payroll & Benefits
│   │   ├── 62100 Salaries & Wages Expense
│   │   ├── 62150 Direct Clinical Labor Expense
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
    ├── 93200 Exchange Gain/Loss
    └── 93300 Cash Shortage / Overage
```

**Key Corrections Applied:**

- **21240** is the sole Dividend WHT Payable account (removed 22400 references).
- **61800** handles non-COGS inventory shrinkage/spoilage; `54000` restricted to COGS-related adjustments only.
- **62150** replaces `52000` for clinic labor (reclassified to OpEx).
- Added **Suspense & Clearing (11450)** for unmatched transactions.
- Added **Employee Receivables (11460)**, **Provision for Gratuity (21900)**, **Deferred Revenue (21380)**, **Bad Debt Expense (61900)**, **Cash Shortage/Overage (93300)**, **Gain/Loss on Disposal (12600)**.

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
5. **Approval Rules (Corrected):**
   - Entry ≤ ETB 5,000: Accountant can post directly
   - Entry ETB 5,001–20,000: **Manager approval** required (Manager cannot create JEs, only approve)
   - Entry ETB 20,001–100,000: Financial Manager approval required
   - Entry > ETB 100,000: Board President approval required
6. Once approved: status → **Posted**; immutable; GL updated; reversal requires contra-entry

**Journal Entry Reversal Workflow:**

- Accountant initiates "Reverse Entry" on posted journal
- System auto-generates contra-entry with identical lines (debits/credits flipped) and links to original
- Reversal follows same approval tier as original entry
- Reason mandatory; original entry marked as 'reversed'

**Auto-Generated Journal Entries (System Integration):**

| Source Module        | Auto-Journal Entry                                                  | Frequency        |
| -------------------- | ------------------------------------------------------------------- | ---------------- |
| POS Payment          | Debit Cash / Credit Revenue Account                                 | Real-time        |
| Pharmacy POS         | Debit Cash / Credit Pharmacy Revenue                                | Real-time        |
| Pharmacy Dispensing  | Debit COGS / Credit Inventory                                       | Real-time        |
| Goods Receipt        | Debit Inventory / Credit AP                                         | Real-time        |
| Expense Recorded     | Debit Expense / Credit Cash or AP                                   | Real-time        |
| Stock Adjustment     | Debit 61800 or 54000 / Credit Inventory                             | On approval      |
| Credit Note          | Debit 49000 / Credit Cash or AR                                     | On approval      |
| Invoice Write-off    | Debit 61900 / Credit AR                                             | On approval      |
| Payroll Posted       | Debit Salaries Expense / Credit Bank, Tax Payable, Pension Payable  | Per payroll run  |
| Statutory Remittance | Debit Tax/Pension Payable / Credit Bank                             | On remittance    |
| End-of-Day Cash      | Debit Bank / Credit Cash (deposit)                                  | Daily            |
| EOD Cash Variance    | Debit/Credit 93300 / Cash                                           | Daily            |
| Share Issuance       | Debit Cash/Bank / Credit Common Stock, Share Premium                | On approval      |
| Dividend Declaration | Debit Retained Earnings / Credit Div. Payable, WHT Payable (21240)  | On approval      |
| Dividend Payment     | Debit Dividends Payable / Credit Bank                               | On batch payment |
| Dividend WHT Remit   | Debit WHT Payable (21240) / Credit Bank                             | On remittance    |
| Treasury Buyback     | Debit Treasury Stock / Credit Bank                                  | On approval      |
| Treasury Reissue     | Debit Cash / Credit Treasury Stock, Share Premium/Retained Earnings | On approval      |
| Legal Reserve Trans. | Debit Retained Earnings / Credit Legal Reserve                      | Fiscal year-end  |

#### 4.5.3 Accounts Receivable Workflow

**From Billing Module:**

- Unpaid or partially paid invoices auto-create AR entries
- **Customer Aging:** 0-30 days, 31-60 days, 61-90 days, >90 days
- **Collection Tracking:** Receptionist logs collection attempts; Manager reviews weekly
- **Invoice Reconciliation:** Payments matched to invoices; unapplied payments tracked as patient deposits (`21370`)
- **Bad Debt Provision:** Accountant reviews >90 days; proposes write-off via §4.2.4 workflow

#### 4.5.4 Accounts Payable Workflow

**From Inventory Module:**

- Approved supplier invoices create AP entries
- **Supplier Aging:** Tracks payment terms compliance
- **PO Reconciliation:** Three-way match (PO quantity vs. Receipt quantity vs. Invoice quantity)
- **Payment Scheduling:** Accountant schedules payment based on terms; Financial Manager approves if above threshold
- **Supplier Return Debit Notes:** Reduce AP balance upon Manager approval

#### 4.5.5 Bank & Cash Workflow

**Cash Book:**

- Daily cash register reconciliation by Receptionist (main cashier) and Pharmacist (pharmacy POS)
- **Manager verifies** end-of-day cash count vs. system total independently
- Cash deposit to bank recorded by Accountant
- **Variance Handling:** If physical cash ≠ system cash, difference posted to `93300` Cash Shortage/Overage with mandatory explanation

**Bank Reconciliation:**

- Monthly import of bank statement (CSV/manual entry)
- System auto-matches transactions; unmatched items flagged to `11450` Suspense & Clearing
- Accountant investigates discrepancies; Financial Manager reviews

**Petty Cash:**

- Fixed float amount (e.g., ETB 10,000)
- Expenses recorded with receipts; replenishment triggered when low
- Manager approves replenishment; Accountant processes

#### 4.5.6 Period-End Close Workflow

**Month-End Close:**

1. **Accountant** initiates "Month-End Close" checklist:
   - All bank reconciliations complete
   - All AP/AR reconciliations complete
   - No unposted journal entries in closed period
   - Inventory count variance posted
   - Payroll posted (if applicable)
   - Depreciation posted
2. System validates sequential close (previous period must be closed)
3. Accountant locks period to new transactions
4. **Financial Manager reviews** and confirms period closure
5. System generates **Management Pack** (P&L, BS, TB, CF) automatically

**Year-End Close:**

1. Executes Month-End Close steps
2. **Income Summary Close:**
   - Debit all Revenue accounts / Credit `39000` Income Summary
   - Credit all Expense accounts / Debit `39000` Income Summary
   - Net balance of `39000` transferred to `33000` Retained Earnings (profit) or reversed (loss)
3. **Legal Reserve Appropriation** (if share-based and net profit > 0):
   - Calculate transfer = min(net profit × 5%, target 10% of share capital − current reserve)
   - Debit `33000` / Credit `34600`
4. **Equity Compliance Check** (if share-based): `EquityComplianceService.runAll()`
5. **Board President reviews** year-end financial statements
6. New fiscal year opening balances auto-generated; `39900` Opening Balance Equity cleared

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

**Operational Steps:**

1. **Accountant** or **Manager** records expense (Manager limited to ≤ETB 5,000)
2. System checks approval threshold:
   - ≤ ETB 5,000: Auto-approved if recorded by Accountant; if recorded by Manager, requires Financial Manager approval (no self-approval)
   - ETB 5,001 – 50,000: Financial Manager approval
   - > ETB 50,000: Board President approval
     >
3. **Threshold Change Control:** Any modification to approval thresholds in Clinic Settings requires **Board President** approval with audit log
4. Payment processed; expense posted to GL

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
6. System auto-generates **Payroll Profile** with default salary structure
7. Employee becomes eligible for attendance tracking, leave, and payroll

**Termination / Final Settlement Workflow:**

- Manager initiates termination with reason and last working day
- HR processes: leave encashment, gratuity calculation (if applicable), final payroll
- **Financial Manager approves** final settlement payment
- GL posting: Debit `21900` Provision for Gratuity (if provisioned) or `62100` Salaries Expense / Credit `11130` Bank
- Employee status → 'terminated'; system access revoked

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
- **Bonus & Commissions:** Dedicated input screen for anomalous bonuses; system applies tax spreading algorithm to prevent tier jumps.
- **Access:** HR enters time/variable data; input window locks the moment the period transitions past `draft`.

#### 4.8.2 The Computation Engine & Statutory Rules Engine

The system features an automated, deterministic calculation pipeline conforming to Ethiopian Labor & Tax Law driven by the **Ethiopian Statutory Rules Engine**.

##### 4.8.2.1 Statutory Rules Engine (Strategy Pattern)

The statutory deduction logic implements a decoupled **Strategy Pattern** integrating securely into the computation pipeline:

###### Income Tax Rule (PAYE)

Evaluates `Taxable Income` against the progressive 2025 Ethiopian Income Tax Brackets and applies exact deduction thresholds per tier.

**Ethiopian Progressive Tax Brackets (2025 Amendment, effective July 1, 2025):**

| Bracket | Min (ETB) | Max (ETB) | Rate | Deduction |
| ------- | --------- | --------- | ---- | --------- |
| 1       | 0         | 2,000     | 0%   | 0         |
| 2       | 2,001     | 4,000     | 15%  | 300       |
| 3       | 4,001     | 7,000     | 20%  | 500       |
| 4       | 7,001     | 10,000    | 25%  | 850       |
| 5       | 10,001    | 14,000    | 30%  | 1,350     |
| 6       | 14,001    | ∞        | 35%  | 2,050     |

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
    └── Variable inputs entered (timesheets, deductions, advances, bonuses)

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

| Line Type                       | Expected Canonical Code                 | Debit/Credit Action                         | Auto-Generated |
| ------------------------------- | --------------------------------------- | ------------------------------------------- | -------------- |
| **Salary Expense**        | `62100` Basic Salaries & Wages        | **Debit** (Total Basic Pay)           | Yes            |
| **Direct Clinical Labor** | `62150` Direct Clinical Labor Expense | **Debit** (Clinician labor allocated) | Yes            |
| **Allowance Exp.**        | `62600`/`62700` Housing/Transport   | **Debit** (Total Fixed Allowances)    | Yes            |
| **Pension Exp.**          | `62400` Employer Pension Expense      | **Debit** (Employer 11% Portion)      | Yes            |
| **Overtime Exp.**         | `62200` Overtime Pay Expense          | **Debit** (Total Overtime Earned)     | Yes            |
| **Bonus Expense**         | `62300` Bonus Expense                 | **Debit** (Total Bonus Paid)          | Yes            |
| **Employer SHI Exp.**     | `62910` Employer SHI Expense (1.5%)   | **Debit** (Employer SHI Portion)      | Yes            |
| **Tax Liability**         | `21230` Income Tax Payable (PAYE)     | **Credit** (Total Deducted PAYE)      | Yes            |
| **Pension Liab.**         | `21320`/`21330` Pension Payables    | **Credit** (Employee 7% + Emp 11%)    | Yes            |
| **SHI Liability**         | `21340`/`21350` SHI Payables        | **Credit** (Employee 1.5% + Emp 1.5%) | Yes            |
| **Asset Recovery**        | `21360` Other Payroll Deductions      | **Credit** (Repayment Deductions)     | Yes            |
| **Net Pay Accrual**       | `21310` Net Salaries Payable          | **Credit** (Total Net Pay Escrowed)   | Yes            |

*Next Step: A secondary Payment Journal handles the `Debit Net Salaries Payable` to `Credit Bank / Cash on Hand`.*

#### 4.8.5 Advance & Loan Lifecycle Workflow

| HR Event               | Accounting Impact                                                | Workflow Action                               |
| ---------------------- | ---------------------------------------------------------------- | --------------------------------------------- |
| Salary Advance Issued  | Debit Employee Receivable (11460) / Credit Cash                  | Financial Manager approves & issues advance   |
| Disbursed Repayment    | Debit Cash / Credit Employee Receivable (11460)                  | Auto-deducted directly via computation engine |
| End-of-Service Benefit | Debit Provision for Gratuity (21900) / Credit Bank               | Manual (requires Financial Manager approval)  |
| Payroll Reversals      | Contra-entries corresponding exact prior ledger posting records. | Secured `Void` functionality by FinManager  |

**Payroll Reversal / Void Workflow:**

- Financial Manager initiates reversal on posted payroll
- System generates exact contra-entry reversing all GL impacts (salaries expense, payables, bank)
- Reversal reason mandatory; original payroll marked 'reversed'
- New replacement payroll must be computed and posted separately

#### 4.8.6 Statutory Remittance Workflow

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Accountant  │────►│ System      │────►│ Financial   │
│         │     │ generates   │     │ calculates  │     │ Manager     │
│         │     │ remittance  │     │ total       │     │ reviews &   │
│         │     │ batch       │     │ liabilities │     │ approves    │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                  │
                    ┌────────────────────────────────────────────┘
                    ▼
           ┌─────────────┐     ┌─────────────┐
           │ Payment to  │────►│ GL Posting: │
           │ ERCA /      │     │ DR Tax/Pension│
           │ Pension /   │     │ Payable     │
           │ SHI         │     │ CR Bank     │
           └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. **Accountant** opens "Statutory Remittance" from Payroll or Accounting menu
2. Selects remittance type: PAYE (21230), Pension (21320+21330), SHI (21340+21350), Dividend WHT (21240)
3. System aggregates pending liabilities from posted payrolls/dividends
4. **Financial Manager reviews** totals and approves remittance batch
5. Accountant executes bank transfer; uploads remittance receipt/reference
6. System clears liability accounts; GL posted
7. **Overdue tracking:** Unremitted statutory liabilities flagged on Financial Manager dashboard after statutory deadline

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

| Ethiopian Type         | Category Name                                          | SL Rate | DV Rate | Method Locked | Poolable | Target GL Mapping                            |
| ---------------------- | ------------------------------------------------------ | ------- | ------- | ------------- | -------- | -------------------------------------------- |
| `buildings`          | Buildings & Structural Improvements                    | 5%      | —      | SL only ✅    | ❌       | `12200` (Cost) / `12210` (Accum. Deprec) |
| `intangibles`        | Intangible Assets                                      | 10%     | —      | SL only ✅    | ❌       | `12xxx` General Fixed Assets               |
| `greenhouses`        | Greenhouses                                            | 10%     | —      | SL only ✅    | ❌       | `12xxx` General Fixed Assets               |
| `computers_software` | Computers, Software & Data Storage                     | 20%     | 25%     | ❌            | ✅       | `12400` (Cost) / `12410` (Accum. Deprec) |
| `mining_petroleum`   | Mining & Petroleum Assets                              | 25%     | 30%     | ❌            | ✅       | `12xxx` General Fixed Assets               |
| `all_other`          | All Other Depreciable Assets (e.g., Medical Machinery) | 15%     | 20%     | ❌            | ✅       | `12300` (Cost) / `12310` (Accum. Deprec) |

**Key Statutory Rules:**

- Buildings, Intangibles, and Greenhouses must be depreciated individually via **Straight-Line**.
- Computers and Machinery can leverage pooled **Diminishing Value** tracking.

#### 4.9.3 Depreciation Engine & GL Posting

Asset depreciation calculates utilizing either the global **Straight-Line (SL)** formula or **Diminishing-Value (DV)** pooling logic. Depreciation is locked and securely posted at month-end.

**GL Journal Auto-Posting Template:**

| Account Type                       | Description                                                    | Action           |
| ---------------------------------- | -------------------------------------------------------------- | ---------------- |
| `61600` Depreciation Expense     | Month's designated depreciation cost                           | **Debit**  |
| `12x10` Accumulated Depreciation | Contra-asset corresponding to the targeted asset pool/category | **Credit** |

#### 4.9.4 Asset Lifecycle Workflows

1. **Acquisition:** Accountant records purchase details, logging invoice date and capitalizing VAT (Debit `12xxx` Fixed Asset, Credit `11100`/`21100` Bank/AP).
2. **Monthly Depreciation:** Financial Manager executes an automatic period-end process ensuring exact statutory percentages are exhausted against active `12xxx` book values per month.
3. **Disposal:** Whether sold, scrapped, or lost, system unlinks the exact `Asset Book Value` logic, reversing the accumulated contra-asset (Debit `12x10`) and original asset ledger (Credit `12xxx`), immediately recognizing Gain/Loss from Asset Disposal.

**Asset Disposal Workflow (Detailed):**

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ START   │────►│ Accountant  │────►│ Financial   │────►│ Asset       │
│         │     │ initiates   │     │ Manager     │     │ removed     │
│         │     │ disposal    │     │ approves    │     │ from active │
└─────────┘     └─────────────┘     └─────────────┘     └──────┬──────┘
                                                                  │
                    ┌────────────────────────────────────────────┘
                    ▼
           ┌─────────────┐     ┌─────────────┐
           │ GL Posting: │────►│ Proceeds to │
           │ DR Accum.Dep│     │ Bank (if    │
           │ DR 12600    │     │ sold)       │
           │ CR Asset    │     │             │
           └─────────────┘     └─────────────┘
```

**Operational Steps:**

1. Accountant selects asset, enters disposal type (sale/scrap/loss), date, and sale proceeds (if any)
2. System calculates net book value and accumulated depreciation
3. **Financial Manager approves** disposal
4. GL posted:
   - Debit: `12x10` Accumulated Depreciation (full amount)
   - Debit/Credit: `12600` Gain/Loss on Asset Disposal (balancing figure)
   - Credit: `12xxx` Original Asset Cost
   - If sold: Debit `11130` Bank / Credit `12600` (proceeds)
5. Asset status → 'disposed'; immutable history retained in `Asset History`

---


### 4.10 EQUITY MODULE WORKFLOW

#### 4.10.1 System Architecture Overview

The Equity Module operates as a dual-ledger system, synchronizing quantity-based share tracking with amount-based double-entry accounting:

```
┌─────────────────────────────────────────────────────────┐
│                     EQUITY MODULE ARCHITECTURE                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐    ┌──────────────────────────────┐   │
│  │  Share Ledger│    │  General Ledger (Double-Entry)    │   │
│  │  (Qty-based) │    │  (Amount-based)                  │   │
│  │              │    │                                  │   │
│  │  ShareLedger │◄──►│  JournalEntry + JournalEntryLine│   │
│  │  model       │    │  via ShareTransactionService     │   │
│  └──────┬───────┘    └──────────────┬─────────────────┘   │
│         │                           │                           │
│         ▼                           ▼                           │
│  ┌──────────────┐    ┌──────────────────────────────┐   │
│  │  Cap Table   │    │  EquityMovement                  │   │
│  │  (Ownership) │    │  (Statement of Changes in Equity)│   │
│  └──────────────┘    └──────────────────────────────┘   │
│                                                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │  EquityComplianceService (Ethiopian Commercial Code)│   │
│  │  → Minimum Capital · Capital Adequacy · Legal Reserve │   │
│  │  → Nominee Check · SC Board · SC Audit · SC Shareholders│   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

**Control Flow Pipeline** — Every equity-altering action follows this secure path:

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

**Compliance Check Trigger Points** — `EquityComplianceService.runAll()` runs automatically after:

- Share issuance (cash or bonus)
- Share buyback
- Dividend approval and payment
- Journal entry posting (for share-based companies)
- Period close

#### 4.10.2 Company Type Gating & Entity Classification

**Entity Types (2021 Ethiopian Commercial Code)**

| Company Type                  | Equity Module                     | Share-Based | Statutory Basis    |
| ----------------------------- | --------------------------------- | ----------- | ------------------ |
| Sole Proprietorship           | Owner's Capital (31000/32000)     | ❌ No       | N/A                |
| Private Limited Company (PLC) | Full Equity Module                | ✅ Yes      | Art. 510 et seq.   |
| Single-Member PLC             | Full Equity + Nominee Enforcement | ✅ Yes      | Art. 510 (variant) |
| Share Company (SC)            | Full Equity + SC Compliance Suite | ✅ Yes      | Art. 304 et seq.   |

**Gating Methods on `Company` Model**

| Method                         | Purpose                                            | Returns `true` For               |
| ------------------------------ | -------------------------------------------------- | ---------------------------------- |
| `isShareBased()`             | Canonical equity gating — controls all Equity nav | PLC, Single-Member PLC, SC         |
| `isPLC()`                    | Checks PLC family membership (excludes SC)         | PLC, Single-Member PLC             |
| `isSC()`                     | Checks Share Company status                        | SC only                            |
| `isSingleMemberPLC()`        | Single-member variant check                        | Single-Member PLC only             |
| `requiresBoardOfDirectors()` | SC governance requirement (Art. 338)               | SC only                            |
| `requiresExternalAudit()`    | SC audit requirement (Art. 381)                    | SC only                            |
| `requiresIFRS()`             | Full IFRS compliance (AABE classification)         | SC only                            |
| `needsNomineeInfo()`         | Single-Member PLC nominee check                    | SM-PLC with empty `nominee_name` |

**Sole Proprietorship — Separate Service Layer**
Sole Proprietorship uses `OwnerCapitalService` (not the PLC equity services):

| Operation            | Journal Entry                                           | Accounts                   |
| -------------------- | ------------------------------------------------------- | -------------------------- |
| Capital Contribution | DR Bank, CR Owner's Capital (31000)                     | PostingGuard enforced      |
| Drawing/Withdrawal   | DR Owner's Drawings (32000), CR Bank                    | PostingGuard enforced      |
| Year-End Close       | DR Owner's Capital (31000), CR Owner's Drawings (32000) | Closes drawings to capital |

#### 4.10.3 Ethiopian Statutory Compliance Framework

**3.1 Master Compliance Matrix — All Integrated Articles**
The following Ethiopian legal requirements are actively enforced in the codebase through automated validations & dashboard alerts:

| #  | Legal Requirement                          | Article / Proclamation                                 | Enforcement Point                                                                          | Applies To  |
| -- | ------------------------------------------ | ------------------------------------------------------ | ------------------------------------------------------------------------------------------ | ----------- |
| 1  | Minimum Capital — PLC                     | Art. 510, Commercial Code 2021                         | `EquityComplianceService::checkMinimumCapital()` — Dashboard Alert                      | PLC, SM-PLC |
| 2  | Minimum Capital — SC                      | Art. 304, Commercial Code 2021                         | `EquityComplianceService::checkMinimumCapital()` — Dashboard Alert                      | SC          |
| 3  | Capital Adequacy / EGM Trigger             | Art. 473, Commercial Code 2021                         | `EquityComplianceService::checkCapitalAdequacy()` — Dashboard Alert + Dividend block    | PLC, SC     |
| 4  | Legal Reserve Appropriation                | Art. 452, Commercial Code 2021                         | `PeriodCloseService::applyLegalReserveAppropriation()` — Automated at year-end          | PLC, SC     |
| 5  | Legal Reserve Fulfillment                  | Art. 452, Commercial Code 2021                         | `EquityComplianceService::checkLegalReserveStatus()` — Dashboard Alert + Dividend block | PLC, SC     |
| 6  | Minimum Par Value                          | Art. 452, Commercial Code 2021                         | `ShareTransactionService::validateShareIssuance()` — Hard block on issuance             | PLC, SC     |
| 7  | No Below-Par Issuance                      | Art. 452, Commercial Code 2021                         | `ShareTransactionService::validateShareIssuance()` — Hard block on issuance             | PLC, SC     |
| 8  | Single-Member PLC Nominee                  | Commercial Code 2021 (SM-PLC provisions)               | `EquityComplianceService::checkNomineeRequirement()` — Dashboard Alert                  | SM-PLC      |
| 9  | Single-Member PLC — One Shareholder       | Commercial Code 2021 (SM-PLC provisions)               | `ShareTransactionService::validateShareIssuance()` — Hard block on issuance             | SM-PLC      |
| 10 | SC Minimum Shareholders (≥ 5)             | Art. 304, Commercial Code 2021                         | `EquityComplianceService::checkShareholderCount()` — Dashboard Alert                    | SC          |
| 11 | SC Board of Directors (3-13 members)       | Art. 338-340, Commercial Code 2021                     | `EquityComplianceService::checkBoardComposition()` — Dashboard Alert                    | SC          |
| 12 | SC Non-Shareholder Director Ratio (≤ 1/3) | Art. 338-340, Commercial Code 2021                     | `EquityComplianceService::checkBoardComposition()` — Dashboard Alert                    | SC          |
| 13 | SC External Audit Requirement              | Art. 381, Commercial Code 2021                         | `EquityComplianceService::checkExternalAuditRequirement()` — Dashboard Alert            | SC          |
| 14 | SC IFRS Compliance                         | AABE Directive (Public Interest Entity classification) | `Company::requiresIFRS()` — Reporting classification gate                               | SC          |
| 15 | Dividend WHT (10%)                         | Income Tax Proclamation No. 979/2016, Art. 53          | `DividendPaymentService::calculateShareholderDividends()` — Auto-calculated             | PLC, SC     |
| 16 | Tax Clearance Before Dividend              | Income Tax Proclamation No. 979/2016                   | `DividendValidationService::validateDeclaration()` check #8 — Dividend block            | PLC, SC     |
| 17 | Solvency Test Before Dividend              | Commercial Code 2021 (general solvency provisions)     | `DividendValidationService::performSolvencyTest()` — Dividend block                     | PLC, SC     |
| 18 | Distributable Profit Restriction           | Commercial Code 2021 (realized profit provisions)      | `DividendValidationService::getDistributableProfit()` — Dividend block                  | PLC, SC     |
| 19 | Undistributed Profit Tax                   | Income Tax Proclamation No. 979/2016, Art. 61          | `UndistributedProfitAssessment` model — Tax tracking                                    | PLC, SC     |

**3.2 Minimum Capital Thresholds**

| Company Type            | Minimum Capital (ETB) | Article  | Default in System                               | Configurable Via                   |
| ----------------------- | --------------------- | -------- | ----------------------------------------------- | ---------------------------------- |
| PLC / Single-Member PLC | 15,000                | Art. 510 | `EquityConfigurationService::getThresholds()` | `SettingCompany.minimum_capital` |
| Share Company (SC)      | 50,000                | Art. 304 | `EquityConfigurationService::getThresholds()` | `SettingCompany.minimum_capital` |

**3.3 Capital Adequacy — Art. 473**

| Condition                                 | Trigger                      | Required Action                                      |
| ----------------------------------------- | ---------------------------- | ---------------------------------------------------- |
| Accumulated losses > 50% of share capital | Dashboard DANGER alert       | Extraordinary General Meeting (EGM) must be convened |
| While breached:                           | Dividend declaration BLOCKED | Directors face legal liability if no EGM held        |

Implementation: `EquityComplianceService::checkCapitalAdequacy()` computes accumulated losses as `max(0, abs(min(0, RE_balance)))` and compares against `share_capital × threshold%`.
EGM Recording: `EgmLog` model captures `meeting_date`, `agenda_type`, `outcome`, `attendees_count`, `resolution_text`, and `board_resolution_number`.

**3.4 Legal Reserve — Art. 452**

| Parameter                   | Value                   | Configurable                              |
| --------------------------- | ----------------------- | ----------------------------------------- |
| Annual appropriation rate   | 5% of annual net profit | `SettingCompany.legal_reserve_rate`     |
| Target cap                  | 10% of share capital    | `SettingCompany.legal_reserve_cap_rate` |
| Minimum par value per share | ETB 100                 | `SettingCompany.minimum_par_value`      |

Automated Enforcement:

- At year-end close: `PeriodCloseService::applyLegalReserveAppropriation()` automatically calculates and posts the reserve transfer journal entry: `DR Retained Earnings (33000), CR Legal Reserve (34600)`
- Dashboard Alert: `EquityComplianceService::checkLegalReserveStatus()` shows a WARNING alert with progress percentage until the 10% target is reached
- Dividend Block: `DividendValidationService` check #3 blocks dividend declarations if the legal reserve obligation has not been met

**3.5 Share Company (SC) Specific Compliance**
*Art. 304 — Minimum Shareholders*

| Check                                           | Threshold                                         | Enforcement                                            |
| ----------------------------------------------- | ------------------------------------------------- | ------------------------------------------------------ |
| Active shareholders with positive share balance | ≥ 5 (configurable via `shareholder_min_count`) | Dashboard DANGER alert via `checkShareholderCount()` |

Calculation: Queries `ShareLedger` grouped by `shareholder_id`, filters for `SUM(credit_shares - debit_shares) > 0`.

*Art. 338-340 — Board of Directors*

| Check                          | Threshold                                                          | Enforcement             |
| ------------------------------ | ------------------------------------------------------------------ | ----------------------- |
| Total directors                | 3 to 13 (configurable via `board_min_size` / `board_max_size`) | Dashboard WARNING alert |
| Non-shareholder director ratio | ≤ 33% (configurable via `non_shareholder_director_max_ratio`)   | Dashboard WARNING alert |

Data Source: `BoardMember` model with `active()` and `directors()` scopes. Distinguishes `shareholder_director` vs `non_shareholder_director` member types.

*Art. 381 — External Audit*

| Check                          | Threshold                            | Enforcement             |
| ------------------------------ | ------------------------------------ | ----------------------- |
| Time since last external audit | ≤ 16 months                         | Dashboard WARNING alert |
| No audit on record             | Missing `last_external_audit_date` | Dashboard WARNING alert |

Data Source: `SettingCompany.last_external_audit_date`. Share Companies are classified as Public Interest Entities per AABE directive.

**3.6 Dividend Withholding Tax — Income Tax Proclamation No. 979/2016**

| Parameter           | Value                                      | Source                                                      |
| ------------------- | ------------------------------------------ | ----------------------------------------------------------- |
| Standard WHT rate   | 10% on gross dividend                      | Art. 53, Proclamation 979/2016                              |
| WHT per shareholder | `gross_amount × withholding_tax_rate`   | `DividendPaymentService::calculateShareholderDividends()` |
| Net payout          | `gross_amount − withholding_tax_amount` | Stored on each `DividendPayment` record                   |

Journal Entries:

- Declaration: `DR Retained Earnings (33000), CR Dividends Payable (21500) [net], CR WHT Payable (21240) [tax]`
- Remittance: `DR WHT Payable (21240), CR Cash/Bank`

**3.7 Undistributed Profit Tax — Income Tax Proclamation No. 979/2016, Art. 61**

| Field                           | Description                                                                   |
| ------------------------------- | ----------------------------------------------------------------------------- |
| `origin_tax_year`             | Year the profit was earned                                                    |
| `undistributed_profit_amount` | Amount of profit not distributed as dividend                                  |
| `deadline_date`               | Deadline for distribution or reinvestment                                     |
| `tax_rate` / `tax_amount`   | Applicable tax rate and computed obligation                                   |
| `status`                      | `open` → `assessed` → `paid` / `reinvested_at` / `distributed_at` |

Model: `UndistributedProfitAssessment` with `scopeOpen()` and `scopeExpired()` for tracking overdue obligations.

**3.8 Eight-Point Dividend Validation Gate**
Source: `DividendValidationService::validateDeclaration()` — All 8 checks must pass before a dividend can be approved:

| # | Validation                                                                            | Statutory Basis                               | Fail Action |
| - | ------------------------------------------------------------------------------------- | --------------------------------------------- | ----------- |
| 1 | Retained Earnings must be positive                                                    | Commercial Code 2021 — realized profits only | Hard block  |
| 2 | Dividend ≤ Distributable Profit (RE − Legal Reserve − Revaluation Reserve − AOCI) | Commercial Code 2021                          | Hard block  |
| 3 | Legal Reserve obligation fulfilled (account 34600 ≥ 10% of account 34000)            | Art. 452, Commercial Code 2021                | Hard block  |
| 4 | Solvency Test passes (Total Assets − Dividend ≥ Total Liabilities)                  | Commercial Code 2021                          | Hard block  |
| 5 | No Unpaid Share Capital exists (issuances without `bank_account_id`)                | Commercial Code 2021                          | Hard block  |
| 6 | Total Equity ≥ Minimum Capital Floor (ETB 15K PLC / ETB 50K SC)                      | Art. 510 / Art. 304, Commercial Code 2021     | Hard block  |
| 7 | Accumulated losses < 50% of share capital (capital adequacy)                          | Art. 473, Commercial Code 2021                | Hard block  |
| 8 | No outstanding Tax Obligations with `status='due'` past due date                    | Income Tax Proclamation No. 979/2016          | Hard block  |

Override Path: If all checks fail but board/management authorizes:

```php
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

#### 4.10.4 Chart of Accounts — Equity Ledger Map

**PLC/SC Equity Accounts** (auto-created by `ChartOfAccountsService::createDefaultAccounts()`)

| Code  | Account Name                                  | Normal Balance        | Detail Type                 | System | Condition     |
| ----- | --------------------------------------------- | --------------------- | --------------------------- | ------ | ------------- |
| 34000 | Common Stock                                  | Credit                | Common Stock                | No     | PLC/SC/SM-PLC |
| 34100 | Preferred Stock                               | Credit                | Preferred Stock             | No     | PLC/SC/SM-PLC |
| 34200 | Share Premium – Common                       | Credit                | Paid-in Capital or Surplus  | No     | PLC/SC/SM-PLC |
| 34210 | Share Premium – Preferred                    | Credit                | Paid-in Capital or Surplus  | No     | PLC/SC/SM-PLC |
| 34300 | Additional Paid-in Capital (APIC)             | Credit                | Paid-in Capital or Surplus  | No     | PLC/SC/SM-PLC |
| 34500 | Treasury Stock                                | Debit (Contra-Equity) | Treasury Stock              | No     | PLC/SC/SM-PLC |
| 34800 | Accumulated Other Comprehensive Income (AOCI) | Credit                | AOCI                        | No     | PLC/SC/SM-PLC |
| 34700 | Dividend Distribution                         | Credit                | Dividends Paid / Owner Draw | No     | PLC/SC/SM-PLC |
| 34600 | Legal Reserve                                 | Credit                | Retained Earnings           | No     | PLC/SC/SM-PLC |
| 33000 | Retained Earnings                             | Credit                | Retained Earnings           | ✅ Yes | All types     |
| 39000 | Income Summary                                | Credit                | Income Summary              | ✅ Yes | All types     |
| 39900 | Opening Balance Equity                        | Credit                | Opening Balance Equity      | ✅ Yes | All types     |
| 21500 | Dividends Payable                             | Credit                | Dividends Payable           | ✅ Yes | PLC/SC/SM-PLC |
| 21240 | Dividend Withholding Tax Payable              | Credit                | Dividend WHT Payable        | ✅ Yes | PLC/SC/SM-PLC |

**Sole Proprietorship Equity Accounts**

| Code  | Account Name           | Normal Balance        | Condition |
| ----- | ---------------------- | --------------------- | --------- |
| 31000 | Owner's Equity/Capital | Credit                | SP only   |
| 32000 | Owner's Drawings       | Debit (Contra-Equity) | SP only   |

**Configurable Account Resolution**
Source: `EquityConfigurationService::account()` — three-tier fallback:

1. `SettingCompany` field (e.g., `equity_share_capital_account_id`)
2. `Account::where('account_code', defaultCode)->where('company_id', companyId)->first()`
3. Exception if not found

| Role Key                    | Settings Field                          | Default Code |
| --------------------------- | --------------------------------------- | ------------ |
| `share_capital`           | `equity_share_capital_account_id`     | 34000        |
| `legal_reserve`           | `equity_legal_reserve_account_id`     | 34600        |
| `retained_earnings`       | `equity_retained_earnings_account_id` | 33000        |
| `treasury`                | `equity_treasury_account_id`          | 34500        |
| `dividends_payable`       | `equity_dividends_payable_account_id` | 21500        |
| `withholding_tax_payable` | `equity_withholding_tax_account_id`   | 21240        |

**Configurable Compliance Thresholds**
Source: `EquityConfigurationService::getThresholds()`

| Parameter                      | PLC Default | SC Default | Settings Override              | Statutory Basis     |
| ------------------------------ | ----------- | ---------- | ------------------------------ | ------------------- |
| `minimum_capital`            | ETB 15,000  | ETB 50,000 | `minimum_capital`            | Art. 510 / Art. 304 |
| `capital_adequacy_threshold` | 50%         | 50%        | `capital_adequacy_threshold` | Art. 473            |
| `legal_reserve_rate`         | 5%          | 5%         | `legal_reserve_rate`         | Art. 452            |
| `legal_reserve_cap_rate`     | 10%         | 10%        | `legal_reserve_cap_rate`     | Art. 452            |
| `minimum_par_value`          | ETB 100     | ETB 100    | `minimum_par_value`          | Art. 452            |

#### 4.10.5 Data Model Topology

**Core Models & Relationships**

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
├── transaction_type: initial_issue|issuance|buyback|transfer|stock_split|reverse_split|conversion|bonus_issue|treasury_reissue
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
├── equity_component: common_stock|preferred_stock|share_premium|retained_earnings|treasury_stock|aoci
├── movement_type: beginning_balance|net_income|net_loss|other_comprehensive_income|dividends|share_issuance|share_buyback|share_split|prior_period_adjustment
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

#### 4.10.6 Service Layer Architecture

**Dependency Graph**

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

**Service Responsibilities**

| Service                        | Responsibility                                                                                                   |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------- |
| `LedgerPostingService`       | Entry router — dispatches `ShareTransaction` to correct method by `transaction_type`                        |
| `ShareTransactionService`    | Core:`issueShares()`, `transferShares()`, `buybackShares()`, `issueBonusShares()`, `declareDividend()` |
| `TreasuryStockService`       | Treasury buyback/reissue with Weighted Average Cost gain/loss logic                                              |
| `DividendPaymentService`     | Approve declaration → Calculate shareholders → Execute batch payment → Remit WHT                              |
| `DividendValidationService`  | 8-check statutory validation gate (Section 3.8)                                                                  |
| `EquityComplianceService`    | 7 persistent dashboard compliance alerts (Section 14)                                                            |
| `EquityConfigurationService` | Threshold/account resolution with request-level caching                                                          |
| `ComplianceOverrideService`  | Records authorized compliance overrides with full audit trail                                                    |
| `ShareholdersEquityService`  | Generates Statement of Changes in Equity report matrix                                                           |
| `RetainedEarningsService`    | Retained Earnings statement (PLC) or Owner's Equity statement (SP)                                               |
| `PeriodCloseService`         | Revenue/Expense → Income Summary → Retained Earnings → Legal Reserve (Art. 452)                               |
| `OwnerCapitalService`        | SP only: capital contributions, drawings, year-end close                                                         |

#### 4.10.7 Share Class Configuration

**Navigation:** Equity → Share Classes

**Data Requirements**

| Field                          | Validation                                      | Statutory Basis              |
| ------------------------------ | ----------------------------------------------- | ---------------------------- |
| `class_name`                 | Required (e.g., "Ordinary Shares")              | —                           |
| `class_type`                 | `common` or `preferred`                     | —                           |
| `par_value`                  | ≥ ETB 100 (configurable `minimum_par_value`) | Art. 452, Comm. Code 2021    |
| `authorized_shares`          | > 0 (maximum issuable ceiling)                  | —                           |
| `share_capital_account_id`   | FK → Account (e.g., 34000)                     | Required for journal entries |
| `share_premium_account_id`   | FK → Account (e.g., 34200)                     | Required for premium booking |
| `treasury_shares_account_id` | FK → Account (e.g., 34500)                     | Required for buyback/reissue |

**Integrity Check**
`ShareClass::verifyConsistency()` — compares `issued_shares` counter against `ShareLedger SUM(credit_shares - debit_shares)`. Called before every issuance and buyback.

#### 4.10.8 Shareholder Registration

**Navigation:** Equity → Shareholders

**Data Captured**

| Field                             | Purpose                                                            |
| --------------------------------- | ------------------------------------------------------------------ |
| `shareholder_name`              | Legal name (required)                                              |
| `shareholder_type`              | `individual`, `institutional`, or `corporate`                |
| `tax_id`                        | TIN — essential for WHT reporting (Proc. 979/2016)                |
| `email`, `phone`, `address` | Contact information                                                |
| `is_active`                     | Default `true`; inactive shareholders excluded from new issuance |

**Single-Member PLC Enforcement**
When `Company::isSingleMemberPLC()` is `true`, `ShareTransactionService::validateShareIssuance()` enforces exactly 1 shareholder by querying both `ShareLedger` and posted `ShareTransaction` records for any other shareholder.

#### 4.10.9 Share Issuance (Primary Market)

**Navigation:** Equity → Issue Shares (3-step wizard)

**User Flow**

```
Step 1 (Details): Shareholder, Share Class, Type (Cash/Bonus), Shares, Price, Bank Account
Step 2 (Preview): Live journal entry debit/credit rendering
Step 3 (Confirm): Summary review → Submit
```

**Processing Pipeline**

```
IssueShares.processIssuance(data)
├── DB::beginTransaction()
├── Validate: authorized_shares - issued_shares >= requested
├── Board President approval required (strategic authority)
├── Financial Manager executes posting (tactical authority)
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

**Cash Issuance — `issueShares()`**
Pre-Checks (Art. 452 enforcement):

- `ShareClass.verifyConsistency()` — integrity gate
- Available authorized shares check
- Par value ≥ ETB 100 (minimum par value)
- Issue price ≥ par value (no below-par issuance)
- Single-Member PLC: single shareholder enforcement

Atomic Transaction:

1. `ShareClass.increment('issued_shares', qty)`
2. JournalEntry: "Share Issuance"
   - `DR Cash/Bank .................. total_amount`
   - `CR Share Capital (34000) ...... par_value × shares`
   - `CR Share Premium (34200) ...... premium (price − par) × shares [if any]`
3. `EquityMovement::create(equity_component: common_stock|preferred_stock, movement_type: share_issuance)`
4. `ShareLedger::create(credit_shares: qty)`
5. `ShareTransaction.status → 'posted'`

**Bonus Issuance — `issueBonusShares()`**
Capitalises existing reserves into share capital (no cash movement):
Journal Entry:

- `DR Retained Earnings (33000) ... par_value × shares`
- `CR Share Capital (34000) ....... par_value × shares`

#### 4.10.10 Share Transfer (Secondary Market)

Source: `ShareTransactionService::transferShares()`

**Key Characteristics**

- No GL impact — no journal entry created
- Share Ledger only — updates cap table ownership
- Company total capital unchanged

**Processing**

1. Validate seller balance: `ShareLedger SUM(credit - debit) >= qty`
2. `ShareLedger::create(debit_shares: qty)` → seller (OUT)
3. `ShareLedger::create(credit_shares: qty)` → buyer (IN)
4. `ShareTransaction.status → 'posted'`

**Approval Workflow**
`ShareTransfer` model supports board approval: `from_shareholder_id`, `to_shareholder_id`, `approval_status`, `approved_by`, `board_resolution_number`

#### 4.10.11 Treasury Stock (Buyback & Reissue)

**Navigation:** Equity → Treasury Stock (tab-based: Buyback | Reissue)

**11.1 Share Buyback**
Source: `TreasuryStockService::processBuyback()`

```
Pre-Checks:
  ├── ShareClass.verifyConsistency()
  └── PostingGuardService.assertCanPost()

Atomic Transaction:
  1. TreasuryStock::create(type: 'buyback', status: 'posted')
  2. ShareTransaction::create(type: 'buyback', number_of_shares: -qty)
  3. ShareLedger::create(debit_shares: qty) → removes from seller
  4. JournalEntry:
     ├── DR Treasury Stock (34500) ... total_cost
     └── CR Cash/Bank ............... total_cost
  5. EquityMovement::create(equity_component: treasury_stock, movement_type: share_buyback, debit_amount: total_cost)
```

**11.2 Treasury Reissue**
Source: `TreasuryStockService::processReissue()`
Cost Calculation (Weighted Average Cost method):

- `WAC = (total_buyback_cost − total_reissued_cost) / remaining_shares`
- `cost_basis = shares × WAC`
- `proceeds = shares × reissue_price`
- `gain_loss = proceeds − cost_basis`

Atomic Transaction:

1. `TreasuryStock::create(type: 'reissue')`
2. `ShareTransaction::create(type: 'treasury_reissue')`
3. `ShareLedger::create(credit_shares: qty)` → gives shares to buyer
4. JournalEntry:
   - `DR Cash/Bank ............... proceeds`
   - `CR Treasury Stock (34500) .. cost_basis`
   - `CR Share Premium (34200) ... gain (if gain > 0)`
   - `DR Retained Earnings (33000) or Share Premium .. |loss| (if loss < 0)`
5. `EquityMovement::create(equity_component: treasury_stock)`

**Gain/Loss Routing Rules**

| Scenario                       | Debit                           | Credit                                |
| ------------------------------ | ------------------------------- | ------------------------------------- |
| Reissue at gain                | Cash                            | Treasury Stock + Share Premium (gain) |
| Reissue at loss (RE available) | Cash + Retained Earnings (loss) | Treasury Stock                        |
| Reissue at loss (no RE)        | Cash + Share Premium (loss)     | Treasury Stock                        |

**Live Financial Preview**
The `ManageTreasuryStock` Reissue form includes a real-time financial analysis section showing Weighted Average Cost per share, Total reissue proceeds, and Estimated Gain/Loss with color-coded display.

#### 4.10.12 Dividend Lifecycle

**State Machine**

```
declared → [approveDeclaration()] → approved → [calculateShareholderDividends()]
                                                     ↓
                                   → [executeBatchPayment()] → paid
                                                     ↓
                                   → [remitWithholdingTax()] → (WHT cleared)
```

**12.1 Declaration & Approval**
Source: `DividendPaymentService::approveDeclaration()`
Pre-Approval: All 8 statutory checks from `DividendValidationService::validateDeclaration()` must pass. **Board President approval required for declaration.**
Processing:

1. Sync `shares_outstanding` from `ShareLedger` at `record_date`
2. Recalculate `total_amount = dividend_per_share × actual_shares`
   Journal Entry:

- `DR Retained Earnings (33000) ......... total_amount (gross)`
- `CR Dividends Payable (21500) ......... net_amount (total − WHT)`
- `CR WHT Payable (21240) ............... withholding_tax_amount`

3. `EquityMovement::create(equity_component: retained_earnings, movement_type: dividends, debit_amount: total_amount)`
4. `Dividend.status → 'approved'`

**12.2 Shareholder Calculation**
Source: `DividendPaymentService::calculateShareholderDividends()`

1. Query `ShareLedger` at `record_date`: `SUM(credit_shares − debit_shares) WHERE date ≤ record_date GROUP BY shareholder_id`
2. For each shareholder with shares > 0:
   - `gross_amount = shares_held × dividend_per_share`
   - `withholding_tax = gross × WHT_rate (10%, Proc. 979/2016)`
   - `net_amount = gross − withholding_tax`
   - `DividendPayment::create(status: 'pending')`

**12.3 Batch Payment**
Source: `DividendPaymentService::executeBatchPayment()`
Journal Entry:

- For each shareholder: `DR Dividends Payable (21500) ... net_amount`
- `CR Cash/Bank ................... SUM(all net_amounts)`
  Updates: Each `DividendPayment.status → 'paid'`, `Dividend.status → 'paid'`

**12.4 WHT Remittance**
Source: `DividendPaymentService::remitWithholdingTax()`
Journal Entry:

- `DR WHT Payable (21240) ... total_tax`
- `CR Cash/Bank ............ total_tax`

#### 4.10.13 Period Close & Legal Reserve Appropriation

Source: `PeriodCloseService`

**Period Close Pipeline**

1. `validatePeriodCloseable()` → Checks locks, pending JEs, sequential enforcement, policy versions, account existence
2. `calculateNetIncomeForPeriod()` → Revenue(credit−debit) − Expenses(debit−credit)
3. `createClosingEntries()` → Close Revenue/Expenses → Income Summary → Retained Earnings
4. `RetainedEarningsService.recordMovement(net_income | net_loss)`
5. `EquityMovement::create(movement_type: net_income | net_loss)`
6. `updateFiscalYearRetainedEarnings()` → closing_retained_earnings = opening + YTD net income

**Legal Reserve — Automated Year-End Appropriation (Art. 452)**
Triggered only at fiscal year-end close:

```
applyLegalReserveAppropriation():
  ├── Condition: annual_net_income > 0
  ├── Condition: legal_reserve_balance < target (10% of share_capital)
  ├── Transfer = min(income × 5%, target − current_reserve)
  └── Journal Entry:
      ├── DR Retained Earnings (33000) ... transfer
      └── CR Legal Reserve (34600) ....... transfer
```

Post-Close: `runEquityComplianceChecks()` → Re-evaluates all 7 compliance alerts after the close.

#### 4.10.14 Equity Compliance Dashboard Alerts

Source: `EquityComplianceService` — 7 persistent compliance checks, stored as `EquityComplianceAlert` records.

**Alert System Architecture**

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

**Complete Alert Catalog**
*(Alerts 1-7 cover Minimum Capital, Capital Adequacy/EGM, Legal Reserve, SM-PLC Nominee, SC Shareholder Count, SC Board Composition, SC External Audit. All auto-resolve when conditions clear, persist via `updateOrCreate`, and block dividends where applicable.)*

#### 4.10.15 Statement of Changes in Equity

Source: `ShareholdersEquityService::generateStatement()`
**Report Matrix Structure**
Columns: Common Stock (34000), Preferred Stock (34100), Share Premium (34200/34210), Retained Earnings (33000), Treasury Stock (34500), AOCI (34800), Total
Rows: Beginning Balance, Share Issuance, Net Income/Loss, OCI, Share Buyback, Dividends, Share Split, Prior Period Adjustments, Ending Balance.

#### 4.10.16 Retained Earnings Statement

Source: `RetainedEarningsService`
**PLC Flow:** Opening Balance + Net Income − Dividends ± Adjustments ± Other = Ending Balance
**Sole Proprietorship Flow:** Opening Balance + Net Income + Contributions − Drawings = Ending Balance

#### 4.10.17 Filament UI Surface Map

**Navigation Group:** "Equity" (visible only when `Company::isShareBased()`)

| Page/Resource      | Type                 | Visibility Gate                                      | Description                                |
| ------------------ | -------------------- | ---------------------------------------------------- | ------------------------------------------ |
| Share Classes      | Resource (CRUD)      | `isShareBased()`                                   | Define share classes with par values       |
| Shareholders       | Resource (CRUD)      | `isShareBased()`                                   | Register individuals/entities with TIN     |
| Share Transactions | Resource (CRUD)      | `isShareBased()`                                   | View all transaction history               |
| Issue Shares       | Page (3-Step Wizard) | `isShareBased()` + `equity.share_issuance.index` | Cash & Bonus issuance with journal preview |
| Treasury Stock     | Page (Tab Form)      | `isShareBased()` + `equity.treasury_stock.index` | Buyback & Reissue with WAC analysis        |
| Dividends          | Resource (CRUD)      | `isShareBased()`                                   | Declare, approve, manage dividends         |
| Owner's Capital    | Resource (CRUD)      | SP only (not share-based)                            | Capital contributions & drawings           |

**Dashboard Widgets:** EquitySummaryOverview, EquityReportsNavigationWidget, DividendByShareClassChart, DividendPaymentStats, DividendPaymentStatusChart, TaxRemittanceTracker.

#### 4.10.18 Security & Permission Model

**Permission Keys (Filament Shield)**

| Permission                      | Controls                       |
| ------------------------------- | ------------------------------ |
| `equity.share_issuance.index` | Issue Shares page access       |
| `equity.treasury_stock.index` | Treasury Stock page access     |
| `equity.dividend.index`       | Dividend pages access          |
| `equity.shareholder.index`    | Shareholder resource + Reports |

**Posting Controls**

- Period Lock: `PostingGuardService.assertCanPost(companyId, date, context)`
- Fiscal Period: Date must fall within an open/unlocked `AccountingPeriod`
- Sequential Close: Cannot post to a closed/locked period
- Compliance Override: `ComplianceOverrideService.record()` — audit-logged bypass

**Compliance Override Audit Trail**
Every override records: `company_id`, `context`, `subject_id`, `subject_type`, `reason`, `violations` (JSON), `requested_by_user_id`, `approved_by_user_id`.

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

**Credit Invoice Control:** If invoice is credit, revenue recognition is deferred until payment collection OR Manager-approved credit terms are met. Pharmacy dispensing blocked for overdue credit patients unless Manager overrides.

### 5.2 Inventory Cost Flow (FIFO)

```
Supplier Delivery → Goods Receipt (Inventory ↑ @ actual cost) → Batch Recorded (batch_id, qty, unit_cost, exp) → Dispensing Event (FIFO selection) → COGS Calculated = qty × batch unit_cost → GL Entry: Debit COGS, Credit Inventory
```

### 5.3 Payroll Expense Flow

```
Payroll Period Opened → HR/Accountant enters variable inputs → System computes Payroll Sheet → Financial Manager reviews & approves → Payroll Posted (period closed) → Auto GL Entry: Debit Salaries Expense, Credit Tax/Pension Payable, Credit Bank (Net Pay)
```

**Added Step:** After payroll posting, statutory liabilities (PAYE, Pension, SHI) await discharge via §4.8.6 Remittance Workflow.

### 5.4 Equity Transaction Flow

```
Shareholder Approved Matrix → Share Issuance Created → Board President Approves → Financial Manager Executes Posting → Journal Posted: Debit Cash, Credit Share Capital/Premium → Share Ledger Updates (Cap Table +1) → EquityComplianceService.runAll()
```

### 5.5 Dividend Payment Flow

```
Board of Directors → Declaration (Subject to 8-Point Gate) → Board President Approval → Journal Posted: Debit Retained Earnings, Credit Div Pay, Credit WHT Pay (21240) → Batch Shareholder Payouts Generated → Bank Payment clears payable → WHT Remittance clears 21240
```

---

## 6. Reporting & Executive Oversight Workflows

### 6.1 Dashboard Hierarchy

#### 6.1.1 Board President — Executive Overview

**Focus:** Governance, strategic financial health, policy compliance
**Widgets:** KPI Overview, Monthly/quarterly trends, Audit trail exceptions, Pending Board-level approvals (>ETB 50,000 expenses, write-offs, equity events, fiscal year changes)

#### 6.1.2 Financial Manager — Financial Management

**Focus:** Tactical financial control, cash management, payroll oversight, month-end integrity, statutory remittance status
**Widgets:** Live Cash Position, Pending Approvals Queue, Monthly Close Status, Payroll Next Run, AR/AP Exception Report, Statutory Remittance Tracker, Equity Compliance Alerts
**Quick Actions:** Approve Payroll, Review Bank Reconciliation, Open/Close Fiscal Period, Execute Statutory Remittance, Generate Management Pack

#### 6.1.3 Manager — Operations Dashboard

**Focus:** Clinic operations, staff oversight, inventory, patient flow
**Widgets:** Today's Revenue, Pending Operational Approvals, Staff Attendance, Pharmacy Queue + Inventory Alerts, Cash Register Status

#### 6.1.4 Accountant — Accounting Dashboard

**Focus:** Transaction processing, reconciliation, bookkeeping accuracy, period-end close
**Widgets:** Unreconciled Items, Pending Journal Entries, Payroll Input Status, VAT/Tax Summary, Period Close Checklist, Suspense & Clearing Account (11450) items

#### 6.1.5 HR — HR Operations Dashboard

**Focus:** Workforce management, attendance compliance, payroll preparation
**Widgets:** Today's Attendance, Pending Leave Requests, Payroll Input Deadline, Employee Count by Status, Contract Expiry Alerts

#### 6.1.6 Receptionist — Cashier Dashboard

**Focus:** Fast patient service, accurate cash handling
**Widgets:** Quick POS Launch, Today's Transactions, Pending Overdue Collections, Cash Register (Opening/Current/Expected Closing), Credit Note Initiation

#### 6.1.7 Pharmacist — Pharmacy Dashboard

**Focus:** Dispensing accuracy, inventory health, OTC sales
**Widgets:** Pending Prescriptions Queue, Today's Dispensing Count + Pharmacy POS Sales, Stock Alerts, Quick Medicine Search, Pharmacy POS Quick Launch

### 6.2 Report Generation Workflows

**Report Parameter Entry & Export Approval:**

1. User selects report → System presents parameter screen
2. User previews report → System logs view access
3. For **sensitive reports** (payroll detail, equity register, patient data): **Manager or Financial Manager approval required** before export to PDF/Excel
4. Export logged with user, timestamp, data range, IP; stored in audit trail
5. Scheduled reports distributed via in-app notification; Executive reports auto-generated on month-end close

**Financial Reports:** Income Statement, Balance Sheet, Cash Flow, Trial Balance, General Ledger Detail.
**Pharmacy Reports:** Dispensing Summary, Stock Valuation (FIFO), Expiry Report, Fast/Slow Moving Items.
**Sales & Revenue:** Daily Sales Summary, Revenue by Category, Payment Method Analysis, Outstanding Debtors.
**Executive Dashboards — KPI Taxonomy:** Revenue per Patient, Average Transaction Value, Inventory Turnover, DSO, Cash Conversion Cycle, Payroll to Revenue Ratio, Gross/Operating Margin, Statutory Compliance Rate, Patient Satisfaction Index.

---

## 7. Approval & Verification Architecture

### 7.1 Approval Matrix (v2.0 — Corrected)

| Transaction Type     | Threshold             | Initiator            | Approver                                         | Execution / Posting        | Notification     | Escalation if Pending > |
| -------------------- | --------------------- | -------------------- | ------------------------------------------------ | -------------------------- | ---------------- | ----------------------- |
| Price Override       | Any amount            | Receptionist         | Manager (in-session or post-hoc)                 | System auto-logs           | Auto + Audit log | N/A                     |
| Credit Invoice       | ≤ETB 5,000           | Receptionist         | Auto-approved (Manager notified)                 | System                     | Auto             | 24 hrs → Manager       |
| Credit Invoice       | >ETB 5,000            | Receptionist         | Manager                                          | System                     | In-app + Email   | 24 hrs → Fin Mgr       |
| Patient Refund       | Any amount            | Receptionist         | Manager                                          | Cashier / Accountant       | In-app + Email   | Same day → Fin Mgr     |
| Expense Record       | ≤ETB 5,000           | Accountant           | Auto-approved                                    | Accountant                 | None             | N/A                     |
| Expense Record       | ≤ETB 5,000           | Manager              | **Financial Manager** (no self-approval)   | Accountant                 | In-app           | 48 hrs → Board         |
| Expense Approval     | ETB 5,001 – 50,000   | Accountant / Manager | Financial Manager                                | Accountant                 | In-app + Email   | 48 hrs → Board         |
| Expense Approval     | >ETB 50,000           | Accountant / Manager | Board President                                  | Financial Manager          | In-app + Email   | 72 hrs → EGM           |
| Stock Adjustment     | Any value             | Pharmacist (request) | Manager (all)                                    | Pharmacist (post-approval) | In-app           | Same day                |
| Purchase Order       | ≤ETB 25,000          | Pharmacist / Manager | Manager                                          | System                     | In-app           | 48 hrs → Fin Mgr       |
| Purchase Order       | ETB 25,001 – 100,000 | Pharmacist / Manager | Financial Manager                                | System                     | In-app + Email   | 72 hrs → Board         |
| Purchase Order       | >ETB 100,000          | Pharmacist / Manager | Board President                                  | System                     | In-app + Email   | 1 week                  |
| Journal Entry        | ≤ETB 5,000           | Accountant           | Auto-posted                                      | Accountant                 | None             | N/A                     |
| Journal Entry        | ETB 5,001 – 20,000   | Accountant           | Manager (cannot be same person)                  | Accountant                 | In-app           | 48 hrs → Fin Mgr       |
| Journal Entry        | ETB 20,001 – 100,000 | Accountant           | Financial Manager                                | Accountant                 | In-app + Email   | 72 hrs → Board         |
| Journal Entry        | >ETB 100,000          | Accountant           | Board President                                  | Financial Manager          | In-app + Email   | 1 week                  |
| Payroll Run          | Any amount            | HR / Accountant      | Financial Manager                                | System (auto-GL)           | In-app + Email   | 48 hrs before pay-date  |
| Invoice Write-off    | ≤ETB 5,000           | Accountant           | Manager + Financial Manager                      | Accountant                 | In-app + Email   | 1 week                  |
| Invoice Write-off    | >ETB 5,000            | Accountant           | Board President                                  | Accountant                 | In-app + Email   | 1 week                  |
| Share Issuance       | Any amount            | Financial Manager    | **Board President**                        | Financial Manager          | In-app + Email   | 2 weeks                 |
| Dividend Declaration | Any amount            | Financial Manager    | **Board President**                        | Financial Manager          | In-app + Email   | 2 weeks                 |
| Dividend Payment     | Any amount            | Financial Manager    | Financial Manager (dual-control with Accountant) | System                     | In-app + Email   | 1 week                  |
| Compliance Override  | N/A                   | Financial Manager    | **Board President**                        | System                     | In-app + Email   | Immediate               |
| Fiscal Period Close  | N/A                   | Accountant           | Financial Manager + Board President              | System                     | In-app + Email   | 5 days into new month   |
| Threshold Change     | Any                   | Manager / Fin Mgr    | **Board President**                        | System                     | In-app + Email   | 1 week                  |

**Escalation & Delegation Rules:**

- If primary approver is on leave > 3 days, delegated authority auto-activates (configured in User Management by Board President)
- Emergency break-glass: Financial Manager can execute critical payments (statutory, payroll) with post-hoc Board President ratification within 72 hours; full audit trail logged as `ComplianceOverride`

### 7.2 Verification Procedures

**End-of-Day (Receptionist + Pharmacist):**

1. Count physical cash + card slips + mobile money confirmations
2. System generates "Expected Cash" report from POS / Pharmacy POS
3. Receptionist/Pharmacist enters actual count; system calculates variance
4. **If variance ≠ 0:** explanation required; **Manager notified immediately**; day cannot be closed until Manager reviews
5. Variance posted to `93300` Cash Shortage/Overage with narrative
6. Cash deposit slip prepared; Accountant receives next day

**End-of-Week (Manager):**

1. Review all pending operational approvals
2. Verify stock adjustments and small POs
3. Check exception reports (price overrides, refunds, credit notes)
4. Review staff attendance anomalies from HR
5. **Sample Pharmacy POS transactions** for pricing and stock accuracy

**End-of-Month (Accountant + Financial Manager):**

1. **Days 1–2 (Accountant):** Bank reconciliation completion, AP/AR aging review, inventory physical count vs. system, journal entry review, payroll variable input completion, statutory remittance batch preparation.
2. **Days 3–4 (Financial Manager):** Reviews trial balance, approves payroll run, reviews variance analysis, verifies cash position & liquidity, approves statutory remittance batches, approves period close request.
3. **Day 5 (Board President):** Receives executive summary P&L, Balance Sheet, Cash Flow. Reviews strategic KPI dashboard. Approves any Board-level items flagged by Financial Manager.

---

## 8. Audit & Compliance Architecture

### 8.1 Audit Trail Coverage

**Spatie Laravel-Activitylog captures:**

- Who created/modified/deleted what record, when, from which IP
- Before/after values for financial records
- Login/logout events, failed login attempts
- **View access logging** for sensitive modules (patient records, payroll detail, equity register, board governance)

**Financial Audit Trail:** Every invoice change, journal entry, inventory movement, price change, payroll change, and export logged with full context.
**Inventory Audit Trail:** Batch-level tracking, expiry tracking, stock adjustment with mandatory reason code.
**HR Audit Trail:** Employee record changes, attendance corrections, leave approval workflow, payroll access logs.
**Equity Audit Trail:** Every share transaction, dividend declaration/payment, compliance override, and EGM outcome securely logged.

### 8.2 Data Retention & Backup

**Backup Schedule (Spatie Laravel-Backup):**

- **Daily:** Database dump to S3-compatible storage (retain 14 days)
- **Weekly:** Full backup including uploads (retain 8 weeks)
- **Monthly:** Archive backup (retain 12 months)
- **On-demand:** Pre-update backup triggered by Manager

**Data Export:** Accountant, Financial Manager, HR, and Board President can export relevant reports. All exports logged with user, timestamp, data range, and IP.

---

## 9. System Navigation & UX Workflow

### 9.1 Role-Based Landing Pages

```
IF role = Receptionist → Cashier Dashboard (quick POS access)
IF role = Pharmacist   → Pharmacy Dashboard (pending prescriptions + Pharmacy POS)
IF role = HR           → HR Operations (attendance + pending leave + payroll input status)
IF role = Accountant   → Accounting Dashboard (unreconciled items + payroll input + period close)
IF role = Manager      → Manager Operations (pending approvals + alerts + EOD verification)
IF role = Financial Manager → Financial Management (cash position + approvals + payroll + remittances)
IF role = Board President → Executive Overview (KPIs + trends + governance approvals)
```

### 9.2 Menu Access Control

**Dynamic Menu Rendering:** Menu items filtered by `Spatie Permission` gates. Sub-menu items hidden if no access. "Quick Actions" buttons bypass navigation.
**Cross-Module Navigation:** Contextual deep-linking (e.g., Patient Directory → Create Invoice, Stock Alert → Create PO, Attendance → Add Payroll Input).

### 9.3 Notification Architecture (Filament Database Notifications)

**Real-time Notifications:** Role-specific alerts (prescriptions, approvals, deadlines, cash variances, compliance alerts).
**Notification Actions:** Click → navigate directly. Approve/reject from panel. Mark as read; unread count on bell icon.

---

## 10. Simplified Enterprise Workflow Structure

### 10.1 Daily Operational Rhythm

*(Visual workflow diagram preserved from original architecture: Receptionist/Pharmacist/HR/Accountant/Manager parallel tracks converging on Financial Manager oversight)*

### 10.2 Weekly Operational Rhythm

| Day                         | Receptionist            | Pharmacist                  | HR                                 | Accountant              | Manager                     | Financial Manager                                  |
| --------------------------- | ----------------------- | --------------------------- | ---------------------------------- | ----------------------- | --------------------------- | -------------------------------------------------- |
| **Monday**            | Normal ops              | Stock deep-check            | Attendance lock & payroll prep     | AP payment run          | Staff meeting, PO approvals | Review week-start cash position                    |
| **Tuesday–Thursday** | Normal ops              | Normal dispensing + OTC POS | Leave processing, timesheet review | Normal accounting       | Floor supervision           | Exception monitoring                               |
| **Friday**            | Normal ops              | Expiry review, reorder prep | Weekly attendance summary          | Week-end reconciliation | Weekly report review        | Review accumulated approvals; management pack prep |
| **Saturday**          | (If open) Reduced hours | (If open) Reduced hours     | (If open) Reduced hours            | Backup verification     | (If open) Supervision       | (If open) Oversight                                |

### 10.3 Monthly/Quarterly Rhythm

**Month-End (Days 1–5):** Accountant completes reconciliations → HR finalizes inputs → Financial Manager reviews trial balance, approves payroll, closes period, generates management reports → Board President reviews executive pack.
**Quarterly:** Supplier performance review, pricing review, inventory turnover analysis, backup verification, KPI target review.
**Annually:** Fiscal year close, full physical count, contract/payroll audit, audit preparation, new fiscal year setup, **Board President approves** new thresholds & policies.

---

## 11. Data Model Relationships

```
patients
    ├── invoices (one-to-many)
    │       ├── invoice_items (one-to-many)
    │       ├── payments (one-to-many)
    │       └── credit_notes (one-to-many)          ← NEW
    │             └── credit_note_items (one-to-many)
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
                            └── supplier_returns (one-to-many)   ← NEW

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
                    └── payroll_reversals (one-to-many)           ← NEW

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

egm_logs (one-to-many)                                 ← NEW
    └── linked to company

equity_compliance_alerts (one-to-many)
    └── linked to company

compliance_overrides (one-to-many)                     ← NEW
    └── linked to users (requested_by, approved_by)
```

---

## 12. Risk Mitigation & Business Rules

### 12.1 Financial Integrity Rules

- **No deletion of posted transactions** — only reversal entries with full audit trail
- **Immutable invoice after payment** — modifications require credit note + new invoice
- **Mandatory receipt reference** — every payment must have sequential receipt number
- **Dual control on cash** — Receptionist counts, Manager verifies, Accountant records deposit; variance posted to `93300`
- **Payroll immutability** — once posted, only reversible via approved contra-entry by Financial Manager
- **Reverse transaction governance** — Credit notes, refunds, write-offs, and payroll reversals require tiered approval equal to or greater than original transaction

### 12.2 Inventory Integrity Rules

- **Negative stock blocked** — system prevents dispensing below zero
- **FIFO enforced** — default batch selection; override requires documented reason and Manager notification
- **Expiry blocking** — expired items cannot be dispensed; must be adjusted out via approved write-off
- **Cost freeze** — once batch is received, cost is fixed; no retroactive changes
- **Adjustment control** — all value adjustments require Manager approval; quantity-only recounts auto-approved with reason

### 12.3 HR & Payroll Safeguards

- **Employee ID uniqueness** — national ID or system-generated unique identifier
- **Payroll period lock** — only one open period; prevents duplicate payments
- **Anomaly detection** — system flags payroll variance > 20% vs. prior month
- **Statutory compliance** — PAYE and pension calculations validated against configured tax tables
- **Statutory remittance enforcement** — Unremitted liabilities flagged after deadline; Financial Manager accountable

### 12.4 Operational Safeguards

- **Session timeout** — 30 minutes inactivity (configurable in Clinic Settings)
- **Concurrent edit detection** — warn if another user modifies same record
- **Daily backup reminder** — Manager dashboard shows backup status
- **Audit log immutability** — activity logs stored in separate table; no user can delete
- **Patient privacy** — consent captured at registration; role-based data scope enforced; view access logged

### 12.5 Equity & Statutory Safeguards

- **Minimum Capital** — hard alert if equity drops below ETB 15K (PLC) / 50K (SC)
- **Legal Reserve Automation** — 5% deducted from net income automatically until 10% threshold reached
- **Dividend Validation Gate** — 8-point automated check blocks non-compliant declarations; **Board President approval required**
- **Single-Member Verification** — automatically blocks secondary ownership for SM-PLC
- **Capital Adequacy Protection** — forces EGM logging if accumulated losses exceed 50% of share capital
- **EGM Logging** — Extraordinary General Meeting outcomes recorded with attendees, resolutions, and board resolution numbers

---

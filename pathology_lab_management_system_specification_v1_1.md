REVISION 1.1 --- REPORT-FIRST BUILD PRIORITY

PATHOLOGY LAB MANAGEMENT SYSTEM

Product Requirements Document (PRD) + Software Requirements
Specification (SRS)

Version 1.0 \| 10 November 2026 Foundational product specification ---
independent of future customer websites

# 1. Purpose and product boundary

This document defines a complete, configurable pathology/diagnostic
laboratory management system (LMS) for operating one laboratory or a
group of branches. It covers the operational product: patient
registration, orders, sample collection and tracking, laboratory
processing, results, validation, report delivery, billing, inventory,
staff, quality, administration and reporting.

Important product boundary: this specification does not include the
later customer-facing website or a separate branded website for each
laboratory buyer. Those can be separate products/integrations later. The
core LMS must expose secure APIs and configuration points so a future
website, mobile app, partner portal or other channel can connect without
rewriting the laboratory workflow.

## 1.1 Product goals

-   Run the complete lifecycle from patient enquiry/registration to
    final report delivery and payment reconciliation.

-   Reduce manual entry, duplicate records, sample mix-ups,
    turnaround-time delays and billing errors.

-   Maintain traceability: who did what, when, on which sample,
    instrument, result and report version.

-   Support single-branch and multi-branch operations with role-based
    access and branch-level controls.

-   Make test catalogues, reference ranges, workflows, report layouts,
    pricing and business rules configurable.

-   Provide reliable audit trails, backups, security and operational
    dashboards.

## 1.2 Non-goals for the first core release

-   Replacing a pathologist's clinical judgement or autonomously
    diagnosing patients.

-   Building a public marketing website, SEO pages, online booking
    website or customer-specific branded website.

-   Building a full hospital/EMR system, pharmacy, radiology PACS or
    insurance-claims clearinghouse.

-   Assuming every analyser supports automatic connectivity; instrument
    integration is phased and device-specific.

# 2. Users, roles and permissions

Use role-based access control (RBAC), with permissions scoped by branch,
department and record where appropriate. Do not rely only on hiding
buttons in the UI; enforce permissions on the server.

| Role \| Typical responsibilities \| Sensitive permissions \|

| --- \| --- \| --- \|

| Super Admin / Product Operator \| Platform configuration, tenant
  lifecycle, system health \| Cross-tenant access only under controlled
  support process; never routine access to patient data \|

| Lab Owner / Lab Admin \| Lab profile, branches, pricing, staff,
  settings, dashboards \| Business settings, user administration, report
  policy \|

| Lab Manager \| Daily operations, workload, escalations, approvals \|
  Operational overrides, refunds/discount approvals if delegated \|

| Receptionist / Front Desk \| Patient registration, appointments,
  orders, billing \| Patient demographics and order entry; no result
  validation \|

| Phlebotomist / Collector \| Collection queue, identity checks,
  collection status \| Collection and sample events; no result editing
  \|

| Lab Technician \| Accessioning, processing, result entry, QC \|
  Assigned tests/results; no final release unless authorised \|

| Pathologist / Authorised Signatory \| Review, validate, reject, amend
  and sign reports \| Clinical validation and report release \|

| Billing / Cashier \| Invoices, payments, refunds, dues \| Financial
  operations; no clinical result changes \|

| Inventory Manager \| Reagents, consumables, suppliers, stock movements
  \| Inventory and purchase workflows \|

| Quality Manager \| QC, calibration, non-conformances, audits \|
  Quality records and corrective actions \|

| Auditor / Read-only \| Read approved records and audit history \|
  Read-only access, restricted export \|

## 2.1 Permission design

-   Permissions should be granular (e.g. patient.read, order.create,
    sample.collect, result.enter, result.validate, report.release,
    invoice.refund).

-   Support branch scope, department scope, assigned-work scope and
    time-limited elevated access.

-   Require a reason for overrides, result amendments, report
    cancellation, deletion requests and refunds.

-   Use maker-checker approval for high-risk actions such as refunds,
    price overrides, report amendments and stock adjustments.

-   Never hard-delete clinical or financial records; use cancellation,
    correction or archival with audit history.

# 3. End-to-end workflow and module connections

The central workflow is an order-led process. Every operational event
should reference stable IDs for patient, visit/order, specimen, test,
result and report.

| Stage \| Primary module \| Creates / updates \| Next hand-off \|

| --- \| --- \| --- \| --- \|

| 1. Register \| Patient & Visit \| Patient ID, demographics, visit ID
  \| Order entry \|

| 2. Place order \| Test Ordering \| Order, ordered test lines,
  priority, referring doctor \| Estimate / collection \|

| 3. Quote and bill \| Billing \| Invoice, discounts, payment/credit
  status \| Collection eligibility \|

| 4. Collect \| Collection \| Collection event, collector, time, site,
  tube/container \| Accessioning \|

| 5. Accession \| Sample Tracking \| Accession number, barcode, specimen
  status \| Lab worklist \|

| 6. Process \| Lab Operations \| Worklist, aliquots, department
  routing, instrument runs \| Result entry \|

| 7. Enter results \| Results \| Values, units, flags, comments,
  operator \| Technical review \|

| 8. Review & validate \| Validation / Quality \| QC checks, validation
  decision, signatory \| Report generation \|

| 9. Publish \| Reporting \| Immutable report version, release timestamp
  \| Delivery \|

| 10. Deliver & close \| Notifications / Portal API / Billing \|
  Delivery events, acknowledgement, balance state \| Analytics /
  retention \|

## 3.1 Workflow rules

-   An order may contain multiple test panels; panels expand into
    component tests according to a versioned catalogue.

-   Each specimen can satisfy one or more tests only when specimen type,
    volume, stability and handling rules permit it.

-   One order may produce multiple specimens and multiple reports; do
    not assume one order equals one sample or one PDF.

-   Each sample must have a unique barcode/accession identity and a
    traceable status history.

-   Results may be numeric, textual, categorical, titre,
    organism/susceptibility, microscopic observation or structured
    pathology findings.

-   Reports must be released only after required results are complete
    and required validation/QC checks pass, unless an authorised
    exception is recorded.

-   Critical values need an escalation/communication workflow with
    acknowledgement and timestamp, not just a coloured flag.

-   Recollection, cancellation, add-on tests, corrected reports and
    partial reports must be explicit workflows.

# 4. Functional requirements by module

## 4.1 Lab, tenant and branch configuration

-   Create lab organisation, legal/business profile, branding
    configuration, contact details and operating hours.

-   Create branches, collection centres, departments and processing
    locations.

-   Configure branch-specific test availability, pricing, taxes, report
    headers, signatories and working calendars.

-   Configure numbering sequences for patient IDs, visit IDs, orders,
    accession numbers, invoices and report IDs.

-   Support timezone, locale, date/time format, currency and
    configurable units.

## 4.2 Patient and referring-doctor management

-   Register patient with unique patient ID; capture name, date of
    birth/age, sex where clinically relevant, contact details, address
    and identifiers permitted by policy.

-   Search and possible-duplicate detection using name, phone, date of
    birth and other approved matching fields.

-   Maintain patient merge workflow with review, reason and audit
    history; preserve source IDs and linked orders.

-   Record consent, communication preferences, guardian details where
    applicable and relevant clinical notes.

-   Manage referring doctors, clinics, corporate clients and referral
    sources with contact and commission/contract fields only where
    legally permitted.

-   Support walk-in registration and repeat-patient lookup.

## 4.3 Appointment, token and queue management

-   Optional appointment slots for collection; walk-in token generation
    and queue display.

-   Separate queues for registration, billing, collection, accessioning
    and each lab department.

-   Priority handling (routine, urgent/stat) with authorised priority
    changes and reason.

-   Queue ageing, waiting time and escalation indicators.

## 4.4 Test catalogue and service configuration

-   Maintain tests, profiles/panels, packages, calculated tests and
    reflex/add-on rules.

-   Each test definition includes code, display name, department,
    specimen type, container, minimum volume, preparation instructions,
    stability, method, turnaround target, result type, units and report
    section.

-   Version reference intervals by age, sex, pregnancy status or other
    clinically approved context where relevant; include source,
    effective dates and review approval.

-   Configure qualitative choices, numeric precision, decimal rules,
    panic/critical thresholds and interpretive comments under authorised
    clinical governance.

-   Support synonyms, external codes, LOINC mapping where applicable,
    and local catalogue codes.

-   Prevent silent modification of historical test definitions; retain
    catalogue version used by each order.

## 4.5 Order entry and sample requirements

-   Create orders from reception, authorised clinician/referrer
    integration or future website/API.

-   Add individual tests, panels, packages, priority, clinical history
    and collection instructions.

-   Validate specimen requirements and detect duplicate or incompatible
    test combinations.

-   Allow add-on tests only when specimen stability/volume and policy
    allow; record who authorised them.

-   Support order cancellation, partial cancellation, recollection and
    referral/send-out tests.

## 4.6 Billing, payments and credit control

-   Generate estimate and invoice from ordered tests and branch/client
    price list.

-   Support configurable discounts, coupons, packages, corporate rates,
    tax treatment and approval limits.

-   Record cash, card, bank transfer, UPI or other locally configured
    payment modes; store references, not sensitive payment credentials.

-   Support deposits, split payments, partial payments, refunds, credit
    notes, outstanding balances and receipts.

-   Link every financial transaction to invoice, order, cashier and
    audit event; reconcile daily collections.

-   Allow configurable rules on whether collection/testing/report
    release can proceed with unpaid balances.

## 4.7 Collection and phlebotomy

-   Generate collection labels/barcodes with patient and specimen
    identifiers; avoid exposing unnecessary personal data on labels.

-   Collection worklist with patient identity verification using at
    least two approved identifiers.

-   Record collector, collection date/time, collection site, specimen
    type, container, volume, fasting status and collection notes as
    relevant.

-   Record not-collected, failed draw, patient refusal,
    haemolysed/clotted/insufficient specimen and recollection reason.

-   Support home collection route/collector assignment as an optional
    module.

-   Print/reprint labels with audit trail; reprints must not create
    duplicate specimen identities.

## 4.8 Accessioning and specimen tracking

-   Scan barcode at receipt; verify order, specimen, container, label
    and transport condition.

-   Record received time, receiving staff, condition, accept/reject/hold
    decision and rejection reason.

-   Track status: ordered, awaiting collection, collected, in transit,
    received, accepted, rejected, processing, completed, stored,
    disposed.

-   Support aliquots, parent-child specimen links, split samples, pooled
    specimens only where policy permits.

-   Track storage location, temperature log reference, retention period
    and disposal authorisation.

-   Provide full chain-of-custody timeline and location history.

## 4.9 Lab worklists and processing

-   Department-specific worklists with filters for priority, due time,
    sample status, analyser and assigned technician.

-   Batch processing, bench assignment, rerouting, send-out tests and
    pending-work visibility.

-   Record method, instrument, reagent/lot where required, operator,
    run/batch ID and processing timestamps.

-   Support manual worksheets and printable bench sheets where analyser
    integration is not available.

-   Track turnaround time from configurable start/end events; separately
    measure collection-to-receipt and receipt-to-validation.

## 4.10 Instrument and analyser integration

-   Design an adapter layer for instrument connectivity; do not couple
    instrument-specific code to core order/result services.

-   Support phased interfaces: manual import, CSV/file exchange,
    ASTM/HL7 or vendor protocol where supported, and validated
    middleware/API.

-   Map instrument test codes, units, sample IDs, flags and result
    statuses to the internal catalogue.

-   Queue inbound messages, validate identity and mapping, quarantine
    unmatched/duplicate messages and provide replay with audit.

-   Capture instrument, software/version, run ID, QC state and
    timestamps.

-   Require vendor documentation and real device testing before claiming
    compatibility.

## 4.11 Result entry and calculation

-   Support numeric, text, dropdown, qualitative, microscopic,
    microbiology and structured histopathology result forms.

-   Store raw value, normalised value, unit, reference interval
    snapshot, abnormal flag, method, operator and timestamp.

-   Apply authorised calculations with versioned formula, input
    provenance and validation; never silently overwrite raw values.

-   Flag out-of-range, panic/critical, delta-check and instrument flags
    separately.

-   Support result drafts, pending, preliminary, verified, corrected and
    cancelled states.

-   Permit result comments and attachments with access control and
    malware scanning.

## 4.12 Quality control and validation

-   Configure internal QC levels, frequency, lot, instrument, acceptable
    ranges and QC charts/Westgard rule evaluation where adopted by the
    lab.

-   Block or warn on patient result release when QC is failed/overdue
    according to configured policy.

-   Record calibration, maintenance, reagent lot changes, proficiency
    testing and corrective/preventive actions.

-   Technical review by authorised staff and clinical validation by
    pathologist/authorised signatory.

-   Support critical result notification, read-back/acknowledgement,
    escalation and documented communication.

-   Keep reviewer, decision, reason, timestamp and the exact result
    version reviewed.

## 4.13 Report generation and release

-   Generate configurable PDF reports with lab/branch identity, patient
    details, order details, results, units, reference intervals, flags,
    method, notes and signatory.

-   Support partial/preliminary and final reports, multi-page reports,
    grouped panels and department-specific layouts.

-   Include report ID, version, issue date/time, page numbering and
    verification/signature details.

-   Release only validated report versions; corrections create a new
    version that supersedes but does not erase the previous version.

-   Record report generated, released, viewed, downloaded, printed,
    emailed or otherwise delivered events.

-   Provide a report authenticity mechanism such as a secure
    verification code/QR resolving to a controlled verification
    endpoint; do not expose report contents publicly.

## 4.14 Notifications and communication

-   Configurable SMS, email and messaging-provider adapters for order
    status, collection reminders and report-ready notifications.

-   Messages should contain minimal sensitive information; use
    authenticated access for report retrieval.

-   Template management, language variants, delivery status, retries,
    opt-out and provider failure handling.

-   Record communication attempts and acknowledgements, especially for
    critical values.

## 4.15 Inventory, procurement and vendors

-   Maintain reagent, consumable, kit and general supply catalogue with
    SKU, unit, lot/batch, expiry and storage conditions.

-   Goods receipt, purchase orders, supplier details, stock transfers,
    consumption, returns, wastage and adjustments.

-   FEFO (first-expiry-first-out) suggestions, low-stock alerts, expiry
    alerts and blocked expired lots.

-   Link reagent/lot consumption to instrument runs or manual batches
    when traceability is required.

-   Approval workflow for purchase orders and stock adjustments;
    maintain stock ledger.

## 4.16 Staff, shifts and workload

-   Staff profiles, roles, branch/department assignment, shift schedules
    and leave/availability.

-   Work allocation and workload dashboards; avoid using staff accounts
    as shared logins.

-   Training/competency records and authorisation matrix for tests,
    instruments and report sign-off.

-   Disable access promptly when staff leave; retain historical
    attribution.

## 4.17 Quality management and incident handling

-   Non-conformance, sample rejection, complaint, incident and
    corrective/preventive action registers.

-   Root-cause analysis, owner, due date, evidence, approval and closure
    verification.

-   Document control for SOPs, forms, policies and version
    acknowledgement.

-   Internal audit schedule, findings, evidence and action tracking.

## 4.18 Analytics and operational reports

-   Daily orders, test volumes, revenue, collections, outstanding
    balances and refunds.

-   Turnaround time by test, department, branch, priority and
    percentile; show exclusions and calculation definitions.

-   Pending samples, overdue worklists, rejected specimens, recollection
    rate and report correction rate.

-   QC failure trends, reagent consumption, expiry loss, stock-outs and
    analyser utilisation.

-   Doctor/referrer, corporate-client and branch performance, with
    privacy-aware access controls.

-   CSV/XLSX export permissioned and logged; dashboards must use
    consistent definitions.

## 4.19 Administration, audit and support

-   User lifecycle, role/permission management, branch settings,
    catalogue configuration and integration credentials.

-   Immutable audit trail for access to sensitive records and all
    create/update/release/cancel/export actions.

-   System health, job queues, failed notifications, integration errors
    and backup status.

-   Configurable data retention, legal hold, patient correction requests
    and controlled archival/deletion processes.

-   Support access should be time-limited, approved, logged and
    minimised.

# 5. Core data model and entity relationships

Use relational storage for transactional and traceability data. The
following is a logical model; physical tables can be normalised further
during implementation.

| Entity \| Key relationships / important fields \|

| --- \| --- \|

| Organisation / Tenant \| Owns branches, users, configuration,
  catalogue and business records \|

| Branch / Collection Centre \| Belongs to tenant; owns queues, price
  lists, departments and numbering sequences \|

| User / Role / Permission \| User assigned to tenant and one or more
  branches; role grants granular permissions \|

| Patient \| Tenant-scoped patient identity; has visits/orders and
  consent/preferences \|

| Visit / Encounter \| Belongs to patient and branch; contains
  registration and visit context \|

| Order \| Belongs to patient, visit and branch; has status, priority,
  source and referring doctor \|

| Order Item \| References versioned test/profile catalogue; links to
  required specimen(s) \|

| Test / Panel / Profile \| Versioned catalogue definition; panel
  contains component tests \|

| Specimen \| Belongs to patient/order; type, barcode, collection and
  acceptance status \|

| Aliquot \| Child of specimen; links to parent and processing
  destination \|

| Work Item / Run \| Order item assigned to department, bench, analyser
  or send-out provider \|

| Result \| Belongs to order item and specimen; typed value, unit,
  flags, provenance and status \|

| QC Record \| Links to instrument, test/method, lot, run and acceptance
  decision \|

| Validation Event \| Links reviewer/signatory to exact result
  set/version and decision \|

| Report / Report Version \| Belongs to order; immutable version,
  release and delivery events \|

| Invoice / Payment / Refund \| Links to order/patient; financial ledger
  and cashier audit \|

| Inventory Item / Lot / Movement \| Tracks stock, supplier, expiry,
  consumption and location \|

| Instrument / Maintenance \| Instrument metadata, mapping, maintenance
  and calibration history \|

| Notification / Delivery Event \| Links recipient, template, provider,
  delivery state and report/order \|

| Audit Event \| Actor, action, entity, before/after reference, reason,
  timestamp, source/IP \|

## 5.1 Relationship rules

-   Every tenant-owned record must carry tenant_id; branch-owned records
    should also carry branch_id.

-   Use UUIDs or similarly non-guessable internal identifiers;
    human-readable accession numbers are separate display identifiers.

-   Enforce foreign keys and tenant-scoped uniqueness. Never rely on
    frontend filtering for tenant isolation.

-   Store timestamps in UTC and render in the branch timezone; preserve
    clinically meaningful local collection times.

-   Store result values in typed columns or a safe structured
    representation, not one unvalidated free-text field.

-   Snapshot test catalogue version, price, units and reference interval
    used at order/result time so future edits do not rewrite history.

# 6. Suggested architecture and service boundaries

Start with a modular monolith unless scale, team size or integration
volume justifies splitting services. Keep module boundaries explicit so
extraction later remains possible.

| Layer / module \| Responsibilities \|

| --- \| --- \|

| Web application (staff UI) \| Role-aware screens, worklists, forms,
  dashboards and report viewer \|

| API / application layer \| Authentication, authorisation, validation,
  workflow orchestration and versioned APIs \|

| Identity & access \| Users, roles, MFA, sessions, branch and tenant
  scope \|

| Patient & order domain \| Patient, visit, order, catalogue and order
  state machine \|

| Specimen & lab domain \| Collection, accessioning, tracking,
  worklists, processing and result lifecycle \|

| Clinical validation domain \| QC gates, review, critical alerts,
  report release and amendment \|

| Finance domain \| Pricing, invoices, payments, refunds and
  reconciliation \|

| Inventory / quality domain \| Lots, movements, QC, calibration,
  incidents and SOPs \|

| Integration adapters \| Analyser protocols, SMS/email, payment
  providers, future customer website/API \|

| Background workers \| PDF generation, notifications, scheduled alerts,
  exports, integration retries \|

| Data layer \| Relational database, object storage for
  PDFs/attachments, cache and audit/event records \|

| Observability \| Central logs, metrics, tracing, alerts,
  backup/restore monitoring \|

## 6.1 Recommended implementation principles

-   Use a modular monolith initially: separate domain modules, service
    interfaces and database schemas/namespaces where useful.

-   Use a relational database such as PostgreSQL for orders, samples,
    results, billing and audit-linked transactions.

-   Use object storage for generated reports and attachments; store
    metadata and immutable version references in the database.

-   Use a queue for asynchronous PDF generation, notifications, exports
    and analyser message processing.

-   Use Redis only for caching, rate limiting and short-lived
    coordination; it must not be the source of truth for clinical or
    financial records.

-   Use transactional outbox or equivalent reliable event publishing for
    cross-module workflows.

-   Version APIs and maintain OpenAPI documentation. Use idempotency
    keys for externally retried create/payment/integration requests.

-   Keep instrument adapters and external provider integrations isolated
    behind documented interfaces.

# 7. Key screens and navigation

| Area \| Screens / views \|

| --- \| --- \|

| Dashboard \| Branch overview, KPI cards, alerts, pending queues,
  critical-value escalations \|

| Front desk \| Patient search, registration, visit, order builder,
  estimate, invoice, payment receipt \|

| Collection \| Collection queue, barcode label, identity confirmation,
  collection capture, home collection route \|

| Accessioning \| Barcode scan, specimen receipt, accept/reject/hold,
  aliquot creation, location tracking \|

| Lab bench \| Department worklist, worksheet, instrument run, result
  entry, pending and overdue filters \|

| Validation \| QC status, technical review, pathologist review,
  critical result communication, release queue \|

| Reports \| Report preview, version history, release,
  reissue/correction and delivery timeline \|

| Finance \| Invoices, payments, refunds, dues, daily close and
  reconciliation \|

| Inventory \| Items, lots, purchase orders, goods receipt, stock
  ledger, expiry and reorder alerts \|

| Quality \| QC charts, calibration, maintenance, non-conformances,
  CAPA, SOPs and audits \|

| Administration \| Users, roles, branches, catalogue, price lists,
  templates, integrations and audit log \|

# 8. API and future customer website integration

The customer-specific website is deliberately outside the current scope.
The LMS should nevertheless be integration-ready. Expose a documented,
secure API so a future website can submit enquiries/appointments/orders
(if enabled), check allowed order statuses and deliver reports through
authenticated access.

| API group (illustrative) \| Example operations \|

| --- \| --- \|

| Identity \| POST /api/v1/auth/token; POST /api/v1/auth/revoke \|

| Patients \| POST /api/v1/patients; GET /api/v1/patients/{id} \|

| Appointments \| POST /api/v1/appointments; GET
  /api/v1/appointments/{id} \|

| Orders \| POST /api/v1/orders; GET /api/v1/orders/{id}; POST
  /api/v1/orders/{id}/cancel \|

| Specimens \| GET /api/v1/orders/{id}/specimens; GET
  /api/v1/specimens/{id}/status \|

| Payments \| POST /api/v1/payment-intents; POST
  /api/v1/payment-webhooks/{provider} \|

| Reports \| GET /api/v1/reports/{id}/metadata; GET
  /api/v1/reports/{id}/download \|

| Webhooks \| order.created; specimen.status_changed; report.released;
  payment.updated \|

## 8.1 Integration security rules

-   Use OAuth 2.0/OIDC or scoped API credentials; use separate
    credentials per integration and environment.

-   Use TLS, strict CORS allowlists for browser clients, rate limits,
    request validation and webhook signature verification.

-   Never expose database IDs, unrestricted patient search or public
    report file URLs.

-   Report access should use short-lived, single-purpose tokens or
    authenticated sessions with expiry and revocation.

-   Use idempotency keys, replay protection and signed webhooks; log
    delivery attempts and provide retry tooling.

-   Give each customer website/integration a narrowly scoped service
    identity; do not share a global admin key.

# 9. Non-functional requirements

| Category \| Requirement / target to validate \|

| --- \| --- \|

| Security \| RBAC, tenant isolation, MFA for privileged roles, TLS in
  transit, encryption at rest, secrets manager, secure session handling
  \|

| Privacy \| Collect minimum necessary data; consent and purpose
  tracking; restricted exports; retention and access policies \|

| Auditability \| Append-only audit events for clinical, financial,
  access, export and administrative actions \|

| Availability \| Define target with buyers; suggested initial target
  99.5% monthly excluding planned maintenance \|

| Performance \| Common staff screens target \<2 seconds at normal load;
  search and worklists paginated; report generation asynchronous \|

| Scalability \| Support multiple branches and configurable growth;
  load-test expected concurrent users and daily order volume \|

| Reliability \| Database backups, point-in-time recovery where
  available, queue retry/dead-letter handling and restore drills \|

| Data integrity \| Transactions, foreign keys, uniqueness constraints,
  optimistic locking and idempotent external operations \|

| Accessibility \| Keyboard support, readable contrast, clear validation
  errors and responsive layouts \|

| Maintainability \| Automated tests, code review, migrations, API
  documentation, structured logs and release process \|

| Interoperability \| Documented APIs; HL7/ASTM/vendor interfaces only
  after device-specific validation \|

| Localization \| Configurable currency, tax, language, date/time, units
  and branch timezone \|

## 9.1 Security baseline

-   Apply OWASP ASVS-informed controls and secure development practices.

-   Use least privilege, password hashing with a modern algorithm, MFA
    for admins/signatories and session revocation.

-   Encrypt backups and stored reports; segregate production, staging
    and development data.

-   Do not use real patient data in development/testing unless formally
    authorised and protected; prefer synthetic data.

-   Mask sensitive fields in logs; never log passwords, tokens, full
    report contents or payment credentials.

-   Run dependency, container, static-analysis and vulnerability scans;
    maintain incident response and breach procedures.

# 10. Compliance, clinical governance and India deployment considerations

Regulatory applicability depends on the laboratory's location, services,
accreditation status, ownership and data flows. Before production
deployment in India, obtain qualified legal, privacy and
laboratory-quality advice. Validate applicable requirements rather than
treating this document as legal certification.

-   Assess the Digital Personal Data Protection Act, 2023 and
    rules/notifications in force at deployment, including notice,
    consent or other lawful basis, processor/vendor obligations,
    security safeguards, rights handling and breach response as
    applicable.

-   Assess applicable Clinical Establishments legislation/rules,
    state-specific requirements, biomedical waste requirements and local
    laboratory licensing obligations.

-   Where the lab seeks accreditation, map workflows and evidence to the
    current applicable NABL/ISO 15189 requirements and the lab's quality
    manual; do not claim accreditation through software alone.

-   Define clinical ownership for test catalogue, reference intervals,
    critical limits, interpretive text, QC policy and report sign-off.

-   Define retention periods for patient records, reports, consent,
    financial records, QC and audit data with counsel and applicable
    standards.

-   Use approved electronic signature processes and validate whether the
    chosen signature method meets the lab's legal and accreditation
    needs.

-   Ensure patient identity, confidentiality and report delivery
    processes are reviewed before go-live.

# 11. Business rules and exception workflows

| Scenario \| Expected system behaviour \|

| --- \| --- \|

| Duplicate patient suspected \| Warn, show matching candidates to
  authorised staff, allow reviewed merge; never auto-merge \|

| Wrong or unreadable label \| Quarantine specimen, record incident and
  resolve identity before processing \|

| Rejected specimen \| Record reason, notify relevant staff, request
  recollection and preserve original specimen history \|

| Insufficient sample \| Mark affected tests, assess add-on/aliquot
  options under SOP, request recollection if needed \|

| Instrument result mismatch \| Quarantine inbound result, show
  mapping/identity issue, require authorised resolution \|

| QC failure \| Apply configured block/warning, document investigation
  and approval before release \|

| Critical result \| Trigger immediate communication/escalation, require
  acknowledgement and log method/time/person \|

| Report correction \| Create superseding report version, identify
  corrected fields/reason, notify recipient and retain prior version \|

| Payment webhook repeated \| Idempotently process once; preserve
  provider transaction reference and event history \|

| Network outage \| Allow safe queueing/retry; prevent duplicate
  orders/payments; provide manual downtime procedure \|

| Staff leaves organisation \| Disable account/sessions; retain
  historical audit attribution \|

| Patient data correction request \| Route through authorised
  verification and correction process; preserve audit and legal
  retention \|

# 12. MVP, phased roadmap and prioritisation

| Phase \| Build scope \| Exit criteria \|

| --- \| --- \| --- \|

| Phase 0 --- Discovery & governance \| Map real lab SOPs, test
  catalogue, roles, branches, report samples, devices, billing and legal
  requirements \| Signed workflow maps, data dictionary, clinical owner
  and acceptance criteria \|

| Phase 1 --- Operational MVP \| Tenant/branch setup, users/RBAC,
  patient, test catalogue, orders, billing, collection, barcode,
  accessioning, manual worklists, result entry, validation, PDF report,
  audit \| A complete routine order can be processed and released with
  traceability \|

| Phase 2 --- Daily operations \| Queues, partial reports, critical
  alerts, notifications, payment reconciliation, dashboards, inventory
  basics, backups and restore drills \| Staff can run day-to-day
  operations without shadow spreadsheets for core workflow \|

| Phase 3 --- Quality & automation \| QC charts/rules, calibration,
  CAPA, analyser adapters, advanced inventory, send-out tests, advanced
  analytics \| Device interfaces and quality controls validated with
  real lab processes \|

| Phase 4 --- Multi-branch & integrations \| Central catalogue, branch
  price lists, inter-branch transfers, corporate accounts, API/webhooks
  and external website integration \| Branch isolation, consolidated
  reporting and external integration security verified \|

| Phase 5 --- Optimisation \| Mobile collection tools, advanced
  analytics, workflow automation, multilingual support and
  customer-specific extensions \| Measured improvements in TAT, errors,
  adoption and operating cost \|

## 12.1 MVP must-have vs later

| Must-have for first usable release \| Can follow after stable MVP \|

| --- \| --- \|

| Patient, visit, order, test catalogue \| Appointment scheduling and
  home collection routing \|

| Invoice, payment recording and receipt \| Advanced corporate credit
  and commission workflows \|

| Barcode, collection and accessioning \| Complex aliquot automation and
  specimen storage optimisation \|

| Worklists and manual result entry \| Broad analyser integrations \|

| Result review, authorisation and report versioning \| Advanced
  pathology image workflows \|

| Audit log, RBAC, backups and tenant/branch controls \| Advanced BI,
  predictive analytics and AI assistance \|

| Basic inventory and low-stock/expiry visibility \| Full procurement
  and supplier portal \|

# 13. Testing and acceptance criteria

Build test cases around complete workflows and failure modes, not only
isolated screens.

-   Patient registration detects likely duplicates without blocking
    legitimate family/shared-contact cases.

-   An order with multiple tests generates the correct specimen
    requirements and labels.

-   Barcode scanning cannot silently attach a specimen to the wrong
    patient/order.

-   Rejected, recollected, split and add-on specimens preserve a
    complete event timeline.

-   Only authorised roles can enter, review, validate, release, amend or
    cancel results/reports.

-   QC failure and overdue QC apply the configured release policy.

-   Critical-value escalation records notification, acknowledgement,
    recipient and timestamps.

-   Report PDF matches the approved template and released result
    snapshot; later catalogue changes do not alter old reports.

-   Corrected report creates a new version and retains the superseded
    report with reason and notification event.

-   Repeated payment/webhook requests do not duplicate payments or
    invoices.

-   Tenant A cannot access Tenant B data through UI, API, exports,
    guessed identifiers or background jobs.

-   Backup restoration is demonstrated and measured against recovery
    objectives.

-   Audit records capture actor, action, target, timestamp, reason and
    relevant before/after references.

# 14. Delivery plan and implementation sequence

1.  Conduct discovery with at least one working pathology lab: observe
    reception, collection, accessioning, bench work, validation, billing
    and report delivery.

2.  Collect sample requisition forms, report formats, test catalogue,
    SOPs, rejection reasons, QC forms, price lists and instrument
    manuals.

3.  Create process maps, role-permission matrix, data dictionary, state
    machines and clickable wireframes.

4.  Design tenant/branch boundaries, relational schema, audit model and
    API contracts before building the main workflows.

5.  Implement vertical slices: patient → order → specimen → result →
    validation → report, then add billing and operational breadth.

6.  Run pilot using synthetic or properly authorised test data; conduct
    user acceptance with reception, collectors, technicians,
    pathologists and finance.

7.  Train users, configure live catalogue and SOPs, migrate only
    validated data, run parallel operations, then cut over with rollback
    plan.

# 15. Open decisions to resolve before development

| Decision \| Why it matters \|

| --- \| --- \|

| Single lab vs multi-tenant SaaS from day one? \| Changes tenancy, data
  isolation, billing, deployment and support architecture \|

| Which test disciplines? \| Clinical biochemistry, haematology,
  serology, microbiology, histopathology and others have different
  workflows \|

| Which branches and collection centres? \| Determines branch routing,
  numbering, stock and consolidated reporting \|

| Which analysers and interfaces? \| Determines protocols, middleware,
  vendor cooperation and validation effort \|

| Who can validate/sign each test/report? \| Defines clinical
  authorisation matrix and release controls \|

| What are the actual report formats and languages? \| Determines report
  template system and localisation \|

| Which payment/tax modes are required? \| Determines billing,
  reconciliation and accounting integration \|

| What retention and privacy policies apply? \| Determines access,
  archival, deletion and hosting design \|

| Cloud, on-premise or hybrid? \| Affects network reliability, device
  connectivity, security and support \|

| Expected daily orders, users and branches? \| Required for capacity
  planning and performance targets \|

# 16. Definition of done for the core LMS

-   A complete order can be traced from patient registration through
    every specimen, test, result, validation and released report.

-   Every high-risk action is permission-checked, reasoned where
    required and auditable.

-   Clinical catalogue and report configuration are versioned and
    governed by authorised lab personnel.

-   Tenant and branch isolation are verified with automated security
    tests.

-   Financial records reconcile to invoices, payments, refunds and daily
    close.

-   Critical-value, QC, specimen rejection, recollection and report
    correction workflows are tested end to end.

-   Backups, restore, monitoring, incident response and staff
    offboarding procedures are operational.

-   User acceptance is signed by representatives of laboratory
    operations and clinical governance.

# Appendix A --- Suggested core statuses

| Object \| Suggested lifecycle states \|

| --- \| --- \|

| Order \| Draft → Confirmed → Partially collected → Collected → In
  progress → Partially reported → Completed; with Cancelled/On hold \|

| Specimen \| Expected → Collected → In transit → Received → Accepted →
  Processing → Stored/Disposed; with Rejected/Cancelled \|

| Test work item \| Pending → Assigned → In progress → Result entered →
  Technical review → Clinically validated → Reported; with On
  hold/Cancelled \|

| Result \| Draft → Preliminary → Verified → Corrected/Superseded; with
  Cancelled \|

| Report \| Draft → Generated → Pending validation → Released →
  Superseded/Withdrawn \|

| Invoice \| Draft → Issued → Partially paid → Paid; with
  Voided/Credited \|

| Payment \| Initiated → Pending → Successful/Failed/Refunded; retain
  provider event history \|

# Appendix B --- Glossary

| Term \| Meaning \|

| --- \| --- \|

| LMS \| Laboratory Management System \|

| LIS \| Laboratory Information System; often used for the laboratory
  workflow/results subsystem \|

| Accession number \| Unique laboratory identifier assigned to a
  received specimen or case \|

| Aliquot \| A portion derived from a parent specimen \|

| TAT \| Turnaround time; must have a clearly defined start and end
  event \|

| QC \| Quality control \|

| CAPA \| Corrective and preventive action \|

| FEFO \| First-expiry-first-out inventory rotation \|

| RBAC \| Role-based access control \|

| Tenant \| A logically isolated customer/laboratory organisation in a
  shared platform \|

| Reflex test \| A follow-on test triggered by a defined result or
  clinical/lab rule \|

Implementation caution: This is a product and engineering baseline, not
a substitute for a laboratory's SOPs, clinical governance, legal review,
accreditation assessment or device-vendor validation. Confirm every
clinical rule with the laboratory's authorised professionals before
production use.

# Revision 1.1 --- Reporting-first product scope

This revision makes PDF report generation, report configuration and the
test catalogue/database the highest-priority parts of the product. All
core patient, order, specimen, laboratory, result, validation, security
and audit features remain in scope. Business-operation features are
deferred from the initial build.

## 1. Updated scope decision

| Status \| Modules \|

| --- \| --- \|

| IN SCOPE --- build now \| Patient and doctor management; test
  catalogue; order entry; collection; barcode and accessioning; specimen
  tracking; lab worklists; result entry; QC and validation; PDF report
  generation and versioning; notifications; staff and RBAC; audit;
  branch configuration; APIs and integration foundation. \|

| DEFERRED --- not part of the initial release \| Inventory and
  procurement; supplier management; stock ledger; advanced
  revenue/business analytics; corporate account/commercial management;
  business optimisation dashboards. Keep their future extension points
  in the architecture, but do not build their screens or workflows now.
  \|

| LATER / OPTIONAL \| Customer-specific branded websites, patient mobile
  app, advanced analyser integrations, home collection routing and
  advanced BI. \|

The deferred business-operation scope does not remove clinical
laboratory operations. It only postpones commercial and back-office
management features. Basic invoice/payment recording may remain if
required to complete reception workflow; advanced finance and business
analytics can be deferred.

## 2. Reporting is the primary product capability

Treat the report as a first-class, versioned clinical record---not
merely a PDF exported from a screen. The PDF must be generated from
validated database records and an approved report-template version. A
released report must be reproducible and must not change when the test
catalogue or template is edited later.

### 2.1 Report template builder and header options

-   Support multiple report templates per laboratory, branch,
    department, report category and customer contract where applicable.

-   Header options: full-width branded header; compact header;
    logo-left/company-details-right; logo-centred; no-logo/minimal
    header; configurable letterhead image or approved background.

-   Header fields: laboratory name, branch name, address, phone, email,
    website, licence/accreditation identifiers where valid, logo, report
    title and page numbering.

-   Allow separate first-page and continuation-page headers, plus
    configurable footer and watermark (e.g. preliminary, duplicate,
    corrected).

-   Configurable patient block: patient name/ID, age/date of birth, sex
    where clinically relevant, visit/order number, referring doctor,
    collection date/time, received date/time and report date/time.

-   Configurable specimen block: specimen type, accession number,
    collection site, collection time and specimen condition where
    relevant.

-   Configurable result table columns: test name, result, unit,
    reference interval, flag, method and comments. Column visibility
    must be configurable by report type.

-   Support grouped panels, sub-tests, sections, nested headings,
    multiline comments, tables, graphs where clinically appropriate, and
    page-break rules.

-   Support separate layouts for haematology, biochemistry, serology,
    microbiology, urine/stool, histopathology and other configured
    disciplines.

-   Support authorised signatory block with name, qualification,
    designation, signature image or validated e-signature workflow, and
    sign-off timestamp.

-   Allow draft preview using synthetic/demo patients; provide
    print-safe A4 output and controlled PDF download.

-   Provide template versioning, approval, effective dates, rollback and
    audit history. Only approved versions can be used for released
    reports.

### 2.2 Report lifecycle and controls

| State / event \| System behaviour \|

| --- \| --- \|

| Draft \| Report preview can be generated from current draft results;
  clearly watermark as draft and prevent external delivery. \|

| Pending validation \| Check required tests, result completeness, QC
  status and required reviewer/signatory permissions. \|

| Released \| Freeze the exact result snapshot, patient/order metadata
  snapshot, template version, signatory and PDF file checksum. \|

| Delivered \| Record channel, recipient, timestamp, delivery provider
  response and acknowledgement where available. \|

| Corrected / amended \| Create a new report version, state what changed
  and why, identify the superseded version, and preserve both versions.
  \|

| Withdrawn / cancelled \| Restrict access to the withdrawn report,
  record reason and actor, retain audit history and notify recipients
  when required. \|

-   A released PDF must be immutable. Never overwrite a previously
    released PDF file.

-   Every PDF should have a unique report number, version, generation
    timestamp and document checksum.

-   Store the PDF in private object storage; store metadata and storage
    key in the database. Do not store public permanent file URLs.

-   Use short-lived authenticated download links or authenticated report
    viewing. Do not expose patient reports through guessable URLs.

-   Provide report verification using a random verification token/QR
    code that confirms authenticity without exposing report contents
    publicly.

-   Record preview, generation, release, reprint, download, email and
    correction events in the audit trail.

-   Support partial reports only when the laboratory explicitly enables
    them; label pending tests clearly and issue a later final report.

### 2.3 PDF generation architecture

| Component \| Responsibility \|

| --- \| --- \|

| Report data assembler \| Fetch authorised patient, order, specimen,
  test, result, reference interval, validation and signatory snapshots.
  \|

| Template renderer \| Apply the approved template version,
  header/footer, layout rules, fonts, tables and page breaks. \|

| PDF worker \| Generate the PDF asynchronously; return job status and
  retry safely on transient failure. \|

| Private file storage \| Store immutable PDF versions and attachments;
  encrypt at rest and restrict access. \|

| Report registry \| Store report ID, version, template version, data
  snapshot reference, checksum, status and timestamps. \|

| Delivery service \| Send notification or secure access link; record
  provider response and retry status. \|

Recommended implementation: render HTML/CSS templates to PDF using a
server-side renderer that supports A4 page layout, embedded fonts,
repeating table headers and controlled page breaks. Keep report data
assembly separate from visual rendering so that changing the design
cannot alter clinical values.

## 3. Test catalogue and database seed data

The test catalogue is the master source used by order entry, specimen
collection, lab worklists, result forms and PDF reports. Build it as
structured, versioned database records---not hard-coded test names in
frontend code.

### 3.1 Required test-master fields

| Field group \| Fields to store \|

| --- \| --- \|

| Identity \| test_id, local_code, standard_code/LOINC if mapped,
  display_name, synonyms, discipline, department, active status,
  catalogue_version \|

| Order configuration \| billable flag, panel/profile membership,
  allowed branches, priority rules, turnaround target, add-on/reflex
  rules \|

| Specimen \| specimen_type, container, anticoagulant/preservative,
  minimum_volume, preferred_volume, collection instructions,
  transport/stability rules \|

| Method \| method name, analyser/method mapping, method version,
  processing notes \|

| Result schema \| result_type, data_type, allowed options, unit,
  precision, min/max validation, formula if calculated, component
  structure \|

| Clinical interpretation \| reference interval rules, critical limits,
  abnormal flag rules, age/sex/pregnancy applicability, interpretive
  comments, effective dates, source and approver \|

| Report configuration \| report section, display order, label, unit
  display, reference interval display, method display, comment rules,
  template mapping \|

| Governance \| created_by, approved_by, approval_date, effective_from,
  effective_to, change_reason, audit reference \|

### 3.2 Starter test catalogue --- common disciplines

The following is a starter catalogue for database seeding and workflow
testing. It is intentionally not a complete national test directory.
Each laboratory must approve its own catalogue, specimen requirements,
methods, units, reference intervals and critical limits before live use.

| Discipline \| Starter test / panel \| Typical specimen (confirm
  locally) \| Result structure \| Data captured / notes \|

| --- \| --- \| --- \| --- \| --- \|

| Haematology \| Complete Blood Count (CBC) \| EDTA whole blood \|
  Numeric + differential \| Haemoglobin, RBC, WBC, platelets, indices
  and differential \|

| Haematology \| ESR \| EDTA/citrate blood as method requires \| Numeric
  \| Method-specific result and unit \|

| Haematology \| Peripheral Blood Smear (PBS) \| EDTA whole blood \|
  Structured text \| RBC morphology, WBC morphology, platelet estimate
  \|

| Haematology \| Reticulocyte Count \| EDTA whole blood \| Numeric \|
  Percentage and/or absolute count \|

| Coagulation \| PT / INR \| Citrated plasma \| Numeric \| PT, control,
  INR and method-specific interval \|

| Coagulation \| APTT \| Citrated plasma \| Numeric \| Seconds and
  method-specific interval \|

| Coagulation \| D-Dimer \| Citrated plasma \| Numeric/qualitative \|
  Assay-specific unit and cut-off \|

| Biochemistry \| Fasting Blood Glucose \| Fluoride plasma/validated
  specimen \| Numeric \| Fasting status and collection time \|

| Biochemistry \| Random Blood Glucose \| Validated blood specimen \|
  Numeric \| Random collection context \|

| Biochemistry \| Post-Prandial Blood Glucose \| Validated blood
  specimen \| Numeric \| Time after meal must be captured \|

| Biochemistry \| HbA1c \| EDTA whole blood \| Numeric \| Percentage
  and/or IFCC unit; method details \|

| Biochemistry \| Liver Function Test (LFT) \| Serum/plasma \| Panel \|
  Bilirubin, AST, ALT, ALP, proteins and other configured components \|

| Biochemistry \| Renal/Kidney Function Test (KFT/RFT) \| Serum/plasma
  \| Panel \| Urea, creatinine, uric acid and electrolytes as configured
  \|

| Biochemistry \| Lipid Profile \| Serum \| Panel \| Total cholesterol,
  triglycerides, HDL, LDL and ratios if validated \|

| Biochemistry \| Electrolytes \| Serum/plasma \| Panel \| Sodium,
  potassium, chloride and bicarbonate/CO2 if configured \|

| Biochemistry \| Calcium \| Serum/plasma \| Numeric \| Total or ionised
  calcium must be distinguished \|

| Biochemistry \| Phosphorus \| Serum/plasma \| Numeric \|
  Method-specific unit and interval \|

| Biochemistry \| Amylase / Lipase \| Serum \| Numeric \| Separate test
  definitions \|

| Biochemistry \| CRP \| Serum/plasma \| Numeric \| Standard CRP vs
  high-sensitivity CRP must be separate \|

| Biochemistry \| Iron Studies \| Serum \| Panel \| Iron, TIBC,
  transferrin saturation and ferritin as configured \|

| Endocrinology \| Thyroid Profile (T3/T4/TSH) \| Serum \| Panel \|
  Total/free T3/T4 must be separately defined \|

| Endocrinology \| TSH \| Serum \| Numeric \|
  Age/pregnancy/method-specific intervals where applicable \|

| Endocrinology \| Vitamin D (25-OH) \| Serum \| Numeric \| Assay and
  unit-specific interpretation \|

| Endocrinology \| Vitamin B12 \| Serum \| Numeric \| Method-specific
  interval \|

| Endocrinology \| Cortisol \| Serum/plasma \| Numeric \| Collection
  time is essential \|

| Serology \| HIV 1 & 2 screening \| Serum/plasma \| Qualitative \|
  Reactive/non-reactive with confirmatory algorithm and authorised
  wording \|

| Serology \| HBsAg \| Serum/plasma \| Qualitative/quantitative \|
  Assay-specific cut-off and confirmatory workflow \|

| Serology \| Anti-HCV \| Serum/plasma \| Qualitative \|
  Reactive/non-reactive and confirmatory pathway \|

| Serology \| Dengue NS1 / IgM / IgG \| Serum/plasma \| Qualitative \|
  Separate analytes and illness-day context \|

| Serology \| Widal \| Serum \| Titre/structured \| Method limitations
  and local clinical interpretation \|

| Serology \| RPR/VDRL \| Serum \| Qualitative/titre \| Reactive status
  and titre \|

| Urinalysis \| Urine Routine & Microscopy \| Urine \| Structured +
  numeric \| Physical, chemical and microscopic findings \|

| Stool \| Stool Routine & Microscopy \| Stool \| Structured text \|
  Macroscopic and microscopic findings \|

| Microbiology \| Urine Culture & Sensitivity \| Urine \| Organism +
  susceptibility \| Growth, colony count, organism and antimicrobial
  susceptibility \|

| Microbiology \| Blood Culture \| Blood culture bottle \| Organism +
  susceptibility \| Bottle, incubation, organism and susceptibility
  timeline \|

| Microbiology \| Sputum Culture \| Sputum \| Organism + susceptibility
  \| Specimen quality, growth and susceptibility \|

| Histopathology \| Biopsy / Histopathology \| Tissue in approved
  fixative \| Structured narrative \| Gross, microscopic description,
  diagnosis and sign-out \|

| Cytology \| FNAC \| Aspirate/smear \| Structured narrative \| Site,
  adequacy, microscopic description and conclusion \|

| Immunology \| ANA \| Serum \| Qualitative/titre/pattern \| Method,
  titre and pattern \|

| Immunology \| Rheumatoid Factor \| Serum \| Numeric/qualitative \|
  Method-specific interpretation \|

### 3.3 Example CBC component records

A panel such as CBC should not be stored as one text result. Store each
component as a separate result definition so it can be validated,
flagged, trended and displayed independently.

| Component test \| Typical result type \| Unit example \| Important
  configuration \|

| --- \| --- \| --- \| --- \|

| Haemoglobin (Hb) \| Numeric \| g/dL \| Age/sex/pregnancy-specific
  interval may apply \|

| RBC count \| Numeric \| 10\^6/µL \| Age/sex-specific interval may
  apply \|

| Haematocrit / PCV \| Numeric \| % \| Method and population-specific
  interval \|

| MCV \| Numeric \| fL \| Derived or analyser-reported; preserve
  provenance \|

| MCH \| Numeric \| pg \| Derived or analyser-reported \|

| MCHC \| Numeric \| g/dL \| Derived or analyser-reported \|

| RDW \| Numeric \| % \| Store analyser-specific parameter definition \|

| Total WBC count \| Numeric \| 10\^3/µL \| Age-specific interval may
  apply \|

| Neutrophils \| Numeric \| % and/or absolute count \| Store percentage
  and absolute count distinctly \|

| Lymphocytes \| Numeric \| % and/or absolute count \| Store percentage
  and absolute count distinctly \|

| Monocytes \| Numeric \| % and/or absolute count \| Store percentage
  and absolute count distinctly \|

| Eosinophils \| Numeric \| % and/or absolute count \| Store percentage
  and absolute count distinctly \|

| Basophils \| Numeric \| % and/or absolute count \| Store percentage
  and absolute count distinctly \|

| Platelet count \| Numeric \| 10\^3/µL \| Include platelet flags and
  smear confirmation workflow where used \|

### 3.4 Reference intervals and clinical values --- mandatory safety rule

Do not hard-code one universal 'normal range' for a test. Reference
intervals and critical limits vary by laboratory, method, analyser,
reagent, specimen, age, sex, pregnancy status and other clinical
context. The software must support multiple effective-dated intervals
and retain the exact interval shown on the released report.

-   Seed test names, disciplines, specimen types and result schemas as
    catalogue data.

-   Load reference intervals only from the adopting laboratory's
    verified method/analyser documentation, validated local studies or
    an authoritative source approved by its laboratory director.

-   Store interval lower/upper bounds, inclusivity, unit, population
    rule, method/analyser, effective dates, source citation/document,
    approver and approval timestamp.

-   Store critical/panic thresholds separately from reference intervals
    and require clinical approval.

-   Never use synthetic sample patient values as medical guidance. Label
    demo records clearly as TEST DATA and block them from production
    report delivery.

-   Do not populate live patient records with scraped or invented
    values. Real patient results must originate from authorised manual
    entry or validated instrument interfaces.

### 3.5 Database tables for catalogue and results

| Suggested table \| Purpose / key columns \|

| --- \| --- \|

| test_definitions \| id, tenant_id, code, name, discipline_id,
  specimen_type_id, result_schema_id, active \|

| test_definition_versions \| test_definition_id, version, method_id,
  effective_from, effective_to, approval_status, change_reason \|

| test_panels \| id, code, name, panel_version \|

| test_panel_components \| panel_id, component_test_id, display_order,
  required_flag \|

| specimen_types \| id, code, name, container, additive,
  handling_instructions \|

| test_specimen_requirements \| test_version_id, specimen_type_id,
  min_volume, preferred_volume, stability_rule_id \|

| result_definitions \| id, test_version_id, component_code, label,
  data_type, unit_id, precision, allowed_values \|

| reference_intervals \| result_definition_id, method_id, analyser_id,
  population_rule, lower_bound, upper_bound, unit_id, effective dates,
  source, approval \|

| critical_limits \| result_definition_id, method_id, population_rule,
  lower/upper critical values, escalation policy, approver \|

| test_method_mappings \| test_version_id, instrument_id,
  external_test_code, unit_mapping, flag_mapping \|

| orders \| patient_id, branch_id, order_number, status, priority,
  ordered_at \|

| order_items \| order_id, test_definition_version_id, status,
  specimen_id \|

| specimens \| order_id, barcode, specimen_type_id, collected_at,
  received_at, status \|

| results \| order_item_id, result_definition_id, raw_value,
  normalised_value, unit_id, flag, entered_by, entered_at, status \|

| result_reviews \| result_id, reviewer_id, decision, reason,
  reviewed_at \|

| report_versions \| order_id, report_number, version,
  template_version_id, result_snapshot, file_key, checksum, released_at
  \|

### 3.6 Example synthetic CBC test record for development

Use synthetic records to test forms, flags and PDF layout. These numbers
below are illustrative test fixtures only, not a clinical reference
range and not suitable for diagnosis or a real patient report.

| Component \| Synthetic demo result \| Demo unit \| Demo flag \|

| --- \| --- \| --- \| --- \|

| Haemoglobin \| 13.8 \| g/dL \| DEMO \|

| RBC count \| 4.70 \| 10\^6/µL \| DEMO \|

| Haematocrit \| 41.0 \| % \| DEMO \|

| MCV \| 87.2 \| fL \| DEMO \|

| MCH \| 29.4 \| pg \| DEMO \|

| MCHC \| 33.7 \| g/dL \| DEMO \|

| Total WBC count \| 7.2 \| 10\^3/µL \| DEMO \|

| Neutrophils \| 58 \| % \| DEMO \|

| Lymphocytes \| 32 \| % \| DEMO \|

| Monocytes \| 6 \| % \| DEMO \|

| Eosinophils \| 3 \| % \| DEMO \|

| Basophils \| 1 \| % \| DEMO \|

| Platelet count \| 250 \| 10\^3/µL \| DEMO \|

## 4. Updated build sequence

| Priority \| Build sequence \| Completion test \|

| --- \| --- \| --- \|

| P0 \| Report data model, test catalogue schema, template builder and
  PDF renderer foundation \| Can preview multiple report layouts from
  approved structured test data. \|

| P1 \| Patient, visit, order, test selection and specimen requirement
  workflow \| A patient order resolves to the correct test versions and
  specimen requirements. \|

| P2 \| Collection, barcode, accessioning, worklists and result entry \|
  A specimen can be tracked end-to-end and results captured as
  structured components. \|

| P3 \| QC, technical review, clinical validation, critical result
  workflow \| Only authorised, eligible results can proceed to final
  release. \|

| P4 \| Immutable report release, correction/versioning, secure PDF
  delivery and audit \| Released PDF exactly matches the approved result
  snapshot and template version. \|

| P5 \| Notifications, API foundation, branch rollout and analyser
  adapters \| External systems can integrate securely without bypassing
  lab workflow controls. \|

Business-operation modules deferred for now: inventory/procurement,
supplier workflows, advanced commercial analytics, corporate account
management and business dashboards. Preserve extension points, but do
not let these delay the report-first clinical workflow.

## 5. Report acceptance checklist

-   Admin can create and approve at least three distinct report header
    styles and assign them by branch/department.

-   A report can display multiple panels, individual components, units,
    reference intervals, flags, comments and signatory details.

-   The renderer handles long reports, multi-page tables, repeating
    table headers, page numbers, footers and continuation headers.

-   A released report is tied to a frozen result snapshot and approved
    template version.

-   Editing a test name, unit, interval or template later does not
    modify an already released PDF.

-   A correction creates a new report version, records the reason and
    preserves the previous PDF.

-   Unauthorised staff cannot release or download restricted reports.

-   Report verification does not reveal report contents to an
    unauthenticated visitor.

-   PDF output is tested across common PDF viewers and prints correctly
    on A4 paper.

-   Demo/test patients and synthetic values are visibly marked and
    cannot accidentally be released as genuine clinical reports.

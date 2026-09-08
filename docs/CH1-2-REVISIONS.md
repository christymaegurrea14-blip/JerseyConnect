# Chapter 1–2 Revisions — Aligning the Manuscript with the Actual System

This file gives paste-ready replacement text for every passage in Chapters 1–2 that describes a
feature differently from how it is actually implemented in the codebase. Each entry names the
manuscript location, quotes what's currently there, and gives the corrected replacement. Copy the
"Replace with" text over the corresponding passage in `Chap1_2_JerseyConnect_REVISED.docx`.

## Why these changes — what was verified against the code

| Claimed in Ch. 1–2 | What's actually implemented (verified against migrations/controllers) |
|---|---|
| Real-time chat via **WhatsApp Business API** | **In-app messaging** (`MessageThread`/`Message` models, one thread per design request). No WhatsApp integration exists anywhere in the codebase. |
| "Real-time" messaging/notifications, **sent/delivered/seen** status | No websocket/broadcast layer (no Pusher/Echo/Reverb) — messaging is request/response via Inertia, not push-based. Read-tracking is a simple two-state `client_last_read_at`/`admin_last_read_at` timestamp, not a three-state indicator. |
| Design form with **dropdown selectors for sizes, necklines, colors, materials** | `design_requests` table only has: team name, primary/secondary/accent color, font style, estimated quantity, notes, and one logo file upload. No size, neckline, or material fields exist. |
| **Automated shipping fee computation** based on destination and parcel weight | `orders.shipping_fee` is a plain integer the **admin manually enters** (`AdminOrderController`, required when marking an order "shipped"). No weight field, no distance/fee formula anywhere. |
| **Real-time delivery tracking** via third-party courier integration | Order status (`shipped`, `delivered`, etc.) is **manually updated by the admin**; `courier_receipts` records courier name, transaction number, and date shipped as data entry, not a live tracking feed. |
| "Update **inventory levels**" | `jerseys` table has no stock/quantity field — admins manage the template catalog (name, image, price, active/inactive), not stock counts. |
| Generic "**report generation**" module | The dashboard (`DashboardController`) computes real analytics: weekly order count & revenue with week-over-week % change, best-selling templates, pending design requests, pending GCash verifications, unread messages, new signups, and a "needs attention" list — but there's no exportable/generated report feature. |
| "Select from available **payment methods**" (plural) | Payment is **GCash only** — reference number + proof-of-payment screenshot, verified manually by an admin. |

None of this is a step down for the manuscript — the real dashboard analytics and status pipeline are
concrete and defensible in a panel Q&A, whereas the original wording (WhatsApp API, auto fee
computation, live tracking) invites a question the system can't currently answer. The revised text
below describes only what's built.

---

## 1. Introduction — second paragraph (after "...web-based system developed to bridge this gap...")

**Current:**
> ...enabling users to configure jersey designs through a structured, form-driven workflow—browsing a
> catalog of pre-designed mockup templates, completing a step-by-step custom specification form, and
> uploading their own design files—place orders securely, and monitor the status of their transactions
> throughout the order lifecycle. The platform further integrates a real-time messaging feature via the
> WhatsApp Business Application Programming Interface (API), a comprehensive multi-status notification
> system, and a logistics module that facilitates shipping beyond local areas, real-time delivery
> tracking, and automated shipping fee computation. Through these integrated features, JerseyConnect
> aims to streamline jersey ordering workflows, expand business reach beyond geographic boundaries, and
> elevate the overall customer experience.

**Replace with:**
> ...enabling users to configure jersey designs through a structured, form-driven workflow—browsing a
> catalog of pre-designed jersey templates organized by sport, and completing a design request form
> that captures the team name, color scheme, font style, and estimated quantity, together with a logo
> or design reference file—place orders through an integrated GCash payment process, and monitor the
> status of their transactions throughout the order lifecycle. The platform further integrates an
> in-app messaging feature that keeps all design discussions between customers and administrators
> documented within the platform itself, a status-tracking and alert system that flags unread messages
> and pending actions for administrators, and a delivery module that supports shipments beyond the
> local area through assigned couriers, map-assisted address capture, and recorded shipping fees and
> courier receipts per order. Through these integrated features, JerseyConnect aims to streamline
> jersey ordering workflows, expand business reach beyond geographic boundaries, and elevate the overall
> customer experience.

*(The paragraph right after this, about the panel of examiners requesting "a real-time communication
feature," "a more robust notification system," and "a logistics module" — no change needed. That's an
accurate historical account of what the panel asked for at the proposal defense; the sections below
show how those three requests were actually addressed.)*

---

## 2. Purpose and Description

**Current (design-configuration sentence):**
> The platform allows customers to personalize jersey designs by browsing a curated catalog of
> pre-designed mockup templates and completing a step-by-step custom specification form, using
> structured dropdown selectors to indicate sizes, necklines, colors, and materials. A dedicated file
> upload subsystem further allows customers to submit their own vector graphics, team logos, and design
> references directly during the booking checkout, ensuring that the final product matches their exact
> specifications before an order is formally submitted.

**Replace with:**
> The platform allows customers to personalize jersey designs by browsing a curated catalog of
> pre-designed jersey templates, organized by sport category, and completing a design request form that
> captures the team name, primary, secondary, and accent colors, font style, and estimated quantity. A
> dedicated file upload feature further allows customers to submit a logo or design reference file
> directly during the request process, ensuring that administrators have the visual reference needed to
> prepare the final design before an order is formally approved.

**Current (order management / chat sentence):**
> Beyond design configuration, JerseyConnect facilitates end-to-end order management. Users can place
> orders directly through the platform, select from available payment methods, and receive automated
> confirmation of their transactions. The system integrates a real-time chat feature powered by the
> WhatsApp Business API, enabling direct and documented communication between customers and
> administrators regarding order specifications, revisions, and concerns. This communication channel
> ensures that all design consultations are recorded and accessible within the platform, reducing the
> risk of miscommunication.

**Replace with:**
> Beyond design configuration, JerseyConnect facilitates end-to-end order management. Once a design
> request is reviewed and approved, customers settle a down payment through GCash by submitting a
> reference number and a screenshot of proof of payment, which administrators verify before the
> corresponding order is created. The system integrates an in-app messaging feature, enabling direct and
> documented communication between customers and administrators regarding order specifications,
> revisions, and concerns, without relying on a third-party messaging service. This communication
> channel ensures that all design consultations are recorded and accessible within the platform itself,
> reducing the risk of miscommunication and removing dependence on the availability or policies of an
> external provider.

**Current (notification / logistics sentence):**
> The notification system in JerseyConnect is designed with multi-level status tracking, informing
> customers whether their messages or order updates have been sent, delivered, or seen. This feature
> provides transparency and reduces uncertainty throughout the order fulfillment process. Additionally,
> the platform incorporates a logistics module that supports shipping to addresses beyond the immediate
> local area. The logistics module includes a delivery tracking system, structured address management,
> and an automated shipping fee computation engine based on destination and parcel weight, enabling the
> business to expand its market reach geographically.

**Replace with:**
> JerseyConnect also incorporates a status-tracking and alert system that keeps both customers and
> administrators informed throughout the process: design requests and orders move through clearly
> defined status stages, unread messages are flagged for the recipient, and administrators receive
> dashboard alerts for items that need attention, such as design requests awaiting review, GCash
> payments awaiting verification, and orders that have remained unshipped for an extended period. This
> feature provides transparency and reduces uncertainty throughout the order fulfillment process.
> Additionally, the platform incorporates a delivery module that supports shipping to addresses beyond
> the immediate local area. The delivery module includes structured, map-assisted address capture for
> pinpointing the exact delivery location, courier assignment, and a shipping fee and courier receipt
> recorded by the administrator for each order, enabling the business to expand its market reach
> geographically.

**Current (admin dashboard sentence):**
> For administrators, JerseyConnect provides a dedicated dashboard for managing orders, inventory, and
> user accounts. Administrators can monitor active orders, update inventory levels, manage customer
> records, and configure delivery settings from a centralized management interface. The platform thus
> serves a dual purpose: enhancing the customer-facing ordering experience while simultaneously
> improving back-end operational efficiency for the business.

**Replace with:**
> For administrators, JerseyConnect provides a dedicated dashboard for managing orders, the jersey
> template catalog, and user accounts. The dashboard surfaces weekly order counts and revenue with
> week-over-week comparisons, best-selling templates, and a list of items needing attention, such as
> pending design reviews, unverified GCash payments, and orders stuck in processing. From the same
> centralized interface, administrators can review and act on design requests, manage the jersey
> template catalog, assign couriers and record shipping details, and manage customer accounts. The
> platform thus serves a dual purpose: enhancing the customer-facing ordering experience while
> simultaneously improving back-end operational efficiency for the business.

---

## 3. Objectives of the Study

Replace the numbered list with:

1. Develop a jersey template catalog organized by sport, together with a design request form that
   captures the team name, color scheme, font style, and estimated quantity, and a file upload feature
   that allows users to submit a logo or design reference file during the request process;
2. Implement a secure order management system that enables customers to submit design requests, settle
   a down payment through GCash with proof-of-payment upload, and track their orders as they progress
   through defined status stages;
3. Integrate an in-app messaging feature to facilitate direct and documented communication between
   customers and administrators regarding design specifications and order concerns, without dependence
   on a third-party messaging service;
4. Design a status-tracking and alert system that displays the current stage of each design request and
   order and flags unread messages and pending administrative actions, to ensure communication
   transparency throughout the order process;
5. Develop a delivery module that supports shipping to destinations beyond local areas, incorporating
   map-assisted address capture, courier assignment, and per-order shipping fee and receipt recording;
6. Build an administrative dashboard that enables efficient management of orders, the jersey template
   catalog, and user accounts; and
7. Evaluate the system's usability, functionality, and overall performance based on feedback gathered
   from administrators and end-users using the ISO/IEC 25010 software quality model. *(unchanged)*

---

## 4. Statement of the Problem

Sub-question 2 — **current:**
> How should the system structure its order tracking catalog and configuration forms to capture complex
> design details—including sizing, dimensions, and fabric specifications—in a clear and organized
> manner without relying on live canvas drawing tools?

**Replace with:**
> How should the system structure its jersey template catalog and design request form to capture
> essential design details—including team name, color scheme, font style, and quantity—in a clear and
> organized manner without relying on live canvas drawing tools?

Sub-question 3 — **current:**
> How should the file management backend systematically handle, validate, and store high-resolution
> image and vector design uploads to prevent data corruption and ensure the integrity of submitted
> files?

**Replace with:**
> How should the file management backend systematically handle, validate, and store customer-submitted
> logo and design reference images to prevent data corruption and ensure the integrity of submitted
> files?

Sub-question 4 — **current:**
> How should the status tracking mechanism update clients on the progression of their orders across
> defined phases, such as Pending, Designing, Printing, Sewing, and Ready for Pick-up?

**Replace with:**
> How should the status-tracking mechanism update clients on the progression of their design requests
> and orders through defined phases, from initial review and down-payment verification, through
> production, to shipping and delivery?

Sub-question 5 — **current:**
> How should the administrative billing, booking analytics, and report generation modules compile
> financial and booking data to support informed decision-making by business owners?

**Replace with:**
> How should the administrative dashboard compile order, payment, and booking data—such as weekly sales
> trends, best-selling templates, and pending action items—to support informed decision-making by
> business owners?

---

## 5. Significance of the Study

Paragraph for prospective clients and sports teams — **current:**
> For prospective clients and sports teams, JerseyConnect delivers a transparent and convenient ordering
> experience. The step-by-step custom specification form and file upload subsystem allow clients to
> specify exact sizing, color, and material preferences and to upload team logos and complete group
> rosters directly during checkout, substantially reducing design miscommunications, while the
> multi-status notification system ensures that customers are consistently informed of their order
> progress. The availability of real-time communication via the WhatsApp Business API provides
> immediate access to administrative support throughout the ordering process.

**Replace with:**
> For prospective clients and sports teams, JerseyConnect delivers a transparent and convenient ordering
> experience. The design request form and file upload feature allow clients to specify their color and
> design preferences and to upload team logos directly during the request process, substantially
> reducing design miscommunications, while the status-tracking and alert system ensures that customers
> are consistently informed of their design request and order progress. The availability of in-app
> messaging provides direct access to administrative support throughout the ordering process, without
> requiring clients to leave the platform or rely on a separate messaging application.

*(In the paragraph for manufacturers/shop owners, just swap "logistics module" → "delivery module" for
terminology consistency; no other change needed there.)*

---

## 6. Scope and Limitations

**Scope — current:**
> JerseyConnect covers the complete jersey ordering lifecycle, from design configuration to final
> delivery. The system includes: (1) a pre-designed mockup template catalog paired with a step-by-step
> custom specification form—featuring dropdown selectors for sizes, necklines, colors, and
> materials—and a file upload subsystem that allows customers to submit vector graphics, team logos, and
> design references directly at checkout; (2) an order placement and payment management module; (3) a
> real-time chat feature integrated via the WhatsApp Business API for customer-to-administrator
> communication; (4) a notification system supporting sent, delivered, and seen status tracking for
> messages and order updates; (5) a logistics module capable of handling deliveries to addresses beyond
> the local area, with delivery tracking, address management, and automated shipping fee computation;
> and (6) an administrative dashboard for managing orders, inventory, and user accounts. The platform
> functions strictly as a streamlined e-commerce order placement, design configuration tracker, booking
> ledger, and administrative management solution; design processing relies entirely on the structured
> specification form—through which customers indicate exact text, numbers, size breakdowns, color
> schemes, and collar choices—paired with the file upload utility, rather than on any interactive
> live-canvas editing, drag-and-drop graphics tool, or live 3D rendering capability.

**Replace with:**
> JerseyConnect covers the complete jersey ordering lifecycle, from design configuration to final
> delivery. The system includes: (1) a jersey template catalog organized by sport, paired with a design
> request form that captures the team name, color scheme, font style, and estimated quantity, and a file
> upload feature that allows customers to submit a logo or design reference file directly with their
> request; (2) a design request review workflow and a GCash-based payment process with
> proof-of-payment upload and administrator verification; (3) an in-app messaging feature, contained
> entirely within the platform, for customer-to-administrator communication; (4) a status-tracking and
> alert system covering design request stages, order stages, and unread messages; (5) a delivery module
> capable of handling deliveries to addresses beyond the local area, with map-assisted address capture,
> courier assignment, and per-order shipping fee and receipt recording; and (6) an administrative
> dashboard for managing orders, the jersey template catalog, and user accounts. The platform functions
> strictly as a streamlined e-commerce order placement, design request tracker, booking ledger, and
> administrative management solution; design processing relies entirely on the design request
> form—through which customers indicate the team name, color scheme, font style, and quantity—paired
> with the file upload feature, rather than on any interactive live-canvas editing, drag-and-drop
> graphics tool, or live 3D rendering capability.

**Limitations — current:**
> The following limitations apply to JerseyConnect: (1) the platform is designed for web-based access
> only and does not include a native mobile application; (2) the real-time chat feature is dependent on
> the WhatsApp Business API, and its functionality is subject to the terms, policies, and availability
> of the WhatsApp platform; (3) delivery tracking within the logistics module relies on integration with
> third-party courier services, and the accuracy of tracking data is contingent on the information
> provided by these external providers; (4) the system does not currently support multi-vendor
> functionality and is designed for single-business deployment; (5) the system evaluation is limited to
> user acceptance testing conducted with a defined sample of administrators and end-users within the
> target locale; and (6) the platform does not provide live rendering, drag-and-drop editing, or
> automated visualization of uploaded designs onto jersey mockups, as design verification is performed
> manually by administrators based on the submitted specification form and uploaded files.

**Replace with:**
> The following limitations apply to JerseyConnect: (1) the platform is designed for web-based access
> only and does not include a native mobile application; (2) the in-app messaging feature is not
> push-based, so users need to open or refresh the relevant page to see new messages rather than
> receiving an instant notification; (3) order and delivery status are updated manually by
> administrators rather than through live integration with third-party courier tracking systems, so
> status accuracy depends on timely administrator updates; (4) shipping fees are entered manually by the
> administrator for each order rather than computed automatically from distance or parcel weight; (5)
> the system does not currently support multi-vendor functionality and is designed for single-business
> deployment; (6) the system evaluation is limited to user acceptance testing conducted with a defined
> sample of administrators and end-users within the target locale; and (7) the platform does not provide
> live rendering, drag-and-drop editing, or automated visualization of uploaded designs onto jersey
> mockups, as design verification is performed manually by administrators based on the submitted design
> request form and uploaded files.

---

## 7. Definition of Terms

Replace/rename the following entries (keep the rest — Admin Dashboard, ISO/IEC 25010, User Acceptance
Testing — as is, or lightly enhance Admin Dashboard as shown):

**Admin Dashboard** *(optional light enhancement)*:
> A centralized web-based interface within JerseyConnect that allows system administrators to manage
> orders, the jersey template catalog, design requests, and user accounts, and that surfaces sales
> analytics and pending-action alerts.

**Custom Specification Form → rename to "Design Request Form":**
> Design Request Form. An order configuration form within JerseyConnect that captures a client's design
> requirements—including team name, color scheme, font style, and estimated quantity—together with an
> uploaded logo or design reference file, eliminating the need for live canvas drawing tools.

**Delivery Tracking:**
> Delivery Tracking. A feature within the delivery module of JerseyConnect that enables customers and
> administrators to monitor the status of an order—such as shipped, delivered, or completed—as it is
> updated by the administrator, together with the assigned courier and recorded shipping details.

**File Upload Subsystem:**
> File Upload Subsystem. A component of JerseyConnect's design request process that allows customers to
> submit a logo or design reference image, systematically validating, processing, and storing the
> upload to prevent data corruption and ensure file integrity.

**Logistics Module → rename to "Delivery Module":**
> Delivery Module. A system component of JerseyConnect responsible for managing the shipping and
> delivery of ordered jerseys, encompassing map-assisted address capture, courier assignment, and
> shipping fee and receipt recording.

**Mockup Template Catalog → rename to "Jersey Template Catalog":**
> Jersey Template Catalog. A curated collection of pre-designed jersey layouts, organized by sport,
> presented within JerseyConnect from which clients may select a base design prior to completing the
> design request form.

**Notification System → rename to "Status-Tracking and Alert System":**
> Status-Tracking and Alert System. A feature of JerseyConnect that displays the current stage of a
> design request or order, flags unread messages, and surfaces pending administrative actions—such as
> design requests awaiting review and GCash payments awaiting verification—ensuring transparency
> throughout the order fulfillment process.

**Real-Time Chat → rename to "In-App Messaging":**
> In-App Messaging. A communication feature built into JerseyConnect that enables documented messaging
> between customers and administrators for order-related consultations, contained entirely within the
> platform.

**Shipping Fee Computation → rename to "Shipping Fee Recording":**
> Shipping Fee Recording. A step within the delivery module in which the administrator enters the
> applicable shipping cost for an order, together with the courier receipt details, once the order is
> dispatched.

**WhatsApp Business API — delete this entry entirely** (no longer applicable).

---

## 8. Chapter 2 — Related Literature

**Chaffey (2022) paragraph — current closing sentence:**
> These findings provide direct justification for JerseyConnect's structured design specification
> workflow and file upload subsystem, integrated WhatsApp Business API chat, and multi-status
> notification system.

**Replace with:**
> These findings provide direct justification for JerseyConnect's structured design request workflow
> and file upload feature, in-app messaging, and status-tracking and alert system.

**Kotler, Keller, and Chernev (2022) paragraph — current closing sentence:**
> ...providing empirical grounding for the sent, delivered, and seen status indicators incorporated into
> JerseyConnect's notification system.

**Replace with:**
> ...providing empirical grounding for the status-tracking and alert indicators incorporated into
> JerseyConnect's messaging and order-tracking features.

**Chopra and Meindl (2021) paragraph — current closing sentence:**
> Their supply chain optimization framework directly informed the design of JerseyConnect's logistics
> module, which addresses all three dimensions through its delivery tracking feature, automated shipping
> fee computation engine based on destination and parcel weight, and structured address management
> system.

**Replace with:**
> Their supply chain optimization framework directly informed the design of JerseyConnect's delivery
> module, which addresses these dimensions through its order status-tracking feature,
> administrator-recorded shipping fees, and structured, map-assisted address capture system.

---

## 9. Chapter 2 — Related Studies

**Ramirez, Flores, Castillo, and Navarro (2023) — WhatsApp API study — current closing sentence:**
> Their technical documentation of API authentication, webhook configuration, and message template
> management provided a validated implementation reference for the real-time chat feature integrated
> into JerseyConnect.

**Replace with:**
> Their findings on the impact of direct, in-platform communication on customer responsiveness informed
> the researchers' decision to build direct, documented messaging into JerseyConnect; the final
> implementation, however, uses an in-app messaging module rather than a third-party API, keeping all
> correspondence self-contained within the platform and independent of an external provider's terms and
> availability.

**Villafuerte, Andres, Samson, and Cruz (2022) — automated fee study — current closing sentence:**
> The system architecture and evaluation findings of Villafuerte et al. (2022) served as a primary
> technical reference for the design and implementation of JerseyConnect's logistics module,
> particularly its shipping fee computation engine and delivery tracking feature.

**Replace with:**
> The system architecture and evaluation findings of Villafuerte et al. (2022) informed the design of
> JerseyConnect's delivery module, particularly its structured address capture and courier receipt
> recording; unlike Villafuerte et al.'s automated fee engine, JerseyConnect's current implementation has
> administrators record the shipping fee per order, with automated computation identified as a direction
> for future enhancement (see Chapter 5).

**Ocampo, Cabrera, Lim, and Fernandez (2023) — notification gap study — current closing sentence:**
> JerseyConnect directly addresses this identified gap through its comprehensive notification system,
> which provides customers with real-time visibility into the status of all communications and order
> updates.

**Replace with:**
> JerseyConnect addresses this identified gap through its status-tracking and alert system, which gives
> customers visibility into the status of their design requests, messages, and orders as these are
> updated by administrators.

**Macaraeg, Sison, Delos Santos, and Aquino (2024) — final sentence:** just swap "logistics module" →
"delivery module"; no other change needed.

---

## 10. Chapter 2 — Conceptual Framework (Input / Process / Output paragraphs)

**Input component — current:**
> ...specifically the integration of a real-time chat feature via the WhatsApp Business API, the
> development of a multi-status notification system with sent, delivered, and seen indicators, and the
> inclusion of a logistics module—...

**Replace with:**
> ...specifically the integration of a direct communication feature between clients and administrators,
> the development of a status-tracking and alert system, and the inclusion of a delivery module—...

**Process component — current:**
> ...which produced the core system modules including the mockup template catalog, custom specification
> form, and file upload subsystem, order and payment management module, WhatsApp Business API-integrated
> chat, multi-status notification system, logistics module with delivery tracking and automated shipping
> fee computation, and the administrative dashboard;...

**Replace with:**
> ...which produced the core system modules including the jersey template catalog, design request form,
> and file upload feature, GCash-based order and payment management module, in-app messaging,
> status-tracking and alert system, delivery module with map-assisted address capture and shipping fee
> recording, and the administrative dashboard;...

**Output component — current:**
> The primary output is the deployed JerseyConnect web platform, which delivers a form-driven jersey
> design configuration workflow—comprising the mockup template catalog, custom specification form, and
> file upload subsystem—integrated WhatsApp chat and multi-status notifications, and a fully functional
> logistics module encompassing delivery tracking, structured address management, and automated
> shipping fee computation.

**Replace with:**
> The primary output is the deployed JerseyConnect web platform, which delivers a form-driven jersey
> design configuration workflow—comprising the jersey template catalog, design request form, and file
> upload feature—integrated in-app messaging and a status-tracking and alert system, and a fully
> functional delivery module encompassing courier assignment, structured address capture, and shipping
> fee and receipt recording.

---

## Important — one thing text edits can't fix

**Figure 1 (Conceptual Framework diagram, embedded as an image in Chapter 2)** almost certainly still
shows the old module names visually (it was built before these corrections). Text edits in this document
won't change what's drawn inside that picture — you'll need to update that figure separately (in
whatever tool you built it in — PowerPoint, Canva, draw.io, etc.) so its labels match the corrected
module list above: **jersey template catalog, design request form, file upload feature, GCash-based
payment, in-app messaging, status-tracking and alert system, delivery module** (map-assisted address
capture, courier assignment, shipping fee recording), and **administrative dashboard**. Say the word if
you'd like a draw.io source file for a redrawn version of that IPO diagram.

# Jihan Mart Admin Backend: Build Spec

This is the master spec for the Jihan Mart backend. Part A lists the changes agreed with the owner on 2026-10-24; **where Part A and Part B disagree, Part A wins.** Part B is the original build prompt (written by inspecting the reference admin panel page by page).

---

## Part A: Amendments (these override Part B)

### A1. Stack and hosting
- Laravel (latest stable that the installed PHP supports; Part B says 11), PHP 8.3+, MySQL 8 or MariaDB 10.11+.
- Target host is a **Hostinger VPS** (Ubuntu, Nginx, PHP FPM, MySQL, Supervisor for the queue). It must still run on ordinary cPanel hosting (database queue driver + cron), so no hard Redis/Docker dependency.
- Local development on Windows uses portable PHP, Composer and MariaDB (no installers).
- Code lives in `backend/` inside this repository. The current static storefront (`index.html`, `app.js`, `styles.css`, `hero3d.js`) moves into Laravel's `public/` folder (or a Blade view) so storefront and API share one domain and no CORS setup is needed.

### A2. Storefront API (missing from Part B, required)
The existing frontend must stop reading `data/catalog.js` and use a JSON API under `/api/v1` (read endpoints cached; all money in BDT; Bangla and English names where available):

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/settings` | logo, site name, contact info, socials, free shipping threshold, payment methods, manual payment numbers, feature flags |
| GET | `/api/v1/categories` | category tree with product counts, icons, cover image |
| GET | `/api/v1/products` | filters: `category`, `sub`, `q`, `max_price`, `sort` (pop, low, high, new), `page`, `per_page`; returns card data incl. rating avg/count, sales, stock, discount |
| GET | `/api/v1/products/{id or slug}` | detail incl. gallery, variants, tiers, payment policy, description, approved reviews summary |
| GET | `/api/v1/collections`, `/api/v1/flash-deals` | active promos with products and end time |
| GET | `/api/v1/locations` | districts (states) and areas (cities) with shipping cost |
| POST | `/api/v1/coupons/validate` | code + cart → discount or error |
| POST | `/api/v1/abandoned-carts` | capture on phone input (upsert by phone + session) |
| POST | `/api/v1/orders` | checkout and "Buy now"; validates stock, payment policy, coupon, shipping; returns order code |
| GET | `/api/v1/orders/track?code=&phone=` | public tracking (status timeline only, no personal data beyond what the caller supplied) |
| GET/POST | `/api/v1/reviews` | list approved reviews (store wide or `product_id`), submit new review |
| POST | `/api/v1/reviews/{id}/helpful` | helpful vote (one per device/IP) |
| POST | `/api/v1/subscribers` | newsletter phone or email |

Rate limit the POST endpoints and protect them with CSRF (same domain) or a honeypot + throttle.

### A3. Reviews (replaces Part B 2.10)
Table `reviews`: `product_id` nullable (null = store review), `user_id` nullable, `name`, `phone` nullable, `rating` 1..5, `comment`, `photo_upload_id` nullable, `helpful_count`, `status` (pending, approved, rejected), `admin_reply` nullable, `ip`, timestamps.
Admin page `/admin/reviews`: filters (status, rating, product, has photo, date), columns (#, Product or "Store", Customer, Rating, Comment, Photo, Helpful, Status switch Approve/Reject, Reply, Delete). New reviews are **pending** until approved (setting: auto approve on/off). Storefront shows only approved reviews.

### A4. Scope trimming
Build order: Phases 1, 2, 3 → Storefront API (A2) and frontend connection → Phases 4, 5 → launch → Phases 6, 7, 8 → Phase 9.
Skip or keep switched off unless the owner asks: Vendor system, Classified products, Wallet, Pickup points, multi currency (BDT only), Zones, Carrier wise shipping, S3, Firebase, Notice (reseller), Arabic/Hindi (English + Bangla only), foreign payment gateways (PayPal, Stripe, Mercadopago, Iyzico, Instamojo, PayStack, Payhere, Ngenius, VoguePay, RazorPay, Authorize.Net, Payku).

### A5. Courier fraud check
Pathao and Steadfast may not expose customer delivery history through an official API. Before coding the fraud check, confirm the endpoints in their current official docs. If unavailable, use a Bangladeshi fraud checker service or our own store history only, behind the same `FraudCheckService` interface.

### A6. Owner inputs needed later
Pathao and Steadfast merchant API credentials; bKash, Nagad or SSLCommerz merchant accounts (sandbox first); Jihan Mart contact details, logo and real product list.

---

## Part B: Original build prompt

### Build Prompt: Jihan Mart Admin Backend
You are building the complete admin backend for Jihan Mart, a Bangladeshi ecommerce store that sells cosmetics, jewellery, accessories and gadgets with Cash on Delivery as the main payment method. Replicate the feature set, page structure, fields and workflows described below exactly. This spec was written by inspecting a live, working admin panel page by page, so every field name, column, filter and button listed here exists in production and should exist in Jihan Mart too.

Work in phases (see the end of this file). After each phase, run migrations, seed demo data, and verify every page loads and every form saves.

### 0. Tech stack and global rules
* Framework: Laravel 11, PHP 8.2+, MySQL 8. It must deploy on ordinary cPanel shared hosting (no Docker requirement, queue can run on `database` driver with a cron).
* Admin UI: Blade + Tailwind CSS + Alpine.js. Clean modern dashboard look: collapsible left sidebar with icons and nested dropdown menus, top bar with: sidebar toggle, "View website" icon, Create Order button, Clear Cache button, notification bell, language switcher, admin avatar with Profile and Logout.
* Admin URL prefix: `/admin`. Admin login at `/login` with a configurable background image.
* Bangla support: UI strings translatable (English and Bangla at minimum). Currency symbol ৳. All dates shown as `DD-MM-YYYY hh:mm AM`. Timezone Asia/Dhaka by default.
* Phone format: Bangladeshi mobile `01XXXXXXXXX`. Write one helper `cleanBdPhone()` that strips `+88`, `88`, spaces and dashes so the same customer always matches.
* Settings storage: one `business_settings` table (`type`, `value`, `lang`). Almost every settings page posts an array of `types[]` plus values to a single `business-settings/update` endpoint. Secrets that belong in `.env` (SMTP, payment keys, courier keys, social login keys) are written to `.env` through an `env_key_update` endpoint and never shown back in full.
* Media library ("Uploaded Files"): every image field in the whole admin is a hidden input holding upload IDs, opened through a reusable file picker modal (browse library, search, sort, upload new, select one or many). Convert uploads to `.webp` automatically. Image size hints are shown on each field (examples below).
* Toggles: boolean flags on list pages (Published, Featured, Todays Deal, etc.) are inline switches that save by AJAX instantly with a toast notification.
* Deletes: always go through a shared "Delete Confirmation" modal (Cancel / Delete).
* Pagination: 15 rows per page on list pages unless noted.
* Bulk actions: list pages with a checkbox column have a "Bulk Action" dropdown (at least "Delete selection").
* Permissions: every admin route is protected by a named permission (Spatie Laravel Permission). See section 13.
* Activity log: every change to an order is written to `order_activities` (see section 3.6).

### 1. Dashboard (`/admin`)
Top card: "Dashboard" title with a date preset select (Today, Yesterday, Last 7 Days, This Month, Last Month, All Time, Custom Range), a date range picker and a Filter button. Default is This Month. Every number below respects the selected range.

KPI cards (colored gradient cards):
* Total Sales Revenue (filtered range)
* Today's Revenue with "N Orders Today"
* This Month Sales with month label
* Total Orders with "View All Orders" link

Order Status Summary card: total orders in range plus a "Manage Orders" link. One tile per delivery status showing amount, order count and percent of total: Pending, Confirmed, Picked Up, On The Way, Delivered, Phone Off, Not Receive Call, Returned, Cancelled. Each tile links to the filtered order list.

Small stat tiles: Total Customers, Active Products, Categories, Low Stock Alert (count of products at or below threshold).

Charts:
* Monthly Sales Overview for the current year (revenue trend line or bar)
* Realtime Analytics (orders and visitors in the last minutes, simple polling is fine)
* Last 7 Days Sales (daily revenue bars)
* Category Wise Sales (pie or bar)
* Category Wise Stock (bar)

Lists:
* Recent Orders table: Order Code, Customer (avatar initial + name), Amount, Delivery Status badge, Payment badge, Options (view). Link "View All Orders".
* Low Stock Warning paginated list: product thumbnail, name, "N in stock" (negative stock must display in red, e.g. "‑3 in stock").
* Top Selling Products: thumbnail, name, "N Sold", price.

Footer: "© Jihan Mart vX.Y".

### 2. Products

#### 2.1 Add New Product (`/admin/products/create`) and Edit (`/admin/products/{id}/edit`)
One long page split into cards, with a right side column for toggles. Bottom buttons: Save and Unpublish, Save and Publish.

Product Information
* Product Name * (text)
* Category * (multi select with search, nested categories shown with "‑‑" indent)
* Brand (select)
* Unit (text, placeholder "Unit (e.g. KG, Pc etc)")
* Weight in Kg (number)
* Minimum Purchase Qty * (number, default 1)
* Tags (tag input, "Type and hit enter to add a tag")
* Suggestion Category (select; used to show "related products" on the product page)
* Barcode * (number; auto generate if empty)

Product Images
* Gallery Images (600x600), multiple, from media library
* Thumbnail Image (300x300)

Product Videos
* Video Provider (Youtube, Dailymotion, Vimeo)
* Video Link

Product Variation
* Colors (multi select from Colors list, with a toggle to enable)
* Attributes (multi select: Color, Size, Shade, Design, Pcs, Fragrance, Variation, Flavor, or whatever exists). After choosing attributes, show a value picker per attribute. Then auto generate a variant combination table: Variant name, Variant price, SKU, Quantity, Photo. Each combination is its own stock row.

Product price + stock
* Unit price * (number)
* Sale price (number)
* Discount Date Range (date range picker)
* Discount * (number) + Discount type (Flat / Percent)
* Quantity * (current stock, hidden when variants exist)
* SKU
* External link + External link button text (optional affiliate style button)

Quantity Based Tiered Pricing
* Repeating rows: Min Quantity (e.g. 2, 5, 10) and Tier Unit Price. "Add Tier" button, remove per row.

Product Description
* Rich text editor (headings, bold, lists, links, images, video embed, code view)

PDF Specification
* PDF file from media library

SEO Meta Tags
* Meta Title, Meta Description, Meta Image

Right column cards
* Payment Policy: Payment Requirement select: "Full Cash On Delivery (100% COD)", "Partial Advance + COD", "100% Prepayment Only (No COD)". When Partial: Advance Type (Fixed Amount / Percentage) and Advance Amount. The storefront checkout must enforce this per product.
* Featured switch
* Todays Deal switch
* Free Shipping switch
* Direct Order (Details Page) switch, default on. When on, the product page shows a quick order form (name, phone, address) instead of only Add to Cart.
* Flash Deal: Add To Flash (select an active flash deal), Discount, Discount Type (Flat / Percent)
* Estimate Shipping Time: Shipping Days
* VAT and Tax: for each tax type, value + Flat/Percent

#### 2.2 All Products (`/admin/products/all`)
Filters row (GET form): Status (All Status, Approved, Published, Unpublished), Variant (All, Simple, Variable), Flag (Landing Page, Featured, Today Deal), Sort By (Num of Sale high/low, Stock high/low, Base Price high/low), search box "Type and Enter".

Header buttons: Add New Product, Bulk Action (Delete selection).

Table columns:
* checkbox
* Name: thumbnail + name
* Info: Num of Sale: N times, Base Price, Sale Price, SKU, Rating
* Collection: one checkbox per collection (e.g. Free Delivery, Shop 2000 Get Free Delivery, Up To 50% Off, Mystery Box, 99 Tk Offer). Ticking assigns the product to that collection instantly by AJAX.
* Total Stock: per variant breakdown (variant name + qty), red when low or negative
* Todays Deal (switch), Published (switch), Featured (switch), Free Shipping (switch), Direct Order (switch)
* Options: View (opens storefront product page in new tab), Edit, Duplicate, Delete

#### 2.3 Bulk Import (`/admin/product-bulk-upload/index`)
Instruction card with steps, buttons Download CSV (template), Download Category (id list), Download Brand (id list). Upload form: Choose File (csv/xlsx) and Upload CSV. Validate rows, report errors per row, create products.

#### 2.4 Bulk Export (`/admin/product-bulk-export`)
Export all products (or filtered) to Excel/CSV with all main fields.

#### 2.5 Category (`/admin/categories`)
List: search, Add New category. Columns: #, Name, Parent Category, Order Level, Level, Featured (switch), Options (Edit, Delete).

Create/Edit form: Name, Parent Category (No Parent or any category, nested), Ordering Number, Banner (200x200), Image (306x306), Single Grid (switch), Mega menu (switch), Mega Section (1 to 4), Meta Title, Meta Description. Slug auto generated.

#### 2.6 Collections (`/admin/collection/index`)
Promotional product groups shown on the storefront. List columns: #, Name, Start Date, End Date, Image, Status, Options (Edit, Delete). Form: Name, Start Date (datetime), End Date (datetime), Image (416x182), Status (Active / Inactive). Products are attached from the All Products page checkboxes.

#### 2.7 Brand (`/admin/brands`)
Same page holds list (left) and "Add New Brand" form (right). List: #, Name, Logo, Options. Form: Name, Logo (120x80), Meta Title, Meta Description.

#### 2.8 Attribute (`/admin/attributes`)
List + add form on the same page. Columns: #, Name, Values (chips), Options (Attribute values, Edit, Delete). Attribute values page (`/admin/attributes/{id}`): table of values with add value form (Value) and edit/delete.

#### 2.9 Colors (`/admin/colors`)
List + add form. Columns: #, Name, Options. Form: Name, Color Code (hex). Plus a "Color filter activation" switch that enables color filter on the storefront.

#### 2.10 Product Reviews (`/reviews` or `/admin/reviews`)
Filter by Rating (high to low / low to high). Columns: #, Product, Product Owner, Customer, Rating, Comment, Published (switch). **(Replaced by A3.)**

### 3. Sales (orders): the most important module

#### 3.1 Order statuses
Delivery status enum (exact order and labels): `pending` Pending, `confirmed` Confirmed, `picked_up` Picked Up, `on_the_way` On The Way, `delivered` Delivered, `phone_off` Phone Off, `not_receive_call` Not Receive Call, `returned` Returned, `cancelled` Cancel.

Payment status: `unpaid` Un‑Paid, `partial_paid` Partial Paid, `paid` Paid.

Order sources: website checkout, product page direct order, landing page order, admin manual order (POS), converted abandoned cart. Store `source` and `landing_domain` on the order.

Order code is a running number (e.g. 3588) separate from the DB id. Public order links use an encrypted id.

#### 3.2 All Orders (`/admin/all_orders`)
Header: title "All Orders" with total count badge, buttons Create Order, All Confirmed Checklist (N), Bulk Action dropdown.

Status pills (one click filters, each with live count): All, Pending, Confirmed, Picked Up, On The Way, Delivered, Phone Off, Not Receive Call, Returned, Cancel.

Filter bar: Search ("Code, Name, Phone, Email..."), Date Range picker, Payment (All, Paid, Partial Paid, Un‑Paid), District (from shipping states, e.g. ঢাকার ভিতরে, ঢাকার বাহিরে, ঢাকার উপশহর), Area / Thana (loaded by AJAX from the chosen district), Apply Filter button. Info line: "Showing all latest orders. Use filters above to narrow down results."

Bulk Action dropdown:
* Delete selection
* Bulk send courier
* Bulk Invoice Print (opens one printable page with all selected invoices)
* Packaging Checklist (Selected): only confirmed orders allowed, non confirmed are skipped with a warning
* Packaging Checklist (All Confirmed) N

Table columns:
* checkbox (stores the row's delivery status as a data attribute)
* Order Code, with a "New" badge for orders not yet opened
* Total Item
* Customer: name, phone as a click to call link (`tel:`), a call counter badge that opens Call History, district/area text
* Fraud: a colored courier badge button (green safe, yellow medium, red risky) that opens the Fraud Check modal. Load badges for all visible rows in one batch request.
* Date and Time
* Amount
* Delivery Status: inline select with all statuses. Changing it saves immediately by AJAX, logs activity, shows toast.
* Comment: "Add Comment" button opening the comment modal, last comment shown
* Payment method
* Payment Status badge
* Options: Actions dropdown with View, Download Invoice, Send Courier, Edit, Activity Log, Delete

Rows are clickable to expand on narrow screens (responsive table that collapses columns into a "+" expander on mobile).

Modals on this page:
1. Call History: shows customer name, phone, order code, total calls, a Call Now button (tel link that also logs a call), "Caller Breakdown (who called how many times)", note input ("Add a note for this call, e.g. Confirmed order, Will call later, Not answered") with Add Log / Note, and a table: #, Called By (staff), Phone Number, Date and Time, Note / Remarks.
   * Clicking the phone number anywhere instantly logs a call (`POST /admin/orders/log-call` with order_id, phone, call_status=called), increments the badge optimistically, and returns total_calls.
   * `POST /admin/orders/add-call-note` saves a note.
2. Order Comment: textarea, Cancel, Save Comment (`POST /admin/orders/update_admin_note`).
3. Customer Fraud Check: "Refresh Live" button. Sections:
   * Combined Multi Courier Delivery Trust Score: overall risk badge and title (e.g. "Reliable Customer"), Total BD Couriers parcels, Delivered, Cancelled/Returned, success rate.
   * Steadfast Courier statistics: total, delivered, cancelled, success rate, risk badge, message.
   * Pathao Courier statistics: same fields.
   * Store history: this customer's past orders in our own DB by status. Endpoint `GET /admin/orders/customer-fraud-check?phone=&order_id=&force_refresh=` returns `{success, data:{overall, steadfast, pathao, store}}`. Cache each phone's result (e.g. 24h) and only hit couriers on force refresh. Batch endpoint `customer-fraud-check-batch` for the list badges.
4. Order Activity and Audit Log: timeline (see 3.6).
5. Delete confirmation.

#### 3.3 Order Details (`/admin/orders/{encrypted_id}`)
Top buttons: Send Courier, Edit Order, Re‑Print Invoice.

Control row:
* Payment Status select (Unpaid, Paid, Partial Paid), saves by AJAX (`update_payment_status`)
* Delivery Status select (all statuses), saves by AJAX (`update_delivery_status`)
* Tracking Code (optional) input, saves on blur (`update_tracking_code`)
* Comment textarea + Save Comment

Customer block: name, phone with call link and call counter, fraud badge (e.g. "100% Safe"), address with district/area.

Order info block: Order #, Order Status, Order Date, Total amount, Payment method, Additional Info (customer note).

Items table: #, Photo, Description (name, variant, SKU), Delivery Type, Qty, Price, Total.

Totals: Sub Total, Shipping, Discount, Coupon, Advance Paid, TOTAL, TOTAL DUE.

Order Activity and History Log card: "Track all staff actions, courier dispatches, edits and status changes", activity count, "View Modal" button, timeline.

#### 3.4 Create Order (`/admin/orders/create`) and Edit Order (`/pos-order-edit/{id}`)
A POS style screen, two columns.

Left (product picker): search "Search by Product Name, SKU or Barcode...", Category filter, Brand filter, product grid cards (image, name, price, stock). Clicking a card adds it to cart; variant products open a variant chooser.

Right (cart and customer):
* Select Customer: searchable select "Walk In Customer / Guest" or existing customers (name + phone), plus quick add new customer (name, phone, district, area, address)
* Shipping address with District and Area selects (area loaded from district) which sets shipping cost automatically
* Cart lines: product, variant, qty stepper, unit price (editable), line total, remove
* Advance Amount + Advance Method (Cash on Delivery, Bkash, Nagad, Rocket), Sender Phone (optional), Transaction ID
* Order Instructions / Notes (optional)
* Discount button: amount + Flat/Percent
* Edit shipping cost button
* Summary: Sub Total, Shipping, Discount, Advance, Total
* Place and Create Order (edit page: Update Order). Link "Back to All Orders".

Stock is deducted when the order is created and restored when it is cancelled or returned.

#### 3.5 Invoices and checklists
* Invoice (`/ecommerce-invoice/{id}` and bulk `/ecommerce-invoice?id[]=..`): printable A4/A5. Header with logo, shop name, address, phone, email (from POS Configuration settings). Invoice No, date, customer name, phone, address. Items: Image, Product Name (with SKU), Quantity, Unit Price, Subtotal. Totals: Total Quantity, Sub Total, Shipping Cost, Discount, Grand Total, Advance, TOTAL DUE. Footer text from settings ("Thanks for shopping from Jihan Mart"). Toggle settings: show SKU, show image on invoice.
* Packaging Checklist (`/admin/orders/packaging-checklist?id[]=..`): picking list aggregated by product for confirmed orders. Columns: SKU / Barcode, Image, Product Details (name + variant), Order Numbers (all order codes that need it), Quantity (total). Buttons Close and Print Checklist.

#### 3.6 Order activity log
Table `order_activities`: order_id, user_id (staff), action_type, title, old_value, new_value, note, ip, user_agent, created_at. Action types and timeline dot colors: `status_change` blue, `payment_status_change` green, `courier_sent` amber, `order_edited` cyan, `call_logged` and `call_note` green, `note_updated` slate. Show old value struck through → new value. Endpoint returns activities as JSON for the modal.

Table `order_call_logs`: order_id, user_id, phone, call_status, note, created_at.

#### 3.7 Abandoned Carts (`/admin/abandoned-carts`)
Capture checkout form data (name, phone, address, district, area, cart items) as soon as the customer types a phone number on checkout or a landing page, before they submit. If they later complete the order, mark the cart as converted.

Filter: search "Type name, phone, address", Filter.

Columns: #, Customer Name, Phone, Address, District / Thana, Cart Items ("N Item(s)" button opens a Cart Items modal with product list), Source (website / landing page domain), Status (Pending / Converted), Date and Time, Options: Convert to Order (confirm modal "Confirm Conversion to Order", then creates a real pending order), Delete.

#### 3.8 Status shortcut menu items
Sidebar under Sales: Create Order, All Orders, Abandoned Carts, Pending Orders, Confirmed Orders, Picked‑up Orders, On The Way Orders, Delivered Orders, Cancelled Orders. Each status item is the All Orders page with the status pre filtered.

### 4. Landing Page builder
Lets the store run separate single product or campaign landing sites on their own domains or subdomains that place orders into the same backend.

#### 4.1 Home Pages (`/admin/landing-home-page`)
List: SL, Domain, Action (Edit, Delete). Button Create.

Create/Edit form sections:
* Domain Name (e.g. `example.com` or `sub.example.com`)
* Logo
* Home Slider: repeating image + link rows ("Add New")
* Home Video 1: video link
* Product features: repeating image + text rows
* Home Banner 1 (max 1), Home Banner 2 (max 1), Home Banner 3 (max 1): image + link
* Home Two Banner (max 2), Home Three Banner (max 3): image + link rows
* Home Products: multi select products to show
* Home Content 1, 2, 3: title + rich text
* About Us information: rich text
* Contact Information: Email, Phone, Address

The public side: a lightweight landing template that resolves the current request host to the matching landing record and renders these sections with an order form on the same page.

#### 4.2 Landing Page All Orders (`/admin/landing-page-order-index`)
Filters: Delivery Status, Payment Status (Paid, Partial Paid, Un‑Paid), date, search "Type Order code and hit Enter", Bulk Action, Filter. Columns: checkbox, Order Code, Domain, Total Item, Customer, Amount, Delivery Status, Payment method, Payment Status, options.

### 5. Customers (`/admin/customers`)
Search "Type email or name and Enter", Bulk Action (Delete selection). Columns: checkbox, Name, Email Address, Phone, Register At, Options (view details with order history, ban/unban, Delete). Customers are auto created from guest orders by phone number.

### 6. Uploaded Files (`/admin/uploaded-files`)
Media library grid. Sort: newest, oldest, smallest, largest. Search "Search your files". Upload New File (drag and drop, multiple, progress bar). Each card: preview, file name, extension, size, menu with Details info, Download, Copy link, Delete. Upload page at `/admin/uploaded-files/create`.

### 7. Reports

#### 7.1 Date Wise Sale Report (`/admin/date-wise-sale-report`)
Filters: Date Range + quick buttons (Today, Yesterday, Last 7 Days, This Month, Last Month, All Time); Delivery Status (Delivered default, All, Pending, Confirmed, Picked Up, On The Way, Cancelled); Group By (By Invoice / Item detailed, By Date and Product aggregated, By Product total period); Category; Payment Status (All, Paid, Unpaid); search "Product name / Order code".

Summary cards: total sales amount, pieces sold, number of orders, number of products.

Table "Detailed Sales Report (Itemized by Invoice and Product)", with "Status: Delivered, Showing 1‑20 of N": #, Date, Invoice No (links to order), Image, Product Name (links to storefront), Variation, Category, Pieces Sold (পিস), Avg Unit Price, Total Sales (৳), Payment.

Buttons: Export to Excel / CSV, Print Report, View Sales Summary.

#### 7.2 Sale Summary Report (`/admin/sale-summary-report`)
Filters: Date Range + quick buttons, View Mode (Daily Summary day by day / Monthly Summary month by month), Delivery Status, Payment Status, Platform (All, Reseller, Inhouse).

Summary cards: revenue, orders, pieces sold, average order value. Chart "Delivered Revenue and Pieces Sold Trend".

Table "Daily Sales Summary Breakdown": #, Date (with weekday), Orders, Invoice No(s) (each links to order), Pieces Sold, Product Sales, Shipping, Discount, Grand Total (৳), AOV, Top Selling Product (name + pcs), Action (link to Date Wise report for that day).

Buttons: Export to Excel / CSV, Print Summary, Product‑Wise Details.

#### 7.3 Product Sale (`/admin/in_house_sale_report`)
Filter by category. Columns: #, Image, Product Name, Num of Sale.

#### 7.4 Products Stock (`/admin/stock_report`)
Filter by category. Columns: Image, Product Name, Stock.

### 8. Marketing
* Flash deals (`/admin/flash_deals`): list columns #, Title, Banner, Start Date, End Date, Status (switch), Featured (switch), Page Link, Options. Form: Title, Background Color (hex), Text Color (White / Dark), Banner (1920x500), Date range, Products (multi select). After selecting products show a table to set per product discount and discount type. Public page `/flash-deal/{slug}` with countdown.
* Newsletters (`/admin/newsletter`): send email to selected users and/or subscribers: Emails (multi select), Newsletter subject, Newsletter content (rich text). (The reference site's page is broken, build it properly.)
* Subscribers (`/admin/subscribers`): #, Email, Date, Options (delete). Storefront footer has a subscribe form.
* Coupon (`/admin/coupon`): list #, Code, Type, Start Date, End Date, Options. Create: Coupon Type select loads a sub form by AJAX:
  * For Total Orders (cart base): Coupon code, User (All or specific customers), Minimum Shopping, Usage Limit Per User, Usage Limit Per Coupon, Discount + type (৳ / %), Maximum Discount Amount, Date range.
  * For Products: Coupon code, Products (multi select), Date range, Discount + type (Amount / Percent).
* Notice (`/admin/notice`): notices pushed to reseller/seller dashboards. Form: Users (multi select resellers), Notice Title *, Image (1300x650), Description. List: #, Title, Description, Image.
* Product CSV Download: one click downloads a Facebook / Google catalog feed CSV (id, title, description, availability, condition, price, sale_price, link, image_link, brand). Also expose it at a stable public feed URL.

### 9. Website Setup
All forms post to `business-settings/update`.
* Header: Header Logo, Show Language Switcher, Show Currency Switcher, Enable sticky header, Topbar Banner + link, Help line number, Header Nav Menu (repeating Label + Link rows, "Add New").
* Footer: Footer Logo, About description, Play Store Link, App Store Link; Contact Info Widget (address, phone, email); Link Widget One (title + repeating label/link rows); Copyright Text; Show Social Links switch with Facebook, Messenger, Twitter, Instagram, YouTube, LinkedIn, TikTok, WhatsApp links; Payment Methods Widget image.
* Pages: list #, Name, URL, Actions. Default pages: Home Page, Seller Policy, Return Policy, Support Policy, Terms and Conditions, Privacy Policy, plus Add New Page. Custom page form: Title *, Link (slug) *, Add Content * (rich text), Page Link Position (SHOPPING INFORMATION, ABOUT, SERVICE INFORMATION, CATEGORY, KNOW US: decides which footer column shows the link), Meta Title, Meta Description, Keywords, Meta Image.
  * Home Page Settings (edit of the Home page): Home Slider (photos and links, 1296x331), Home Banner Section 1, 2 and 3 (banners and links), Home Categories (choose categories), Category wise product sections.
* Appearance: General (Frontend Website Name, Site Motto, Site Icon, Website Base Color, Website Base Hover Color), Global SEO (Meta Title, Meta description, Keywords, Meta Image), Cookies Agreement (text + show switch), Website Popup (show switch, popup content, show subscriber form switch), Custom Script (header script before `</head>`, footer script before `</body>`).

### 10. Shipping and courier integration

#### 10.1 Shipping Configuration (`/admin/shipping_configuration`)
* Select Shipping Method (radio): Product Wise Shipping Cost, Flat Rate, Seller Wise, Area Wise Shipping Cost (default for Jihan Mart), Carrier Wise. Save.
* Flat Rate Cost (amount)
* Shipping Cost for Admin Products (amount)
* Minimum Order Amount for Free Shipping: Enable Free Shipping Offer switch + Minimum Order Amount (৳), e.g. 2000.
* Pathao Courier and Fraud Check API card, status chip "Configured (.env)" or "Not Set (.env)": Base URL (`https://api-hermes.pathao.com`), Client ID *, Client Secret *, Username / Email *, Password *, buttons Test Pathao Connection and Save to .env, and a "How to get Pathao API Credentials" help box.
* Steadfast Courier and Webhook API card with status chip: Base URL (`https://portal.packzy.com`), API Key *, Secret Key *, Webhook URL (read only, our endpoint, with Copy button), Webhook Secret (optional bearer token), buttons Test Steadfast Connection and Save to .env, plus "Steadfast Webhook Setup Instructions".
* Add a default courier select (Steadfast or Pathao) used by "Send Courier".

#### 10.2 Courier behaviour
* Send Courier (single from order list or details, or bulk): `POST /admin/create-order-courier` with order ids. For each order call the chosen courier's create order API with invoice (order code), recipient name, phone, full address, COD amount (total due), note. Save consignment id and tracking code on the order, set status to Picked Up (configurable), log `courier_sent` activity. Return `{success, message}` and toast it. Use the courier's bulk endpoint for bulk sends. Prevent sending the same order twice.
* Pathao specifics: issue access token (cache until expiry, refresh with refresh token), store id, and map our district/area to Pathao city/zone/area IDs (store mapping columns on states and cities, with a sync command that pulls Pathao's city/zone/area lists).
* Steadfast webhook: public route receiving delivery status updates (verify bearer secret), find order by consignment id or invoice, map courier status to our delivery status (delivered → Delivered, cancelled → Returned or Cancel, partial delivered, etc.), log activity.
* Fraud check: service class that queries each courier's customer delivery history by phone and merges with our own store history into the `overall` score and risk levels described in 3.2. Verify the exact courier endpoints against their current official API docs before coding.

#### 10.3 Locations
* Shipping Countries: list (#, Name, Code, Show/Hide switch), filter by name. Bangladesh enabled.
* Shipping States (used as delivery zone / district): list + add form (Name, Country). Columns #, Name, Country, Show/Hide, Action. Seed: ঢাকার ভিতরে, ঢাকার বাহিরে, ঢাকার উপশহর (and optionally all 64 districts).
* Shipping Cities (used as area / thana): list + add form: Name, State, Cost (shipping charge). Area wise shipping uses this cost. Endpoint `POST /get-cities` with state_id returns areas for dependent selects. (The reference page is broken, build it working.)
* Shipping Zones: Name, countries, status.
* Shipping Carrier: list #, Logo, Name, Transit Time, Status, Options. Form: Carrier Name *, Transit Time *, Logo, Free Shipping switch, Billing Type (According to Weight / According to Price), range table (from, to, cost per zone) with "Add new range".

### 11. Setup and Configurations
* General Settings: System Name, System Logo White, System Logo Black, System Timezone, Admin login page background, POS API URL, POS API Token, Business Location ID (for syncing orders to an external POS).
* POS Configuration (Invoice Contact Info): Invoice Site Name, Contact address, Contact phone, Contact email, Invoice Footer.
* Features activation: grid of switches grouped as:
  * System: HTTPS, Maintenance Mode, Disable image encoding, Order Sync POS
  * Invoice Setup: show invoice SKU, show invoice image
  * Business Related: Vendor System, Classified Product, Wallet System, Coupon System, Pickup Point, Conversation, Seller Product Manage By Admin, Admin Approval On Seller Product, Email Verification, Product Query
  * Payment Related: Bkash, Nagad, SSLCommerz, Aamarpay, Cash Payment (others optional)
  * Social Media Login: Facebook, Google

  Disabled modules must also disappear from the sidebar (this is how Blog, Support and Affiliate are hidden).
* Languages: Default Language select (English, Bangla, ...), list (#, Name, Code, App Lang Code, RTL switch, Status, Options: Translation, Edit), translation editor page (key / value table with search), Add New Language.
* Currency: System Default Currency (BDT ৳), Symbol Format, Decimal Separator, No of decimals, currency list with exchange rate and status, Add New Currency.
* VAT and TAX: list #, Tax Type, Status, Options; add/edit tax types.
* Pickup point: #, Name, Manager, Location, Pickup Station Contact, Status, Options; form for the same.
* SMTP Settings: Type (Sendmail, SMTP, Mailgun), host, port, username, password, encryption, from address, from name, Mailgun domain/secret; Send test email box.
* Payment Methods: cards for Bkash (app key, app secret, username, password, sandbox), Nagad (mode, merchant id, merchant number, PG public key, merchant private key), SSLCommerz (store id, store password, sandbox), Aamarpay (store id, signature key, sandbox). Manual Payment card: repeating rows of gateway (Bkash / Rocket / Nagad) + number, shown on checkout for "send money then enter TrxID".
* Order Configuration: Minimum Order Amount Check switch + amount.
* File System and Cache: S3 credentials + activation, Cache driver and Session driver (file / redis), Redis host, password, port.
* Social media Logins: Google and Facebook client id/secret.
* Facebook: Facebook Chat (switch + Page ID), Facebook Comment (switch + App ID).
* Google: Analytics Tools page holds Facebook Pixel (switch + Pixel ID, fire PageView, ViewContent, AddToCart, InitiateCheckout, Purchase on the storefront) and Google Analytics (switch + tracking ID); Google reCAPTCHA (switch + site key + secret); Google Map (switch + API key); Google Firebase (switch + FCM server key).

Do not build the reference panel's "Insert Table Column" and "Insert Permission" developer links; use migrations and seeders instead.

### 12. Optional modules (built but switched off by default)
* Blog System: posts (#, Title, Category, Short Description, Status, Options; form with title, category, slug, banner, short description, rich description, meta fields) and blog categories.
* Support: tickets (Ticket ID, Sending Date, Subject, User, Status, Last reply, Options; reply thread) and Product Conversations (#, Date, Title, Sender, Receiver, Options).
* Affiliate / Reseller System:
  * Affiliate Registration Form builder: repeating fields (type Text / Select / File, label).
  * Affiliate Configurations: User Registration and First Purchase (% of order), User First Purchase Limit (%, amount, order amount limit, start/end), Product Sharing and Purchasing (amount + $ or %), Product Sharing Category Wise (commission amount + type per category; cannot be on together with single product sharing), Affiliate link validation time (days).
  * Affiliate Users: #, Name, Phone, Email, Verification Info (Show), Approval (switch), Due Amount, Options (Pay Now, Payment History).
  * Referral Users: #, Name, Phone, Email, Referred By.
  * Affiliate Withdraw Requests: #, Date, Name, Email, Amount, Status, options.
  * Affiliate Logs: #, Referred By, Referral User, Amount, Order Id, Referral Type, Product, Date.
  * Sales reports must support the Platform filter Reseller / Inhouse.

### 13. Staffs and permissions
* All staffs: #, Name, Email, Role, Options (Edit, Delete). Form: Name *, Email *, Phone, Password *, Role *.
* Staff permissions (Roles): #, Name, Options. Role form: Name + permission checkboxes grouped by section (Products, Categories, Brands, Attributes, Reviews, Orders, Customers, Reports, Blog, Marketing, Support, Website Setup, Setup and Configurations, Staffs, Affiliate, Landing Page). Each section has view/add/edit/delete style permissions, plus order specific ones: change delivery status, change payment status, send courier, view fraud check, delete order, export reports.
* Permissions: list #, Permissions (machine name), Section, Guard Name, Options; add/edit.
* Super admin (type admin) bypasses all checks. Sidebar items hide when the staff lacks the permission.

### 14. Storefront dependencies (minimum)
The backend must support these storefront behaviours even if the theme is built later: product listing and detail with variants, collections, flash deal pages, cart and checkout with district → area → shipping cost, payment policy per product (COD / partial advance / prepaid with bKash, Nagad or manual TrxID), coupon apply, direct order form on product page, abandoned cart capture on phone input, Facebook Pixel events, custom pages, landing domains. **(Concrete API in A2.)**

### 15. Data model (minimum tables)
users (type: admin, staff, customer, reseller), roles/permissions, business_settings, uploads, categories, brands, attributes, attribute_values, colors, products, product_categories, product_stocks (variant, sku, price, qty, image), product_tiers, product_taxes, collections, collection_product, reviews, flash_deals, flash_deal_products, coupons, coupon_usages, orders (code, user_id, guest name/phone, address json, state_id, city_id, source, landing_domain, payment_method, payment_status, delivery_status, subtotal, shipping_cost, discount, coupon_discount, advance_amount, advance_method, sender_no, transaction_id, grand_total, due, tracking_code, courier, consignment_id, admin_note, customer_note, viewed), order_details, order_activities, order_call_logs, fraud_check_cache, abandoned_carts, landing_pages, countries, states, cities (cost, courier mapping ids), zones, carriers, carrier_ranges, pickup_points, taxes, languages, translations, currencies, pages, subscribers, notices, blogs, blog_categories, tickets, ticket_replies, conversations, affiliate tables.

### 16. Build phases
1. Project setup, auth, layout (sidebar, top bar), settings table, media library + picker, permissions skeleton.
2. Catalog: categories, brands, attributes, colors, products (with variants, tiers, payment policy), collections, bulk import/export, reviews.
3. Shipping locations and area wise cost, then Orders: All Orders with every filter, inline status, modals, order details, POS create/edit, invoices, packaging checklist, activity and call logs, abandoned carts.
4. Courier integration (Steadfast + Pathao send, webhook, fraud check).
5. Dashboard and all reports with export and print.
6. Marketing (flash deals, coupons, newsletter, subscribers, notice, catalog CSV).
7. Website setup, landing page builder, appearance, pages, home page settings.
8. Setup and Configurations, payment gateways, features activation, languages, currency, staffs and roles.
9. Optional modules (blog, support, affiliate/reseller).

### 17. Acceptance checklist
* Every sidebar item opens a working page with no 500 errors.
* Creating an order from the POS screen deducts stock; cancelling restores it; stock can never silently go negative (block or warn at order time).
* Changing a status inline writes an activity row with old → new value and the staff name.
* Clicking a customer phone logs a call and increments the counter.
* Fraud badge loads for every row in one batch request.
* Send Courier creates a consignment and stores tracking; webhook updates status.
* Area selection on POS and checkout sets the shipping cost from the area.
* Reports totals match the orders list for the same filters.
* A staff role without "delete order" permission cannot see or call delete.
* All money shows with ৳ and two decimals; all dates in Asia/Dhaka.

# Jihan Mart Admin Panel: ফিচারের তালিকা

e99shop-এর admin panel (`backend.e99shop.com/admin`) ভেতর থেকে দেখে বানানো তালিকা (২৪ অক্টোবর ২০২৬)। শুধু মেনু, ফর্মের field, টেবিলের কলাম আর অপশন দেখা হয়েছে। কোনো কাস্টমারের তথ্য, অর্ডারের তথ্য বা কোড কপি করা হয়নি।

**ভিত্তি:** ওদের panel আসলে **Active eCommerce CMS** (ActiveITzone-এর পেইড Laravel সফটওয়্যার, `aiz-core`)। তার উপর ওদের ডেভেলপার (DevOrbit) বাংলাদেশের জন্য অনেক কিছু নিজে যোগ করেছে। নিচে ★ চিহ্ন দেওয়া ফিচারগুলো এরকম যোগ করা ফিচার, মূল সফটওয়্যারে সাধারণত থাকে না।

---

## ১. Dashboard
- কার্ড: মোট বিক্রি, আজকের বিক্রি, এই মাসের বিক্রি, মোট অর্ডার
- অর্ডার স্ট্যাটাস সারাংশ (প্রতিটা স্ট্যাটাসে কত টাকার অর্ডার)
- চার্ট: মাসভিত্তিক বিক্রি (বছর), গত ৭ দিনের বিক্রি, ক্যাটাগরিভিত্তিক বিক্রি, ক্যাটাগরিভিত্তিক স্টক
- সাম্প্রতিক অর্ডার
- কম স্টকের সতর্কতা (Low Stock Warning)
- সবচেয়ে বেশি বিক্রি হওয়া পণ্য

## ২. Products
- **Add New Product** ফর্ম, এই অংশগুলো নিয়ে:
  - **Product Information:** নাম, ক্যাটাগরি (একাধিক, সাব ক্যাটাগরিসহ), ব্র্যান্ড, ইউনিট, ওজন (কেজি), সর্বনিম্ন কেনার সংখ্যা, ট্যাগ, Suggestion Category, Barcode
  - **Product Images:** গ্যালারি (600x600), থাম্বনেইল (300x300)
  - **Product Videos:** YouTube / Dailymotion / Vimeo লিংক
  - **Product Variation:** Color, Size, Shade, Design, Pcs, Fragrance, Variation, Flavor (প্রতিটা variation-এর আলাদা দাম ও স্টক)
  - **Price + Stock:** Unit price, Sale price, ছাড়ের তারিখের সীমা, ছাড় (Flat / Percent), পরিমাণ, SKU, External link ও বাটনের লেখা
  - ★ **Quantity Based Tiered Pricing:** বেশি কিনলে কম দাম
  - **Description** (rich text), **PDF Specification**
  - **SEO:** Meta Title, Meta Image
  - ★ **Payment Policy:** পুরো COD / আংশিক অগ্রিম + COD / ১০০% অগ্রিম। অগ্রিমের ধরন (Flat / %) ও পরিমাণ
  - টগল: Status, Featured, Today's Deal, Free Shipping, ★ Direct Order (details পেজ থেকে সরাসরি অর্ডার)
  - **Flash Deal:** কোন ফ্ল্যাশ ডিলে যাবে, তার ছাড় ও ধরন
  - আনুমানিক ডেলিভারির দিন, **VAT & Tax** (Flat / Percent)
- **All Products:** তালিকা। কলাম: নাম, তথ্য, Collection, মোট স্টক, Today's Deal, Published, Featured, Free Shipping, Direct Order। ফিল্টার: স্ট্যাটাস, Simple / Variable, Flag (Landing Page / Featured / Today Deal)। সাজানো: বিক্রি, স্টক, দাম অনুযায়ী। পণ্যকে Collection-এ যোগ করা যায়।
- **Bulk Import** (Excel / CSV দিয়ে একসাথে অনেক পণ্য) ও **Bulk Export**
- **Category:** নাম, Parent Category, Order Level, Level, Featured (সাব ক্যাটাগরি সমর্থন)
- ★ **Collections:** নাম, শুরু ও শেষের তারিখ, ছবি, স্ট্যাটাস (যেমন "Free Delivery", "Up To 50% Off", "Mystery Box", "99 Tk Offer")
- **Brand**, **Attribute**, **Colors**

## ৩. Landing Page ★
- **Home Pages:** আলাদা domain-এর জন্য আলাদা landing / home page বানানো (SL, Domain, Action)
- **Landing Page Orders:** ওই landing page থেকে আসা অর্ডারের আলাদা তালিকা (Domain কলামসহ), ডেলিভারি ও পেমেন্ট স্ট্যাটাস দিয়ে ফিল্টার, Bulk Action

## ৪. Sales (অর্ডার)
- ★ **Create Order:** admin নিজে অর্ডার বানাতে পারে (POS-এর মতো)। ক্যাটাগরি বা ব্র্যান্ড দিয়ে পণ্য খোঁজা, কাস্টমার বাছাই (Walk In / Guest / রেজিস্টার্ড), ছাড় (Flat / %), অগ্রিম টাকা ও তার মাধ্যম (COD / bKash / Nagad / Rocket), পাঠানোর নম্বর, TrxID, নোট
- **All Orders:**
  - কলাম: Order Code, মোট আইটেম, কাস্টমার, ★ Fraud, তারিখ ও সময়, টাকা, ডেলিভারি স্ট্যাটাস, ★ Comment, পেমেন্ট মাধ্যম, পেমেন্ট স্ট্যাটাস
  - ফিল্টার: খোঁজা, তারিখের সীমা, পেমেন্ট (Paid / Partial Paid / Unpaid), জেলা (ঢাকার ভিতরে / উপশহর / বাহিরে), এলাকা বা থানা
  - টেবিল থেকেই ডেলিভারি স্ট্যাটাস বদলানো যায়
  - প্রতি অর্ডারে: View, Download Invoice, ★ Send Courier, Edit, ★ Activity Log, Delete
  - Bulk: Delete, ★ Bulk Send Courier, ★ Bulk Invoice Print, ★ Packaging Checklist (বাছাই করা বা সব Confirmed অর্ডারের)
- **ডেলিভারি স্ট্যাটাস:** Pending, Confirmed, Picked Up, On The Way, Delivered, ★ Phone Off, ★ Not Receive Call, ★ Returned, Cancel
- স্ট্যাটাস অনুযায়ী আলাদা মেনু: Pending, Confirmed, Picked Up, On The Way, Delivered, Cancelled
- **Order Details:** পণ্যের তালিকা (ছবি, বিবরণ, ডেলিভারির ধরন, পরিমাণ, দাম, মোট), পেমেন্ট ও ডেলিভারি স্ট্যাটাস বদলানো, Tracking Code, ★ Comment সেভ, ★ Fraud Check, ★ Send Courier, Edit Order, Print Invoice, ★ Order Activity & History Log
- ★ **Abandoned Carts:** যারা চেকআউটে তথ্য দিয়েও অর্ডার শেষ করেনি। কলাম: নাম, ফোন, ঠিকানা, জেলা বা থানা, কার্টের পণ্য, Source, Status, সময়। ফিল্টার আছে।

## ৫. Customers
- কাস্টমার তালিকা (নাম, ইমেইল, ফোন, রেজিস্ট্রেশনের তারিখ), একসাথে অনেকগুলো মোছা
- Uploaded Files (মিডিয়া লাইব্রেরি)

## ৬. Reports
- ★ **Date Wise Sale Report:** তারিখের সীমা (আজ / গতকাল / ৭ দিন / এই মাস / গত মাস / সব সময়), ডেলিভারি স্ট্যাটাস, দেখানোর ধরন (Invoice / Item / Date ও Product / Product), ক্যাটাগরি, পেমেন্ট, খোঁজা। মোট টাকা, মোট পিস, অর্ডার সংখ্যা। Excel / CSV export ও Print।
- ★ **Sale Summary Report:** দৈনিক বা মাসিক সারাংশ। কলাম: অর্ডার, Invoice নম্বর, বিক্রি হওয়া পিস, পণ্যের বিক্রি, Shipping, Discount, Grand Total, AOV (গড় অর্ডারের মূল্য), সবচেয়ে বেশি বিক্রি হওয়া পণ্য। ট্রেন্ড চার্ট। Platform ফিল্টার (Reseller / Inhouse)।
- **Product Sale** (পণ্যভিত্তিক বিক্রি)
- **Products Stock** (ক্যাটাগরি অনুযায়ী স্টক)

## ৭. Blog System
- পোস্ট, ব্লগের ক্যাটাগরি

## ৮. Marketing
- **Flash Deals:** শিরোনাম, ব্যানার, শুরু ও শেষ, স্ট্যাটাস, Featured, পেজ লিংক
- **Newsletters**, **Coupon** (কোড, ধরন, শুরু ও শেষের তারিখ), **Notice** (শিরোনাম, বিবরণ, ছবি), Product CSV Download

## ৯. Support
- **Ticket** (Support Desk: Ticket ID, তারিখ, বিষয়, ইউজার, স্ট্যাটাস, শেষ উত্তর)
- **Product Conversations** (পণ্য নিয়ে কাস্টমারের প্রশ্ন)

## ১০. Affiliate System
- রেজিস্ট্রেশন ফর্ম, Configuration, Affiliate Users, Referral Users, Withdraw Requests, Affiliate Logs

## ১১. Website Setup
- **Header:** লোগো, ভাষা ও কারেন্সি বদলের অপশন দেখানো, Sticky header, Topbar banner ও লিংক, Helpline নম্বর, Nav মেনু
- **Footer**, **Pages** (নিজের পেজ: নাম, URL)
- **Appearance:** সাইটের নাম, Motto, Icon, মূল রঙ ও hover রঙ, Global SEO (Meta title, description, keywords, image), Cookies agreement, Website popup, Subscriber form, Header ও Footer-এ custom script

## ১২. Shipping
- **Shipping Configuration:** খরচের পদ্ধতি (পণ্যভিত্তিক / Flat / Seller ভিত্তিক / এলাকাভিত্তিক / Carrier ভিত্তিক), ফ্রি শিপিং অফার ও তার সর্বনিম্ন অর্ডার
- ★ **Pathao Courier ও Fraud Check API:** Base URL, Client ID, Secret, Username, Password
- ★ **Steadfast Courier ও Webhook:** API Key, Secret Key, Webhook URL ও Secret
- Countries, States, Cities, Zones, Carriers

## ১৩. Setup & Configurations
- **General Settings:** সিস্টেমের নাম, লোগো (সাদা ও কালো), Timezone, admin লগইন পেজের ব্যাকগ্রাউন্ড, ★ POS API URL, Token, Business Location ID
- **POS Configuration:** Invoice-এর নাম, ঠিকানা, ফোন, ইমেইল, footer
- **Features Activation:**
  - **System:** HTTPS, Maintenance mode, Image encoding, ★ Order Sync POS, Invoice-এ SKU ও ছবি দেখানো
  - **Business:** Vendor system, Classified product, Wallet, Coupon, Pickup point, Conversation, Email verification, Product query
  - **Payment:** প্রতিটা payment gateway চালু বা বন্ধ
- **Languages** (English, বাংলা, Arabic, Hindi), **Currency**, **VAT & Tax**, **Pickup Point**, **SMTP Settings**
- **Payment Methods:**
  - **Manual Payment:** bKash, Rocket, Nagad
  - **বাংলাদেশি gateway:** bKash Checkout, Nagad, SSLCommerz, Aamarpay
  - **বিদেশি gateway:** PayPal, Stripe, Mercadopago, Iyzico, Instamojo, PayStack, Payhere, Ngenius, VoguePay, RazorPay, Authorize.Net, Payku
- ★ **Order Configuration:** সর্বনিম্ন অর্ডারের পরিমাণ
- File System ও Cache, Social Login, Facebook Chat ও Comment, Google Analytics, reCAPTCHA, Map, Firebase

## ১৪. Staffs
- Staff তালিকা, Role (Staff permissions), প্রতিটা Permission আলাদা করে নিয়ন্ত্রণ

## ১৫. অন্যান্য
- Notification (সব দেখার পেজ), admin panel-এর ভাষা বদল (English / Bangla / Arabic / Hindi), Profile, Logout, Clear Cache

---

## Jihan Mart-এর জন্য আমার পরামর্শ

**প্রথম ধাপে (অবশ্যই লাগবে):** Dashboard, Products (variation, bulk import ও export সহ), Category, Collections, Orders (সব স্ট্যাটাস, invoice, activity log, comment), Create Order, Abandoned Carts, Customers, Coupon, Flash Deal, Shipping (এলাকাভিত্তিক চার্জ ও ফ্রি শিপিং), Payment (COD, bKash ও Nagad manual, পরে gateway), Pathao বা Steadfast courier ও Fraud Check, Reports (তারিখভিত্তিক ও সারাংশ), Website Setup (header, footer, appearance, pages), Staff ও Permission, **Customer Reviews অনুমোদন** (এটা ওদের panel-এ নেই, আমাদের frontend-এ আছে)।

**পরে যোগ করা যায়:** Landing Page (একাধিক domain), Blog, Support Ticket, Affiliate, POS sync, Newsletter, Notice।

**সম্ভবত লাগবে না:** বেশিরভাগ বিদেশি payment gateway (PayPal, Stripe, Mercadopago, Iyzico, Instamojo, PayStack, Payhere, Ngenius, VoguePay, RazorPay, Authorize.Net, Payku), Vendor ও Classified system, Wallet।

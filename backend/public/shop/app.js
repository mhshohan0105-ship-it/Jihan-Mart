// ===================== Data =====================
// Catalog, settings and delivery areas come from the backend (window.STORE, same data as /api/v1)
const STORE = window.STORE || { catalog: { categories: [], products: [], boxes: [] }, settings: {}, locations: [] };
const API = window.API || "/api/v1";
const CATALOG = STORE.catalog;
const SETTINGS = STORE.settings || {};
const LOCATIONS = STORE.locations || [];

const CATEGORIES = CATALOG.categories
  .map((c) => {
    const subs = c.subs.map((s) => ({ id: "s" + s.id, en: s.en, bn: s.bn }));
    if (subs.length) subs.push({ id: "other", en: "Others", bn: "অন্যান্য" });
    return { id: "c" + c.id, en: c.en, bn: c.bn, emoji: c.emoji, c: `linear-gradient(160deg,${c.color})`, image: c.image, subs, count: c.count };
  })
  .sort((a, b) => b.count - a.count);
const catById = Object.fromEntries(CATEGORIES.map((c) => [c.id, c]));

const FLASH = CATALOG.flash || null;
const PRODUCTS = CATALOG.products.map((p, i) => ({
  id: p.id, slug: p.slug, cat: "c" + p.cat,
  // products only in the parent category fall under "Others" when the category has sub categories
  sub: p.sub ? "s" + p.sub : (catById["c" + p.cat]?.subs.length ? "other" : null),
  img: p.img, emoji: catById["c" + p.cat]?.emoji || "🛍️",
  price: p.price, old: p.old, tag: p.old ? "sale" : "", sales: p.sales, stock: p.stock,
  rating: p.rating, reviews: p.reviews, policy: p.policy, variants: p.variants,
  pop: p.sales * 2 - i / 1000, // best sellers first, newest first on ties
  // a running admin flash deal picks the products; otherwise cheap discounted items fill the row
  flash: FLASH ? FLASH.ids.includes(p.id) && p.stock > 0 : p.old > 0 && p.stock > 0,
  bn: p.name, en: p.name,
}));
const subName = (p) => {
  const c = catById[p.cat];
  const s = p.sub && c.subs.find((x) => x.id === p.sub && x.id !== "other");
  return s ? nm(s) : nm(c);
};
// Mystery boxes are real products (Admin → Products, collection "Mystery Box"); mb1..mb3 map to them by price order
const BOX_BN = ["মিস্ট্রি বক্স মিনি", "মিস্ট্রি বক্স প্লাস", "মিস্ট্রি বক্স রয়্যাল"];
const BOXES = Object.fromEntries((CATALOG.boxes || []).map((b, i) => ["mb" + (i + 1), {
  id: "mb" + (i + 1), pid: String(b.id), emoji: "🎁", price: b.price, c: "linear-gradient(135deg,#f47c20,#1b2b57)", bn: BOX_BN[i] || b.name, en: b.name,
}]));
const FREE_SHIP = Number(SETTINGS.free_shipping_min || 0);

// ===================== i18n =====================
// Bangla text lives in the HTML; English is here. Bangla is captured on load.
const EN = {
  ann1: "🍳 Everything your kitchen needs, in one place", ann2: "💸 Cash on delivery all over Bangladesh", ann3: "🔥 New cookware & kitchen collection just landed", ann4: "🚚 Free delivery on orders ৳2000+",
  navHome: "Home", navCat: "Categories", navOffers: "Hot Offers", navShop: "Shop", navMystery: "Mystery Box", navHelp: "Help",
  searchPh: "Search products…",
  heroEyebrow: "Kitchen & cookware store · Delivery across Bangladesh", heroT1: "A kitchen you will love,", heroT2: "cooking made easy", slogan: "Quality · Durability · Value",
  heroSub: "Cookware, dish racks, storage boxes, kitchen gadgets and organizers, all in one place. Order without login, pay when it arrives.",
  heroBtn1: "Shop now", heroBtn2: "🎁 Mystery Box", stat1: "+ products", stat2: "% off up to", stat3: "districts covered",
  tr1t: "Shop with confidence", tr1d: "Check the item in front of the courier at delivery. Any problem and we replace it.",
  tr2t: "Safe payment", tr2d: "bKash, Nagad, Rocket, card or cash on delivery.",
  tr3t: "Fast delivery", tr3d: "1 to 2 days inside Dhaka, 2 to 4 days outside.",
  tr4t: "Great value", tr4d: "Handpicked kitchen essentials with new deals every day.",
  catEyebrow: "Categories", catTitle: "From kitchen to the whole home",
  flashEyebrow: "Flash sale", flashTitle: "Today's Flash Deals", hrs: "Hours", mins: "Minutes", secs: "Seconds",
  shopEyebrow: "Shop", shopTitle: "Popular & new arrivals", maxPrice: "Max price",
  sortPop: "Popular", sortLow: "Price: low → high", sortHigh: "Price: high → low", sortNew: "Newest", noResult: "No products found 😕",
  myEyebrow: "Mystery Box", myTitle: "Pay ৳499, get ৳1500+ worth of surprises",
  myDesc: "Each box holds 4 to 6 handpicked items: kitchen gadgets, storage and everyday essentials. You find out when you open it!",
  boxMini: "Mini", boxPlus: "Plus", boxRoyal: "Royal", spinBtn: "Spin the box", addBox: "Add to cart",
  revEyebrow: "Reviews", revTitle: "What customers say",
  revSortNew: "Newest first", revSortHigh: "Highest rating", revSortLow: "Lowest rating", revSortHelpful: "Most helpful", revMore: "More reviews",
  revWrite: "Write a review", revNamePh: "Your name", revTextPh: "Tell us about the product or your shopping experience…", revPhoto: "Add a photo (optional)", revSubmit: "Submit review", payTitle: "Ways to pay", payCod: "Cash on Delivery",
  helpEyebrow: "Help", helpTitle: "Delivery, returns & FAQ", delTitle: "🚚 Delivery charges",
  delIn: "Inside Dhaka", delSub: "Dhaka suburbs", delOut: "Outside Dhaka", delFree: "Orders ৳2000+", free: "Free",
  d12: "1 to 2 days", d23: "2 to 3 days", d24: "2 to 4 days",
  trackTitle: "📦 Track your order", trackDesc: "Enter your order number and phone to see where your order is.", orderNoPh: "Order number (e.g. 1001)", trackBtn: "Track",
  q1: "What is the return policy?", a1: "Check the item in front of the courier when it arrives. If it is broken, damaged, the wrong item or does not match the photo, we replace it. If you do not want it, pay only the delivery charge and hand it back to the courier. Products with a warranty are covered for the stated period.",
  q2: "Do I need an account to order?", a2: "No. Just your name, phone number and address.",
  q3: "Is cash on delivery available?", a3: "Yes, all over Bangladesh. You can also pay in advance with bKash/Nagad/Rocket.",
  q4: "What's inside a Mystery Box?", a4: "4 to 6 handpicked items worth more than the box price. Every box is different!",
  nlTitle: "Get new kitchen deals first", nlDesc: "Leave your phone number and we'll SMS you when a flash sale starts.", phonePh: "01XXXXXXXXX", subscribe: "Subscribe",
  footAbout: "Welcome to Jihan Mart, your trusted online store for everything the kitchen needs. From cookware, dish racks and storage boxes to handy kitchen gadgets, we deliver quality products at fair prices all over Bangladesh.",
  footCat: "Top categories", footLinks: "Quick links", loadMore: "Show more products", flashMore: "Show more deals",
  lnkReturn: "Return Policy", lnkTerms: "Terms & Conditions", lnkPrivacy: "Privacy Policy", lnkTrack: "Track order", footContact: "Contact us",
  address: "Dhaka, Bangladesh", rights: "All Rights Reserved",
  yourCart: "Your cart", subtotal: "Subtotal", checkout: "Checkout (no login needed)", wishlist: "Wishlist",
  coTitle: "Confirm your order", namePh: "Your name", addrPh: "Full address", shipping: "Delivery", total: "Total", placeOrder: "Place order", senderPh: "Number you sent from", trxPh: "Transaction ID", notePh: "Extra info (optional)", couponPh: "Coupon code", revVerifyHint: "Only people who bought this product can review it. Enter your order number and the phone number you ordered with.", revOrderPh: "Order number (e.g. 1001)", revVerifyBtn: "Verify", apply: "Apply", discount: "Coupon discount", nlPh: "Phone number or email",
  thanks: "Thank you! Order placed", orderNo: "Order number:", willCall: "Our team will call you shortly to confirm.",
};
const DYN = {
  bn: {
    items: "টি পণ্য", quick: "দ্রুত দেখুন", added: "কার্টে যোগ হয়েছে ✓", wishAdd: "উইশলিস্টে যোগ হয়েছে ♥", wishRemove: "উইশলিস্ট থেকে সরানো হয়েছে",
    emptyCart: "কার্ট খালি", emptyWish: "উইশলিস্ট খালি", all: "সব", addCart: "কার্টে যোগ করুন",
    freeLeft: (n) => `আর <b>৳${n}</b> কিনলে ফ্রি ডেলিভারি 🚚`, freeOk: "🎉 আপনি ফ্রি ডেলিভারি পাচ্ছেন!",
    qvDesc: "প্রিমিয়াম কোয়ালিটি। ক্যাশ অন ডেলিভারি, কুরিয়ারের সামনে পণ্য চেক করে নেওয়ার সুবিধা।",
    subbed: "সাবস্ক্রাইব হয়েছে! অফার শুরু হলে SMS পাবেন", couponOk: (n) => `কুপন যোগ হয়েছে, ৳${n} ছাড়`, couponRecheck: "কার্ট বদলেছে, কুপন আবার Apply করুন", boxAdded: "মিস্ট্রি বক্স কার্টে যোগ হয়েছে 🎁",
    steps: ["কনফার্মড", "প্যাকিং", "পথে", "ডেলিভারড"], trackNote: "ডেমো স্ট্যাটাস: আসল ট্র্যাকিং ব্যাকএন্ড যুক্ত হলে দেখাবে।",
    sold: (n) => `${n} টি বিক্রি হয়েছে`, buyNow: "এখনই কিনুন", addCartShort: "কার্টে যোগ", save: (n) => `৳${n} সাশ্রয়`, inStock: "স্টকে আছে", outStock: "স্টক শেষ", outToast: "দুঃখিত, পণ্যটি এখন স্টকে নেই",
    showing: (a, b) => `${b} টির মধ্যে ${a} টি পণ্য দেখানো হচ্ছে`, allIn: (c) => `সব ${c}`,
    helpful: "কাজের", revDelete: "মুছুন", revDelConfirm: "রিভিউটি মুছে ফেলবেন?", revBased: (n) => `${n} টি রিভিউ`,
    revWriteBtn: "রিভিউ লিখুন", revAll: "সব", revWithPhoto: "📷 ছবিসহ", revNoMatch: "এই ফিল্টারে কোনো রিভিউ নেই।",
    revEmpty: "এখনো কোনো রিভিউ নেই। আপনার অভিজ্ঞতা শেয়ার করে অন্যদের সাহায্য করুন!", revFirst: "প্রথম রিভিউ দিন",
    revAbout: "রিভিউ দিচ্ছেন", revPickStar: "স্টার বেছে নিন", starLabels: ["", "খুব খারাপ 😞", "খারাপ 😕", "মোটামুটি 🙂", "ভালো 😊", "অসাধারণ! 🤩"],
    errRating: "দয়া করে স্টার দিয়ে রেটিং দিন।", errName: "আপনার নাম লিখুন।", errText: "রিভিউ অন্তত ১০ অক্ষরের হতে হবে।",
    revThanks: "ধন্যবাদ! আপনার রিভিউ যোগ হয়েছে ⭐", revPending: "ধন্যবাদ! যাচাইয়ের পর রিভিউটি দেখানো হবে ⭐",
    netErr: "সার্ভারে সমস্যা হয়েছে, আবার চেষ্টা করুন", pickZone: "জেলা / জোন বাছুন", pickArea: "এলাকা / থানা বাছুন",
    statusLabel: { pending: "অপেক্ষমাণ", confirmed: "কনফার্মড", picked_up: "কুরিয়ারে দেওয়া হয়েছে", on_the_way: "পথে আছে", delivered: "ডেলিভারি হয়েছে", phone_off: "ফোন বন্ধ পাওয়া গেছে", not_receive_call: "কল রিসিভ হয়নি", returned: "ফেরত এসেছে", cancelled: "বাতিল হয়েছে" }, qvReviews: "রিভিউ", qvNoRev: "এখনো কোনো রিভিউ নেই। প্রথম রিভিউ আপনিই দিন!",
  },
  en: {
    items: " items", quick: "Quick view", added: "Added to cart ✓", wishAdd: "Added to wishlist ♥", wishRemove: "Removed from wishlist",
    emptyCart: "Your cart is empty", emptyWish: "Your wishlist is empty", all: "All", addCart: "Add to cart",
    freeLeft: (n) => `Add <b>৳${n}</b> more for free delivery 🚚`, freeOk: "🎉 You've unlocked free delivery!",
    qvDesc: "Premium quality. Cash on delivery, and you can check the item in front of the courier.",
    subbed: "Subscribed! We'll SMS you when offers go live", couponOk: (n) => `Coupon applied, ৳${n} off`, couponRecheck: "Cart changed, apply the coupon again", boxAdded: "Mystery Box added to cart 🎁",
    steps: ["Confirmed", "Packing", "On the way", "Delivered"], trackNote: "Demo status: real tracking appears once the backend is connected.",
    sold: (n) => `${n} sold`, buyNow: "Buy now", addCartShort: "Add", save: (n) => `Save ৳${n}`, inStock: "In stock", outStock: "Out of stock", outToast: "Sorry, this item is out of stock",
    showing: (a, b) => `Showing ${a} of ${b} products`, allIn: (c) => `All ${c}`,
    helpful: "Helpful", revDelete: "Delete", revDelConfirm: "Delete this review?", revBased: (n) => `${n} review${n === 1 ? "" : "s"}`,
    revWriteBtn: "Write a review", revAll: "All", revWithPhoto: "📷 With photos", revNoMatch: "No reviews match this filter.",
    revEmpty: "No reviews yet. Share your experience and help other shoppers!", revFirst: "Write the first review",
    revAbout: "Reviewing", revPickStar: "Pick a rating", starLabels: ["", "Terrible 😞", "Poor 😕", "Okay 🙂", "Good 😊", "Excellent! 🤩"],
    errRating: "Please choose a star rating.", errName: "Please enter your name.", errText: "Your review needs at least 10 characters.",
    revThanks: "Thanks! Your review was added ⭐", revPending: "Thanks! Your review will appear after a quick check ⭐",
    netErr: "Something went wrong, please try again", pickZone: "Choose district / zone", pickArea: "Choose area / thana",
    statusLabel: { pending: "Pending", confirmed: "Confirmed", picked_up: "Handed to courier", on_the_way: "On the way", delivered: "Delivered", phone_off: "Phone was off", not_receive_call: "Call not received", returned: "Returned", cancelled: "Cancelled" }, qvReviews: "Reviews", qvNoRev: "No reviews yet. Be the first to review!",
  },
};

const store = {
  get(k, d) { try { const v = localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; } },
  set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); return true; } catch (e) { return false; } },
};

let lang = store.get("jm-lang", "bn");
const BN = {};
document.querySelectorAll("[data-i18n]").forEach((el) => { BN[el.dataset.i18n] = el.textContent; });
document.querySelectorAll("[data-i18n-ph]").forEach((el) => { BN[el.dataset.i18nPh] = el.placeholder; });

const t = (k) => DYN[lang][k];
const nm = (o) => o[lang];

function applyLang() {
  document.documentElement.lang = lang;
  const dict = lang === "en" ? EN : BN;
  document.querySelectorAll("[data-i18n]").forEach((el) => { const v = dict[el.dataset.i18n]; if (v) el.textContent = v; });
  document.querySelectorAll("[data-i18n-ph]").forEach((el) => { const v = dict[el.dataset.i18nPh]; if (v) el.placeholder = v; });
  document.getElementById("langBtn").textContent = lang === "bn" ? "EN" : "বাং";
  renderAll();
  buildNavRoll();
}

// ===================== Premium nav =====================
// Each link gets two stacked copies of its text that roll up on hover
function buildNavRoll() {
  document.querySelectorAll(".nav-link").forEach((a) => {
    const txt = a.textContent.trim();
    a.innerHTML = `<span class="roll"><span>${txt}</span><span aria-hidden="true">${txt}</span></span>`;
  });
  requestAnimationFrame(() => movePill(document.querySelector(".nav-link.active")));
}

function movePill(link) {
  const pill = document.getElementById("navPill");
  if (!link || window.innerWidth <= 900) { pill.style.opacity = 0; return; }
  const n = document.getElementById("nav").getBoundingClientRect();
  const r = link.getBoundingClientRect();
  pill.style.opacity = 1;
  pill.style.width = r.width + 28 + "px";
  pill.style.transform = `translate(${r.left - n.left - 14}px, -50%)`;
}

function initNav() {
  const nav = document.getElementById("nav");
  const links = [...document.querySelectorAll(".nav-link")];

  // stagger entrance
  [...links, ...document.querySelectorAll(".header-actions > *")].forEach((el, i) => el.style.setProperty("--d", 0.35 + i * 0.06 + "s"));
  document.getElementById("header").classList.add("ready");

  links.forEach((a) => a.addEventListener("pointerenter", () => movePill(a)));
  nav.addEventListener("pointerleave", () => movePill(nav.querySelector(".nav-link.active")));
  window.addEventListener("resize", () => movePill(nav.querySelector(".nav-link.active")));

  // scroll spy: highlight the section you're looking at
  const spy = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return;
      links.forEach((a) => a.classList.toggle("active", a.getAttribute("href") === "#" + e.target.id));
      if (!nav.matches(":hover")) movePill(nav.querySelector(".nav-link.active"));
    });
  }, { rootMargin: "-45% 0px -50% 0px" });
  // only in-page links (the product page points them at "/#...")
  links.forEach((a) => { const h = a.getAttribute("href"); const sec = h.startsWith("#") && h.length > 1 && document.querySelector(h); if (sec) spy.observe(sec); });

  // hide header when scrolling down, show when scrolling up + progress bar
  let lastY = window.scrollY;
  const header = document.getElementById("header");
  const bar = document.getElementById("scrollProgress");
  window.addEventListener("scroll", () => {
    const y = window.scrollY;
    const anyOpen = document.querySelector(".drawer.open, .modal.open, .nav.open");
    header.classList.toggle("hide", y > lastY && y > 500 && !anyOpen && !nav.matches(":hover"));
    lastY = y;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    bar.style.transform = `scaleX(${max > 0 ? y / max : 0})`;
  }, { passive: true });

  // icon buttons: ripple on click
  document.querySelectorAll(".icon-btn, .lang-btn").forEach((b) => b.addEventListener("click", (e) => {
    const r = b.getBoundingClientRect();
    const s = document.createElement("span");
    s.className = "ripple";
    s.style.left = e.clientX - r.left + "px";
    s.style.top = e.clientY - r.top + "px";
    b.appendChild(s);
    setTimeout(() => s.remove(), 600);
  }));
}

// ===================== State =====================
let cart = store.get("jm-cart", []); // [{key, qty}]
let wish = store.get("jm-wish", []); // [id]
const PRICE_MAX = Math.max(3000, Math.ceil(Math.max(0, ...PRODUCTS.map((p) => p.price)) / 100) * 100);
let filter = { cat: "all", sub: "all", q: "", max: PRICE_MAX, sort: "pop" };
const PAGE = 12;
let shown = PAGE;

const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

function itemByKey(key) {
  return BOXES[key] || PRODUCTS.find((p) => String(p.id) === String(key));
}
function colorOf(item) { return item.c || catById[item.cat].c; }
const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
const soldOut = (p) => p.stock !== undefined && p.stock <= 0;

// Real products show their photo, sample ones show an emoji
function visual(p, cls = "emoji") {
  return p.img
    ? `<img class="p-img" src="${p.img}" alt="${esc(nm(p))}" loading="lazy" decoding="async">`
    : `<span class="${cls}">${p.emoji}</span>`;
}

// ===================== Rendering =====================
function stars(r) { return "★".repeat(Math.round(r)) + "☆".repeat(5 - Math.round(r)); }
function metaLine(p) {
  const st = revStats(productReviews(p.id));
  if (st.count) return `<span class="p-rating">${starBar(st.avg, "xs")} <small>${st.avg.toFixed(1)} (${st.count})</small></span>`;
  if (soldOut(p)) return `<span class="p-stock out">${t("outStock")}</span>`;
  return `<span class="p-stock">${p.sales ? t("sold")(p.sales) : t("inStock")}</span>`;
}

const CART_ICON = `<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M5 7h14l-1.2 11.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 7z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 7a3 3 0 0 1 6 0" fill="none" stroke="currentColor" stroke-width="2"/></svg>`;
function actionButtons(p) {
  if (soldOut(p)) return `<div class="p-actions"><button class="p-btn out" disabled>${t("outStock")}</button></div>`;
  return `<div class="p-actions">
    <button class="p-btn cart" data-add="${p.id}">${CART_ICON}<span>${t("addCart")}</span></button>
    <button class="p-btn buy" data-buy="${p.id}"><span>${t("buyNow")}</span></button>
  </div>`;
}

const productUrl = (p) => "/product/" + encodeURIComponent(p.slug || p.id);

function productCard(p) {
  const off = p.old ? Math.round((1 - p.price / p.old) * 100) : 0;
  const tag = p.tag === "new" ? `<span class="p-tag new">NEW</span>` : off ? `<span class="p-tag sale">${off}% OFF</span>` : "";
  const on = wish.includes(p.id) ? "on" : "";
  return `
  <article class="p-card ${soldOut(p) ? "is-out" : ""}" data-id="${p.id}">
    <div class="p-media ${p.img ? "has-img" : ""}" style="--c:${colorOf(p)}">
      ${tag}
      <button class="p-wish ${on}" data-wish="${p.id}" aria-label="Wishlist">
        <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 2.3 4 6.3 4c2.2 0 3.6 1.2 4.4 2.4h2.6C14.1 5.2 15.5 4 17.7 4c4 0 6 4.3 4.3 7.7C19.5 16.4 12 21 12 21z" fill="none" stroke="#fff" stroke-width="1.8"/></svg>
      </button>
      <button class="p-quick" data-quick="${p.id}" aria-label="${t("quick")}" title="${t("quick")}">
        <svg viewBox="0 0 24 24" width="18" height="18"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="#fff" stroke-width="1.8"/></svg>
      </button>
      <a href="${productUrl(p)}" class="p-link" aria-label="${esc(nm(p))}">${visual(p)}</a>
      <span class="p-shine" aria-hidden="true"></span>
    </div>
    <div class="p-info">
      <span class="p-cat">${subName(p)}</span>
      <h3 class="p-name"><a href="${productUrl(p)}">${esc(nm(p))}</a></h3>
      ${metaLine(p)}
      <div class="p-bottom">
        <div class="p-price"><b>৳${p.price}</b>${p.old ? `<s>৳${p.old}</s>` : ""}</div>
        ${p.old ? `<span class="p-save">${t("save")(p.old - p.price)}</span>` : ""}
      </div>
      ${actionButtons(p)}
    </div>
  </article>`;
}

// Category cards, footer list and mega menu never change with filters, so build them once per language
function renderCatalogNav() {
  const count = (fn) => PRODUCTS.filter(fn).length;
  // Admin → Home Page can pick and order the category cards
  const homeIds = (SETTINGS.home?.categories || []).map((id) => "c" + id);
  const homeCats = homeIds.length ? homeIds.map((id) => catById[id]).filter(Boolean) : CATEGORIES;
  $("#catGrid").innerHTML = homeCats.map((c) => {
    const cover = PRODUCTS.find((p) => p.cat === c.id && p.img && p.stock > 0);
    return `<div class="cat-card tilt reveal" style="--c:${c.c}" data-cat="${c.id}">
      ${cover ? `<img class="cat-cover" src="${cover.img}" alt="" loading="lazy">` : ""}
      <span class="emoji">${c.emoji}</span><h3>${nm(c)}</h3><span>${c.count}${t("items")}</span></div>`;
  }).join("");
  $("#footCats").innerHTML = CATEGORIES.slice(0, 6).map((c) => `<li><a href="#shop" data-cat-link="${c.id}">${nm(c)}</a></li>`).join("");
  renderMega(count);
}

// Filter chips (these follow the selected category)
function renderCategories() {
  $("#chips").innerHTML = [{ id: "all", bn: t("all"), en: t("all") }, ...CATEGORIES]
    .map((c) => `<button class="chip ${filter.cat === c.id ? "active" : ""}" data-chip="${c.id}">${nm(c)}</button>`).join("");

  // Sub categories of the selected category (Beauty, Jewellery)
  const cur = catById[filter.cat];
  const subs = cur ? cur.subs.filter((s) => PRODUCTS.some((p) => p.cat === cur.id && p.sub === s.id)) : [];
  $("#subChips").hidden = !subs.length;
  $("#subChips").innerHTML = subs.length
    ? [{ id: "all", bn: t("allIn")(nm(cur)), en: t("allIn")(nm(cur)) }, ...subs]
        .map((s) => `<button class="chip sm ${filter.sub === s.id ? "active" : ""}" data-sub="${s.id}">${nm(s)}</button>`).join("")
    : "";

}

// Mega menu under "Categories" in the nav: all categories + sub categories of the big ones
function renderMega(count) {
  let i = 0;
  const subCols = CATEGORIES.filter((c) => c.subs.length).map((c) => `
    <div class="mega-col mega-sub">
      <h5>${nm(c)}</h5>
      ${c.subs.filter((s) => s.id !== "other").map((s) => `<a href="#shop" class="mega-sub-link" style="--i:${i++}" data-cat-link="${c.id}" data-sub-link="${s.id}">${nm(s)} <small>${count((p) => p.cat === c.id && p.sub === s.id)}</small></a>`).join("")}
    </div>`).join("");
  $("#mega").innerHTML = `
    <div class="mega-col mega-cats">
      ${CATEGORIES.map((c, j) => `<a href="#shop" class="mega-item" style="--i:${Math.min(j, 12)}" data-cat-link="${c.id}">
        <span class="mega-ico" style="--c:${c.c}">${c.emoji}</span>
        <span><b>${nm(c)}</b><small>${c.count}${t("items")}</small></span></a>`).join("")}
    </div>${subCols}`;
}

function filteredProducts() {
  const q = filter.q.trim().toLowerCase();
  const list = PRODUCTS.filter((p) =>
    (filter.cat === "all" || p.cat === filter.cat) &&
    (filter.sub === "all" || p.sub === filter.sub) &&
    p.price <= filter.max &&
    (!q || p.bn.toLowerCase().includes(q) || p.en.toLowerCase().includes(q) || catById[p.cat].en.toLowerCase().includes(q) || catById[p.cat].bn.includes(q))
  );
  const sorters = {
    pop: (a, b) => soldOut(a) - soldOut(b) || b.pop - a.pop,
    low: (a, b) => a.price - b.price,
    high: (a, b) => b.price - a.price,
    new: (a, b) => b.id - a.id,
  };
  return list.sort(sorters[filter.sort]);
}

// reset = new filter (start from the first page); otherwise append the next page
function renderProducts(reset = true) {
  const list = filteredProducts();
  const grid = $("#productGrid");
  const from = reset ? 0 : grid.children.length;
  if (reset) shown = PAGE;
  const html = list.slice(from, shown).map(productCard).join("");
  if (reset) grid.innerHTML = html; else grid.insertAdjacentHTML("beforeend", html);
  [...grid.children].slice(from).forEach((el, i) => {
    el.style.animationDelay = i * 0.05 + "s";
    el.classList.add("pop-in");
    // drop the entrance animation once done so it can't hold the hover transform
    el.addEventListener("animationend", () => el.classList.remove("pop-in"), { once: true });
  });
  $("#emptyMsg").hidden = list.length > 0;
  $("#loadMore").hidden = shown >= list.length;
  $("#resultCount").textContent = list.length ? t("showing")(Math.min(shown, list.length), list.length) : "";
  bindTilt(grid);
}

// Flash deals: a grid like the shop with a "show more" button (no sideways slider, easier on phones)
const FLASH_PAGE = 8;
let flashShown = FLASH_PAGE;
function flashDeals() {
  if (FLASH) {
    const order = Object.fromEntries(FLASH.ids.map((id, i) => [id, i]));
    return PRODUCTS.filter((p) => p.flash).sort((a, b) => order[a.id] - order[b.id]);
  }
  // biggest discount first, at most 3 per category for variety
  const per = {};
  return PRODUCTS.filter((p) => p.flash)
    .sort((a, b) => (b.old - b.price) / b.old - (a.old - a.price) / a.old)
    .filter((p) => (per[p.cat] = (per[p.cat] || 0) + 1) <= 3);
}
function renderFlash() {
  const deals = flashDeals();
  if (FLASH) $("[data-i18n=flashTitle]").textContent = FLASH.title;
  $("#flashRow").innerHTML = deals.slice(0, flashShown).map(productCard).join("");
  const left = deals.length - flashShown;
  $("#flashMore").hidden = left <= 0;
  $("#flashLeft").textContent = left > 0 ? `(${left})` : "";
  bindTilt($("#flashRow"));
}

// ===================== API =====================
async function api(path, { method = "GET", body } = {}) {
  const isForm = body instanceof FormData;
  const res = await fetch(API + path, {
    method,
    headers: { Accept: "application/json", "X-Locale": lang, ...(body && !isForm ? { "Content-Type": "application/json" } : {}) },
    body: body && !isForm ? JSON.stringify(body) : body,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error((data.errors && Object.values(data.errors)[0]?.[0]) || data.message || t("netErr"));
  return data;
}
async function loadReviews() {
  try {
    reviews = (await api("/reviews")).data;
    renderReviews();
    renderProducts();
    renderFlash();
  } catch (e) { /* storefront still works without reviews */ }
}

// ===================== Customer reviews =====================
// Saved in this browser for now. TODO: post to the backend (with admin approval) so every visitor sees them.
let reviews = []; // approved reviews from the API: [{id, pid, name, rating, text, photo, date, helpful, reply}]
let helpfulVoted = store.get("jm-helpful", []); // review ids this browser marked helpful
const rev = { filter: "all", sort: "new", shown: 6, pid: null, rating: 0, photo: "", order: null, phone: "" };

function revStats(list) {
  const dist = [0, 0, 0, 0, 0, 0];
  list.forEach((r) => dist[r.rating]++);
  const avg = list.length ? list.reduce((a, r) => a + r.rating, 0) / list.length : 0;
  return { count: list.length, avg, dist };
}
const productReviews = (pid) => reviews.filter((r) => r.pid === pid);
// five stars filled to the exact average (4.3 shows 4 and a third)
const starBar = (avg, cls = "") => `<span class="stars-fill ${cls}" style="--pct:${(avg / 5) * 100}%" role="img" aria-label="${avg.toFixed(1)} / 5">★★★★★</span>`;

function timeAgo(ts) {
  const rtf = new Intl.RelativeTimeFormat(lang === "bn" ? "bn" : "en", { numeric: "auto" });
  const steps = [[60, "second"], [60, "minute"], [24, "hour"], [30, "day"], [12, "month"], [Infinity, "year"]];
  let v = Math.round((ts - Date.now()) / 1000);
  for (const [n, unit] of steps) {
    if (Math.abs(v) < n) return rtf.format(v, unit);
    v = Math.round(v / n);
  }
}
const avatarHue = (name) => [...name].reduce((h, c) => (h * 31 + c.charCodeAt(0)) % 360, 7);

function reviewCard(r) {
  const p = r.pid ? PRODUCTS.find((x) => x.id === r.pid) : null;
  const voted = helpfulVoted.includes(r.id);
  return `
  <article class="rev-card">
    <header>
      <div class="av" style="--h:${avatarHue(r.name)}">${esc(r.name[0].toUpperCase())}</div>
      <div><b>${esc(r.name)}</b>${r.verified ? `<span class="rev-verified">✔ ${lang === "bn" ? "যাচাইকৃত ক্রেতা" : "Verified buyer"}</span>` : ""}<small>${timeAgo(r.date)}</small></div>
      ${starBar(r.rating, "sm")}
    </header>
    ${p ? `<button class="rev-product" data-quick="${p.id}">${p.img ? `<img src="${p.img}" alt="" loading="lazy">` : p.emoji}<span>${esc(nm(p))}</span></button>` : ""}
    <p>${esc(r.text)}</p>
    ${r.photo ? `<img class="rev-img" src="${r.photo}" alt="" loading="lazy">` : ""}
    ${r.reply ? `<p class="rev-reply"><b>Jihan Mart:</b> ${esc(r.reply)}</p>` : ""}
    <footer>
      <button class="helpful ${voted ? "on" : ""}" data-helpful="${r.id}">👍 ${t("helpful")} ${r.helpful ? `<span>${r.helpful}</span>` : ""}</button>
    </footer>
  </article>`;
}

function renderReviews() {
  const st = revStats(reviews);
  $("#revSummary").innerHTML = `
    <div class="rev-avg">${st.count ? st.avg.toFixed(1) : "0.0"}<small>/5</small></div>
    ${starBar(st.avg, "lg")}
    <p class="rev-total">${t("revBased")(st.count)}</p>
    <div class="rev-bars">
      ${[5, 4, 3, 2, 1].map((n) => `
        <button class="rev-bar ${rev.filter === String(n) ? "active" : ""}" data-rev-filter="${n}">
          <span>${n}★</span><i><em style="width:${st.count ? (st.dist[n] / st.count) * 100 : 0}%"></em></i><small>${st.dist[n]}</small>
        </button>`).join("")}
    </div>
    <button class="btn btn-primary full" data-write-review="">✍️ ${t("revWriteBtn")}</button>`;

  const filters = [["all", t("revAll")], ["5", "5★"], ["4", "4★"], ["3", "3★"], ["2", "2★"], ["1", "1★"], ["photo", t("revWithPhoto")]];
  $("#revFilters").innerHTML = filters.map(([id, label]) => `<button class="chip sm ${rev.filter === id ? "active" : ""}" data-rev-filter="${id}">${label}</button>`).join("");

  const list = reviews.filter((r) => rev.filter === "all" || (rev.filter === "photo" ? r.photo : String(r.rating) === rev.filter));
  const sorters = {
    new: (a, b) => b.date - a.date,
    high: (a, b) => b.rating - a.rating || b.date - a.date,
    low: (a, b) => a.rating - b.rating || b.date - a.date,
    helpful: (a, b) => b.helpful - a.helpful || b.date - a.date,
  };
  list.sort(sorters[rev.sort]);
  $("#revList").innerHTML = list.length
    ? list.slice(0, rev.shown).map(reviewCard).join("")
    : `<div class="rev-empty"><div>💬</div><p>${reviews.length ? t("revNoMatch") : t("revEmpty")}</p>${reviews.length ? "" : `<button class="btn btn-ghost" data-write-review="">${t("revFirst")}</button>`}</div>`;
  $("#revMore").hidden = list.length <= rev.shown;
}

async function toggleHelpful(id) {
  const r = reviews.find((x) => String(x.id) === String(id));
  if (!r) return;
  const voted = helpfulVoted.includes(r.id);
  try {
    const res = await api(`/reviews/${r.id}/helpful`, { method: "POST", body: { undo: voted ? 1 : 0 } });
    r.helpful = res.helpful;
    helpfulVoted = voted ? helpfulVoted.filter((x) => x !== r.id) : [...helpfulVoted, r.id];
    store.set("jm-helpful", helpfulVoted);
    renderReviews();
  } catch (e) { toast(e.message); }
}

function deleteReview() {} // customers cannot delete reviews; moderation happens in Admin → Product Reviews

function qvReviews(p) {
  const list = productReviews(p.id).sort((a, b) => b.date - a.date);
  const st = revStats(list);
  return `
  <div class="qv-reviews">
    <div class="qv-rev-head">
      <b>${t("qvReviews")} (${st.count})</b>
      ${st.count ? `${starBar(st.avg, "sm")}<small>${st.avg.toFixed(1)}</small>` : ""}
      <button class="link-btn" data-write-review="${p.id}">✍️ ${t("revWriteBtn")}</button>
    </div>
    ${list.length
      ? list.slice(0, 3).map((r) => `<div class="qv-rev"><div>${starBar(r.rating, "xs")} <b>${esc(r.name)}</b> <small>${timeAgo(r.date)}</small></div><p>${esc(r.text)}</p></div>`).join("")
      : `<p class="qv-norev">${t("qvNoRev")}</p>`}
  </div>`;
}

function openReviewForm(pid) {
  closeAll();
  rev.pid = pid ? Number(pid) : null;
  rev.rating = 0;
  rev.photo = "";
  rev.order = null;
  rev.phone = "";
  $("#reviewForm").reset();
  $("#revVerify").reset();
  $("#revVerifyErr").hidden = true;
  $("#revVerified").hidden = true;
  // a product review needs a delivered order first; a store review does not
  $("#revVerify").hidden = !rev.pid;
  $("#reviewForm").hidden = !!rev.pid;
  $("#revPhotoPreview").hidden = true;
  $("#revErr").hidden = true;
  paintStars(0);
  const p = rev.pid && PRODUCTS.find((x) => x.id === rev.pid);
  $("#revFor").innerHTML = p
    ? `${p.img ? `<img src="${p.img}" alt="">` : `<span>${p.emoji}</span>`}<div><small>${t("revAbout")}</small><b>${esc(nm(p))}</b></div>`
    : `<span>🛍️</span><div><small>${t("revAbout")}</small><b>Jihan Mart</b></div>`;
  openPanel($("#reviewModal"));
}

function paintStars(n, preview = false) {
  $$("#starPick button").forEach((b) => {
    const on = Number(b.dataset.star) <= n;
    b.classList.toggle("on", on);
    b.classList.toggle("preview", preview && on);
    b.setAttribute("aria-checked", Number(b.dataset.star) === rev.rating);
  });
  $("#starLabel").textContent = n ? t("starLabels")[n] : t("revPickStar");
}

// shrink the photo so it fits in browser storage
function readPhoto(file) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => {
      const scale = Math.min(1, 640 / Math.max(img.width, img.height));
      const c = document.createElement("canvas");
      c.width = Math.round(img.width * scale);
      c.height = Math.round(img.height * scale);
      c.getContext("2d").drawImage(img, 0, 0, c.width, c.height);
      URL.revokeObjectURL(img.src);
      resolve(c.toDataURL("image/jpeg", 0.78));
    };
    img.onerror = reject;
    img.src = URL.createObjectURL(file);
  });
}

async function submitReview(e) {
  e.preventDefault();
  const f = e.target;
  const name = f.elements.name.value.trim();
  const text = f.elements.text.value.trim();
  const showErr = (msg) => {
    const el = $("#revErr");
    el.textContent = msg;
    el.hidden = false;
    el.classList.remove("shake"); void el.offsetWidth; el.classList.add("shake");
  };
  const err = !rev.rating ? t("errRating") : name.length < 2 ? t("errName") : text.length < 10 ? t("errText") : "";
  if (err) return showErr(err);

  const body = new FormData();
  if (rev.pid) {
    body.append("product_id", rev.pid);
    body.append("order_code", rev.order || "");
    body.append("phone", rev.phone);
  }
  body.append("name", name);
  body.append("rating", rev.rating);
  body.append("comment", text);
  body.append("website", "");
  body.append("recaptcha_token", await window.captchaToken?.("review") || "");
  if (rev.photo) body.append("photo", await (await fetch(rev.photo)).blob(), "review.jpg");
  try {
    const res = await api("/reviews", { method: "POST", body });
    closeAll();
    toast(res.status === "approved" ? t("revThanks") : t("revPending"));
    // the product page lists reviews from the server, so show the new one by reloading
    if (res.status === "approved" && window.PRODUCT_PAGE) setTimeout(() => location.reload(), 1200);
    else if (res.status === "approved") await loadReviews();
  } catch (e2) { showErr(e2.message); }
}

async function verifyReviewOrder(e) {
  e.preventDefault();
  const f = e.target;
  const btn = f.querySelector("button");
  const showErr = (msg) => { const el = $("#revVerifyErr"); el.textContent = msg; el.hidden = false; };
  const order = f.elements.order.value.trim(), phone = f.elements.phone.value.trim();
  if (!order || !phone) return showErr(lang === "bn" ? "অর্ডার নম্বর ও ফোন নম্বর দুটোই দিন" : "Enter both the order number and phone");
  btn.disabled = true;
  try {
    const res = await api("/reviews/verify", { method: "POST", body: { product_id: rev.pid, order_code: order, phone } });
    rev.order = res.order;
    rev.phone = phone;
    $("#reviewForm").elements.name.value = res.name || "";
    $("#revVerified").textContent = lang === "bn" ? `✔ অর্ডার #${res.order} যাচাই হয়েছে, এবার রিভিউ লিখুন` : `✔ Order #${res.order} verified, now write your review`;
    $("#revVerified").hidden = false;
    f.hidden = true;
    $("#reviewForm").hidden = false;
  } catch (err) {
    showErr(err.message);
  } finally {
    btn.disabled = false;
  }
}

function initReviewForm() {
  const pick = $("#starPick");
  $("#revVerify").addEventListener("submit", verifyReviewOrder);
  $("#revSort").addEventListener("change", (e) => { rev.sort = e.target.value; renderReviews(); });
  $("#revMore").addEventListener("click", () => { rev.shown += 6; renderReviews(); });
  $("#reviewForm").addEventListener("submit", submitReview);
  pick.addEventListener("click", (e) => {
    const b = e.target.closest("[data-star]");
    if (!b) return;
    rev.rating = Number(b.dataset.star);
    paintStars(rev.rating);
    b.classList.remove("pop"); void b.offsetWidth; b.classList.add("pop");
  });
  pick.addEventListener("pointerover", (e) => { const b = e.target.closest("[data-star]"); if (b) paintStars(Number(b.dataset.star), true); });
  pick.addEventListener("pointerleave", () => paintStars(rev.rating));
  pick.addEventListener("keydown", (e) => {
    if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
    rev.rating = Math.min(5, Math.max(1, rev.rating + (e.key === "ArrowRight" ? 1 : -1)));
    paintStars(rev.rating);
    $$("#starPick button")[rev.rating - 1].focus();
  });
  $("#reviewForm").elements.photo.addEventListener("change", async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    try {
      rev.photo = await readPhoto(file);
      $("#revPhotoPreview").src = rev.photo;
      $("#revPhotoPreview").hidden = false;
    } catch (err) {
      rev.photo = "";
    }
  });
}

function renderCart() {
  const lines = cart.map((l) => ({ ...l, item: itemByKey(l.key) })).filter((l) => l.item);
  const count = lines.reduce((s, l) => s + l.qty, 0);
  const sub = lines.reduce((s, l) => s + l.qty * l.item.price, 0);
  bumpBadge($("#cartCount"), count);
  $("#subtotal").textContent = "৳" + sub;
  $("#cartItems").innerHTML = lines.length
    ? lines.map((l) => `
      <div class="c-item">
        <div class="c-thumb" style="--c:${colorOf(l.item)}">${visual(l.item)}</div>
        <div class="c-info">
          <h4>${esc(nm(l.item))}</h4>
          <b>৳${l.item.price * l.qty}</b>
          <div class="qty"><button data-dec="${l.key}">−</button><span>${l.qty}</span><button data-inc="${l.key}">+</button></div>
        </div>
        <button class="c-remove" data-rm="${l.key}" aria-label="Remove">🗑</button>
      </div>`).join("")
    : `<div class="cart-empty"><div>🛍️</div>${t("emptyCart")}</div>`;
  const left = Math.max(0, FREE_SHIP - sub);
  $("#freeShipMsg").innerHTML = left ? t("freeLeft")(left) : t("freeOk");
  $("#freeShipBar").style.width = Math.min(100, (sub / FREE_SHIP) * 100) + "%";
  $("#checkoutBtn").disabled = !lines.length;
  $("#checkoutBtn").style.opacity = lines.length ? 1 : 0.5;
  store.set("jm-cart", cart);
  return sub;
}

function renderWish() {
  bumpBadge($("#wishCount"), wish.length);
  const items = wish.map((id) => PRODUCTS.find((p) => p.id === id)).filter(Boolean);
  $("#wishItems").innerHTML = items.length
    ? items.map((p) => `
      <div class="c-item">
        <div class="c-thumb" style="--c:${colorOf(p)}">${visual(p)}</div>
        <div class="c-info"><h4>${esc(nm(p))}</h4><b>৳${p.price}</b></div>
        <button class="p-btn cart sm" data-add="${p.id}" ${soldOut(p) ? "disabled" : ""}>${t("addCartShort")}</button>
        <button class="c-remove" data-wish="${p.id}" aria-label="Remove">✕</button>
      </div>`).join("")
    : `<div class="cart-empty"><div>💝</div>${t("emptyWish")}</div>`;
  store.set("jm-wish", wish);
}

function bumpBadge(el, n) {
  if (el.textContent !== String(n)) {
    el.textContent = n;
    el.classList.remove("bump"); void el.offsetWidth; el.classList.add("bump");
  }
}

function renderAll() {
  renderCatalogNav();
  renderCategories();
  renderFlash();
  renderProducts();
  renderReviews();
  renderCart();
  renderWish();
  observeReveal();
  bindTilt(document);
  $("#year").textContent = new Date().getFullYear();
}

// ===================== Cart & wishlist actions =====================
function addToCart(key, fromEl, qty = 1) {
  const item = itemByKey(key);
  if (soldOut(item)) { toast(t("outToast")); return; }
  const line = cart.find((l) => String(l.key) === String(key));
  line ? (line.qty += qty) : cart.push({ key: String(key), qty });
  window.track?.("AddToCart", { value: item.price, content_ids: [String(item.pid || key)], content_type: "product" });
  renderCart();
  toast(t("added"));
  if (fromEl) flyToCart(fromEl, item);
}
// Buy now: make sure the item is in the cart, then go straight to checkout
function buyNow(key) {
  const item = itemByKey(key);
  if (soldOut(item)) { toast(t("outToast")); return; }
  if (!cart.some((l) => String(l.key) === String(key))) { cart.push({ key: String(key), qty: 1 }); renderCart(); }
  openCheckout();
}
function changeQty(key, d) {
  const line = cart.find((l) => l.key === key);
  if (!line) return;
  line.qty += d;
  if (line.qty <= 0) cart = cart.filter((l) => l !== line);
  renderCart();
}
function toggleWish(id) {
  id = Number(id);
  const has = wish.includes(id);
  wish = has ? wish.filter((w) => w !== id) : [...wish, id];
  $$(`[data-wish="${id}"].p-wish`).forEach((b) => b.classList.toggle("on", !has));
  renderWish();
  toast(t(has ? "wishRemove" : "wishAdd"));
}

function flyToCart(fromEl, item) {
  const s = fromEl.getBoundingClientRect();
  const e = $("#cartBtn").getBoundingClientRect();
  const f = document.createElement("div");
  f.className = "fly";
  if (item.img) f.innerHTML = `<img src="${item.img}" alt="">`; else f.textContent = item.emoji;
  f.style.left = s.left + s.width / 2 - 20 + "px";
  f.style.top = s.top + s.height / 2 - 20 + "px";
  document.body.appendChild(f);
  requestAnimationFrame(() => {
    f.style.transform = `translate(${e.left - s.left}px, ${e.top - s.top}px) scale(.3) rotate(360deg)`;
    f.style.opacity = "0.2";
  });
  setTimeout(() => f.remove(), 850);
}

let toastTimer;
function toast(msg) {
  const el = $("#toast");
  el.textContent = msg;
  el.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove("show"), 2200);
}

// ===================== Drawers & modals =====================
function openPanel(el) { el.classList.add(el.classList.contains("modal") ? "open" : "open"); if (el.classList.contains("drawer")) $("#overlay").classList.add("show"); }
function closeAll() {
  $$(".drawer.open, .modal.open").forEach((el) => el.classList.remove("open"));
  $("#overlay").classList.remove("show");
}

function quickView(id) {
  const p = PRODUCTS.find((x) => x.id === Number(id));
  window.track?.("ViewContent", { value: p.price, content_ids: [String(p.id)], content_name: p.en, content_type: "product" });
  const off = p.old ? Math.round((1 - p.price / p.old) * 100) : 0;
  $("#qvInner").innerHTML = `
    <div class="qv-media ${p.img ? "has-img" : ""}" style="--c:${colorOf(p)}">${p.img ? `<img src="${p.img}" alt="${esc(nm(p))}">` : `<span>${p.emoji}</span>`}</div>
    <div class="qv-info">
      <span class="p-cat">${nm(catById[p.cat])}</span>
      <h3>${esc(nm(p))}</h3>
      ${metaLine(p)}
      <div class="p-price"><b>৳${p.price}</b>${p.old ? `<s>৳${p.old}</s> <span class="p-tag sale" style="position:static">${off}% OFF</span>` : ""}</div>
      <p>${t("qvDesc")}</p>
      ${actionButtons(p)}
      <a class="qv-more" href="${productUrl(p)}">${lang === "bn" ? "সম্পূর্ণ বিবরণ দেখুন →" : "View full details →"}</a>
      ${qvReviews(p)}
    </div>`;
  openPanel($("#quickView"));
}

function openCheckout() {
  closeAll();
  window.track?.("InitiateCheckout", { value: cartSubtotal(), num_items: cart.reduce((n, l) => n + l.qty, 0) });
  $("#checkoutForm").hidden = false;
  $("#coSuccess").hidden = true;
  $("#coErr").hidden = true;
  fillZones();
  updateCheckoutSum();
  openPanel($("#checkoutModal"));
}
function fillZones() {
  const zone = $("#zoneSel");
  if (zone.options.length) return;
  zone.innerHTML = `<option value="">${t("pickZone")}</option>` + LOCATIONS.map((z) => `<option value="${z.id}">${esc(z.name)}</option>`).join("");
  fillAreas();
}
function fillAreas() {
  const zone = LOCATIONS.find((z) => String(z.id) === $("#zoneSel").value);
  $("#areaSel").innerHTML = `<option value="">${t("pickArea")}</option>` + (zone?.cities || []).map((c) => `<option value="${c.id}" data-cost="${c.cost}">${esc(c.name)} (৳${c.cost})</option>`).join("");
  updateCheckoutSum();
}
function cartSubtotal() {
  return cart.reduce((s, l) => s + l.qty * (itemByKey(l.key)?.price || 0), 0);
}
function updateCheckoutSum() {
  const sub = cartSubtotal();
  const area = $("#areaSel").selectedOptions[0];
  const areaCost = SETTINGS.shipping_type === "flat_rate" ? Number(SETTINGS.flat_rate || 0) : Number(area?.dataset.cost || 0);
  const ship = FREE_SHIP > 0 && sub >= FREE_SHIP ? 0 : areaCost;
  $("#coSub").textContent = "৳" + sub;
  $("#coShip").textContent = !area?.value && SETTINGS.shipping_type !== "flat_rate" ? "—" : ship ? "৳" + ship : (lang === "bn" ? "ফ্রি" : "Free");
  // a cart change after applying a coupon means the discount must be checked again
  if (coupon && coupon.sub !== sub) clearCoupon(t("couponRecheck"));
  const off = coupon ? coupon.discount : 0;
  $("#coDiscRow").hidden = !off;
  $("#coDisc").textContent = "−৳" + off;
  $("#coTotal").textContent = "৳" + Math.max(0, sub + ship - off);
}
let coupon = null;
function clearCoupon(msg) {
  coupon = null;
  $("#couponMsg").textContent = msg || "";
  $("#couponMsg").className = "coupon-msg";
}
async function applyCoupon() {
  const code = $("#couponInput").value.trim();
  if (!code) return;
  const btn = $("#couponApply");
  btn.disabled = true;
  try {
    const res = await api("/coupons/validate", { method: "POST", body: { code, phone: $("#checkoutForm").phone.value, items: cartItems() } });
    coupon = { code: res.code, discount: res.discount, sub: cartSubtotal() };
    $("#couponMsg").textContent = t("couponOk")(res.discount);
    $("#couponMsg").className = "coupon-msg ok";
  } catch (err) {
    clearCoupon(err.message);
    $("#couponMsg").className = "coupon-msg err";
  } finally {
    btn.disabled = false;
    updateCheckoutSum();
  }
}
function cartItems() {
  return cart.map((l) => ({ product_id: Number(itemByKey(l.key)?.pid || l.key), variant: "", quantity: l.qty }));
}

// ===================== Effects =====================
let revealObs;
function observeReveal() {
  if (!revealObs) {
    revealObs = new IntersectionObserver((entries) => {
      entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add("in"); revealObs.unobserve(e.target); } });
    }, { threshold: 0.12 });
  }
  $$(".reveal:not(.in)").forEach((el, i) => { el.style.transitionDelay = (i % 4) * 0.08 + "s"; revealObs.observe(el); });
}

const canHover = window.matchMedia("(hover: hover)").matches;
function bindTilt(root) {
  if (!canHover) return;
  root.querySelectorAll(".tilt:not([data-tilt])").forEach((el) => {
    el.dataset.tilt = 1;
    el.addEventListener("pointermove", (e) => {
      const r = el.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
      el.classList.remove("tilt-reset");
      el.style.transform = `rotateY(${(x - 0.5) * 16}deg) rotateX(${(0.5 - y) * 16}deg) translateZ(10px)`;
      const media = el.querySelector(".p-media");
      if (media) { media.style.setProperty("--mx", x * 100 + "%"); media.style.setProperty("--my", y * 100 + "%"); }
    });
    el.addEventListener("pointerleave", () => { el.classList.add("tilt-reset"); el.style.transform = ""; });
  });
}

function magnetic() {
  if (!canHover) return;
  $$(".magnetic").forEach((el) => {
    el.addEventListener("pointermove", (e) => {
      const r = el.getBoundingClientRect();
      el.style.transform = `translate(${(e.clientX - r.left - r.width / 2) * 0.25}px, ${(e.clientY - r.top - r.height / 2) * 0.35}px)`;
    });
    el.addEventListener("pointerleave", () => { el.style.transform = ""; });
  });
}

function cursor() {
  if (!canHover) return;
  const dot = $("#cursorDot");
  let x = 0, y = 0, cx = 0, cy = 0;
  window.addEventListener("pointermove", (e) => { x = e.clientX; y = e.clientY; });
  document.addEventListener("pointerover", (e) => dot.classList.toggle("hover", !!e.target.closest("a, button, .p-card, .cat-card, select, input")));
  (function loop() { cx += (x - cx) * 0.2; cy += (y - cy) * 0.2; dot.style.transform = `translate(${cx}px, ${cy}px)`; requestAnimationFrame(loop); })();
}

function counters() {
  const obs = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return;
      const el = e.target, to = Number(el.dataset.to), start = performance.now();
      (function step(now) {
        const k = Math.min(1, (now - start) / 1800), eased = 1 - Math.pow(1 - k, 3);
        el.textContent = Math.round(to * eased).toLocaleString("en-US");
        if (k < 1) requestAnimationFrame(step);
      })(start);
      obs.unobserve(el);
    });
  });
  $$(".count").forEach((el) => obs.observe(el));
}

function countdown() {
  const pad = (n) => String(n).padStart(2, "0");
  function tick() {
    const now = new Date();
    let end = new Date(now);
    if (FLASH) end = new Date(FLASH.ends);
    else end.setHours(23, 59, 59, 999); // the default flash row resets every midnight
    const s = Math.max(0, Math.floor((end - now) / 1000));
    $("#cdH").textContent = pad(Math.floor(s / 3600));
    $("#cdM").textContent = pad(Math.floor((s % 3600) / 60));
    $("#cdS").textContent = pad(s % 60);
  }
  tick();
  setInterval(tick, 1000);
}

// ===================== Events =====================
document.addEventListener("click", (e) => {
  const card = e.target.closest(".p-card");
  if (card && !e.target.closest("button, a, input, select, label")) {
    const p = PRODUCTS.find((x) => String(x.id) === card.dataset.id);
    if (p) { location.href = productUrl(p); return; }
  }
  const el = e.target.closest("[data-add],[data-buy],[data-write-review],[data-helpful],[data-rev-filter],[data-del-review],[data-wish],[data-quick],[data-inc],[data-dec],[data-rm],[data-chip],[data-sub],[data-cat],[data-cat-link],[data-sub-link],[data-close]");
  if (!el) return;
  const d = el.dataset;
  if ("writeReview" in d) openReviewForm(d.writeReview);
  else if (d.helpful) toggleHelpful(d.helpful);
  else if (d.revFilter) { rev.filter = rev.filter === d.revFilter && d.revFilter !== "all" ? "all" : d.revFilter; rev.shown = 6; renderReviews(); }
  else if (d.delReview) deleteReview(d.delReview);
  else if (d.add) addToCart(d.add, el);
  else if (d.buy) buyNow(d.buy);
  else if (d.wish) toggleWish(d.wish);
  else if (d.quick) quickView(d.quick);
  else if (d.inc) changeQty(d.inc, 1);
  else if (d.dec) changeQty(d.dec, -1);
  else if (d.rm) { cart = cart.filter((l) => l.key !== d.rm); renderCart(); }
  else if (d.chip) { filter.cat = d.chip; filter.sub = "all"; renderCategories(); renderProducts(); }
  else if (d.sub) { filter.sub = d.sub; renderCategories(); renderProducts(); }
  else if (d.cat || d.catLink || d.subLink) {
    e.preventDefault();
    if (window.PRODUCT_PAGE) {
      // the shop grid lives on the home page: remember the category and go there
      try { sessionStorage.setItem("jm-goto", JSON.stringify({ cat: d.cat || d.catLink, sub: d.subLink || "all" })); } catch (err) {}
      location.href = "/#shop";
      return;
    }
    $("#nav").classList.remove("open"); $("#burger").classList.remove("x");
    document.activeElement.blur();
    // close the mega menu right away, even while the mouse is still over it
    const drop = el.closest(".nav-drop");
    if (drop) { drop.classList.add("shut"); drop.addEventListener("pointerleave", () => drop.classList.remove("shut"), { once: true }); }
    filter.cat = d.cat || d.catLink;
    filter.sub = d.subLink || "all";
    renderCategories(); renderProducts();
    $("#shop").scrollIntoView({ behavior: "smooth" });
  }
  else if ("close" in d) closeAll();
});

$("#overlay").addEventListener("click", closeAll);
$$(".modal").forEach((m) => m.addEventListener("click", (e) => { if (e.target === m) closeAll(); }));
document.addEventListener("keydown", (e) => { if (e.key === "Escape") closeAll(); });

$("#cartBtn").addEventListener("click", () => openPanel($("#cartDrawer")));
$("#wishBtn").addEventListener("click", () => openPanel($("#wishDrawer")));
$("#checkoutBtn").addEventListener("click", () => { if (cart.length) openCheckout(); });
$("#areaSel").addEventListener("change", updateCheckoutSum);

$("#langBtn").addEventListener("click", () => { lang = lang === "bn" ? "en" : "bn"; store.set("jm-lang", lang); applyLang(); });

let searchTimer;
// Search: suggestions drop down under the box while typing (the page stays put);
// Enter or "see all results" filters the shop grid and only then scrolls to it.
let suggestIdx = -1;
function searchMatches(q, limit = 6) {
  q = q.trim().toLowerCase();
  if (!q) return [];
  const words = q.split(/\s+/);
  const hay = (p) => (p.bn + " " + p.en + " " + catById[p.cat].en + " " + catById[p.cat].bn).toLowerCase();
  return PRODUCTS.filter((p) => words.every((w) => hay(p).includes(w)))
    .sort((a, b) => soldOut(a) - soldOut(b) || nm(b).toLowerCase().startsWith(q) - nm(a).toLowerCase().startsWith(q) || b.pop - a.pop)
    .slice(0, limit);
}
function renderSuggest() {
  const q = $("#searchInput").value;
  const box = $("#searchSuggest");
  const list = searchMatches(q);
  suggestIdx = -1;
  if (!q.trim()) { box.hidden = true; $("#searchInput").setAttribute("aria-expanded", "false"); return; }
  const total = searchMatches(q, Infinity).length;
  box.innerHTML = (list.length
    ? list.map((p, i) => `<a class="ss-item" role="option" id="ss${i}" href="${productUrl(p)}">
        ${p.img ? `<img src="${p.img}" alt="" loading="lazy">` : `<span class="ss-emoji">${p.emoji}</span>`}
        <span class="ss-text"><b>${esc(nm(p))}</b><small>${nm(catById[p.cat])}${soldOut(p) ? " · " + t("outStock") : ""}</small></span>
        <span class="ss-price">৳${p.price}</span></a>`).join("")
      + `<button type="button" class="ss-all" data-search-all>${lang === "bn" ? `সব ফলাফল দেখুন (${total})` : `See all results (${total})`}</button>`
    : `<p class="ss-empty">${lang === "bn" ? "কোনো পণ্য পাওয়া যায়নি" : "No products found"}</p>`);
  box.hidden = false;
  $("#searchInput").setAttribute("aria-expanded", "true");
}
function closeSuggest() { $("#searchSuggest").hidden = true; $("#searchInput").setAttribute("aria-expanded", "false"); }
function searchAll() {
  const q = $("#searchInput").value.trim();
  closeSuggest();
  $("#searchInput").blur();
  if (window.PRODUCT_PAGE) {
    // the shop grid lives on the home page
    try { sessionStorage.setItem("jm-goto", JSON.stringify({ cat: "all", sub: "all", q })); } catch (err) {}
    location.href = "/#shop";
    return;
  }
  filter.q = q;
  filter.cat = "all"; filter.sub = "all";
  renderCategories(); renderProducts();
  $("#shop").scrollIntoView({ behavior: "smooth" });
}
$("#searchInput").addEventListener("input", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    renderSuggest();
    // clearing the box resets the shop grid quietly
    if (!$("#searchInput").value.trim() && filter.q) { filter.q = ""; renderProducts(); }
  }, 120);
});
$("#searchInput").addEventListener("focus", () => { if ($("#searchInput").value.trim()) renderSuggest(); });
$("#searchInput").addEventListener("keydown", (e) => {
  const items = [...$$("#searchSuggest .ss-item")];
  if (e.key === "ArrowDown" || e.key === "ArrowUp") {
    if (!items.length) return;
    e.preventDefault();
    suggestIdx = (suggestIdx + (e.key === "ArrowDown" ? 1 : -1) + items.length) % items.length;
    items.forEach((a, i) => a.classList.toggle("on", i === suggestIdx));
    $("#searchInput").setAttribute("aria-activedescendant", items[suggestIdx].id);
  } else if (e.key === "Enter") {
    e.preventDefault();
    if (suggestIdx >= 0 && items[suggestIdx]) location.href = items[suggestIdx].href;
    else if ($("#searchInput").value.trim()) searchAll();
  } else if (e.key === "Escape") {
    closeSuggest();
  }
});
document.addEventListener("click", (e) => {
  if (e.target.closest("[data-search-all]")) { searchAll(); return; }
  if (!e.target.closest(".search")) closeSuggest();
});
$("#priceRange").max = PRICE_MAX;
$("#priceRange").value = PRICE_MAX;
$("#priceVal").textContent = "৳" + PRICE_MAX;
$("#priceRange").addEventListener("input", (e) => { filter.max = Number(e.target.value); $("#priceVal").textContent = "৳" + filter.max; renderProducts(); });
$("#sortSel").addEventListener("change", (e) => { filter.sort = e.target.value; renderProducts(); });
$("#loadMore").addEventListener("click", () => { shown += PAGE; renderProducts(false); });
$("#flashMore").addEventListener("click", () => { flashShown += FLASH_PAGE; renderFlash(); });

// Mobile menu
$("#burger").addEventListener("click", () => { $("#nav").classList.toggle("open"); $("#burger").classList.toggle("x"); });
$$("#nav a").forEach((a) => a.addEventListener("click", () => { $("#nav").classList.remove("open"); $("#burger").classList.remove("x"); }));

// Header shadow on scroll
window.addEventListener("scroll", () => $("#header").classList.toggle("scrolled", window.scrollY > 20), { passive: true });

// Mystery box
let selectedBox = "mb1";
$("#boxOptions").addEventListener("click", (e) => {
  const b = e.target.closest(".box-opt");
  if (!b) return;
  $$(".box-opt").forEach((x) => x.classList.toggle("active", x === b));
  selectedBox = b.dataset.box;
});
$("#spinBox").addEventListener("click", () => {
  const cube = $("#mysteryCube");
  cube.classList.remove("fast"); void cube.offsetWidth; cube.classList.add("fast");
  cube.addEventListener("animationend", () => cube.classList.remove("fast"), { once: true });
});
$("#addBox").addEventListener("click", (e) => { if (BOXES[selectedBox]) { addToCart(BOXES[selectedBox].pid, e.currentTarget); toast(t("boxAdded")); } });

// Checkout: the order goes to Admin → All Orders
$("#zoneSel").addEventListener("change", fillAreas);
$("#areaSel").addEventListener("change", updateCheckoutSum);
$("#checkoutForm").addEventListener("change", (e) => {
  if (e.target.name === "pay") { $("#trxFields").hidden = e.target.value === "cod"; showPayNumber(e.target.value); }
});
// capture the cart as soon as a valid phone number is typed (Admin → Abandoned Carts)
let captureTimer;
$("#checkoutForm").addEventListener("input", (e) => {
  if (!["phone", "name", "address"].includes(e.target.name)) return;
  clearTimeout(captureTimer);
  captureTimer = setTimeout(() => {
    const f = $("#checkoutForm");
    if (!/^01[3-9]\d{8}$/.test(f.phone.value.replace(/\D/g, "").replace(/^88/, "")) || !cart.length) return;
    api("/abandoned-carts", { method: "POST", body: {
      phone: f.phone.value, name: f.elements.name.value, address: f.address.value,
      state_id: f.state_id.value || null, city_id: f.city_id.value || null, items: cartItems(), website: f.website.value,
    } }).catch(() => {});
  }, 800);
});
$("#checkoutForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const btn = $("#coSubmit");
  const pay = f.querySelector("[name=pay]:checked")?.value || "cod";
  $("#coErr").hidden = true;
  const minOrder = Number(SETTINGS.min_order || 0);
  if (minOrder > 0 && cartSubtotal() < minOrder) {
    $("#coErr").textContent = lang === "bn" ? `সর্বনিম্ন অর্ডার ৳${minOrder}` : `Minimum order is ৳${minOrder}`;
    $("#coErr").hidden = false;
    return;
  }
  btn.disabled = true;
  try {
    const recaptcha_token = await window.captchaToken?.("order");
    const res = await api("/orders", { method: "POST", body: { recaptcha_token,
      name: f.elements.name.value, phone: f.phone.value, address: f.address.value,
      state_id: f.state_id.value, city_id: f.city_id.value, items: cartItems(),
      payment_method: pay, sender_no: f.sender_no.value, transaction_id: f.transaction_id.value,
      customer_note: f.customer_note.value, website: f.website.value, coupon_code: coupon?.code || "",
    } });
    window.track?.("Purchase", { value: res.order.total, content_ids: cartItems().map((i) => String(i.product_id)), content_type: "product" });
    $("#orderId").textContent = "#" + res.order.code;
    $("#checkoutForm").hidden = true;
    $("#coSuccess").hidden = false;
    f.reset();
    clearCoupon();
    cart = [];
    renderCart();
  } catch (err) {
    $("#coErr").textContent = err.message;
    $("#coErr").hidden = false;
  } finally {
    btn.disabled = false;
  }
});

// Order tracking: order number + phone
const TRACK_STEP = { pending: 1, confirmed: 1, phone_off: 1, not_receive_call: 1, picked_up: 2, on_the_way: 3, delivered: 4 };
$("#trackForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const res = $("#trackResult");
  res.hidden = false;
  try {
    const { data } = await api(`/orders/track?code=${encodeURIComponent(f.code.value)}&phone=${encodeURIComponent(f.phone.value)}`);
    const steps = t("steps");
    const done = TRACK_STEP[data.status] || 0;
    const stopped = ["cancelled", "returned"].includes(data.status);
    res.innerHTML = stopped
      ? `<p class="track-stop">#${data.code}: ${esc(t("statusLabel")[data.status] || data.status_label)}</p>`
      : `<p style="margin:0 0 10px;font-size:14px"><b>#${data.code}</b> · ${esc(t("statusLabel")[data.status] || data.status_label)} · ৳${data.total}</p>
        <div class="steps"><div class="bar" style="width:0"></div>${steps.map((s, i) => `<div class="step ${i < done ? "done" : ""}"><i></i>${s}</div>`).join("")}</div>`;
    if (!stopped) requestAnimationFrame(() => { res.querySelector(".bar").style.width = `calc(${(Math.max(done - 1, 0) / (steps.length - 1)) * 100}% - 8px)`; });
  } catch (err) {
    res.innerHTML = `<p class="track-stop">${esc(err.message)}</p>`;
  }
});

$("#nlForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await api("/subscribers", { method: "POST", body: { contact: e.target.elements[0].value } });
    e.target.reset();
    toast(t("subbed"));
  } catch (err) {
    toast(err.message);
  }
});
$("#couponApply").addEventListener("click", applyCoupon);
$("#couponInput").addEventListener("keydown", (e) => { if (e.key === "Enter") { e.preventDefault(); applyCoupon(); } });

// ===================== Init =====================
// Admin → Website Setup. Anything left empty keeps the built in design text.
function applyWebsiteSetup() {
  const h = SETTINGS.header || {}, f = SETTINGS.footer || {}, home = SETTINGS.home || {};
  const link = (url) => esc(url || "#");
  const ext = (url) => /^https?:/.test(url || "") ? ' target="_blank" rel="noopener"' : "";
  if (h.logo) $$(".header .logo-img").forEach((img) => { img.src = h.logo; img.classList.add("custom"); });
  if (f.logo) $$(".footer .logo-img").forEach((img) => { img.src = f.logo; img.classList.add("custom"); });
  if (h.sticky === false) $("#header").classList.add("no-sticky");
  if (h.lang_switcher === false) $("#langBtn").hidden = true;
  if (h.announcements?.length) {
    const spans = h.announcements.map((a) => `<span>${esc(a)}</span>`).join("");
    $(".marquee-track").innerHTML = spans + spans; // doubled for the endless scroll
  }
  if (h.banner) $(".topbar").outerHTML = `<a class="topbar-banner" href="${link(h.banner.link)}"${ext(h.banner.link)}><img src="${esc(h.banner.img)}" alt=""></a>`;
  if (h.menu?.length) {
    // custom links replace the plain links; the category mega menu stays
    $$("#nav > a.nav-link").forEach((a) => a.remove());
    $("#nav").insertAdjacentHTML("beforeend", h.menu.map((m) => `<a href="${link(m.link)}" class="nav-link"${ext(m.link)}>${esc(m.label)}</a>`).join(""));
  }
  if (f.about) { const p = $("[data-i18n=footAbout]"); p.textContent = f.about; p.removeAttribute("data-i18n"); }
  if (f.links_title) { $("#footLinksTitle").textContent = f.links_title; $("#footLinksTitle").removeAttribute("data-i18n"); }
  if (f.links?.length) {
    $("#footLinks").innerHTML = f.links.map((l) => `<li><a href="${link(l.link)}"${ext(l.link)}>${esc(l.label)}</a></li>`).join("")
      + `<li><a href="#help" data-i18n="lnkTrack">অর্ডার ট্র্যাক</a></li>`;
  }
  if (f.show_socials === false) $("#footSocials").hidden = true;
  const s = SETTINGS.socials || {};
  const extra = { messenger: "Messenger", youtube: "YouTube", twitter: "X", linkedin: "LinkedIn" };
  $("#footSocials").insertAdjacentHTML("beforeend", Object.entries(extra).filter(([k]) => s[k])
    .map(([k, label]) => `<a href="${link(s[k])}" target="_blank" rel="noopener" aria-label="${label}" class="soc-text">${label.slice(0, 2)}</a>`).join(""));
  const apps = [f.play_store && `<a href="${link(f.play_store)}" target="_blank" rel="noopener">Google Play</a>`, f.app_store && `<a href="${link(f.app_store)}" target="_blank" rel="noopener">App Store</a>`].filter(Boolean);
  if (f.payment_images?.length || apps.length) {
    $("#footPay").innerHTML = apps.join("") + (f.payment_images || []).map((src) => `<img src="${esc(src)}" alt="" loading="lazy">`).join("");
    $("#footPay").hidden = false;
  }
  if (f.copyright) $(".footer .copy").textContent = f.copyright;
  if (h.helpline) $(".contact").insertAdjacentHTML("afterbegin", `<li>☎️ <a href="tel:${esc(h.helpline)}">${esc(h.helpline)}</a></li>`);

  const banners = (list) => `<div class="promo-banners n${Math.min(list.length, 3)}">${list.map((b) => `<a href="${link(b.link || "#shop")}"${ext(b.link)}><img src="${esc(b.img)}" alt="" loading="lazy"></a>`).join("")}</div>`;
  const [b1 = [], b2 = [], b3 = []] = home.banners || [];
  if (home.slider?.length || b1.length) {
    $("#homePromo").innerHTML = (home.slider?.length ? `<div class="promo-slider" id="promoSlider">${home.slider.map((sl) => `<a href="${link(sl.link || "#shop")}"${ext(sl.link)}><img src="${esc(sl.img)}" alt=""></a>`).join("")}</div>` : "") + (b1.length ? banners(b1) : "");
    $("#homePromo").hidden = false;
    const sl = $("#promoSlider");
    if (sl && sl.children.length > 1) setInterval(() => {
      sl.scrollTo({ left: sl.scrollLeft + sl.clientWidth >= sl.scrollWidth - 4 ? 0 : sl.scrollLeft + sl.clientWidth, behavior: "smooth" });
    }, 5000);
  }
  if (b2.length) { $("#homeBanner2").innerHTML = banners(b2); $("#homeBanner2").hidden = false; }
  if (b3.length && $("#homeBanner3")) { $("#homeBanner3").innerHTML = banners(b3); $("#homeBanner3").hidden = false; }

  const store = (fn) => { try { return fn(); } catch { return null; } };
  if (SETTINGS.cookies && !store(() => localStorage.getItem("jm_cookies"))) {
    document.body.insertAdjacentHTML("beforeend", `<div class="cookie-bar" id="cookieBar"><p>${esc(SETTINGS.cookies)}</p><button class="btn btn-primary" id="cookieOk">OK</button></div>`);
    $("#cookieOk").addEventListener("click", () => { store(() => localStorage.setItem("jm_cookies", "1")); $("#cookieBar").remove(); });
  }
  if (SETTINGS.popup && !store(() => sessionStorage.getItem("jm_popup"))) {
    setTimeout(() => {
      // popup HTML is written by store admins in the rich text editor
      document.body.insertAdjacentHTML("beforeend", `<div class="site-popup" id="sitePopup"><div class="site-popup-box"><button class="site-popup-x" aria-label="Close">×</button>
        <div class="site-popup-body">${SETTINGS.popup.html || ""}</div>
        ${SETTINGS.popup.subscribe ? `<form class="nl-form" id="popupNl"><input required data-i18n-ph="nlPh" placeholder="ফোন নম্বর বা ইমেইল" maxlength="190"><button class="btn btn-primary" data-i18n="subscribe">সাবস্ক্রাইব</button></form>` : ""}</div></div>`);
      const close = () => { store(() => sessionStorage.setItem("jm_popup", "1")); $("#sitePopup").remove(); };
      $("#sitePopup").addEventListener("click", (e) => { if (e.target.id === "sitePopup" || e.target.closest(".site-popup-x")) close(); });
      $("#popupNl")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        try { await api("/subscribers", { method: "POST", body: { contact: e.target.elements[0].value } }); toast(t("subbed")); close(); } catch (err) { toast(err.message); }
      });
    }, 2500);
  }
}

// coming back from a product page's category menu
function applyPendingCategory() {
  let goTo = null;
  try { goTo = JSON.parse(sessionStorage.getItem("jm-goto") || "null"); sessionStorage.removeItem("jm-goto"); } catch (err) {}
  if (!goTo || window.PRODUCT_PAGE) return;
  filter.cat = goTo.cat;
  filter.sub = goTo.sub || "all";
  if (goTo.q) { filter.q = goTo.q; $("#searchInput").value = goTo.q; }
  renderCategories();
  renderProducts();
  setTimeout(() => $("#shop").scrollIntoView({ behavior: "smooth" }), 300);
}

// /product/{slug}: the server renders the product; here we wire gallery, quantity, cart and related items
function initProductPage() {
  const PP = window.PRODUCT_PAGE;
  if (!PP) return;
  const p = PRODUCTS.find((x) => x.id === PP.id);
  // section links in the shared header point back to the home page
  $$('a[href^="#"]').forEach((a) => { if (a.getAttribute("href").length > 1) a.setAttribute("href", "/" + a.getAttribute("href")); });
  $$(".logo").forEach((a) => a.setAttribute("href", "/"));
  if (p) window.track?.("ViewContent", { value: p.price, content_ids: [String(p.id)], content_name: p.en, content_type: "product" });

  $("#productPage").addEventListener("click", (e) => {
    const thumb = e.target.closest("[data-pp-img]");
    if (thumb) {
      $("#ppMain").src = thumb.dataset.ppImg;
      $$(".pp-thumb").forEach((b) => b.classList.toggle("on", b === thumb));
    }
    const step = e.target.closest("[data-pp-qty]");
    if (step) {
      const q = $("#ppQty");
      q.value = Math.min(Number(q.max) || 99, Math.max(1, Number(q.value || 1) + Number(step.dataset.ppQty)));
    }
    const copy = e.target.closest("[data-pp-copy]");
    if (copy) navigator.clipboard?.writeText(copy.dataset.ppCopy).then(() => toast(lang === "bn" ? "লিংক কপি হয়েছে" : "Link copied"));
    const catLink = e.target.closest("[data-pp-cat]");
    if (catLink) { e.preventDefault(); location.href = "/#shop"; }
  });
  const qty = () => Math.max(1, Math.min(99, parseInt($("#ppQty")?.value, 10) || 1));
  $("#ppAdd")?.addEventListener("click", (e) => p && addToCart(p.id, e.currentTarget, qty()));
  $("#ppBuy")?.addEventListener("click", () => {
    if (!p) return;
    const line = cart.find((l) => String(l.key) === String(p.id));
    line ? (line.qty = qty()) : cart.push({ key: String(p.id), qty: qty() });
    renderCart();
    openCheckout();
  });

  // more from the same category, then best sellers
  const same = PRODUCTS.filter((x) => x.id !== PP.id && x.stock > 0 && x.cat === "c" + PP.cat);
  const more = PRODUCTS.filter((x) => x.id !== PP.id && x.stock > 0 && !same.includes(x)).sort((a, b) => b.pop - a.pop);
  const related = [...same, ...more].slice(0, 8);
  if (related.length) {
    $("#relatedRow").innerHTML = related.map(productCard).join("");
    $("#related").hidden = false;
  }
}

// Setup → Payment Methods: only enabled methods, and the number to send money to
function applyPayments() {
  const enabled = SETTINGS.payments || ["cod", "bkash", "nagad", "rocket"];
  $$("[data-pay]").forEach((l) => { l.hidden = !enabled.includes(l.dataset.pay); });
  const first = [...$$("[data-pay]")].find((l) => !l.hidden)?.querySelector("input");
  if (first) { first.checked = true; $("#trxFields").hidden = first.value === "cod"; showPayNumber(first.value); }
}
function showPayNumber(method) {
  const rows = (SETTINGS.payment_numbers || []).filter((r) => r.gateway.includes(method));
  $("#payNumber").hidden = !rows.length || method === "cod";
  $("#payNumber").innerHTML = rows.map((r) => `${esc(r.gateway)} ${esc(r.type)}: <b>${esc(r.number)}</b>`).join("<br>")
    + (rows.length ? `<br><small>${lang === "bn" ? "এই নম্বরে Send Money করে নিচে নম্বর ও TrxID দিন" : "Send money to this number, then enter your number and TrxID below"}</small>` : "");
}

// Setup → Facebook Chat (Messenger button) and Google Map
function applyIntegrations() {
  if (SETTINGS.messenger) {
    document.body.insertAdjacentHTML("beforeend", `<a class="messenger-fab" href="https://m.me/${encodeURIComponent(SETTINGS.messenger)}" target="_blank" rel="noopener" aria-label="Messenger">
      <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M12 2C6.4 2 2 6.1 2 11.7c0 2.9 1.2 5.5 3.2 7.3v3.5l3-1.7c1.2.3 2.4.5 3.8.5 5.6 0 10-4.1 10-9.7S17.6 2 12 2zm1 13-2.5-2.7L5.6 15l5.4-5.8 2.6 2.7 4.8-2.7L13 15z"/></svg></a>`);
  }
  if (SETTINGS.map && $("#help")) {
    $("#help").insertAdjacentHTML("beforeend", `<div class="map-embed reveal"><iframe title="map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
      src="https://maps.google.com/maps?q=${encodeURIComponent(SETTINGS.map)}&z=16&output=embed"></iframe></div>`);
  }
}

function applySettings() {
  if (SETTINGS.phone) { $("#ftPhone").textContent = SETTINGS.phone; $("#ftPhone").href = "tel:" + SETTINGS.phone; }
  if (SETTINGS.email) { $("#ftEmail").textContent = SETTINGS.email; $("#ftEmail").href = "mailto:" + SETTINGS.email; }
  if (SETTINGS.address) { $("#ftAddress").textContent = SETTINGS.address; $("#ftAddress").removeAttribute("data-i18n"); }
  const s = SETTINGS.socials || {};
  const wa = s.whatsapp ? "https://wa.me/88" + String(s.whatsapp).replace(/\D/g, "").replace(/^88/, "") : null;
  $$("[data-todo]").forEach((a) => {
    const url = { facebook: s.facebook, instagram: s.instagram, tiktok: s.tiktok, whatsapp: wa }[a.dataset.todo];
    if (url) { a.href = url; a.target = "_blank"; a.rel = "noopener"; }
  });
}
applyWebsiteSetup();
initProductPage();
applyPayments();
applyIntegrations();
applySettings();
applyLang();
applyPendingCategory();
initNav();
magnetic();
cursor();
$("#statProducts").dataset.to = PRODUCTS.length;
counters();
countdown();
initReviewForm();
loadReviews();
window.addEventListener("load", () => setTimeout(() => $("#preloader").classList.add("done"), 400));
setTimeout(() => $("#preloader").classList.add("done"), 2000); // safety if a CDN or images are slow

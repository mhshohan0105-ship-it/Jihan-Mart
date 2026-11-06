// ===================== Data =====================
// All real products come from data/catalog.js (scraped from e99shop.com)
const CATALOG = window.CATALOG || { categories: [], products: [] };

// Bangla name, emoji and colour for each category id
const CAT_STYLE = {
  2: ["কসমেটিক ও বিউটি", "💄", "#ff3d8b,#9d174d"],
  3: ["জুয়েলারি", "💍", "#f59e0b,#7c2d12"],
  22: ["মেকআপ", "💋", "#ec4899,#831843"],
  19: ["পোশাক ও আন্ডার গার্মেন্টস", "👗", "#a855f7,#4c1d95"],
  76: ["পারফিউম ও সুগন্ধি", "🌸", "#f472b6,#701a75"],
  77: ["চকলেট", "🍫", "#b45309,#451a03"],
  4: ["মেয়েদের ব্যাগ", "👜", "#22c55e,#14532d"],
  27: ["বাথ", "🛁", "#38bdf8,#0c4a6e"],
  6: ["হেয়ার অ্যাক্সেসরিজ", "🎀", "#fb7185,#881337"],
  11: ["পেট আইটেম", "🐾", "#84cc16,#365314"],
  10: ["মোবাইল ও গ্যাজেট", "📱", "#6366f1,#1e1b4b"],
  8: ["কিচেন ও বেকিং", "🍳", "#f97316,#7c2d12"],
  7: ["বেবি আইটেম", "🍼", "#7dd3fc,#1e3a8a"],
  75: ["লাইট ও ডেকোরেশন", "💡", "#facc15,#713f12"],
  26: ["স্টেশনারি", "✏️", "#14b8a6,#134e4a"],
  20: ["সানগ্লাস", "🕶️", "#64748b,#0f172a"],
  12: ["অন্যান্য অ্যাক্সেসরিজ", "🧩", "#8b5cf6,#312e81"],
  9: ["বার্থডে ও পার্টি", "🎉", "#f43f5e,#4c0519"],
  5: ["মেয়েদের ঘড়ি", "⌚", "#d4a373,#3f2a14"],
  78: ["হোম ডেকর", "🏠", "#10b981,#064e3b"],
};
const SUB_BN = {
  21: "স্কিন কেয়ার", 24: "লিপস্টিক", 23: "মেকআপ ব্রাশ ও টুলস", 25: "আই অ্যাক্সেসরিজ", 73: "লিপ কেয়ার", 51: "নেইলস",
  15: "নেকলেস", 72: "ক্রাউন ও হেডপিস", 71: "নেকলেস সেট", 17: "হিজাব ব্রোচ ও পিন", 16: "ব্রেসলেট ও পায়েল", 14: "কানের দুল", 13: "ফিঙ্গার রিং",
};

const CATEGORIES = CATALOG.categories
  .map((c) => {
    const [bn, emoji, col] = CAT_STYLE[c.id] || [c.en, "🛍️", "#ff3d8b,#8b5cf6"];
    const subs = c.subs.map((s) => ({ id: "s" + s.id, en: s.en, bn: SUB_BN[s.id] || s.en }));
    if (subs.length) subs.push({ id: "other", en: "Others", bn: "অন্যান্য" });
    return { id: "c" + c.id, en: c.en, bn, emoji, c: `linear-gradient(160deg,${col})`, subs, count: c.count };
  })
  .sort((a, b) => b.count - a.count);
const catById = Object.fromEntries(CATEGORIES.map((c) => [c.id, c]));

const PRODUCTS = CATALOG.products.map((p, i) => ({
  id: p.id, cat: "c" + p.cat, sub: p.sub === null ? null : p.sub ? "s" + p.sub : "other",
  img: p.img, emoji: catById["c" + p.cat]?.emoji || "🛍️",
  price: p.price, old: p.old, tag: p.old ? "sale" : "", sales: p.sales, stock: p.stock,
  pop: p.sales * 2 - i / 1000, // best sellers first, newest first on ties
  flash: p.old > 0 && p.price <= 150 && p.stock > 0,
  bn: p.name, en: p.name,
}));
const subName = (p) => {
  const c = catById[p.cat];
  const s = p.sub && c.subs.find((x) => x.id === p.sub && x.id !== "other");
  return s ? nm(s) : nm(c);
};
const BOXES = {
  mb1: { id: "mb1", emoji: "🎁", price: 499, c: "linear-gradient(135deg,#ff3d8b,#8b5cf6)", bn: "মিস্ট্রি বক্স মিনি", en: "Mystery Box Mini" },
  mb2: { id: "mb2", emoji: "🎁", price: 999, c: "linear-gradient(135deg,#ff3d8b,#8b5cf6)", bn: "মিস্ট্রি বক্স প্লাস", en: "Mystery Box Plus" },
  mb3: { id: "mb3", emoji: "🎁", price: 1999, c: "linear-gradient(135deg,#ffc65c,#ff3d8b)", bn: "মিস্ট্রি বক্স রয়্যাল", en: "Mystery Box Royal" },
};
const FREE_SHIP = 2000;

// ===================== i18n =====================
// Bangla text lives in the HTML; English is here. Bangla is captured on load.
const EN = {
  ann1: "🚚 Free delivery on orders ৳2000+", ann2: "💸 Cash on delivery all over Bangladesh", ann3: "🎁 Mystery Box: a surprise in every box", ann4: "🔥 Flash sale is live!",
  navHome: "Home", navCat: "Categories", navOffers: "Hot Offers", navShop: "Shop", navMystery: "Mystery Box", navHelp: "Help",
  searchPh: "Search products…",
  heroEyebrow: "30,000+ products · Delivery across Bangladesh", heroT1: "Your daily needs,", heroT2: "our promise", slogan: "Quality · Trust · Satisfaction",
  heroSub: "Cosmetics, jewellery, rings, earrings and bags, all in one place. Order without login, pay when it arrives.",
  heroBtn1: "Shop now", heroBtn2: "🎁 Mystery Box", stat1: "+ products", stat2: "% off up to", stat3: "districts covered",
  tr1t: "Shop with confidence", tr1d: "Wrong or damaged item? Return or exchange within 7 days.",
  tr2t: "Safe payment", tr2d: "bKash, Nagad, Rocket, card or cash on delivery.",
  tr3t: "Fast delivery", tr3d: "1 to 2 days inside Dhaka, 2 to 4 days outside.",
  tr4t: "Great value", tr4d: "30,000+ products with new deals every day.",
  catEyebrow: "Categories", catTitle: "Pick what you love",
  flashEyebrow: "Flash sale", flashTitle: "Today's Flash Deals", hrs: "Hours", mins: "Minutes", secs: "Seconds",
  shopEyebrow: "Shop", shopTitle: "Best sellers & new arrivals", maxPrice: "Max price",
  sortPop: "Popular", sortLow: "Price: low → high", sortHigh: "Price: high → low", sortNew: "Newest", noResult: "No products found 😕",
  myEyebrow: "Mystery Box", myTitle: "Pay ৳499, get ৳1500+ worth of surprises",
  myDesc: "Each box holds 4 to 6 handpicked items: cosmetics, jewellery and accessories. You find out when you open it!",
  boxMini: "Mini", boxPlus: "Plus", boxRoyal: "Royal", spinBtn: "Spin the box", addBox: "Add to cart",
  revEyebrow: "Reviews", revTitle: "What customers say",
  revSortNew: "Newest first", revSortHigh: "Highest rating", revSortLow: "Lowest rating", revSortHelpful: "Most helpful", revMore: "More reviews",
  revWrite: "Write a review", revNamePh: "Your name", revTextPh: "Tell us about the product or your shopping experience…", revPhoto: "Add a photo (optional)", revSubmit: "Submit review", payTitle: "Ways to pay", payCod: "Cash on Delivery",
  helpEyebrow: "Help", helpTitle: "Delivery, returns & FAQ", delTitle: "🚚 Delivery charges",
  delIn: "Inside Dhaka", delSub: "Dhaka suburbs", delOut: "Outside Dhaka", delFree: "Orders ৳2000+", free: "Free",
  d12: "1 to 2 days", d23: "2 to 3 days", d24: "2 to 4 days",
  trackTitle: "📦 Track your order", trackDesc: "Enter your order number to see where your order is.", orderNoPh: "Order number (e.g. JM12345)", trackBtn: "Track",
  q1: "What is the return policy?", a1: "Within 7 days of delivery you can return or exchange an item that is wrong, damaged, or doesn't match the photo. It must be unused and in its packaging.",
  q2: "Do I need an account to order?", a2: "No. Just your name, phone number and address.",
  q3: "Is cash on delivery available?", a3: "Yes, all over Bangladesh. You can also pay in advance with bKash/Nagad/Rocket.",
  q4: "What's inside a Mystery Box?", a4: "4 to 6 handpicked items worth more than the box price. Every box is different!",
  nlTitle: "Get new offers first", nlDesc: "Leave your phone number and we'll SMS you when a flash sale starts.", phonePh: "01XXXXXXXXX", subscribe: "Subscribe",
  footAbout: "Welcome to Jihan Mart, your premier destination for online shopping in Bangladesh. Discover a world of convenience, quality, and variety as you explore our extensive range of products designed to meet all your needs and preferences.",
  footCat: "Top categories", footLinks: "Quick links", loadMore: "Show more products",
  lnkReturn: "Return Policy", lnkTerms: "Terms & Conditions", lnkPrivacy: "Privacy Policy", lnkTrack: "Track order", footContact: "Contact us",
  address: "Dhaka, Bangladesh", rights: "All Rights Reserved",
  yourCart: "Your cart", subtotal: "Subtotal", checkout: "Checkout (no login needed)", wishlist: "Wishlist",
  coTitle: "Confirm your order", namePh: "Your name", addrPh: "Full address", shipping: "Delivery", total: "Total", placeOrder: "Place order",
  thanks: "Thank you! Order placed", orderNo: "Order number:", willCall: "Our team will call you shortly to confirm.",
};
const DYN = {
  bn: {
    items: "টি পণ্য", quick: "দ্রুত দেখুন", added: "কার্টে যোগ হয়েছে ✓", wishAdd: "উইশলিস্টে যোগ হয়েছে ♥", wishRemove: "উইশলিস্ট থেকে সরানো হয়েছে",
    emptyCart: "কার্ট খালি", emptyWish: "উইশলিস্ট খালি", all: "সব", addCart: "কার্টে যোগ করুন",
    freeLeft: (n) => `আর <b>৳${n}</b> কিনলে ফ্রি ডেলিভারি 🚚`, freeOk: "🎉 আপনি ফ্রি ডেলিভারি পাচ্ছেন!",
    qvDesc: "প্রিমিয়াম কোয়ালিটি, ছবির সাথে ১০০% মিল। ক্যাশ অন ডেলিভারি ও ৭ দিনের রিটার্ন সুবিধা।",
    subbed: "সাবস্ক্রাইব হয়েছে! অফার শুরু হলে SMS পাবেন", boxAdded: "মিস্ট্রি বক্স কার্টে যোগ হয়েছে 🎁",
    steps: ["কনফার্মড", "প্যাকিং", "পথে", "ডেলিভারড"], trackNote: "ডেমো স্ট্যাটাস: আসল ট্র্যাকিং ব্যাকএন্ড যুক্ত হলে দেখাবে।",
    sold: (n) => `${n} টি বিক্রি হয়েছে`, buyNow: "এখনই কিনুন", addCartShort: "কার্টে যোগ", save: (n) => `৳${n} সাশ্রয়`, inStock: "স্টকে আছে", outStock: "স্টক শেষ", outToast: "দুঃখিত, পণ্যটি এখন স্টকে নেই",
    showing: (a, b) => `${b} টির মধ্যে ${a} টি পণ্য দেখানো হচ্ছে`, allIn: (c) => `সব ${c}`,
    helpful: "কাজের", revDelete: "মুছুন", revDelConfirm: "রিভিউটি মুছে ফেলবেন?", revBased: (n) => `${n} টি রিভিউ`,
    revWriteBtn: "রিভিউ লিখুন", revAll: "সব", revWithPhoto: "📷 ছবিসহ", revNoMatch: "এই ফিল্টারে কোনো রিভিউ নেই।",
    revEmpty: "এখনো কোনো রিভিউ নেই। আপনার অভিজ্ঞতা শেয়ার করে অন্যদের সাহায্য করুন!", revFirst: "প্রথম রিভিউ দিন",
    revAbout: "রিভিউ দিচ্ছেন", revPickStar: "স্টার বেছে নিন", starLabels: ["", "খুব খারাপ 😞", "খারাপ 😕", "মোটামুটি 🙂", "ভালো 😊", "অসাধারণ! 🤩"],
    errRating: "দয়া করে স্টার দিয়ে রেটিং দিন।", errName: "আপনার নাম লিখুন।", errText: "রিভিউ অন্তত ১০ অক্ষরের হতে হবে।",
    revThanks: "ধন্যবাদ! আপনার রিভিউ যোগ হয়েছে ⭐", qvReviews: "রিভিউ", qvNoRev: "এখনো কোনো রিভিউ নেই। প্রথম রিভিউ আপনিই দিন!",
  },
  en: {
    items: " items", quick: "Quick view", added: "Added to cart ✓", wishAdd: "Added to wishlist ♥", wishRemove: "Removed from wishlist",
    emptyCart: "Your cart is empty", emptyWish: "Your wishlist is empty", all: "All", addCart: "Add to cart",
    freeLeft: (n) => `Add <b>৳${n}</b> more for free delivery 🚚`, freeOk: "🎉 You've unlocked free delivery!",
    qvDesc: "Premium quality, 100% as pictured. Cash on delivery and 7 day returns.",
    subbed: "Subscribed! We'll SMS you when offers go live", boxAdded: "Mystery Box added to cart 🎁",
    steps: ["Confirmed", "Packing", "On the way", "Delivered"], trackNote: "Demo status: real tracking appears once the backend is connected.",
    sold: (n) => `${n} sold`, buyNow: "Buy now", addCartShort: "Add", save: (n) => `Save ৳${n}`, inStock: "In stock", outStock: "Out of stock", outToast: "Sorry, this item is out of stock",
    showing: (a, b) => `Showing ${a} of ${b} products`, allIn: (c) => `All ${c}`,
    helpful: "Helpful", revDelete: "Delete", revDelConfirm: "Delete this review?", revBased: (n) => `${n} review${n === 1 ? "" : "s"}`,
    revWriteBtn: "Write a review", revAll: "All", revWithPhoto: "📷 With photos", revNoMatch: "No reviews match this filter.",
    revEmpty: "No reviews yet. Share your experience and help other shoppers!", revFirst: "Write the first review",
    revAbout: "Reviewing", revPickStar: "Pick a rating", starLabels: ["", "Terrible 😞", "Poor 😕", "Okay 🙂", "Good 😊", "Excellent! 🤩"],
    errRating: "Please choose a star rating.", errName: "Please enter your name.", errText: "Your review needs at least 10 characters.",
    revThanks: "Thanks! Your review was added ⭐", qvReviews: "Reviews", qvNoRev: "No reviews yet. Be the first to review!",
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
  links.forEach((a) => { const sec = document.querySelector(a.getAttribute("href")); if (sec) spy.observe(sec); });

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
let filter = { cat: "all", sub: "all", q: "", max: 3000, sort: "pop" };
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
      ${visual(p)}
      <span class="p-shine" aria-hidden="true"></span>
    </div>
    <div class="p-info">
      <span class="p-cat">${subName(p)}</span>
      <h3 class="p-name">${esc(nm(p))}</h3>
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
  $("#catGrid").innerHTML = CATEGORIES.map((c) => {
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

function renderFlash() {
  // discounted items up to ৳150, biggest discount first, at most 2 per category for variety
  const per = {};
  const deals = PRODUCTS.filter((p) => p.flash)
    .sort((a, b) => (b.old - b.price) / b.old - (a.old - a.price) / a.old)
    .filter((p) => (per[p.cat] = (per[p.cat] || 0) + 1) <= 2)
    .slice(0, 14);
  $("#flashRow").innerHTML = deals.map(productCard).join("");
  bindTilt($("#flashRow"));
}

// ===================== Customer reviews =====================
// Saved in this browser for now. TODO: post to the backend (with admin approval) so every visitor sees them.
let reviews = store.get("jm-reviews", []); // [{id, pid, name, rating, text, photo, date, helpful}]
let helpfulVoted = store.get("jm-helpful", []); // review ids this browser marked helpful
const rev = { filter: "all", sort: "new", shown: 6, pid: null, rating: 0, photo: "" };

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
      <div><b>${esc(r.name)}</b><small>${timeAgo(r.date)}</small></div>
      ${starBar(r.rating, "sm")}
    </header>
    ${p ? `<button class="rev-product" data-quick="${p.id}">${p.img ? `<img src="${p.img}" alt="" loading="lazy">` : p.emoji}<span>${esc(nm(p))}</span></button>` : ""}
    <p>${esc(r.text)}</p>
    ${r.photo ? `<img class="rev-img" src="${r.photo}" alt="" loading="lazy">` : ""}
    <footer>
      <button class="helpful ${voted ? "on" : ""}" data-helpful="${r.id}">👍 ${t("helpful")} ${r.helpful ? `<span>${r.helpful}</span>` : ""}</button>
      <button class="rev-del" data-del-review="${r.id}">${t("revDelete")}</button>
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

function toggleHelpful(id) {
  const r = reviews.find((x) => x.id === id);
  if (!r) return;
  const voted = helpfulVoted.includes(id);
  r.helpful += voted ? -1 : 1;
  helpfulVoted = voted ? helpfulVoted.filter((x) => x !== id) : [...helpfulVoted, id];
  store.set("jm-reviews", reviews);
  store.set("jm-helpful", helpfulVoted);
  renderReviews();
}

function deleteReview(id) {
  if (!confirm(t("revDelConfirm"))) return;
  reviews = reviews.filter((x) => x.id !== id);
  store.set("jm-reviews", reviews);
  renderReviews();
  renderFlash();
  renderProducts();
}

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
  $("#reviewForm").reset();
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

function submitReview(e) {
  e.preventDefault();
  const f = e.target;
  const name = f.elements.name.value.trim();
  const text = f.elements.text.value.trim();
  const err = !rev.rating ? t("errRating") : name.length < 2 ? t("errName") : text.length < 10 ? t("errText") : "";
  if (err) {
    const el = $("#revErr");
    el.textContent = err;
    el.hidden = false;
    el.classList.remove("shake"); void el.offsetWidth; el.classList.add("shake");
    return;
  }
  const r = { id: Date.now().toString(36) + Math.random().toString(36).slice(2, 6), pid: rev.pid, name, rating: rev.rating, text, photo: rev.photo, date: Date.now(), helpful: 0 };
  reviews.unshift(r);
  if (!store.set("jm-reviews", reviews) && r.photo) {
    // storage full: keep the review, drop the photo
    r.photo = "";
    store.set("jm-reviews", reviews);
  }
  closeAll();
  toast(t("revThanks"));
  rev.filter = "all";
  rev.sort = "new";
  $("#revSort").value = "new";
  renderReviews();
  renderFlash();
  renderProducts();
  if (r.pid) setTimeout(() => quickView(r.pid), 450);
}

function initReviewForm() {
  const pick = $("#starPick");
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
function addToCart(key, fromEl) {
  const item = itemByKey(key);
  if (soldOut(item)) { toast(t("outToast")); return; }
  const line = cart.find((l) => String(l.key) === String(key));
  line ? line.qty++ : cart.push({ key: String(key), qty: 1 });
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
      ${qvReviews(p)}
    </div>`;
  openPanel($("#quickView"));
}

function openCheckout() {
  closeAll();
  $("#checkoutForm").hidden = false;
  $("#coSuccess").hidden = true;
  updateCheckoutSum();
  openPanel($("#checkoutModal"));
}
function updateCheckoutSum() {
  const sub = cart.reduce((s, l) => s + l.qty * (itemByKey(l.key)?.price || 0), 0);
  const ship = sub >= FREE_SHIP ? 0 : Number($("#areaSel").value);
  $("#coSub").textContent = "৳" + sub;
  $("#coShip").textContent = ship ? "৳" + ship : (lang === "bn" ? "ফ্রি" : "Free");
  $("#coTotal").textContent = "৳" + (sub + ship);
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
    const now = new Date(), end = new Date(now);
    end.setHours(23, 59, 59, 999); // flash sale resets every midnight
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
$("#searchInput").addEventListener("input", (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    filter.q = e.target.value;
    renderProducts();
    if (filter.q) $("#shop").scrollIntoView({ behavior: "smooth" });
  }, 250);
});
$("#priceRange").addEventListener("input", (e) => { filter.max = Number(e.target.value); $("#priceVal").textContent = "৳" + filter.max; renderProducts(); });
$("#sortSel").addEventListener("change", (e) => { filter.sort = e.target.value; renderProducts(); });
$("#loadMore").addEventListener("click", () => { shown += PAGE; renderProducts(false); });

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
$("#addBox").addEventListener("click", (e) => { addToCart(selectedBox, e.currentTarget); toast(t("boxAdded")); });

// Checkout (frontend demo: connect to your backend/order API here)
$("#checkoutForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const id = "JM" + Math.floor(10000 + Math.random() * 90000);
  $("#orderId").textContent = id;
  $("#checkoutForm").hidden = true;
  $("#coSuccess").hidden = false;
  cart = [];
  renderCart();
});

// Order tracking (demo)
$("#trackForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const steps = t("steps"), done = 2;
  const res = $("#trackResult");
  res.hidden = false;
  res.innerHTML = `<div class="steps"><div class="bar" style="width:0"></div>${steps.map((s, i) => `<div class="step ${i < done ? "done" : ""}"><i></i>${s}</div>`).join("")}</div>
    <p style="margin:12px 0 0;font-size:13px">${t("trackNote")}</p>`;
  requestAnimationFrame(() => { res.querySelector(".bar").style.width = `calc(${((done - 1) / (steps.length - 1)) * 100}% - 8px)`; });
});

$("#nlForm").addEventListener("submit", (e) => { e.preventDefault(); e.target.reset(); toast(t("subbed")); });

// ===================== Init =====================
applyLang();
initNav();
magnetic();
cursor();
$("#statProducts").dataset.to = PRODUCTS.length;
counters();
countdown();
initReviewForm();
window.addEventListener("load", () => setTimeout(() => $("#preloader").classList.add("done"), 400));
setTimeout(() => $("#preloader").classList.add("done"), 2000); // safety if a CDN or images are slow

<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('shop/styles.css') }}?v={{ filemtime(public_path('shop/styles.css')) }}">
  <meta name="theme-color" content="#1b2b57">
  @isset($product)
    @include('partials.site-head', ['title' => $product['meta_title'], 'description' => $product['meta_description'], 'image' => $product['images'][0] ?? null])
    <link rel="canonical" href="{{ $product['url'] }}">
    <meta property="og:type" content="product">
    <meta property="product:price:amount" content="{{ $product['price'] }}">
    <meta property="product:price:currency" content="BDT">
    <script type="application/ld+json">{!! json_encode($product['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
  @else
    @include('partials.site-head')
  @endisset
</head>
<body class="{{ isset($product) ? 'product-page' : '' }}">

  <!-- Preloader -->
  <div id="preloader">
    <div class="loader-cube"><span>J</span><span>M</span></div>
  </div>

  <!-- Top announcement bar -->
  <div class="topbar">
    <div class="marquee">
      <div class="marquee-track">
        <span data-i18n="ann1">🍳 রান্নাঘরের সব প্রয়োজনীয় জিনিস এক জায়গায়</span>
        <span data-i18n="ann2">💸 ক্যাশ অন ডেলিভারি সারা বাংলাদেশে</span>
        <span data-i18n="ann3">🔥 নতুন কুকওয়্যার ও কিচেন কালেকশন এসেছে</span>
        <span data-i18n="ann4">🚚 ৳২০০০+ অর্ডারে ফ্রি ডেলিভারি</span>
        <span data-i18n="ann1">🍳 রান্নাঘরের সব প্রয়োজনীয় জিনিস এক জায়গায়</span>
        <span data-i18n="ann2">💸 ক্যাশ অন ডেলিভারি সারা বাংলাদেশে</span>
        <span data-i18n="ann3">🔥 নতুন কুকওয়্যার ও কিচেন কালেকশন এসেছে</span>
        <span data-i18n="ann4">🚚 ৳২০০০+ অর্ডারে ফ্রি ডেলিভারি</span>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="header" id="header">
    <a href="#home" class="logo"><img class="logo-img" src="{{ asset('shop/assets/jihan-mart-logo.jpg') }}" alt="Jihan Mart" width="46" height="46"><span class="logo-mark">Jihan</span><span class="logo-text">Mart</span></a>

    <nav class="nav" id="nav">
      <span class="nav-pill" id="navPill" aria-hidden="true"></span>
      <a href="#home" class="nav-link" data-i18n="navHome">হোম</a>
      <div class="nav-drop">
        <a href="#categories" class="nav-link" data-i18n="navCat">ক্যাটাগরি</a>
        <div class="mega" id="mega"></div>
      </div>
      <a href="#flash" class="nav-link" data-i18n="navOffers">হট অফার</a>
      <a href="#shop" class="nav-link" data-i18n="navShop">শপ</a>
      <a href="#mystery" class="nav-link" data-i18n="navMystery">মিস্ট্রি বক্স</a>
      <a href="#help" class="nav-link" data-i18n="navHelp">সাহায্য</a>
    </nav>

    <div class="header-actions">
      <div class="search">
        <input type="search" id="searchInput" data-i18n-ph="searchPh" placeholder="পণ্য খুঁজুন…" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="searchSuggest" aria-autocomplete="list">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <div class="search-suggest" id="searchSuggest" role="listbox" hidden></div>
      </div>
      <button class="lang-btn" id="langBtn" aria-label="Change language">EN</button>
      <button class="icon-btn" id="wishBtn" aria-label="Wishlist">
        <svg viewBox="0 0 24 24" width="22" height="22"><path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 2.3 4 6.3 4c2.2 0 3.6 1.2 4.4 2.4h2.6C14.1 5.2 15.5 4 17.7 4c4 0 6 4.3 4.3 7.7C19.5 16.4 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
        <span class="badge" id="wishCount">0</span>
      </button>
      <button class="icon-btn" id="cartBtn" aria-label="Cart">
        <svg viewBox="0 0 24 24" width="22" height="22"><path d="M5 7h14l-1.2 11.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 7z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9 7a3 3 0 0 1 6 0" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
        <span class="badge" id="cartCount">0</span>
      </button>
      <button class="icon-btn burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
    <div class="scroll-progress" id="scrollProgress"></div>
  </header>

  @isset($product)
    @include('partials.product-detail')
  @endisset

  <!-- Hero with 3D canvas -->
  <section class="hero" id="home">
    <canvas id="hero3d"></canvas>
    <div class="hero-glow g1"></div>
    <div class="hero-glow g2"></div>
    <div class="hero-content">
      <p class="eyebrow reveal" data-i18n="heroEyebrow">কিচেন ও কুকওয়্যার স্টোর · সারা বাংলাদেশে ডেলিভারি</p>
      <h1 class="hero-title reveal">
        <span data-i18n="heroT1">রান্নাঘর হোক আরও সুন্দর,</span><br>
        <span class="shine" data-i18n="heroT2">রান্না হোক আরও সহজ</span>
      </h1>
      <p class="hero-sub reveal" data-i18n="heroSub">কুকওয়্যার, ডিশ র‍্যাক, স্টোরেজ বক্স, কিচেন গ্যাজেট আর অর্গানাইজার, সব এক জায়গায়। লগইন ছাড়াই অর্ডার করুন, পণ্য হাতে পেয়ে টাকা দিন।</p>
      <div class="hero-cta reveal">
        <a href="#shop" class="btn btn-primary magnetic" data-i18n="heroBtn1">এখনই কিনুন</a>
        <a href="#mystery" class="btn btn-ghost magnetic" data-i18n="heroBtn2">🎁 মিস্ট্রি বক্স</a>
      </div>
      <div class="hero-stats reveal">
        <div><b class="count" id="statProducts" data-to="0">0</b><span data-i18n="stat1">+ পণ্য</span></div>
        <div><b class="count" data-to="50">0</b><span data-i18n="stat2">% পর্যন্ত ছাড়</span></div>
        <div><b class="count" data-to="64">0</b><span data-i18n="stat3">জেলায় ডেলিভারি</span></div>
      </div>
      <p class="hero-slogan reveal" data-i18n="slogan">মান · টেকসই · সাশ্রয়</p>
    </div>
    <a href="#trust" class="scroll-hint" aria-label="Scroll"><span></span></a>
  </section>

  <!-- Trust strip -->
  <section class="trust" id="trust">
    <div class="trust-item reveal tilt">
      <div class="ti-icon">🛡️</div>
      <h3 data-i18n="tr1t">নিশ্চিন্তে কেনাকাটা</h3>
      <p data-i18n="tr1d">ডেলিভারির সময় কুরিয়ারের সামনে পণ্য চেক করে নিন। সমস্যা থাকলে আমরা বদলে দেব।</p>
    </div>
    <div class="trust-item reveal tilt">
      <div class="ti-icon">💳</div>
      <h3 data-i18n="tr2t">নিরাপদ পেমেন্ট</h3>
      <p data-i18n="tr2d">বিকাশ, নগদ, রকেট, কার্ড অথবা ক্যাশ অন ডেলিভারি।</p>
    </div>
    <div class="trust-item reveal tilt">
      <div class="ti-icon">🚚</div>
      <h3 data-i18n="tr3t">দ্রুত ডেলিভারি</h3>
      <p data-i18n="tr3d">ঢাকার ভেতরে ১ থেকে ২ দিন, ঢাকার বাইরে ২ থেকে ৪ দিন।</p>
    </div>
    <div class="trust-item reveal tilt">
      <div class="ti-icon">🍽️</div>
      <h3 data-i18n="tr4t">সেরা দাম</h3>
      <p data-i18n="tr4d">রান্নাঘরের প্রতিটি প্রয়োজনে বাছাই করা পণ্য, প্রতিদিন নতুন অফার।</p>
    </div>
  </section>

  <!-- Flash sale -->
  <section class="section flash" id="flash">
    <div class="flash-head reveal">
      <div>
        <p class="eyebrow" data-i18n="flashEyebrow">ফ্ল্যাশ সেল</p>
        <h2><span data-i18n="flashTitle">আজকের ফ্ল্যাশ ডিল</span> <span class="fire">🔥</span></h2>
      </div>
      <div class="countdown" id="countdown">
        <div><b id="cdH">00</b><span data-i18n="hrs">ঘণ্টা</span></div>
        <i>:</i>
        <div><b id="cdM">00</b><span data-i18n="mins">মিনিট</span></div>
        <i>:</i>
        <div><b id="cdS">00</b><span data-i18n="secs">সেকেন্ড</span></div>
      </div>
    </div>
    <div class="product-grid" id="flashRow"></div>
    <div class="load-more-wrap"><button class="btn btn-ghost" id="flashMore" hidden><span data-i18n="flashMore">আরও অফার দেখুন</span> <span id="flashLeft"></span></button></div>
  </section>

  <!-- Admin → Pages → Home Page: slider + banner section 1 -->
  <section class="section home-promo" id="homePromo" hidden></section>

  <!-- Categories -->
  <section class="section" id="categories">
    <div class="section-head reveal">
      <p class="eyebrow" data-i18n="catEyebrow">ক্যাটাগরি</p>
      <h2 data-i18n="catTitle">কিচেন থেকে পুরো ঘর, সব এক জায়গায়</h2>
    </div>
    <div class="cat-grid" id="catGrid"></div>
  </section>

  <!-- Shop -->
  <section class="section" id="shop">
    <div class="section-head reveal">
      <p class="eyebrow" data-i18n="shopEyebrow">শপ</p>
      <h2 data-i18n="shopTitle">জনপ্রিয় ও নতুন পণ্য</h2>
    </div>

    <div class="filters reveal">
      <div class="chips" id="chips"></div>
      <div class="filter-right">
        <label class="price-filter">
          <span data-i18n="maxPrice">সর্বোচ্চ দাম</span>
          <b id="priceVal">৳3000</b>
          <input type="range" id="priceRange" min="99" max="3000" step="50" value="3000">
        </label>
        <select id="sortSel" aria-label="Sort">
          <option value="pop" data-i18n="sortPop">জনপ্রিয়</option>
          <option value="low" data-i18n="sortLow">দাম: কম → বেশি</option>
          <option value="high" data-i18n="sortHigh">দাম: বেশি → কম</option>
          <option value="new" data-i18n="sortNew">নতুন</option>
        </select>
      </div>
    </div>

    <div class="chips sub-chips" id="subChips" hidden></div>
    <p class="result-count" id="resultCount"></p>

    <div class="product-grid" id="productGrid"></div>
    <p class="empty" id="emptyMsg" data-i18n="noResult" hidden>কোনো পণ্য পাওয়া যায়নি 😕</p>
    <div class="load-more-wrap"><button class="btn btn-ghost magnetic" id="loadMore" data-i18n="loadMore" hidden>আরও পণ্য দেখুন</button></div>
  </section>

  <section class="section home-promo" id="homeBanner2" hidden></section>

  <!-- Mystery box -->
  <section class="section mystery" id="mystery">
    <div class="mystery-inner">
      <div class="cube-stage reveal">
        <div class="cube" id="mysteryCube">
          <div class="face f-front">?</div>
          <div class="face f-back">🎁</div>
          <div class="face f-right">💄</div>
          <div class="face f-left">💍</div>
          <div class="face f-top">✨</div>
          <div class="face f-bottom">👜</div>
        </div>
        <div class="cube-shadow"></div>
      </div>
      <div class="mystery-text reveal">
        <p class="eyebrow" data-i18n="myEyebrow">মিস্ট্রি বক্স</p>
        <h2 data-i18n="myTitle">৳৪৯৯ দিয়ে পান ৳১৫০০+ মূল্যের সারপ্রাইজ</h2>
        <p data-i18n="myDesc">প্রতিটা বক্সে থাকে ৪ থেকে ৬টি বাছাই করা পণ্য: কিচেন গ্যাজেট, স্টোরেজ আর রোজকার দরকারি জিনিস। খুললেই জানবেন কী আছে!</p>
        <div class="box-options" id="boxOptions">
          <button class="box-opt active" data-box="mb1"><b>৳499</b><span data-i18n="boxMini">মিনি</span></button>
          <button class="box-opt" data-box="mb2"><b>৳999</b><span data-i18n="boxPlus">প্লাস</span></button>
          <button class="box-opt" data-box="mb3"><b>৳1999</b><span data-i18n="boxRoyal">রয়্যাল</span></button>
        </div>
        <div class="hero-cta">
          <button class="btn btn-primary magnetic" id="spinBox" data-i18n="spinBtn">বক্স ঘুরিয়ে দেখুন</button>
          <button class="btn btn-ghost magnetic" id="addBox" data-i18n="addBox">কার্টে যোগ করুন</button>
        </div>
      </div>
    </div>
  </section>

  <section class="section home-promo" id="homeBanner3" hidden></section>

  <!-- Reviews -->
  <section class="section" id="reviews">
    <div class="section-head reveal">
      <p class="eyebrow" data-i18n="revEyebrow">রিভিউ</p>
      <h2 data-i18n="revTitle">ক্রেতারা কী বলছেন</h2>
    </div>
    <div class="rev-layout">
      <aside class="rev-summary reveal" id="revSummary"></aside>
      <div class="rev-main">
        <div class="rev-toolbar reveal">
          <div class="chips" id="revFilters"></div>
          <select id="revSort" aria-label="Sort reviews">
            <option value="new" data-i18n="revSortNew">নতুন আগে</option>
            <option value="high" data-i18n="revSortHigh">বেশি রেটিং আগে</option>
            <option value="low" data-i18n="revSortLow">কম রেটিং আগে</option>
            <option value="helpful" data-i18n="revSortHelpful">সবচেয়ে কাজের</option>
          </select>
        </div>
        <div class="rev-list" id="revList"></div>
        <div class="load-more-wrap"><button class="btn btn-ghost" id="revMore" data-i18n="revMore" hidden>আরও রিভিউ</button></div>
      </div>
    </div>
  </section>

  <!-- Payment methods -->
  <section class="section pay reveal">
    <h3 data-i18n="payTitle">যেভাবে পেমেন্ট করবেন</h3>
    <div class="pay-row">
      <span class="pay-chip cod" data-i18n="payCod">ক্যাশ অন ডেলিভারি</span>
      <span class="pay-chip bkash">bKash</span>
      <span class="pay-chip nagad">Nagad</span>
      <span class="pay-chip rocket">Rocket</span>
      <span class="pay-chip card">Visa / Mastercard</span>
    </div>
  </section>

  <!-- Help: delivery, returns, FAQ -->
  <section class="section" id="help">
    <div class="section-head reveal">
      <p class="eyebrow" data-i18n="helpEyebrow">সাহায্য</p>
      <h2 data-i18n="helpTitle">ডেলিভারি, রিটার্ন ও প্রশ্নোত্তর</h2>
    </div>
    <div class="help-grid">
      <div class="help-card reveal tilt">
        <h3 data-i18n="delTitle">🚚 ডেলিভারি চার্জ</h3>
        <table>
          <tr><td data-i18n="delIn">ঢাকার ভেতরে</td><td>৳60 · <span data-i18n="d12">১ থেকে ২ দিন</span></td></tr>
          <tr><td data-i18n="delSub">ঢাকার আশেপাশে</td><td>৳100 · <span data-i18n="d23">২ থেকে ৩ দিন</span></td></tr>
          <tr><td data-i18n="delOut">ঢাকার বাইরে</td><td>৳120 · <span data-i18n="d24">২ থেকে ৪ দিন</span></td></tr>
          <tr><td data-i18n="delFree">৳২০০০+ অর্ডার</td><td class="free" data-i18n="free">ফ্রি</td></tr>
        </table>
      </div>
      <div class="help-card reveal tilt">
        <h3 data-i18n="trackTitle">📦 অর্ডার ট্র্যাক করুন</h3>
        <p data-i18n="trackDesc">অর্ডার নম্বর ও ফোন নম্বর দিয়ে আপনার অর্ডার কোথায় আছে দেখুন।</p>
        <form class="track-form" id="trackForm">
          <input name="code" required data-i18n-ph="orderNoPh" placeholder="অর্ডার নম্বর (যেমন 1001)">
          <input name="phone" type="tel" required data-i18n-ph="phonePh" placeholder="01XXXXXXXXX">
          <button class="btn btn-primary" data-i18n="trackBtn">ট্র্যাক</button>
        </form>
        <div class="track-result" id="trackResult" hidden></div>
      </div>
      <div class="faq reveal">
        <details>
          <summary data-i18n="q1">রিটার্ন পলিসি কী?</summary>
          <p data-i18n="a1">ডেলিভারির সময় কুরিয়ারের সামনেই পণ্য চেক করে নিন। ভাঙা, ক্ষতিগ্রস্ত, ভুল পণ্য বা ছবির সাথে না মিললে আমরা বদলে দেব। পণ্য নিতে না চাইলে শুধু ডেলিভারি চার্জ দিয়ে কুরিয়ারের কাছে ফেরত দিতে পারবেন। ওয়ারেন্টিযুক্ত পণ্যে নির্ধারিত মেয়াদ পর্যন্ত ওয়ারেন্টি সুবিধা পাবেন।</p>
        </details>
        <details>
          <summary data-i18n="q2">অর্ডার করতে কি অ্যাকাউন্ট লাগবে?</summary>
          <p data-i18n="a2">না। শুধু নাম, ফোন নম্বর আর ঠিকানা দিয়েই অর্ডার করা যায়।</p>
        </details>
        <details>
          <summary data-i18n="q3">ক্যাশ অন ডেলিভারি আছে?</summary>
          <p data-i18n="a3">হ্যাঁ, সারা বাংলাদেশে ক্যাশ অন ডেলিভারি আছে। চাইলে বিকাশ/নগদ/রকেটে আগেও পেমেন্ট করতে পারেন।</p>
        </details>
        <details>
          <summary data-i18n="q4">মিস্ট্রি বক্সে কী থাকে?</summary>
          <p data-i18n="a4">বক্সের দামের চেয়ে বেশি মূল্যের ৪ থেকে ৬টি বাছাই করা পণ্য থাকে। প্রতিটা বক্স আলাদা!</p>
        </details>
      </div>
    </div>
  </section>

  <!-- Newsletter -->
  <section class="section newsletter reveal">
    <h2 data-i18n="nlTitle">নতুন কিচেন অফার সবার আগে পান</h2>
    <p data-i18n="nlDesc">ফোন নম্বর দিন, ফ্ল্যাশ সেল শুরু হলে SMS পাবেন।</p>
    <form id="nlForm" class="nl-form">
      <input required data-i18n-ph="nlPh" placeholder="ফোন নম্বর বা ইমেইল" maxlength="190">
      <button class="btn btn-primary" data-i18n="subscribe">সাবস্ক্রাইব</button>
    </form>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="foot-grid">
      <div>
        <a href="#home" class="logo"><img class="logo-img" src="{{ asset('shop/assets/jihan-mart-logo.jpg') }}" alt="Jihan Mart" width="46" height="46"><span class="logo-mark">Jihan</span><span class="logo-text">Mart</span></a>
        <p data-i18n="footAbout">Jihan Mart এ আপনাকে স্বাগতম, রান্নাঘরের সব প্রয়োজনীয় জিনিসের নির্ভরযোগ্য অনলাইন ঠিকানা। কুকওয়্যার, ডিশ র‍্যাক, স্টোরেজ বক্স থেকে কিচেন গ্যাজেট, মানসম্মত পণ্য সাশ্রয়ী দামে পৌঁছে যায় সারা বাংলাদেশে।</p>
        <div class="socials" id="footSocials">
          <a href="#" data-todo="facebook" aria-label="Facebook">
            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M14 8V6.5c0-.7.2-1.2 1.2-1.2H17V2.1A24 24 0 0 0 14.4 2C11.9 2 10.2 3.5 10.2 6.3V8H7.5v3.6h2.7V22H14V11.6h2.8l.4-3.6H14z"/></svg>
          </a>
          <a href="#" data-todo="instagram" aria-label="Instagram">
            <svg viewBox="0 0 24 24" width="18" height="18"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>
          </a>
          <a href="#" data-todo="tiktok" aria-label="TikTok">
            <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M16.6 2h-3.4v13.4a2.9 2.9 0 1 1-2.1-2.8V9.1a6.4 6.4 0 1 0 5.5 6.3V8.6a8 8 0 0 0 4.4 1.4V6.6a4.5 4.5 0 0 1-4.4-4.6z"/></svg>
          </a>
          <a href="#" data-todo="whatsapp" aria-label="WhatsApp">
            <svg viewBox="0 0 32 32" width="18" height="18"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.6c-2 0-4-.5-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.6 10.6 0 1 1 16 26.6z"/></svg>
          </a>
        </div>
      </div>
      <div>
        <h4 data-i18n="footCat">টপ ক্যাটাগরি</h4>
        <ul id="footCats"></ul>
      </div>
      <div>
        <h4 data-i18n="footLinks" id="footLinksTitle">কুইক লিংক</h4>
        <ul id="footLinks">
          <li><a href="#help" data-i18n="lnkReturn">রিটার্ন পলিসি</a></li>
          <li><a href="#help" data-i18n="lnkTerms">শর্তাবলি</a></li>
          <li><a href="#help" data-i18n="lnkPrivacy">প্রাইভেসি পলিসি</a></li>
          <li><a href="#help" data-i18n="lnkTrack">অর্ডার ট্র্যাক</a></li>
        </ul>
      </div>
      <div>
        <h4 data-i18n="footContact">যোগাযোগ</h4>
        <ul class="contact">
          <!-- filled from Admin settings (contact_address, contact_phone, contact_email) -->
          <li>📍 <span id="ftAddress" data-i18n="address">ঢাকা, বাংলাদেশ</span></li>
          <li>📞 <a href="#" id="ftPhone">01XXXXXXXXX</a></li>
          <li>✉️ <a href="#" id="ftEmail">email@example.com</a></li>
          <li>💬 <a href="#" data-todo="whatsapp">WhatsApp</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-pay" id="footPay" hidden></div>
    <p class="copy">Copyright © <span id="year"></span> Jihan Mart · <span data-i18n="rights">সর্বস্বত্ব সংরক্ষিত</span></p>
  </footer>

  <!-- Floating WhatsApp -->
  <a class="wa-float" href="#" data-todo="whatsapp" aria-label="WhatsApp">
    <svg viewBox="0 0 32 32" width="28" height="28"><path fill="#fff" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.6c-2 0-4-.5-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.6 10.6 0 1 1 16 26.6zm5.8-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.2-.2-.3 0-.5.1-.7l.5-.6.3-.5v-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.6 5.7 5 .8.4 1.4.6 1.9.7.8.3 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
  </a>

  <!-- Cart drawer -->
  <div class="overlay" id="overlay"></div>
  <aside class="drawer" id="cartDrawer" aria-label="Cart">
    <div class="drawer-head">
      <h3 data-i18n="yourCart">আপনার কার্ট</h3>
      <button class="close" data-close aria-label="Close">✕</button>
    </div>
    <div class="free-ship">
      <p id="freeShipMsg"></p>
      <div class="bar"><span id="freeShipBar"></span></div>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="drawer-foot">
      <div class="row"><span data-i18n="subtotal">সাবটোটাল</span><b id="subtotal">৳0</b></div>
      <button class="btn btn-primary full" id="checkoutBtn" data-i18n="checkout">অর্ডার করুন (লগইন লাগবে না)</button>
    </div>
  </aside>

  <!-- Wishlist drawer -->
  <aside class="drawer" id="wishDrawer" aria-label="Wishlist">
    <div class="drawer-head">
      <h3 data-i18n="wishlist">উইশলিস্ট</h3>
      <button class="close" data-close aria-label="Close">✕</button>
    </div>
    <div class="cart-items" id="wishItems"></div>
  </aside>

  <!-- Quick view modal -->
  <div class="modal" id="quickView">
    <div class="modal-box qv">
      <button class="close" data-close aria-label="Close">✕</button>
      <div class="qv-inner" id="qvInner"></div>
    </div>
  </div>

  <!-- Write review modal -->
  <div class="modal" id="reviewModal">
    <div class="modal-box">
      <button class="close" data-close aria-label="Close">✕</button>
      <h3 data-i18n="revWrite">রিভিউ লিখুন</h3>
      <div class="rev-for" id="revFor"></div>
      <!-- product reviews: buyers confirm their delivered order first -->
      <form id="revVerify" class="co-form" novalidate hidden>
        <p class="rev-hint" data-i18n="revVerifyHint">শুধু যারা এই পণ্যটি কিনেছেন তারাই রিভিউ দিতে পারবেন। আপনার অর্ডার নম্বর ও যে ফোন নম্বরে অর্ডার করেছিলেন সেটি দিন।</p>
        <input name="order" inputmode="numeric" autocomplete="off" data-i18n-ph="revOrderPh" placeholder="অর্ডার নম্বর (যেমন 1001)">
        <input name="phone" type="tel" autocomplete="tel" data-i18n-ph="phonePh" placeholder="01XXXXXXXXX">
        <p class="form-err" id="revVerifyErr" hidden></p>
        <button class="btn btn-primary full" data-i18n="revVerifyBtn">যাচাই করুন</button>
      </form>
      <form id="reviewForm" class="co-form" novalidate>
        <p class="rev-ok" id="revVerified" hidden></p>
        <div class="star-pick" id="starPick" role="radiogroup" aria-label="Rating">
          <button type="button" data-star="1" aria-label="1">★</button><button type="button" data-star="2" aria-label="2">★</button><button type="button" data-star="3" aria-label="3">★</button><button type="button" data-star="4" aria-label="4">★</button><button type="button" data-star="5" aria-label="5">★</button>
          <span class="star-label" id="starLabel"></span>
        </div>
        <input name="name" maxlength="40" data-i18n-ph="revNamePh" placeholder="আপনার নাম">
        <textarea name="text" rows="4" maxlength="600" data-i18n-ph="revTextPh" placeholder="পণ্য বা কেনাকাটার অভিজ্ঞতা সম্পর্কে লিখুন…"></textarea>
        <label class="rev-photo">
          <input type="file" name="photo" accept="image/*" hidden>
          <span class="rev-photo-btn">📷 <span data-i18n="revPhoto">ছবি যোগ করুন (ঐচ্ছিক)</span></span>
          <img id="revPhotoPreview" alt="" hidden>
        </label>
        <p class="form-err" id="revErr" hidden></p>
        <button class="btn btn-primary full" data-i18n="revSubmit">রিভিউ জমা দিন</button>
      </form>
    </div>
  </div>

  <!-- Checkout modal -->
  <div class="modal" id="checkoutModal">
    <div class="modal-box">
      <button class="close" data-close aria-label="Close">✕</button>
      <h3 data-i18n="coTitle">অর্ডার কনফার্ম করুন</h3>
      <form id="checkoutForm" class="co-form">
        <input name="name" required data-i18n-ph="namePh" placeholder="আপনার নাম">
        <input name="phone" type="tel" required pattern="01[0-9]{9}" data-i18n-ph="phonePh" placeholder="01XXXXXXXXX">
        <textarea name="address" required rows="2" data-i18n-ph="addrPh" placeholder="পূর্ণ ঠিকানা"></textarea>
        <div class="co-row">
          <select name="state_id" id="zoneSel" required></select>
          <select name="city_id" id="areaSel" required></select>
        </div>
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
        <div class="pay-opts">
          <label data-pay="cod"><input type="radio" name="pay" value="cod" checked> <span data-i18n="payCod">ক্যাশ অন ডেলিভারি</span></label>
          <label data-pay="bkash"><input type="radio" name="pay" value="bkash"> bKash</label>
          <label data-pay="nagad"><input type="radio" name="pay" value="nagad"> Nagad</label>
          <label data-pay="rocket"><input type="radio" name="pay" value="rocket"> Rocket</label>
        </div>
        <p class="pay-number" id="payNumber" hidden></p>
        <div class="co-row" id="trxFields" hidden>
          <input name="sender_no" data-i18n-ph="senderPh" placeholder="যে নম্বর থেকে পাঠিয়েছেন">
          <input name="transaction_id" data-i18n-ph="trxPh" placeholder="Transaction ID">
        </div>
        <textarea name="customer_note" rows="2" data-i18n-ph="notePh" placeholder="অতিরিক্ত তথ্য (ঐচ্ছিক)"></textarea>
        <div class="coupon-box">
          <input id="couponInput" autocomplete="off" data-i18n-ph="couponPh" placeholder="কুপন কোড">
          <button type="button" class="btn btn-ghost" id="couponApply" data-i18n="apply">Apply</button>
        </div>
        <p class="coupon-msg" id="couponMsg"></p>
        <p class="co-err" id="coErr" hidden></p>
        <div class="co-sum">
          <div class="row"><span data-i18n="subtotal">সাবটোটাল</span><b id="coSub">৳0</b></div>
          <div class="row"><span data-i18n="shipping">ডেলিভারি</span><b id="coShip">৳0</b></div>
          <div class="row" id="coDiscRow" hidden><span data-i18n="discount">কুপন ছাড়</span><b id="coDisc">৳0</b></div>
          <div class="row total"><span data-i18n="total">মোট</span><b id="coTotal">৳0</b></div>
        </div>
        <button class="btn btn-primary full" id="coSubmit" data-i18n="placeOrder">অর্ডার কনফার্ম</button>
      </form>
      <div class="co-success" id="coSuccess" hidden>
        <div class="check">✓</div>
        <h3 data-i18n="thanks">ধন্যবাদ! অর্ডার হয়েছে</h3>
        <p><span data-i18n="orderNo">অর্ডার নম্বর:</span> <b id="orderId"></b></p>
        <p data-i18n="willCall">আমাদের টিম শীঘ্রই কল করে কনফার্ম করবে।</p>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>
  <div class="cursor-dot" id="cursorDot"></div>

  @unless (isset($product))
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  @endunless
  <script>
    // catalog, settings and delivery areas from the backend (cached), API base for orders, tracking and reviews
    window.STORE = @json($payload);
    window.PRODUCT_PAGE = @json($product['page'] ?? null);
    window.API = @json(url('/api/v1'));
  </script>
  @unless (isset($product))
  <script src="{{ asset('shop/hero3d.js') }}?v={{ filemtime(public_path('shop/hero3d.js')) }}"></script>
  @endunless
  <script src="{{ asset('shop/app.js') }}?v={{ filemtime(public_path('shop/app.js')) }}"></script>
{!! get_setting('footer_script') !!}
</body>
</html>

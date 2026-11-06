@php
    $logo = uploaded_asset($page->logo_upload_id) ?: ($settings['header']['logo'] ?? null) ?: asset('shop/assets/jihan-mart-logo.jpg');
    $img = fn ($rows) => array_values(array_filter(array_map(fn ($r) => ($u = uploaded_asset($r['image'] ?? null)) ? ['img' => $u, 'link' => $r['link'] ?? '', 'text' => $r['text'] ?? ''] : null, (array) $rows)));
    $sliders = $img($page->sliders);
    $features = $img($page->features);
    $banners = collect($page->banners ?? [])->map($img);
    $contents = collect($page->contents ?? [])->filter(fn ($c) => trim(strip_tags($c['body'] ?? '')) !== '' || ($c['title'] ?? '') !== '')->values();
    $items = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name, 'img' => $p->thumbnailUrl(), 'price' => $p->finalPrice(), 'old' => (float) $p->unit_price,
        'variants' => $p->variant_product ? $p->stocks->map(fn ($s) => ['v' => $s->variant, 'price' => $p->finalPrice($s->price)])->values() : null,
    ])->values();
    $phone = $page->phone ?: ($settings['phone'] ?? null);
    $banner = function ($key) use ($banners) {
        return $banners[$key] ?? [];
    };
@endphp
<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
  @include('partials.site-head', ['title' => $page->domain.' | '.$settings['site_name']])
  <style>
    :root { --navy: #1b2b57; --orange: #f47c20; --orange-d: #d9650c; --bg: #f5f7fb; --line: #e6e9f2; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Hind Siliguri", system-ui, sans-serif; background: var(--bg); color: #1f2937; line-height: 1.6; }
    img { max-width: 100%; display: block; }
    a { color: inherit; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 0 16px; }
    .top { background: #fff; border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 10; }
    .top .wrap { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 10px; padding-bottom: 10px; }
    .top img { height: 44px; width: auto; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 0; border-radius: 999px; padding: 12px 24px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; background: var(--orange); color: #fff; }
    .btn:hover { background: var(--orange-d); }
    .btn:disabled { opacity: .6; cursor: wait; }
    section { margin: 28px 0; }
    .slider { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; border-radius: 18px; scrollbar-width: none; }
    .slider > a { flex: 0 0 100%; scroll-snap-align: start; }
    .slider img { width: 100%; aspect-ratio: 1296 / 450; object-fit: cover; }
    .video { position: relative; aspect-ratio: 16 / 9; border-radius: 18px; overflow: hidden; background: #000; }
    .video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .grid { display: grid; gap: 14px; }
    .g2 { grid-template-columns: repeat(2, 1fr); } .g3 { grid-template-columns: repeat(3, 1fr); } .g4 { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
    .banners img { width: 100%; border-radius: 16px; }
    .feature { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 16px; display: flex; gap: 12px; align-items: center; }
    .feature img { width: 56px; height: 56px; object-fit: cover; border-radius: 12px; }
    .card { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: clamp(16px, 3vw, 28px); }
    h2 { color: var(--navy); margin: 0 0 12px; font-size: clamp(22px, 3vw, 30px); }
    .prod { background: #fff; border: 2px solid var(--line); border-radius: 16px; overflow: hidden; cursor: pointer; transition: border-color .2s; }
    .prod.on { border-color: var(--orange); }
    .prod img { width: 100%; aspect-ratio: 1; object-fit: cover; }
    .prod .b { padding: 12px; }
    .prod h3 { margin: 0 0 6px; font-size: 15px; }
    .price b { color: var(--orange); font-size: 18px; } .price s { opacity: .5; margin-left: 6px; }
    .qty { display: inline-flex; align-items: center; border: 1px solid var(--line); border-radius: 999px; margin-top: 8px; }
    .qty button { width: 32px; height: 32px; border: 0; background: none; font-size: 18px; cursor: pointer; }
    .qty span { min-width: 24px; text-align: center; font-weight: 700; }
    .prod select { width: 100%; margin-top: 8px; }
    form label { display: block; font-weight: 600; margin: 10px 0 4px; font-size: 14px; }
    input, select, textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--line); border-radius: 12px; font: inherit; background: #fff; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; } .row.total { border-top: 1px dashed var(--line); font-weight: 800; font-size: 18px; }
    .err { color: #dc2626; font-weight: 600; } .ok { text-align: center; padding: 30px; }
    .ok b { color: var(--orange); font-size: 26px; }
    .hp { position: absolute; left: -9999px; }
    footer { background: var(--navy); color: #fff; padding: 28px 0; margin-top: 40px; font-size: 14px; }
    @media (max-width: 720px) { .g2, .g3 { grid-template-columns: 1fr; } .order-grid { grid-template-columns: 1fr !important; } }
  </style>
</head>
<body>
  <header class="top"><div class="wrap">
    <a href="#"><img src="{{ $logo }}" alt="{{ $settings['site_name'] }}"></a>
    <a href="#order" class="btn">অর্ডার করুন</a>
  </div></header>

  <main class="wrap">
    @if ($sliders)
      <section class="slider" id="slider">@foreach ($sliders as $s)<a href="{{ $s['link'] ?: '#order' }}"><img src="{{ $s['img'] }}" alt=""></a>@endforeach</section>
    @endif

    @if ($embed = $page->videoEmbed())
      <section class="video"><iframe src="{{ $embed }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy" title="video"></iframe></section>
    @endif

    @if ($features)
      <section class="grid g4">@foreach ($features as $f)<div class="feature"><img src="{{ $f['img'] }}" alt=""><span>{{ $f['text'] }}</span></div>@endforeach</section>
    @endif

    @foreach ([['banner1', 'g2'], [0, null], ['two', 'g2'], [1, null], ['banner2', 'g2'], ['three', 'g3'], [2, null], ['banner3', 'g2']] as [$key, $cls])
      @if (is_int($key))
        @if ($c = $contents[$key] ?? null)
          <section class="card">@if ($c['title'])<h2>{{ $c['title'] }}</h2>@endif<div>{!! $c['body'] !!}</div></section>
        @endif
      @elseif ($rows = $banner($key))
        <section class="grid banners {{ count($rows) > 1 ? $cls : '' }}">@foreach ($rows as $b)<a href="{{ $b['link'] ?: '#order' }}"><img src="{{ $b['img'] }}" alt="" loading="lazy"></a>@endforeach</section>
      @endif
    @endforeach

    <section id="order" class="card">
      <h2>অর্ডার করতে নিচের ফর্মটি পূরণ করুন</h2>
      <form id="lpForm" class="grid order-grid" style="grid-template-columns: 1.2fr 1fr; gap: 24px" novalidate>
        <div>
          <div class="grid g4" id="lpProducts" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr))"></div>
        </div>
        <div>
          <input class="hp" name="website" tabindex="-1" autocomplete="off">
          <label for="lpName">আপনার নাম *</label><input id="lpName" name="name" required maxlength="120">
          <label for="lpPhone">মোবাইল নম্বর *</label><input id="lpPhone" name="phone" required inputmode="tel" placeholder="01XXXXXXXXX">
          <label for="lpAddress">সম্পূর্ণ ঠিকানা *</label><textarea id="lpAddress" name="address" rows="2" required></textarea>
          <label for="lpZone">এলাকা *</label>
          <select id="lpZone" name="state_id" required><option value="">বেছে নিন</option>@foreach ($locations as $z)<option value="{{ $z['id'] }}">{{ $z['name'] }}</option>@endforeach</select>
          <label for="lpArea">থানা / এরিয়া *</label><select id="lpArea" name="city_id" required><option value="">আগে এলাকা বেছে নিন</option></select>
          <div style="margin-top:14px">
            <div class="row"><span>সাবটোটাল</span><b id="lpSub">৳0</b></div>
            <div class="row"><span>ডেলিভারি চার্জ</span><b id="lpShip">—</b></div>
            <div class="row total"><span>মোট</span><b id="lpTotal">৳0</b></div>
          </div>
          <p class="err" id="lpErr" hidden></p>
          <button class="btn" id="lpSubmit" style="width:100%;margin-top:10px">অর্ডার কনফার্ম করুন (ক্যাশ অন ডেলিভারি)</button>
        </div>
      </form>
      <div class="ok" id="lpOk" hidden><p>ধন্যবাদ! আপনার অর্ডার নম্বর</p><b id="lpCode"></b><p>শীঘ্রই আমরা কল করে কনফার্ম করব।</p></div>
    </section>

    @if (trim(strip_tags((string) $page->about)) !== '')
      <section class="card"><h2>আমাদের সম্পর্কে</h2><div>{!! $page->about !!}</div></section>
    @endif
  </main>

  <footer><div class="wrap">
    @if ($page->address)<p>📍 {{ $page->address }}</p>@endif
    @if ($phone)<p>📞 <a href="tel:{{ $phone }}">{{ $phone }}</a></p>@endif
    @if ($page->email)<p>✉️ <a href="mailto:{{ $page->email }}">{{ $page->email }}</a></p>@endif
    <p style="opacity:.7">© {{ date('Y') }} {{ $settings['site_name'] }}</p>
  </div></footer>

  <script>
    const LP = { products: @json($items), locations: @json($locations), api: @json(url('/api/v1')),
      freeMin: {{ (float) ($settings['free_shipping_min'] ?? 0) }}, shipType: @json($settings['shipping_type'] ?? 'area_wise'), flat: {{ (float) ($settings['flat_rate'] ?? 0) }} };
    const $ = (s) => document.querySelector(s);
    const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    // one product is picked by default so a visitor can order in one step
    const cart = Object.fromEntries(LP.products.map((p, i) => [p.id, { qty: i === 0 ? 1 : 0, variant: p.variants?.[0]?.v || "" }]));
    const priceOf = (p) => (p.variants ? p.variants.find((v) => v.v === cart[p.id].variant)?.price ?? p.price : p.price);

    function renderProducts() {
      $("#lpProducts").innerHTML = LP.products.map((p) => `
        <div class="prod ${cart[p.id].qty ? "on" : ""}" data-id="${p.id}">
          ${p.img ? `<img src="${esc(p.img)}" alt="" loading="lazy">` : ""}
          <div class="b"><h3>${esc(p.name)}</h3>
            <div class="price"><b>৳${priceOf(p)}</b>${p.old > priceOf(p) ? `<s>৳${p.old}</s>` : ""}</div>
            ${p.variants ? `<select data-variant="${p.id}">${p.variants.map((v) => `<option ${v.v === cart[p.id].variant ? "selected" : ""}>${esc(v.v)}</option>`).join("")}</select>` : ""}
            <div class="qty"><button type="button" data-dec="${p.id}">−</button><span>${cart[p.id].qty}</span><button type="button" data-inc="${p.id}">+</button></div>
          </div></div>`).join("");
      totals();
    }
    function subtotal() { return LP.products.reduce((s, p) => s + cart[p.id].qty * priceOf(p), 0); }
    function totals() {
      const sub = subtotal();
      const area = $("#lpArea").selectedOptions[0];
      const cost = LP.shipType === "flat_rate" ? LP.flat : Number(area?.dataset.cost || 0);
      const ship = LP.freeMin > 0 && sub >= LP.freeMin ? 0 : cost;
      const known = LP.shipType === "flat_rate" || area?.value;
      $("#lpSub").textContent = "৳" + sub;
      $("#lpShip").textContent = known ? (ship ? "৳" + ship : "ফ্রি") : "—";
      $("#lpTotal").textContent = "৳" + (sub + (known ? ship : 0));
    }
    $("#lpProducts").addEventListener("click", (e) => {
      const inc = e.target.closest("[data-inc]"), dec = e.target.closest("[data-dec]");
      if (inc) cart[inc.dataset.inc].qty = Math.min(99, cart[inc.dataset.inc].qty + 1);
      else if (dec) cart[dec.dataset.dec].qty = Math.max(0, cart[dec.dataset.dec].qty - 1);
      else if (!e.target.closest("select")) { const card = e.target.closest("[data-id]"); if (card && !cart[card.dataset.id].qty) cart[card.dataset.id].qty = 1; else return; }
      else return;
      renderProducts();
    });
    $("#lpProducts").addEventListener("change", (e) => { const id = e.target.dataset.variant; if (id) { cart[id].variant = e.target.value; renderProducts(); } });
    $("#lpZone").addEventListener("change", () => {
      const zone = LP.locations.find((z) => String(z.id) === $("#lpZone").value);
      $("#lpArea").innerHTML = '<option value="">বেছে নিন</option>' + (zone?.cities || []).map((c) => `<option value="${c.id}" data-cost="${c.cost}">${esc(c.name)} (৳${c.cost})</option>`).join("");
      totals();
    });
    $("#lpArea").addEventListener("change", totals);

    async function post(path, body) {
      const res = await fetch(LP.api + path, { method: "POST", headers: { Accept: "application/json", "Content-Type": "application/json", "X-Locale": "bn" }, body: JSON.stringify(body) });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error((data.errors && Object.values(data.errors)[0]?.[0]) || data.message || "কিছু একটা সমস্যা হয়েছে, আবার চেষ্টা করুন");
      return data;
    }
    const items = () => LP.products.filter((p) => cart[p.id].qty).map((p) => ({ product_id: p.id, variant: cart[p.id].variant, quantity: cart[p.id].qty }));

    // capture the cart once a phone number is typed, like the main checkout
    let capT;
    $("#lpPhone").addEventListener("input", () => {
      clearTimeout(capT);
      capT = setTimeout(() => {
        const f = $("#lpForm");
        if (!/^(\+?88)?01\d{9}$/.test(f.phone.value.replace(/\s|-/g, "")) || !items().length) return;
        post("/abandoned-carts", { name: f.elements.name.value, phone: f.phone.value, address: f.address.value, state_id: f.state_id.value || null, city_id: f.city_id.value || null, items: items(), website: f.website.value }).catch(() => {});
      }, 900);
    });

    $("#lpForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const f = e.target, btn = $("#lpSubmit");
      $("#lpErr").hidden = true;
      if (!items().length) { $("#lpErr").textContent = "অন্তত একটি পণ্য বেছে নিন"; $("#lpErr").hidden = false; return; }
      btn.disabled = true;
      try {
        const res = await post("/orders", { name: f.elements.name.value, phone: f.phone.value, address: f.address.value, state_id: f.state_id.value, city_id: f.city_id.value,
          items: items(), payment_method: "cod", source: "landing", website: f.website.value, recaptcha_token: await window.captchaToken?.("order") });
        window.track?.("Purchase", { value: res.order.total, content_ids: items().map((i) => String(i.product_id)), content_type: "product" });
        $("#lpCode").textContent = "#" + res.order.code;
        f.hidden = true;
        $("#lpOk").hidden = false;
      } catch (err) {
        $("#lpErr").textContent = err.message;
        $("#lpErr").hidden = false;
      } finally {
        btn.disabled = false;
      }
    });

    // auto-advance the slider
    const slider = $("#slider");
    if (slider && slider.children.length > 1) setInterval(() => {
      const next = slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 4 ? 0 : slider.scrollLeft + slider.clientWidth;
      slider.scrollTo({ left: next, behavior: "smooth" });
    }, 4500);
    renderProducts();
  </script>
  {!! get_setting('footer_script') !!}
</body>
</html>

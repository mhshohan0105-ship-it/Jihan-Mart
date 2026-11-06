@php
    /** @var \App\Models\Product $p */
    $p = $product['model'];
    $off = $product['old'] ? round((1 - $product['price'] / $product['old']) * 100) : 0;
    $inStock = $p->current_stock > 0;
    $cat = $product['category'];
@endphp
{{-- Server rendered so search engines and link previews see the real product; cart and checkout are handled by shop/app.js --}}
<section class="pp" id="productPage" data-id="{{ $p->id }}">
  <nav class="pp-crumbs" aria-label="breadcrumb">
    <a href="{{ url('/') }}">হোম</a> <span>/</span>
    @if ($cat)<a href="{{ url('/#shop') }}" data-pp-cat="{{ $cat->id }}">{{ $cat->name_bn ?: $cat->name }}</a> <span>/</span>@endif
    <span>{{ \Illuminate\Support\Str::limit($p->name, 60) }}</span>
  </nav>

  <div class="pp-grid">
    <div class="pp-gallery">
      <div class="pp-main">
        @if ($off)<span class="p-tag sale">{{ $off }}% OFF</span>@endif
        @if ($product['images'])
          <img id="ppMain" src="{{ $product['images'][0] }}" alt="{{ $p->name }}" width="600" height="600" fetchpriority="high">
        @else
          <span class="pp-noimg">🛍️</span>
        @endif
      </div>
      @if (count($product['images']) > 1)
        <div class="pp-thumbs">
          @foreach ($product['images'] as $i => $img)
            <button type="button" class="pp-thumb {{ $i === 0 ? 'on' : '' }}" data-pp-img="{{ $img }}" aria-label="ছবি {{ $i + 1 }}"><img src="{{ $img }}" alt="" loading="lazy"></button>
          @endforeach
        </div>
      @endif
    </div>

    <div class="pp-info">
      @if ($cat)<span class="p-cat">{{ $cat->name_bn ?: $cat->name }}</span>@endif
      <h1 class="pp-title">{{ $p->name }}</h1>
      @if ($p->brand)<p class="pp-brand">ব্র্যান্ড: <b>{{ $p->brand->name }}</b></p>@endif

      <div class="pp-price">
        <b>৳{{ number_format($product['price'], 0) }}</b>
        @if ($product['old'])<s>৳{{ number_format($product['old'], 0) }}</s><span class="p-save">৳{{ number_format($product['old'] - $product['price'], 0) }} সাশ্রয়</span>@endif
      </div>
      <p class="pp-stock {{ $inStock ? '' : 'out' }}">{{ $inStock ? '✅ স্টকে আছে' : '❌ স্টক শেষ' }}</p>

      @if ($inStock)
        <div class="pp-buy">
          <div class="pp-qty" aria-label="পরিমাণ">
            <button type="button" data-pp-qty="-1" aria-label="কমান">−</button>
            <input id="ppQty" type="number" min="1" max="{{ min(99, $p->current_stock) }}" value="1" inputmode="numeric">
            <button type="button" data-pp-qty="1" aria-label="বাড়ান">+</button>
          </div>
          <button type="button" class="btn btn-ghost" id="ppAdd">কার্টে যোগ করুন</button>
          <button type="button" class="btn btn-primary" id="ppBuy">এখনই কিনুন</button>
        </div>
      @endif

      <ul class="pp-perks">
        <li>💵 ক্যাশ অন ডেলিভারি, পণ্য হাতে পেয়ে টাকা দিন</li>
        <li>🚚 ঢাকায় ১ থেকে ২ দিন, ঢাকার বাইরে ২ থেকে ৪ দিন</li>
        <li>🔍 ডেলিভারির সময় কুরিয়ারের সামনে পণ্য চেক করে নিন, সমস্যা থাকলে বদলে দেব (<a href="{{ url('/page/return-policy') }}">রিটার্ন পলিসি</a>)</li>
        @if ($p->warranty)<li>🛡️ ওয়ারেন্টি: <b>{{ $p->warranty }}</b></li>@endif
        @if ($phone = get_setting('contact_phone'))<li>📞 অর্ডারে সাহায্য: <a href="tel:{{ $phone }}">{{ $phone }}</a></li>@endif
      </ul>

      <div class="pp-share">
        <span>শেয়ার করুন:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($product['url']) }}" target="_blank" rel="noopener">Facebook</a>
        <a href="https://wa.me/?text={{ urlencode($p->name.' '.$product['url']) }}" target="_blank" rel="noopener">WhatsApp</a>
        <button type="button" data-pp-copy="{{ $product['url'] }}">লিংক কপি</button>
      </div>
    </div>
  </div>

  <div class="pp-desc">
    <h2>পণ্যের বিবরণ</h2>
    {{-- description HTML is written by store admins --}}
    @if (trim(strip_tags((string) $p->description)) !== '')
      <div class="pp-desc-body">{!! $p->description !!}</div>
    @else
      <p>{{ $p->name }}। মানসম্মত পণ্য, সাশ্রয়ী দাম। প্রশ্ন থাকলে WhatsApp বা ফোনে যোগাযোগ করুন।</p>
    @endif
  </div>
</section>

@php($rt = $product['rating'])
<section class="pp-reviews" id="ppReviews">
  <div class="pp-rev-head">
    <div>
      <h2>ক্রেতাদের রিভিউ ({{ $rt['count'] }})</h2>
      <p class="pp-rev-note">✔ শুধু যারা এই পণ্যটি কিনেছেন তারাই রিভিউ দিতে পারেন</p>
    </div>
    <button type="button" class="btn btn-primary" data-write-review="{{ $p->id }}">✍️ রিভিউ দিন</button>
  </div>

  @if ($rt['count'])
    <div class="pp-rev-summary">
      <div class="pp-rev-avg"><b>{{ number_format($rt['avg'], 1) }}</b><span class="pp-stars" style="--r: {{ $rt['avg'] }}" aria-label="{{ $rt['avg'] }} / 5"></span><small>{{ $rt['count'] }}টি রিভিউ</small></div>
      <div class="pp-rev-bars">
        @foreach ($rt['dist'] as $n => $c)
          <div class="pp-rev-bar"><span>{{ $n }}★</span><i><em style="width: {{ $rt['count'] ? round($c / $rt['count'] * 100) : 0 }}%"></em></i><small>{{ $c }}</small></div>
        @endforeach
      </div>
    </div>
    <div class="pp-rev-list">
      @foreach ($product['reviews'] as $r)
        <article class="pp-rev">
          <header>
            <b>{{ $r->name }}</b>
            @if ($r->verified)<span class="pp-verified">✔ যাচাইকৃত ক্রেতা</span>@endif
            <small>{{ $r->created_at->format('d M Y') }}</small>
          </header>
          <span class="pp-stars sm" style="--r: {{ $r->rating }}" aria-label="{{ $r->rating }} / 5"></span>
          <p>{{ $r->comment }}</p>
          @if ($r->photo)<img src="{{ $r->photo->url }}" alt="" loading="lazy">@endif
          @if ($r->admin_reply)<p class="pp-rev-reply"><b>Jihan Mart:</b> {{ $r->admin_reply }}</p>@endif
        </article>
      @endforeach
    </div>
  @else
    <p class="pp-rev-empty">এখনো কোনো রিভিউ নেই। পণ্যটি কিনে থাকলে প্রথম রিভিউটি আপনিই দিন।</p>
  @endif
</section>

<section class="section" id="related" hidden>
  <div class="section-head">
    <p class="eyebrow">আরও দেখুন</p>
    <h2>একই রকম আরও পণ্য</h2>
  </div>
  <div class="product-grid" id="relatedRow"></div>
</section>

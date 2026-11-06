@php($logo = $settings['header']['logo'] ?? null)
<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('shop/styles.css') }}?v={{ filemtime(public_path('shop/styles.css')) }}">
  @include('partials.site-head', [
      'title' => ($page->meta_title ?: $page->title).' | '.$settings['site_name'],
      'description' => $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160),
      'image' => uploaded_asset($page->meta_image_upload_id),
  ])
  <style>
    .page-wrap { max-width: 860px; margin: 0 auto; padding: 32px 16px 64px; }
    .page-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; max-width: 1200px; margin: 0 auto; }
    .page-top .logo-img { height: 46px; width: auto; }
    .page-card { background: var(--card); border: 1px solid var(--card-b); border-radius: 24px; padding: clamp(20px, 4vw, 44px); }
    .page-card h1 { margin: 0 0 18px; font-size: clamp(26px, 4vw, 38px); color: var(--navy); }
    .page-body { line-height: 1.8; font-size: 16px; overflow-wrap: anywhere; }
    .page-body img { max-width: 100%; height: auto; border-radius: 12px; }
    .page-foot { text-align: center; padding: 24px 16px; font-size: 14px; opacity: .7; }
  </style>
</head>
<body>
  <header class="page-top">
    <a href="{{ url('/') }}" class="logo">
      <img class="logo-img" src="{{ $logo ?: asset('shop/assets/jihan-mart-logo.jpg') }}" alt="{{ $settings['site_name'] }}">
      @unless ($logo)<span class="logo-mark">Jihan</span><span class="logo-text">Mart</span>@endunless
    </a>
    <a href="{{ url('/#shop') }}" class="btn btn-primary">শপ করুন</a>
  </header>
  <main class="page-wrap">
    <article class="page-card">
      <h1>{{ $page->title }}</h1>
      {{-- content is written by store admins in the rich text editor --}}
      <div class="page-body">{!! $page->content !!}</div>
      @if (setting_on('facebook_comment') && ($fbApp = get_setting('facebook_comment_app_id')))
        <div id="fb-root"></div>
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0&appId={{ urlencode($fbApp) }}"></script>
        <div class="fb-comments" data-href="{{ url()->current() }}" data-width="100%" data-numposts="10"></div>
      @endif
    </article>
  </main>
  <footer class="page-foot">{{ $settings['footer']['copyright'] ?: 'Copyright © '.date('Y').' '.$settings['site_name'] }}</footer>
  {!! get_setting('footer_script') !!}
</body>
</html>

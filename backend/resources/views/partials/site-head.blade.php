@php
    // shared <head> bits for the storefront, custom pages and landing pages (Admin → Appearance)
    $siteName = get_setting('site_name', 'Jihan Mart');
    $metaTitle = $title ?? (get_setting('meta_title') ?: $siteName.' | '.get_setting('site_motto', 'রান্নাঘরের সব প্রয়োজন এক জায়গায়'));
    $metaDesc = $description ?? get_setting('meta_description', 'Jihan Mart: কুকওয়্যার, ডিশ র‍্যাক, স্টোরেজ বক্স, কিচেন গ্যাজেট ও অর্গানাইজার। সাশ্রয়ী দাম, ক্যাশ অন ডেলিভারি, সারা বাংলাদেশে ডেলিভারি।');
    $metaImage = $image ?? uploaded_asset(get_setting('meta_image'));
    $icon = uploaded_asset(get_setting('site_icon')) ?: asset('shop/assets/jihan-mart-logo.jpg');
    $base = get_setting('base_color');
    $hover = get_setting('base_hover_color');
    $rgb = fn ($hex) => implode(', ', array_map('hexdec', str_split(ltrim($hex, '#'), 2)));
@endphp
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDesc }}">
@if ($kw = get_setting('meta_keywords'))<meta name="keywords" content="{{ $kw }}">@endif
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
@if ($metaImage)<meta property="og:image" content="{{ $metaImage }}">@endif
<link rel="icon" href="{{ $icon }}">
@if ($base && preg_match('/^#[0-9a-fA-F]{6}$/', $base))
<style>:root { --orange: {{ $base }}; --primary-rgb: {{ $rgb($base) }};@if ($hover && preg_match('/^#[0-9a-fA-F]{6}$/', $hover)) --orange-d: {{ $hover }};@endif }</style>
@endif
@php
    $pixel = setting_on('facebook_pixel') ? get_setting('facebook_pixel_id') : null;
    $ga = setting_on('google_analytics') ? get_setting('google_analytics_id') : null;
    $captchaKey = setting_on('google_recaptcha') ? \App\Support\EnvWriter::get('RECAPTCHA_SITE_KEY') : null;
@endphp
@if ($pixel && preg_match('/^\d{5,20}$/', $pixel))
<script>
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', @json($pixel)); fbq('track', 'PageView');
</script>
@endif
@if ($ga && preg_match('/^[A-Z0-9-]{4,30}$/', $ga))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', @json($ga));</script>
@endif
@if ($captchaKey)
<script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($captchaKey) }}" async defer></script>
@endif
<script>
  // shared by the storefront and landing pages
  window.track = (event, data = {}) => { try { window.fbq && fbq('track', event, { currency: 'BDT', ...data }); window.gtag && gtag('event', event, data); } catch (e) {} };
  window.captchaToken = (action) => new Promise((resolve) => {
    const key = @json($captchaKey);
    if (!key || !window.grecaptcha) return resolve('');
    grecaptcha.ready(() => grecaptcha.execute(key, { action }).then(resolve, () => resolve('')));
  });
</script>
{!! get_setting('header_script') !!}

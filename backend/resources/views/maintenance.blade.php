<!doctype html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>{{ get_setting('site_name', 'Jihan Mart') }} | শীঘ্রই ফিরছি</title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; font-family: "Hind Siliguri", system-ui, sans-serif;
      background: radial-gradient(circle at 30% 20%, #2c4a8f, #1b2b57 60%); color: #fff; text-align: center; }
    .box { max-width: 520px; }
    img { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; background: #fff; }
    h1 { font-size: clamp(26px, 5vw, 40px); margin: 18px 0 8px; color: #f47c20; }
    p { opacity: .85; line-height: 1.7; }
    a { color: #fff; }
  </style>
</head>
<body>
  <div class="box">
    <img src="{{ uploaded_asset(get_setting('header_logo')) ?: asset('shop/assets/jihan-mart-logo.jpg') }}" alt="">
    <h1>আমরা শীঘ্রই ফিরছি</h1>
    <p>সাইটে কিছু আপডেট চলছে। একটু পরে আবার চেষ্টা করুন।<br>We are updating the shop, please check back soon.</p>
    @if ($phone = get_setting('contact_phone'))<p>📞 <a href="tel:{{ $phone }}">{{ $phone }}</a></p>@endif
  </div>
</body>
</html>

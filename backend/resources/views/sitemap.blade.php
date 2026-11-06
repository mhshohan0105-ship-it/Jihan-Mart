{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ url('/') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
@foreach ($products as $p)
  <url><loc>{{ url('/product/'.$p->slug) }}</loc><lastmod>{{ $p->updated_at?->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>
@endforeach
@foreach ($pages as $page)
  <url><loc>{{ url('/page/'.$page->slug) }}</loc><lastmod>{{ $page->updated_at?->toAtomString() }}</lastmod><priority>0.4</priority></url>
@endforeach
</urlset>

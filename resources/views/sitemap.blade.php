{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $entry)
    <url>
        <loc>{{ $entry['url'] }}</loc>
@if ($entry['updated'])
        <lastmod>{{ $entry['updated'] }}</lastmod>
@endif
    </url>
@endforeach
</urlset>

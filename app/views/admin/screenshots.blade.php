<style>
body {
    width: 800px;
    max-width: 800px;
    margin: 0 auto;
    float: none;
    background: #fff url(none);
}
body {
    color: #000;
}
h1,h2,h3,h4,h5,h6 {
    font-family: 'Century Gothic', CenturyGothic, AppleGothic, sans-serif;
}
h1 { font-size: 250%; }
</style>

<body>
{{ ''; $index = 0; }}
@foreach ($links as $key => $url)
<h1>{{trans("screenshots.$key")}}</h1>
<img src="{{$url}}" width="800"><br>
{{ ''; $index++}}
@if ($index%2 === 0)
    <p style="page-break-after: always; break-after: always;"></p>
@endif
@endforeach
<script>
    window.print();
</script>
</body>

<div id="header-section">
    <div id="header-details">
        <div id="header-logo">
            <img src="{{$data['merchant']['image']}}" width=100% />
        </div>
        <div id="merchant-name">{{$data['merchant']['name']}}</div>
    </div>

    @include('hostedpage.partials.contact', ['view' => 'header'])
</div>

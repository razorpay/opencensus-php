@section('phone')
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
        <path d="M0 0h24v24H0z" fill="none"/>
        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
    </svg>
@endsection

@section('email')
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
        <path d="M0 0h24v24H0z" fill="none"/>
    </svg>
@endsection

<div id="contact-details">
    @if($view === 'form' and isset($contact) === true)
        <div class="mobile-el">
            <div class="heading" style="line-height: 32px;">Contact Us</div>
            <div>
                <a href="tel:{{$contact['phone']}}">{{$contact['phone']}}
                    @yield('phone')
                </a>
            </div>
            <div>
                <a href="mailto:{{$contact['email']}}?subject={{$email_subject}}" target="_blank">{{$contact['email']}}
                    @yield('email')
                </a>
            </div>
        </div>
    @endif


    @if($view === 'header' and isset($contact) === true)
        <div class="desktop-el">
            <div>
                <a href="tel:{{$contact['phone']}}">{{$contact['phone']}}
                    @yield('phone')
                </a>
            </div>
            <div>
                <a href="mailto:{{$contact['email']}}?subject={{$email_subject}}" target="_blank">{{$contact['email']}}
                    @yield('email')
                </a>
            </div>
        </div>
    @endif
</div>

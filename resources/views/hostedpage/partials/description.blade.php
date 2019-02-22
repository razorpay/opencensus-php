<div id="description-section">
    <div class="heading" style="font-size: 18px;">
        {{$payment_page_data['title']}}
    </div>
    <div class="text-underline"></div>

    <p>{{$intro_note}}</p>

    @if(empty($instructions) === false)
        <ol>
            @foreach ($instructions as $key => $ins)
                <li>{{$ins}}</li>
            @endforeach
        </ol>
    @endif

    <p style="opacity: 0.8">{{$end_note}} @if(isset($contact) === true)<a href="mailto:{{$contact['email']}}?subject={{$email_subject}}" target="_blank">{{$contact['email']}}</a> or <a href="tel:{{$contact['phone']}}">{{$contact['phone']}}</a>@endif</p>

    <div class="footer description-footer">
        <a href="https://razorpay.com/" target="_blank">
            Powered by
            <img src="https://cdn.razorpay.com/logo.svg" />
        </a>
    </div>
</div>

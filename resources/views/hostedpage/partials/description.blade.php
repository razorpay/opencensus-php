<?php
    $intro_note = 'Welcome to the Schindler Group. Now, pay your Schindler service bill in 4 simple steps :';
    $instructions = array('Enter the details for the service you availed.', 'Choose the method of payment. ', 'Pay the amount. ', 'Receive online confirmation and get a confirmation email.');
    $end_note =  'In case of any doubts, please reach out to Schindler on';
?>
<div id="description-section">
    <div class="heading" style="font-size: 18px;">
        {{$payment_page_data['title']}}
    </div>
    <div class="text-underline"></div>

    @if(isset($payment_page_data['description']) === true)
        <p id="payment-for"></p>
    @endif
    <p>{{$intro_note}}</p>

    <ol>
        @foreach ($instructions as $key => $ins)
            <li>{{$ins}}</li>
        @endforeach
    </ol>

    <p style="opacity: 0.8">{{$end_note}} <a href="mailto:{{$contact['email']}}?subject={{$email_subject}}" target="_blank">{{$contact['email']}}</a> or <a href="tel:{{$contact['phone']}}">{{$contact['phone']}}</a></p>

    <div class="footer description-footer">
        <a href="https://razorpay.com/" target="_blank">
            Powered by
            <img src="https://cdn.razorpay.com/logo.svg" />
        </a>
    </div>
</div>

@extends('emails.merchant.international_enablement_request.base')

@section('content')
    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px auto 5px;">
        @if (isset($approved_products) === true)
            We wish to inform you that your request for international payment acceptance on {{$approved_products}} has been evaluated and approved with the below changes basis inputs from our banking partner:
        @else
            We wish to inform you that your request has been evaluated and approved with the below changes basis inputs from our banking partner:
        @endif
    </p>

    <ol class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 5px auto 20px;">
        <li>The schedule for international settlements will be T + {{$international_settlement_cycle}}</li>
        <li>The per transaction limit will be INR {{$max_txn_amount_inr}}.</li>
    </ol>

    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px 0;">
        Please note that settlements are an automated process. Payments captured on a particular day are consolidated in a single settlement and are settled to your bank account as per the settlement cycle. Your settlement cycle is T + {{$domestic_settlement_cycle}} working days for domestic payments and T + {{$international_settlement_cycle}} working days for international payments, T being the transaction capture date. Please note: The cycle does not include Saturdays, Sundays, and bank holidays.
    </p>
    @if (isset($rejected_products) === true)
    <p class="para-normal font-size-medium" style="font-size: 14px; line-height: 1.5; color: #515978; margin: 20px auto 5px;">
        Unfortunately, We regret to inform you that the request for international payment acceptance on {{$rejected_products}} has not been approved by our banking partners and hence we would not be able to enable international payments through these products.
    </p>
    @endif
    @include('emails.merchant.international_enablement_request.typeform')
@endsection

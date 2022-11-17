@if ($reason === 'beneficiary_bank_confirmation_pending')

     Confirmation of credit to the beneficiary is pending from {{$beneficiary_bank}}. Please check the status after {{Carbon\Carbon::createFromTimestamp($processByTime, 'Asia/Kolkata')->format("dS F Y, h:i A") ?? null}}

@elseif ($reason === 'bank_window_closed')

    The {{$mode}} window for the day is closed. Please check the status after {{Carbon\Carbon::createFromTimestamp($processByTime, 'Asia/Kolkata')->format("dS F Y, h:i A") ?? null}}

@elseif ($reason === 'payout_bank_processing')

    Payout is being processed by our partner bank. Please check the final status after some time

@elseif ($reason === 'amount_limit_exhausted')

    The {{$mode}} 24*7 limits for your account has been exhausted. Please check the status after {{Carbon\Carbon::createFromTimestamp($processByTime, 'Asia/Kolkata')->format("dS F Y, h:i A") ?? null}}

@elseif ($reason === 'partner_bank_pending')
    Payout is being processed by our partner bank. Please check the final status after {{Carbon\Carbon::createFromTimestamp($processByTime, 'Asia/Kolkata')->format("dS F Y, h:i A") ?? null}}


@endif

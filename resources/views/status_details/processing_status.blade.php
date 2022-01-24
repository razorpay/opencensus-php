@if ($reason === 'beneficiary_bank_confirmation_pending')

    @if ($mode === 'NEFT' or $mode === 'RTGS')

        Confirmation of credit to the beneficiary is pending from {{$beneficiary_bank}}. Please check the status after some time

    @else

        Confirmation of credit to the beneficiary is pending from {{$beneficiary_bank}}. Please check the status after some time

    @endif

@elseif ($reason === 'bank_window_closed')

    The {{$mode}} window for the day is closed. Payout will be processed by our partner bank after some time

@elseif ($reason === 'payout_processing' or $reason === 'payout_bank_processing')

    Payout is being processed by our partner bank. Please check the final status after some time

@endif

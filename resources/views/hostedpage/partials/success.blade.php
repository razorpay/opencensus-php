<?php
    $has_error      = isset($error) === true and $error === true;
?>

<div id="success-section">
    <div class="{{{ $has_error ? 'animoo error' : 'animoo'}}}">
        <div class="circle circle-1 spring"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
        @if($has_error)
            <span class="mark cross">&times;</span>
        @else
            <span class="mark check"></span>
        @endif
    </div>

    <div class="heading" style="font-size: 18px">
        @if($has_error)
            Payment Failed
        @else
            Payment Completed
        @endif
    </div>
    <div id="success-msg"></div>
    <div id="payment-id"></div>
    @if(!$has_error)
        <div id="success-footer">A confirmation email has been mailed to you</div>
    @endif
</div>

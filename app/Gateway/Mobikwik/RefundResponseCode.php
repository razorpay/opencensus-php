<?php

namespace RZP\Gateway\Mobikwik;

class RefundResponseCode
{
    public static $codes = array(
        '0' => 'Success',
        '1' => 'Checksum mismatch',
        '6' => 'No refund on same transaction id within 5 minutes',
        '7' => 'Failed to debit from merchant',
        '10' => 'Refund can only be done to a user',
        '22' => 'Merchant insufficient balance',
        '45' => 'General error',
        '92' => 'Internal state error',
        '93' => 'Internal transaction void error',
        '94' => 'Email is not match',
        '99' => 'Merchant Blocked',
        '101' => 'No orderid found',
    );
}

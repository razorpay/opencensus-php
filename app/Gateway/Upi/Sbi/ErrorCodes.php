<?php

namespace RZP\Gateway\Upi\Sbi;

class ErrorCodes
{
    /**
     * This variable maps SBI's error codes to their error messages
     * @see https://drive.google.com/a/razorpay.com/file/d/1gd7ZioBlpsmZgNRbme3pvR2KmCFWQ6pt/view?usp=sharing
     * @var array
     */
    protected static $map = [
        'UP00' => 'SUCCESS',
        'UP96' => 'Unable to process your request',
        'UPNR' => 'NPCI communication error',
        'UPVD' => 'Validation error',
        'UPXF' => 'TRANSACTION DATA NOT FOUND',
        'UPDM' => 'TRNSACTION DEEEMED APPROVED',
        'UPTO' => 'TRANSACTION TIMEOUT',
        'UPTP' => 'TRANSACTION PENDING',
        'UPVT' => 'VPA is not available',
        'UPVA' => 'VPA is available',
    ];
}

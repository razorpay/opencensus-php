<?php

namespace RZP\Models\Payment\UpiMetadata;

class Mode
{
    const BHARAT_QR = 'bharat_qr';
    const UPI_QR    = 'upi_qr';
    const IN_APP    = 'in_app';

    // For auto recurring payments
    const AUTO      = 'auto';
    // For normal initial payments
    const INITIAL   = 'initial';
}

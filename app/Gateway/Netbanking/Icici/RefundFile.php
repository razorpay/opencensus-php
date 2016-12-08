<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Icici_Netbanking_Refund';

    // The columns of the file
    protected static $headers = [
        'Payee id',
        'SPID',
        'Bank Reference No.',
        'Transaction Date',
        'Transaction Amount',
        'Refund Amount',
        'Transaction Id',
        'Reversal/Cancellation',
        'Remarks'
    ];

    public function generate($input)
    {
        sd($input);
    }
}

<?php

namespace RZP\Gateway\Paytm;

use RZP\Models\Bank\IFSC;

class BankNames
{
    public $names = array(
        IFSC::UTIB => 'Axis Bank',
        IFSC::ICIC => 'ICICI Bank',
        IFSC::SBIN => 'State Bank of India',
    );
}

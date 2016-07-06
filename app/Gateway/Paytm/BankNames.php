<?php

namespace Gateway\Paytm;

use Models\Bank\IFSC;

class BankNames
{
    public $names = array(
        IFSC::AXIS => 'Axis Bank';
        IFSC::ICIC => 'ICICI Bank';
        IFSC::SBIN => 'State Bank of India';
    );
}

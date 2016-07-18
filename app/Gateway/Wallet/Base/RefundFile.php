<?php

namespace RZP\Gateway\Wallet\Base;

use Carbon\Carbon;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class RefundFile
{
    use FileHandlerTrait;

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate($input)
    {
        ;
    }

    protected function sendRefundEmail()
    {
        ;
    }
}
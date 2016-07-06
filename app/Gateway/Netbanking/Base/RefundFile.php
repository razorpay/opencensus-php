<?php

namespace RZP\Gateway\Netbanking\Base;

use Carbon\Carbon;
use Models\Settlement\Kotak\FileHandlerTrait;

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

    protected function sendHdfcNbRefundEmail()
    {
        ;
    }
}
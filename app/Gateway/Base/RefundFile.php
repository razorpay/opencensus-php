<?php

namespace RZP\Gateway\Base;

use Mail;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class RefundFile
{
    use FileHandlerTrait;

    public function __construct()
    {
        $this->mail = Mail::getFacadeRoot();
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

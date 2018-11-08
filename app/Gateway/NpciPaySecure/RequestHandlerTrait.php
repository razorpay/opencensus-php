<?php

namespace RZP\Gateway\NpciPaySecure;

trait RequestHandlerTrait
{
    protected function checkBin()
    {
        $requestArray = $this->getCheckBinRequestArray();
    }

    protected function getCheckBinRequestArray()
    {

        $cardNumber = $this->input['card']['number'];

        $cardBin = substr($cardNumber, 0, 9);

        sd($cardBin);
    }
}

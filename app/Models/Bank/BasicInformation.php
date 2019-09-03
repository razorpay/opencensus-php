<?php

namespace RZP\Models\Bank;

use Razorpay\IFSC\Client;
use Razorpay\IFSC\Entity;

class BasicInformation
{
    protected $ifscCode;
    public function __construct($ifscCode)
    {
        $this->ifscCode = $ifscCode;
    }

    public function getBankInformation(): Entity
    {

        $client = new Client();

        $bankInfo = $client->lookupIFSC($this->ifscCode);

        return $bankInfo;
    }
}

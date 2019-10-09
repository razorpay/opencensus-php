<?php

namespace RZP\Models\Bank;

use Razorpay\IFSC\Client;
use Razorpay\IFSC\Entity;

/**
 * Class BankInfo
 * @package RZP\Models\Bank
 * This class calls the IFSC Service to get basic bank information against the IFSC Code
 */
class BankInfo
{
    protected $ifscCode;

    public function __construct(string $ifscCode)
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

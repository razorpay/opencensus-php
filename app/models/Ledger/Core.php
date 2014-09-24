<?php

namespace Models\Ledger;

use Models\Base;
use Models\Card;
use Models\Ledger;
use Models\Merchant;
use Models\Pricing;
use Models\Payment;

class Core
{
    protected $entities = array();

    protected $record;

    public function __construct()
    {
        $this->merchant = \BasicAuth::getMerchant();
    }
}


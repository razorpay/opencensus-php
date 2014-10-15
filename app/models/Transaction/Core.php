<?php

namespace Models\Transaction;

use Models\Base;
use Models\Card;
use Models\Transaction;
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


<?php

namespace Models\Payment;

use Models\Base;
use Constants\Table;

class EsRepository extends Base\EsRepository
{
    public function __construct()
    {
        parent::__construct();
    }

    public function fetch($params, $merchantId)
    {
        $payments = [];

        if (isset($params['notes']))
        {
            $payments = $this->fetchNotes($this->getEsType(), $params, $merchantId);
        }

        return $payments;
    }

    public function getEsType()
    {
        return Table::PAYMENT;
    }
}
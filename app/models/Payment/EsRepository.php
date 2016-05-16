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
        $payments = new Base\PublicCollection;

        if (isset($params['notes']))
        {
            $payments = $this->fetchNotes(Table::PAYMENT, $params, $merchantId);
        }

        return $payments;
    }
}
<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Settlement;

class SettlementController extends BaseController
{
    public function postGatewayMpr()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->gatewayMpr($input);

        return $data;
    }

    public function getLedgerRecords()
    {
        $data = (new Settlement\Service)->getLedgerRecords();

        return $data;
    }
}
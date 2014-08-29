<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Settlement;

class SettlementController extends BaseController
{
    public function postHdfcMpr()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->hdfcMpr($input);

        return $data;
    }
}
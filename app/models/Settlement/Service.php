<?php

namespace Models\Settlement;

use Models\Base;
use Models\Merchant;
use Models\Pricing;
use Models\Terminal;
use EE\Exception;
use EE\Error\ErrorCode;

class Service extends Base\Service
{
    public function hdfcMpr($input)
    {
        $file = $input['hdfc_mpr'];

        $filePath = $file->getRealPath();

        $data = \Excel::load($filePath)
                      ->noHeading()
                      ->ignoreEmpty()
                      ->formatDates(false)
                      ->toArray();

        $data = $data[0];
        $headings = array_shift($data);

        foreach ($input as $row)
        {
            $this->reconcileHdfcMprRecord($input);
        }
    }

    protected function reconcileHdfcMprRecord($input)
    {
        $lgrCore = new Ledger\Core;

        $transactionId = Gateway::call('getTransactionId', $input);

        $lgrCore->loadEntities($transactionId);

        $lgrCore->newRecord();

        $entitiesArray = $core->entittiesToArray();

        $data = Gateway::call('reconcile', $input, $entitiesArray);

        $lgrCore->reconcileRecord($data);
    }
}

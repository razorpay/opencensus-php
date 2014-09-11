<?php

namespace Models\Settlement;

use Models\Base;
use Models\Gateway;
use Models\Ledger;
use EE\Exception;
use EE\Error\ErrorCode;

class Service extends Base\Service
{
    public function gatewayMpr($input)
    {
        $mprFile = $input['mpr'];
        $gateway = $input['gateway'];

        $filePath = $mprFile->getRealPath();

        $data = \Excel::load($filePath)
                      ->noHeading()
                      ->ignoreEmpty()
                      ->formatDates(false)
                      ->toArray();

        if ((count($data) === 3) and
            (count($data[1]) === 0))
        {
            //
            // For excel files, with 3 sheets, we get the
            // data for first sheet only, discarding other sheets.
            // The simple check to determine sheets is that they will 3
            // in number and data in second sheet should be empty.
            //
            $data = $data[0];
        }

        $headings = array_shift($data);

        foreach($headings as &$attr)
        {
            $attr = strtolower($attr);
            $attr = str_replace(' ', '_', $attr);
        }

        $headingCount = count($headings);

        $lgrs = array();

        $r = range(1, $headingCount);
        foreach ($data as $row)
        {
            foreach($r as $i)
            {
                if (isset($row[$i]) === false)
                {
                    $row = array_slice($row, 0, $i - 1, true) +
                           array($i => null) +
                           array_slice($row, $i - 1, null, true);
                }
            }

            $assocArray = array_combine($headings, $row);
            $lgr = $this->reconcileMprRecord($assocArray, $gateway);
            array_push($lgrs, $lgr);
        }

        return $lgrs;
    }

    protected function reconcileMprRecord($record, $gateway)
    {
        $lgrCore = new Ledger\Core;

        $transactionId = Gateway::call('getTransactionId', $record, 'test');

        $lgrCore->loadEntities($transactionId);

        $lgr = $lgrCore->newRecord();

        $entitiesArray = $lgrCore->entitiesToArray();

        $params = array(
            'input' => $record,
            'ledgerId' => $lgr->getKey(),
            'entities' => $entitiesArray);

        $data = Gateway::call('reconcile', $params, 'test');

        $lgr = $lgrCore->reconcileRecord($data);

        return $lgr;
    }

    public function getLedgerRecords($input)
    {
        $lgrs = (new Ledger\Repository)->fetch($input);

        return $lgrs->toPublicArray();
    }

    public function getLedgerRecordById($id)
    {
        $lgr = (new Ledger\Repository)->findByIdAndMerchantId($id, \BasicAuth::getMerchant()->getKey());

        return $lgr->toArrayPublic();
    }
}

<?php

namespace Models\Settlement;

use EE\Error\ErrorCode;
use EE\Exception;
use Illuminate\Database\Eloquent\Collection;
use Models\Base;
use Models\Gateway;
use Models\Ledger;

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

        $reconciledAt = time();

        foreach ($data as $row)
        {
            foreach($r as $i)
            {
                // Some keys may have corresponding blank columns
                // In such cases, excel does not provide a value for it.
                // So, we manually set those keys to 'null'
                if (isset($row[$i]) === false)
                {
                    $row = array_slice($row, 0, $i - 1, true) +
                           array($i => null) +
                           array_slice($row, $i - 1, null, true);
                }
            }

            $assocArray = array_combine($headings, $row);
            $lgr = $this->reconcileMprRecord($assocArray, $gateway, $reconciledAt);
            array_push($lgrs, $lgr);
        }

        return $lgrs;
    }

    protected function reconcileMprRecord($record, $gateway, $reconciledAt)
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

        $lgr = $lgrCore->reconcileRecord($data, $reconciledAt);

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

    public function generateTestMprForToday()
    {
        if ($mode !== 'test')
        {
            return;
        }

        // Get the timestamp on T-1 day 12 am for IST
        $t = Carbon::today('Asia/Kolkata');
        $t_1 = $t->copy()->addDays(-1);

        $t = $t->timestamp;
        $t_1 = $t_1->timestmap;

        $txnRepo = (new Transaction\Repository)->findByStatusBetweenTimestmaps(
                        Transaction\Status::CAPTURED,
                        $t_1,
                        $t);

        $txns = $txnRepo->fetch($params);

        $rfndRepo = new Refund\Repository;
        $refunds = $rfndRepo->findBetweenTimestamps($t_1, $t);

        $txns->load('merchant', 'merchant.terminal', 'card');
        $rows = array();

        foreach($txns->all() as $txn)
        {
            $cols = array(
                'transaction' => $txn->toArray(),
                'merchant'    => $txn->merchant->toArray(),
                'terminal'    => $txn->merchant->terminal->toArray(),
                'card'        => $txn->card->toArray()
            );

            array_push($rows, $cols);
        }

        $mprFile = (new Gateway)->call('generateMpr', $rows, 'test');

        \Mail::send('', array(), function($message)
        {
            $message->from('shashankkumar.me@gmail.com', 'shk');

            $message->to('mpr@mpr.razorpay.com')->cc('settlement@razorpay.com');

            $message->attach($filename);
        });

        return 'done!';
    }

    public function generateSettlements()
    {
        // Get the timestamp today at 12 am
        $t = Carbon::today('Asia/Kolkata');

        $lgrRepo = new Ledger\Repository;

        $lgrs = $lgrRepo->fetchTransactionsExpectedToSettle($t);

        $mercRepo = new Merchant\Repository;

        $merchantId = $lgrs->first()->getMerchantId();
        $merchant = $mercRepo->findOrFail($merchantId);

        $settlements = new Collection();
        $setlRepo = new Settlement\Repository;
        $amount = 0;

        foreach ($lgrs->all() as $lgr)
        {
            if ($lgr->getMerchantId() !== $merchantId)
            {
                $setlLedger = $this->settlementLedger($merchant, $amount);

                $input = array(
                    'amount' => $amount,
                    'merchant_id' => $merchantId,
                    'ledger_id' => $setlLedger->getKey());

                $setl = (new Settlement\Entity)->build($input);
                $setlLedger->setAttribute(Ledger\Entity::ENTITY_ID, $setl->getKey());
                $merchantBalance = $merchantRepo->getBalanceLockForUpdate($this->entities['merchant']->getKey());
                $merchantBalance->subAmount($ledger['debit']);
                $merchantRepo->save($merchantBalance);
                $setlLedger['balance'] = $merchantBalance->getBalance();

                $lgrRepo->save($setlLedger);
                $setlRepo->save($setl);

                $merchantId = $lgr->getMerchantId();
                $amount = 0;
            }

            $amount += $lgr->getCredit() - $lgr->getDebit();
        }

        $lgrRepo->settled($lgrs, $t);

        return $setlements->toArray();
    }

    protected function settlementLedger($merchant, $amount)
    {
        $lgr = new Ledger\Entity;

        $values = array(
            Ledger\Entity::MERCHANT_ID => $merchant->getKey(),
            Ledger\Entity::DEBIT => $amount,
            Ledger\Entity::FEE => 0,
            Ledger\Entity::AMOUNT => $amount,
            Ledger\Entity::ENTITY_TYPE => 'settlement',
        );

        $lgr->build($values);

        return $lgr;
    }
}

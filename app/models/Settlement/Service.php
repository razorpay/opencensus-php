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
    public function gatewayMprReconcile($input)
    {
        $data = MprParser::parseMprFile($input['mpr']);

        $reconciler = new Reconciler($data, $input['gateway']);

        return $reconciler->reconcile($data, $input['gateway']);
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

    public function gatewayMprGenerate()
    {
        $generator = new MprGenerator($this->mode);

        return $generator->generateTestMprForToday();
    }

}

<?php

namespace Models\Settlement;

use EE\Error\ErrorCode;
use EE\Exception;
use Illuminate\Database\Eloquent\Collection;
use Models\Base;
use Models\Card;
use Models\Gateway;
use Models\Ledger;
use Models\Merchant;
use Models\Pricing;
use Models\Transaction;

class Reconciler
{
    protected $reconciledAtTimestamp;

    protected $lgr;
    protected $merchant;
    protected $transaction;
    protected $card;

    public function __construct($mprData, $gateway)
    {
        $this->reconciledAtTimestamp = time();

        $this->feeCalculator = new Pricing\Fee;
    }

    public function reconcile($mprData, $gateway)
    {
        $lgrs = array();

        foreach ($mprData as $row)
        {
            $lgr = $this->reconcileMprRecord(
                $row,
                $gateway);

            array_push($lgrs, $lgr);
        }

        return $lgrs;
    }

    protected function reconcileMprRecord($mprRecord, $gateway)
    {
        $transactionId = Gateway::call('getTransactionId', $mprRecord, 'test');

        $lgr = $this->newLedgerRecord();

        $entitiesArray = $this->loadEntities($transactionId);

        $params = array(
            'input' => $mprRecord,
            'ledgerId' => $lgr->getKey(),
            'entities' => $entitiesArray);

        $data = Gateway::call('reconcile', $params, 'test');

        $lgr = $this->reconcileRecord($data);

        return $lgr;
    }

    protected function reconcileRecord($data)
    {
        $lgr = $this->lgr;

        $entities = $this->entities;

        list($fee, $pricingRuleId) = $this->calculateMerchantFees();

        $this->updateCardNetworkAndCountry(
            $this->card,
            $data['card']['network'],
            $data['card']['country']);

        $amount = $this->txn->getAmount();
        $credit = $amount - $fee;
        $gatewayFee = $data['ledger']['gateway_fee'];
        $apiFee = $fee - $gatewayFee;

        $lgrData = array(
            Ledger\Entity::AMOUNT => $amount,
            Ledger\Entity::GATEWAY_FEE => $data['ledger']['gateway_fee'],
            Ledger\Entity::MERCHANT_ID => $this->merchant->getKey(),
            Ledger\Entity::ENTITY_ID => $this->txn->getKey(),
            Ledger\Entity::ENTITY_TYPE => 'transaction',
            Ledger\Entity::FEE => $fee,
            Ledger\Entity::CREDIT => $credit,
            Ledger\Entity::DEBIT => 0,
            Ledger\Entity::PRICING_RULE_ID => $pricingRuleId,
            Ledger\Entity::API_FEE => $apiFee);

        $this->lgr->fill($lgrData);

        $this->updateBalances($lgr);

        (new Ledger\Repository)->save($lgr);

        return $lgr;
    }

    protected function updateCardNetworkAndCountry($card, $network, $country)
    {
        $card->setCountry($country);

        if (($network !== null) and
            ($network !== ''))
        {
            $card->setNetwork($network);
        }

        (new Card\Repository)->saveOrFail($card);
    }

    protected function calculateMerchantFees()
    {
        return $this->feeCalculator->calculateMerchantFees(
                    $this->merchant,
                    $this->card,
                    $this->txn->getAmount());
    }

    protected function updateBalances()
    {
        $merchantRepo = new Merchant\Repository();

        $nodalBalance = $merchantRepo->getEscrowBalanceLockForUpdate();
        $merchantBalance = $merchantRepo->getBalanceLockForUpdate($this->merchant->getKey());

        $merchantBalance->addAmount($this->lgr['credit']);
        $merchantBalance->subAmount($this->lgr['debit']);
        $merchantRepo->save($merchantBalance);

        $nodalBalance->addAmount($this->lgr['api_fee']);
        $merchantRepo->save($nodalBalance);

        $this->lgr['balance'] = $merchantBalance->getBalance();
        $this->lgr['escrow_balance'] = $nodalBalance->getBalance();
    }

    protected function newLedgerRecord()
    {
        $lgr = new Ledger\Entity;
        $lgr->generateId();
        $lgr->setReconciledAt($this->reconciledAtTimestamp);

        $this->lgr = $lgr;
        $this->entities['ledger'] = $lgr;

        return $lgr;
    }

    protected function loadEntities($transactionId)
    {
        $txn  = (new Transaction\Core)->retrieveById($transactionId);
        $this->merchant = $txn->merchant;
        $this->card = $txn->card;
        $this->terminal = $txn->merchant->terminal;
        $this->txn = $txn;

        return $entitiesArray = array(
            'transaction' => $this->txn->toArray(),
            'merchant' => $this->merchant->toArray(),
            'card' => $this->card->toArray(),
            'terminal' => $this->terminal->toArray(),
            'ledger' => $this->lgr->toArray());
    }
}
<?php

namespace Models\Ledger;

use Models\Base;
use Models\Card;
use Models\Ledger;
use Models\Merchant;
use Models\Pricing;
use Models\Transaction;

class Core
{
    protected $entities = array();

    protected $record;

    public function __construct()
    {
        $this->merchant = \BasicAuth::getMerchant();
    }

    public function newRecord()
    {
        $record = new Ledger\Entity;
        $record->generateId();

        $this->record = $record;
        $this->entities['ledger'] = $record;

        return $record;
    }

    public function loadEntities($transactionId)
    {
        $txn  = (new Transaction\Core)->retrieveById($transactionId);

        $this->entities['transaction'] = $txn;
        $this->entities['merchant'] = $txn->merchant;
        $this->entities['card'] = $txn->card;
        $this->entities['terminal'] = $txn->merchant->terminal;

        return $this->entities;
    }

    public function entitiesToArray()
    {
        $entities = $this->entities;

        $entitiesArray = array();

        foreach ($entities as $name => $entity)
        {
            $entitiesArray[$name] = $entity->toArray();
        }

        return $entitiesArray;
    }

    public function reconcileRecord($data)
    {
        $record = $this->record;

        $entities = $this->entities;

        $record['amount'] = $entities['transaction']->getAmount();
        $record['gateway_fee'] = $data['ledger']['gateway_fee'];
        $record['merchant_id'] = $entities['merchant']->getKey();
        $record['entity_id'] = $entities['transaction']->getKey();
        $record['entity_type'] = 'transaction';

        $this->updateCardNetworkAndCountry($entities['card'], $data['card']);

        list($fee, $pricingRuleId) = $this->calculateMerchantFees();

        $record['fee'] = $fee;
        $record['credit'] = $record['amount'] - $fee;
        $record['debit'] = 0;
        $record['pricing_rule_id'] = $pricingRuleId;
        $record['escrow_balance'] = 0;

        $apiFee = $fee - $record['gateway_fee'];

        $this->updateBalances($record);

        (new Ledger\Repository)->save($record);

        return $record;
    }

    protected function calculateMerchantFees()
    {
        $entities = $this->entities;

        $pricing = new Pricing\Fee;
        return $pricing->calculateMerchantFees(
                    $entities['merchant'],
                    $entities['card'],
                    $entities['transaction']['amount']);
    }

    protected function updateBalances($ledger)
    {
        $merchantRepo = new Merchant\Repository();

//        $nodalBalance = $merchantRepo->getEscrowBalanceLockForUpdate();
        $merchantBalance = $merchantRepo->getBalanceLockForUpdate($this->entities['merchant']->getKey());

        $merchantBalance->addAmount($ledger['credit']);
        $merchantRepo->save($merchantBalance);

        $apiFee = $ledger['fee'] - $ledger['gateway_fee'];
  //      $nodalBalance->addAmount($apiFee);
  //      $merchantRepo->save($nodalBalance);

        $ledger['api_fee'] = $apiFee;
        $ledger['balance'] = $merchantBalance->getBalance();
    //    $ledger['escrow_balance'] = $nodalBalance->getBalance();
    }

    protected function updateCardNetworkAndCountry($card, $data)
    {
        $card->setCountry($data['country']);

        if (($data['network'] !== null) and
            ($data['network'] !== ''))
        {
            $card->setNetwork($data['network']);
        }

        (new Card\Repository)->saveOrFail($card);
    }
}


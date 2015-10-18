<?php

namespace Models\Settlement;

use Constants\Mode;
use Models;
use Models\Base;
use Models\EE\Exception;
use Models\Adjustment;
use Models\Merchant\BankAccount;
use Models\Transaction;
use Models\Settlement;

class Merchant
{
    protected $merchant;

    protected $amount;

    protected $apiFee;

    protected $setl;

    protected $setlTransaction;

    protected $txns;

    public function __construct($merchant, $channel)
    {
        $this->merchant = $merchant;

        $this->channel = $channel;

        $this->merchantRepo = new Models\Merchant\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->setlRepo = new Settlement\Repository;

        // Get merchant bank account
        $this->attachMerchantBankAccount();
    }

    public function settle($txns, $amount, $apiFee, $gatewayFee)
    {
        $this->amount = $amount;
        $this->apiFee = $apiFee;
        $this->txns = $txns;

        $setl = $this->createSetlEntityAndTxn();

        // Updates merchant and api balance
        $this->updateBalances();

        $this->saveChangesToDb();

        return $setl;
    }

    public function collectApiFees($apiFee)
    {
        $this->amount = $apiFee;
        $this->apiFee = 0;
        $this->txns = new Base\PublicCollection;

        $adjInput = array(
            'description' => 'Settlement for ' . time(),
            'amount' => $apiFee,
            'currency' => 'INR',
        );

        $adj = (new Adjustment\Core)->createAdjustment($adjInput, $this->merchant);

        $this->txns->push($adj->transaction);

        $setl = $this->createSetlEntityAndTxn();

        (new Transaction\Core)->updateBalances($this->setlTransaction, false);

        $this->saveChangesToDb();

        return [$setl, $adj->transaction];
    }

    protected function createSetlEntityAndTxn()
    {
        // Create settlement transaction
        $setlTransaction = $this->newSettlementTransaction();

        // Create settlement entity
        $setl = $this->newSettlementEntity();

        $setlTransaction->entity()->associate($setl);

        return $setl;
    }

    protected function newSettlementTransaction()
    {
        $txn = new Transaction\Entity;

        $values = array(
            Transaction\Entity::DEBIT       => $this->amount,
            Transaction\Entity::CREDIT      => 0,
            Transaction\Entity::CURRENCY    => 'INR',
            Transaction\Entity::GATEWAY_FEE => 0,
            Transaction\Entity::API_FEE     => 0,
            Transaction\Entity::SETTLED     => 1,
            Transaction\Entity::SETTLED_AT  => time(),
            Transaction\Entity::FEE         => 0,
            Transaction\Entity::AMOUNT      => $this->amount,
            Transaction\Entity::TYPE        => Transaction\Type::SETTLEMENT,
            Transaction\Entity::CHANNEL     => $this->channel
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($this->merchant);

        $this->setlTransaction = $txn;

        return $txn;
    }

    protected function newSettlementEntity()
    {
        $setl = (new Settlement\Entity)->generateId();

        $fee =

        $setl->setAmount($this->amount);
        $setl->setStatus(Status::CREATED);
        $setl->setFees($this->apiFee);
        $setl->setChannel($this->channel);

        $setl->transaction()->associate($this->setlTransaction);
        $setl->merchant()->associate($this->merchant);

        $this->setl = $setl;

        return $setl;
    }

    protected function saveChangesToDb()
    {
        // Saves to db
        $this->txnRepo->saveOrFail($this->setlTransaction);
        $this->setlRepo->saveOrFail($this->setl);

        $this->txnRepo->updateSettlementId($this->txns, $this->setl->getId());
    }

    protected function updateBalances()
    {
        return (new Transaction\Core)->updateBalances($this->setlTransaction);
    }

    /**
     * Attaches bank account to merchant entity
     */
    protected function attachMerchantBankAccount()
    {
        $mode = \BasicAuth::getMode();

        if ($mode === Mode::TEST)
        {
            $ba = $this->getDefaultBank($this->merchant);
        }
        else
        {
            $ba = (new BankAccount\Repository)->getBankAccount($this->merchant);

            if ($ba === null)
            {
                throw new Exception\LogicException(
                    'Merchant bank account not found');
            }
        }

        return $ba;
    }

    protected function getDefaultBank($merchant)
    {
        $attributes = array(
            'merchant_id'       => $merchant->getId(),
            'ifsc_code'         => 'RZPB0000000',
            'beneficiary_name'  => $merchant['name'],
            'beneficiary_code'  => strtoupper(random_alpha_string(4)),
            'account_number'    => '10101030103');

        $ba = (new BankAccount\Entity)->newInstance($attributes, true);

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        return $ba;
    }
}

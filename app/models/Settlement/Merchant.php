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
use Models\Settlement\Details;

class Merchant
{
    protected $merchant;

    protected $amount;

    protected $apiFee;

    protected $setl;

    protected $setlTransaction;

    protected $txns;

    protected $setlDetails;

    public function __construct($merchant, $channel)
    {
        $this->merchant = $merchant;

        $this->channel = $channel;

        $this->merchantRepo = new Models\Merchant\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->setlRepo = new Settlement\Repository;
        $this->setlDetailsRepo = new Settlement\Details\Repository;

        // Get merchant bank account
        $this->attachMerchantBankAccount();
    }

    public function settle($txns, $amount, $fee, $apiFee, $gatewayFee, $serviceTax)
    {
        $this->amount = $amount;
        $this->apiFee = $apiFee;
        $this->fee = $fee;
        $this->txns = $txns;
        $this->serviceTax = $serviceTax;

        $setl = $this->createSetlEntityAndTxn();

        // Create Settlement Details entity
        $this->createSettlementDetailsEntities();

        // Updates merchant and api balance
        $this->updateBalances();

        $this->saveChangesToDb();

        return $setl;
    }

    public function collectApiFees($apiFee)
    {
        $this->amount = $apiFee;
        $this->fee = 0;
        $this->txns = new Base\PublicCollection;
        $this->setlDetails = new Base\PublicCollection;
        $this->serviceTax = 0;

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

    protected function createSettlementDetailsEntities()
    {
        $this->setlDetails = new Base\PublicCollection;

        $totalServiceTax = 0;
        $totalFee = 0;
        $totalAmount = 0;

        $entityTypes = array(
            Transaction\Type::PAYMENT, 
                Transaction\Type::REFUND, 
                Transaction\Type::ADJUSTMENT
            );

        foreach ($entityTypes as $entityType) 
        {
            $totalAmount = 0;
            $entityTxns = $this->txns->filter(function($txn) 
                use ($entityType, & $totalFee, & $totalServiceTax, & $totalAmount)
            {
                if ($txn->getType() === $entityType) {
                    $totalServiceTax    += $txn->getServiceTax();
                    $totalFee           += ($txn->getFee() - $txn->getServiceTax());
                    $totalAmount        += $txn->getAmount();
                    
                    return true;
                }
            });

            $setlDetailEntity = $this->getSettlementDetailsEntity($entityType, $entityTxns->count(), $totalAmount);

            $this->setlDetails->push($setlDetailEntity);
        }

        $this->setlDetails->push($this->getSettlementDetailsEntity('service_tax', 0, $totalServiceTax));
        $this->setlDetails->push($this->getSettlementDetailsEntity('fee', 0, $totalFee));
    }

    protected function getSettlementDetailsEntity($type, $count, $amount)
    {
        $input = array(
            Settlement\Details\Entity::MERCHANT_ID       => $this->merchant->getId(),
            Settlement\Details\Entity::SETTLEMENT_ID     => $this->setl->getId(),
            Settlement\Details\Entity::TYPE              => $type,
            Settlement\Details\Entity::AMOUNT            => $amount,
            Settlement\Details\Entity::COUNT             => $count
        );

        $setlDetailEntity = new Settlement\Details\Entity;
        $setlDetailEntity->fillAndGenerateId($input);

        $setlDetailEntity->merchant()->associate($this->merchant);
        $setlDetailEntity->settlement()->associate($this->setl);
        
        return $setlDetailEntity;

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
            Transaction\Entity::CHANNEL     => $this->channel,
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($this->merchant);

        $this->setlTransaction = $txn;

        return $txn;
    }

    protected function newSettlementEntity()
    {
        $setl = (new Settlement\Entity)->generateId();

        $setl->setAmount($this->amount);
        $setl->setStatus(Status::CREATED);
        $setl->setFees($this->fee);
        $setl->setServiceTax($this->serviceTax);
        $setl->setChannel($this->channel);

        $setl->transaction()->associate($this->setlTransaction);
        $setl->merchant()->associate($this->merchant);

        if ($this->bankAccount->getId() !== null)
        {
            $setl->bankAccount()->associate($this->bankAccount);
        }

        $this->setl = $setl;

        return $setl;
    }

    protected function saveChangesToDb()
    {
        // Saves to db
        $this->txnRepo->saveOrFail($this->setlTransaction);
        $this->setlRepo->saveOrFail($this->setl);

        foreach ($this->setlDetails->all() as $setlDetailsEntity) 
        {
            $this->setlDetailsRepo->saveOrFail($setlDetailsEntity);
        }

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

        if (($mode === Mode::TEST) and
            ($this->merchant->bankAccount === null))
        {
            $ba = $this->attachTestBank($this->merchant);
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

        $this->bankAccount = $ba;
        return $ba;
    }

    protected function attachTestBank($merchant)
    {
        $attributes = array(
            'ifsc_code'             => 'RZPB0000000',
            'beneficiary_name'      => random_alpha_string(5),
            'beneficiary_email'     => $merchant->getAttribute('email'),
            'account_number'        => random_integer(11),
            'beneficiary_address1'  => random_integer(14),
            'beneficiary_city'      => 'Mumbai',
            'beneficiary_state'     => 'MH',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '400069',
            'beneficiary_mobile'    => '9393993939',
        );

        $ba = (new BankAccount\Entity)->build($attributes, true);

        $ba->beneficiary_code = strtoupper(random_alpha_string(4));

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        $ba->save();

        return $ba;
    }
}

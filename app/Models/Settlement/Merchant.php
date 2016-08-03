<?php

namespace RZP\Models\Settlement;

use RZP\Constants\Mode;
use RZP\Models;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Adjustment;
use RZP\Models\Merchant\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Details as SettlementDetails;

class Merchant
{
    protected $merchant;

    protected $amount;

    protected $apiFee;

    protected $setl;

    protected $setlTransaction;

    protected $txns;

    protected $setlDetails;

    public function __construct($merchant, $channel, $repo)
    {
        $this->merchant = $merchant;

        $this->channel = $channel;

        $this->repo = $repo;

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
        $this->setlDetails = new Base\PublicCollection;

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

    public function createSettlementDetails($setl)
    {
        $this->setl = $setl;
        $this->txns = $setl->setlTransactions;

        $this->setlDetails = new Base\PublicCollection;

        $this->createSettlementDetailsEntities();

        $this->repo->saveOrFailCollection($this->setlDetails);
    }

    protected function createSettlementDetailsEntities()
    {
        $totalServiceTax = 0;
        $totalFee = 0;
        $totalAmount = 0;

        $entityTypes = array(
            Transaction\Type::PAYMENT,
            Transaction\Type::REFUND,
            Transaction\Type::ADJUSTMENT);

        foreach ($entityTypes as $entityType)
        {
            $totalAmount = 0;

            $entityTxns = $this->txns->filter(function($txn)
                use ($entityType, & $totalFee, & $totalServiceTax, & $totalAmount)
            {
                if ($txn->getType() === $entityType)
                {
                    $totalServiceTax    += $txn->getServiceTax();

                    $totalFee           += $txn->getFee();

                    if ($txn->getCredit() > 0)
                    {
                        $totalAmount    += $txn->getAmount();
                    }
                    else
                    {
                        $totalAmount    -= $txn->getAmount();
                    }

                    return true;
                }
            });

            $this->createSetlDetailsEntity($entityType, $entityTxns->count(), $totalAmount);
        }

        $this->createSetlDetailsEntity(SettlementDetails\Type::SERVICE_TAX, null, $totalServiceTax);

        $this->createSetlDetailsEntity(SettlementDetails\Type::FEE, 0, $totalFee);
    }

    protected function createSetlDetailsEntity($type, $count, $amount)
    {
        $input = array(
            SettlementDetails\Entity::TYPE          => $type,
            SettlementDetails\Entity::AMOUNT        => $amount,
            SettlementDetails\Entity::COUNT         => $count
        );

        $setlDetailEntity = new SettlementDetails\Entity;
        $setlDetailEntity->build($input);

        $setlDetailEntity->merchant()->associate($this->merchant);
        $setlDetailEntity->settlement()->associate($this->setl);

        $this->setlDetails->push($setlDetailEntity);

        return $setlDetailEntity;
    }

    protected function createSetlEntityAndTxn()
    {
        // Create settlement transaction
        $setlTransaction = $this->newSettlementTransaction();

        // Create settlement entity
        $setl = $this->newSettlementEntity();

        $setlTransaction->source()->associate($setl);

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
        $this->repo->saveOrFail($this->setlTransaction);
        $this->repo->saveOrFail($this->setl);

        $this->repo->saveOrFailCollection($this->setlDetails);

        $this->repo->transaction->updateSettlementId($this->txns, $this->setl->getId());
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
            $ba = $this->repo->bank_account->getBankAccount($this->merchant);

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
            'ifsc_code'             => BankAccount\Entity::SPECIAL_IFSC_CODE,
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

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        $this->repo->bank_account->save($ba);

        return $ba;
    }
}

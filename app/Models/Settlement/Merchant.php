<?php

namespace RZP\Models\Settlement;

use App;
use RZP\Constants\Mode;
use RZP\Models;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Adjustment;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\Schedule\Task\Type as ScheduleTaskType;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Details as SetlDetails;
use RZP\Models\Settlement\Details\Component as SetlComponent;
use RZP\Models\FundTransfer\Batch\BatchFundTransferTrait;

class Merchant
{
    use BatchFundTransferTrait;

    protected $merchant;
    protected $amount;
    protected $apiFee;
    protected $setl;
    protected $setlTransaction;
    protected $bankTransferAtpt;
    protected $txns;
    protected $setlDetails;
    protected $fee;
    protected $tax;
    protected $setlTime;
    protected $setlDetailAmounts;
    protected $scheduleTasks;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    public function __construct($merchant, $channel, $repo = null)
    {
        $app = App::getFacadeRoot();

        $this->merchant = $merchant;

        $this->channel = $channel;

        $this->repo = $repo;

        $this->ba = $app['basicauth'];

        // Get merchant bank account
        $this->attachMerchantBankAccount();
    }

    public function retryFailedSettlement(Settlement\Entity $setl)
    {
        $this->setl = $setl;

        $this->txns = $this->setl->setlTransactions;

        // Update Settlement Entity
        $this->updateSettlementEntity();

        // Increment attempts in settlements
        $this->setl->incrementAttempts();

        // Create Settlement attempt entity
        $this->createSettlementAttemptEntity();

        return [$this->setl, $this->bankTransferAtpt];
    }

    public function settle(
        $txns,
        $amount,
        $fee,
        $apiFee,
        $tax,
        $setlTime,
        array $setlDetailAmounts): array
    {
        $this->amount = $amount;
        $this->apiFee = $apiFee;
        $this->fee = $fee;
        $this->txns = $txns;

        $this->tax = $tax;
        $this->setlTime = $setlTime;
        $this->setlDetailAmounts = $setlDetailAmounts;

        $this->createSetlEntityAndTxn();

        // Create Settlement attempt entity
        $this->createSettlementAttemptEntity();

        // Create Settlement Details entity
        $this->setlDetails = new Base\PublicCollection;
        $this->createSettlementDetailsEntities();

        // Updates merchant and api balance
        $this->updateBalances();

        $this->updateMerchantScheduleTask();

        $this->saveChangesToDb();

        // Update transactions
        $this->updateTransactions();

        return [$this->setl, $this->bankTransferAtpt];
    }

    protected function updateTransactions()
    {
        // Update transactions
        $values = [
            Transaction\Entity::SETTLED_AT      => $this->setlTime,
            Transaction\Entity::SETTLED         => true,
            Transaction\Entity::SETTLEMENT_ID   => $this->setl->getId(),
        ];

        $this->repo->transaction->settled($this->txns, $values);
    }

    public function createSettlementDetails($setl)
    {
        $this->setl = $setl;
        $this->txns = $setl->setlTransactions;

        $this->setlDetails = new Base\PublicCollection;

        $this->setlDetailAmounts = $this->calculateSettlementDetailAmounts($this->txns);

        $this->createSettlementDetailsEntities();

        $this->repo->saveOrFailCollection($this->setlDetails);
    }

    public function calculateSettlementDetailAmounts($txns): array
    {
        $entityTypes = SetlComponent::getAllComponents();

        $details = [];

        foreach ($entityTypes as $componentType)
        {
            $details[$componentType]['amount'] = 0;

            $details[$componentType]['count'] = 0;
        }

        foreach ($txns as $txn)
        {
            $componentType = $txn->getType();

            $details[$componentType]['count'] += 1;

            switch ($componentType)
            {
                case Transaction\Type::PAYMENT:
                case Transaction\Type::REVERSAL:
                    $details[$componentType]['amount'] += $txn->getAmount();
                    break;

                case Transaction\Type::REFUND:
                case Transaction\Type::PAYOUT:
                case Transaction\Type::TRANSFER:
                case Transaction\Type::DISPUTE:
                    $details[$componentType]['amount'] -= $txn->getAmount();
                    break;

                case Transaction\Type::ADJUSTMENT:
                    $details[$componentType]['amount'] += $txn->getCredit();
                    $details[$componentType]['amount'] -= $txn->getDebit();
                    break;

                default:
                    throw new Exception\LogicException('Invalid Settlement-component-type:' . $componentType);
            }

            $details[SetlComponent::TAX]['amount'] += $txn->getTax();

            $details[SetlComponent::FEE]['amount'] += ($txn->getFee() - $txn->getTax());

            // FeeCredits is either zero or equal to fees.
            $details[SetlComponent::FEE_CREDITS]['amount'] += $txn->getFeeCredits();
        }

        return $details;
    }

    protected function createSettlementDetailsEntities()
    {
        foreach ($this->setlDetailAmounts as $componentType => $detail)
        {
            switch ($componentType)
            {
                case SetlDetails\Component::FEE:
                case SetlDetails\Component::TAX:

                    $this->createSetlDetailsEntity(
                        $componentType,
                        'debit',
                        null,
                        $detail['amount']);

                    break;

                case SetlDetails\Component::FEE_CREDITS:
                    if ($detail['amount'] > 0)
                    {
                        $this->createSetlDetailsEntity(
                            $componentType,
                            'credit',
                            null,
                            $detail['amount']);
                    }

                    break;

                default:
                    $txnType = $detail['amount'] < 0 ? 'debit' : 'credit';

                    if ($detail['count'] !== 0)
                    {
                        $this->createSetlDetailsEntity(
                            $componentType,
                            $txnType,
                            $detail['count'],
                            abs($detail['amount']));
                    }

                    break;
            }
        }
    }

    protected function createSetlDetailsEntity($component, $type, $count, $amount): SetlDetails\Entity
    {
        $input = array(
            SetlDetails\Entity::COMPONENT => $component,
            SetlDetails\Entity::TYPE      => $type,
            SetlDetails\Entity::AMOUNT    => $amount,
            SetlDetails\Entity::COUNT     => $count
        );

        $setlDetailEntity = new SetlDetails\Entity;
        $setlDetailEntity->build($input);

        $setlDetailEntity->merchant()->associate($this->merchant);
        $setlDetailEntity->settlement()->associate($this->setl);

        $this->setlDetails->push($setlDetailEntity);

        return $setlDetailEntity;
    }

    protected function createSetlEntityAndTxn()
    {
        // Create settlement transaction
        $this->newSettlementTransaction();

        // Create settlement entity
        $this->newSettlementEntity();

        $this->setlTransaction->source()->associate($this->setl);
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
    }

    protected function newSettlementEntity()
    {
        $setl = (new Settlement\Entity)->generateId();

        $input = [
            Settlement\Entity::AMOUNT       => $this->amount,
            Settlement\Entity::STATUS       => Status::CREATED,
            Settlement\Entity::FEES         => $this->fee,
            Settlement\Entity::TAX          => $this->tax,
            Settlement\Entity::CHANNEL      => $this->channel,
        ];

        $setl = $setl->build($input);

        $setl->transaction()->associate($this->setlTransaction);
        $setl->merchant()->associate($this->merchant);

        $setl->bankAccount()->associate($this->bankAccount);

        $this->setl = $setl;
    }

    protected function updateSettlementEntity()
    {
        $setl = $this->setl;

        // try the settlment with current merchant bank account as that might
        // have been the reason for settlement failure
        $setl->bankAccount()->associate($this->bankAccount);

        // set the settlement status back to created, and other fields to null
        $setl->setStatus(Status::CREATED);
        $setl->setFailureReason(null);
        $setl->setUtr(null);
        $setl->setRemarks(null);

        $this->setl = $setl;
    }

    protected function createSettlementAttemptEntity()
    {
        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $values = [
            FundTransferAttempt\Entity::CHANNEL         => $this->channel,
            FundTransferAttempt\Entity::VERSION         => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS          => FundTransferAttempt\Status::INITIATED,
        ];

        $fundTransferAttempt->fillAndGenerateId($values);

        $fundTransferAttempt->source()->associate($this->setl);

        $fundTransferAttempt->merchant()->associate($this->merchant);

        $fundTransferAttempt->bankAccount()->associate($this->bankAccount);

        $this->bankTransferAtpt = $fundTransferAttempt;
    }

    protected function saveChangesToDb()
    {
        $this->repo->saveOrFail($this->setlTransaction);

        $this->repo->saveOrFail($this->setl);

        $this->repo->saveOrFail($this->bankTransferAtpt);

        $this->repo->saveOrFailCollection($this->setlDetails);

        $this->repo->saveOrFailCollection($this->scheduleTasks);
    }

    protected function updateBalances(): Transaction\Entity
    {
        return (new Transaction\Core)->updateBalances($this->setlTransaction);
    }

    protected function updateMerchantScheduleTask()
    {
        $scheduleTasks = $this->repo
                              ->schedule_task
                              ->fetchByMerchant($this->merchant, ScheduleTaskType::SETTLEMENT);

        $this->scheduleTasks = new Base\PublicCollection;

        foreach ($scheduleTasks as $scheduleTask)
        {
            $scheduleTask->updateNextRunAndLastRun();

            $this->scheduleTasks->push($scheduleTask);
        }
    }

    /**
     * Attaches bank account to merchant entity
     */
    protected function attachMerchantBankAccount(): BankAccount\Entity
    {
        $mode = $this->ba->getMode();

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

    protected function attachTestBank($merchant): BankAccount\Entity
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

        $ba = (new BankAccount\Entity)->build($attributes);

        $ba->merchant()->associate($merchant);

        $ba->associateMerchant($merchant);

        $ba->generateBeneficiaryCode();

        $merchant->setRelation('bankAccount', $ba);

        $this->repo->saveOrFail($ba);

        return $ba;
    }
}

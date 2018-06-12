<?php

namespace RZP\Models\Settlement;

use App;
use Carbon\Carbon;

use RZP\Models;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Settlement\Details as SetlDetails;
use RZP\Models\Schedule\Task\Type as ScheduleTaskType;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Settlement\Details\Component as SetlComponent;

class Merchant
{
    protected $merchant;
    protected $amount;
    protected $apiFee;
    protected $setl;
    protected $payout;
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

        $this->trace = $app['trace'];

        // Get merchant bank account
        $this->attachMerchantBankAccount();
    }

    public function retryFailedSettlement(Settlement\Entity $setl)
    {
        $this->setl = $setl;

        $this->updateSettlementEntity();

        $this->setl->incrementAttempts();

        $this->repo->saveOrFail($this->setl);

        // Create Settlement attempt entity
        $this->createSettlementAttemptEntity();

        return [$this->setl, $this->bankTransferAtpt];
    }

    public function retryFailedPayout(Payout\Entity $payout): Payout\Entity
    {
        $this->payout = $payout;

        $this->payout->reload();

        if ($this->payout->getStatus() !== Payout\Status::FAILED)
        {
            throw new Exception\RuntimeException(
                'Invalid Payout.',
                [
                    'id'     => $this->payout->getId(),
                    'status' => $this->payout->getStatus()
                ]);
        }

        $this->resetPayoutEntity();

        $this->payout->incrementAttempts();

        $this->repo->saveOrFail($this->payout);

        $this->createPayoutAttemptEntity();

        return $this->payout;
    }

    public function settle(
        $txns,
        $amount,
        $fee,
        $apiFee,
        $tax,
        $setlTime,
        array $setlDetailAmounts): Entity
    {
        $this->amount = $amount;
        $this->apiFee = $apiFee;
        $this->fee = $fee;
        $this->txns = $txns;

        $this->tax = $tax;
        $this->setlTime = $setlTime;
        $this->setlDetailAmounts = $setlDetailAmounts;

        $this->setlDetails = new Base\PublicCollection;

        $startTime = microtime(true);

        $this->repo->transaction(function()
        {
            //create new settlement entity
            $this->newSettlementEntity();

            // Create Settlement Details entity
            $this->createSettlementDetailsEntities();

            // update schedule tasks
            $this->updateMerchantScheduleTask();

            // save settlement and details
            $this->saveSettlementEntitiesToDb();

            // Update transactions for settlement
            $this->updateTransactions();
        });

        $timeTaken = microtime(true) - $startTime;

        $this->trace->info(TraceCode::SETTLEMENT_MERCHANT_SETTLE_TIME_TAKEN, ['time_taken' => $timeTaken]);

        return $this->setl;
    }

    public function createTransaction($settlement)
    {
        $this->setl = $settlement;

        $this->repo->transaction(function()
        {
            $this->setlTransaction = (new Transaction\Core)->createFromSettlement($this->setl);

            $this->repo->saveOrFail($this->setlTransaction);

            $this->repo->saveOrFail($this->setl);
        });
    }

    public function createSettlementAttempt() : FundTransferAttempt\Entity
    {
        assert($this->setl->hasTransaction(), true);

        $initiateAt = $this->txns->max(Transaction\Entity::SETTLED_AT);

        $this->createSettlementAttemptEntity($initiateAt);

        return $this->bankTransferAtpt;
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

            // Add credits if txn is of type fee credits.
            $details[SetlComponent::FEE_CREDITS]['amount'] += ($txn->isFeeCredits() ? $txn->getCredits() : 0);

            // Add credits if txn is of type refund credits.
            $details[SetlComponent::REFUND_CREDITS]['amount'] += ($txn->isRefundCredits() ? $txn->getCredits() : 0);
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
                case SetlDetails\Component::REFUND_CREDITS:
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
        $setl->batchFundTransfer()->dissociate();

        $this->setl = $setl;
    }

    protected function resetPayoutEntity()
    {
        // set the settlement status back to created, and other fields to null
        $this->payout->setStatus(Status::CREATED);
        $this->payout->setFailureReason(null);
        $this->payout->setUtr(null);
        $this->payout->setRemarks(null);
        $this->payout->batchFundTransfer()->dissociate();
    }

    protected function createSettlementAttemptEntity(int $initiateAt = null)
    {
        $this->createFundTransferAttempt($this->setl, $this->bankAccount, $initiateAt);
    }

    protected function createPayoutAttemptEntity(int $initiateAt = null)
    {
        $bankAccount = $this->payout->destination;

        $this->createFundTransferAttempt($this->payout, $bankAccount, $initiateAt);
    }

    protected function createFundTransferAttempt(
        Base\Entity $source,
        BankAccount\Entity $bankAccount,
        int $initiateAt = null)
    {
        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $fundTransferAttempt->merchant()->associate($this->merchant);

        $fundTransferAttempt->source()->associate($source);

        $fundTransferAttempt->bankAccount()->associate($bankAccount);

        $initiateAt = ($initiateAt ?: Carbon::now(Timezone::IST)->getTimestamp());

        $values = [
            FundTransferAttempt\Entity::INITIATE_AT     => $initiateAt,
            FundTransferAttempt\Entity::CHANNEL         => $this->channel,
            FundTransferAttempt\Entity::VERSION         => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS          => FundTransferAttempt\Status::CREATED,
            FundTransferAttempt\Entity::PURPOSE         => FundTransferAttempt\Purpose::SETTLEMENT,
        ];

        $fundTransferAttempt->fillAndGenerateId($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->bankTransferAtpt = $fundTransferAttempt;
    }

    protected function saveSettlementEntitiesToDb()
    {
        $this->repo->saveOrFail($this->setl);

        $this->repo->saveOrFailCollection($this->setlDetails);

        $this->repo->saveOrFailCollection($this->scheduleTasks);
    }

    protected function updateMerchantScheduleTask()
    {
        $scheduleTasks = $this->repo
                              ->schedule_task
                              ->fetchByMerchant($this->merchant, ScheduleTaskType::SETTLEMENT);

        $this->scheduleTasks = new Base\PublicCollection;

        foreach ($scheduleTasks as $scheduleTask)
        {
            $scheduleTask->updateNextRunAndLastRun(true);

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

<?php

namespace RZP\Models\Settlement;

use App;
use Carbon\Carbon;

use RZP\Models;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Diag\EventCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant as MerchantModel;
use RZP\Models\Settlement\Details as SetlDetails;
use RZP\Models\Schedule\Task\Type as ScheduleTaskType;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Settlement\Details\Component as SetlComponent;

class Merchant
{
    use SettlementTrait;

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
    protected $logging;
    protected $mode;
    protected $env;
    protected $merchantSettleToPartner;
    protected $isAggregateSettlement;
    protected $isNewService;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    protected $app;

    public function __construct($merchant,
                                $channel,
                                $repo = null,
                                $logging = false,
                                array $merchantSettleToPartner = [],
                                bool $isAggregateSettlement = false,
                                bool $isNewService = false)
    {
        $this->app = App::getFacadeRoot();

        $this->merchant = $merchant;

        $this->channel = $channel;

        $this->repo = $repo;

        $this->ba = $this->app['basicauth'];

        $this->trace = $this->app['trace'];

        $this->merchantSettleToPartner = $merchantSettleToPartner;

        $this->isAggregateSettlement = $isAggregateSettlement;

        $this->isNewService = $isNewService;

        // Get settlement bank account
        $this->attachSettlementBankAccount();

        $this->logging = $logging;

        $this->mode = $this->app['rzp.mode'];

        $this->env = $this->app['env'];
    }

    public function retryFailedSettlement(Settlement\Entity $setl, array $merchantSettleToPartner)
    {
        $this->setl = $setl;

        $this->updateSettlementEntity($merchantSettleToPartner);

        $this->setl->incrementAttempts();

        $this->repo->saveOrFail($this->setl);

        // Create Settlement attempt entity
        $this->createSettlementAttemptEntity(null, $merchantSettleToPartner);

        return [$this->setl, $this->bankTransferAtpt];
    }

    public function settle(
        $txns,
        $amount,
        $fee,
        $apiFee,
        $tax,
        $setlTime,
        array $setlDetailAmounts,
        array $merchantSettleToPartner,
        Balance\Entity $balance): Entity
    {
        $this->amount = $amount;
        $this->apiFee = $apiFee;
        $this->fee    = $fee;
        $this->txns   = $txns;

        $this->tax               = $tax;
        $this->setlTime          = $setlTime;
        $this->setlDetailAmounts = $setlDetailAmounts;

        $this->setlDetails = new Base\PublicCollection;

        if ($this->logging === true)
        {
            $startTime = microtime(true);
        }

        $this->repo->transaction(function() use ($merchantSettleToPartner, $balance)
        {
            //create new settlement entity
            $this->newSettlementEntity($merchantSettleToPartner, $balance);

            // Create Settlement Details entity
            $this->createSettlementDetailsEntities();

            // update schedule tasks
            $this->updateMerchantScheduleTask();

            // save settlement and details
            $this->saveSettlementEntitiesToDb();

            // Update transactions for settlement
            $this->updateTransactions();
        });

        if ($this->logging === true)
        {
            $timeTaken = microtime(true) - $startTime;

            $this->trace->info(TraceCode::SETTLEMENT_MERCHANT_SETTLE_TIME_TAKEN, ['time_taken' => $timeTaken]);
        }

        return $this->setl;
    }

    public function createTransaction($settlement, $journalID=null)
    {
        try
        {
            $this->setl = $settlement;

            $this->repo->transaction(function() use ($journalID)
            {
                $this->setlTransaction = (new Transaction\Core)->createFromSettlement($this->setl, $journalID);

                $this->repo->saveOrFail($this->setlTransaction);

                $this->repo->saveOrFail($this->setl);
            });
        }
        catch(\Throwable $e)
        {
            //
            // this is required as any failure in transaction wont revert the changes in the model.
            // so forcefully mark the settlement transaction as null
            //
            $settlement->transaction()->dissociate();

            throw $e;
        }
    }

    public function createSettlementAttempt($merchantSettleToPartner, $params = []) : FundTransferAttempt\Entity
    {
        assert($this->setl->hasTransaction(), true); // nosemgrep : assert-fix-false-positives

        $initiateAt = null;

        if(isset($params['initiate_at']) === true)
        {
            $initiateAt = $params['initiate_at'];
        }
        else
        {
            $initiateAt = $this->txns->max(Transaction\Entity::SETTLED_AT);
        }

        $this->createSettlementAttemptEntity($initiateAt, $merchantSettleToPartner);

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

        $this->repo->transaction->settled($this->txns, $values, $this->logging);
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

    protected function createSettlementDetailsEntities()
    {
        foreach ($this->setlDetailAmounts as $componentType => $detail)
        {
            switch ($componentType)
            {
                case SetlDetails\Component::FEE:
                case SetlDetails\Component::TAX:
                    // this is added to support the negative fees in case of reversal
                    // if the reversal is only consider then amount comes negative in settlement
                    // details entity thus added credit and debit to figure out it is reversal or proper fee
                    $txnType = $detail['amount'] < 0 ? 'credit' : 'debit';

                    $this->createSetlDetailsEntity(
                        $componentType,
                        $txnType,
                        null,
                        abs($detail['amount']));

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
                        $this->storeComponentFeeAndTax($componentType, $detail);

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

    protected function storeComponentFeeAndTax($componentType, array $details)
    {
        if (array_key_exists(SetlDetails\Component::FEE, $details) === true)
        {
            $txnType = $details[SetlDetails\Component::FEE] < 0 ? 'credit' : 'debit';

            $this->createSetlDetailsEntity(
                $componentType  . '_' . SetlDetails\Component::FEE,
                $txnType,
                null,
                abs($details[SetlDetails\Component::FEE]));
        }

        if (array_key_exists(SetlDetails\Component::TAX, $details) === true)
        {
            $txnType = $details[SetlDetails\Component::TAX] < 0 ? 'credit' : 'debit';

            $this->createSetlDetailsEntity(
                $componentType  . '_' . SetlDetails\Component::TAX,
                $txnType,
                null,
                abs($details[SetlDetails\Component::TAX]));
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

    protected function newSettlementEntity($merchantSettleToPartner, Balance\Entity $balance, array $input = [])
    {
        $setl = (new Settlement\Entity);

        if(empty($input) === true)
        {
            $input = [
                Settlement\Entity::AMOUNT       => $this->amount,
                Settlement\Entity::STATUS       => Status::CREATED,
                Settlement\Entity::FEES         => $this->fee,
                Settlement\Entity::TAX          => $this->tax,
                Settlement\Entity::CHANNEL      => $this->channel,
            ];

            $setl->generateId();
        }
        else
        {
            $setl->setId($input[Settlement\Entity::ID]);

            unset($input[Settlement\Entity::ID]);
        }

        $setl = $setl->build($input);
        $setl->setChannel($this->getChannel($input[Settlement\Entity::CHANNEL]));

        $setl->merchant()->associate($this->merchant);

        $setl->balance()->associate($balance);

        if ($this->isNewService === true)
        {
            $this->setl = $setl;

            return;
        }

        // in case of test mode set settlement status to initiated
        if ($this->doMockAttemptProcessed() === true)
        {
            $setl->setStatus(Status::INITIATED);
        }

        $mid = $this->merchant->getId();

        if (isset($merchantSettleToPartner[$mid]) === true)
        {
            $partnerBankAccountId = $merchantSettleToPartner[$mid];

            $partnerBankAccount = $this->repo->bank_account->getBankAccountById($partnerBankAccountId);

            $setl->bankAccount()->associate($partnerBankAccount);
        }
        else
        {
            $setl->bankAccount()->associate($this->bankAccount);
        }

        $this->setl = $setl;
    }

    public function getChannel(string $channel): string
    {
        if (strcasecmp($channel, Channel::ICICI_OPGSP_EXPORT) === 0)
        {
            return Channel::ICICIEXP;
        }
        return $channel;
    }

    protected function updateSettlementEntity(array $merchantSettleToPartner)
    {
        $setl = $this->setl;

        $mid = $this->merchant->getId();

        // add partner bank account to settlement entity if submerchant is settling to partner
        // else try the settlement with current merchant bank account as that might
        // have been the reason for settlement failure
        if(isset($merchantSettleToPartner[$mid]) === true)
        {
            $partnerBankAccountId = $merchantSettleToPartner[$mid];

            $partnerBankAccount = $this->repo->bank_account->getBankAccountById($partnerBankAccountId);

            $setl->bankAccount()->associate($partnerBankAccount);
        }
        else
        {
            $setl->bankAccount()->associate($this->bankAccount);
        }

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

    /**
     * creates attempt for the settlement
     * if its run on test mode on prod then it'll also mock the bank response
     * for success condition
     *
     * @param int|null $initiateAt
     * @param array $merchantSettleToPartner
     * @return FundTransferAttempt\Entity
     */
    protected function createSettlementAttemptEntity(int $initiateAt = null, array $merchantSettleToPartner)
    {
        $customProperties = [
            'merchant_id'           => $this->merchant->getId(),
            'channel'               => $this->channel,
            'settlement_id'         => $this->setl->getId(),
            'transaction_count'     => $this->txns ? $this->txns->count() : 0,
            'settlement_amount'     => $this->setl->getAmount(),
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::FTA_CREATION_INITIATED,
            $this->setl,
            null,
            $customProperties);

        $fta = $this->createFundTransferAttempt($this->setl, $this->bankAccount, $initiateAt, $merchantSettleToPartner);

        if ($this->doMockAttemptProcessed() === true)
        {
            $this->updateMockResponse($fta);
        }

        (new Destination\Core)->register($this->setl, $fta);

        return $fta;
    }

    /**
     * It'll check the condition for mocking FTA
     * It depends on request mode and env
     * currently we are also considering dev for testing purpose
     *
     * @return bool
     */
    protected function doMockAttemptProcessed(): bool
    {
        // adding dev for local testing purpose
        // and enabling mocking attempt on on prod
        if (($this->mode === Mode::TEST) and (in_array($this->env, [Environment::PRODUCTION], true) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * It'll set the mock data required for the FTA to get processed
     * bank status code been set based on the channel used
     * utr will be a random string
     *
     * @param FundTransferAttempt\Entity $fta
     */
    protected function updateMockResponse(FundTransferAttempt\Entity $fta)
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $channel = $fta->getChannel();

        $status = $this->getStatusInstanceByChannel($channel);

        // set the fist successful status
        // in case of mock we have to set the only the success response
        $bankStatusCode = array_keys($status::getSuccessfulStatus())[0];

        $fta->setUtr($currentTimestamp . random_alphanum_string(6));

        $fta->setStatus(FundTransferAttempt\Status::INITIATED);

        $fta->setBankStatusCode($bankStatusCode);

        $this->repo->saveOrFail($fta);
    }

    /**
     * It'll return the status object back to the caller
     *
     * @param string $channel
     * @return mixed
     */
    protected function getStatusInstanceByChannel(string $channel)
    {
        $class = 'RZP\\Models\\FundTransfer\\'
        . ucfirst($channel)
        . '\\Reconciliation\\Status';

        return new $class();
    }

    protected function createPayoutAttemptEntity(int $initiateAt = null)
    {
        $bankAccount = $this->payout->destination ?? $this->payout->fundAccount->account;

        $this->createFundTransferAttempt($this->payout, $bankAccount, $initiateAt);
    }

    protected function createFundTransferAttempt(
        Base\Entity $source,
        BankAccount\Entity $bankAccount,
        int $initiateAt = null,
        $merchantSettleToPartner)
    {
        // TODO: this should be in fta core and should be using `create` function to do all this

        $fundTransferAttempt = new FundTransferAttempt\Entity;

        $fundTransferAttempt->merchant()->associate($this->merchant);

        $mid = $this->merchant->getId();

        if(isset($merchantSettleToPartner[$mid]) === true)
        {
            $partnerBankAccountId = $merchantSettleToPartner[$mid];
            $partnerBankAccount = $this->repo->bank_account->getBankAccountById($partnerBankAccountId);
            $fundTransferAttempt->bankAccount()->associate($partnerBankAccount);

        }
        else
        {
            $fundTransferAttempt->bankAccount()->associate($bankAccount);
        }

        $fundTransferAttempt->source()->associate($source);

        $initiateAt = ($initiateAt ?: Carbon::now(Timezone::IST)->getTimestamp());

        $values = [
            FundTransferAttempt\Entity::INITIATE_AT     => $initiateAt,
            FundTransferAttempt\Entity::CHANNEL         => $this->channel,
            FundTransferAttempt\Entity::VERSION         => FundTransferAttempt\Version::V3,
            FundTransferAttempt\Entity::STATUS          => FundTransferAttempt\Status::CREATED,
            // TODO: In case of retry also, the purpose should be the same as the original payout purpose.
            FundTransferAttempt\Entity::PURPOSE         => FundTransferAttempt\Purpose::SETTLEMENT,
        ];

        $fundTransferAttempt->fillAndGenerateId($values);

        $this->repo->saveOrFail($fundTransferAttempt);

        $this->bankTransferAtpt = $fundTransferAttempt;

        return $fundTransferAttempt;
        //TODO:: disabled fts flow for settlement
        //(new FundTransferAttempt\Core)->sendFTSFundTransferRequest($fundTransferAttempt, true);
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
    protected function attachSettlementBankAccount()
    {
        if ($this->isNewService === true)
        {
            return null;
        }

        $mode = $this->ba->getMode();

        $mid = $this->merchant->getId();

        if (($mode === Mode::TEST) and
            ($this->merchant->bankAccount === null) and
            (isset($this->merchantSettleToPartner[$mid]) === false))
        {
            $ba = $this->attachTestBank($this->merchant);
        }
        else
        {
            $ba = $this->repo->bank_account->getBankAccount($this->merchant);

            if (($ba === null) and
                (isset($this->merchantSettleToPartner[$mid]) === false) and
                ($this->isAggregateSettlement === false))
            {
                throw new Exception\LogicException(
                    'Settling bank account not found');
            }
            else
            {
                if(isset($this->merchantSettleToPartner[$mid]) === true)
                {
                    $partnerBankAccountId = $this->merchantSettleToPartner[$mid];
                    $ba = $this->repo->bank_account->getBankAccountById($partnerBankAccountId);
                }
            }
        }

        $this->bankAccount = $ba;

        return $ba;
    }

    protected function attachTestBank($merchant)
    {
        if ($this->isAggregateSettlement === true)
        {
            return null;
        }

        $attributes = array(
            'ifsc_code'             => BankAccount\Entity::SPECIAL_IFSC_CODE,
            'beneficiary_name'      => random_string_special_chars(5),
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

    public function createSettlementFromNewService($balance, array $params)
    {
        $input = [
            Settlement\Entity::AMOUNT          => $params['amount'],
            Settlement\Entity::FEES            => $params['fees'],
            Settlement\Entity::TAX             => $params['tax'],
            Settlement\Entity::CHANNEL         => $this->channel,
            Settlement\Entity::ID              => $params['settlement_id'],
            Settlement\Entity::STATUS          => $params['status'],
            Settlement\Entity::IS_NEW_SERVICE  => true,
        ];

        $merchantSettleToPartner = $this->merchantSettleToPartner;

        $this->setlDetailAmounts = $this->createSettlementDetailsFromNewService($params['details']);

        $this->setlDetails = new Base\Collection;

        $destinationMerchantId = null;

        $journalID = null;

        if (!empty($params['journal_id']))
        {
            $journalID = $params['journal_id'];
        }

        if(($params['type'] === Feature\Constants::AGGREGATE_SETTLEMENT) and isset($params['destination_merchant_id']) === true)
        {
            $destinationMerchantId = $params['destination_merchant_id'];

            if(empty($destinationMerchantId) === true)
            {
                throw new \Exception('empty destination MID sent for aggregate settlement type');
            }

        }

        $settlementTransfer = $this->repo->transaction(function() use ($merchantSettleToPartner, $balance, $input, $destinationMerchantId, $journalID)
        {
            //create new settlement entity
            $this->newSettlementEntity($merchantSettleToPartner, $balance, $input);

            // Create Settlement Details entity
            $this->createSettlementDetailsEntities();

            // Save settlement Entity to database
            $this->repo->saveOrFail($this->setl);

            // Save settlement Details Entity to database
            $this->repo->saveOrFailCollection($this->setlDetails);

            //create transaction corresponding to settlement
            $this->createTransaction($this->setl, $journalID);

            $settlementTransfer = null;

            if(empty($destinationMerchantId) === false)
            {
                $settlementTransfer = (new Transfer\Core)->transfer(
                    $this->setl,
                    $destinationMerchantId,
                    $balance->getType());
            }

            return $settlementTransfer;
        });

        return [$this->setl, $settlementTransfer];
    }

    protected function createSettlementDetailsFromNewService(array &$details)
    {
        foreach ($details as $componentType => &$detail)
        {
            $fee = (array_key_exists(SetlDetails\Component::FEE, $detail) === true) ? $detail[SetlDetails\Component::FEE] : 0;
            $tax = (array_key_exists(SetlDetails\Component::TAX, $detail) === true) ? $detail[SetlDetails\Component::TAX] : 0;

            if(array_key_exists(SetlDetails\Entity::AMOUNT, $detail) === true)
            {
                $detail[SetlDetails\Entity::AMOUNT] += $fee + $tax;
            }

        }

        return $details;
    }
}

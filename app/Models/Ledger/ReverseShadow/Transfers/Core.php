<?php

namespace RZP\Models\Ledger\ReverseShadow\Transfers;

use App;
use Carbon\Carbon;
use RZP\Constants\Metric;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Jobs\AsyncBalanceUpdateForTransfer;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Ledger\Constants;
use RZP\Models\Feature;
use RZP\Models\Currency;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Transfer;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use function PHPUnit\Framework\assertEquals;

class Core extends Base\Core
{

    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createBulkTransactionMessageForTransfer($transfer, $transferPaymentMerchant, $merchantAccounts, $fee, $tax, $transferPayment = null): array
    {

        $transferDebitJournal = $this->createTransactionMessageForDebitJournal($transfer, $merchantAccounts, $fee, $tax);

        if($transferPayment === null ) {
            $transferPayment = $this->generatePaymentEntity();
        }

        $transferCreditJournal = $this->createTransactionMessageForCreditJournal($transfer, $transferPaymentMerchant,$transferPayment);

        $bulkJournals = [$transferDebitJournal, $transferCreditJournal];

        return [
            LedgerConstants::TRANSACTOR_EVENT             => LedgerConstants::TRANSFER,
            LedgerConstants::TRANSACTOR_ID                => $transfer->getPublicId(),
            LedgerConstants::TRANSACTION_DATE             => $transfer->getUpdatedAt(),
            LedgerConstants::CURRENCY                     => LedgerConstants::INR_CURRENCY,
            LedgerConstants::JOURNALS                     => $bulkJournals,
            LedgerConstants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            LedgerConstants::LEDGER_INTEGRATION_MODE      => LedgerConstants::REVERSE_SHADOW,
            LedgerConstants::TENANT                       => LedgerConstants::TENANT_PG
        ];

    }

    public function generatePaymentEntity(): Payment\Entity
    {
        $payment = new Payment\Entity;
        $payment->generateId();
        return $payment;
    }


    public function createTransactionMessageForDebitJournal($transfer, $merchantAccounts, $fee, $tax): array
    {
        $merchantAccountBalances = $this->getMerchantAccountBalancesMap($merchantAccounts);

        $amountCreditsAccounts = $this->getValidAmountCreditsAccounts($merchantAccounts);

        try
        {
            $splitzAmountRevampResponse = $this->app['splitzService']->evaluateRequest([
                'id' => $transfer->getMerchantId(),
                'experiment_id' => $this->app['config']->get('app.amount_credits_split_in_ledger_experiment_id'),
            ]);

            $variant = $splitzAmountRevampResponse['response']['variant']['name'] ?? '';

            $amountCreditsSplitEnabled = ($variant === 'enable');
        }
        catch (\Throwable $e)
        {
            $amountCreditsSplitEnabled = false;
        }

        $this->trace->info(TraceCode::TRANSFER_AMOUNT_CREDITS_V2_EXPERIMENT_EVALUTATION, [
            'merchant_id' => $transfer->getMerchantId(),
            'is_exp_enabled' => $amountCreditsSplitEnabled,
            'balances' => $merchantAccountBalances,
            'credit_accounts' => $amountCreditsAccounts,
        ]);

        $moneyParams = $this->generateMoneyParamsForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax, $amountCreditsSplitEnabled);

        $additionalParams = $this->fetchRulesForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax);

        $transactionMessage = $this->generateBaseForJournalEntry($transfer);

        $transactionMessage[LedgerConstants::MONEY_PARAMS] = $moneyParams;

        if (($amountCreditsSplitEnabled === true) &&
            ($merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS] >= $transfer->getAmount()))
        {
            $additionalParams[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::AMOUNT_CREDITS_REDEMPTION_V2;

            $transactionMessage[Constants::DYNAMIC_MONEY_PARAMS] = $this->getDynamicMoneyParams($amountCreditsAccounts, $transfer->getAmount());
        }

        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = array_merge(
            $additionalParams, [LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_DEBIT]
        );

        return $transactionMessage;
    }

    public function generateMoneyParamsForTransferDebit(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax, $amountCreditsSplitEnabled = false): array
    {
        $moneyParams = [];

        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];
        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $fee = $fee - $tax;

        $amount = $transfer->getAmount();

        $moneyParams[LedgerConstants::AMOUNT]                         = strval($amount);
        $moneyParams[LedgerConstants::BASE_AMOUNT]                    = strval($amount);

        if ($amountCredits > 0 && $amountCredits >= $transfer->getAmount())
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::RAZORPAY_REWARDS]           = strval($amount);

            if ($amountCreditsSplitEnabled === false)
            {
                $moneyParams[LedgerConstants::AMOUNT_CREDITS]         = strval($amount);
            }
        }
        else if ($this->isFeeCredits($feeCredits, $fee + $tax) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[LedgerConstants::FEE_CREDITS]                = strval($tax + $fee);
        }
        // if merchant is on postpaid model
        else if($this->isTransferPostpaid($transfer) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[LedgerConstants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
        }

        $maxNegativeLimit = $this->getMaxNegativeLimitForTransfer($transfer);

        if ($maxNegativeLimit !== 0)
        {
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_LIMIT] = strval($maxNegativeLimit);
        }

        return $moneyParams;
    }

    public function fetchRulesForTransferDebit(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax) : array
    {
        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];
        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $rule = [];

        if ($amountCredits > 0 && $amountCredits >= $transfer->getAmount())
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::AMOUNT_CREDITS_REDEMPTION;
        }
        else if($this->isFeeCredits($feeCredits, $fee) === true)
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::FEE_CREDITS;
        }
        else if($this->isTransferPostpaid($transfer))
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::POSTPAID;
        }

        return $rule;
    }

    public function createTransactionMessageForCreditJournal(Transfer\Entity $transfer, Merchant\Entity $transferPaymentMerchant, $transferPayment = null): array
    {
        $moneyParams = $this->generateMoneyParamsForTransferCredit($transfer);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $transferPaymentMerchant->getId(),
            Constants::CURRENCY                  => $transferPaymentMerchant->getCurrency(),
            Constants::TRANSACTION_DATE          => $transfer->getUpdatedAt(),
        ];
        if($transferPayment !== null) {
            $transactionMessage[LedgerConstants::NOTES] =  [LedgerConstants::PAYMENT_ID => $transferPayment->getPublicId()];
        }
        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS]      = [ LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_CREDIT ];

        return  $transactionMessage;
    }

    public function generateMoneyParamsForTransferCredit(Transfer\Entity $transfer): array
    {
        $amount = $transfer->getAmount();

        return [
            LedgerConstants::AMOUNT                     => strval($amount),
            LedgerConstants::BASE_AMOUNT                => strval($amount),
            LedgerConstants::MERCHANT_PAYABLE_AMOUNT    => strval($amount),
            LedgerConstants::MERCHANT_BALANCE_AMOUNT    => strval($amount),
        ];
    }

    public function saveOrderAndPaymentTransferReverseShadowLedgerEntriesToOutbox($transfer, $transferPaymentMerchant)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccounts = $this->getMerchantAccounts($ledgerService, $transfer->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $transactionMessage = $this->createBulkTransactionMessageForTransfer($transfer, $transferPaymentMerchant, $merchantAccounts, $fee, $tax);

        $transactorId = $transactionMessage[LedgerConstants::TRANSACTOR_ID];

        $transactorEvent = $transactionMessage[LedgerConstants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);

        return [$fee, $tax];
    }

    public function createLedgerEntriesForTransferReverseShadowInSync($transfer, $transferPayment)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccounts = $this->getMerchantAccounts($ledgerService, $transfer->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $journalPayload = $this->createBulkTransactionMessageForTransfer($transfer, $transferPayment->merchant, $merchantAccounts, $fee, $tax,$transferPayment);

        $journalResponse = $this->createJournalInLedger($journalPayload, true);

        [$creditJournalId, $debitJournalId] = $this->determineJournalIdForAPITransaction($journalResponse, "merchant_balance", "merchant_balance" );

        if((empty($creditJournalId)) or
            (empty($debitJournalId)))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EXPECTED_FUND_ACCOUNT_TYPE_NOT_PRESENT,
                null,
                [
                    LedgerConstants::TRANSFER_ID      => $transfer->getId(),
                ]);
        }

        $filteredDebitJournal = array_filter($journalResponse, function ($item) use ($debitJournalId) {
            return $item['id'] === $debitJournalId;
        });

        $filteredCreditJournal = array_filter($journalResponse, function ($item) use ($creditJournalId) {
            return $item['id'] === $creditJournalId;
        });

        $debitJournal = reset($filteredDebitJournal);

        $creditJournal = reset($filteredCreditJournal);


        // dual write beginning and nss to push
        $runningInQueue = app()->runningInQueue();
        if ($runningInQueue === true)
        {
            app('worker.ctx')->setLedgerDualWriteFlow(true);
        }
        else
        {
            app('request.ctx')->setLedgerDualWriteFlow(true);
        }

        // create txns without balance update and dispatch for settlement if experiment is enabled
        // balance update is done asynchronously via Kafka for sync journal creates
        $this->createTransferTxnAndTransferPaymentTxnAndPushForSettlement($transfer, $debitJournal, $creditJournal, $transferPayment);

        return [$fee, $tax];
    }

    protected function isTransferPostpaid(Transfer\Entity $transfer): bool
    {
        return ($transfer->merchant->getFeeModel() === Merchant\FeeModel::POSTPAID);
    }

    public function isNegativeBalanceEnabledForTxnTypeAndMerchant(string $txnType, string $balanceType = Balance\Type::PRIMARY) : bool
    {
        if ((array_key_exists($balanceType, Balance\Core::NEGATIVE_FLOWS) === false) or
            (in_array($txnType, Balance\Core::NEGATIVE_FLOWS[$balanceType]) === false))
        {
            return false;
        }

        return true;
    }

    private function getMaxNegativeLimitForTransfer(Transfer\Entity $transfer): int
    {
        $balanceType = Balance\Type::PRIMARY;
        $txnType = Transaction\Type::TRANSFER;

        $isNegativeBalanceEnabled = $this->isNegativeBalanceEnabledForTxnTypeAndMerchant($txnType, $balanceType);

        if ($isNegativeBalanceEnabled === false)
        {
            return 0;
        }

        $merchant = $transfer->merchant;

        $balance= $this->getBalanceByTypeFromTiDBForMerchantWithFail($merchant, $balanceType);

        $negativeAllowedFlows = (new BalanceConfig\Core())->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            return 0;
        }

        $maxNegative = (new BalanceConfig\Core())->getMaxNegativeAmountManualForBalanceId($balance->getId());

        return $maxNegative;

    }

    /**
     * calculates the max negative limit for transfer. Doesn't take transfer entity as input as it is not required for this computation.
     * The merchant is derived from this->merchant which is set in the constructor. This function can be used as a replacement for getMaxNegativeLimitForTransfer
     * @return int
     */
    public function getMaxNegativeLimitForTransferV2(): int
    {
        $balanceType = Balance\Type::PRIMARY;
        $txnType = Transaction\Type::TRANSFER;

        $isNegativeBalanceEnabled = $this->isNegativeBalanceEnabledForTxnTypeAndMerchant($txnType, $balanceType);

        if ($isNegativeBalanceEnabled === false)
        {
            return 0;
        }

        $merchant = $this->merchant;

        $balance= $this->getBalanceByTypeFromTiDBForMerchantWithFail($merchant, $balanceType);

        $negativeAllowedFlows = (new BalanceConfig\Core())->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            return 0;
        }

        $maxNegative = (new BalanceConfig\Core())->getMaxNegativeAmountManualForBalanceId($balance->getId());

        return $maxNegative;
    }

    public function getBalanceByTypeFromTiDBForMerchantWithFail(Merchant\Entity $merchant, string $balanceType)
    {
        $this->trace->info(TraceCode::TRANSFER_BALANCE_CONFIG_EXPERIMENT_EVALUATION, [
            'merchant_id' => $merchant->getId(),
            'balance_type' => $balanceType,
            'message'=> 'fetching merchant balance from harvester'
        ]);

        return $this->repo->balance->getMerchantBalanceByTypeTiDBOrFail($merchant->getId(), $balanceType);

    }

    public function getBalanceByTypeFromTiDBForMerchantWithoutFail(Merchant\Entity $merchant, string $balanceType)
    {

        // fetch balance for balance_id from TiDB
        $this->trace->info(TraceCode::TRANSFER_BALANCE_CONFIG_EXPERIMENT_EVALUATION, [
            'merchant_id' => $merchant->getId(),
            'balance_type' => $balanceType,
            'message'=> 'fetching merchant balance from TiDB without fail'
        ]);
        return $this->repo->balance->getMerchantBalanceByTypeTiDB($merchant->getId(), $balanceType);

    }

    private function pushTransferDataToKafkaForAPITransactionCreation($transfer, $transferPayment, $creditJournalId, $debitJournalId)
    {
        if (($this->app->runningUnitTests() === true))
        {
            return;
        }

        $producerKey =  $transfer->getId().'_'.$transferPayment->getId();

        $data = [
            'transfer_id' => $transfer->getId(),
            'payment_id' => $transferPayment->getId(),
            'transfer_journal_id' => $debitJournalId,
            'payment_journal_id' => $creditJournalId

        ];

        $message = [
            Constants::KAFKA_MESSAGE_DATA      => $data,
            Constants::KAFKA_MESSAGE_TASK_NAME  => Constants::CREATE_TRANSACTION_FOR_DIRECT_TRANSFER
        ];

        $topic = env('CREATE_REFUND_TXN_API', Constants::CREATE_REFUND_TXN_API);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_TRANSFER_API_TXN_PUSH_SUCCESS, [
                Constants::PRODUCER_KEY => $producerKey,
                Constants::TOPIC        => $topic,
                Constants::MESSAGE      => $message
            ]);

            $this->trace->count(Metric::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE, [
                Constants::TOPIC        => $topic,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->count(Metric::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE, [
                Constants::TOPIC        => $topic,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE,
                [
                    Constants::PRODUCER_KEY => $producerKey,
                    Constants::TOPIC        => $topic,
                    Constants::MESSAGE      => $message
                ]);

            throw $ex;
        }
    }

    public function createReverseShadowEntriesForCustomerWalletLoadingV2(Transfer\Entity $transfer)
    {
        $transactionMessage = $this->createTransactionMessageForCustomerWalletLoadingV2($transfer);

        $transactorId = $transactionMessage[LedgerConstants::TRANSACTOR_ID];

        $transactorEvent = $transactionMessage[LedgerConstants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $outboxPayload->setEntityType(LedgerOutboxConstants::CUSTOMER_TRANSFER);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);

        $this->trace->info(TraceCode::TRANSACTION_MESSAGE_CREATED_IN_REVERSE_SHADOW,
            [
                'transactor_id'               => $transactorId,
                'transactor_event'            =>$transactorEvent,
                'payload_name'                => $payloadName,
            ]);
    }

    public function createTransactionMessageForCustomerWalletLoading(Transfer\Entity $transfer): array
    {
        $debitTransaction = $transfer->transaction;

        $merchant = $debitTransaction->merchant;

        $transactionMessage = [
            Constants::API_TRANSACTION_ID        => $debitTransaction->getId(),
            Constants::MERCHANT_ID               => $debitTransaction->getMerchantId(),
            Constants::CURRENCY                  => $merchant->getCurrency(),
            Constants::TRANSACTION_DATE          => $debitTransaction->getCreatedAt(),
            Constants::LEDGER_INTEGRATION_MODE   => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY           => Uuid::uuid1(),
            Constants::TENANT                    => Constants::TENANT_PG,
        ];

        $additionalParams = $this->fetchRulesForTransferCredits($debitTransaction);

        $additionalParams = (count($additionalParams) > 0) ? $additionalParams : null;

        $moneyParams = $this->generateMoneyParamsForCustomerWalletLoadingDebit($debitTransaction);

        $transferData = [
            Constants::TRANSACTOR_EVENT             => Constants::CUSTOMER_WALLET_LOADING,
            Constants::TRANSACTOR_ID                => $transfer->getPublicId(),
            Constants::MONEY_PARAMS                 => $moneyParams,
            Constants::ADDITIONAL_PARAMS            => $additionalParams
        ];

        return array_merge($transactionMessage, $transferData);
    }

    public function createTransactionMessageForCustomerWalletLoadingV2(Transfer\Entity $transfer)
    {
        $ledgerService = $this->app['ledger'];

        $response = $this->app['splitzService']->evaluateRequest([
            'id'            => $transfer->getMerchantId(),
            'experiment_id' => $this->app['config']->get('app.amount_credits_split_in_ledger_experiment_id'),
        ]);

        $variant = $response['response']['variant']['name'] ?? '';

        $amountCreditsSplitEnabled = $variant === 'enable';

        $merchantAccountBalances = [];

        $amountCreditsAccounts = [];

        if ($variant == "enable")
        {
            $merchantAccounts = $this->getMerchantAccounts($ledgerService, $transfer->getMerchantId());

            $merchantAccountBalances = $this->getMerchantAccountBalancesMap($merchantAccounts);

            $amountCreditsAccounts = $this->getValidAmountCreditsAccounts($merchantAccounts);
        }
        else
        {
            $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $transfer->getMerchantId());
        }

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $moneyParams = $this->generateMoneyParamsForCustomerWalletLoadingDebitV2($transfer, $merchantAccountBalances, $fee, $tax, $amountCreditsSplitEnabled);

        $additionalParams = $this->fetchRulesForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax);

        $resultingBalance = floatval($merchantAccountBalances[LedgerConstants::MERCHANT_BALANCE]) - floatval($moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]);

        $maxNegativeLimit = $this->getMaxNegativeLimitForTransfer($transfer);

        if ($resultingBalance < $maxNegativeLimit * -1)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE,
                Transaction\Entity::BALANCE,
                [
                    'amount' => $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT],
                    'balance' => $merchantAccountBalances[LedgerConstants::MERCHANT_BALANCE],
                    'negative_limit' => $maxNegativeLimit
                ]
            );
        }

        $journalData = array(
            Constants::TRANSACTOR_ID                 => $transfer->getPublicId(),
            Constants::TRANSACTOR_EVENT              => Constants::CUSTOMER_WALLET_LOADING,
            Constants::MONEY_PARAMS                  => $moneyParams,
            Constants::ADDITIONAL_PARAMS             => (count($additionalParams) > 0) ? $additionalParams : null,
            Constants::LEDGER_INTEGRATION_MODE       => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY               => Uuid::uuid1(),
            Constants::TENANT                        => Constants::TENANT_PG,
        );

        if ($amountCreditsSplitEnabled &&
            ($merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS] >= $transfer->getAmount()))
        {
            $journalData[Constants::DYNAMIC_MONEY_PARAMS] = $this->getDynamicMoneyParams($amountCreditsAccounts, $transfer->getAmount());;

            $journalData[Constants::ADDITIONAL_PARAMS][LedgerConstants::VERSION] = "v2";
        }

        $transactionMessage = $this->generateBaseForJournalEntry($transfer);

        return array_merge($transactionMessage, $journalData);
    }

    public function fetchRulesForTransferCredits(Transaction\Entity $transaction)
    {
        $rule = [];

        if($transaction->isGratis() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS;
        }

        if($transaction->isFeeCredits() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }

        return $rule;
    }

    public function generateMoneyParamsForCustomerWalletLoadingDebit(Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = $transaction->getAmount();
        $tax = $transaction->getTax() !== null ? $transaction->getTax() : 0;
        $fee = $transaction->getFee() != null ? $transaction->getFee() - $tax : 0;

        $moneyParams[Constants::AMOUNT]                         = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                    = strval($amount);

        if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval($tax);
            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        else if ($transaction->isGratis() === true)
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            $moneyParams[Constants::TAX]                        = strval($tax);
            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($fee);
        }

        return $moneyParams;
    }

    public function generateMoneyParamsForCustomerWalletLoadingDebitV2(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax, $amountCreditsSplitEnabled): array
    {
        $moneyParams = [];

        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];

        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $amount = $transfer->getAmount();

        $transferCommission = $fee - $tax;

        $moneyParams[Constants::AMOUNT]                         = strval($amount);

        $moneyParams[Constants::BASE_AMOUNT]                    = strval($amount);

        if($amountCredits >= $amount)
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);

            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);

            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

            if ($amountCreditsSplitEnabled === false)
            {
                $moneyParams[LedgerConstants::AMOUNT_CREDITS]             = strval($amount);
            }

            $moneyParams[LedgerConstants::RAZORPAY_REWARDS]           = strval($amount);
        }
        else if ($this->isFeeCredits($feeCredits, $transferCommission + $tax) === true)
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);

            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

            $moneyParams[Constants::TAX]                        = strval($tax);

            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($transferCommission);

            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $transferCommission);
        }
        else if($this->isTransferPostpaid($transfer) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

            $moneyParams[LedgerConstants::TAX]                        = strval($tax);

            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($transferCommission);

            $moneyParams[LedgerConstants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $transferCommission);

            $moneyParams[LedgerConstants::CUSTOMER_WALLET_AMOUNT]    = strval($amount);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);

            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $transferCommission + $tax);

            $moneyParams[Constants::TAX]                        = strval($tax);

            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($transferCommission);
        }

        $maxNegativeLimit = $this->getMaxNegativeLimitForTransfer($transfer);

        if ($maxNegativeLimit !== 0)
        {
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_LIMIT] = strval($maxNegativeLimit);
        }

        return $moneyParams;
    }

    public function createTransferTxnAndTransferPaymentTxnAndPushForSettlement($transfer, $debitJournal, $creditJournal, $transferPayment=null)
    {
        if ($transferPayment === null)
        {
            $transferPayment = $this->repo->payment->findByTransferIdAndMerchant($transfer->getId(), $transfer->getToId());

            $transferPayment = $this->repo->payment->findOrFail($transferPayment->getId());
        }

        $transferMerchant = $this->repo->merchant->findOrFail($transfer->getMerchantId());

        $paymentMerchant = $this->repo->merchant->findOrFail($transferPayment->getMerchantId());

        if (($transferMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
            or ($paymentMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false))
        {
            return;
        }

        $properties = [
            'request_data' => json_encode(["merchant_id" => $transferMerchant->getId()]),
            'id'            => $transferMerchant->getId(),
            'experiment_id' => $this->app['config']->get('app.api_ledger_dual_write_rearch'),
        ];

        // dual write re-arch enabled for both child+parent if enabled for parent
        $dualWriteRearchEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');

        $this->trace->info(TraceCode::TRANSFER_REVERSE_SHADOW_DUAL_EXPERIMENT_EVALUTATION,
            [
                'merchant_id' => $transfer->getMerchantId(),
                'is_exp_enabled' => $dualWriteRearchEnabled,
            ]);

        if ($dualWriteRearchEnabled === false) {

            $transferTxn = $this->createTransferTransactionFromLedgerJournal($debitJournal, $transfer);

            $transferPaymentTxn = $this->createTransferPaymentTransactionFromLedgerJournal($creditJournal, $transferPayment);

            // write api txn as is and dispatch for settlement. along with that trigger balance update
            $txnCore = (new Transaction\Core());

            $txnCore->dispatchForSettlementBucketing($transferTxn);

            $txnCore->dispatchForSettlementBucketing($transferPaymentTxn);

            (new Transfer\Core())->dispatchForAsyncBalanceUpdate($transfer);

            $this->trace->info(TraceCode::TRANSFER_REVERSE_SHADOW_TXN_CREATION_SUCCESS,
                [
                    'transfer_id'               => $transfer->getId(),
                    'transfer_txn_id'           => $transferTxn->getId(),
                    'transfer_payment_txn_id'   => $transferPaymentTxn->getId(),
                ]);
        }
        else
        {
            // merchant is on RS, child+parent both.
            // do early dispatch of settlement and dispatch transfers for dual write.
            // ensure that job does dual write+balance update on api.

            //note:  if nss dispatch fails+ or dual write dispatch/write fails, the job is to be retried
            $transferTxn = $this->createTransferTransactionFromLedgerJournalRearch($debitJournal, $transfer);

            $transferPaymentTxn = $this->createTransferPaymentTransactionFromLedgerJournalRearch($creditJournal, $transferPayment);


            $bucketCore = new Bucket\Core;

            // dispatch transfer and payment to nss
            $parentStatus = $bucketCore->shouldProcessViaNewService($transferMerchant->getId());

            if ($parentStatus === true) {
                $bucketCore->publishForSettlement($transferTxn);
            }

            $childStatus = $bucketCore->shouldProcessViaNewService($paymentMerchant->getId());

            if ($childStatus === true) {
                $bucketCore->publishForSettlement($transferPaymentTxn);
            }

            // dispatch for dual write job
            $this->pushTransferDataToKafkaForAPIDualWrite($transfer, $transferPayment, $creditJournal, $debitJournal);

        }

    }


    private function pushTransferDataToKafkaForAPIDualWrite($transfer, $transferPayment, $creditJournal, $debitJournal)
    {
        if (($this->app->runningUnitTests() === true))
        {
            return;
        }

        $producerKey =  $transfer->getId();

        $data = [
            'payload_api'=>[
                'id' => $transfer->getId(),
                'transfer_id' => $transfer->getId(),
                'payment_id' => $transferPayment->getId(),
                'journals'=>[
                    $creditJournal,
                    $debitJournal
                ]
            ]
        ];

        $message = [
            Constants::KAFKA_MESSAGE_DATA      => $data,
            Constants::KAFKA_MESSAGE_TASK_NAME  => Constants::DUAL_WRITE_TRANSACTION_FOR_API_EVENTS
        ];

        $topic = env('DUAL_WRITE_TRANSACTION_FOR_API_EVENTS', Constants::DUAL_WRITE_TRANSACTION_FOR_API_EVENTS);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message)));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_TRANSFER_API_TXN_PUSH_SUCCESS, [
                Constants::PRODUCER_KEY => $producerKey,
                Constants::TOPIC        => $topic,
                Constants::MESSAGE      => $message
            ]);

            $this->trace->count(Metric::KAFKA_TRANSFER_API_TXN_PUSH_SUCCESS, [
                Constants::TOPIC        => $topic,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->count(Metric::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE, [
                Constants::TOPIC        => $topic,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE,
                [
                    Constants::PRODUCER_KEY => $producerKey,
                    Constants::TOPIC        => $topic,
                    Constants::MESSAGE      => $message
                ]);

            throw $ex;
        }
    }


    public function createTransferTransactionFromLedgerJournal($journal, $transfer)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        $merchant = $transfer->merchant;

        if ($transfer->isExternal() === false)
        {
            $txn->sourceAssociate($transfer);
        }
        else
        {
            $txn->setEntityId($transfer->getId());

            $txn->setType($transfer->getEntity());
        }

        $txn->merchant()->associate($merchant);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $commissionLedgerEntry = $this->getCommissionLedgerEntryForTransactionTypeFromJournal($journal, Transaction\Type::TRANSFER);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journal,Constants::PAYABLE, Constants::RZP_GST);

        $apiFee = $commissionLedgerEntry['amount'] + $taxBalanceLedgerEntry['amount'];

        $values = [
            Transaction\Entity::API_FEE         => (int) $apiFee,
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::RECONCILED_AT   => time(),
            Transaction\Entity::RECONCILED_TYPE => ReconciledType::NA,
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::CHANNEL         => $transfer->merchant->getChannel(),
        ];

        $txn->fill($values);

        $txn->setBalanceUpdated(false);

        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $txn->setReference3("disabled");
        }

        $this->repo->saveOrFail($txn);

        if ($transfer->isExternal() === false)
        {
            $this->repo->saveOrFail($transfer);
        }

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
            [
                'txn_id'                => $txn->getId(),
                'transfer_id'           => $transfer->getId(),
                'transfer_source_id'    => $transfer->getSourceId(),
                'transfer_source_type'  => $transfer->getSourceType(),
            ]);

        return $txn;
    }

    public function createTransferPaymentTransactionFromLedgerJournal($journal, $transferPayment)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        if ($transferPayment->hasTransaction() === true && $transferPayment->isExternal() === false)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($transferPayment);
        }
        else if ($transferPayment->isExternal() === true)
        {
            $txn->setEntityId($transferPayment->getId());

            $txn->setType($transferPayment->getEntity());
        }
        else
        {
            $txn->sourceAssociate($transferPayment);

            $txn->merchant()->associate($transferPayment->merchant);
        }

        $txnData = [
            Transaction\Entity::CHANNEL         => $transferPayment->merchant->getChannel(),
        ];

        if ($transferPayment->getGateway() === Payment\Gateway::WALLET_OPENWALLET)
        {
            $txnData[Transaction\Entity::RECONCILED_AT]     = time();
            $txnData[Transaction\Entity::RECONCILED_TYPE]   = ReconciledType::NA;
        }

        $txn->fill($txnData);

        $settledAt = (new Transaction\Core())->getSettledAtTimestamp($transferPayment);

        $onHold = $transferPayment->getOnHold() ?? false;

        $txn->setReconciledAt(time());

        $txn->setReconciledType(ReconciledType::NA);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Transaction\Entity::ON_HOLD, $onHold);

        $txn->setBalanceUpdated(false);

        $merchant = $txn->merchant;

        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $txn->setReference3("disabled");
        }

        $this->repo->saveOrFail($txn);

        if ($transferPayment->isExternal() === false)
        {
            $this->repo->saveOrFail($transferPayment);
        }

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
            [
                'txn_id'                => $txn->getId(),
                'payment_id'            => $transferPayment->getId(),
                'transfer_id'           => $transferPayment->getTransferId(),
            ]);

        return $txn;
    }

    public function createTransferPaymentTransactionFromLedgerJournalWithoutIdempotency($journal, $transferPayment)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        if ($transferPayment->isExternal() === true)
        {
            $txn->setEntityId($transferPayment->getId());

            $txn->setType($transferPayment->getEntity());
        }
        else
        {
            $txn->sourceAssociate($transferPayment);

            $txn->merchant()->associate($transferPayment->merchant);
        }

        $txnData = [
            Transaction\Entity::CHANNEL         => $transferPayment->merchant->getChannel(),
        ];

        if ($transferPayment->getGateway() === Payment\Gateway::WALLET_OPENWALLET)
        {
            $txnData[Transaction\Entity::RECONCILED_AT]     = time();
            $txnData[Transaction\Entity::RECONCILED_TYPE]   = ReconciledType::NA;
        }

        $txn->fill($txnData);

        $settledAt = (new Transaction\Core())->getSettledAtTimestamp($transferPayment);

        $onHold = $transferPayment->getOnHold() ?? false;

        $txn->setReconciledAt(time());

        $txn->setReconciledType(ReconciledType::NA);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Transaction\Entity::ON_HOLD, $onHold);

        $txn->setBalanceUpdated(false);

        $merchant = $txn->merchant;

        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $txn->setReference3("disabled");
        }

        $this->repo->saveOrFail($txn);

        if ($transferPayment->isExternal() === false)
        {
            $this->repo->saveOrFail($transferPayment);
        }

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
                           [
                               'txn_id'                => $txn->getId(),
                               'payment_id'            => $transferPayment->getId(),
                               'transfer_id'           => $transferPayment->getTransferId(),
                           ]);

        return $txn;
    }

     public function createTransferPaymentTransactionFromLedgerJournalRearch($journal, $transferPayment)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        if ($transferPayment->hasTransaction() === true && $transferPayment->isExternal() === false)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($transferPayment);
        }
        else if ($transferPayment->isExternal() === true)
        {
            $txn->setEntityId($transferPayment->getId());

            $txn->setType($transferPayment->getEntity());
        }
        else
        {
            $transferPayment->setAttribute(Payment\Entity::TRANSACTION_ID,$txn->getId());

            $txn->setEntityId($transferPayment->getId());

            $txn->setType($transferPayment->getEntity());

            $txn->merchant()->associate($transferPayment->merchant);
        }

        $txnData = [
            Transaction\Entity::CHANNEL         => $transferPayment->merchant->getChannel(),
        ];

        if ($transferPayment->getGateway() === Payment\Gateway::WALLET_OPENWALLET)
        {
            $txnData[Transaction\Entity::RECONCILED_AT]     = time();
            $txnData[Transaction\Entity::RECONCILED_TYPE]   = ReconciledType::NA;
        }

        $txn->fill($txnData);

        $settledAt = (new Transaction\Core())->getSettledAtTimestamp($transferPayment);

        $onHold = $transferPayment->getOnHold() ?? false;

        $txn->setReconciledAt(time());

        $txn->setReconciledType(ReconciledType::NA);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Transaction\Entity::ON_HOLD, $onHold);

        $txn->setBalanceUpdated(false);

        $merchant = $txn->merchant;

        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $txn->setReference3("disabled");
        }

        $this->trace->info(TraceCode::TIDB_STREAMING_MAKESHIFT_LOGIC, [
            "txnReference3"        => $txn->getReference3(),
            "enableTidbStreaming"   => false
        ]);

        if ($transferPayment->isExternal() === false)
        {
            $this->repo->saveOrFail($transferPayment);
        }

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
            [
                'txn_id'                => $txn->getId(),
                'payment_id'            => $transferPayment->getId(),
                'transfer_id'           => $transferPayment->getTransferId(),
            ]);

        return $txn;
    }

     public function createTransferTransactionFromLedgerJournalRearch($journal, $transfer)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        $merchant = $transfer->merchant;

        // source_associate does 2 way mapping. Txn ->Transfer and Transfer->Txn.
        //  for dual write rearch cases for enabled variant scenarios only transfer will be mapped txn and not vice versa.
        // Kafka dual writes will do the pending mapping for same
        if ($transfer->isExternal() === false)
        {
            $txn->setEntityId($transfer->getId());

            $txn->setType($transfer->getEntity());

            $transfer->setAttribute(Transfer\Entity::TRANSACTION_ID,$txn->getId());
        }
        else
        {
            $txn->setEntityId($transfer->getId());

            $txn->setType($transfer->getEntity());
        }

        $txn->merchant()->associate($merchant);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $commissionLedgerEntry = $this->getCommissionLedgerEntryForTransactionTypeFromJournal($journal, Transaction\Type::TRANSFER);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journal,Constants::PAYABLE, Constants::RZP_GST);

        $apiFee = $commissionLedgerEntry['amount'] + $taxBalanceLedgerEntry['amount'];

        $values = [
            Transaction\Entity::API_FEE         => (int) $apiFee,
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::RECONCILED_AT   => time(),
            Transaction\Entity::RECONCILED_TYPE => ReconciledType::NA,
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::CHANNEL         => $transfer->merchant->getChannel(),
        ];

        $txn->fill($values);

        $txn->setBalanceUpdated(false);

        if($merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
        {
            $txn->setReference3("disabled");
        }

        $this->trace->info(TraceCode::TIDB_STREAMING_MAKESHIFT_LOGIC, [
            "txnReference3"        => $txn->getReference3(),
            "enableTidbStreaming"   => false
        ]);

        // note: txn is not db saved, only transfer is saved to db here in rearch. Api ledger is created via kafka dual write job
        if ($transfer->isExternal() === false)
        {
            $this->repo->saveOrFail($transfer);
        }

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
            [
                'txn_id'                => $txn->getId(),
                'transfer_id'           => $transfer->getId(),
                'transfer_source_id'    => $transfer->getSourceId(),
                'transfer_source_type'  => $transfer->getSourceType(),
            ]);

        return $txn;
    }


    public function createVirtualTransferPaymentTransactionFromLedgerJournal($journal, $transferPayment)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        $txnData = [
            Transaction\Entity::CHANNEL         => $transferPayment->merchant->getChannel(),
        ];

        if ($transferPayment->getGateway() === Payment\Gateway::WALLET_OPENWALLET)
        {
            $txnData[Transaction\Entity::RECONCILED_AT]     = time();
            $txnData[Transaction\Entity::RECONCILED_TYPE]   = ReconciledType::NA;
        }

        $txn->fill($txnData);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $onHold = $transferPayment->getOnHold() ?? false;

        $txn->setReconciledAt(time());

        $txn->setReconciledType(ReconciledType::NA);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Transaction\Entity::ON_HOLD, $onHold);

        $txn->setBalanceUpdated(false);

        return $txn;
    }

    public function shouldProcessPaymentTransfersForReverseShadow($paymentId, $merchant)
    {
        $transfers = $this->repo
            ->transfer
            ->fetchBySourceTypeAndIdAndMerchant(
                Transfer\Constant::PAYMENT,  $paymentId, $merchant , [Transfer\Status::PENDING]);

        foreach ($transfers as $transfer)
        {
            $payloadName = $this->getPayloadName($transfer->getPublicId(), LedgerConstants::TRANSFER);

            $outboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadName($payloadName);

            if (count($outboxEntries) === 0)
            {
                [, $debitJournalId] = (new Transfer\Core())->fetchJournalIdFromLedgerForTransfer($transfer, $transfer->getMerchantId());

                $this->trace->info(TraceCode::PAYMENT_TRANSFER_PROCESS_RETRY,
                    [
                        'payment_id'              => $paymentId,
                        'transfer_id'             => $transfer->getId(),
                        'outbox_entry_found'      => false,
                        'journal_found'           => empty($debitJournalId) === false,
                    ]);

                if (empty($debitJournalId) === false)
                {
                    // Journal exists, so transfer shouldn't be re-processed
                    return false;
                }

                // No outbox and journal present
                return true;
            }

            $this->trace->info(TraceCode::PAYMENT_TRANSFER_PROCESS_RETRY,
                [
                    'payment_id'            => $paymentId,
                    'transfer_id'           => $transfer->getId(),
                    'outbox_entry_found'    => true,
                ]);
        }

        return false;
    }

    public function shouldProcessOrderTransfersForReverseShadow($orderId, $merchant)
    {
        $transfers = $this->repo
            ->transfer
            ->fetchBySourceTypeAndIdAndMerchant(
                Transfer\Constant::ORDER,  $orderId, $merchant , [Transfer\Status::PENDING, Transfer\Status::FAILED]);

        foreach ($transfers as $transfer)
        {
            $payloadName = $this->getPayloadName($transfer->getPublicId(), LedgerConstants::TRANSFER);

            $outboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadName($payloadName);

            if (count($outboxEntries) === 0)
            {
                [, $debitJournalId] = (new Transfer\Core())->fetchJournalIdFromLedgerForTransfer($transfer, $transfer->getMerchantId());

                $this->trace->info(TraceCode::ORDER_TRANSFER_PROCESS_RETRY,
                    [
                        'order_id'              => $orderId,
                        'transfer_id'           => $transfer->getId(),
                        'outbox_entry_found'    => false,
                        'journal_found'         => empty($debitJournalId) === false,
                    ]);

                if (empty($debitJournalId) === false)
                {
                    // Journal exists, so transfer shouldn't be re-processed
                    return false;
                }

                // No outbox and journal present
                return true;
            }

            $this->trace->info(TraceCode::ORDER_TRANSFER_PROCESS_RETRY,
                [
                    'order_id'              => $orderId,
                    'transfer_id'           => $transfer->getId(),
                    'outbox_entry_found'    => true,
                ]);
        }

        return false;
    }
}

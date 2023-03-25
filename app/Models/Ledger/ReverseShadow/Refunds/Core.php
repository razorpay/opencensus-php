<?php

namespace RZP\Models\Ledger\ReverseShadow\Refunds;

use Carbon\Carbon;
use RZP\Constants\Metric;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Ledger\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Pricing\Fee;
use RZP\Models\Dispute\Entity;
use RZP\Services\KafkaProducer;
use RZP\Models\Merchant\RefundSource;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Payment\Refund\Speed as Speed;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    /**
     * @throws \Exception
     */
    public function createLedgerEntriesForRefundReverseShadow(RefundEntity $refund)
    {
        $this->trace->info(TraceCode::LEDGER_REFUND_JOURNAL_CREATE_REQUEST, [
            RefundConstants::REFUND_ID  => $refund->getId(),
        ]);

        $commission = $refund->getFee() - $refund->getTax();
        $tax = $refund->getTax();

        // Generate payload
        if (($refund->isDirectSettlementWithoutRefund() === true) or
            ($refund->isDirectSettlementRefund() === true))
        {
            $journalPayload = $this->createTransactionMessageForDSRefund($refund, $commission, $tax);
        }
        else if ($refund->payment->hasBeenCaptured() === false)
        {
            $journalPayload = $this->createTransactionMessageForAuthorizedRefund($refund);
        }
        else
        {
            $journalPayload = $this->createTransactionMessageForCapturedRefund($refund, $commission, $tax);
        }

        if ($journalPayload === null)
        {
            $this->trace->debug(TraceCode::NULL_JOURNAL_PAYLOAD_CREATED, [
                "refund"    => $refund
            ]);
            return null;
        }

        try
        {
            $journalResponse = $this->createRefundJournalInLedger($journalPayload);
        }
        catch (\Exception $e)
        {

            $this->trace->traceException($e);

            $this->trace->debug(TraceCode::LEDGER_JOURNAL_CREATE_ERROR, [
                LedgerConstants::MESSAGE    => $e->getMessage(),
                RefundConstants::REFUND_ID  => $refund->getId()
            ]);

            if (str_contains($e->getMessage(), \RZP\Models\LedgerOutbox\Constants::INSUFFICIENT_BALANCE_FAILURE) and $this->isRefundCredits($refund->merchant) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE, null, [
                    RefundConstants::REFUND_ID  => $journalPayload[Constants::TRANSACTOR_ID],
                ]);
            }

            if (str_contains($e->getMessage(), \RZP\Models\LedgerOutbox\Constants::INSUFFICIENT_BALANCE_FAILURE) and $this->isRefundCredits($refund->merchant) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS, null, [
                    Constants::MESSAGE  => TraceCode::getMessage(TraceCode::MERCHANT_REFUND_CREDITS_DEBIT_FAILURE),
                ]);
            }

            throw new Exception\ServerErrorException(
                TraceCode::LEDGER_JOURNAL_CREATE_ERROR,
                ErrorCode::SERVER_ERROR
            );
        }

        $this->pushToKafkaForAPITransactionCreation($refund, $journalResponse);

        return $journalResponse;
    }

    private function createTransactionMessageForDSRefund(RefundEntity $refund, $fee, $tax)
    {
        list($rule, $moneyParams) = $this->fetchMoneyParamsAndLedgerRulesForRefundsDirectSettlement($refund, $fee, $tax);

        if ((isset($rule) === false) and
            (isset($moneyParams) === false))
        {
            $this->trace->info(
                TraceCode::LEDGER_DS_REFUND_CASE_NOT_FOUND,
                [
                    "refund"      => $refund
                ]
            );
            return null;
        }

        $transactionMessage = $this->createTransactionMessageForRefund($refund, $moneyParams);

        $transactionMessage[Constants::ADDITIONAL_PARAMS] = $rule;

        return $transactionMessage;

    }

    private function createTransactionMessageForCapturedRefund(RefundEntity $refund, $fee, $tax)
    {
        list($rule, $moneyParams) = $this->fetchLedgerRulesAndMoneyParamsForRefunds($refund, $fee, $tax);

        $transactionMessage = $this->createTransactionMessageForRefund($refund, $moneyParams);

        $transactionMessage[Constants::ADDITIONAL_PARAMS] = $rule;

        return $transactionMessage;
    }

    private function createTransactionMessageForAuthorizedRefund(RefundEntity $refund)
    {
        $amount = abs($refund->getAmount());
        $moneyParams = [
            Constants::AMOUNT       => strval($amount),
            Constants::BASE_AMOUNT  => strval($amount)
        ];

        $transactionMessage = $this->createTransactionMessageForRefund($refund, $moneyParams);

        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [
            Constants::REFUND_ACCOUNTING => Constants::AUTOREFUND
        ];

        return $transactionMessage;
    }

    private function fetchLedgerRulesAndMoneyParamsForRefunds(RefundEntity $refund, $fee, $tax)
    {
        $rule = null;
        $moneyParams = [];

        $amount = abs($refund->getAmount());

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);

        if($refund->isRefundSpeedInstant() === true) {
            $moneyParams[Constants::REFUND_AMOUNT]      = strval($amount);
            $moneyParams[Constants::COMMISSION]         = strval($fee);
            $moneyParams[Constants::TAX]                = strval($tax);
        }

        // case when a normal speed refund occurs
        if(($refund->getSpeedDecisioned() === speed::NORMAL) and
            ($this->isRefundCredits($refund->merchant)) === true)
        {
            $rule[Constants::REFUND_ACCOUNTING] = Constants::REFUND_PROCESSED_WITH_CREDITS;

            $moneyParams[Constants::REFUND_CREDITS] = strval($amount);
            $moneyParams[Constants::REFUND_AMOUNT]  = strval($amount);
        }
        // case when an instant speed refund occurs
        else if($refund->isRefundSpeedInstant() === true)
        {
            if($this->isRefundCredits($refund->merchant) === true and $refund->merchant->isPostpaid() === false)
            {
                $rule[Constants::REFUND_ACCOUNTING]         = Constants::REFUND_PROCESSED_WITH_CREDITS_INSTANT;
                $moneyParams[Constants::REFUND_CREDITS]     = strval($amount + $fee + $tax);
            }
            else if($this->isRefundCredits($refund->merchant) === false and $refund->merchant->isPostpaid() === false)
            {
                $rule[Constants::REFUND_ACCOUNTING]                 = Constants::REFUND_INSTANT_PROCESSED;
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            }
            else if($this->isRefundCredits($refund->merchant) === true and $refund->merchant->isPostpaid() === true)
            {
                $rule[Constants::REFUND_ACCOUNTING]                 = Constants::INSTANT_REFUND_PROCESSED_WITH_CREDITS_POSTPAID_MODEL;
                $moneyParams[Constants::REFUND_CREDITS]             = strval($amount);
                $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($fee + $tax);
            }
            else if($this->isRefundCredits($refund->merchant) === false and $refund->merchant->isPostpaid() === true)
            {
                $rule[Constants::REFUND_ACCOUNTING]                 = Constants::INSTANT_REFUND_PROCESSED_POSTPAID_MODEL;
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
                $moneyParams[Constants::MERCHANT_RECEIVABLE_AMOUNT] = strval($fee + $tax);
            }
        }
        else
        {
            // When normal refund occurs (speed = normal, refund_source = merchant_balance)
            $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        return [$rule, $moneyParams];
    }

    private function createTransactionMessageForRefund(RefundEntity $refund, array $moneyParams): array
    {
        $transactorEvent = Constants::REFUND_PROCESSED;

        if (empty($refund->getNotes() === false))
        {
            $notes = $refund->getNotes();

            if ((isset($notes['reason']) === true) and
                (str_starts_with($notes['reason'], Entity::getSign()) === true))
            {
                $transactorEvent = Constants::DISPUTE_REFUND_PROCESSED;
            }
        }

        $transactionDate = ($refund->getCreatedAt() === 0) ? Carbon::now(Timezone::IST)->getTimestamp() : $refund->getCreatedAt();

        $refundData = array(
            Constants::MERCHANT_ID                   => $refund->getMerchantId(),
            Constants::CURRENCY                      => $refund->getCurrency(),
            Constants::TRANSACTOR_ID                 => $refund->getPublicId(),
            Constants::TRANSACTOR_EVENT              => $transactorEvent,
            Constants::MONEY_PARAMS                  => $moneyParams,
            Constants::IDENTIFIERS                   => [
                Constants::GATEWAY           => $refund->getGateway(),
            ],
            Constants::TRANSACTION_DATE     => $transactionDate
        );

        return $refundData;
    }

    private function fetchMoneyParamsAndLedgerRulesForRefundsDirectSettlement(RefundEntity $refund, $fee, $tax)
    {
        if (($refund->isDirectSettlementRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($this->isRefundCredits($refund->merchant) === true))
        {
            return $this->fetchDSWithRefundTerminalInstantSpeedRefundCredits($refund, $fee, $tax);
        }

        if (($refund->isDirectSettlementRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($this->isRefundCredits($refund->merchant) === false))
        {
            return $this->fetchDSWithRefundTerminalInstantSpeedMerchantBalance($refund, $fee, $tax);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($this->isRefundCredits($refund->merchant) === true))
        {
            return $this->fetchDSWithoutRefundTerminalInstantSpeedRefundCredits($refund, $fee, $tax);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === true) and
            ($this->isRefundCredits($refund->merchant) === false))
        {
            return $this->fetchDSWithoutRefundTerminalInstantSpeedMerchantBalance($refund, $fee, $tax);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($this->isRefundCredits($refund->merchant) === true))
        {
            return $this->fetchDSWithoutRefundTerminalNormalSpeedRefundCredits($refund);
        }

        if (($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($this->isRefundCredits($refund->merchant) === false))
        {
            return$this->fetchDSWithoutRefundTerminalNormalSpeedMerchantBalance($refund);
        }

        if (($refund->payment->hasBeenCaptured() === false) and
            ($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($this->isRefundCredits($refund->merchant) === true))
        {
            return $this->fetchAutoDSWithoutRefundTerminalNormalSpeedRefundCredits($refund);
        }

        if (($refund->payment->hasBeenCaptured() === false) and
            ($refund->isDirectSettlementWithoutRefund() === true) and
            ($refund->isRefundSpeedInstant() === false) and
            ($this->isRefundCredits($refund->merchant) === false))
        {
            return $this->fetchAutoDSWithoutRefundTerminalNormalSpeedMerchantBalance($refund);
        }
        return [null, null];
    }

    private function fetchDSWithRefundTerminalInstantSpeedRefundCredits(RefundEntity $refund, $fee, $tax)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITH_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]        = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS]     = strval( $fee + $tax + $amount);
        $moneyParams[Constants::COMMISSION]         = strval($fee);
        $moneyParams[Constants::TAX]                = strval($tax);
        $moneyParams[Constants::REFUND_AMOUNT]      = strval($amount);

        return [$rule, $moneyParams];

    }

    private function fetchDSWithRefundTerminalInstantSpeedMerchantBalance(RefundEntity $refund, $fee, $tax)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITH_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval( $fee + $tax + $amount);
        $moneyParams[Constants::COMMISSION]                 = strval($fee);
        $moneyParams[Constants::TAX]                        = strval($tax);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);

        return [$rule, $moneyParams];

    }
    private function fetchDSWithoutRefundTerminalInstantSpeedRefundCredits(RefundEntity $refund, $fee, $tax)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND_CREDITS;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]        = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS]     = strval($amount + $fee + $tax);
        $moneyParams[Constants::REFUND_AMOUNT]      = strval($amount);
        $moneyParams[Constants::COMMISSION]         = strval($fee);
        $moneyParams[Constants::TAX]                = strval($tax);

        return [$rule, $moneyParams];
    }

    private function fetchDSWithoutRefundTerminalInstantSpeedMerchantBalance(RefundEntity $refund, $fee, $tax)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_INSTANT_REFUND;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
        $moneyParams[Constants::COMMISSION]                 = strval($fee);
        $moneyParams[Constants::TAX]                        = strval($tax);

        return [$rule, $moneyParams];

    }
    private function fetchDSWithoutRefundTerminalNormalSpeedRefundCredits (RefundEntity $refund)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_NORMAL_REFUND_CREDITS;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS] = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]  = strval($amount);

        return [$rule, $moneyParams];
    }
    private function fetchDSWithoutRefundTerminalNormalSpeedMerchantBalance(RefundEntity $refund)
    {
        $amount = abs($refund->getAmount());
        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::DIRECT_SETTLEMENT_NORMAL_REFUND;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return [$rule, $moneyParams];

    }

    private function fetchAutoDSWithoutRefundTerminalNormalSpeedRefundCredits(RefundEntity $refund)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTO_REFUND_DIRECT_SETTLEMENT_CREDITS_NORMAL;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_CREDITS] = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]  = strval($amount);

        return [$rule, $moneyParams];

    }
    public  function fetchAutoDSWithoutRefundTerminalNormalSpeedMerchantBalance(RefundEntity $refund)
    {
        $amount = abs($refund->getAmount());

        $rule[Constants::DIRECT_SETTLEMENT_ACCOUNTING] = Constants::AUTO_REFUND_DIRECT_SETTLEMENT_NORMAL;
        $rule[Constants::DIRECT_SETTLEMENT_TERMINAL] = Constants::WITHOUT_REFUND;

        $moneyParams[Constants::BASE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::REFUND_AMOUNT]              = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return [$rule, $moneyParams];

    }

    private function callLedgerForJournalCreation(array $journalPayload)
    {
        $ledgerService = $this->app['ledger'];

        $ledgerRequestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER => Constants::TENANT_PG
        ];

        $journal = $ledgerService->createJournal($journalPayload, $ledgerRequestHeaders);
    }

    private function pushToKafkaForAPITransactionCreation(RefundEntity $refund, $journalResponse)
    {
        $producerKey =  $refund->getId();

        $data = [
            "journal_id"        => $journalResponse['id'],
            "id"                => $refund->getId(),
            "payment_id"        => $refund->payment->getId(),
            "amount"            => $refund->getAmount(),
            "base_amount"       => $refund->getBaseAmount(),
            "speed_decisioned"  => $refund->getSpeedDecisioned(),
            "gateway"           => $refund->getGateway(),
            "mode"              => $this->mode,
            "fee"               => $refund->getFee(),
            "tax"               => $refund->getTax(),
        ];

        $message = [
            LedgerConstants::KAFKA_MESSAGE_DATA      => $data,
            $message[LedgerConstants::KAFKA_MESSAGE_TASK_NAME] = LedgerConstants::CREATE_TXN_FOR_REFUND_TASK
        ];

        $topic = env('CREATE_REFUND_TXN_API', LedgerConstants::CREATE_REFUND_TXN_API);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_REFUND_API_TXN_PUSH_SUCCESS, [
                "producer_key" => $producerKey,
                "topic" => $topic,
                "message" => $message
            ]);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_REFUND_API_TXN_PUSH_FAILED,
                [
                    "producer_key" => $producerKey,
                    "topic" => $topic,
                    "message" => $message
                ]);

            $this->trace->count(Metric::REFUND_API_TXN_KAFKA_PUSH_FAILURE);
        }
    }

}

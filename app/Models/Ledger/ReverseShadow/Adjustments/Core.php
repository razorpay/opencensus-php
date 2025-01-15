<?php

namespace RZP\Models\Ledger\ReverseShadow\Adjustments;

use App;
use RZP\Models\Base;
use RZP\Error\Error;
use Ramsey\Uuid\Uuid;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Transaction;
use RZP\Services\KafkaProducer;
use RZP\Models\Merchant\Balance;
use RZP\Models\Ledger\Constants;
use RZP\Models\Adjustment\Entity;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createLedgerEntryForRazorpayDisputeDeductReverseShadow(Entity $adjustment, $disputePublicId)
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        $transactorId = $disputePublicId;

        $transactorEvent = Constants::RAZORPAY_DISPUTE_DEDUCT;

        $transactionMessage = $this->generateBaseForJournalEntry($adjustment);

        $maxNegativeLimit = $this->getMaxNegativeLimitForAdjustment($adjustment);

        $disputeDeductData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::GATEWAY_DISPUTE_PAYABLE_AMOUNT    => strval($adjustmentAmount),
                Constants::MERCHANT_BALANCE_LIMIT            => strval($maxNegativeLimit)
            ],
            Constants::NOTES                        => [
                Constants::ADJUSTMENT_ID    => $adjustment->getId()
            ]
        );

        $journalPayload = array_merge($transactionMessage, $disputeDeductData);

        $journal = $this->createAdjustemntAndDisputeJournalInLedger($journalPayload);

        $this->dispatchToSettlementFromJournalIfApplicable($journal, $adjustment);

        return $journal;
    }

    public function createAdjustemntAndDisputeJournalInLedger($journalPayload)
    {
        try
        {
            $journal = $this->createJournalInLedger($journalPayload);

            return $journal;
        }
        catch( \Exception $e)
        {
            $err = $e->getError() ? $e->getError()->toPublicArray() : [];

            $errorResponse = $err['error'] ?? [];

            $errorMessage =  $errorResponse[Error::DESCRIPTION];

            $transactorId = $journalPayload[Constants::TRANSACTOR_ID];

            $transactorEvent = $journalPayload[Constants::TRANSACTOR_EVENT];

            if (str_contains($errorMessage, LedgerReverseShadowConstants::BAD_REQUEST_RECORD_ALREADY_EXIST) === true)
            {
                //fetch journal and return
                $ledgerService = $this->app['ledger'];

                $existingJournal = $this->getJournalByTransactorInfo($transactorId, $transactorEvent, $ledgerService);

                if ($existingJournal === null)
                {
                    $this->trace->info(TraceCode::PG_LEDGER_JOURNAL_NOT_FOUND, [
                        Constants::TRANSACTOR_ID               => $transactorId,
                        Constants::TRANSACTOR_EVENT            => $transactorEvent,
                    ]);

                    throw $e;
                }

                return $existingJournal;
            }

            throw $e;
        }
    }
    public function createLedgerEntryForForRazorpayDisputeReversalReverseShadow(Entity $adjustment, $disputePublicId)
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        $transactorId = $disputePublicId;

        $transactorEvent = Constants::RAZORPAY_DISPUTE_REVERSAL;

        $transactionMessage = $this->generateBaseForJournalEntry($adjustment);

        $maxNegativeLimit = $this->getMaxNegativeLimitForAdjustment($adjustment);

        $disputeReversalData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::GATEWAY_DISPUTE_PAYABLE_AMOUNT    => strval($adjustmentAmount),
                Constants::MERCHANT_BALANCE_LIMIT            => strval($maxNegativeLimit)
            ],
            Constants::NOTES                        => [
                Constants::ADJUSTMENT_ID    => $adjustment->getId()
            ]
        );

        $journalPayload = array_merge($transactionMessage, $disputeReversalData);

        $journal = $this->createAdjustemntAndDisputeJournalInLedger($journalPayload);

        $this->dispatchToSettlementFromJournalIfApplicable($journal, $adjustment);

        return $journal;
    }

    public function createLedgerEntryForManualAdjustmentReverseShadow(Entity $adjustment, string $publicId)
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        $transactorId = $publicId;

        $transactorEvent = Constants::POSITIVE_ADJUSTMENT;

        if ($adjustment->getAmount() < 0)
        {
            $transactorEvent =  Constants::NEGATIVE_ADJUSTMENT;
        }

        $transactionMessage = $this->generateBaseForJournalEntry($adjustment);

        $maxNegativeLimit = $this->getMaxNegativeLimitForAdjustment($adjustment);

        $manualAdjData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::ADJUSTMENT_AMOUNT                 => strval($adjustmentAmount),
                Constants::MERCHANT_BALANCE_LIMIT            => strval($maxNegativeLimit)
            ]
        );

        $journalPayload = array_merge($transactionMessage, $manualAdjData);

        $journal = $this->createAdjustemntAndDisputeJournalInLedger($journalPayload);

        $this->dispatchToSettlementFromJournalIfApplicable($journal, $adjustment);

        return $journal;
    }

    public function createLedgerEntryForManualReservePrimaryNegativeAdjustmentReverseShadow(Entity $adjustment, string $publicId)
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        $transactorId = $publicId;

        $transactorEvent = Constants::NEGATIVE_ADJUSTMENT;

        $transactionMessage = $this->generateBaseForJournalEntry($adjustment);

        $maxNegativeLimit = $this->getMaxNegativeLimitForAdjustment($adjustment, Balance\Type::RESERVE_PRIMARY);

        $manualAdjData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::RESERVE_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::ADJUSTMENT_AMOUNT                 => strval($adjustmentAmount),
                Constants::MERCHANT_BALANCE_LIMIT            => strval($maxNegativeLimit)
            ],
            Constants::ADDITIONAL_PARAMS            => [
                Constants::BALANCE_TYPE => Constants::RESERVE_BALANCE
            ]
        );

        $journalPayload = array_merge($transactionMessage, $manualAdjData);

        return $this->createAdjustemntAndDisputeJournalInLedger($journalPayload);

    }

    private function getMaxNegativeLimitForAdjustment(Entity $adjustment, $balanceType = Balance\Type::PRIMARY): int
    {
        $txnType = Transaction\Type::ADJUSTMENT;

        $balanceConfigCore = new Balance\BalanceConfig\Core();

        $isNegativeBalanceEnabled = $this->isNegativeBalanceEnabledForTxnTypeAndMerchant($txnType, $balanceType);

        if ($isNegativeBalanceEnabled === false) {
            return 0;
        }

        $balance = $adjustment->merchant->getBalanceByTypeOrFail($balanceType);

        $negativeAllowedFlows = $balanceConfigCore->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            return 0;
        }

        return $balanceConfigCore->getMaxNegativeAmountManualForBalanceId($balance->getId());

    }

    private function dispatchToSettlementFromJournalIfApplicable($journal, $adjustment)
    {
        $transactorEvent = $journal[LedgerConstants::TRANSACTOR_EVENT];

        if (($transactorEvent === LedgerConstants::RAZORPAY_DISPUTE_DEDUCT) OR ($transactorEvent === LedgerConstants::RAZORPAY_DISPUTE_REVERSAL))
        {
            $virtualAdjustmentTransaction = $this->transformJournalResponseToTransactionEntityForDispute($journal, $adjustment);
        }
        else if (($transactorEvent === LedgerConstants::POSITIVE_ADJUSTMENT) OR ($transactorEvent === LedgerConstants::NEGATIVE_ADJUSTMENT))
        {
            $virtualAdjustmentTransaction = $this->transformJournalResponseToTransactionEntityForAdjustment($journal);
        }

        $bucketCore = new Bucket\Core;

        $status = $bucketCore->shouldProcessViaNewService($virtualAdjustmentTransaction->getMerchantId());

        if ($status === true)
        {
            $bucketCore->publishForSettlement($virtualAdjustmentTransaction);
        }
    }

    public function createOnlyLedgerEntryInReverseShadow($journalPayload)
    {
        $this->trace->info(TraceCode::REVERSE_SHADOW_CREATE_JOURNAL_IN_SYNC, [
            "request"    => $journalPayload
        ]);

        $journal = $this->createJournalInLedger($journalPayload, false, false, true);

        $this->trace->info(TraceCode::REVERSE_SHADOW_CREATE_JOURNAL_IN_SYNC, [
            Constants::RESPONSE      => $journal
        ]);

        return $journal;
    }
}

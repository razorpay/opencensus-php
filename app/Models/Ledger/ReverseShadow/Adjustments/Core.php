<?php

namespace RZP\Models\Ledger\ReverseShadow\Adjustments;

use App;
use Ramsey\Uuid\Uuid;
use RZP\Constants\Metric;
use RZP\Models\Adjustment\Entity;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Services\KafkaProducer;

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

        $disputeDeductData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::GATEWAY_DISPUTE_PAYABLE_AMOUNT    => strval($adjustmentAmount)
            ],
        );

        $journalPayload = array_merge($transactionMessage, $disputeDeductData);

        $journal = $this->createJournalInLedger($journalPayload);

        $this->pushAdjustmentToKafkaForAPITransactionCreation($adjustment, $journal);
    }

    public function createLedgerEntryForForRazorpayDisputeReversalReverseShadow(Entity $adjustment, $disputePublicId)
    {
        $adjustmentAmount = $adjustment->getAmount() != null ? abs($adjustment->getAmount()) : 0;

        $transactorId = $disputePublicId;

        $transactorEvent = Constants::RAZORPAY_DISPUTE_REVERSAL;

        $transactionMessage = $this->generateBaseForJournalEntry($adjustment);

        $disputeReversalData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::GATEWAY_DISPUTE_PAYABLE_AMOUNT    => strval($adjustmentAmount)
            ],
        );

        $journalPayload = array_merge($transactionMessage, $disputeReversalData);

        $journal = $this->createJournalInLedger($journalPayload);

        $this->pushAdjustmentToKafkaForAPITransactionCreation($adjustment, $journal);
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

        $manualAdjData = array(
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::MONEY_PARAMS                 => [
                Constants::MERCHANT_BALANCE_AMOUNT           => strval($adjustmentAmount),
                Constants::BASE_AMOUNT                       => strval($adjustmentAmount),
                Constants::ADJUSTMENT_AMOUNT                 => strval($adjustmentAmount)
            ]
        );

        $journalPayload = array_merge($transactionMessage, $manualAdjData);

        $journal = $this->createJournalInLedger($journalPayload);

        $this->pushAdjustmentToKafkaForAPITransactionCreation($adjustment, $journal);
    }

    private function pushAdjustmentToKafkaForAPITransactionCreation(Entity $adjustment,  $journal)
    {
        if (($this->app->runningUnitTests() === true))
        {
            return;
        }

        $producerKey =  $adjustment->getId();

        $data = [
            Entity::ID  => $adjustment->getId(),
            Entity::TRANSACTION_ID => $journal['id']
        ];

        $message = [
            Constants::KAFKA_MESSAGE_DATA      => $data,
            Constants::KAFKA_MESSAGE_TASK_NAME  => Constants::CREATE_TRANSACTION_FOR_ADJUSTMENT
        ];

        $topic = env('CREATE_REFUND_TXN_API', Constants::CREATE_REFUND_TXN_API);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_ADJUSTMENT_API_TXN_PUSH_SUCCESS, [
                Constants::PRODUCER_KEY => $producerKey,
                Constants::TOPIC        => $topic,
                Constants::MESSAGE      => $message
            ]);

            $this->trace->count(Metric::KAFKA_ADJUSTMENT_API_TXN_PUSH_SUCCESS, [
                Constants::TOPIC        => $topic,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->count(Metric::KAFKA_ADJUSTMENT_API_TXN_PUSH_FAILURE, [
                Constants::TOPIC        => $topic,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_ADJUSTMENT_API_TXN_PUSH_FAILURE,
                [
                    Constants::PRODUCER_KEY => $producerKey,
                    Constants::TOPIC        => $topic,
                    Constants::MESSAGE      => $message
                ]);

            throw $ex;
        }
    }
}

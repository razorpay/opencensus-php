<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Jobs\MdrFixJob;
use RZP\Models\FundAccount\Validation\Core;
use RZP\Models\Payment;
use RZP\Models\Pricing\Fee;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Refund;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Report\Types\BasicEntityReport;

class Service extends Base\Service
{
    public function settlementFixer()
    {
        return $this->repo->transaction(function()
        {
            return (new BugFixer)->settlementFixerInTxn();
        });
    }

    public function createFeeBreakupForTransaction($input)
    {
        return (new Transaction\DataMigration())->createFeeBreakupForTransaction($input);
    }

    public function updateMultipleTransactions(array $input)
    {
        (new Validator())->validateInput('unsettled_txns_channel_update', $input);

        $channel    = $input['channel'];

        $merchantId = $input['merchant_id'];

        return (new Transaction\BulkUpdate)->updateMultipleTransactions($merchantId, $channel);
    }

    public function markTransactionPostpaid($input)
    {
        $this->trace->info(
            TraceCode::TRANSACTIONS_TO_POSTPAID_INPUT,
            $input);

        $transactionIds = $input['transaction_ids'];

        $successIds = [];

        $failedIds = [];

        $transactions = $this->repo->transaction->fetchMultipleTransactionsFromIds($transactionIds);
        $transactionCore = (new Transaction\Core);

        foreach ($transactions as $transaction)
        {
            try
            {
                $transactionCore->markTransactionPostpaid($transaction);
                $successIds[] = $transaction->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::TRANSACTIONS_TO_POSTPAID_FAILED,
                    ['transaction_id' => $transaction->getId()]
                );

                $failedIds[] = $transaction->getId();
            }
        }

        $response = [
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
        ];

        $this->trace->info(
            TraceCode::TRANSACTIONS_TO_POSTPAID_RESPONSE,
            $response);

        return $response;
    }

    public function fixSettled(string $entity, array $input): array
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_VALIDATION_TRANSACTION_FIX,
            $input);

        return $this->app['api.mutex']->acquireAndRelease(
            'fix_settled_column_for_fav',
            function () use ($entity, $input)
            {
                $count = $input['count'] ?? 200;

                $txnIds =  $this->repo->transaction->fetchSettledTransactionsWithoutSettlementId($entity, $count);

                $this->repo->transaction->updateSettledToFalse($entity, $txnIds);

                return [
                    'count'             => $count,
                    'txns_processed'    => $txnIds,
                ];
            });
    }

    /**
     * This Method is used to put the transaction on hold passed in the input
     * @param array $input
     * @return array
     */
    public function toggleTransactionHold(array $input)
    {
        (new Validator)->validateInput('toggle_transaction_hold', $input);

        $this->trace->info(
          TraceCode::TOGGLE_TRANSACTION_HOLD,
          [
                'transaction_ids' => $input['transaction_ids'],
                'reason_for_hold' => $input['reason'],
          ]);

        return $this->toggleTransactionFlag($input['transaction_ids'], true);
    }

    /**
     * This method is used to release the transactions passed in the input
     * @param array $input
     * @return array
     */
    public function toggleTransactionRelease(array $input)
    {
        (new Validator)->validateInput('toggle_transaction_release', $input);

        $this->trace->info(
            TraceCode::TOGGLE_TRANSACTION_RELEASE,
            [
                'transaction_ids' => $input['transaction_ids'],
            ]);

        return $this->toggleTransactionFlag($input['transaction_ids'], false);
    }

    /**
     * This methods basically used to toggle the on_hold flag of the transaction Ids
     * @param array $transactionIds
     * @param bool $toggleFlag
     * @return array
     */
    public function toggleTransactionFlag(array $transactionIds, bool $toggleFlag)
    {
        $requestCount = sizeof($transactionIds);

        $failedTransactionUpdate = (new Transaction\Core)->toggleTransactionOnHold($transactionIds, $toggleFlag);

        $failedCount = sizeof($failedTransactionUpdate);

        $successCount = $requestCount - $failedCount;

        $response = [
            'total_requests'        => $requestCount,
            'successfully_updated'  => $successCount,
            'failed'                => $failedCount,
        ];

        $this->trace->info(
            TraceCode::TOGGLE_TRANSACTION_COMPLETE,
            [
                'response'                      => $response,
                'transactions_failed_to_update' => $failedTransactionUpdate,
            ]);

        return $response;
    }

    public function mdrAdjustment(array $input)
    {
        MdrFixJob::dispatch($this->mode, $input);
        return ['success' => true];
    }

    public function processMdrAdjustmentRow(array $input)
    {
        $this->trace->info(TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES, ['foo'=>'bar']);
        $paymentId = $input['payment_id'];

        $merchantId = $input['merchant_id'];

        $transactionId = $input['transaction_id'];
        try
        {

            $payment = $this->repo->payment->findOrFail($paymentId);

            $transaction = $this->repo->transaction->findOrFail($transactionId);

            if (($payment->getMerchantId() !== $merchantId) or
                ($transaction->getMerchantId() !== $merchantId))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            }
            [$oldFee, $oldTax] = [$transaction->getFee(), $transaction->getTax()];

            $pricingFee = new Fee;

            $pricingFee->setMerchant($payment->merchant);

            [$newFee, $newTax] = $pricingFee->calculateMerchantFees($payment);

            $response = [
                Entity::MERCHANT_ID => $merchantId,
                'transaction_id'    => $transactionId,
                'payment_id'        => $paymentId,
                'old_fee'           => $oldFee,
                'old_tax'           => $oldTax,
                'new_fee'           => $newFee,
                'new_tax'           => $newTax,
                'delta_fee'         => $newFee - $oldFee,
                'delta_tax'         => $newTax - $oldTax,
                'success'           => true,
                'errorDescription'  => '',
            ];
        }
        catch (\Throwable $e)
        {
            $response = [
                Entity::MERCHANT_ID => $merchantId,
                'transaction_id'    => $transactionId,
                'payment_id'        => $paymentId,
                'success'           => false,
                'errorDescription'  => $e->getMessage(),
            ];
        }

        return $response;
    }
}

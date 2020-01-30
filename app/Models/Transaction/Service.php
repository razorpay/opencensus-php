<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FundAccount\Validation\Core;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing\Fee;
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

    public function mdrAdjustmentCalculation(array $input)
    {

        $response = new Base\PublicCollection();

        foreach ($input as $row)
        {
            $rowResponse = $this->processMdrAdjustmentRow($row);

            $response->push($rowResponse);
        }

        return $response->toArrayWithItems();
    }

    protected function processMdrAdjustmentRow(array $input)
    {
        $transactionId = $input['transaction_id'];

        $response = [
            'transaction_id'    => $transactionId,
            'idempotency_key'   => $input['idempotency_key'],
        ];

        try
        {
            $this->trace->info(TraceCode::MDR_ADJUSTMENT_CALCULATION_INITIATED, ['transaction_id' => $transactionId]);

            $transaction = $this->repo->transaction->findOrFail($transactionId);

            $paymentId = $transaction->getEntityId();

            if ($transaction->getType() != Constants\Entity::PAYMENT)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            }

            $merchantId = $transaction->getMerchantId();

            $payment = $this->repo->payment->findOrFail($paymentId);

            if (($payment->getMerchantId() !== $merchantId) or
                ($transaction->getMerchantId() !== $merchantId))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            }

            [$oldFee, $oldTax] = [$transaction->getFee(), $transaction->getTax()];

            $pricingFee = new Fee;

            $pricingFee->setMerchant($payment->merchant);

            [$newFee, $newTax] = $pricingFee->calculateMerchantFees($payment);

            $isDebitLessThan2k = $payment->getBaseAmount() < 2000 * 100 ? true : false;

            $response['payment_id']         = $paymentId;

            $response['merchant_id']        = $merchantId;

            $response['TYPE']               = 'payment';

            $response['old_fee']            = $oldFee;

            $response['old_tax']            = $oldTax;

            $response['new_fee']            = $newFee;

            $response['new_tax']            = $newTax;

            $response['delta_fee']          = $newFee - $oldFee;

            $response['delta_tax']          = $newTax - $oldTax;

            $response['debit_less_than_2k'] = $isDebitLessThan2k;

            $response['success']            = true;

            $response['errorDescription']   = '';


            $response = array_merge($response, $transaction->toArrayPublic());

            if (isset($response['notes']) === true)
            {
                unset($response['notes']);
            }

            if (isset($response['description']) === true)
            {
                unset($response['description']);
            }

            $this->trace->info(TraceCode::MDR_ADJUSTMENT_CALCULATION_COMPLETE, $response);
        }
        catch (Exception\BaseException $e)
        {
            $response['success'] = false;

            $response['http_status_code'] = $e->getError()->getHttpStatusCode();

            $response['error'] =  [
                'code'        => $e->getError(),
                'description' => $e->getMessage(),
            ];

            $this->trace->info(TraceCode::SERVER_ERROR_MDR_ADJUSTMENT_CALCULATION_FAILED, $response);
        }
        catch (\Throwable $e)
        {
            $response['success'] = false;

            $response['error'] =  [
                'code'        => 'Server error',
                'description' => $e->getMessage(),
                ];

            $response['http_status_code'] =   500;

            $this->trace->info(TraceCode::SERVER_ERROR_MDR_ADJUSTMENT_CALCULATION_FAILED, $response);

        }

        return $response;
    }
}

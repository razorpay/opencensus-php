<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund as PaymentRefund;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Creates a bank account entity, but doesn't save. Save is done later. Creation is needed
     * before because we need validations and generations to run for further processing.
     *
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input)
    {
        // This method does not save to DB. It should not save to DB,
        // because it is used to validate-and-modify the input received
        // in the notify request. We only create a bank_transfer obj here.
        $bankTransfer = (new Entity)->build($input);

        return $bankTransfer;
    }

    /**
     * Creates bank transfer and calls processor with it.
     * Implements mutex lock to avoid race conditions.
     * Catches validationExceptions to stop unnecessary retries.
     *
     * @param array $input
     *
     * @return bool
     */
    public function process(array $input, string $provider = null)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESSING,
            $input
        );

        $processor = new Processor($provider);

        try
        {
            $bankTransfer = $this->create($input);

            $this->mutex->acquireAndRelease(
                $input[Entity::PAYEE_ACCOUNT],
                function() use ($processor, $bankTransfer)
                {
                    $processor->process($bankTransfer);
                },
                60,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

            $valid = true;
        }
        catch (Exception\BadRequestValidationFailureException $ex)
        {
            // Returning anything other than a 200 causes Kotak to retry here.
            //
            // However, validation failures are due to Kotak sending the request
            // in wrong format, or (more frequently) the wrong request altogether.
            // So retrying doesn't help us, and will cause unnecessary errors.
            // Best to trace, and return false, to stop the request.
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $input);

            $valid = false;
        }

        return $valid;
    }

    /**
     * Shell method, real refund logic is in the Refund helper class.
     *
     * @param array $data
     */
    public function refund(array $data)
    {
        (new Refund)->process($data);
    }

    /**
     * Kotak has a second route that it hits to notify us of a bank transfer payment. It was useful
     * when these APIs were being planned, but serves no real purpose now. To not lose the info,
     * all we do here is validate input, find the bank transfer and marked it as 'notified'.
     *
     * @param array $input
     *
     * @return bool
     */
    public function notify(array $input)
    {
        // Bank Transfer core does not save to DB in this step.
        // This is effectively just a modify-and-validate.
        $this->create($input);

        $bankTransfer = $this->repo->bank_transfer->findByUtr($input[Entity::REQ_UTR]);

        if ($bankTransfer !== null)
        {
            $this->notifyIfApplicable($bankTransfer);
        }
        else
        {
            $this->trace->error(
                TraceCode::BANK_TRANSFER_UNEXPECTED_NOTIFY,
                [
                    'input' => $input,
                ]
            );
        }

        return true;
    }

    /**
     * Marks the bank transfer as notified.
     *
     * @param Entity $bankTransfer
     */
    protected function notifyIfApplicable(Entity $bankTransfer)
    {
        if ($bankTransfer->isNotified() === false)
        {
            $bankTransfer->setNotified(true);

            $this->repo->saveOrFail($bankTransfer);
        }
    }

    /**
     * Processes refund retries, but skips those
     * with fund_transfer_attempt already created.
     *
     * @param array $input
     *
     * @return array
     */
    public function retryBankTransferRefund(array $input)
    {
        $refunds = $this->getRefundsToRetry($input);

        $this->trace->info(
            TraceCode::REFUND_RETRY_INITIATED,
            [
                'input'      => $input,
                'refund_ids' => $refunds->getIds()
            ]);

        $status  = [];
        $success = 0;
        $failure = 0;

        foreach ($refunds as $refund)
        {
            //
            // If refund was marked as failed after creating a fund
            // transfer attempt then that means this failure was result
            // of payout file recon. We aren't handling these just now.
            //
            // Reusing the same route for manual retries. If refund is being
            // retried manually, then we don't check for this condition.
            //
            if (($this->isManualRetry($refund, $input) === false) and
                ($refund->fundTransferAttempts->isNotEmpty() === true))
            {
                $this->trace->info(
                    TraceCode::REFUND_RETRY_SKIPPED,
                    [
                        'refund_id'                 => $refund->getPublicId(),
                        'fund_transfer_attempt_ids' => $refund->fundTransferAttempts->getIds(),
                    ]);

                continue;
            }

            try
            {
                $processor = $this->getNewProcessor($refund->merchant);

                $status[$refund->getPublicId()] = $processor->processRefundRetry($refund);

                $success++;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::INFO,
                    TraceCode::PAYMENT_VERIFY_REFUND_EXCEPTION,
                    [
                        'refund_id'       => $refund->getPublicId(),
                    ]);

                $failure++;
            }
        }

        return [
            'successful'    => $success,
            'failure'       => $failure,
            'status'        => $status,
        ];
    }

    /**
     * If ids were given in input, this is a manual retry.
     * We skip certain checks in this case.
     *
     * @param PaymentRefund\Entity $refund
     * @param array                $input
     *
     * @return bool
     */
    protected function isManualRetry(PaymentRefund\Entity $refund, array $input): bool
    {
        if ((isset($input['ids']) === true) and
            (in_array($refund->getPublicId(), $input['ids'], true) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * New processor instance for retrying refund
     *
     * @param $merchant
     *
     * @return Payment\Processor\Processor
     */
    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }

    /**
     * Fetches failed refunds to retry, or takes from input
     *
     * @param array $input
     *
     * @return mixed
     */
    protected function getRefundsToRetry(array $input)
    {
        if (isset($input['ids']) === true)
        {
            $refunds = $this->repo
                            ->refund
                            ->findManyByPublicIds($input['ids']);
        }
        else
        {
            $method = Payment\Method::BANK_TRANSFER;

            $refunds = $this->repo
                            ->refund
                            ->fetchFailedRefundsByMethod($method);
        }

        return $refunds;
    }
}

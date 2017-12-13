<?php

namespace RZP\Models\BankTransfer;

use Config;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\BankAccount;
use RZP\Models\Payment\Refund as PaymentRefund;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    const NRE_FAILURE_MESSAGES = [
        'neft-return credit to nri account',
        'imps-rtn-nre account',
    ];

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
        catch (\Throwable $ex)
        {
            $this->alertException($ex, $input);

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
     * Trace to splunk, and also send an alert to Slack.
     *
     * @param  \Throwable $ex
     * @param  array      $input
     */
    protected function alertException(\Throwable $ex, array $input)
    {
        //
        // Empty request is not really actionable, trace info and skip Slack
        //
        if (empty($input) === true)
        {
            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESSING_FAILED,
                [
                    'input' => $input
                ]);

            return;
        }

        // Any non-trivial (non-empty request) exception is critical, as
        // bank transfers are never supposed to fail. Trace accordingly.
        $this->trace->traceException(
            $ex, Trace::CRITICAL, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $input);

        $this->app['slack']->queue(
            TraceCode::BANK_TRANSFER_PROCESSING_FAILED,
            array_merge($input, ['message' => $ex->getMessage()]),
            [
                'channel'  => Config::get('slack.channels.virtual_accounts_log'),
                'username' => 'Scrooge',
                'icon'     => ':x:'
            ]
        );
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

        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByUtrAndPayeeIfsc(
                                $input[Entity::REQ_UTR],
                                $input[Entity::PAYEE_IFSC]);

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
            if ($this->skipRefund($refund) === true)
            {
                $this->trace->info(
                    TraceCode::REFUND_RETRY_SKIPPED,
                    [
                        'refund_id'     => $refund->getPublicId(),
                        'refund_status' => $refund->getStatus(),
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

    protected function skipRefund(PaymentRefund\Entity $refund)
    {
        if ($refund->isStatusFailed() === false)
        {
            return true;
        }

        $latestAttempt = $refund->fundTransferAttempts->last();

        if (($latestAttempt !== null) and
            ($this->isRefundToNreAccount($latestAttempt) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * If the last attempt failed with one of these messages, we
     * can consider it a hard bounce and not make more attempts.
     *
     * @return boolean
     */
    protected function isRefundToNreAccount($latestAttempt)
    {
        $msg = strtolower($latestAttempt->getRemarks());

        if (in_array($msg, self::NRE_FAILURE_MESSAGES, true) === true)
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

    public function editPayerBankAccount(Entity $bankTransfer, array $input)
    {
        $payerBankAccount = $bankTransfer->payerBankAccount;

        if ($payerBankAccount === null)
        {
            $payerBankAccount = $this->createPayerBankAccount($bankTransfer, $input);

            $bankTransfer->payerBankAccount()->associate($payerBankAccount);
        }
        else
        {
            $payerBankAccount = $payerBankAccount->edit($input, 'editVirtualBankAccount');
        }

        $this->repo->saveOrFail($payerBankAccount);

        $this->repo->saveOrFail($bankTransfer);

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PAYER_BANK_ACCOUNT_EDITED,
            [
                'bank_account' => $payerBankAccount->toArrayPublic(),
                'input'        => $input,
            ]);

        return $bankTransfer;
    }

    protected function createPayerBankAccount(Entity $bankTransfer, array $input)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = PayerBankAccount::getBankAccountInput($bankTransfer, $input);

        $bankAccount = $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($bankTransfer->merchant);

        $bankAccount->associateVirtualAccount($bankTransfer->virtualAccount);

        return $bankAccount;
    }
}

<?php

namespace RZP\Reconciliator;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Reconciliator\RequestProcessor;

class Service extends Base\Service
{
    /**
     * List of gateways where we are doing recon processing via batch.
     */
    const BATCH_RECON_GATEWAYS = [
        RequestProcessor\Base::AXIS,
        RequestProcessor\Base::HDFC,
        RequestProcessor\Base::ATOM,
        RequestProcessor\Base::KOTAK,
        RequestProcessor\Base::HITACHI,
        RequestProcessor\Base::CARD_FSS,
        RequestProcessor\Base::JIOMONEY,
        RequestProcessor\Base::BILLDESK,
        RequestProcessor\Base::UPI_ICICI,
        RequestProcessor\Base::OLAMONEY,
        RequestProcessor\Base::MOBIKWIK,
        RequestProcessor\Base::FIRST_DATA,
        RequestProcessor\Base::FREECHARGE,
        RequestProcessor\Base::NETBANKING_CSB,
        RequestProcessor\Base::NETBANKING_BOB,
        RequestProcessor\Base::NETBANKING_OBC,
        RequestProcessor\Base::NETBANKING_RBL,
        RequestProcessor\Base::NETBANKING_AXIS,
        RequestProcessor\Base::NETBANKING_ICICI,
        RequestProcessor\Base::NETBANKING_FEDERAL,
        RequestProcessor\Base::NETBANKING_HDFC,
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK,
        RequestProcessor\Base::VIRTUAL_ACC_YESBANK,
        RequestProcessor\Base::NETBANKING_CORPORATION,
    ];

    /**
     * List of gateways where we skips the batch summary slack post
     */
    const BATCH_SUMMARY_SKIP_GATEWAYS = [
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK,
        RequestProcessor\Base::VIRTUAL_ACC_YESBANK,
    ];

    public function initiateReconciliationProcess(array $input)
    {
        $this->traceReconRequest($input);

        try
        {
            $summary = $this->processReconciliationRequest($input);
        }
        catch (\Throwable $e)
        {
            if ($this->isManualRequest($input) === true)
            {
                $this->trace->traceException(
                    $e, Trace::ERROR, TraceCode::RECON_ALERT);

                throw $e;
            }

            $this->trace->traceException(
                $e, Trace::DEBUG, TraceCode::RECON_ALERT);

            // We do not throw an exception as route is hit via Mailgun,
            // and Mailgun will attempt retrying, which we don't want.
            return [];
        }

        return $summary;
    }

    public function reconciliateCancelledTransactions($gateway)
    {
        if ($gateway !== Payment\Gateway::BILLDESK)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                'gateway',
                $gateway);
        }

        $transactions = $this->repo->transaction->getCancelledBilldeskTransactions();

        $transactionCore = new Transaction\Core;

        $successCount = $failureCount = 0;

        $failures = [];

        foreach ($transactions as $transaction)
        {
            $success = $transactionCore->updateReconciliationData($transaction);

            if ($success === true)
            {
                $successCount++;
            }
            else
            {
                $failures[] = $transaction->getId();
                $failureCount++;
            }
        }

        $data = [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failures'      => $failures,
        ];

        $this->trace->info(
            TraceCode::RECONCILE_CANCELLED_TRANSACTIONS,
            $data
        );

        return $data;
    }

    /**
     * Determines whether the reconciliation request is manual or
     * via MailGun and gets the files details accordingly.
     *
     * @param array $input The input received from the route.
     * @return array Summary of reconciliation
     * @throws Exception\ReconciliationException Raised when there are no
     *                                           files to reconcile.
     */
    protected function processReconciliationRequest(array $input)
    {
        $requestProcessor = $this->getRequestProcessor($input);

        //
        // Sets the gateway reconciliator object and
        // Gets all the file details from the input.
        //
        $reconDetails = $requestProcessor->process($input);

        // There must be at least one file. Otherwise, error.
        if (empty($reconDetails[RequestProcessor\Base::FILE_DETAILS]) === true)
        {
            throw new Exception\ReconciliationException(
                'File details are empty.');
        }

        $this->trace->info(
            TraceCode::RECON_FILE_DETAILS,
            $reconDetails[RequestProcessor\Base::FILE_DETAILS]);

        $gateway = $requestProcessor->getGateway();

        $gatewayReconciliator = $requestProcessor->getGatewayReconciliator();

        $orchestrator = new Orchestrator($gateway, $gatewayReconciliator);

        //
        // This is a temporary logic. Plan is to move all gateway reconciliation
        // to batch once it is stable
        //
        if (in_array($gateway, self::BATCH_RECON_GATEWAYS, true) === true)
        {
            return $orchestrator->orchestrateV2($reconDetails);
        }

        return $orchestrator->orchestrate($reconDetails);
    }

    /**
     * Request body, if sent via mail through Mailgun, is too large
     * to be parsed effectively on Splunk. So we unset the body params,
     * then trace everything else.
     * Other headers will be enough to identify the mail if needed.
     *
     * @param array $input Request body
     */
    protected function traceReconRequest(array $input)
    {
        unset($input['body-html']);
        unset($input['body-plain']);
        unset($input['stripped-html']);
        unset($input['stripped-text']);
        unset($input['message-headers']);

        $this->trace->info(
            TraceCode::RECON_REQUEST,
            $input);
    }

    /**
     * Initializes the request processor to be used to handle the request
     * based on the source of the request i.e manual | mailgun
     *
     * @param  array                        $input
     * @return RequestProcessor\Base
     */
    protected function getRequestProcessor(array $input): RequestProcessor\Base
    {
        // Checks if it's manual call or mailgun call
        if ($this->isManualRequest($input) === true)
        {
            $requestProcessor = new RequestProcessor\Manual;
        }
        else
        {
            $requestProcessor = new RequestProcessor\Mailgun;
        }

        return $requestProcessor;
    }

    /**
     * Checks if request is manual or via Mailgun.
     *
     * @param array $input The input received from the route.
     * @return boolean Flag to indicate manual request
     */
    protected function isManualRequest(array $input)
    {
        if ((isset($input['manual']) === true) and ($input['manual'] === '1'))
        {
            return true;
        }

        return false;
    }

}

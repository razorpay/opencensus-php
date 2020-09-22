<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use App;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\RequestProcessor;
use RZP\Jobs\NbPlusRecon\NetbankingRecon;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Services\NbPlus\Netbanking as NetbankingService;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class NetbankingServiceRecon extends PaymentReconciliate
{
    //
    // These are the attributes required from the netbanking entity on nbplus service
    //
    const NETBANKING_ATTRIBUTES = [
        NetbankingService::GATEWAY_TRANSACTION_ID,
        NetbankingService::BANK_TRANSACTION_ID,
        NetbankingService::BANK_ACCOUNT_NUMBER,
        NetbankingService::ADDITIONAL_DATA
    ];

    //
    // These are the fields that need to be compared from the recon file with the data from nbplus service
    // if the field is present in the recon file
    //
    const RECON_PARAMS = [
        NetbankingService::GATEWAY_TRANSACTION_ID,
        NetbankingService::BANK_TRANSACTION_ID,
        NetbankingService::BANK_ACCOUNT_NUMBER,
        NetbankingService::CREDIT_ACCOUNT_NUMBER,
        NetbankingService::CUSTOMER_ID
    ];

    protected function updateAndFetchGatewayPayment()
    {
        if ($this->payment->isRoutedThroughNbPlus() === true)
        {
            return null;
        }

        return parent::updateAndFetchGatewayPayment();
    }

    protected function persistReconciliationData($rowDetails, $row)
    {
        parent::persistReconciliationData($rowDetails, $row);

        if ($this->payment->isRoutedThroughNbPlus() === true)
        {
            $this->nbPlusPaymentServiceDispatch($rowDetails);
        }
    }

    protected function tryAuthorizeFailedPayment($row)
    {
        //
        // If a gateway has implemented force authorization,
        // always use that, instead of verify. There's no
        // need for running verify if force authorization is present.
        //
        // Disabling force auth if request from mailgun because of
        // vulnerability mentioned in SBB-330.
        if (($this->allowForceAuthorization === true) and
            ($this->source !== RequestProcessor\Base::MAILGUN))
        {
            return $this->handleForceAuthorization($row);
        }
        else
        {
            return $this->handleVerifyPaymentWithGatewayData($row);
        }
    }

    protected function handleVerifyPaymentWithGatewayData($row)
    {
        $paymentService = new Payment\Service;

        $gatewayData = [
            NetbankingService::GATEWAY_TRANSACTION_ID =>  $this->getGatewayTransactionId($row),
            NetbankingService::BANK_TRANSACTION_ID    =>  $this->getReferenceNumber($row)
        ];

        try
        {
            // Try to make it authorized
            $verifyResponse = $paymentService->verifyPaymentWithGatewayData($this->payment, $gatewayData);
        }
        catch(\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                    'message'    => 'Verification/Authorization threw an exception. -> ' . $ex->getMessage(),
                    'payment_id' => $this->payment->getId(),
                    'amount'     => $this->payment->getAmount(),
                    'gateway'    => $this->gateway
                ]);

            $this->trace->traceException($ex);

            return false;
        }

        switch($verifyResponse)
        {
            case VerifyResult::AUTHORIZED:

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'message'    => 'Verify returned authorized.',
                        'payment_id' => $this->payment->getId(),
                        'gateway'    => $this->gateway
                    ]
                );

                $authorizeSuccess = $this->handleVerifyAuthorized();

                break;

            case VerifyResult::SUCCESS:

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                        'message'    => 'Verify returned failed. Payment is still in failed state.',
                        'payment_id' => $this->payment->getId(),
                        'amount'     => $this->payment->getAmount(),
                        'gateway'    => $this->gateway
                    ]);

                $authorizeSuccess = false;

                break;

            case VerifyResult::ERROR:
            case VerifyResult::TIMEOUT:
            case VerifyResult::UNKNOWN:

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_FAILED_VERIFY,
                        'message'       => 'Verify command failed or unable to recognize the response.',
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getAmount(),
                        'verify_status' => $verifyResponse,
                        'gateway'       => $this->gateway
                    ]);

                $authorizeSuccess = false;

                break;

            // If payment is already being authorized by other thread
            // or any unexpected gateway error comes, null is returned. No slack
            // message in this case, happens for all the payments in the file.
            default:

                $this->trace->info(
                    TraceCode::RECON_FAILED_VERIFY,
                    [
                        'message'       => 'Verify command failed or unable to recognize the response.',
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getAmount(),
                        'gateway'       => $this->gateway,
                        'verify_status' => $verifyResponse,
                    ]);

                $authorizeSuccess = false;

                break;
        }

        return $authorizeSuccess;
    }

    protected function nbPlusPaymentServiceDispatch(array $rowDetails)
    {
        $debitAccountNumber = null;

        $creditAccountNumber = null;

        $customerId = null;

        if (isset($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true)
        {
            $debitAccountNumber  = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][BaseReconciliate::ACCOUNT_NUMBER] ?? null;
            $creditAccountNumber = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][BaseReconciliate::CREDIT_ACCOUNT_NUMBER] ?? null;
        }

        if (isset($rowDetails[BaseReconciliate::CUSTOMER_DETAILS]) === true)
        {
            $customerId  = $rowDetails[BaseReconciliate::CUSTOMER_DETAILS][Base\Reconciliate::CUSTOMER_ID] ?? null;
        }

        $data = [
            'payment_id' => $this->payment->getId(),
            'recon_file_data'     => [
                NetbankingService::GATEWAY_TRANSACTION_ID => $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID] ?? null,
                NetbankingService::BANK_TRANSACTION_ID    => $rowDetails[BaseReconciliate::REFERENCE_NUMBER] ?? null,
                NetbankingService::BANK_ACCOUNT_NUMBER    => $debitAccountNumber,
                NetbankingService::CREDIT_ACCOUNT_NUMBER  => $creditAccountNumber,
                NetbankingService::CUSTOMER_ID            => $customerId
            ],
            'mode'       => $this->mode,
            'gateway'    => $this->gateway,
            'batch_id'   => $this->batchId,
        ];

        NetbankingRecon::dispatch($data);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'  => Base\InfoCode::RECON_NBPLUS_JOB_DISPATCH,
                'payment_id' => $this->payment->getId(),
            ]
        );
    }
}

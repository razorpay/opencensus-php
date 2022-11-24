<?php

namespace RZP\Reconciliator\Base\SubReconciliator\NbPlus;

use App;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Reconciliator\RequestProcessor;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Services\NbPlus\Wallet as WalletService;
use RZP\Services\NbPlus\Paylater as PaylaterService;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Services\NbPlus\Netbanking as NetbankingService;

class NbPlusServiceRecon extends SubReconciliator\PaymentReconciliate
{
    use AppReconTrait;
    use PaylaterReconTrait;
    use EmandateReconTrait;
    use NetbankingReconTrait;
    use CardlessEmiReconTrait;
    use WalletReconTrait;
    //
    // These are the attributes required from the netbanking entity on nbplus service
    //
    const NETBANKING_ATTRIBUTES = [
        NetbankingService::GATEWAY_TRANSACTION_ID,
        NetbankingService::BANK_TRANSACTION_ID,
        NetbankingService::BANK_ACCOUNT_NUMBER,
        NetbankingService::ADDITIONAL_DATA
    ];

    const PAYLATER_ATTRIBUTES = [
        PaylaterService::GATEWAY_REFERENCE_NUMBER,
        PaylaterService::PROVIDER_REFERENCE_NUMBER,
    ];

    const WALLET_ATTRIBUTES = [
        WalletService::WALLET_TRANSACTION_ID,
        WalletService::ADDITIONAL_DATA
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
        NetbankingService::CUSTOMER_ID,
        NetbankingService::VERIFICATION_ID,
    ];

    protected function updateAndFetchGatewayPayment()
    {
        if ($this->payment->isRoutedThroughNbPlus() === true)
        {
            return null;
        }

        return parent::updateAndFetchGatewayPayment();
    }

    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
       parent::runPreReconciledAtCheckRecon($rowDetails);

        if ($this->payment->isRoutedThroughNbPlus() === true)
        {
            switch ($this->payment->getMethod())
            {
                case Payment\Method::NETBANKING;
                    $this->nbPlusPaymentServiceNetbankingDispatch($rowDetails);
                    break;
                case Payment\Method::WALLET:
                    $this->nbPlusPaymentServiceWalletDispatch($rowDetails);
                    break;
                case Payment\Method::CARDLESS_EMI;
                    $this->nbPlusPaymentServiceCardlessEmiDispatch($rowDetails);
                    break;
                case Payment\Method::APP:
                    $this->nbPlusPaymentServiceAppMethodDispatch($rowDetails);
                    break;
                case Payment\Method::EMANDATE;
                    $this->nbPlusPaymentServiceEmandateDispatch($rowDetails);
                    break;
                case Payment\Method::PAYLATER:
                    $this->nbPlusPaymentServicePaylaterDispatch($rowDetails);
                    break;
            }
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

    protected function handleForceAuthorization(array $row)
    {
        $authResponse = parent::handleForceAuthorization($row);

        if (($authResponse === true) and ($this->payment->isExternal() === true))
        {
            (new Transaction\Core)->dispatchUpdatedTransactionToCPS($this->paymentTransaction, $this->payment);
        }

        return $authResponse;
    }
}

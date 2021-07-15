<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use RZP\Models\BankTransferRequest;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\UnexpectedPaymentReason;

class Core extends BankTransfer\Core
{
    public function processBankTransfer(BankTransferRequest\Entity $bankTransferRequest)
    {
        $bankTransferInput = $bankTransferRequest->getBankTransferProcessInput();
        $provider          = $bankTransferRequest->getGateway();
        $paymentSuccess    = false;
        $bankTransfer      = null;
        $errorMessage      = null;

        try
        {
            $bankTransfer = $this->processBankTransferRequest($bankTransferRequest, $bankTransferInput, $provider);

            $paymentSuccess = (($bankTransfer !== null) and ($bankTransfer->getUnexpectedReason() === null));
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::BANK_TRANSFER_PROCESSING_FAILED, $bankTransferRequest->toArrayTrace());

            $errorMessage = $ex->getMessage();

            if (($bankTransfer === null) or ($bankTransfer->getUnexpectedReason() === null))
            {
                switch ($errorMessage)
                {
                    case TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR:
                        return StatusCode::ALREADY_PROCESSED;

                    case TraceCode::REFUND_OR_CAPTURE_PAYMENT_FAILED:
                         $paymentSuccess = true;
                         break;

                    default:
                        return $errorMessage;
                }
            }
            else
            {
                return $bankTransfer->getUnexpectedReason();
            }
        }
        finally
        {
            $this->postProcessBankTransferUpdation($bankTransfer, $bankTransferInput, $bankTransferRequest, $errorMessage, $paymentSuccess);
        }

        return $bankTransfer->getUnexpectedReason() === null ? StatusCode::SUCCESS : $bankTransfer->getUnexpectedReason();
    }

}

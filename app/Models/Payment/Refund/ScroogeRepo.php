<?php

namespace RZP\Models\Payment\Refund;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;

trait ScroogeRepo
{
    public function findForPaymentId(string $paymentId)
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findForPaymentId',
                    'payment_id'  => $paymentId,
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundForPayment($paymentId);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'payment_id' => $paymentId,
                ]);

        }

        return $this->findForPaymentIdFromAPI($paymentId);
    }
}



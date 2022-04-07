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
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => 'findForPaymentId',
                    'payment_id'      => $paymentId,
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and
                (EntityConstants::validateExternalRepoEntity($this->entityName) === true) and
                ($forceLoadFromApi === false))
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



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
                    'method_name'     => __FUNCTION__,
                    'payment_id'      => $paymentId,
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundForPayment($paymentId);
                $apiResponse     = $this->findForPaymentIdFromAPI($paymentId);

                (new Service())->compareRefundsAndLogDifference(
                    $apiResponse->toArray(), $scroogeResponse->toArray(), ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }
                return $apiResponse;
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



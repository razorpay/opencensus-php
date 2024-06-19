<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;
use stdClass;

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

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = $this->findForPaymentIdFromAPI($paymentId);

                (new Service())->compareRefundsAndLogDifference(
                    $apiResponse->toArray(), $scroogeResponse->toArray(), ['method_name' => __FUNCTION__]);

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

    public function findForPaymentIdAndAmount(string $paymentId, int $amount)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name'     => __FUNCTION__,
                    'payment_id'      => $paymentId,
                    'route_name'      => $routeName,
                ]);

            $scroogeResponse = $this->fetchRefundForPaymentIdAndAmount($paymentId, $amount);

            //Razorx check
            if ($this->isScroogeReadMigration() == true)
            {
                return $scroogeResponse->all();
            }

            $apiResponse     = $this->findForPaymentAndAmountFromApi($paymentId,$amount);

            (new Service())->compareRefundsAndLogDifference(
                $apiResponse->toArray(), $scroogeResponse->all(), ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION]);

            return $apiResponse;

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'payment_id' => $paymentId,
                    'amount'    =>  $amount
                ]);
        }

        return $this->findForPaymentAndAmountFromApi($paymentId, $amount);
    }

    public function findRefundByIds($refundIds)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name'     => __FUNCTION__,
                    'route_name'      => $routeName,
                ]);

            $scroogeResponse = $this->fetchRefundByIds($refundIds);
            $extractedResponse = [];

            foreach ($scroogeResponse->all() as $entry) {
                // Extract the desired values
                $extractedResponse[] = [
                    'payment_id' => $entry->payment_id,
                    'reference1' => $entry->reference1,
                    'id' => $entry->id,
                ];
            }
            $scroogeResponse=$extractedResponse;

            //Razorx check
            if ($this->isScroogeReadMigration() == true)
            {
                return $scroogeResponse;
            }

            $apiResponse     = $this->fetchRefundByRefundIdsFromApi($refundIds);

            (new Service())->compareRefundsAndLogDifference(
                    $apiResponse->toArray(), $scroogeResponse, ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION]);

            return $apiResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'refund_ids' => $refundIds
                ]);
        }
        return $this->fetchRefundByRefundIdsFromApi($refundIds);
    }

    public function findRefundById($refundId , $connectionType,$columns = array('*'))
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name'     => __FUNCTION__,
                    'route_name'      => $routeName,
                ]);

            $scroogeResponse = $this->fetchRefundByIds($refundId);
            if ($this->isScroogeReadMigrationForFetchById() == true)
            {
                return $scroogeResponse->all()[0];
            }

            $apiResponse     = parent::find($refundId,$columns,$connectionType);

            (new Service())->compareRefundEntitesAndLogDifference(
                $apiResponse, $scroogeResponse->all()[0], false, ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_FOR_FETCH_BY_ID]);

            return $apiResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'refund_id' => $refundId
                ]);
        }
        return parent::find($refundId,$columns,$connectionType);
    }

    public function findForPaymentByReceiptAndMerchant(string $receipt, string $merchantId)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name'     => __FUNCTION__,
                    'payment_id'      => $receipt,
                    'merchant_id'     => $merchantId,
                    'route_name'      => $routeName,
                ]);

            $scroogeResponse = $this->fetchRefundByReceiptAndMerchantId($receipt, $merchantId);

            if ($this->isScroogeReadMigration() == true)
            {
                return $scroogeResponse->all();
            }

            $apiResponse     = $this->findByReceiptAndMerchantFromApi($receipt, $merchantId);

            (new Service())->compareRefundEntitesAndLogDifference(
                $apiResponse, $scroogeResponse->all()[0],false, ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION]);

            return $apiResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'receipt' => $receipt,
                    'merchant_id'=> $merchantId,
                ]);
        }

        return $this->findByReceiptAndMerchantFromApi($receipt, $merchantId);
    }

    public function fetchRefundByReversalIdAndMerchant(string $reversalId, string $accountId, array $relations = [])
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name' => __FUNCTION__,
                    'reversal_id' => $reversalId,
                    'merchant_id' => $accountId,
                    'route_name' => $routeName
                ]);

            $refund = $this->fetchRefundByReversalIdAndMerchantId($reversalId, $accountId)->all()[0];

            $reversalColumns = $this->repo->reversal->dbColumn('*');

            $reversalEntityId = $this->repo->reversal->dbColumn(ReversalEntity::ID);

            $reversal = $this->repo->reversal->newQuery()
                ->select($reversalColumns)
                ->where($reversalEntityId, $refund->getReversalId())
                ->firstOrFailPublic();

            $refund->setRelation(Entity::REVERSAL, $reversal);

            if ($this->isScroogeReadMigrationForReversal() == true) {
                return $refund;
            }

            $apiResponse = $this->findByReversalIdAndMerchantFromApi($reversalId, $accountId, $relations);

            (new Service())->compareRefundEntitesAndLogDifference(
                $apiResponse, $refund,false, ['method_name' => __FUNCTION__, 'type' => TraceCode::SCROOGE_MISC_QUERIES_MIGRATION]);

            return $apiResponse;

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'receipt' => $reversalId,
                    'merchant_id'=> $accountId,
                ]);
        }

        return $this->findByReversalIdAndMerchantFromApi($reversalId, $accountId, $relations);
    }

    public function fetchRefundByPaymentAndBaseAmount(string $paymentId, $baseAmount)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();


            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name'     => __FUNCTION__,
                    'payment_id'      => $paymentId,
                    'base_amount'     => $baseAmount,
                    'route_name'      => $routeName,
                ]);


            $scroogeResponse = $this->fetchRefundByPaymentAndBaseAmountFromScrooge($paymentId, $baseAmount);

            if ($this->isScroogeReadMigration() == true)
            {
                return $scroogeResponse->all();
            }

            $apiResponse     = $this->findForPaymentAndBaseAmountFromApi($paymentId, $baseAmount);

            (new Service())->compareRefundsAndLogDifference(
                $apiResponse->toArray(), $scroogeResponse->all(), ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION]);

            return $apiResponse;

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'payment_id' => $paymentId,
                    'base_amount'=> $baseAmount,
                ]);
        }

        return $this->findForPaymentAndBaseAmountFromApi($paymentId, $baseAmount);
    }

    public function fetchFirstRefundByPayment(string $paymentId)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();


            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
                [
                    'method_name'     => __FUNCTION__,
                    'payment_id'      => $paymentId,
                    'route_name'      => $routeName,
                ]);


            $scroogeResponse = $this->fetchFirstRefundByPaymentFromScrooge($paymentId);

            if ($this->isScroogeReadMigration() == true)
            {
                return $scroogeResponse;
            }

            $apiResponse     = $this->fetchFirstForPaymentIdFromApi($paymentId);


            (new Service())->compareRefundEntitesAndLogDifference(
                $apiResponse, $scroogeResponse,false, ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION]);

            return $apiResponse;

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'payment_id' => $paymentId
                ]);
        }

        return $this->fetchFirstForPaymentIdFromApi($paymentId);
    }

    public function compareAndFindByPaymentIdAndReference3FromScrooge(string $paymentId, int $seqNo)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2,
                [
                    'method_name'     => __FUNCTION__,
                    'payment_id'      => $paymentId,
                    'sequence_no'     => $seqNo,
                    'route_name'      => $routeName,
                ]);

            $scroogeResponse = $this->findByPaymentIdAndReference3FromScrooge($paymentId, $seqNo);

            if ($this->isScroogeReadMigration2() == true)
            {
                return $scroogeResponse->all()[0];
            }

            $apiResponse     = $this->findByPaymentIdAndReference3FromApi($paymentId, $seqNo);

            (new Service())->compareRefundEntitesAndLogDifference(
                $apiResponse, $scroogeResponse->all()[0],false, ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2]);

            return $apiResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'payment_id' => $paymentId,
                    'sequence_no'=> $seqNo,
                ]);
        }

        return $this->findByPaymentIdAndReference3FromApi($paymentId, $seqNo);
    }

    public function compareAndFetchRefundsForGatewaysBetweenTimestampsFromTidb($type, $gatewayCodes, $from, $to, $gateway)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2,
                [
                    'method_name' => __FUNCTION__,
                    'type' => $type,
                    'gateway_codes' => $gatewayCodes,
                    'from' => $from,
                    'to' => $to,
                    'gateway' => $gateway,
                    'route_name' => $routeName,
                ]);

            $tidbResponse = $this->repo->refund_tidb->fetchRefundsForGatewaysBetweenTimestampsFromTidb($type, $gatewayCodes, $from, $to, $gateway);

            if ($this->isScroogeReadMigration2() == true)
            {
                return $tidbResponse;
            }

            $apiResponse     = $this->fetchRefundsForGatewaysBetweenTimestampsFromApi($type, $gatewayCodes, $from, $to, $gateway);

            (new Service())->compareRefundsAndLogDifference(
                $apiResponse->all(), $tidbResponse->all(), ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2]);

            return $apiResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TIDB_REFUND_ENTITY_FETCH_FAILURE,
                [
                    'type' => $type,
                    'gateway_codes' => $gatewayCodes,
                    'from' => $from,
                    'to' => $to,
                    'gateway' => $gateway,
                ]);
        }

        return $this->fetchRefundsForGatewaysBetweenTimestampsFromApi($type, $gatewayCodes, $from, $to, $gateway);
    }

//    public function compareAndFetchFailedRefundsForGatewayBetweenTimestampsFromTidb($from, $to, $gateway)
//    {
//        $this->entityName = $this->entity;
//
//        try
//        {
//            $routeName = $this->route->getCurrentRouteName();
//
//            $this->trace->info(
//                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION,
//                [
//                    'method_name' => __FUNCTION__,
//                    'from' => $from,
//                    'to' => $to,
//                    'gateway' => $gateway,
//                    'route_name' => $routeName,
//                ]);
//
//            $tidbResponse = $this->repo->refund_tidb->fetchFailedRefundsForGatewayBetweenTimestampsFromTidb($from, $to, $gateway);
//            $apiResponse     = $this->fetchFailedRefundsForGatewayBetweenTimestampsFromApi($from, $to, $gateway);
//
//            $this->app['trace']->info(TraceCode::NODAL_BEN_ADD_REQUEST, [
//                '$tidbResponse' => $tidbResponse,
//                '$apiResponse' => $apiResponse,
//                'route'        => $this->route
//            ]);
//
//            (new Service())->compareRefundsAndLogDifference(
//                $apiResponse->toArray(), $tidbResponse->toArray(), ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2]);
//
//            if ($this->isScroogeReadMigration2() == true)
//            {
//                return $tidbResponse;
//            }
//            return $apiResponse;
//        }
//        catch (\Throwable $e)
//        {
//            $this->trace->traceException(
//                $e,
//                Trace::ERROR,
//                TraceCode::TIDB_REFUND_ENTITY_FETCH_FAILURE,
//                [
//                    'from' => $from,
//                    'to' => $to,
//                    'gateway' => $gateway,
//                ]);
//        }
//
//        return $this->fetchFailedRefundsForGatewayBetweenTimestampsFromApi($from, $to, $gateway);
//    }


    public function compareAndFetchIrctcDeltaRefundsFromTidb(string $merchantId, int $from, int $to)
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2,
                [
                    'method_name' => __FUNCTION__,
                    '$merchantId'=> $merchantId,
                    '$from' => $from,
                    '$to' => $to,
                ]);

            $tidbResponse = $this->repo->refund_tidb->fetchIrctcDeltaRefundsFromTidb($merchantId,$from,$to);

            if ($this->isScroogeReadMigration2() == true)
            {
                return $tidbResponse;
            }

            $apiResponse     = $this->fetchIrctcDeltaRefundsFromApi($merchantId,$from,$to);

            (new Service())->compareRefundsAndLogDifference(
                $apiResponse->all(), $tidbResponse->all(), ['method_name' => __FUNCTION__, 'type'=> TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_2]);

            return $apiResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TIDB_REFUND_ENTITY_FETCH_FAILURE,
                [
                    'merchant_id' => $merchantId,
                    'from' => $from,
                    'to' => $to,
                ]);
        }

        return $this->fetchIrctcDeltaRefundsFromApi($merchantId,$from,$to);
    }
}



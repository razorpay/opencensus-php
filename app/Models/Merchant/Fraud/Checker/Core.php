<?php

namespace RZP\Models\Merchant\Fraud\Checker;

use RZP\Models\Base;
use RZP\Jobs\NotifyRas;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function milestoneCron(string $category): array
    {
        $this->trace->info(TraceCode::MERCHANT_FRAUD_CHECKER_MILESTONE_CRON_STARTED);

        $eventType = Constants::RAS_EVENT_TYPE_MILESTONE_CHECKER;

        if (Constants::isValidCategory($category, $eventType) === false)
        {
            $this->trace->error(
                TraceCode::MERCHANT_FRAUD_CHECKER_MILESTONE_INVALID_CATEGORY,
                [
                    'category' => $category,
                ]
            );

            $this->trace->info(TraceCode::MERCHANT_FRAUD_CHECKER_MILESTONE_CRON_ENDED);

            return ['success' => false];
        }

        $response = $this->process($category, Constants::RAS_EVENT_TYPE_MILESTONE_CHECKER);

        $this->trace->info(TraceCode::MERCHANT_FRAUD_CHECKER_MILESTONE_CRON_ENDED);

        return $response;
    }

    private function process(string $category, string $eventType)
    {
        list($listOfMerchantIds, $hasError)  = $this->getMerchantIdsToProcess($category, $eventType);

        if ($hasError === true)
        {
            $this->trace->error(
                TraceCode::MERCHANT_FRAUD_CHECKER_PROCESSING_ERROR,
                [
                    'category'   => $category,
                    'event_type' => $eventType,
                ]
            );

            return ['success' => false];
        }

        if (empty($listOfMerchantIds) === true)
        {
            $this->trace->info(
                TraceCode::MERCHANT_FRAUD_CHECKER_NO_MERCHANTS_FOUND,
                [
                    'category'   => $category,
                    'event_type' => $eventType,
                ]
            );

            return ['success'         => true,
                    'merchants_found' => 0];
        }

        $merchantsProcessed = $this->sendAlerts($listOfMerchantIds, $category, $eventType);

        return ['success'             => true,
                'merchants_found'     => count($listOfMerchantIds),
                'merchants_processed' => $merchantsProcessed];
    }

    private function getMerchantIdsToProcess(string $category, string $eventType): array
    {
        $queryToExecute = Constants::getDruidQuery($category, $eventType);

        return $this->getMerchantListFromDruid($queryToExecute);
    }

    private function getMerchantListFromDruid(string $query): array
    {
        list($error, $res) = $this->app['druid.service']->getDataFromDruid(['query' => $query]);

        if (isset($error) === true)
        {
            return [null, true];
        }

        return [array_pluck($res, 'merchants_id'), false];
    }

    private function sendAlerts(array $listOfMerchantIds, string $category, string $eventType)
    {
        $merchantsProcessed = 0;

        foreach ($listOfMerchantIds as $merchantId)
        {
            $this->trace->info(TraceCode::MERCHANT_FRAUD_CHECKER_RAS_NOTIFICATION_INITIATED,
               [
                   'merchant_id' => $merchantId,
               ]
            );

            try
            {
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $rasAlertRequest = $this->constructRasAlertRequest($merchant, $category, $eventType);

                NotifyRas::dispatch($this->mode, $rasAlertRequest);

                $merchantsProcessed += 1;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::MERCHANT_FRAUD_CHECKER_RAS_NOTIFICATION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'category'    => $category,
                        'event_type'  => $eventType,
                    ]
                );
            }
        }

        return $merchantsProcessed;
    }

    private function constructRasAlertRequest(Merchant\Entity $merchant, string $category, string $eventType)
    {
        $merchantId = $merchant->getId();

        $entityType = Constants::getRasEntityType($category);

        $rasAlertRequest = [
            'merchant_id'     => $merchantId,
            'entity_type'     => $entityType,
            'entity_id'       => $merchantId,
            'category'        => $category,
            'source'          => Constants::RAS_SOURCE_API_SERVICE,
            'event_type'      => $eventType,
            'event_timestamp' => now()->timestamp,
            'data'            => null,
        ];

        return $rasAlertRequest;
    }
}

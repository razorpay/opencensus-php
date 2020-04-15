<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use App;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Payment\Entity as Payment;

class NetbankingBase extends Base
{
    const MAX_ATTEMPTS = 2;

    /**
     * Being used to paginate the fetch from nbplus
     * Number of gateway entities to be fetched in each call
     */
    const FETCH_ENTITY_COUNT = 1000;

    public function generateData(PublicCollection $claims)
    {
        $data           = [];

        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $paymentsGroupedByCps = $claims->groupBy('cps_route');

        if (isset($paymentsGroupedByCps[Payment::API]) === true)
        {
            $apiPayments = ($paymentsGroupedByCps[Payment::API])->pluck('id')->toArray();

            $apiGatewayEntities = $this->fetchGatewayEntities($apiPayments);

            $apiGatewayEntities = $apiGatewayEntities->keyBy('payment_id');

            $data = array_map(function($row) use ($apiGatewayEntities)
            {
                $paymentId = $row['payment']['id'];

                if (isset($apiGatewayEntities[$paymentId]) === true)
                {
                    $row['gateway'] = $apiGatewayEntities[$paymentId]->toArray();
                }

                return $row;
            }, $data);
        }

        if (isset($paymentsGroupedByCps[Payment::NB_PLUS_SERVICE]) === true)
        {
            $nbPlusPayments = ($paymentsGroupedByCps[Payment::NB_PLUS_SERVICE])->pluck('id')->toArray();

            list($nbPlusGatewayEntities, $fetchSuccess) = $this->fetchNbPlusGatewayEntities($nbPlusPayments);

            // Throwing an error in case of scrooge fetch failure
            if ($fetchSuccess === false)
            {
                throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'id' => $this->gatewayFile->getId(),
                    ]
                );
            }

            $data = array_map(function($row) use ($nbPlusGatewayEntities)
            {
                $paymentId = $row['payment']['id'];

                if (isset($nbPlusGatewayEntities[$paymentId]) === true)
                {
                    $row['gateway'] = $nbPlusGatewayEntities[$paymentId];
                }

                return $row;
            }, $data);
        }

        return $data;
    }

    protected function fetchNbPlusGatewayEntities($paymentIds)
    {
        $shouldFetchEntities = true;

        $start = 0;

        $fetchLimit = self::FETCH_ENTITY_COUNT;

        $gatewayData = [];

        $fetchSuccess = true;

        while ($shouldFetchEntities === true)
        {
            $requestPaymentIds = array_slice($paymentIds, $start, $fetchLimit);

            if ((count($requestPaymentIds) === 0) or ($fetchSuccess === false))
            {
                $shouldFetchEntities = false;
            }
            else
            {
                for ($i = 0; $i < self::MAX_ATTEMPTS; $i++)
                {
                    try
                    {
                        $request = [
                            'payment_ids'   => $requestPaymentIds,
                        ];

                        $response = App::getFacadeRoot()['nbplus.payments']->fetchNetbankingData($request);

                        $start += $fetchLimit;

                        $gatewayData = array_merge($gatewayData, $response['items']);

                        $fetchSuccess = true;

                        break;
                    }
                    catch (\Exception $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::GATEWAY_FILE_ERROR_GENERATING_DATA,
                            [
                                'input' => $request,
                                'id'    => $this->gatewayFile->getId(),
                            ]
                        );

                        $fetchSuccess = false;
                    }
                }
            }
        }

        return [$gatewayData, $fetchSuccess];
    }
}

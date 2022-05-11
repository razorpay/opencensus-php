<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use App;

use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Payment\Entity as Payment;

class NetbankingBase extends Base
{
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

        if ((isset($paymentsGroupedByCps[Payment::NB_PLUS_SERVICE]) === true) or
            (isset($paymentsGroupedByCps[Payment::NB_PLUS_SERVICE_PAYMENTS]) === true))
        {
            $nbPlusGatewayPayments = isset($paymentsGroupedByCps[Payment::NB_PLUS_SERVICE]) ?
                $paymentsGroupedByCps[Payment::NB_PLUS_SERVICE]->pluck('id')->toArray() : [];
            $nbPlusPayments = isset($paymentsGroupedByCps[Payment::NB_PLUS_SERVICE_PAYMENTS]) ?
                $paymentsGroupedByCps[Payment::NB_PLUS_SERVICE_PAYMENTS]->pluck('id')->toArray() : [];
            $ids = array_merge($nbPlusGatewayPayments, $nbPlusPayments);

            list($nbPlusGatewayEntities, $fetchSuccess) = $this->fetchNbPlusGatewayEntities($ids, Method::NETBANKING);

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
}

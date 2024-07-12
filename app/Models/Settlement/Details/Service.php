<?php

namespace RZP\Models\Settlement\Details;

use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Trace\TraceCode;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Base\UniqueIdEntity;


class Service extends Base\Service
{
    public function getSettlementDetails($id)
    {

        $experimentVariable = UniqueIdEntity::generateUniqueId();
        // shadow mode experiment
        $shadow = $this->app->razorx->getTreatment($experimentVariable,
            Settlement\Constants::RAZORX_SETL_GET_DETAILS_FROM_NSS_SHADOW,
            $this->mode
        );

        if ($shadow === Settlement\Constants::RAZORX_VARIANT_ON) {
            $nssResponse = app('settlements_dashboard')->settlementGetDetails($id);

            $experimentVariable = UniqueIdEntity::generateUniqueId();
            // reverse shadow mode experiment
            $reverseShadow = $this->app->razorx->getTreatment($experimentVariable,
                Settlement\Constants::RAZORX_SETL_GET_DETAILS_FROM_NSS_REVERSE_SHADOW,
                $this->mode
            );

            if ($reverseShadow === Settlement\Constants::RAZORX_VARIANT_ON)
            {
                return $nssResponse;
            }

            $apiResponse = $this->getSettlementDetailsOld($id);

            (new Settlement\Service())->compareSettlementsAndLogDifference($apiResponse["items"], $nssResponse["items"], ['method_name' => __FUNCTION__], Settlement\Details\Entity::COMPONENT);

            return $apiResponse;
        }

        return $this->getSettlementDetailsOld($id);
    }

    public function getSettlementDetailsOld($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $merchant = $this->merchant;

        if ((new Settlement\Service)->includeDsSettlementTransactions() === true)
        {
            try {

                $fetchDetailsInput = [
                    'settlement_id' => $id,
                    'merchant_id'=>$merchant->getId()
                ];

                $details = app('settlements_merchant_dashboard')->getDsSettlementDetails($fetchDetailsInput, $this->mode);

                $this->getDsSettlementDetails($details);

                $setlDetails = [
                    'entity'    => 'collection',
                    'count'     => count($details['items']),
                    'items'     => $details['items'],
                    'has_aggregated_fee_tax' => $details['has_aggregated_fee_tax']
                ];

                return $setlDetails;

            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::GET_SETTLEMENT_DETAILS_FOR_PAYMENT_FAILED,
                    [
                        'id' => $id,
                        'request' => 'fetch',
                        'code' => $e->getCode(),
                    ]);

                throw new BadRequestException(ErrorCode::BAD_REQUEST_SETTLEMENT_NOT_FOUND);

            }
        }

        $setlDetails = (new Core)->getSettlementDetails($id, $merchant);

        $setlDetails['setl_details']['has_aggregated_fee_tax'] = $setlDetails['has_aggregated_fee_tax'];

        return $setlDetails['setl_details'];
    }

    public function getDsSettlementDetails(&$details)
    {
        foreach ($details['items'] as &$item)
        {
            if (!isset($item['fee'])) {
                $item['fee'] = 0;
            }
            if (!isset($item['tax'])) {
                $item['tax'] = 0;
            }
        }
    }

    public function postSettlementDetailsForOldTxns($input)
    {
        $data = (new Core)->addSettlementDetailsForOldTxns($input);

        return $data;
    }
}

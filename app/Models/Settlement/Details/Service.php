<?php

namespace RZP\Models\Settlement\Details;

use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Trace\TraceCode;
use RZP\Models\Feature\Constants as Features;


class Service extends Base\Service
{
    public function getSettlementDetails($id)
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
        foreach ($details['items'] as $item)
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

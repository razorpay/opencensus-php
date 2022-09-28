<?php

namespace RZP\Models\Merchant\OneClickCheckout\Native;

use RZP\Trace\TraceCode;
use RZP\Models\Order\OrderMeta;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Order;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Base;

class Service extends Base\Service
{

    public function updateOrderStatus($input)
    {
        $rzpOrderId = $input[OneClickCheckout\Constants::ID];

        $order = (new Order\Service())->fetchById($rzpOrderId);

        $merchantOrderId = $order[Order\Entity::RECEIPT];

        $status = Constants::ACTION_STATUS_MAPPING[$input[OneClickCheckout\Constants::ACTION]];

        $start = millitime();

        (new Core())->updateOrderStatus($merchantOrderId, $input[OneClickCheckout\Constants::MERCHANT_ID], $status);

        $this->trace->info(
            TraceCode::ORDER_STATUS_UPDATE,
            [
                'id'        =>$input[OneClickCheckout\Constants::ID],
                'status'    => $status,
                'time'      => millitime() - $start
            ]);
        $reviewStatus = Order1cc\Constants::ACTION_FINAL_REVIEW_STATUS_MAPPING[$input[Order1cc\Constants::ACTION]];

        $param = [
            Order1cc\Fields::REVIEW_STATUS  => $reviewStatus,
            Order\Entity::ID                => $rzpOrderId
        ];

        (new OrderMeta\Service())->updateReviewStatusFor1ccOrder($param,$input[OneClickCheckout\Constants::MERCHANT_ID]);
    }
}

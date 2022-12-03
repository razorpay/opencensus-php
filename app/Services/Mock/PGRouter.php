<?php


namespace RZP\Services\Mock;

use RZP\Services\PGRouter as BasePGRouter;


class PGRouter extends BasePGRouter
{
    public function syncOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return
            [
                "message" => "Order Sync process successfully initiated."
            ];
    }

    public function validateAndCreatePaymentJson(array $input, bool $throwExceptionOnFailure = false): array
    {
        return
            [
                "razorpay_payment_id" => "pay_" . $input['id'],
                "http_status" => 200
            ];
    }
}

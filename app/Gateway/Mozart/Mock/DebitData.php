<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class DebitData extends Base\Mock\Server
{
    public static function wallet_paypal($entities)
    {
        $response = [
            'data' =>
                [
                    '_raw ' => '{\"body\":\"{\\\"id\\\":\\\"6TH801614C6688932\\\",\\\"amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"10.00\\\"},\\\"final_capture\\\":true,\\\"seller_protection\\\":{\\\"status\\\":\\\"ELIGIBLE\\\",\\\"dispute_categories\\\":[\\\"ITEM_NOT_RECEIVED\\\",\\\"UNAUTHORIZED_TRANSACTION\\\"]},\\\"disbursement_mode\\\":\\\"INSTANT\\\",\\\"seller_receivable_breakdown\\\":{\\\"gross_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"10.00\\\"},\\\"paypal_fee\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"0.81\\\"},\\\"net_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"9.19\\\"}},\\\"invoice_id\\\":\\\"67g7bb7u6\\\",\\\"custom_id\\\":\\\"67g7bb7u6\\\",\\\"status\\\":\\\"PARTIALLY_REFUNDED\\\",\\\"create_time\\\":\\\"2019-08-01T09:23:28Z\\\",\\\"update_time\\\":\\\"2019-08-01T09:25:08Z\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932/refund\\\",\\\"rel\\\":\\\"refund\\\",\\\"method\\\":\\\"POST\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/3A830293F71184842\\\",\\\"rel\\\":\\\"up\\\",\\\"method\\\":\\\"GET\\\"}]}\",\"header\":{\"Vary\":[\"Authorization\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"be420230b853c\",\"be420230b853c\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Content-Length\":[\"925\"],\"Content-Type\":[\"application/json;charset=UTF-8\"],\"Date\":[\"Thu, 01 Aug 2019 09:26:46 GMT\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D1454391901%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Thu, 01 Aug 2019 09:56:46 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"]},\"status\":200}',
                    "CaptureId" => "67g7bb7u6",
                    'VerifyLink' => 'https://api.sandbox.paypal.com/v2/checkout/orders/12345678901234567',
                    'amount' => $entities['payment']['amount'],
                    'paymentId' => $entities['payment']['id'],
                    'currency' => 'USD',
                    'status' => 'capture_successful',
                ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => '',
            'next' => [],
            'success' => true,
        ];

        return $response;
    }
}


<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Feature\Constants as FeatureConstants;

class Mocks
{
    public function getShopifyMockListPromotions()
    {
      $promotions = [
          [
              'code'        => '50off',
              'summary'     => 'Get Rs 50 off',
              'description' => 'Big savings for customers',
              'tnc'         => [
                  '1. Only one time use',
              ],
          ],
      ];
      return ['promotions' => $promotions, 'status_code' => 200];
    }

    public function getShopifyMockApplyPromotion($code)
    {
        if ($code === '50off')
        {
            $statusCode = 200;
            $response = [
                'promotion' => [
                    'reference_id' => $code,
                    'code'         => $code,
                    'value'        => 5000,
                ]
            ];
        }
        else
        {
            $statusCode = 400;
            $response = [
                'failure_code' => 'INVALID_COUPON',
                'failure_reason' => 'Coupon does not exist',
              ];
        }
        return ['response' => $response, 'status_code' => $statusCode];
    }

    public function getShopifyMockShippingInfo(array $input): array
    {
        $addresses = $input['addresses'];

        foreach ($addresses as $key => $address) {
            $address['id'] = $key;
            $address['cod'] = true;
            $address['serviceable'] = true;
            $address['cod_fee'] = 3000;
            $address['shipping_fee'] = 6000;
            $addresses[$key] = $address;
        }

        return ['addresses' => $addresses];
    }

    public function getShopifyMockPlaceOrder(): array
    {
        return [
          'order' => [
            'buyer_accepts_marketing' => true,
            'discount_codes' => [
              [
                'code' => '100OFF',
                'amount' => '100',
                'type' => 'fixed_amount'
              ]
            ],
            'line_items' => [
              [
                'name' => 'fan',
                'price' => '2200.00',
                'product_id' => 42021089149155,
                'quantity' => 1,
                'title' => 'fan'
              ]
            ],
            'inventory_behaviour' => 'decrement_obeying_policy',
            'send_receipt' => true,
            'test' => true
          ]
        ];
    }
}

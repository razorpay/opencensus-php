<?php

namespace RZP\Models\Merchant\Shopify1cc;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Validator;

class Service extends Base\Service
{
    // get list of promotions from shopify
    public function getPromotions(array $input): array
    {
        return $this->getShopifyMockListPromotions();
    }

    // apply promotion to storefront checkout
    public function applyPromotion(array $input): array
    {
        return $this->getShopifyMockApplyPromotion($input['code']);
    }

    // remove promotion from storefront checkout
    public function removePromotion(array $input): array
    {
        return $this->getShopifyMockListPromotions();
    }

    // get shipping packages from storefront checkout
    public function getShippingInfo(array $input): array
    {
        return $this->getShopifyMockShippingInfo($input);
    }

    private function getShopifyMockListPromotions()
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

    private function getShopifyMockApplyPromotion($code)
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

    private function getShopifyMockShippingInfo(array $input): array
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
}

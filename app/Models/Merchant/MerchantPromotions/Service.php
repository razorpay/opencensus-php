<?php

namespace RZP\Models\Merchant\MerchantPromotions;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Validator;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\Merchant1ccConfig;

class Service extends Base\Service
{
    /**
     * Returns valid coupon codes retrieved from the merchant
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function fetchCouponCodes(array $input): array
    {
        $startTime = millitime();

        $this->trace->info(TraceCode::MERCHANT_FETCH_COUPONS_REQUEST, $input);

        $mockResponse = $input['mock_response'] ?? null;

        unset($input['mock_response']);

        (new Validator)->validateInput('fetchCouponsRequest', $input);

        $orderId = $input['order_id'];

        $merchantOrderId = null;

        try
        {
            $rzpOrder = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

            $merchantOrderId = $rzpOrder->getReceipt();
        }
        catch (Throwable $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        if ($merchantOrderId === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR
            );
        }
        $input['order_id'] = $merchantOrderId;

        $platformConfig = $this->merchant->getMerchantPlatformConfig();

        $externalCallStart = millitime();

        if ($platformConfig !== null and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY)
        {
            // TODO: critical error if not found !
            $input['order_id'] = $rzpOrder->toArrayPublic()['notes']['storefront_id'];

            $decodedResponse = (new Shopify\Service)->getShopifyCoupons($input);
        }
        else
        {
            $fetchCouponsUrlConfig = $this->merchant->getFetchCouponsUrlConfig();

            if ($fetchCouponsUrlConfig === null)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_FETCH_COUPONS_URL_NOT_CONFIGURED);
            }
            $fetchCouponsUrl = $fetchCouponsUrlConfig->getValue();

            $response = $this->sendRequestToMerchant($fetchCouponsUrl, $input, $mockResponse);

            $decodedResponse = json_decode($response->body, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                throw new Exception\ServerErrorException(
                  'Error while calling Merchant URL',
                  ErrorCode::SERVER_ERROR_MERCHANT_FETCH_COUPONS_EXTERNAL_CALL_EXCEPTION
                );
            }
        }

        $this->traceResponseTime(
            Metric::MERCHANT_EXTERNAL_COUPONS_REQUEST_DURATION_MILLIS,
            $externalCallStart
        );

        $validator = (new Validator);

        if (isset($decodedResponse['promotions']) === false)
        {
            throw new Exception\ServerErrorException('', ErrorCode::SERVER_ERROR_MERCHANT_FETCH_COUPONS_EXTERNAL_CALL_EXCEPTION);
        }

        foreach ($decodedResponse['promotions'] as $coupon)
        {
            $validator->validateInput('fetchCouponsResponse', $coupon);
        }

        $this->traceResponseTime(Metric::MERCHANT_COUPONS_REQUEST_DURATION_MILLIS, $startTime);

        return $decodedResponse;
    }

    /**
     * Verify customer provided coupon's validity.
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     * @throws \Throwable
     */
    public function applyCoupon(array $input): array
    {
        $startTimeMillis = millitime();

        $this->trace->info(TraceCode::MERCHANT_CHECK_COUPON_VALIDITY_REQUEST, $input);

        $mockResponse = $input['mock_response'] ?? null;

        unset($input['mock_response']);

        try
        {
            (new Validator)->validateInput('applyCouponRequest', $input);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_INVALID_REQUEST_COUNT);
            throw $e;
        }

        $orderId = $input['order_id'];

        $merchantOrderId = null;
        try
        {
            $rzpOrder = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);
            $merchantOrderId = $rzpOrder->getReceipt();
        }
        catch (Throwable $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $input['order_id'] = $merchantOrderId;

        $platformConfig = $this->merchant->getMerchantPlatformConfig();

        $externalRequestStart = millitime();

        if ($platformConfig !== null and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY)
        {
            // TODO: critical error if not found !
            $input['order_id'] = $rzpOrder->toArrayPublic()['notes']['storefront_id'];

            $res = (new Shopify\Service)->applyShopifyCoupon($input);

            $decodedResponse = $res['response'];

            $statusCode = $res['status_code'];
        }
        else
        {
            $couponValidityUrlConfig = $this->merchant->getApplyCouponUrlConfig();

            if ($couponValidityUrlConfig === null)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_COUPON_VALIDITY_URL_NOT_CONFIGURED);
            }

            $couponValidityUrl = $couponValidityUrlConfig->getValue();

            $response = $this->sendRequestToMerchant($couponValidityUrl, $input, $mockResponse);

            $decodedResponse = json_decode($response->body, true);

            $statusCode = $response->status_code;

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                throw new Exception\ServerErrorException(
                  'Error while calling Merchant URL',
                  ErrorCode::SERVER_ERROR_MERCHANT_COUPON_VALIDITY_EXTERNAL_CALL_EXCEPTION
                );
            }
        }

        $this->traceResponseTime(
            Metric::MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_TIME_MILLIS,
            $externalRequestStart
        );

        $this->traceResponseTime(Metric::MERCHANT_COUPON_VALIDITY_REQUEST_DURATION_MILLIS, $startTimeMillis);

        try
        {
            switch ($statusCode)
            {
                case 200:
                    (new Validator)->setStrictFalse()->validateInput('applyCouponResponse', $decodedResponse);
                    break;
                case 400:
                    (new Validator)->setStrictFalse()->validateInput('applyCouponInvalidRequestResponse', $decodedResponse);
                    return ['status_code' => 400, 'data' => $decodedResponse];
            }
        }
        catch (Throwable $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_INVALID_RESPONSE_COUNT);
            throw $e;
        }

        // The Order changes may not exist when this code is merged.
        if (method_exists(Order\OrderMeta\Core::class, 'update1CCOrder') === true)
        {
            (new Order\OrderMeta\Core)->update1CCOrder($orderId, ['promotions' => [$decodedResponse['promotion']]]);
        }

        return ['status_code' => 200, 'data' => ['promotions' => [$decodedResponse['promotion']]]];
    }

    public function removeCoupon(array $input)
    {
        if (isset($input['order_id']) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $orderId = $input['order_id'];

        if (method_exists(Order\OrderMeta\Core::class, 'update1CCOrder') === true)
        {
            (new Order\OrderMeta\Core)->update1CCOrder($orderId, ['promotions' => []]);
        }
    }

    /**
     * @throws Exception\ServerErrorException
     */
    protected function sendRequest($request, $mockResponse = null)
    {
        if ((getenv('APP_ENV') === 'testing') and
            ($mockResponse !== null))
        {
            $mockResponseObj = new \stdClass();

            $mockResponseObj->body = json_encode($mockResponse['body']);
            $mockResponseObj->status_code = $mockResponse['status_code'] ?? 200;

            return $mockResponseObj;
        }

        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content']
            );
        }
        catch (Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error while calling Merchant URL',
                ErrorCode::SERVER_ERROR,
                null,
                $e
            );
        }
        return $response;
    }

    protected function traceResponseTime(string $metric, int $startTime, $extraDimensions = [])
    {
        $duration = millitime() - $startTime;

        $dimensions = array_merge(
            $extraDimensions,
            [
                'merchant_id' => $this->merchant->getId(),
            ]);

        $this->trace->histogram($metric, $duration, $dimensions);
    }

    protected function sendRequestToMerchant($merchantUrl, $input, $mockResponse)
    {
      $headers = [
          'Content-Type' => 'application/json',
          'Accept'       => 'application/json',
      ];

      $request = [
          'url'     => $merchantUrl,
          'method'  => Requests::POST,
          'headers' => $headers,
          'content' => json_encode($input),
      ];

      return $this->sendRequest($request, $mockResponse);
    }
}

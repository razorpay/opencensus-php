<?php

namespace RZP\Models\Merchant\MerchantPromotions;

use RZP\Constants\Mode;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Validator;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use RZP\Models\Merchant\OneClickCheckout\Core as OneClickCheckoutCore;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\CouponProvider;

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

        $dimensions =[
            "mode" => $this->mode,
        ];
        $decodedResponse = [];
        $ex = '';
        try{
            $this->trace->count(Metric::FETCH_COUPONS_REQUEST_COUNT, $dimensions);

            $mockResponse = $input['mock_response'] ?? null;

            unset($input['mock_response']);

            (new Validator)->validateInput('fetchCouponsRequest', $input);

            if(empty($input['order_id']) === true && empty($input['checkout_id']) === true)
            {
                $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "Order ID or Checkout ID id required.");

                throw $ex;
            }
            $rzpOrder = null;

            if (empty($input['order_id']) === false)
            {
                $orderId = $input['order_id'];

                $merchantOrderId = null;

                try
                {
                    $rzpOrder = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

                    $merchantOrderId = $rzpOrder->getReceipt();
                }
                catch (\Throwable $e)
                {
                    $this->trace->count(Metric::FETCH_COUPONS_ERROR_COUNT, $dimensions);
                    $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
                    throw $ex;
                }

                if ($merchantOrderId === null)
                {
                    $this->trace->count(Metric::FETCH_COUPONS_ERROR_COUNT, $dimensions);
                    $ex = new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR
                    );
                    throw $ex;
                }

                $input['order_id'] = $merchantOrderId;
            }

            if (empty($input['checkout_id']) === false)
            {
                $input['order_id'] = $input['checkout_id'];
            }

            $platformConfig = $this->merchant->getMerchantPlatformConfig();

            $externalCallStart = millitime();

            $dimensions = $this->addPlatformDimension($platformConfig, $dimensions);

            if ($platformConfig !== null and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY)
            {
                // TODO: critical error if not found !
                if (empty($rzpOrder) === false)
                {
                    $input['order_id'] = $rzpOrder->toArrayPublic()['notes']['storefront_id'];
                }

                $this->trace->count(Metric::FETCH_COUPONS_SHOPIFY_REQUEST_COUNT, $dimensions);

                $decodedResponse = (new Shopify\Service)->getShopifyCoupons($input, $this->merchant->getId());
            }
            else
            {
                $fetchCouponsUrlConfig = $this->merchant->getFetchCouponsUrlConfig();

                if ($fetchCouponsUrlConfig === null)
                {
                    $this->trace->count(Metric::FETCH_COUPONS_ERROR_COUNT, $dimensions);
                    $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_FETCH_COUPONS_URL_NOT_CONFIGURED);
                    throw $ex;
                }
                $fetchCouponsUrl = $fetchCouponsUrlConfig->getValue();

                $this->trace->count(Metric::FETCH_COUPONS_MERCHANT_REQUEST_COUNT, $dimensions);

                $response = (new CommonUtils())->sendRequestToMerchant($fetchCouponsUrl, $input, $mockResponse);

                $decodedResponse = json_decode($response->body, true);

                if (json_last_error() !== JSON_ERROR_NONE)
                {
                    $ex = new Exception\ServerErrorException(
                        'Error while calling Merchant URL',
                        ErrorCode::SERVER_ERROR_MERCHANT_FETCH_COUPONS_EXTERNAL_CALL_EXCEPTION
                    );
                    throw $ex;
                }
            }

            $this->traceResponseTime(
                Metric::MERCHANT_EXTERNAL_COUPONS_REQUEST_DURATION_MILLIS,
                $externalCallStart,
                $dimensions
            );

            $validator = (new Validator);

            if (isset($decodedResponse['promotions']) === false)
            {
                $ex = new Exception\ServerErrorException('', ErrorCode::SERVER_ERROR_MERCHANT_FETCH_COUPONS_EXTERNAL_CALL_EXCEPTION);
                throw $ex;
            }

            foreach ($decodedResponse['promotions'] as $coupon)
            {
                $validator->validateInput('fetchCouponsResponse', $coupon);
            }

            $this->traceResponseTime(
                Metric::MERCHANT_COUPONS_REQUEST_DURATION_MILLIS,
                $startTime,
                $dimensions
            );

            return $decodedResponse;
        }
        catch (\Throwable $e)
        {
            $ex = $e;
            throw $e;
        }
        finally
        {
            if (empty($ex) === true){
                $this->trace->info(TraceCode::FETCH_COUPONS_REQUEST,
                    array_merge(
                        $dimensions,
                        [
                            'request' => $this->getMaskedContactDetails($input),
                            'response' => $this->getMaskedCoupons($decodedResponse) ,
                            'exception'=> $ex,
                        ]
                    )
                );
            }else {
                $internalErrorCode = "";
                if (($ex instanceof Exception\BaseException) === true) {
                    $internalErrorCode = $ex->getError()->getInternalErrorCode();
                }
                $this->trace->count(Metric::FETCH_COUPONS_MERCHANT_ERROR_COUNT, array_merge(
                    $dimensions,
                    [
                        'internal_error_code' => $internalErrorCode,
                    ]
                ));
                $this->trace->error(TraceCode::FETCH_COUPONS_ERROR,
                    array_merge(
                        $dimensions,
                        [
                            'request' => $this->getMaskedContactDetails($input),
                            'response' => $this->getMaskedCoupons($decodedResponse),
                            'internal_error_code' => $internalErrorCode,
                            'exception'=> $ex->getTrace(),
                        ]
                    )
                );
            }
        }
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

        $dimensions = [
            "mode" => $this->mode
        ];

        $decodedResponse = [];

        $ex = '';

        try {
            $this->trace->count(Metric::MERCHANT_COUPON_VALIDITY_REQUEST_COUNT, $dimensions);

            $mockResponse = $input['mock_response'] ?? null;

            unset($input['mock_response']);

            try {
                (new Validator)->validateInput('applyCouponRequest', $input);
            } catch (\Throwable $e) {
                $this->trace->count(Metric::MERCHANT_COUPON_VALIDITY_INVALID_REQUEST_COUNT, $dimensions);
                $ex = $e;
                throw $e;
            }

            $routeToMagicCheckoutService = false;
            try {
                $routeToMagicCheckoutService = $this->canRouteToMagicCheckoutService($input, $this->merchant);
            } catch (\Throwable $e) {
                $routeToMagicCheckoutService = false;
            }

            if ($routeToMagicCheckoutService === true)
            {
                $input['merchant_id'] = $this->merchant->getId();
                return (new CouponProvider\Service())->applyCoupon($input);
            }

            $orderId = $input['order_id'];

            $merchantOrderId = null;
            try {
                $rzpOrder = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);
                $merchantOrderId = $rzpOrder->getReceipt();
            } catch (\Throwable $e) {
                $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
                throw $ex;
            }

            $orderMeta = array_first($rzpOrder->orderMetas ?? [], function ($orderMeta) {
                return $orderMeta->getType() === Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
            });

            if ($orderMeta === null) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
            }

            $input['order_id'] = $merchantOrderId;

            $platformConfig = $this->merchant->getMerchantPlatformConfig();

            $externalRequestStart = millitime();

            $dimensions = $this->addPlatformDimension($platformConfig, $dimensions);

            if ($platformConfig !== null and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY) {
                // TODO: critical error if not found !
                $input['order_id'] = $rzpOrder->toArrayPublic()['notes']['storefront_id'];

                $input['cart_id'] = $rzpOrder->toArrayPublic()['notes']['cart_id'];

                $this->trace->count(Metric::MERCHANT_COUPON_VALIDITY_SHOPIFY_REQUEST_COUNT, $dimensions);

                $res = (new Shopify\Service)->applyShopifyCoupon($input, $this->merchant->getId(), $orderId);

                $decodedResponse = $res['response'];

                $statusCode = $res['status_code'];
            } else {
                $couponValidityUrlConfig = $this->merchant->getApplyCouponUrlConfig();

                if ($couponValidityUrlConfig === null) {

                    $this->trace->count(Metric::MERCHANT_COUPON_VALIDITY_INVALID_REQUEST_COUNT, $dimensions);
                    $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_COUPON_VALIDITY_URL_NOT_CONFIGURED);
                    throw $ex;
                }

                $couponValidityUrl = $couponValidityUrlConfig->getValue();

                $this->trace->count(Metric::MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_COUNT, $dimensions);

                $response = (new CommonUtils())->sendRequestToMerchant($couponValidityUrl, $input, $mockResponse);

                $decodedResponse = json_decode($response->body, true);

                $statusCode = $response->status_code;

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $ex = new Exception\ServerErrorException(
                        'Error while calling Merchant URL',
                        ErrorCode::SERVER_ERROR_MERCHANT_COUPON_VALIDITY_EXTERNAL_CALL_EXCEPTION
                    );
                    throw $ex;
                }
            }

            $this->traceResponseTime(
                Metric::MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_TIME_MILLIS,
                $externalRequestStart,
                $dimensions
            );

            $this->traceResponseTime(
                Metric::MERCHANT_COUPON_VALIDITY_REQUEST_DURATION_MILLIS,
                $startTimeMillis,
                $dimensions
            );

            try {
                switch ($statusCode) {
                    case 200:
                        (new Validator)->setStrictFalse()->validateInput('applyCouponResponse', $decodedResponse);
                        break;
                    case 400:
                        (new Validator)->setStrictFalse()->validateInput('applyCouponInvalidRequestResponse', $decodedResponse);
                        return ['status_code' => 422, 'data' => $decodedResponse];
                }
            } catch (\Throwable $e) {
                $this->trace->count(Metric::MERCHANT_EXTERNAL_COUPON_VALIDITY_REQUEST_INVALID_RESPONSE_COUNT, $dimensions);
                $ex = $e;
                throw $e;
            }

            $promotions = $orderMeta->getValue()['promotions'] ?? [];

            $couponRestriction = $this->merchant->get1ccConfigFlagStatus('one_cc_gift_card_restrict_coupon');

            /*  if true remove gift card
             *  and coupon as well
             */
            if ($couponRestriction === true) {
                $promotions = [];
            }
            else {
                $promotions = (new CommonUtils())->removeCouponsFromPromotions($promotions);
            }

            array_push($promotions, $decodedResponse['promotion']);

            (new OneClickCheckoutCore)->update1CcOrder($orderId, ['promotions' => $promotions]);

            // get coupon_config for MID
            $couponConfig = $this->merchant->get1ccConfig(Type::COUPON_CONFIG);

            $disabledMethods = $this->getDisabledMethods($couponConfig, $decodedResponse['promotion']['reference_id']);

            if (empty($disabledMethods) === false)
            {
                $decodedResponse['promotion']['disabled_methods'] = $disabledMethods;
            }

            return ['status_code' => 200, 'data' => ['promotions' => [$decodedResponse['promotion']]]];

        }
        catch(\Throwable $e)
        {
            $ex = $e;
            throw $e;
        }
        finally
        {
            if (empty($ex) === true){
                $this->trace->info(TraceCode::MERCHANT_CHECK_COUPON_VALIDITY_REQUEST,
                    array_merge(
                        $dimensions,
                        [
                            'request' => $this->getMaskedContactDetails($input),
                            'response' => (empty($decodedResponse['promotion']) === true) ? $decodedResponse :
                                $this->getMaskedCoupons(['promotions' => [$decodedResponse['promotion']]]),
                            'exception' => $ex
                        ]
                    )
                );
            }else {
                $internalErrorCode = "";
                if (($ex instanceof Exception\BaseException) === true) {
                    $internalErrorCode = $ex->getError()->getInternalErrorCode();
                }
                $this->trace->count(Metric::MERCHANT_COUPON_VALIDITY_ERROR_COUNT, array_merge(
                    $dimensions,
                    [
                        'internal_error_code' => $internalErrorCode,
                    ]
                ));
                $this->trace->error(TraceCode::MERCHANT_CHECK_COUPON_VALIDITY_ERROR,
                    array_merge(
                        $dimensions,
                        [
                            'request' => $this->getMaskedContactDetails($input),
                            'response' => (empty($decodedResponse['promotion']) === true) ? $decodedResponse :
                                $this->getMaskedCoupons(['promotions' => [$decodedResponse['promotion']]]),
                            'exception' => $ex->getTrace(),
                            'message'   => $ex->getMessage(),
                            'internal_error_code' => $internalErrorCode
                        ]
                    )
                );
            }
        }
    }

    /**
     * Remove Coupons
     * @param array $input
     * @throws \Throwable
     */
    public function removeCoupon(array $input)
    {
        try {
            (new Validator)->validateInput('removeCouponRequest', $input);

            $orderId = $input['order_id'];

            $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

            $orderMeta = array_first($order->orderMetas ?? [], function ($orderMeta) {
                return $orderMeta->getType() === Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
            });

            if ($orderMeta === null) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
            }

            $existingPromotions = $orderMeta->getValue()['promotions'] ?? [];
            $promotions = (new CommonUtils())->removeCouponsFromPromotions($existingPromotions);

            (new OneClickCheckoutCore)->update1CcOrder($orderId, ['promotions' => $promotions]);

        } catch (\Throwable $e) {
            $input['reference_id'] = mask_by_percentage($input['reference_id']);

            $this->trace->error(TraceCode::REMOVE_COUPON_ERROR,
                [
                    'request' =>  $input,
                    'exception'=> $e->getTrace()
                ]
            );

            throw $e;
        }
    }


    // NOTE: At scale we will remove merchant_id to reduce cardinality
    protected function traceResponseTime(string $metric, int $startTime, $dimensions = [])
    {
        $duration = millitime() - $startTime;

        $this->trace->histogram($metric, $duration, $dimensions);
    }


    protected function getMaskedContactDetails($input): array
    {
        $maskedRequest =[];
        if (empty($input['order_id']) === false) {
            $maskedRequest = array_merge($maskedRequest,
                [
                    "order_id" => $input['order_id'],
                ]);
        }
        if (empty($input['contact']) === false) {
            $maskedRequest = array_merge($maskedRequest,
                [
                    'contact' => mask_phone($input['contact'])
                ]);
        }
        if (empty($input['email']) === false) {
            $maskedRequest = array_merge($maskedRequest,
                [
                    'email' => mask_email($input['email'])]
            );
        }
        return $maskedRequest;
    }

    protected function getMaskedCoupons($response): array
    {
        $res = [];
        if (empty($response['promotions']) === false)
        {
            foreach ($response['promotions'] as $coupon)
            {
                if (empty($coupon['code']) === false)
                {
                    $coupon['code'] = mask_by_percentage($coupon['code']);
                }
                if (empty($coupon['reference_id']) === false)
                {
                    $coupon['reference_id'] = mask_by_percentage($coupon['reference_id']);
                }
                $res = array_merge($res, $coupon);
            }
        }
        return $res;
    }

    protected function addPlatformDimension($platformConfig, $dimensions = []): array
    {
        if ($platformConfig === null)
        {
            return $dimensions;
        }

        return array_merge(
            $dimensions,
            [
                'platform' => $platformConfig->getValue(),
            ]
        );
    }

    /**
     * @param $couponConfig
     * @param $referenceId
     * @return array|void[]
     */
    public function getDisabledMethods($couponConfig, $referenceId): array
    {
        $disabledMethods = [];
        if ($couponConfig !== null)
        {
            $couponConfigData = $couponConfig->getValueJson();
            if(array_key_exists($referenceId, $couponConfigData) === true) {
                $config = $couponConfigData[$referenceId];
            }
            if (empty($config) == false)
            {
                $disabledMethods = $config['disabled_methods'];
            }
        }
        return $disabledMethods;
    }

    public function canRouteToMagicCheckoutService(array $input, $merchant): bool
    {
        $platformConfig = $merchant->getMerchantPlatformConfig();
        if ((app()->isEnvironmentProduction() === true && $this->mode === Mode::TEST) ||
            ($platformConfig != null && $platformConfig->getValue() === Constants::SHOPIFY) ||
            ($this->merchant->get1ccConfigFlagStatus(Constants::ONE_CC_GIFT_CARD) === true))
        {
            return false;
        }

        $expResult = (new SplitzExperimentEvaluator())->evaluateExperiment(
            [
                'id'            => $input['order_id'],
                'experiment_id' => $this->app['config']->get('app.magic_apply_coupon_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchant->getId(),
                    ]),
            ]
        );
        return $expResult['variant'] === 'magic';
    }

}

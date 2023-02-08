<?php

namespace RZP\Models\Merchant\MerchantGiftCardPromotions;

use RZP\Exception\ServerErrorException;
use RZP\Models\Feature\Constants as FeatureConstants;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Validator;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as OrderOneCCFields;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
use RZP\Models\Merchant\OneClickCheckout\Core as OneClickCheckoutCore;
use RZP\Models\Order\OrderMeta;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payment;
use RZP\Models\Merchant\OneClickCheckout\Shopify;

class Service extends Base\Service
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Verify customer provided gift card's validity and Apply
     * @param string $orderId
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    public function applyGiftCard(string $orderId, array $input): array
    {

        $startTime = millitime();

        $dimensions = [
            'mode' => $this->mode
        ];

        $ex = [];

        try {

            $platformConfig = $this->merchant->getMerchantPlatformConfig();

            $this->checkGiftCardApplicability($platformConfig);

            $dimensions = array_merge($dimensions,
                [
                    'platform' => $platformConfig->getValue()
                ]);

            $this->trace->count(Metric::APPLY_GIFT_CARD_REQUEST_COUNT, $dimensions);

            $mockResponse = $input['mock_response'] ?? null;

            unset($input['mock_response']);

            (new Validator)->validateInput('applyGiftCardRequest', $input);

            $action = function () use ($orderId, $input, $startTime,$dimensions, $ex, $mockResponse ) {

                list($order, $orderMeta) = (new CommonUtils())->getOneCcOrderMeta($orderId);

                (new CommonUtils())->validateOrderNotPaidOrRefunded($order);

                if ($orderMeta === null) {
                    $this->trace->count(Metric::APPLY_GIFT_CARD_ERROR_COUNT, $dimensions);
                    $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
                    throw $ex;
                }

                $multipleGiftCardsSupported = $this->merchant->get1ccConfigFlagStatus(Constants::ONE_CC_MULTIPLE_GIFT_CARD);

                $couponRestriction = $this->merchant->get1ccConfigFlagStatus(Constants::ONE_CC_GIFT_CARD_RESTRICT_COUPON);

                $promotions = $orderMeta->getValue()[OrderOneCCFields::PROMOTIONS] ?? [];

                if (count($promotions) > 0) {
                    if ($couponRestriction === true) {
                        $promotions = (new CommonUtils())->removeCouponsFromPromotions($promotions);
                    }

                    if ($multipleGiftCardsSupported === false) {
                        $promotions = (new CommonUtils())->removeGiftCardsFromPromotions($promotions);
                    }

                    if ($this->checkIfSameGiftCardAppliedAgain($promotions, $input[Constants::GIFT_CARD_NUMBER]) === true) {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_ERROR,
                            null,
                            null,
                            PublicErrorDescription::SAME_GIFT_CARD_APPLIED);
                    }
                }

                $validateGiftCardCallStart = millitime();

                $response = $this->validateGiftCard($orderId, $input, $mockResponse);

                $this->traceResponseTime(
                    Metric::MERCHANT_EXTERNAL_VALIDATE_GIFT_CARD_DURATION_MILLIS,
                    $validateGiftCardCallStart,
                    $dimensions
                );

                if ($response['status_code'] === 400) {
                    return $response;
                }

                $result = $response['response'];

                array_push($promotions,
                    [
                        OrderOneCCFields::PROMOTIONS_REFERENCE_ID => isset($result[Constants::GIFT_CARD_REFERENCE_ID]) === true ? $result[Constants::GIFT_CARD_REFERENCE_ID] : $result[Constants::GIFT_CARD_NUMBER],
                        OrderOneCCFields::PROMOTIONS_CODE => $result[Constants::GIFT_CARD_NUMBER],
                        OrderOneCCFields::PROMOTIONS_TYPE => OrderOneCCFields::GIFT_CARD,
                        OrderOneCCFields::PROMOTIONS_VALUE => $result[Constants::GIFT_CARD_BALANCE]
                    ]
                );

                (new OneClickCheckoutCore)->update1CcOrder(
                    $orderId,
                    [OrderOneCCFields::PROMOTIONS => $promotions],
                    $mockResponse);

                $this->traceResponseTime(
                    Metric::APPLY_GIFT_CARD_REQUEST_DURATION_MILLIS,
                    $startTime,
                    $dimensions
                );
               return ['status_code' => 200, 'data' => [OrderOneCCFields::GIFT_CARD_PROMOTION => $result]];
            };

            $mutexKey = (new CommonUtils())->get1ccUpdateOrderMutex($orderId);

            $result = $this->mutex->acquireAndReleaseStrict(
                $mutexKey,
                $action,
                OrderMeta\Order1cc\Constants::ORDER_ACTION_MUTEX_LOCK_TIMEOUT,
                ErrorCode::SERVER_ERROR_MUTEX_RESOURCE_NOT_ACQUIRED,
                OrderMeta\Order1cc\Constants::ORDER_ACTION_MUTEX_RETRY_COUNT
            );

            return $result;
        } catch (\Throwable $e) {
            $ex = $e;
            throw $ex;
        } finally {
            if (empty($ex) === true){
                $this->trace->info(TraceCode::APPLY_GIFT_CARD_REQUEST,
                    array_merge(
                        $dimensions,
                        [
                            'request' => $this->getMaskedGiftCardDetails($input)
                        ]
                    )
                );
            }else {
                $this->trace->error(TraceCode::APPLY_GIFT_CARD_REQUEST_ERROR,
                    array_merge(
                        $dimensions,
                        [
                            'request'  => $this->getMaskedGiftCardDetails($input),
                            'exception'=> array_slice(explode("\n", $ex->getTraceAsString()), 0, 20)
                        ]
                    )
                );
            }
        }
    }

    /**
     * Remove Gift Cards
     * @param string $orderId
     * @param array $input
     * @throws Exception\BadRequestException
     * @throws \Throwable
     * @return void
     */
    public function removeGiftCard(string $orderId, array $input)
    {
        try {
            $platformConfig = $this->merchant->getMerchantPlatformConfig();

            $this->checkGiftCardApplicability($platformConfig);

            (new Validator)->validateInput('removeGiftCardRequest', $input);

            $mutexKey = (new CommonUtils())->get1ccUpdateOrderMutex($orderId);

            $this->mutex->acquireAndRelease($mutexKey,
                function() use ($orderId, $input) {
                    list($order, $orderMeta) = (new CommonUtils())->getOneCcOrderMeta($orderId);

                    (new CommonUtils())->validateOrderNotPaidOrRefunded($order);

                    if ($orderMeta === null) {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
                    }

                    $giftCardsToRemove = $input[Constants::GIFT_CARD_NUMBERS];

                    $promotions = $orderMeta->getValue()[OrderOneCCFields::PROMOTIONS] ?? [];

                    $couponsApplied = (new CommonUtils())->removeGiftCardsFromPromotions($promotions);

                    $appliedGiftCards = (new CommonUtils())->removeCouponsFromPromotions($promotions);

                    $remainingGiftCards = array_values(array_filter($appliedGiftCards, function($card) use ($giftCardsToRemove) {
                        return (in_array($card[OrderOneCCFields::PROMOTIONS_CODE], $giftCardsToRemove) == false);
                    }));

                    $remainingPromotions = array_merge($couponsApplied,$remainingGiftCards);

                    (new OneClickCheckoutCore)->update1CcOrder($orderId, [OrderOneCCFields::PROMOTIONS => $remainingPromotions]);
                },
                OrderMeta\Order1cc\Constants::ORDER_ACTION_MUTEX_LOCK_TIMEOUT,
                ErrorCode::SERVER_ERROR_MUTEX_RESOURCE_NOT_ACQUIRED,
                OrderMeta\Order1cc\Constants::ORDER_ACTION_MUTEX_RETRY_COUNT
            );

        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::REMOVE_GIFT_CARD_REQUEST_ERROR,
                [
                    'request' =>  $this->maskGiftCardNumbers($input),
                    'exception'=> array_slice(explode("\n", $e->getTraceAsString()), 0, 20)
                ]
            );
            throw $e;
        }
    }


    /**
     * Verify customer provided gift card's validity
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     * @throws Throwable
     */
    public function validateGiftCard(string $orderId, $input, $mockResponse = null) {

        $platformConfig = $this->merchant->getMerchantPlatformConfig();

        $this->checkGiftCardApplicability($platformConfig);

        list($order, $orderMeta) =  (new CommonUtils())->getOneCcOrderMeta($orderId);

        $merchantOrderId = $order->getReceipt();

        if(is_null($merchantOrderId))
        {
            $this->trace->count(Metric::VALIDATE_GIFT_CARD_ERROR_COUNT);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ORDER_ID_UNAVAILABLE
            );
        }

        $input['order_id'] = $merchantOrderId;

        $platform = $platformConfig->getValue();

        $response = [];

        switch ($platform)
        {
            case 'shopify':
                $orderPublic = $order->toArrayPublic();
                $input['order_id'] = $orderPublic['notes']['storefront_id'];

                $input['cart_id'] = $orderPublic['notes']['cart_id'];

                if (method_exists(Shopify\Service::class, 'validateGiftCard') === true) {
                    $response = (new Shopify\Service)->validateGiftCard($input, $this->merchant->getId());
                }
                else {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR,
                        null,
                        null,
                        PublicErrorDescription::GIFT_CARD_APPLICATION_NOT_ALLOWED);
                }

                $decodedResponse = $response['response'];

                $statusCode = $response['status_code'];
                break;
            case 'woocommerce':
                $domainUrlConfig = $this->merchant->get1ccConfig(Merchant1ccConfig\Type::DOMAIN_URL);

                if ($domainUrlConfig === null) {
                    throw new Exception\ServerErrorException(
                        PublicErrorDescription::MERCHANT_DOMAIN_URL_NOT_PRESENT,
                        ErrorCode::SERVER_ERROR_MERCHANT_DOMAIN_URL_NOT_PRESENT
                    );
                }

                $wooCommerceEndPoint = $this->app['config']->get('app.magic_checkout_woocommerce_giftcard_url');

                $giftCardValidityUrl = ($domainUrlConfig->getValue()).$wooCommerceEndPoint;

                $response = (new CommonUtils())->sendRequestToMerchant($giftCardValidityUrl, $input, $mockResponse);

                $decodedResponse = json_decode($response->body, true);

                $statusCode = $response->status_code;

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->trace->count(Metric::MERCHANT_VALIDATE_GIFT_CARD_ERROR_COUNT);
                    throw new Exception\ServerErrorException(
                        PublicErrorDescription::VALIDATE_GIFT_CARD_RESPONSE_JSON_ERROR,
                        ErrorCode::SERVER_ERROR_MERCHANT_VALIDATE_GIFT_CARD_EXTERNAL_CALL_EXCEPTION
                    );
                }
                break;
            default:
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_GIFT_CARD_FUNCTIONALITY_NOT_APPLICABLE);
        }

        try {
            switch ($statusCode) {
                case 200:
                    (new Validator)->setStrictFalse()->validateInput('applyGiftCardResponse', $decodedResponse);
                    break;
                case 400:
                    (new Validator)->setStrictFalse()->validateInput('applyGiftCardInvalidRequestResponse', $decodedResponse);
                    return ['status_code' => 400, 'data' => $decodedResponse];
            }
        } catch (\Throwable $e) {
            $this->trace->count(Metric::MERCHANT_VALIDATE_GIFT_CARD_ERROR_COUNT);

            $this->trace->error(TraceCode::VALIDATE_GIFT_CARD_EXTERNAL_RESPONSE_ERROR,
                [
                    'request' =>  $this->getMaskedGiftCardDetails($input),
                    'response' => $statusCode === 200 ? $this->getMaskedGiftCardResponseDetails($decodedResponse) : $decodedResponse,
                    'exception'=> array_slice(explode("\n", $e->getTraceAsString()), 0, 20)
                ]
            );

            throw  new Exception\ServerErrorException('', ErrorCode::SERVER_ERROR_MERCHANT_VALIDATE_GIFT_CARD_EXTERNAL_CALL_EXCEPTION);
        }

        return ['status_code' => 200, 'response' => $decodedResponse[OrderOneCCFields::GIFT_CARD_PROMOTION]];
    }

    protected function traceResponseTime(string $metric, int $startTime, $dimensions = [])
    {
        $duration = millitime() - $startTime;
        $this->trace->histogram($metric, $duration, $dimensions);
    }

    protected function checkIfSameGiftCardAppliedAgain($promotion, $giftCardNumber) {

        $sameGiftCardFound = array_first($promotion , function ($card) use ($giftCardNumber) {
            return (isset($card[OrderOneCCFields::PROMOTIONS_TYPE]) === true &&
                $card[OrderOneCCFields::PROMOTIONS_TYPE] === OrderOneCCFields::GIFT_CARD &&
                $card[OrderOneCCFields::PROMOTIONS_CODE]===$giftCardNumber);
        });

        if (empty($sameGiftCardFound) === false)
        {
           return true;
        }
        return false;
    }

    protected function getMaskedGiftCardDetails(array $data): array
    {
        $maskedResult = [];

        if (empty($data['contact']) === false) {
            $maskedResult['contact'] = mask_phone($data['contact']);
        }
        if (empty($data['email']) === false) {
            $maskedResult['email'] = mask_email($data['email']);
        }
        if (empty($data['gift_card_number']) === false) {
            $maskedResult['gift_card_number'] = mask_by_percentage($data['gift_card_number']);
        }

        return $maskedResult;
    }

    protected function getMaskedGiftCardResponseDetails($data): array
    {
        if (isset($data['gift_card_promotion']) && isset($data['gift_card_promotion']['gift_card_number'])) {
            $data['gift_card_promotion']['gift_card_number'] =  mask_by_percentage($data['gift_card_promotion']['gift_card_number']);
        }

        if (isset($data['gift_card_promotion']) && isset($data['gift_card_promotion']['gift_card_reference_id'])) {
            $data['gift_card_promotion']['gift_card_reference_id'] =  mask_by_percentage($data['gift_card_promotion']['gift_card_reference_id']);
        }
        return $data;
    }

    /**
     * @throws Exception\BadRequestException
     */
    protected function checkGiftCardApplicability($platformConfig) {
        if($this->merchant === null or $this->merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
        }

        if($platformConfig === null or  $platformConfig->getValue() === Merchant1ccConfig\Type::NATIVE or $this->merchant->get1ccConfigFlagStatus(Constants::ONE_CC_GIFT_CARD) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GIFT_CARD_FUNCTIONALITY_NOT_APPLICABLE);
        }
    }

    protected function maskGiftCardNumbers($input):array {
        foreach ($input[Constants::GIFT_CARD_NUMBERS] as $key => $numbers) {
            $input[Constants::GIFT_CARD_NUMBERS][$key] = mask_by_percentage($numbers);
        }

        return $input;
    }

    /**
     * preprocess gift card amount used.
     * @param string $orderId
     * @param array $orderMetaInput
     * @throws \Throwable
     * @return array
     */
    public function preProcessGiftCards(string $orderId, array $orderMetaInput, $mockResponse = null): array
    {
        list($order, $orderMeta) =  (new CommonUtils())->getOneCcOrderMeta($orderId);

        $value = $orderMeta->getValue();
        $existingPromotions = $value[OrderOneCCFields::PROMOTIONS] ?? [];

        $promotions =  isset($orderMetaInput[OrderOneCCFields::PROMOTIONS]) ? $orderMetaInput[OrderOneCCFields::PROMOTIONS] : $existingPromotions;
        $discount = (new CommonUtils())->getAppliedCouponValue($promotions);

        $lineItemsTotal = $value[OrderOneCCFields::LINE_ITEMS_TOTAL];
        $shippingFee = $value[OrderOneCCFields::SHIPPING_FEE] ?? 0;

        $shippingFeeValue = isset($orderMetaInput[OrderOneCCFields::SHIPPING_FEE]) ? $orderMetaInput[OrderOneCCFields::SHIPPING_FEE] : $shippingFee;

        $codFee = isset($orderMetaInput[OrderOneCCFields::COD_FEE]) ? $orderMetaInput[OrderOneCCFields::COD_FEE] : 0;

        $minimumAllowedCartAmount = 100; // minimum cart amount should be Rs 1.

        $cartAmountAfterDiscount = max(0,$lineItemsTotal-$discount);

        $finalCartAmount = max($minimumAllowedCartAmount, $cartAmountAfterDiscount + $shippingFeeValue);

        if (count($promotions) > 0) {

            foreach ($promotions as $key => $card) {

                if (isset($card[OrderOneCCFields::PROMOTIONS_TYPE]) === true && $card[OrderOneCCFields::PROMOTIONS_TYPE] === OrderOneCCFields::GIFT_CARD) {

                    $giftCardInput = (new CommonUtils())->buildGiftCardInput($value, $card);

                    try {
                        $giftCardDetailsResponse = $this->validateGiftCard($orderId, $giftCardInput, $mockResponse);
                    } catch (\Throwable $e) {
                        throw new ServerErrorException(
                            PublicErrorDescription::GIFT_CARD_EXTERNAL_REQUEST_FAILED,
                            ErrorCode::SERVER_ERROR_MERCHANT_VALIDATE_GIFT_CARD_EXTERNAL_CALL_EXCEPTION,
                            null,
                            null);
                    }

                    if ($giftCardDetailsResponse['status_code'] === 400) {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_ERROR,
                            null,
                            null,
                            PublicErrorDescription::GIFT_CARD_INVALID);
                    }

                    $result = $giftCardDetailsResponse['response'];

                    $giftCardBalance = $result[Constants::GIFT_CARD_BALANCE];

                    if ($giftCardBalance < $card[OrderOneCCFields::PROMOTIONS_VALUE]) {
                        throw new Exception\BadRequestException(
                            PublicErrorDescription::GIFT_CARD_BALANCE_LESS_THAN_PREVIOUS_APPLIED);
                    }

                    $amountUsedFromGiftCard = min($giftCardBalance, $finalCartAmount - 100);

                    //adjusting cod fee is extra amount available in gift_card
                    if ($codFee > 0 && $giftCardBalance > $amountUsedFromGiftCard) {
                        $amountAdjustedForCod = min($codFee, $giftCardBalance - $amountUsedFromGiftCard);
                        $codFee = $codFee - $amountAdjustedForCod;
                        $amountUsedFromGiftCard = $amountUsedFromGiftCard + $amountAdjustedForCod;
                    }

                    $promotions[$key][OrderOneCCFields::PROMOTIONS_VALUE] = $result[Constants::GIFT_CARD_PARTIAL_REDEMPTION] === 1 ? $amountUsedFromGiftCard : $giftCardBalance;
                }
            }
        }

        return $promotions;
    }

    /**
     * @param $orderId
     * @param $method
     * @param OrderMeta\Entity $orderMeta
     * @throws \Throwable
     */
    public function adjustCodFeeIfGiftCardBalanceAvailable(string $orderId, OrderMeta\Entity $orderMeta,string $method) {

        $value = $orderMeta->getValue();

        $promotions = $orderMeta->getValue()[OrderOneCCFields::PROMOTIONS] ?? [];

        $orderMetaInput = [
            OrderOneCCFields::PROMOTIONS => $promotions
        ];

        if ($method === Payment\Method::COD) {
            $codFee = $value[OrderOneCCFields::COD_FEE] ?? 0;
            $orderMetaInput[OrderOneCCFields::COD_FEE] = $codFee;
        }

        (new OneClickCheckoutCore)->update1CcOrder(
            $orderId,
            $orderMetaInput
        );

    }
}

<?php

namespace RZP\Models\Merchant\OneClickCheckout\Utils;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as OrderOneCCFields;
use RZP\Models\Merchant\OneClickCheckout\Constants;

/**
 * Common util functions for 1cc
 */
class CommonUtils extends Base\Core
{
    public function sendRequestToMerchant($merchantUrl, $input, $mockResponse)
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

        try
        {
            $response = DomainUtils::sendExternalRequest(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method']
            );
        }
        catch (\Throwable $e)
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

    public function removeCouponsFromPromotions(array $promotions) {
        return array_values(array_filter($promotions, function($card){
            return (isset($card[OrderOneCCFields::PROMOTIONS_TYPE]) === true && $card[OrderOneCCFields::PROMOTIONS_TYPE] === OrderOneCCFields::GIFT_CARD);
        }));
    }

    public function removeGiftCardsFromPromotions(array $promotions) {
        return array_values(array_filter($promotions, function($card){
            return (isset($card[OrderOneCCFields::PROMOTIONS_TYPE]) === false ||  $card[OrderOneCCFields::PROMOTIONS_TYPE] !== OrderOneCCFields::GIFT_CARD);
        }));
    }

    /**
     * @param string $orderId
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     * @return array
     */
    public function getOneCcOrderMeta(string $orderId) {

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $orderMeta = array_first($order->orderMetas ?? [], function ($orderMeta)
        {
            return $orderMeta->getType() === Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
        });
        return [$order, $orderMeta];
    }

    public function buildGiftCardInput(array $value, $card) {
        $giftCardInput = [
            Constants::GIFT_CARD_NUMBER => $card[OrderOneCCFields::PROMOTIONS_CODE],
        ];

        $customerDetails = $value[OrderOneCCFields::CUSTOMER_DETAILS] ?? null;
        if (empty($customerDetails) === false && empty($customerDetails[OrderOneCCFields::CUSTOMER_DETAILS_CONTACT]) === false)
        {
            $giftCardInput[OrderOneCCFields::CUSTOMER_DETAILS_CONTACT] = $customerDetails[OrderOneCCFields::CUSTOMER_DETAILS_CONTACT];
        }

        if (empty($customerDetails) === false && empty($customerDetails[OrderOneCCFields::CUSTOMER_DETAILS_EMAIL]) === false) {
            $giftCardInput[OrderOneCCFields::CUSTOMER_DETAILS_EMAIL]  = $customerDetails[OrderOneCCFields::CUSTOMER_DETAILS_EMAIL];
        }

        return $giftCardInput;
    }

    public function get1ccUpdateOrderMutex(string $orderId) : string
    {
        return Constants::MUTEX_PREFIX_1CC_OPERATION . $orderId;
    }

    // Note:- this code won't work if any other type will be added in promotions
    // since type for coupons are not mandatory since start.
    public function getAppliedCouponValue(array $promotions) {
        $discount = 0;
        if (empty($promotions) === true) {
            return $discount;
        }

        foreach ($promotions as $coupon) {
            if (isset($coupon[OrderOneCCFields::PROMOTIONS_TYPE]) === false ||
                    $coupon[OrderOneCCFields::PROMOTIONS_TYPE] !== OrderOneCCFields::GIFT_CARD) {
                    $discount = $coupon[OrderOneCCFields::PROMOTIONS_VALUE] ?? 0;
                    return $discount;
            }
        }

        return $discount;
    }

    /**
     * @param Order\Entity $order
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    public function validateOrderNotPaidOrRefunded(Order\Entity $order) {

        $order->getValidator()->validateOrderNotPaid();

        $isOrderRefunded = ($order->getStatus() === Constants::ORDER_STATUS_REFUNDED);

        if ($isOrderRefunded === true) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                PublicErrorDescription::GIFT_CARD_ORDER_ALREADY_REFUNDED);
        }

    }

}

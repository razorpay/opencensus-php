<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;


use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout\Monitoring;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Order\OrderMeta;
use RZP\Models\Merchant\Metric;

class GiftCards extends Base\Core
{
    protected $monitoring;
    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();
    }

    public function validateGiftCard(array $input, string $merchantId)
    {
        $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_REQUEST_COUNT);

        $client = $this->getShopifyClientByMerchant($merchantId);

        $giftCard = str_replace(' ', '', $input['gift_card_number']);

        $searchQuery = '/gift_cards/search.json?query=code%3A'.strtolower($giftCard);

        $lastCharacters = str_split($giftCard, 12);

        try {

            $giftCardsData = $client->sendRestApiRequest(
                '',
                'GET',
                $searchQuery
            );

            $giftCardsArray = json_decode($giftCardsData, true);

            $giftCards = $giftCardsArray['gift_cards'];

            if (!empty($giftCards))
            {
                $giftCard = $giftCards[0];

                if($giftCard['disabled_at'] === null)
                {
                    if($giftCard['balance'] > 0)
                    {

                        $date = date("Y-m-d");

                        if($giftCard['expires_on'] === null || $date < $giftCard['expires_on'])
                        {
                            $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_SUCCESS_COUNT);

                            return [
                                'response' => [
                                    'gift_card_promotion' => [
                                        'gift_card_number'          => $input['gift_card_number'],
                                        'balance'         => floatval($giftCard['balance'])*100,
                                        'gift_card_reference_id'  => strval($giftCard['id']),
                                        'allowedPartialRedemption'  => 1
                                    ],
                                ],
                                'status_code' => 200,
                            ];
                        }
                        else
                        {

                            $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                                'error_type' => 'Gift card Expired'
                            ]);

                            return (new Errors)->getGiftCardExpiredResponse();
                        }
                    }
                    else
                    {
                        $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                            'error_type' => 'Gift card has no balance'
                        ]);

                        return (new Errors)->getGiftCardNoBalanceResponse();
                    }
                }
                else
                {
                    $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                        'error_type' => 'Gift card is disabled'
                    ]);

                    return (new Errors)->getGiftCardDisabledResponse();
                }
            }
            else
            {
                $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                    'error_type' => 'Gift card does not exists'
                ]);

                return (new Errors)->getGiftCardDoesNotExistResponse();
            }
        } catch(Exception $e) {
            try{
                if(empty($input['email']))
                {
                    return (new Errors)->emailRequired();
                }
                $searchQuery = '/gift_cards/search.json?query=email%3A'.$input['email'].'+last_characters%3A'.strtolower($lastCharacters[1]);

                $giftCardsData = $client->sendRestApiRequest(
                    '',
                    'GET',
                    $searchQuery
                );

                $giftCardsArray = json_decode($giftCardsData, true);

                $giftCards = $giftCardsArray['gift_cards'];

                if (!empty($giftCards))
                {
                    $giftCard = $giftCards[0];

                    if($giftCard['disabled_at'] === null)
                    {
                        if($giftCard['balance'] > 0)
                        {

                            $date = date("Y-m-d");

                            if($giftCard['expires_on'] === null || $date < $giftCard['expires_on'])
                            {
                                $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_SUCCESS_COUNT);

                                return [
                                    'response' => [
                                        'gift_card_promotion' => [
                                            'gift_card_number'          => $input['gift_card_number'],
                                            'balance'         => floatval($giftCard['balance'])*100,
                                            'gift_card_reference_id'  => strval($giftCard['id']),
                                            'allowedPartialRedemption'  => 1
                                        ],
                                    ],
                                    'status_code' => 200,
                                ];
                            }
                            else
                            {
                                $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                                    'error_type' => 'Gift card Expired'
                                ]);

                                return (new Errors)->getGiftCardExpiredResponse();
                            }
                        }
                        else
                        {
                            $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                                'error_type' => 'Gift card has no balance'
                            ]);

                            return (new Errors)->getGiftCardNoBalanceResponse();
                        }
                    }
                    else
                    {
                        $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                            'error_type' => 'Gift card is disabled'
                        ]);

                        return (new Errors)->getGiftCardDisabledResponse();
                    }
                }
                else
                {
                    $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_VALIDATE_ERROR_COUNT,[
                        'error_type' => 'Gift card does not exists'
                    ]);

                    return (new Errors)->getGiftCardDoesNotExistResponse();
                }
            } catch(\Exception $e) {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_GIFT_CARD_ID);
            }
        }
    }

    public function getShopifyClientByMerchant($merchantId)
    {
        $creds = $this->getShopifyAuthByMerchant($merchantId);

        return new Client($creds);
    }

    public function getShopifyAuthByMerchant($merchantId)
    {
        $config = (new AuthConfig\Core)->getShopify1ccConfig($merchantId);

        return $config;
    }

    public function applyGiftCard($promotion, $order, $payment, $merchantId)
    {
        $client = $this->getShopifyClientByMerchant($merchantId);

        $data = [
            'amount'    =>  floatval('-'.strval($promotion['value']/100)),
        ];

        $body = ['adjustment' => $data];

        $this->monitoring->addTraceCount(Metric::SHOPIFY_APPLY_GIFT_CARD_REQUEST_COUNT);

        try
        {
            $client->sendRestApiRequest(
                json_encode($body),
                'POST',
                '/gift_cards/'.$promotion['reference_id'].'/adjustments.json'
            );

            $promotion['description'] = "applied";

            $this->monitoring->addTraceCount(Metric::SHOPIFY_APPLY_GIFT_CARD_SUCCESS_COUNT);

            return $promotion;
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_APPLY_GIFT_CARD_ERROR_COUNT);

            $receipt = $order->getReceipt();

            if ($receipt !== OneClickCheckout\Constants::SHOPIFY_GC_FAILED_RECEIPT) {

                if (strtolower($payment['method']) !== 'cod') {
                    $paymentId = $payment['id'];

                    $refundData = [
                        'amount' => $payment['amount'],
                    ];

                    $this->monitoring->addTraceCount(Metric::SHOPIFY_ORDER_REFUND_REQUEST_COUNT);

                    try {
                        (new Payment\Service)->refund($paymentId, $refundData);
                        $this->monitoring->addTraceCount(Metric::SHOPIFY_ORDER_REFUND_SUCCESS_COUNT);
                    } catch (\Exception $e) {

                        $this->monitoring->addTraceCount(Metric::SHOPIFY_ORDER_REFUND_ERROR_COUNT);

                        $this->trace->error(
                            TraceCode::SHOPIFY_1CC_GC_FAILURE_REFUND_FAILURE,
                            [
                                'type' => 'gc_failure_place_refund_failed',
                                'order_id' => $order['id'],
                                'payment_id' => $paymentId,
                                'error' => $e->getMessage()
                            ]
                        );
                    }
                }


                $receipt = (new OneClickCheckout\Constants)::SHOPIFY_GC_FAILED_RECEIPT;

                (new Order\Core)->updateReceipt($order, $receipt);
            }

            $promotion['description'] = "invalid";

            return $promotion;
        }
    }

    public function refundGiftCard($promotion, $order, $payment, $merchantId)
    {
        $client = $this->getShopifyClientByMerchant($merchantId);

        $data = [
            'amount'    =>  floatval($promotion['value']/100),
        ];

        $body = ['adjustment' => $data];

        try
        {
            if(isset($promotion['description']) && $promotion['description'] === 'applied')
            {
                $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_REFUND_REQUEST_COUNT);

                $client->sendRestApiRequest(
                    json_encode($body),
                    'POST',
                    '/gift_cards/'.$promotion['reference_id'].'/adjustments.json'
                );

                $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_REFUND_SUCCESS_COUNT);

                $promotion['description'] = "refunded";
            }

            return $promotion;
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_GIFT_CARD_REFUND_ERROR_COUNT);

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_GC_REFUND_FAILURE,
                [
                    'type'     => 'gc_refund_failed',
                    'order_id' => $order['id'],
                    'gc_reference_id' => $promotion['reference_id'],
                    'error'    => $e->getMessage()
                ]
            );
        }
    }
}

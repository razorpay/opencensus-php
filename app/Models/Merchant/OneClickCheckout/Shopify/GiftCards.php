<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Order\OrderMeta;

class GiftCards extends Base\Core
{
    public function validateGiftCard(array $input, string $merchantId)
    {
        $client = $this->getShopifyClientByMerchant($merchantId);

        $giftCard = str_replace(' ', '', $input['gift_card_number']);

        $lastCharacters = str_split($giftCard, 12);

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
                        return (new Errors)->getGiftCardExpiredResponse();
                    }
                }
                else 
                {
                    return (new Errors)->getGiftCardNoBalanceResponse();
                }
            }
            else
            {
                return (new Errors)->getGiftCardDisabledResponse();
            }
        }
        else
        {
            return (new Errors)->getGiftCardDoesNotExistResponse();
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

        try
        {
            $client->sendRestApiRequest(
                json_encode($body),
                'POST',
                '/gift_cards/'.$promotion['reference_id'].'/adjustments.json'
            );

            $promotion['description'] = "applied";

            return $promotion;
        }
        catch (\Exception $e)
        {
            $paymentId = $payment['id'];

            $refundData = [
                'amount' => $payment['amount'],
            ];

            try
            {
                (new Payment\Service)->refund($paymentId, $refundData);
            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::SHOPIFY_1CC_GC_FAILURE_REFUND_FAILURE,
                    [
                        'type'     => 'gc_failure_place_refund_failed',
                        'order_id' => $order['id'],
                        'payment_id' => $paymentId,
                        'error'    => $e->getMessage()
                    ]
                );
            }

            $receipt = (new OneClickCheckout\Constants)::SHOPIFY_GC_FAILED_RECEIPT;

            (new Order\Core)->updateReceipt($order, $receipt);

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
                $client->sendRestApiRequest(
                    json_encode($body),
                    'POST',
                    '/gift_cards/'.$promotion['reference_id'].'/adjustments.json'
                );
            }
        }
        catch (\Exception $e)
        {
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

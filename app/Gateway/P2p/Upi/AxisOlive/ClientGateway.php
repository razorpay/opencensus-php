<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Models\P2p\Client\Entity;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\AxisOlive\Sdk;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\ClientAction;
use RZP\Models\Upi\Turbo\RewardProcessor\Base as Rewards;

/**
 * Class file responsible for client gateway interaction
 * Class ClientGateway
 *
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class ClientGateway extends Gateway implements Contracts\ClientGateway
{
    protected $actionMap = ClientAction::MAP;

    public function getGatewayConfig(Response $response)
    {
        $request = $this->initiateS2sRequest(ClientAction::GET_GATEWAY_CONFIG);

        $request->merge([
                Fields::MERCHANT_ID              => $this->getMerchantId(),
                Fields::MERCHANT_CHANNEL_ID      => $this->getMerchantChannelId(),
                Fields::SUB_MERCHANT_ID          => $this->getSubMerchantId(),
                Fields::MCC_CODE                 => $this->getMerchantCategoryCode(),
                Fields::TIMESTAMP                => $this->getTimeStamp(),
                Fields::MOBILE_NUMBER            => '91' . substr($this->input[CustomerEntity::CONTACT] , -10),
        ]);

        $gatewayResponse = $this->sendGatewayRequestAndParseResponse($request);

        $response->setData([
               Entity::GATEWAY_CONFIG => [
                   Fields::MERCHANT_ID             => $this->getMerchantId(),
                   Fields::MERCHANT_CHANNEL_ID     => $this->getMerchantChannelId(),
                   Fields::SUB_MERCHANT_ID         => $this->getSubMerchantId(),
                   Fields::MCC_CODE                => $this->getMerchantCategoryCode(),
               ],

               Entity::TOKEN =>[
                   Fields::GATEWAY_TOKEN => $gatewayResponse[Fields::DATA][Fields::DATA][Fields::MERCHANT_AUTH_TOKEN],
               ],
        ]);
    }

    public function checkCustomerRewardEligibility($input)
    {
        $request = $this->initiateS2sRequest(ClientAction::GET_CUSTOMER_REWARD_ELIGIBILITY);

        $request->merge([
                            Rewards::CONTACT             => $input[Rewards::CONTACT],
                            Rewards::ACTION              => $input[Rewards::ACTION],
                            Rewards::REWARD_PARTNER_NAME => $input[Rewards::REWARD_PARTNER_NAME],
                            Fields::MERCHANT_ID          => $input[Fields::MERCHANT_ID],
                        ]);

        //Amount will be present only in case of payment reward eligibility check
        if (empty($input[Rewards::AMOUNT]) === false)
        {
            $request->merge([
                                Rewards::AMOUNT => $input[Rewards::AMOUNT]
                            ]);
        }
        $gatewayResponse = $this->sendGatewayRequestAndParseResponse($request);

        return $gatewayResponse;
    }

    public function allotCustomerReward($input)
    {
        $request = $this->initiateS2sRequest(ClientAction::ALLOT_CUSTOMER_REWARD);

        $request->merge([
                            Rewards::CONTACT             => $input[Rewards::CONTACT],
                            Rewards::ACTION              => $input[Rewards::ACTION],
                            Rewards::IDEMPOTENCY_ID      => $input[Rewards::IDEMPOTENCY_ID],
                            Rewards::REWARD_PARTNER_NAME => $input[Rewards::REWARD_PARTNER_NAME],
                            Fields::MERCHANT_ID          => $input[Fields::MERCHANT_ID],
                        ]);
        //Amount will be present only in case of payment reward allotment
        if (empty($input[Rewards::AMOUNT]) === false)
        {
            $request->merge([
                                Rewards::AMOUNT => $input[Rewards::AMOUNT]
                            ]);
        }
        $gatewayResponse = $this->sendGatewayRequestAndParseResponse($request);

        return $gatewayResponse;
    }
}

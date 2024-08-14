<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Models\P2p\Base\Libraries\ContextMap;
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

        $route = Fields::AXIS_3P;
        $version = $this->request->header(ContextMap::X_RAZORPAY_PSP_SDK_VERSION);
        if (!is_null($version) && $version !== '') {

            $versionParts = explode('.', $version);

            if (isset($versionParts[0])) {

                $majorVersion = (int)$versionParts[0];
                // Check if the major version is greater than or equal to 2
                if ($majorVersion >= 2) {
                    $route = Fields::AXIS_2P;
                }
            }
        }

        $merch_id = $this->getMerchantId();
        if($route == Fields::AXIS_2P) {
            $merch_id = $this->app['basicauth']->getMerchantId();
        }

        $request->merge([
                Fields::MERCHANT_ID              => $merch_id,
                Fields::MERCHANT_CHANNEL_ID      => $this->getMerchantChannelId(),
                Fields::SUB_MERCHANT_ID          => $this->getSubMerchantId(),
                Fields::MCC_CODE                 => $this->getMerchantCategoryCode(),
                Fields::TIMESTAMP                => $this->getTimeStamp(),
                Fields::MOBILE_NUMBER            => '91' . substr($this->input[CustomerEntity::CONTACT] , -10),
                Fields::ROUTE                    => $route
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

        // The customer id will be passed in the response for Axis2P Integration only
        if($route == Fields::AXIS_2P)
        {
            $data = $response->data();

            if ($data instanceof ArrayBag)
            {
                $data->putMany([
                    Entity::CUSTOMER => [
                        Fields::ID => $gatewayResponse[Fields::DATA][Fields::DATA][Fields::CUSTOMER_REFERENCE]
                    ]
                ]);

                $response->setData($data->all());
            }
        }
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

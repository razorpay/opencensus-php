<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\P2p\Base\Libraries\ContextMap;
use RZP\Models\P2p\Client\Entity;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\AxisOlive\Sdk;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
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

        if($route == Fields::AXIS_3P) {
            if(!isset($this->input[CustomerEntity::CONTACT]) || empty($this->input[CustomerEntity::CONTACT]))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_D2C_MANDATORY_FIELD_MISSING,
                    CustomerEntity::CONTACT,
                    null);
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
                Fields::ROUTE                    => $route,
        ]);

        if(isset($this->input[CustomerEntity::CONTACT]) && !empty($this->input[CustomerEntity::CONTACT])) {
            $request->merge([
                Fields::MOBILE_NUMBER => '91' . substr($this->input[CustomerEntity::CONTACT] , -10)
            ]);
        }

        if(isset($this->input[Fields::CUSTOMER_ID]) && !empty($this->input[Fields::CUSTOMER_ID])) {
            $request->merge([
                Fields::CUSTOMER_ID              => $this->input[Fields::CUSTOMER_ID]
            ]);
        }

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
                    ],
                    Entity::CERT => $gatewayResponse[Fields::DATA][Fields::DATA][Fields::PUBLIC_KEY]
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

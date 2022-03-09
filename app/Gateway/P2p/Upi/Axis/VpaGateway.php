<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;
use RZP\Gateway\Upi\Base\Vpa;
use RZP\Models\P2p\Vpa\Bank;
use RZP\Models\P2p\Vpa\Entity;
use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Vpa\Credentials;
use RZP\Models\P2p\Device\DeviceToken;
use RZP\Gateway\P2p\Upi\Axis\Actions\VpaAction;
use RZP\Models\P2p\Beneficiary\Entity as Beneficiary;
use RZP\Gateway\P2p\Upi\Axis\Transformers\VpaTransformer;

class VpaGateway extends Gateway implements Contracts\VpaGateway
{
    protected $actionMap = VpaAction::MAP;

    public function initiateAdd(Response $response)
    {
        $request = $this->initiateSdkRequest(VpaAction::VPA_AVAILABILITY);

        $customerVpa = $this->usernameToAddress($this->input->get(Entity::USERNAME));

        $request->merge([
            Fields::CUSTOMER_VPA    => $customerVpa,
        ]);

        $response->setRequest($request);
    }

    public function add(Response $response)
    {
        $this->handleInputSdk();

        $callback = $this->input->get(Entity::CALLBACK);

        $bankAccount = $this->input->get(Entity::BANK_ACCOUNT);

        switch ($callback[Fields::ACTION])
        {
            case VpaAction::VPA_AVAILABILITY:
                $accRefId = $bankAccount[Entity::GATEWAY_DATA][Fields::REFERENCE_ID];
                $address  = $this->usernameToAddress($this->input->get(Entity::USERNAME));

                $this->handleVpaAvailability($response, [
                    Fields::CUSTOMER_VPA            => $address,
                    Fields::ACCOUNT_REFERENCE_ID    => $accRefId,
                ],[
                    Entity::BANK_ACCOUNT_ID         => $bankAccount->get('id'),
                    Entity::USERNAME                => $this->input->get(Entity::USERNAME),
                ]);

                break;

            case VpaAction::LINK_ACCOUNT:
                $this->handleLinkAccount($response, $bankAccount);

                break;
            default:
                throw $this->p2pGatewayException(ErrorMap::INVALID_CALLBACK, $callback);
        }
    }

    public function assignBankAccount(Response $response)
    {
        $bankAccount = $this->input->get(Entity::BANK_ACCOUNT);

        $vpa         = $this->input->get(Entity::VPA);

        if ($this->inputSdk()->isEmpty() === false)
        {
            $this->handleLinkAccount($response, $bankAccount);

            $response->setData([
                Entity::VPA => [
                    Entity::ID  => $vpa[Entity::ID],
                ],
                Entity::BANK_ACCOUNT => [
                    Entity::ID  => $bankAccount[Entity::ID],
                ],
                DeviceToken\Entity::DEVICE_TOKEN => [
                    Entity::ID                      => $this->getContextDeviceToken()->get(DeviceToken\Entity::ID),
                    Entity::GATEWAY_DATA            => [
                        DeviceToken\Entity::EXPIRE_AT   => $this->getCurrentTimestamp(),
                    ],
                ],
            ]);

            return;
        }

        $accRefId    = $bankAccount[Entity::GATEWAY_DATA][Fields::REFERENCE_ID];

        $linkAccount = [
            Fields::CUSTOMER_VPA            => $vpa[Entity::ADDRESS],
            Fields::ACCOUNT_REFERENCE_ID    => $accRefId,
        ];

        $callback = [
            Entity::BANK_ACCOUNT_ID         => $bankAccount->get('id'),
            Entity::USERNAME                => $vpa[Entity::USERNAME],
        ];

        $request = $this->initiateSdkRequest(VpaAction::LINK_ACCOUNT);

        $request->merge($linkAccount);

        $request->setCallback($callback);

        $response->setRequest($request);
    }

    public function initiateCheckAvailability(Response $response)
    {
        $this->initiateAdd($response);
    }

    public function checkAvailability(Response $response)
    {
        $sdk = $this->handleInputSdk();

        if ($this->isVpaAvailable($sdk))
        {
            // the vpa given by the user is free and can be linked to an account.
            // so returning successful response from here

            $response->setData([
                Entity::AVAILABLE     => true,
                Entity::USERNAME      => $this->input->get(Entity::USERNAME),
                Entity::HANDLE        => $this->context->handleCode(),
            ]);

            return $response;
        }

        // vpa given by the user not free(already assigned to someone)
        // so returning a list of vpa suggestions

        $vpaSuggestions = array_map(
            function($item)
            {
                return explode(Entity::AEROBASE, $item)[0];
            }, $sdk->get(Fields::VPA_SUGGESTIONS));

        $response->setData([
            Entity::AVAILABLE         => false,
            Entity::USERNAME          => $this->input->get(Entity::USERNAME),
            Entity::HANDLE            => $this->context->handleCode(),
            Entity::SUGGESTIONS       => $vpaSuggestions
        ]);
    }

    public function delete(Response $response)
    {
        $vpa         = $this->input->get(Entity::VPA);
        $defaultVpa  = $this->input->get(Entity::DEFAULT);

        $request = $this->initiateS2sRequest(VpaAction::DELETE_VPA);

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
            Fields::CUSTOMER_VPA            => $vpa[Entity::ADDRESS],
            Fields::CUSTOMER_PRIMARY_VPA    => $defaultVpa[Entity::ADDRESS],
        ]);

        $s2s = $this->sendS2sRequest($request);

        $response->setData([
            Entity::VPA => [
                Entity::ID  => $vpa[Entity::ID],
            ],
            Entity::SUCCESS => true,
        ]);
    }

    public function setDefault(Response $response)
    {
        $vpa         = $this->input->get(Entity::VPA);
        $defaultVpa  = $this->input->get(Entity::DEFAULT);

        $request = $this->initiateS2sRequest(VpaAction::ADD_DEFAULT);

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
            Fields::CUSTOMER_VPA            => $vpa[Entity::ADDRESS],
            Fields::CUSTOMER_PRIMARY_VPA    => $this->getCustomerPrimaryVpa(),
        ]);

        $response->setData([
            Entity::VPA => [
                Entity::ID  => $vpa[Entity::ID],
            ],
            Entity::SUCCESS => true,
        ]);
    }

    public function validate(Response $response)
    {
        $username = $this->input->get(Entity::USERNAME);

        $handle = $this->input->get(Entity::HANDLE);

        $customerVpa = $this->usernameToAddress($username, $handle);

        $request = $this->initiateS2sRequest(VpaAction::VALIDATE_VPA);

        $request->merge([
            Fields::CUSTOMER_VPA => $customerVpa,
        ]);

        $s2s = $this->sendS2sRequest($request);

        if ($this->toBoolean($s2s[Fields::PAYLOAD][Fields::IS_CUSTOMER_VPA_VALID]) === false)
        {
            $response->setData([
                Beneficiary::TYPE       => Entity::VPA,
                Beneficiary::VALIDATED  => false,
                Entity::HANDLE          => $handle,
                Entity::USERNAME        => $username,
            ]);

            return;
        }

        $vpa = new VpaTransformer($s2s[Fields::PAYLOAD]);

        $response->setData([
            Beneficiary::TYPE           => Entity::VPA,
            Beneficiary::VALIDATED      => true,
            Entity::HANDLE              => $vpa->transformHandle(),
            Entity::USERNAME            => $vpa->transformUsername(),
            Entity::BENEFICIARY_NAME    => $vpa->transformBeneficiaryName(),
            Entity::GATEWAY_DATA        => $vpa->transformGatewayData(),
        ]);

        return;
    }

    public function handleBeneficiary(Response $response)
    {
        $payeeVpa = $this->usernameToAddress($this->input->get(Entity::USERNAME), $this->input->get(Entity::HANDLE));

        if (($this->input->get(Beneficiary::BLOCKED) === true) or
            ($this->input->get(Beneficiary::SPAMMED) === true))
        {
            $request = $this->initiateS2sRequest(VpaAction::BLOCK_VPA);

            $upi = $this->input->get(Transaction\Entity::UPI);

            $request->merge([
                Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                Fields::PAYEE_VPA               => $payeeVpa,
                Fields::SHOULD_BLOCK            => $this->input->get(Beneficiary::BLOCKED) ? 'true' : 'false',
                Fields::SHOULD_SPAM             => $this->input->get(Beneficiary::SPAMMED) ? 'true' : 'false',
                Fields::UPI_REQUEST_ID          => $upi[Transaction\UpiTransaction\Entity::NETWORK_TRANSACTION_ID],
            ]);
        }
        else
        {
            $request = $this->initiateS2sRequest(VpaAction::UNBLOCK_VPA);

            $request->merge([
                Fields::MERCHANT_CUSTOMER_ID => $this->getMerchantCustomerId(),
                Fields::PAYEE_VPA            => $payeeVpa,
            ]);
        }

        $s2s = $this->sendS2sRequest($request);

        $this->handleGatewayResponseCode($s2s[Fields::PAYLOAD]);

        $transformer = new VpaTransformer($s2s[Fields::PAYLOAD]);
        $transformer->put(Fields::CUSTOMER_VPA, $payeeVpa);
        $transformer->put(Beneficiary::BLOCKED, $this->input->get(Beneficiary::BLOCKED));
        $transformer->put(Beneficiary::SPAMMED, $this->input->get(Beneficiary::SPAMMED));

        $output = $transformer->transformBeneficiary();

        $response->setData($output);
    }

    public function fetchAll(Response $response)
    {
        if (empty($this->input->get(Beneficiary::BLOCKED)) === true)
        {
            throw $this->p2pGatewayException(ErrorMap::NOT_AVAILABLE);
        }

        $request = $this->initiateS2sRequest(VpaAction::LIST_BLOCKED);

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID => $this->getMerchantCustomerId(),
            Fields::LIMIT                => 100,
            Fields::OFFSET               => 0,
        ]);

        $s2s = $this->sendS2sRequest($request);

        $output[Entity::DATA] = [];

        foreach ($s2s[Fields::PAYLOAD][Fields::BLOCKED_VPAS] as $blockedVpa)
        {
            $payeeVpa = $blockedVpa[Fields::PAYEE_VPA];

            $transformer = new VpaTransformer($blockedVpa);
            $transformer->put(Fields::CUSTOMER_VPA, $payeeVpa);
            $transformer->put(Beneficiary::BLOCKED, true);
            $transformer->put(Beneficiary::SPAMMED, null);

            $output[Entity::DATA][] = $transformer->transformBeneficiary();
        }

        $response->setData($output);
    }

    protected function handleVpaAvailability(
        Response $response,
        array $linkAccount = null,
        array $callback = [])
    {
        $sdk = $this->handleInputSdk();

        if ($this->toBoolean($sdk[Fields::AVAILABLE]) === false)
        {
            throw $this->p2pGatewayException(ErrorMap::NOT_AVAILABLE);
        }

        // It was just to check availability
        if (is_null($linkAccount) === true)
        {
            $response->setData([
                Entity::SUCCESS => true
            ]);
        }

        $request = $this->initiateSdkRequest(VpaAction::LINK_ACCOUNT);

        $request->merge($linkAccount);

        $request->setCallback($callback);

        $response->setRequest($request);
    }

    protected function handleLinkAccount(Response $response, $bankAccount)
    {
        $sdk = $this->handleInputSdk();

        $this->handleGatewayResponseCode($sdk);

        $vpa = new VpaTransformer($sdk->toArray());

        $response->setData([
            Entity::VPA             => $vpa->transform(),
            Entity::BANK_ACCOUNT    => [
                Entity::ID          => $bankAccount->get('id')
            ]
        ]);
    }

    protected function usernameToAddress(string $username, string $handle = null)
    {
        $hand =  $handle ?? $this->context->handleCode();

        // Since Axis bank is not able to handle uppercase letters
        return strtolower($username) . '@' . $hand;
    }

    protected function isVpaAvailable($content) :bool
    {
        return $content[Fields::AVAILABLE] === 'true';
    }

    protected function getCustomerPrimaryVpa()
    {
        $vpa = $this->input->get(Entity::VPA);
        $default = $this->input->get(Entity::DEFAULT);

        $customerContact = $this->getContextDevice()->get('contact');

        // If VPA is customer phone number
        if (substr($customerContact, -10) === $vpa[Entity::USERNAME])
        {
            return $vpa[Entity::ADDRESS];
        }

        return $default[Entity::ADDRESS];
    }
}


<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Vpa\Bank;
use RZP\Models\P2p\Vpa\Entity;
use RZP\Models\P2p\Vpa\Credentials;
use RZP\Gateway\P2p\Upi\Axis\Actions\VpaAction;
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
                $this->throwP2pGatewayException();
        }
    }

    public function assignBankAccount(Response $response)
    {

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

        $vpaSuggestions = $sdk->get(Fields::VPA_SUGGESTIONS);

        $response->setData([
            Entity::AVAILABLE         => false,
            Entity::USERNAME          => $this->input->get(Entity::USERNAME),
            Entity::HANDLE            => $this->context->handleCode(),
            Entity::SUGGESTIONS       => $vpaSuggestions
        ]);
    }

    public function delete(Response $response)
    {

    }

    protected function handleVpaAvailability(
        Response $response,
        array $linkAccount = null,
        array $callback = [])
    {
        $sdk = $this->handleInputSdk();

        if ($this->toBoolean($sdk[Fields::AVAILABLE]) === false)
        {
            $this->throwP2pGatewayException();
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

        $vpa = new VpaTransformer($sdk->toArray());

        $response->setData([
            Entity::VPA             => $vpa->transform(),
            Entity::BANK_ACCOUNT    => [
                Entity::ID          => $bankAccount->get('id')
            ]
        ]);
    }

    protected function usernameToAddress(string $username)
    {
        return $username . '@' .$this->context->handleCode();
    }

    protected function isVpaAvailable($content) :bool
    {
        return $content[Fields::AVAILABLE] === 'true';
    }
}


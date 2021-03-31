<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Vpa;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\BankAccount\Bank;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Models\P2p\Base\Libraries\Card;
use RZP\Models\P2p\BankAccount\Credentials;
use RZP\Gateway\P2p\Upi\Axis\Transformers\BankAccountTransformer;
use RZP\Gateway\P2p\Upi\Axis\Actions\BankAccountAction as Action;

class BankAccountGateway extends Gateway implements Contracts\BankAccountGateway
{
    protected $actionMap = Action::MAP;

    public function initiateRetrieve(Response $response)
    {
        $request = $this->initiateSdkRequest(Action::GET_ACCOUNTS);

        $bank = $this->input->get(Entity::BANK);

        $request->merge([
            Fields::BANK_CODE   => $bank->get(Bank\Entity::UPI_IIN),
        ]);

        $response->setRequest($request);
    }

    public function retrieve(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $bankAccounts = [];

        foreach ($sdk[Fields::ACCOUNTS] as $account)
        {
            $bankAccount = new BankAccountTransformer($account);

            $bankAccount->put(Fields::VPA_SUGGESTIONS, $sdk[Fields::VPA_SUGGESTIONS] ?? []);

            $bankAccounts[] = $bankAccount->transform();
        }

        $response->setData([
            Entity::BANK_ID       => $this->input->get('bank')->get('id'),
            Entity::BANK_ACCOUNTS => $bankAccounts
        ]);
    }

    public function initiateSetUpiPin(Response $response)
    {
        $action = $this->input->get(Entity::ACTION);
        $vpa    = $this->input->get(Vpa\Entity::VPA);
        $bankAccount = $this->input->get(Entity::BANK_ACCOUNT);

        $sdkAction  = Action::CHANGE_MPIN;
        $sdkRequest = [
            Fields::ACCOUNT_REFERENCE_ID => $bankAccount[Entity::GATEWAY_DATA][Fields::REFERENCE_ID],
            Fields::UPI_REQUEST_ID       => $this->getUpiRequestId(),
        ];

        switch ($action)
        {
            case Credentials::SET:
            case Credentials::RESET:
                $card = $this->input->get(Card::CARD);

                $sdkAction  = Action::SET_MPIN;

                $sdkRequest = array_merge($sdkRequest, [
                    Fields::CUSTOMER_VPA    => $vpa->get(Vpa\Entity::ADDRESS),
                    Fields::CARD            => (string) $card[Card::LAST6],
                    Fields::EXPIRY          => str_pad($card[Card::EXPIRY_MONTH] . $card[Card::EXPIRY_YEAR],
                                                       4, 0, STR_PAD_LEFT)
                ]);
        }

        $request = $this->initiateSdkRequest($sdkAction);

        $request->merge($sdkRequest);

        $response->setRequest($request);
    }

    public function setUpiPin(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $this->handleGatewayResponseCode($sdk);

        $bankAccount = $this->input->get(Entity::BANK_ACCOUNT);

        $response->setData([
            Entity::ID          => $bankAccount->get(Entity::ID)
        ]);
    }

    public function initiateFetchBalance(Response $response)
    {
        $request = $this->initiateSdkRequest(Action::CHECK_BALANCE);

        $bankAccount = $this->input->get(Entity::BANK_ACCOUNT);

        $request->merge([
            Fields::ACCOUNT_REFERENCE_ID => $bankAccount[Entity::GATEWAY_DATA][Fields::REFERENCE_ID],
            Fields::UPI_REQUEST_ID       => $this->getUpiRequestId(),
        ]);

        $response->setRequest($request);
    }

    public function fetchBalance(Response $response)
    {
        $sdk = $this->handleInputSdk();

        $this->handleGatewayResponseCode($sdk);

        $bankAccount = $this->input->get('bank_account');

        $response->setData([
            Entity::ID  => $bankAccount->get('id'),
            Entity::RESPONSE      => [
                Entity::BALANCE   => $this->toPaisa($sdk->get(Fields::BALANCE)),
                Entity::CURRENCY  => 'INR',
            ]
        ]);
    }

    public function retrieveBanks(Response $response)
    {
        $request = $this->initiateS2sRequest(Action::RETRIEVE_BANKS);

        $request->merge([]);

        $s2s = $this->sendS2sRequest($request);

        $output[Bank\Entity::BANKS] = [];

        foreach ($s2s[Fields::BANKS] as $bank)
        {
            $transformer = new BankAccountTransformer($bank, $this->action);

            $output[Bank\Entity::BANKS][] = $transformer->transformBanks($this->context->handleCode());
        }

        $response->setData($output);

        return $response;
    }

}


<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Customer\Token;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function fetchTokens(array $input) : array
    {
        $result = $this->repo->subscription_registration->fetchRecurringTokensByMerchant(
            $this->merchant,
            $input);

        return $result->toArrayPublic();
    }

    public function listAuthLinks(array $input) : array
    {
        $input[Invoice\Entity::ENTITY_TYPE] = Constants\Entity::SUBSCRIPTION_REGISTRATION;

        $invoices = $this->repo->invoice
                         ->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function createAuthLinks(array $input) : array
    {
        $invoice = $this->core->createAuthLink($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetchAuthLink(string $id, array $input) : array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant(
            $id,
            $this->merchant,
            $input
        );

        return (new ViewDataSerializer($invoice))->serializeForApi();
    }

    public function fetchSingleToken(String $id, array $input) : array
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return (new Token\ViewDataSerializer($token))->serializeForSubscriptionRegistration();
    }

    public function deleteSingleToken(String $id) : array
    {
        return $this->deleteTokenForMerchant($id);
    }

    public function chargeToken(String $id, array $input) : array
    {
        return $this->core->chargeToken($id, $input, $this->merchant);
    }

    protected function deleteTokenForMerchant($tokenId) : array
    {
        $token = $this->repo->token->findByPublicIdAndMerchant($tokenId, $this->merchant);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        $token = $this->repo->token->deleteOrFail($token);

        if ($token === null)
        {
            return ['deleted' => true];
        }

        return $token->toArrayPublic();
    }
}

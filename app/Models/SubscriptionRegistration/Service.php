<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Invoice;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->setUser();

        $this->core = new Core();
    }

    public function fetchTokens(array $input)
    {
        $result = $this->repo->subscription_registration->fetchTokensByMerchant(
            $this->merchant,
            $input);

        return $result->toArrayPublic();
    }

    public function listAuthLinks(array $input)
    {
        $input[Invoice\Entity::ENTITY_TYPE] = Constants\Entity::SUBSCRIPTION_REGISTRATION;

        $invoices = $this->repo->invoice
                         ->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function createAuthLinks(array $input)
    {
        $invoice = $this->core->createAuthLink($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetchAuthLink(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            $this->userId,
            $this->userRole,
            $input
        );

        return (new ViewDataSerializer($invoice))->serializeForApi();
    }

    public function fetchSingleToken(String $id, array $input)
    {
        return $this->repo->token->findByPublicIdAndMerchant($id, $this->merchant, $input);
    }

    public function deleteSingleToken(String $id)
    {
        return $this->deleteTokenForMerchant($id);
    }

    protected function setUser()
    {
        $dashboardHeaders = $this->app['basicauth']->getDashboardHeaders();

        $this->userId   = $dashboardHeaders['user_id'] ?? null;

        $this->userRole = $dashboardHeaders['user_role'] ?? null;
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

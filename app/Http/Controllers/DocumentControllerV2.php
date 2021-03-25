<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity as Entity;

class DocumentControllerV2 extends Controller
{

    public function postStakeHolderDocumentsByPartner(string $accountId, string $stakeholderId)
    {
        $input = Request::all();

        return $this->service(Entity::MERCHANT_DOCUMENT)->postDocumentsByPartner($accountId, 'stakeholder', $stakeholderId, $input);
    }

    public function getStakeHolderDocuments(string $accountId, string $stakeholderId)
    {
        return $this->service(Entity::MERCHANT_DOCUMENT)->getDocuments($accountId, 'stakeholder', $stakeholderId);
    }

    public function postAccountDocumentsByPartner(string $accountId)
    {
        $input = Request::all();

        return $this->service(Entity::MERCHANT_DOCUMENT)->postDocumentsByPartner($accountId, 'merchant', $accountId, $input);
    }

    public function getAccountDocuments(string $accountId)
    {
        return $this->service(Entity::MERCHANT_DOCUMENT)->getDocuments($accountId, 'merchant', $accountId);
    }

}

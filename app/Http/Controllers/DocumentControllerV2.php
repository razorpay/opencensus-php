<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity as Entity;
use RZP\Constants\HyperTrace;
use RZP\Trace\Tracer;

class DocumentControllerV2 extends Controller
{

    public function postStakeHolderDocumentsByPartner(string $accountId, string $stakeholderId)
    {
        $input = Request::all();

        return Tracer::inspan(['name' => HyperTrace::POST_STAKEHOLDER_DOCUMENTS], function () use ($accountId, $stakeholderId, $input) {

            return $this->service(Entity::MERCHANT_DOCUMENT)->postDocumentsByPartner($accountId, 'stakeholder', $stakeholderId, $input);
        });
    }

    public function getStakeHolderDocuments(string $accountId, string $stakeholderId)
    {
        return Tracer::inspan(['name' => HyperTrace::GET_STAKEHOLDER_DOCUMENTS], function () use ($accountId, $stakeholderId) {

            return $this->service(Entity::MERCHANT_DOCUMENT)->getDocuments($accountId, 'stakeholder', $stakeholderId);
        });
    }

    public function postAccountDocumentsByPartner(string $accountId)
    {
        $input = Request::all();

        return Tracer::inspan(['name' => HyperTrace::POST_ACCOUNTS_DOCUMENTS], function () use ($accountId, $input) {

            return $this->service(Entity::MERCHANT_DOCUMENT)->postDocumentsByPartner($accountId, 'merchant', $accountId, $input);
        });
    }

    public function getAccountDocuments(string $accountId)
    {
        return Tracer::inspan(['name' => HyperTrace::GET_ACCOUNTS_DOCUMENTS], function () use ($accountId) {

            return $this->service(Entity::MERCHANT_DOCUMENT)->getDocuments($accountId, 'merchant', $accountId);
        });
    }

}

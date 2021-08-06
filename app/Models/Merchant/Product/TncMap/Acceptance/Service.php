<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Product\TncMap;

class Service extends Product\Service
{
    public function __construct()
    {
        parent::__construct();
    }

    public function fetchTnc(string $merchantId): array
    {
        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $tncMap = $this->core()->fetchTnc(Product\Name::ALL);

        $merchantTncAcceptance = $this->core()->fetchMerchantAcceptance($merchant);

        return $this->formatFetchResponse($tncMap, $merchantTncAcceptance);
    }

    public function acceptTnc(string $merchantId, $input): array
    {
        (new Validator)->validateInput('create', $input);

        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $tncMap = $this->core()->fetchTnc(Product\Name::ALL);

        $merchantTncAcceptance = $this->core()->fetchMerchantAcceptance($merchant, $tncMap->getProductName());

        if(empty($merchantTncAcceptance) === true)
        {
            $merchantTncAcceptance = $this->core()->acceptTnc($merchant, $tncMap);
        }

        return $this->formatFetchResponse($tncMap, $merchantTncAcceptance);
    }

    public function formatFetchResponse(TncMap\Entity $tncMap, $merchantTncAcceptance): array
    {
        $response = $tncMap->toArrayPublic();

        if (empty($merchantTncAcceptance) === true)
        {
            $response[Constants::ACCEPTED] = false;

        }
        else
        {
            $response[Constants::ACCEPTED] = true;

            $response[Constants::ACCEPTED_AT] = $merchantTncAcceptance->getAcceptedAt();

            $response[Entity::ACCEPTED_CHANNEL] = $merchantTncAcceptance->getAcceptedChannel();
        }

        $response[TncMap\Entity::ID] = $tncMap->getPublicId();

        return $response;
    }
}

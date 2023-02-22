<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Product\TncMap;
use RZP\Models\Merchant\Product\BusinessUnit\Constants as BusinessUnit;

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

        $ip = (isset($input[Constants::IP]) === true) ? $input[Constants::IP] : null;

        if(empty($merchantTncAcceptance) === true)
        {
            $merchantTncAcceptance = $this->core()->acceptTnc($merchant, $tncMap, $ip);
        }

        return $this->formatFetchResponse($tncMap, $merchantTncAcceptance);
    }

    public function fetchProductConfigTnc(string $productName, Merchant\Entity $merchant): array
    {
        $merchantTncAcceptance = $this->core()->fetchMerchantAcceptanceViaBU($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$productName]);

        return $this->formatFetchTnCResponse($merchantTncAcceptance);
    }

    public function acceptProductConfigTnc(string $productName, Merchant\Entity $merchant, string $ip = null): array
    {
        $tncMap = (new TncMap\Core())->fetchTncForBU(BusinessUnit::PRODUCT_BU_MAPPING[$productName]);

        $merchantTncAcceptance = $this->core()->fetchMerchantAcceptanceViaBU($merchant, BusinessUnit::PRODUCT_BU_MAPPING[$productName]);

        if(empty($merchantTncAcceptance) === true)
        {
            $merchantTncAcceptance = $this->core()->acceptTnc($merchant, $tncMap, $ip);
        }

        return $this->formatFetchTnCResponse($merchantTncAcceptance);
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

    public function formatFetchTnCResponse(Entity $merchantTncAcceptance): array
    {
        $response = [];

        $response[Entity::ID] = $merchantTncAcceptance->getPublicId();

        $response[Constants::ACCEPTED] = true;

        $response[Constants::ACCEPTED_AT] = $merchantTncAcceptance->getAcceptedAt();

        return $response;
    }
}

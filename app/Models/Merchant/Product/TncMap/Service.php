<?php

namespace RZP\Models\Merchant\Product\TncMap;

use Cache;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Merchant\Product\Util;
use RZP\Models\Merchant\Product\BusinessUnit\Constants as BusinessUnit;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($input)
    {
        return $this->core()->create($input);
    }

    public function update($id, $input)
    {
        $tnc = $this->repo->tnc_map->findOrFailPublic($id);

        $tnc = $this->core()->update($tnc, $input);

        return $tnc;
    }

    public function fetch(string $id)
    {
        return $this->core()->fetch($id);
    }

    public function fetchMultiple($input)
    {
        (new Validator)->validateInput('fetch', $input);

        return $this->core()->fetchAll($input);
    }

    public function fetchTncForBU(string $businessUnit): array
    {
        $validbusinessUnit = (in_array($businessUnit, BusinessUnit::VALID_BUSINESS, true) === true);

        if ($validbusinessUnit === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME, null, ['valid_product_names' => BusinessUnit::VALID_BUSINESS]);
        }

        $tncMap = $this->core()->fetchTncForBU($businessUnit);

        return $this->formatFetchResponse($tncMap);
    }

    public function formatFetchResponse(Entity $tncMap): array
    {
        $response[Constants::ENTITY] = $tncMap->getEntity();

        $response[Entity::PRODUCT_NAME] = $tncMap->getBusinessUnit();

        $response[Entity::ID] = $tncMap->getPublicId();

        $response[Util\Constants::TNC] = $tncMap->getContent();

        $response[Constants::LAST_PUBLISHED_AT] = $tncMap->getUpdatedAt();

        return $response;
    }
}

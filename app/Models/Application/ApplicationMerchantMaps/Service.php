<?php

namespace RZP\Models\Application\ApplicationMerchantMaps;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Application\ApplicationMerchantMaps;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new ApplicationMerchantMaps\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_CREATE_MERCHANT_MAPPING, $input);

        $this->repo->application->findOrFailPublic($input[Entity::APP_ID]);

        $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $app = $this->core->create($input);

        return $app->toArrayPublic();
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function update(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_UPDATE_MERCHANT_MAPPING, $input);

        /** @var Entity $app */
        $merchantMapping = $this->repo->application_merchant_mapping->getAppMerchantMap(
                            $input[Entity::APP_ID], $input[Entity::MERCHANT_ID]);

        if ($merchantMapping === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MERCHANT_APP_MAPPING_DOES_NOT_EXIST,
                null,
                $input
            );
        }

        $merchantMapping = $this->core->update($merchantMapping, $input);

        return $merchantMapping->toArrayPublic();
    }

    /**
     * @param string $id
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function get(string $id, array $inout)
    {
        $this->repo->merchant->findOrFailPublic($id);

        $appList = $this->core->get($id, $inout);

        return $appList;
    }
}

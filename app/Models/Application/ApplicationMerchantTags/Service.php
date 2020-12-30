<?php

namespace RZP\Models\Application\ApplicationMerchantTags;

use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Application\ApplicationMerchantTags;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new ApplicationMerchantTags\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_CREATE_MERCHANT_TAG, $input);

        $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $merchantTag = $this->core->create($input);

        return $merchantTag->toArrayPublic();
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function update(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_UPDATE_MERCHANT_TAG, $input);

        /** @var Entity $app */
        $appMerchantTagEntity = $this->repo->application_merchant_tag->getMerchantTag($input[Entity::MERCHANT_ID]);

        if (empty($appMerchantTagEntity) === true)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MERCHANT_TAG_DOES_NOT_EXIST,
                null,
                $input
            );
        }

        $merchantMapping = $this->core->update($appMerchantTagEntity, $input);

        return $merchantMapping->toArrayPublic();
    }
}

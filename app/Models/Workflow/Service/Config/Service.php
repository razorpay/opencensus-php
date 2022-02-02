<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Service\Config;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new Config\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException|Exception\ServerErrorException
     */
    public function create(array $input)
    {
        $merchantId = $input[Entity::CONFIG][Entity::OWNER_ID];
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $validator = new Validator;
        $validator->validateInput(Validator::WORKFLOW_CONFIG_CREATE, $input[Entity::CONFIG]);
        $validator->validateForNoPendingPayouts($merchant);
        $validator->validateForNoPendingPayoutLinks($merchant);
        $validator->validateOrgId($input[Entity::CONFIG], $merchant);

        return $this->core->create($input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function update(array $input)
    {
        $merchantId = $input[Entity::OWNER_ID];
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $validator = new Validator;
        $validator->validateInput(Validator::WORKFLOW_CONFIG_UPDATE, $input);
        $validator->validateForNoPendingPayouts($merchant);
        $validator->validateForNoPendingPayoutLinks($merchant);

        /** @var Entity $config */
        $config = $this->repo->workflow_config->getByConfigId($input[Entity::ID]);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                $input
            );
        }

        return $this->core->update($config, $input);
    }

    /**
     * @param string $id
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function get(string $id)
    {
        /** @var Entity $config */
        $config = $this->repo->workflow_config->getByConfigId($id);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                $id
            );
        }

        return $this->core->getViaWorkflowService($config->getConfigId());
    }

    /**
     * @param string $configType
     * @param string $merchantId
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function getConfigByType(string $configType, string $merchantId)
    {
        $config = $this->repo->workflow_config->getByConfigTypeAndMerchantId($configType, $merchantId);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                ['merchant_id' => $merchantId, 'config_type' => $configType]);
        }

        return $this->core->getViaWorkflowService($config->getConfigId());
    }
}

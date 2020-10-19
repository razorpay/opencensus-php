<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Service\Config;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
     * @throws Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        (new Validator)->setStrictFalse()
            ->validateInput(Validator::CREATE, $input[Entity::CONFIG]);

        $org = $this->repo->org->findOrFailPublic($input[Entity::CONFIG][Entity::ORG_ID]);

        /** @var MerchantEntity $merchant */
        $merchant   = $this->repo->merchant->findOrFailPublic($input[Entity::CONFIG][Entity::OWNER_ID]);

        $this->validateIfNoPendingPayouts($merchant->getMerchantId());

        $configResponse = $this->core->create($input);

        return $configResponse;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function update(array $input)
    {
        (new Validator)->setStrictFalse()
            ->validateInput(Validator::UPDATE, $input[Entity::CONFIG]);

        /** @var Entity $config */
        $config = $this->repo->workflow_config->getByConfigId($input[Entity::CONFIG][Entity::ID]);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                $input
            );
        }

        $this->validateIfNoPendingPayouts($config->getMerchantId());

        $configResponse = $this->core->update($config, $input[Entity::CONFIG]);

        return $configResponse;
    }

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

        $configResponse = $this->core->get($id);

        return $configResponse;
    }

    public function getPayoutConfig(string $merchantId)
    {
        /** @var Entity $config */
        $config = $this->repo->workflow_config->getByConfigType('payout-approval', $merchantId);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                ['merchant_id' => $merchantId]
            );
        }

        $configResponse = $this->core->get($config->getConfigId());

        return $configResponse;
    }

    /**
     * @param string $merchantId
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateIfNoPendingPayouts(string $merchantId)
    {

        $payoutService = new \RZP\Models\Payout\Service;

        $pendingPayouts = $payoutService->getPendingPayoutsForMerchant($merchantId);

        if(empty($pendingPayouts) === false and $pendingPayouts > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUTS,
                null,
                ['merchant_id' => $merchantId]
            );
        }

    }

}

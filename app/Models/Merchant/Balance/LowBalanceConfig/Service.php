<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Traits\ServiceHasCrudMethods;

class Service extends Base\Service
{
    use ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->low_balance_config;
    }

    public function fetchMultiple(array $input): array
    {
        if (isset($input[Entity::ACCOUNT_NUMBER]) === true)
        {
            Validator::validateAndTranslateAccountNumberForBanking($input, $this->merchant);
        }

        $entities = $this->entityRepo->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function disableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicIdAndMerchant($id, $this->merchant);

        if (($entity->getType() === Entity::AUTOLOAD_BALANCE) and
            ($this->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ENABLE_DISABLE_ADMIN_AUTH_ONLY,
                null,
                [
                    'id'    => $id
                ]);
        }

        $response = $this->core->disableConfig($entity);

        return $response->toArrayPublic();
    }

    public function enableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicIdAndMerchant($id, $this->merchant);

        if (($entity->getType() === Entity::AUTOLOAD_BALANCE) and
            ($this->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ENABLE_DISABLE_ADMIN_AUTH_ONLY,
                null,
                [
                    'id'    => $id
                ]);
        }

        $response = $this->core->enableConfig($entity);

        return $response->toArrayPublic();
    }

    public function delete(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicIdAndMerchant($id, $this->merchant);

        if (($entity->getType() === Entity::AUTOLOAD_BALANCE) and
            ($this->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_DELETE_NOT_ALLOWED,
                null,
                [
                    'id'    => $id
                ]);
        }

        return $this->core->delete($entity);
    }

    public function alert()
    {
        $response = $this->core->processLowBalanceAlertsForMerchants();

        return $response;
    }

    public function isAdminAuth()
    {
        return $this->auth->isAdminAuth();
    }

    public function adminCreate($input)
    {
        $merchantId = $input[Entity::MERCHANT_ID];

        unset($input[Entity::MERCHANT_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setMerchant($merchant);

        $entity = $this->core->create($input, $merchant);

        return $entity->toArrayPublic();
    }

    public function adminUpdate(string $id, array $input): array
    {
        $entity = $this->entityRepo->findByPublicId($id);

        $entity = $this->core->update($entity, $input);

        return $entity->toArrayPublic();
    }

    public function adminEnableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicId($id);

        if (($entity->getType() === Entity::AUTOLOAD_BALANCE) and
            ($this->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ENABLE_DISABLE_ADMIN_AUTH_ONLY,
                null,
                [
                    'id'    => $id
                ]);
        }

        $response = $this->core->enableConfig($entity);

        return $response->toArrayPublic();
    }

    public function adminDisableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicId($id);

        if (($entity->getType() === Entity::AUTOLOAD_BALANCE) and
            ($this->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_ENABLE_DISABLE_ADMIN_AUTH_ONLY,
                null,
                [
                    'id'    => $id
                ]);
        }

        $response = $this->core->disableConfig($entity);

        return $response->toArrayPublic();
    }

    public function adminDelete(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicId($id);

        if (($entity->getType() === Entity::AUTOLOAD_BALANCE) and
            ($this->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_DELETE_NOT_ALLOWED,
                null,
                [
                    'id'    => $id
                ]);
        }

        return $this->core->delete($entity);
    }

    public function adminFetchMultiple(array $input, string $merchantId): array
    {
        $entities = $this->entityRepo->fetch($input, $merchantId);

        return $entities->toArrayPublic();
    }
}

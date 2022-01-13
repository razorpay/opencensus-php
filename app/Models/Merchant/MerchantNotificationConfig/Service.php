<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
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

        $this->entityRepo = $this->repo->merchant_notification_config;
    }

    public function fetchAsAdmin(string $id, string $merchantId): array
    {
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_FETCH_REQUEST,
                           [
                               'merchant_id' => $merchantId,
                               'config_id'   => $id
                           ]
        );
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $merchant);

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_FETCH_RESPONSE,
                           [
                               'entity' => $entity->toArrayPublic()
                           ]
        );

        return $entity->toArrayPublic();
    }

    public function fetchMultipleAsAdmin(array $input, string $merchantId): array
    {
        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_MULTIPLE_FETCH_REQUEST,
                           [
                               'merchant_id' => $merchantId,
                               'input'       => $input
                           ]
        );
        // first check if the MID is a valid one, and if not, throw a BadRequestValidationFailure exception
        $merchant = $this->repo->merchant->findOrFailPublic(trim($merchantId));

        $entities = $this->entityRepo->fetch($input, $merchant->getId());

        $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_CONFIG_MULTIPLE_FETCH_RESPONSE,
                           [
                               'merchant_id' => $merchant->getId(),
                               'entities'    => $entities->toArrayPublic()
                           ]
        );

        return $entities->toArrayPublic();
    }

    public function createAsAdmin(array $input, string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $entity = $this->core->create($input, $merchant);

        return $entity->toArrayPublic();
    }

    public function updateAsAdmin(string $id, string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $merchant);

        $entity = $this->core->update($entity, $input);

        return $entity->toArrayPublic();
    }

    public function deleteAsAdmin(string $id, string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $merchant);

        $this->core->delete($entity);

        return $entity->toArrayDeleted();
    }

    public function disableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->disableConfig($entity);

        return $response->toArrayPublic();
    }

    public function disableConfigAsAdmin(string $id, string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        /** @var  $entity Entity */
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $merchant);

        $response = $this->core->disableConfig($entity);

        return $response->toArrayPublic();
    }

    public function enableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->enableConfig($entity);

        return $response->toArrayPublic();
    }

    public function enableConfigAsAdmin(string $id, string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        /** @var  $entity Entity */
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $merchant);

        $response = $this->core->enableConfig($entity);

        return $response->toArrayPublic();
    }
}

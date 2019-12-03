<?php

namespace RZP\Models\Options;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->options;
    }

    public function createOptionsAdmin(array $input, string $merchantId): array
    {
        $entity = $this->core->create($input, (new Merchant\Core())->get($merchantId));

        return $entity->toArrayPublic();
    }

    public function find(
        string $namespace,
        string $service,
        string $referenceId = null,
        string $merchantId = null): array
    {
        $merchantId = (empty($merchantId) === true) ? $this->merchant->getId() : $merchantId;

        $tracePayload = [
            Entity::MERCHANT_ID      => $merchantId,
            Entity::NAMESPACE        => $namespace,
            Entity::SERVICE_TYPE     => $service,
            Entity::REFERENCE_ID     => $referenceId
        ];

        $this->trace->info(TraceCode::OPTIONS_DETAILED_READ_REQUEST, $tracePayload);

        return $this->core->find($namespace, $service, $referenceId, $merchantId);
    }

    public function findAdmin(
        string $namespace,
        string $service,
        string $merchantId): array
    {
        // make sure its a valid merchant id
        $merchantId = (new Merchant\Core())->get($merchantId)->getId();

        return $this->find($namespace, $service, null, $merchantId);
    }

    public function updateOptionsAdmin(array $input, string $namespace, string $service, string $merchantId): array
    {
        $entity = $this->core->fetchByNamespace($namespace, $merchantId);

        $this->validate($namespace, $service, $merchantId, $entity);

        $this->core->update($entity, $input);

        return $entity->toArrayPublic();
    }

    public function deleteOptionsAdmin(string $namespace, string $service, string $merchantId): array
    {
        $entity = $this->core->fetchByNamespace($namespace, $merchantId);

        $this->validate($namespace, $service, $merchantId, $entity);

        $this->core->delete($entity);

        return $entity->toArrayDeleted();
    }

    private function validate(
        string $namespace,
        string $service,
        string $merchantId,
        Entity $entity = null)
    {
        $this->core->validateNamespaceAndService($namespace, $service);

        // this checks for a valid merchant ID
        (new Merchant\Core())->get($merchantId);

        if(empty($entity) === true)
        {
            throw new BadRequestValidationFailureException(
                sprintf(Constants::ERROR_MSG_NO_ENTITY_FOR_MID,
                    $namespace, $service, $merchantId));
        }
    }
}

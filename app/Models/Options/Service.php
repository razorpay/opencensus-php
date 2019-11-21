<?php

namespace RZP\Models\Options;

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

    public function find(string $namespace, string $service, string $referenceId = null): array
    {
        $merchantId = $this->merchant->getId();

        $tracePayload = [
            Entity::MERCHANT_ID      => $merchantId,
            Entity::NAMESPACE        => $namespace,
            Entity::SERVICE_TYPE     => $service,
            Entity::REFERENCE_ID     => $referenceId
        ];

        $this->trace->info(TraceCode::OPTIONS_DETAILED_READ_REQUEST, $tracePayload);

        return $this->core->find($namespace, $service, $referenceId, $merchantId);
    }
}

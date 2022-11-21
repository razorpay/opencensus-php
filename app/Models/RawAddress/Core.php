<?php

namespace RZP\Models\RawAddress;

use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Batch;

class Core extends Base\Core
{

    /**
     * @var Mutex
     */
    protected $mutex;


    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @param array  $input
     * @return Entity
     */
    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::RAW_ADDRESS_CREATE_REQUEST, []);

            $raw_address = new Entity();

            $raw_address->build($input);

            // entity id is required
            $raw_address->generateId();

            $this->app['workflow']
                ->setEntityAndId($raw_address->getEntity(), $raw_address->getId())
                ->handle((new \stdClass), $raw_address);

            $this->repo->raw_address->saveOrFail($raw_address);

            return $raw_address;
    }

    public function bulkCreate(array $data)
    {
        $this->repo->raw_address->bulkInsert($data);
    }
}

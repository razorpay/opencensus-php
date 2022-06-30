<?php

namespace RZP\Models\OfflinePayment;

use Cache;
use RZP\Models\OfflineChallan;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $validator;
    protected $provider;
    protected $ip;
    protected $mutex;
    protected $core;
    protected $transformer;

    /**
     * Service constructor. Sets provider from app auth, and
     * sets request IP for use in validation of providers.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        $this->core = new Core;

        $this->transformer = new Transformer();

        $this->repo = new Repository();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processOfflinePayment(array $requestPayload)
    {

        $response = null;

        $this->trace->info(
            TraceCode::OFFLINE_ECOLLECT_PROCESS_REQUEST,
            $requestPayload
        );

        $response = $this->core->processAndSaveOffline($requestPayload);


        return $response;
    }

    public function checkIfChallanExists($request) {

        $challanRepo = new OfflineChallan\Repository;

        $offline_challan = $challanRepo->fetchByChallanNumber($request[Entity::CHALLAN_NUMBER]);

        if ($offline_challan === null)
        {
            return StatusCode::CHALLAN_NOT_FOUND;
        }

        if ($offline_challan['status'] !== 'validated')
        {
            return StatusCode::CHALLAN_NOT_VALIDATED;
        }
    }
}

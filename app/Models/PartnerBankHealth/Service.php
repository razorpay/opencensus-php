<?php

namespace RZP\Models\PartnerBankHealth;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;

class Service extends Base\Service
{
    /**
     * @var Core
     */
    protected $core;

    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->validator = new Validator();
    }

    public function processStatusUpdateFromFTS($payload)
    {
        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_UPDATE_REQUEST_FROM_FTS, ["payload" => $payload]);

        $this->modifyPayload($payload);

        switch ($payload[Constants::SOURCE])
        {
            case Events::FAIL_FAST_HEALTH:

                $entity = $this->core->processFailFastStatusUpdate($payload);

                $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_STATUS_UPDATE_PROCESSED,
                                   [
                                       'entity' => $entity->toArrayPublic(),
                                   ]);

                $this->core->sendNotifications($payload);

                break;

                //TODO: Handle the case of source = 'downtime'

            default:
                throw new LogicException(
                    "Invalid source : " . $payload[Constants::SOURCE],
                    null
                );
        }
    }

    public function sendNotifications($payload)
    {
        $this->validator->validateInput(Validator::NOTIFICATION, $payload);

        $this->core->sendNotifications($payload);
    }

    public function modifyPayload(& $payload)
    {
        $bankName = $payload[Constants::INSTRUMENT][Constants::BANK];
        $payload[Constants::INSTRUMENT][Constants::BANK] = Core::BANK_TO_IFSC_MAPPING[$bankName];
    }
}

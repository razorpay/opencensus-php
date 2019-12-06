<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;


    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->payout_link;
    }

    public function generateCustomerOtp($payoutLinkId, $input)
    {
        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::PAYOUT_LINK_ID_RULE,
                                  [
                                      Entity::ID => $payoutLinkId
                                  ]);

        $this->trace->info(TraceCode::PAYOUT_CUSTOMER_OTP_REQUEST,
                           $input
        );

        return $this->core->generateCustomerOtp($payoutLinkId);
    }
}

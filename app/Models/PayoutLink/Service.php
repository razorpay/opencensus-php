<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;
    const OTP = 'otp';

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

    public function generateAndSendCustomerOtp($payoutLinkId, $input)
    {
        $this->trace->info(TraceCode::PAYOUT_CUSTOMER_OTP_REQUEST,
                           $input
        );

        return $this->core->generateAndSendCustomerOtp($payoutLinkId);
    }

    public function verifyCustomerOtp($payoutLinkId, $input)
    {
        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::VERIFY_OTP, $input);

        return $this->core->verifyCustomerOtp($payoutLinkId, $input[self::OTP]);
    }
}

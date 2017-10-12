<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    const ACQUIRER = 'sbi';

    protected $gateway = 'upi_mindgate_sbi';

    const BANK = 'sbi';

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@sbi';

    public function authorize(array $input)
    {
        parent::authorize($input);

        sd('1');
    }
}
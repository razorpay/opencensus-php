<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Constants\Mode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

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

        sd($this->getMerchantId(), $this->getSecret());
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}
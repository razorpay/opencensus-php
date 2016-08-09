<?php

namespace RZP\Gateway\Wallet\Freecharge;

use Carbon\Carbon;
use Lib\PhoneBook;
use View;


use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Gateway\Wallet\Freecharge\Action;
use RZP\Gateway\Wallet\Freecharge\ResponseCodeMap;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant;
use RZP\Models\Payment\Core;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_freecharge';

    protected $sortRequestContent = false;

    protected $canRunOtpFlow = true;

    protected $topup = true;

    protected $action = null;

    protected $map = array(
        'email'         => 'email',
        'mobileNumber'  => 'contact',
        'key'           => 'gateway_merchant_id',
        'txnId'         => 'gateway_payment_id',
        'refundId'      => 'gateway_refund_id',
        'status'        => 'status_code',
        'amount'        => 'amount',
        'message'       => 'response_description',
        'received'      => 'received'
    );

    public function authorize(array $input)
    {
        parent::authorize($input);
    }

    public function callback(array $input)
    {

    }

    public function getUrl($type = null)
    {
        if ($type === null)
            $type = $this->action;

        $url = Freecharge\Url::getDomainForType($type, $this->mode);

        $type = strtoupper($type);

        $url .= $this->getRelativeUrl();
    }
}

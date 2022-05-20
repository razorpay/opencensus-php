<?php

namespace RZP\Gateway\P2p\Upi\Mock;

use RZP\Error\P2p\ErrorCode;
use RZP\Exception\LogicException;

class Scenario
{
    // No scenario at all
    const N0000     = 'N0000';

    const CL301     = 'CL301';
    const CL302     = 'CL302';
    const CL303     = 'CL303';

    // Device Scenarios
    const DE101     = 'DE101';
    const DE102     = 'DE102';
    const DE201     = 'DE201';
    const DE202     = 'DE202';
    const DE203     = 'DE203';
    const DE301     = 'DE301';
    const DE302     = 'DE302';
    const DE303     = 'DE303';
    const DE304     = 'DE304';
    const DE305     = 'DE305';
    const DE306     = 'DE306';
    const DE401     = 'DE401';

    // Bank Account Scenarios
    const BA101     = 'BA101';
    const BA102     = 'BA102';
    const BA201     = 'BA201';
    const BA202     = 'BA202';
    const BA203     = 'BA203';
    const BA204     = 'BA204';
    const BA205     = 'BA205';
    const BA301     = 'BA301';
    const BA302     = 'BA302';
    const BA303     = 'BA303';
    const BA304     = 'BA304';
    const BA305     = 'BA305';
    const BA401     = 'BA401';
    const BA402     = 'BA402';
    const BA403     = 'BA403';
    const BA404     = 'BA404';

    // Bank Scenarios
    const BB101      = 'BB101';
    const BB102      = 'BB102';
    const BB103      = 'BB103';
    const BB104      = 'BB104';

    // VPA Scenarios
    const VA101     = 'VA101';
    const VA201     = 'VA201';
    const VA202     = 'VA202';
    const VA203     = 'VA203';
    const VA204     = 'VA204';
    const VA205     = 'VA205';
    const VA301     = 'VA301';
    const VA302     = 'VA302';
    const VA303     = 'VA303';
    const VA304     = 'VA304';
    const VA305     = 'VA305';
    const VA401     = 'VA401';
    const VA501     = 'VA501';
    const VA601     = 'VA601';
    const VA701     = 'VA701';
    const VA702     = 'VA702';
    const VA703     = 'VA703';
    const VA704     = 'VA704';
    const VA801     = 'VA801';
    const VA802     = 'VA802';
    const VA901     = 'VA901';
    const VA902     = 'VA902';
    const VA903     = 'VA903';

    // Transaction Scenarios
    const TR101     = 'TR101';
    const TR201     = 'TR201';
    const TR301     = 'TR301';
    const TR302     = 'TR302';
    const TR401     = 'TR401';
    const TR501     = 'TR501';
    const TR502     = 'TR502';
    const TR601     = 'TR601';
    const TR602     = 'TR602';

    // Mandate Scenarios
    const MA101     = 'MA101';
    const MA201     = 'MA201';
    const MA301     = 'MA301';
    const MA401     = 'MA401';
    const MA501     = 'MA501';
    const MA601     = 'MA601';
    const MA701     = 'MA701';

    /*
     *
     *  self::SCENARIO_ID => [
     *      'entity'        => Entity where scenario is handled
     *      'action'        => Action for which scenario is handled
     *      'success'       => Whether a success scenario of will throw error
     *      'desc'          => Human readable description
     *      'code'          => Error code for failure only
     *      'sub'           => Sub scenario for scenario, only for applicable
     *  ]
     *
     * @var array
     */
    public static $map = [

        self::CL301 => [
            'entity'    => 'cl',
            'action'    => 'registerApp',
            'success'   => true,
            'desc'      => 'CL is not registered with device',
            'code'      => ErrorCode::BAD_REQUEST_PAYMENT_UPI_DEVICE_MISSING,
        ],
        self::CL302 => [
            'entity'    => 'cl',
            'action'    => 'registerApp',
            'success'   => true,
            'desc'      => 'User aborted on CL page',
            'code'      => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        ],
        self::CL303 => [
            'entity'    => 'cl',
            'action'    => 'registerApp',
            'success'   => true,
            'desc'      => 'Technical errors on CL',
            'code'      => ErrorCode::BAD_REQUEST_INVALID_DEVICE,
            'sub'       => 'L05',
        ],

        self::DE101 => [
            'entity'    => 'device',
            'action'    => 'verification',
            'success'   => false,
            'desc'      => 'SMS Timed Out',
            'code'      => ErrorCode::BAD_REQUEST_SMS_FAILED,
        ],
        self::DE102 => [
            'entity'    => 'device',
            'action'    => 'verification',
            'success'   => true,
            'desc'      => 'SMS based validation',
            'code'      => null,
        ],
        self::DE201 => [
            'entity'    => 'device',
            'action'    => 'initiateGetToken',
            'success'   => false,
            'desc'      => 'Not Registered',
            'code'      => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        ],
        self::DE202 => [
            'entity'    => 'device',
            'action'    => 'initiateGetToken',
            'success'   => true,
            'desc'      => 'Force token registration',
            'code'      => null,
        ],
        self::DE203 => [
            'entity'    => 'device',
            'action'    => 'initiateGetToken',
            'success'   => true,
            'desc'      => 'Force token rotation',
            'code'      => null,
        ],
        self::DE301 => [
            'entity'    => 'device',
            'action'    => 'getToken',
            'success'   => false,
            'desc'      => 'Not Registered',
            'code'      => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        ],
        self::DE302 => [
            'entity'    => 'device',
            'action'    => 'getToken',
            'success'   => true,
            'desc'      => 'Force token registration',
            'code'      => null,
        ],
        self::DE303 => [
            'entity'    => 'device',
            'action'    => 'getToken',
            'success'   => true,
            'desc'      => 'Force token rotation',
            'code'      => null,
        ],
        self::DE304 => [
            'entity'    => 'device',
            'action'    => 'getToken',
            'success'   => false,
            'desc'      => 'Fetch token from npci call failed',
            'code'      => ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED,
        ],
        self::DE305 => [
            'entity'    => 'device',
            'action'    => 'getToken',
            'success'   => false,
            'desc'      => 'Invalid Hmac created for npci',
            'code'      => ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED,
        ],
        self::DE306 => [
            'entity'    => 'device',
            'action'    => 'getToken',
            'success'   => false,
            'desc'      => 'Mismatch in device id in hmac',
            'code'      => ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED,
        ],
        self::DE401 => [
            'entity'    => 'device',
            'action'    => 'deregister',
            'success'   => false,
            'desc'      => 'Failed with gateway error',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],

        self::BA101 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBanks',
            'success'   => false,
            'desc'      => 'Failed with unknown gateway error',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::BA102 => [
            'entity'    => 'bank_account',
            'action'    => 'initiateRetrieve',
            'success'   => false,
            'desc'      => 'Failed with unknown gateway error',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::BA201 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::BA202 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE,
        ],
        self::BA203 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => false,
            'desc'      => 'No Account found in bank',
            'code'      => ErrorCode::BAD_REQUEST_NO_BANK_ACCOUNT_FOUND,
        ],
        self::BA204 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => true,
            'desc'      => 'N(<99) number of Accounts Found, with last for (1000 +(N * 10))',
            'code'      => null,
            'sub'       => '102',
        ],
        self::BA301 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::BA302 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Invalid Card Details',
            'code'      => ErrorCode::BAD_REQUEST_INVALID_CARD_DETAILS,
        ],
        self::BA303 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Resend Failed',
            'code'      => ErrorCode::BAD_REQUEST_SMS_OTP_FAILED,
        ],
        self::BA304 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Invalid UPIPIN',
            'code'      => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        ],
        self::BA305 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'NPCI CL fails with Error',
            'code'      => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        ],
        self::BA401 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::BA402 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'Invalid UPIPIN',
            'code'      => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        ],
        self::BA403 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'NPCI CL fails with Error',
            'code'      => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        ],
        self::BA404 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => true,
            'desc'      => 'NPCI CL fails with Error',
            'code'      => null,
            'sub'       => '102',
        ],

        self::BB101 => [
            'entity'    => 'bank',
            'action'    => 'retrieveBanks',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::BB102 => [
            'entity'    => 'bank',
            'action'    => 'retrieveBanks',
            'success'   => false,
            'desc'      => 'Invalid response from bank',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::BB103 => [
            'entity'    => 'bank',
            'action'    => 'retrieveBanks',
            'success'   => true,
            'desc'      => 'N number of bank lists found',
            'code'      => null,
            'sub'       => '104',
        ],
        self::BB104 => [

        ],
        self::VA101 => [
            'entity'    => 'vpa',
            'action'    => 'fetchHandles',
            'success'   => false,
            'desc'      => 'Failed with unknown gateway error',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::VA201 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA202 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Invalid response from bank',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::VA203 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Blocked Username',
            'code'      => ErrorCode::BAD_REQUEST_VPA_NOT_AVAILABLE,
        ],
        self::VA204 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Username Taken',
            'code'      => ErrorCode::BAD_REQUEST_VPA_NOT_AVAILABLE,
        ],
        self::VA205 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Max Limit Reached',
            'code'      => ErrorCode::BAD_REQUEST_MAX_VPA_LIMIT_REACHED,
        ],
        self::VA301 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA302 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Invalid response from bank',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::VA303 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Blocked Username',
            'code'      => ErrorCode::BAD_REQUEST_VPA_NOT_AVAILABLE,
        ],
        self::VA304 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Username Taken',
            'code'      => ErrorCode::BAD_REQUEST_VPA_NOT_AVAILABLE,
        ],
        self::VA305 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Max Limit Reached',
            'code'      => ErrorCode::BAD_REQUEST_MAX_VPA_LIMIT_REACHED,
        ],
        self::VA401 => [
            'entity'    => 'vpa',
            'action'    => 'setDefault',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA501 => [
            'entity'    => 'vpa',
            'action'    => 'assignBankAccount',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA601 => [
            'entity'    => 'vpa',
            'action'    => 'delete',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA701 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA702 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => false,
            'desc'      => 'VPA not exists',
            'code'      => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        ],
        self::VA703 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => true,
            'desc'      => 'Verified Merchant VPA',
            'code'      => null,
        ],
        self::VA704 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => true,
            'desc'      => 'Large Beneficiary Name',
            'code'      => null,
        ],
        self::VA801 => [
            'entity'    => 'vpa',
            'action'    => 'handle',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA802 => [
            'entity'    => 'vpa',
            'action'    => 'handle',
            'success'   => false,
            'desc'      => 'Vpa not blocked, to unblock',
            'code'      => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        ],
        self::VA901 => [
            'entity'    => 'vpa',
            'action'    => 'fetchAll',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::VA902 => [
            'entity'    => 'vpa',
            'action'    => 'fetchAll',
            'success'   => true,
            'desc'      => 'No Blocked Found',
        ],
        self::VA903 => [
            'entity'    => 'vpa',
            'action'    => 'fetchAll',
            'success'   => true,
            'desc'      => 'N Blocked Found',
            'code'      => null,
            'sub'       => '102',
        ],

        self::TR101 => [
            'entity'    => 'transaction',
            'action'    => 'initiatePay',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::TR201 => [
            'entity'    => 'transaction',
            'action'    => 'initiateCollect',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::TR301 => [
            'entity'    => 'transaction',
            'action'    => 'authorize',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::TR302 => [
            'entity'    => 'transaction',
            'action'    => 'authorize',
            'success'   => true,
            'desc'      => 'Fail with error code',
            'code'      => null,
            'sub'       => '102',
        ],
        self::TR401 => [
            'entity'    => 'transaction',
            'action'    => 'reject',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::TR501 => [
            'entity'    => 'transaction',
            'action'    => 'raiseConcern',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::TR502 => [
            'entity'    => 'transaction',
            'action'    => 'raiseConcern',
            'success'   => true,
            'desc'      => 'Respond with error code',
            'code'      => null,
            'sub'       => '102',
        ],
        self::TR601 => [
            'entity'    => 'transaction',
            'action'    => 'concernStatus',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::TR602 => [
            'entity'    => 'transaction',
            'action'    => 'concernStatus',
            'success'   => true,
            'desc'      => 'Respond with error code',
            'code'      => null,
            'sub'       => '102',
        ],
        self::MA101 => [
            'entity'    => 'mandate',
            'action'    => 'initiatePay',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::MA201 => [
            'entity'    => 'mandate',
            'action'    => 'authorize',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::MA301 => [
            'entity'    => 'mandate',
            'action'    => 'initiateReject',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::MA401 => [
            'entity'    => 'mandate',
            'action'    => 'reject',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::MA501 => [
            'entity'    => 'mandate',
            'action'    => 'pause',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::MA601 => [
            'entity'    => 'mandate',
            'action'    => 'unpause',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
        self::MA701 => [
            'entity'    => 'mandate',
            'action'    => 'revoke',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
            'code'      => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ],
    ];

    // These are the scenario properties
    public $id;
    public $sub;
    public $contact;
    public $stan;

    /**
     * Scenario constructor.
     * @param string $id                Unique Id assigned to a scenario
     * @param string|null $sub          Sub part of the scenario
     * @param string|null $contact      Contact to be used in mocked
     * @param string|null $stan         A random stan used for the scenario
     */
    public function __construct(string $id = null, string $sub = null, string $contact = null, string $stan = null)
    {
        $this->id       = $id ?? self::N0000;
        $this->sub      = $sub ?? '000';
        $this->contact  = $contact ?? '919999999999';
        $this->stan     = $stan ?? random_integer(6);
    }

    public function toRequestId()
    {
        return sprintf('M.%s%s.%d.%s', $this->id, $this->sub, $this->contact, $this->stan);
    }


    public static function fromRequestId(string $requestId)
    {
        preg_match('/M\.([\w]{5})([\w]{3})\.([\d]{12})\.([\d]{6})/', $requestId, $matches);

        return (new self($matches[1] ?? null,
                         $matches[2] ?? null,
                         $matches[3] ?? null,
                         $matches[4] ?? null));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSub(): string
    {
        return $this->sub;
    }

    public function getParsedSub(string $id): string
    {
        $default = self::$map[$id]['sub'] ?? '000';

        // For null and 000, we will give preference to default sub
        if ($this->getId() === $id)
        {
            if ($this->getSub() === '000')
            {
                return $default;
            }

            return $this->getSub();
        }

        return $default;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function isSuccess(): bool
    {
        return self::$map[$this->id]['success'] ?? true;
    }

    public function getDesc()
    {
        return self::$map[$this->id]['desc'] ?? null;
    }

    public function getCode()
    {
        return self::$map[$this->id]['code'] ?? null;
    }

    public function is(string $id): bool
    {
        return ($this->id === $id);
    }

    public function in(array $ids): bool
    {
        return in_array($this->id, $ids, true);
    }

    public function toArray()
    {
        return [
            'id'            => $this->getId(),
            'sub'           => $this->getSub(),
            'contact'       => $this->getContact(),
            'success'       => $this->isSuccess(),
            'code'          => $this->getCode(),
        ];
    }
}

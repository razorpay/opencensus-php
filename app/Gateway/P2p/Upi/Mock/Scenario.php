<?php

namespace RZP\Gateway\P2p\Upi\Mock;

use RZP\Error\P2p\ErrorCode;
use RZP\Exception\LogicException;

class Scenario
{
    // Device Scenarios
    const DE101     = 'DE101';
    const DE102     = 'DE102';
    const DE201     = 'DE201';

    // Bank Account Scenarios
    const BA101     = 'BA101';
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

    // VPA Scenarios
    const VA101     = 'VA101';
    const VA201     = 'VA201';
    const VA202     = 'VA202';
    const VA203     = 'VA203';
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
    const VA901     = 'VA901';
    const VA902     = 'VA902';

    // Transaction Scenarios
    const TR101     = 'TR101';
    const TR201     = 'TR201';
    const TR301     = 'TR301';
    const TR302     = 'TR302';
    const TR401     = 'TR401';
    const TR501     = 'TR501';
    const TR601     = 'TR601';
    const TR602     = 'TR602';

    public static $map = [

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
            'action'    => 'getToken',
            'success'   => false,
            'desc'      => 'Not Registered',
            'code'      => ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
        ],
        self::BA101 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBanks',
            'success'   => false,
            'desc'      => '',
        ],
        self::BA202 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
        ],
        self::BA203 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => false,
            'desc'      => 'Beneficiary Multiple Contact',
        ],
        self::BA204 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => false,
            'desc'      => 'No Account Found',
        ],
        self::BA205 => [
            'entity'    => 'bank_account',
            'action'    => 'retrieve',
            'success'   => true,
            'desc'      => 'N Account Found',
        ],
        self::BA301 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
        ],
        self::BA302 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Invalid Card Details',
        ],
        self::BA303 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Resend Failed',
        ],
        self::BA304 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'Invalid UPIPIN',
        ],
        self::BA305 => [
            'entity'    => 'bank_account',
            'action'    => 'setUpiPin',
            'success'   => false,
            'desc'      => 'NPCI CL fails with Error',
        ],
        self::BA401 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
        ],
        self::BA402 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'Any possible balance',
        ],
        self::BA403 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'Invalid UPIPIN',
        ],
        self::BA404 => [
            'entity'    => 'bank_account',
            'action'    => 'fetchBalance',
            'success'   => false,
            'desc'      => 'NPCI CL fails with Error',
        ],
        self::VA101 => [
            'entity'    => 'vpa',
            'action'    => 'fetchHandles',
            'success'   => false,
            'desc'      => '',
        ],
        self::VA201 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Blocked Username',
        ],
        self::VA202 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Username Taken',
        ],
        self::VA203 => [
            'entity'    => 'vpa',
            'action'    => 'checkAvailability',
            'success'   => false,
            'desc'      => 'Max Limit Reached',
        ],
        self::VA301 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Username Taken',
        ],
        self::VA302 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Blocked Username',
        ],
        self::VA303 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Username Taken',
        ],
        self::VA304 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Max Limit Reached',
        ],
        self::VA305 => [
            'entity'    => 'vpa',
            'action'    => 'add',
            'success'   => false,
            'desc'      => 'Bank Account Not Found',
        ],
        self::VA401 => [
            'entity'    => 'vpa',
            'action'    => 'setDefault',
            'success'   => false,
            'desc'      => '',
        ],
        self::VA501 => [
            'entity'    => 'vpa',
            'action'    => 'assignBankAccount',
            'success'   => false,
            'desc'      => 'Bank Account Not Found',
        ],
        self::VA601 => [
            'entity'    => 'vpa',
            'action'    => 'delete',
            'success'   => false,
            'desc'      => 'Default VPA',
        ],
        self::VA701 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => false,
            'desc'      => 'Beneficiary Bank Down',
        ],
        self::VA702 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => false,
            'desc'      => 'VPA not exists',
        ],
        self::VA703 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => true,
            'desc'      => 'Verified Merchant VPA',
        ],
        self::VA704 => [
            'entity'    => 'vpa',
            'action'    => 'validate',
            'success'   => true,
            'desc'      => 'Large Beneficiary Name',
        ],
        self::VA801 => [
            'entity'    => 'vpa',
            'action'    => 'handle',
            'success'   => false,
            'desc'      => 'VPA not blocked',
        ],
        self::VA901 => [
            'entity'    => 'vpa',
            'action'    => 'fetchAll',
            'success'   => true,
            'desc'      => 'No Blocked VPA',
        ],
        self::VA902 => [
            'entity'    => 'vpa',
            'action'    => 'fetchAll',
            'success'   => true,
            'desc'      => 'N Blocked Found',
        ],
        self::TR101 => [
            'entity'    => 'transaction',
            'action'    => 'pay',
            'success'   => false,
            'desc'      => 'Failed with ErrorCode',
        ],
        self::TR201 => [
            'entity'    => 'transaction',
            'action'    => 'collect',
            'success'   => false,
            'desc'      => 'Failed with ErrorCode',
        ],
        self::TR301 => [
            'entity'    => 'transaction',
            'action'    => 'authorize',
            'success'   => false,
            'desc'      => 'Failed with ErrorCode',
        ],
        self::TR302 => [
            'entity'    => 'transaction',
            'action'    => 'authorize',
            'success'   => false,
            'desc'      => 'NPCI CL fails with Error',
        ],
        self::TR401 => [
            'entity'    => 'transaction',
            'action'    => 'reject',
            'success'   => false,
            'desc'      => 'Failed with ErrorCode',
        ],
        self::TR501 => [
            'entity'    => 'transaction',
            'action'    => 'raiseConcern',
            'success'   => false,
            'desc'      => 'API Failed',
        ],
        self::TR601 => [
            'entity'    => 'transaction',
            'action'    => 'concernStatus',
            'success'   => true,
            'desc'      => 'Still Open',
        ],
        self::TR602 => [
            'entity'    => 'transaction',
            'action'    => 'concernStatus',
            'success'   => false,
            'desc'      => 'Failed with ErrorCode',
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
        $this->id       = $id ?? '00000';
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
}

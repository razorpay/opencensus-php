<?php

namespace RZP\Models\BankAccount;

use App;
use RZP\Models\Vpa;
use RZP\Models\Base;
use Razorpay\IFSC\IFSC;
use RZP\Models\Merchant;
use RZP\Models\VirtualAccount;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Payment\Processor\Netbanking;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;

/**
 * @property Merchant\Entity     $merchant
 */

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const ENTITY_ID                     = 'entity_id';
    const TYPE                          = 'type';
    const BENEFICIARY_CODE              = 'beneficiary_code';
    const IFSC_CODE                     = 'ifsc_code';
    const BANK_NAME                     = 'bank_name';
    const ACCOUNT_NUMBER                = 'account_number';
    const BENEFICIARY_NAME              = 'beneficiary_name';
    const REGISTERED_BENEFICIARY_NAME   = 'registered_beneficiary_name';
    const BENEFICIARY_ADDRESS1          = 'beneficiary_address1';
    const BENEFICIARY_ADDRESS2          = 'beneficiary_address2';
    const BENEFICIARY_ADDRESS3          = 'beneficiary_address3';
    const BENEFICIARY_ADDRESS4          = 'beneficiary_address4';
    const BENEFICIARY_EMAIL             = 'beneficiary_email';
    const BENEFICIARY_MOBILE            = 'beneficiary_mobile';
    const BENEFICIARY_PIN               = 'beneficiary_pin';
    const BENEFICIARY_CITY              = 'beneficiary_city';
    const BENEFICIARY_STATE             = 'beneficiary_state';
    const BENEFICIARY_COUNTRY           = 'beneficiary_country';
    const DELETED_AT                    = 'deleted_at';
    const GATEWAY_SYNC                  = 'is_gateway_sync';
    const MOBILE_BANKING_ENABLED        = 'mobile_banking_enabled';
    const ACCOUNT_TYPE                  = 'account_type';
    const MPIN                          = 'mpin';
    const VIRTUAL                       = 'virtual';
    const FTS_FUND_ACCOUNT_ID           = 'fts_fund_account_id';
    const NOTES                         = 'notes';

    const NAME                          = 'name';
    const IFSC                          = 'ifsc';

    // Mobile Banking Enabled
    const MPIN_SET                      = 'mpin_set';

    const IFSC_CODE_LENGTH              = 11;

    const ACCOUNT_NUMBER_LENGTH         = 16;

    const SPECIAL_IFSC_CODE             = 'RZPB0000000';

    //
    // Beneficiary registration constants
    //
    const ON                = 'on';
    const FROM              = 'from';
    const TO                = 'to';
    const ALL               = 'all';
    const RECIPIENT_EMAILS  = 'recipient_emails';
    const DURATION          = 'duration';

    //
    // Used for accepting mode in the input for Bank Account FTA
    //
    const TRANSFER_MODE = 'transfer_mode';

    protected static $sign      = 'ba';

    protected $primaryKey = self::ID;

    protected $entity = 'bank_account';

    protected $fillable = [
        self::ENTITY_ID,
        self::IFSC,
        self::IFSC_CODE,
        self::MOBILE_BANKING_ENABLED,
        self::NAME,
        self::NOTES,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::TYPE,
        self::BENEFICIARY_ADDRESS1,
        self::BENEFICIARY_ADDRESS2,
        self::BENEFICIARY_ADDRESS3,
        self::BENEFICIARY_ADDRESS4,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_CITY,
        self::BENEFICIARY_STATE,
        self::BENEFICIARY_PIN,
    ];

    protected $visible = [
        self::ID,
        self::IFSC,
        self::IFSC_CODE,
        self::NAME,
        self::BANK_NAME,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::TYPE,
        self::BENEFICIARY_CODE,
        self::BENEFICIARY_ADDRESS1,
        self::BENEFICIARY_ADDRESS2,
        self::BENEFICIARY_ADDRESS3,
        self::BENEFICIARY_ADDRESS4,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_CITY,
        self::BENEFICIARY_STATE,
        self::BENEFICIARY_COUNTRY,
        self::BENEFICIARY_PIN,
        self::MPIN_SET,
        self::MPIN,
        self::NOTES,
        self::MOBILE_BANKING_ENABLED,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::IFSC,
        self::BANK_NAME,
        self::NAME,
        self::NOTES,
        self::ACCOUNT_NUMBER,
    ];

    protected $hosted = [
        self::ID,
        self::ENTITY,
        self::IFSC,
        self::BANK_NAME,
        self::NAME,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_EMAIL,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ACCOUNT_NUMBER,
    ];

    protected $appends = [
        self::NAME,
        self::IFSC,
        self::MPIN_SET,
        self::BANK_NAME,
    ];

    protected $guarded = [self::ID];

    protected static $generators = [
        self::ID,
        self::BENEFICIARY_COUNTRY,
    ];

    protected $casts = [
        self::MOBILE_BANKING_ENABLED => 'bool',
        self::GATEWAY_SYNC           => 'bool'
    ];

    protected $ignoredRelations = [
        'source',
    ];

    protected $defaults = [
        self::NOTES => [],
    ];

    protected $pii = [
        self::ACCOUNT_NUMBER,
    ];

    protected $generateIdOnCreate = true;

    public function build(array $input = [], string $operation = 'addBankAccount')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    // we are not doing it via generators as we want to generate only for
    // merchant bank accounts
    public function generateBeneficiaryCode()
    {
        $beneficiaryCode = $this->getKotakBeneficaryCode();

        $this->setAttribute(self::BENEFICIARY_CODE, $beneficiaryCode);
    }

    protected function generateBeneficiaryCountry($input)
    {
        $benificiaryCountry = $input[self::BENEFICIARY_COUNTRY] ?? 'IN';
        $this->setAttribute(self::BENEFICIARY_COUNTRY, $benificiaryCountry);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function vpa()
    {
        return $this->hasOne(Vpa\Entity::class);
    }

    public function source()
    {
        return $this->morphTo('source', self::TYPE, self::ENTITY_ID);
    }

    public function payouts()
    {
        return $this->morphMany('RZP\Models\Payout\Entity', 'destination');
    }

    public function virtualAccount()
    {
        return $this->hasOne(VirtualAccount\Entity::class);
    }

    public function isVirtual()
    {
        return ($this->getAttribute(self::VIRTUAL) === 1);
    }

    public function qrCode()
    {
        return $this->hasOne(QrV2\Entity::class, QrV2\Entity::ID, Entity::ENTITY_ID);
    }

    public function getMpinSetAttribute()
    {
        return ($this->getAttribute(self::MPIN) !== null);
    }

    public function getBankNameAttribute()
    {
        $ifsc = $this->getAttribute(self::IFSC_CODE);

        if ($ifsc === null)
        {
            return null;
        }

        if ($ifsc === self::SPECIAL_IFSC_CODE)
        {
            return 'Razorpay';
        }

        return IFSC::getBankName($ifsc);
    }

    protected function getMpinAttribute()
    {
        if (isset($this->attributes[self::MPIN]) === false)
        {
            return null;
        }

        $mpin = $this->attributes[self::MPIN];

        return Crypt::decrypt($mpin, true, $this);
    }

    protected function getNameAttribute()
    {
        if (isset($this->attributes[self::BENEFICIARY_NAME]) === false)
        {
            return null;
        }

        return $this->attributes[self::BENEFICIARY_NAME];
    }

    protected function setPublicAccountNumberAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        $accountNumber = $this->getAccountNumber();

        if (($basicAuth->isPublicAuth() === true) and
            ($this->getType() !== Type::VIRTUAL_ACCOUNT))
        {
            //
            // Since we should not be exposing account_number in public auth ever.
            // (Except virtual account numbers, of course)
            //
            // Note that we should not use toArrayPublic internally to fetch
            // account_number via bank_account details. We should either directly
            // fetch the account_number via `getAccountNumber()`, or use `toArray`.
            //
            $attributes[self::ACCOUNT_NUMBER] = mask_except_last4($accountNumber);
        }
    }

    public function settlements()
    {
        return $this->hasMany('RZP\Models\Settlement\Entity');
    }

    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getIfscCode()
    {
        return $this->getAttribute(self::IFSC_CODE);
    }

    public function getBankName()
    {
        return $this->getAttribute(self::BANK_NAME);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBeneficiaryCode()
    {
        return $this->getAttribute(self::BENEFICIARY_CODE);
    }

    public function getBeneficiaryCity()
    {
        return $this->getAttribute(self::BENEFICIARY_CITY);
    }

    public function getBeneficiaryPin()
    {
        return $this->getAttribute(self::BENEFICIARY_PIN);
    }

    public function getMpin()
    {
        return $this->getAttribute(self::MPIN);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getMobileBankingEnabled()
    {
        return $this->getAttribute(self::MOBILE_BANKING_ENABLED);
    }

    public function getBeneficiaryAddress1()
    {
        return $this->getAttribute(self::BENEFICIARY_ADDRESS1);
    }

    public function getBeneficiaryAddress2()
    {
        return $this->getAttribute(self::BENEFICIARY_ADDRESS2);
    }

    public function getBeneficiaryAddress3()
    {
        return $this->getAttribute(self::BENEFICIARY_ADDRESS3);
    }

    public function getBeneficiaryAddress4()
    {
        return $this->getAttribute(self::BENEFICIARY_ADDRESS4);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getBeneficiaryEmail()
    {
        return $this->getAttribute(self::BENEFICIARY_EMAIL);
    }

    public function getBeneficiaryMobile()
    {
        return $this->getAttribute(self::BENEFICIARY_MOBILE);
    }

    public function getBeneficiaryState()
    {
        return $this->getAttribute(self::BENEFICIARY_STATE);
    }

    public function getBeneficiaryCountry()
    {
        return $this->getAttribute(self::BENEFICIARY_COUNTRY);
    }

    public function getFtsFundAccountId()
    {
        return $this->getAttribute(self::FTS_FUND_ACCOUNT_ID);
    }

    public function setMobileBankingEnabled($mobileBankingEnabled)
    {
        return $this->setAttribute(self::MOBILE_BANKING_ENABLED, $mobileBankingEnabled);
    }

    public function setIsGatewaySync($isGatewaySync)
    {
        return $this->setAttribute(self::GATEWAY_SYNC, $isGatewaySync);
    }

    public function setMpin($mpin)
    {
        return $this->setAttribute(self::MPIN, $mpin);
    }

    public function setIfsc($ifsc)
    {
        return $this->setAttribute(self::IFSC_CODE, $ifsc);
    }

    public function setBeneficiaryName(string $name)
    {
        $this->setAttribute(self::BENEFICIARY_NAME, $name);
    }

    public function setAccountNumber($accountNumber)
    {
        $this->setAttribute(self::ACCOUNT_NUMBER, $accountNumber);
    }

    protected function setNameAttribute($name)
    {
        $this->setAttribute(self::BENEFICIARY_NAME, $name);
    }

    public function setFtsFundAccountId($ftsFundAccountId)
    {
        return $this->setAttribute(self::FTS_FUND_ACCOUNT_ID, $ftsFundAccountId);
    }

    protected function setIfscAttribute($code)
    {
        $this->setIfscCodeAttribute($code);
    }

    protected function setIfscCodeAttribute($code)
    {
        if ($code !== null)
        {
            $code = strtoupper($code);
        }

        $this->attributes[self::IFSC_CODE] = $code;
    }

    protected function setMpinAttribute($mpin)
    {
        if ($mpin === null)
        {
            $mpin = '';
        }

        $this->attributes[self::MPIN] = Crypt::encrypt($mpin, true, $this);
    }

    protected function getIfscCodeAttribute()
    {
        $code = $this->attributes[self::IFSC_CODE];

        if ($code !== null)
        {
            $code = strtoupper($code);
        }

        return $code;
    }

    protected function getIfscAttribute()
    {
        return $this->attributes[self::IFSC_CODE];
    }

    public function equals($baCopy)
    {
        $orig = $this->toArray();
        ksort($orig);
        unset(
            $orig[self::ID],
            $orig[self::CREATED_AT],
            $orig[self::UPDATED_AT],
            $orig[self::DELETED_AT],
            $orig[self::BENEFICIARY_ADDRESS4]);

        $copy = $baCopy->toArray();
        ksort($copy);
        unset(
            $copy[self::ID],
            $copy[self::CREATED_AT],
            $copy[self::UPDATED_AT],
            $copy[self::DELETED_AT],
            $copy[self::BENEFICIARY_CODE],
            $copy[self::BENEFICIARY_ADDRESS4]);

        $copy = array_filter($copy, function($value)
        {
            return is_null($value) === false;
        });

        return ($orig === $copy);
    }

    /**
     * Kotak requires a beneficiary code in 10 characters.
     * Beneficiary code is generated by using the first 7 + last 3 of
     * Bank account number id.
     *
     * @return string Beneficiary code in 10 characters.
     */
    protected function getKotakBeneficaryCode()
    {
        $id = $this->getAttribute(self::ID);

        $first7 = substr($id, 0, 7);

        $last3 = substr($id, -3);

        $beneficiaryCode = $first7 . $last3;

        $beneficiaryCode = strtoupper($beneficiaryCode);

        assertTrue(strlen($beneficiaryCode) === 10);

        return $beneficiaryCode;
    }

    public function associateCustomer($customer)
    {
        $this->attributes[self::ENTITY_ID] = $customer->getId();

        $this->attributes[self::TYPE] = Type::CUSTOMER;
    }

    public function associateMerchant($merchant,$type=null)
    {
        $this->attributes[self::ENTITY_ID] = $merchant->getId();

        $this->attributes[self::TYPE] = $type ?? Type::MERCHANT;
    }

    public function associateOrg($orgId,$type=null)
    {

        $this->attributes[self::ENTITY_ID] = $orgId;

        $this->attributes[self::TYPE] = $type ?? Type::ORG;
    }

    public function getRedactedAccountNumber()
    {
        $ac = $this->getAccountNumber();

        //
        // How many times should we repeat the redacted portion
        // This does not give a precise result,
        // but it looks good in groups of 4
        //
        // (strlen($ac) - 4) = Length of the segment we want to convert to X
        // divide by 4 to get number of such segments
        // and take ceil so we have a whole number of these

        $repeat = ceil((strlen($ac) - 4) / 4);

        // repeat this section $repeat times
        // and then just append the original last 4 digits
        return str_repeat('XXXX-', $repeat) . substr($ac, -4);
    }

    /**
     * Reutrns the first 4 chars from the IFSC, i.e. the bank code
     *
     * SBIN0001234 => SBIN
     *
     * @return string|null
     */
    public function getBankCode()
    {
        $ifsc = $this->getIfscCode();
        $code = substr($ifsc, 0, 4);

        if ((empty($ifsc) === true) or
            ($code === false))
        {
            return null;
        }

        if (isset(Netbanking::$defaultInconsistentBankCodesMapping[$code]) === true)
        {
            $code = Netbanking::$defaultInconsistentBankCodesMapping[$code];
        }

        return $code;
    }

    public function matches(array $input)
    {
        $new = (new Entity)->build($input, 'addBankTransfer');

        return (($this->getAccountNumber() === $new->getAccountNumber()) and
                ($this->getIfscCode() === $new->getIfscCode()) and
                ($this->getName() === $new->getName()));
    }

    public function toArrayHosted()
    {
        $data = parent::toArrayHosted();

        $data[self::ACCOUNT_TYPE] = $this->getAccountType();

        $data[self::BENEFICIARY_EMAIL] = $this->getBeneficiaryEmail();

        $data[self::BENEFICIARY_MOBILE] = $this->getBeneficiaryMobile();

        return $data;
    }

    public function toArrayTrace()
    {
        $data = $this->toArray();

        foreach($this->pii as $pii)
        {
            if(isset($data[$pii]) === false)
            {
                continue;
            }

            unset($data[$pii]);
        }

        return $data;
    }

    public function getDataForCheckout()
    {
        $data = $this->toArrayHosted();

        $data[self::ACCOUNT_NUMBER] = $this->getAccountNumber();

        unset($data[self::ID]);

        unset($data[self::ENTITY]);

        return $data;
    }

    public function getVirtualAccountTpvData(bool $bankCode = false)
    {
        $ifsc = $this->getIfscCode();

        if ($bankCode === true)
        {
            $ifsc = substr($ifsc, 0, 4);
        }

        return [
            self::IFSC              => $ifsc,
            self::ACCOUNT_NUMBER    => $this->getAccountNumber(),
        ];
    }

    public function isDeleted()
    {
        return ($this->getAttribute(self::DELETED_AT) !== null);
    }
}

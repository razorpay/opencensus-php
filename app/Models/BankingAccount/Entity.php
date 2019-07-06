<?php

namespace RZP\Models\BankingAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;

class Entity extends Base\PublicEntity
{
    const ID                                    = 'id';
    const CHANNEL                               = 'channel';
    const ACCOUNT_NUMBER                        = 'account_number';
    const ACCOUNT_IFSC                          = 'account_ifsc';
    const PINCODE                               = 'pincode';
    const STATUS                                = 'status';
    const BANK_INTERNAL_STATUS                  = 'bank_internal_status';
    const FTS_FUND_ACCOUNT_ID                   = 'fts_fund_account_id';
    const BALANCE_ID                            = 'balance_id';
    const BANK_REFERENCE_NUMBER                 = 'bank_reference_number';
    const USERNAME                              = 'username';
    const PASSWORD                              = 'password';
    const REFERENCE1                            = 'reference1';
    const BENEFICIARY_ADDRESS1                  = 'beneficiary_address1';
    const BENEFICIARY_ADDRESS2                  = 'beneficiary_address2';
    const BENEFICIARY_ADDRESS3                  = 'beneficiary_address3';
    const BENEFICIARY_EMAIL                     = 'beneficiary_email';
    const BENEFICIARY_MOBILE                    = 'beneficiary_mobile';
    const BENEFICIARY_NAME                      = 'beneficiary_name';
    const BENEFICIARY_PIN                       = 'beneficiary_pin';
    const BENEFICIARY_CITY                      = 'beneficiary_city';
    const BENEFICIARY_STATE                     = 'beneficiary_state';
    const BENEFICIARY_COUNTRY                   = 'beneficiary_country';

    const PINCODE_LENGTH    = '6';

    const ACCOUNT_TYPE      = 'CURRENT';

    const VAULT_NAMESPACE = 'banking_accounts_creds';

    // TODO: need to confirm this length
    const ACCOUNT_NUMBER_LENGTH     = '40';
    const ACCOUNT_IFSC_LENGTH  = '11';

    const PINCODES      = 'pincodes';
    const ACTION        = 'action';

    protected $entity = 'banking_account';

    protected static $sign = 'bankacc';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::CHANNEL,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
        self::STATUS,
        self::PINCODE,
        self::FTS_FUND_ACCOUNT_ID,
        self::BALANCE_ID,
        self::BANK_REFERENCE_NUMBER,
        self::BANK_INTERNAL_STATUS,
        self::USERNAME,
        self::PASSWORD,
        self::REFERENCE1,
        self::BENEFICIARY_MOBILE,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_ADDRESS1,
        self::BENEFICIARY_ADDRESS2,
        self::BENEFICIARY_ADDRESS3,
        self::BENEFICIARY_CITY,
        self::BENEFICIARY_STATE,
        self::BENEFICIARY_COUNTRY,
        self::BENEFICIARY_NAME,
    ];

    protected $visible = [
        self::ID,
        self::CHANNEL,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
        self::PINCODE,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
        self::BANK_INTERNAL_STATUS,
        self::USERNAME,
        self::PASSWORD,
        self::REFERENCE1
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::CHANNEL,
        self::BANK_REFERENCE_NUMBER,
        self::STATUS,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_IFSC,
        self::BANK_INTERNAL_STATUS,
        self::USERNAME,
        self::PASSWORD,
        self::REFERENCE1
    ];

    protected static $generators = [
        self::BANK_REFERENCE_NUMBER,
    ];

    // -------------------------- Generators --------------------------------- //

    public function generateBankReferenceNumber()
    {
        // TODO: fix.
        $id = substr(time(), 0, 5);

        $this->setAttribute(self::BANK_REFERENCE_NUMBER, $id);
    }

    // ---------------------------- Setters ----------------------------------- //

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setBankInternalStatus(string $internalStatus)
    {
        $this->setAttribute(self::BANK_INTERNAL_STATUS, $internalStatus);
    }

    public function setFtsFundAccountId(string $fundAccountId)
    {
        $this->setAttribute(self::FTS_FUND_ACCOUNT_ID, $fundAccountId);
    }

    // -------------------------- Getters ------------------------------------ //

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAccountIfsc()
    {
        return $this->getAttribute(self::ACCOUNT_IFSC);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    public function getBeneficiaryCity()
    {
        return $this->getAttribute(self::BENEFICIARY_CITY);
    }

    public function getBeneficiaryEMail()
    {
        return $this->getAttribute(self::BENEFICIARY_EMAIL);
    }

    public function getBeneficiaryState()
    {
        return $this->getAttribute(self::BENEFICIARY_STATE);
    }

    public function getBeneficiaryMobile()
    {
        return $this->getAttribute(self::BENEFICIARY_MOBILE);
    }

    public function getBeneficiaryAddress1()
    {
        return $this->getAttribute(self::BENEFICIARY_ADDRESS1);
    }

    public function getBeneficiaryCountry()
    {
        return $this->getAttribute(self::BENEFICIARY_COUNTRY);
    }

    public function getBankName()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getFtsFundAccountId()
    {
        return $this->getAttribute(self::FTS_FUND_ACCOUNT_ID);
    }

    public function getAccountType()
    {
        return self::ACCOUNT_TYPE;
    }

    public function getUsername()
    {
        return $this->getAttribute(self::USERNAME);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getReference1()
    {
        return $this->getAttribute(self::REFERENCE1);
    }

    // --------------------------- Mutators ----------------------------------- //

    public function setPasswordAttribute(string $password)
    {
        $bankingAccountCore = new Core();

        $token = $bankingAccountCore->tokenizeBankingAccountCredentials($password);

        $this->attributes[self::PASSWORD] = $token;
    }

    // --------------------------- Relations ---------------------------------- //

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }
}

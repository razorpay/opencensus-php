<?php

namespace RZP\Models\FundAccount;

use RZP\Constants;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Contact;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\WalletAccount;
use RZP\Models\VirtualAccount\Provider;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Card\Entity|BankAccount\Entity|Vpa\Entity|WalletAccount\Entity account
 * @property Merchant\Entity merchant
 */
class Entity extends Base\PublicEntity
{

    use SoftDeletes;

    // Attributes
    const ACCOUNT_TYPE  = 'account_type';
    const ACCOUNT_ID    = 'account_id';
    const SOURCE_TYPE   = 'source_type';
    const SOURCE_ID     = 'source_id';
    const BATCH_ID      = 'batch_id';
    const ACTIVE        = 'active';
    const UNIQUE_HASH   = 'unique_hash';

    // Relations
    const SOURCE        = 'source';
    const ACCOUNT       = 'account';

    // Additional input/output attributes
    const CONTACT_ID    = 'contact_id';
    const CUSTOMER_ID   = 'customer_id';
    const CONTACT       = 'contact';
    const CUSTOMER      = 'customer';
    // Details is basically publicly exposed underlying account
    const DETAILS       = 'details';
    // Bank Account is basically publicly exposed underlying account
    // when account type is bank account
    const BANK_ACCOUNT  = 'bank_account';
    // VPA is basically publicly exposed underlying account
    // when account type is VPA
    const VPA           = 'vpa';
    // Card is basically publicly exposed underlying account
    // when account type is card
    const CARD          = 'card';
    // Wallet_account is basically publicly exposed underlying account
    // when account type is wallet
    const WALLET        = 'wallet';

    const NAME          = 'name';

    // merchant_disabled flag is publicly exposed only for merchant dashboard
    // requests to indicate the merchant status on FE by providing info if
    // merchant has been blocked for the product associated with the fund account
    const MERCHANT_DISABLED = 'merchant_disabled';

    const PROVIDER = 'provider';

    const WALLET_ACCOUNT = 'wallet_account';

    const IDEMPOTENCY_KEY = 'idempotency_key';

    // input key
    const ACCOUNT_NUMBER = 'account_number';

    const RESPONSE_CODE   = 'response_code';

    const FUND_ACCOUNT_RX_RETRY_COUNT = '2';

    const FUND_ACCOUNT_BULK_RX_RETRY_COUNT = '2';

    const VA_TO_VA_BLOCKING_MAPPING = [
        '222333'    => Provider::IFSC[Provider::YESBANK],
        '787878'    => Provider::IFSC[Provider::YESBANK],
        '456456'    => Provider::IFSC[Provider::YESBANK],
        '2233'      => Provider::IFSC[Provider::ICICI],
        '2244'      => Provider::IFSC[Provider::ICICI],
        '5656'      => Provider::IFSC[Provider::ICICI],
        '3434'      => Provider::IFSC[Provider::ICICI],
        '2224'      => Provider::IFSC[Provider::RBL],
        '2223'      => Provider::IFSC[Provider::RBL],
        '567890'    => Provider::IFSC[Provider::RBL],
    ];

    protected $generateIdOnCreate = true;

    protected $composite = false;

    protected $fillable = [
        self::ACTIVE,
        self::IDEMPOTENCY_KEY,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
        self::CUSTOMER_ID,
        self::CONTACT,
        self::CUSTOMER,
        self::ACCOUNT_TYPE,
        self::DETAILS,
        self::MERCHANT_DISABLED,
        self::BANK_ACCOUNT,
        self::CARD,
        self::BATCH_ID,
        self::VPA,
        self::ACTIVE,
        self::CREATED_AT,
        self::WALLET,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE_ID,
        self::SOURCE,
        self::BATCH_ID,
        self::BANK_ACCOUNT,
        self::VPA,
        self::CARD,
        self::DETAILS,
        self::WALLET,
        self::ACCOUNT_TYPE,
        self::MERCHANT_DISABLED,
    ];

    protected $publicAuth = [
        self::ID,
        self::ACCOUNT_TYPE,
        self::CARD,
        self::VPA,
        self::BANK_ACCOUNT,
        self::WALLET,
    ];

    protected $defaults = [
        self::ACTIVE            => true,
        self::IDEMPOTENCY_KEY   => null,
        self::UNIQUE_HASH       => null,
    ];

    protected $casts = [
        self::ACTIVE => 'bool',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected static $sign = 'fa';

    protected $entity = 'fund_account';

    // --------------- Getters ---------------

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getContactId()
    {
        return $this->getAttribute(self::CONTACT_ID);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getAccountId()
    {
        return $this->getAttribute(self::ACCOUNT_ID);
    }

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

    public function getIdempotencyKey()
    {
        return $this->getAttribute(self::IDEMPOTENCY_KEY);
    }

    public function getActive(): bool
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function getAccountDestinationAsText(): string
    {
        switch ($this->getAccountType())
        {
            case Type::BANK_ACCOUNT:
                return mask_except_last4($this->account->getAccountNumber());

            case Type::VPA:
                return $this->account->getAddress();

            case Type::CARD:
                return $this->account->getFormatted();

            case Type::WALLET_ACCOUNT:
                return $this->account->getPhone();
        }
    }

    public function getAccountTypeAsText(): string
    {
        switch ($this->getAccountType())
        {
            case Type::VPA:
                return 'VPA';

            // Generic format for all other types, but be explicit.
            case Type::BANK_ACCOUNT:
            case Type::WALLET_ACCOUNT:
            case Type::CARD:
                return ucfirst(str_replace('_', ' ', $this->getAccountType()));
        }
    }

    public function getUniqueHash()
    {
        return $this->getAttribute(self::UNIQUE_HASH);
    }

    // ------------- End Getters -------------

    // --------------- Setters ---------------

    /**
     * The 'source' relation is polymorphic internally. Externally, we want to
     * show it separately as 'contact_id' and 'customer_id'.
     *
     * @param array $attributes
     */
    public function setPublicSourceIdAttribute(array & $attributes)
    {
        $sourceId   = $attributes[self::SOURCE_ID];
        $sourceType = $attributes[self::SOURCE_TYPE];

        if ($sourceType === Constants\Entity::CONTACT)
        {
            $attributes[self::CONTACT_ID] = Contact\Entity::getSignedIdOrNull($sourceId);
        }
        else if ($sourceType === Constants\Entity::CUSTOMER)
        {
            $attributes[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($sourceId);
        }
    }

    /**
     * Refer comments at setPublicSourceIdAttribute() & contact() for details.
     *
     * @param array $attributes
     */
    public function setPublicSourceAttribute(array & $attributes)
    {
        // Don't forget these attributes if a composite payout request is made through strictPrivateAuth as we need to
        // show contact in the response of composite payout.
        if ((app('basicauth')->isStrictPrivateAuth() === true) and
            !(($this->isComposite() === true) or app('basicauth')->isSlackApp()))
        {
            array_forget($attributes, [self::SOURCE, self::CONTACT, self::CUSTOMER]);

            return;
        }

        $sourceType = $attributes[self::SOURCE_TYPE];

        $source = array_pull($attributes, self::SOURCE) ?:
            array_pull($attributes, self::CONTACT) ?:
            array_pull($attributes, self::CUSTOMER);

        if (empty($source) === false)
        {
            $attributes[$sourceType] = $source;
        }
    }

    public function setPublicDetailsAttribute(array & $array)
    {
        if (($this->getMerchantId() !== Merchant\Account::MEDLIFE) and
            ($this->getMerchantId() !== Merchant\Account::OKCREDIT))
        {
            unset ($array[self::DETAILS]);

            return;
        }

        $accountType = array_get($array, self::ACCOUNT_TYPE);

        $accountAttributes = $this->getAccountDetails($accountType);

        $array[self::DETAILS] = $accountAttributes;

        $array[$accountType] = $accountAttributes;
    }

    public function setPublicBankAccountAttribute(array & $array)
    {
        if (array_get($array, self::ACCOUNT_TYPE) === self::BANK_ACCOUNT)
        {
            $accountAttributes = $this->getAccountDetails(self::BANK_ACCOUNT);

            $array[self::BANK_ACCOUNT] = $accountAttributes;
        }
    }

    public function setPublicVpaAttribute(array & $array)
    {
        if (array_get($array, self::ACCOUNT_TYPE) === self::VPA)
        {
            $accountAttributes = $this->getAccountDetails(self::VPA);

            $array[self::VPA] = $accountAttributes;
        }
    }

    public function setPublicWalletAttribute(array & $array)
    {
        $accountType = array_get($array, self::ACCOUNT_TYPE);

        if (in_array($accountType, [self::WALLET_ACCOUNT, self::WALLET], true) === true)
        {
            $accountAttributes = $this->getAccountDetails(self::WALLET_ACCOUNT);

            $array[self::WALLET] = $accountAttributes;
        }
    }

    public function setPublicCardAttribute(array & $array)
    {
        if (array_get($array, self::ACCOUNT_TYPE) === self::CARD)
        {
            $accountAttributes = $this->getAccountDetails(self::CARD);

            $array[self::CARD] = $accountAttributes;
        }
    }

    public function setPublicAccountTypeAttribute(array & $array)
    {
        if (array_get($array, self::ACCOUNT_TYPE) === self::WALLET_ACCOUNT)
        {
            $array[self::ACCOUNT_TYPE] = self::WALLET;
        }
    }

    public function setPublicBatchIdAttribute(array & $attributes)
    {
        $batchId = $this->getAttribute(self::BATCH_ID);

        $attributes[self::BATCH_ID] = Batch\Entity::getSignedIdOrNull($batchId);
    }

    public function setPublicMerchantDisabledAttribute(array & $attributes)
    {
        $accountType = array_get($attributes, self::ACCOUNT_TYPE);

        // 'merchant_disabled' field is sent in the response
        // only for dashboard requests
        if (app('basicauth')->isProxyAuth() === true)
        {
            switch ($accountType)
            {
                case self::WALLET_ACCOUNT:
                    // fallthrough to 'wallet' for handling cases where account_type
                    // is either 'wallet' or 'wallet_account' depending on the order of
                    // execution of setPublic functions
                case self::WALLET:
                    $accountAttributes = $this->getAccountDetails(self::WALLET_ACCOUNT);
                    $isMerchantDisabled = (new Core)->checkMerchantDisabledForWalletProvider($accountAttributes[self::PROVIDER]);

                    $attributes[self::MERCHANT_DISABLED] = $isMerchantDisabled;
                    break;

                default:
                    // Currently setting it to false for fund_accounts of type
                    // bank_account, vpa and card
                    $attributes[self::MERCHANT_DISABLED] = false;
            }
        }
    }

    public function setBatchId(string $batchId)
    {
        $this->setAttribute(self::BATCH_ID,$batchId);
    }

    public function setComposite(bool $composite)
    {
        $this->composite = $composite;

        return $this;
    }

    public function setUniqueHash(string $uniqueHash)
    {
        $this->setAttribute(self::UNIQUE_HASH, $uniqueHash);
    }

    // ------------- End Setters -------------

    // --------------- Helpers ---------------

    public function isActive(): bool
    {
        return ($this->getActive() === true);
    }

    public function isComposite()
    {
        return ($this->composite === true);
    }

    // ------------- End Helpers -------------

    // -------------- Relations --------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch\Entity::class);
    }

    public function validations()
    {
        return $this->hasMany(Validation\Entity::class);
    }

    public function account()
    {
        return $this->morphTo();
    }

    public function source()
    {
        return $this->morphTo();
    }

    /**
     * We have these extra relation methods contact() and customer() just to allow
     * these literals in expand of public api requests, because we have exposed contact_id, contact
     * & customer_id, customer pairs, not source_id, source pair.
     *
     * Known issue: If someone sends expand[]=contact, but if the related source is of type customer
     * the api response will return customer_id & customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function contact()
    {
        return $this->morphTo(Entity::CONTACT, Entity::SOURCE_TYPE, Entity::SOURCE_ID);
    }

    /**
     * Refer comment at contact().
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function customer()
    {
        return $this->morphTo(Entity::CUSTOMER, Entity::SOURCE_TYPE, Entity::SOURCE_ID);
    }

    // ------------ End Relations ------------

    // -------------- Mutators ---------------

    // ------------ End Mutators -------------

    // -------------- Accessors --------------

    // ------------ End Accessors ------------

    public function getAccountDetails(string $accountType)
    {
        $accountAttributes = $this->account->toArrayPublic();

        if ($accountType === Type::CARD)
        {
            $accountAttributes = $this->account->toArrayFundAccount();
        }

        if (app('basicauth')->isPayoutService() === true)
        {
            array_forget($accountAttributes, Base\PublicEntity::ENTITY);
        }
        else
        {
            // For now, don't expose the public id and entity attributes from any of the related entities
            array_forget($accountAttributes, [Base\PublicEntity::ID, Base\PublicEntity::ENTITY]);
        }
        return $accountAttributes;
    }
}

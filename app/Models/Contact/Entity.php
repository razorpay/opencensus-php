<?php

namespace RZP\Models\Contact;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * Class Entity
 *
 * @package RZP\Models\Contact
 *
 * @property Merchant\Entity $merchant
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    // Attributes
    const NAME          = 'name';
    const CONTACT       = 'contact';
    const EMAIL         = 'email';
    const TYPE          = 'type';
    const BATCH_ID      = 'batch_id';

    const FUND_ACCOUNTS = 'fund_accounts';

    //
    // Reference ID is metadata set by the merchant, this does not
    // refer to any entity on our system
    //
    const REFERENCE_ID = 'reference_id';
    const NOTES        = 'notes';
    const ACTIVE       = 'active';

    // Additional input & output attributes
    const ACCOUNT_NUMBER  = 'account_number';
    const FUND_ACCOUNT_ID = 'fund_account_id';
    const IDEMPOTENCY_KEY = 'idempotency_key';
    const PAYMENT_TERMS   = 'payment_terms';
    const TDS_CATEGORY    = 'tds_category';
    const VENDOR          = 'vendor';
    const PAN             = 'pan';
    const GST_IN          = 'gstin';
    const EXPENSE_ID      = 'expense_id';
    const RESPONSE_CODE   = 'response_code';

    // Raw Email used exclusively for ES
    const EMAIL_RAW = 'email.raw';
    const CONTACT_RX_RETRY_COUNT = '2';

    //Partial Search
    const CONTACT_PS       = 'contact_ps';
    const EMAIL_PS         = 'email_ps';

    //Partial search used in ES
    const CONTACT_EMAIL_PARTIAL_SEARCH = 'email.partial_search';
    const CONTACT_NUMBER_PARTIAL_SEARCH = 'contact.partial_search';

    protected $generateIdOnCreate = true;

    protected $isPSPayout = false;

    protected $fillable = [
        self::NAME,
        self::CONTACT,
        self::EMAIL,
        self::TYPE,
        self::REFERENCE_ID,
        self::ACTIVE,
        self::NOTES,
        self::IDEMPOTENCY_KEY,
        self::GST_IN
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::CONTACT,
        self::EMAIL,
        self::TYPE,
        self::REFERENCE_ID,
        self::BATCH_ID,
        self::ACTIVE,
        self::NOTES,
        self::FUND_ACCOUNTS,
        self::CREATED_AT,
        self::PAYMENT_TERMS,
        self::TDS_CATEGORY,
        self::VENDOR,
        self::EXPENSE_ID,
        self::GST_IN,
    ];

    protected $defaults = [
        self::CONTACT           => null,
        self::EMAIL             => null,
        self::TYPE              => null,
        self::REFERENCE_ID      => null,
        self::NOTES             => [],
        self::ACTIVE            => true,
        self::IDEMPOTENCY_KEY   => null
    ];

    protected $casts = [
        self::ACTIVE => 'bool',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::BATCH_ID,
        self::FUND_ACCOUNTS,
        self::PAYMENT_TERMS,
        self::TDS_CATEGORY,
        self::VENDOR,
        self::EXPENSE_ID,
        self::GST_IN,
    ];

    protected $publicAuth = [
        self::ID,
        self::NAME,
        self::FUND_ACCOUNTS,
    ];

    /**
     * Mainly used for `expands`
     *
     * @var array
     */
    protected $embeddedRelations = [
        self::FUND_ACCOUNTS,
    ];

    protected static $sign = 'cont';

    protected $entity = 'contact';

    protected $paymentTerms = null;

    protected $tdsCategory = null;

    protected $vendor = null;

    protected $expenseId = null;

    protected $gstIn = null;

    // --------------- Getters ---------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

    public function getActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    // ------------- End Getters -------------

    // --------------- Setters ---------------

    public function setEmail(string $email = null)
    {
        $this->setAttribute(self::EMAIL, $email);
    }

    public function setType(string $type = null)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setName(string $name = null)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setPaymentTerms(int $paymentTerms)
    {
        $this->paymentTerms = $paymentTerms;
    }

    public function setTdsCategory(int $tdsCategory)
    {
        $this->tdsCategory = $tdsCategory;
    }

    public function setVendor(array $vendorDetails)
    {
        $this->vendor = $vendorDetails;
    }

    public function setExpenseId(string $expenseId = null)
    {
        $this->expenseId = $expenseId;
    }

    public function setGstIn(string $gstIn = null)
    {
        $this->gstIn = $gstIn;
    }

    public function setIsPSPayout(bool $isPSPayout)
    {
        $this->isPSPayout = $isPSPayout;
    }

    // ------------- End Setters -------------

    // ----------- Public Setters ------------

    public function setPublicBatchIdAttribute(array &$attributes)
    {
        $batchId = $this->getAttribute(self::BATCH_ID);

        $attributes[self::BATCH_ID] = Batch\Entity::getSignedIdOrNull($batchId);
    }

    public function setPublicFundAccountsAttribute(array &$attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        //
        // For other auths (private, proxy,etc), fund_accounts of a contact can be retrieved
        // by `expands`. Currently, `expands` doesn't work for `public` auth. Until that is
        // fixed, this is a temporary solution / hack.
        //
        // Currently, we don't want to add `fund_accounts` in default expands. If and when we
        // decide to add in default expands, we can remove the public setter for public auth.
        //
        if ($basicAuth->isPublicAuth() === true)
        {
            $attributes[self::FUND_ACCOUNTS] = $this->fundAccounts()
                                                    ->where(FundAccount\Entity::ACTIVE, 1)
                                                    ->getResults()
                                                    ->toArrayPublicEmbedded();
        }
    }

    public function setPublicPaymentTermsAttribute(array &$attributes)
    {
        if ($this->shouldVendorDetailsBeAdded($attributes))
        {
            $attributes[self::PAYMENT_TERMS] = $this->paymentTerms;
        }
    }

    public function setPublicGstinAttribute(array &$attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        // Earlier gstin was not stored in contacts and other services stored extra metadata on
        // their service. gstin was one such metadata. Now gstin is stored in contacts and we would
        // prefer returning whatever comes from other services instead of contacts table for
        // backward compatibility
        if (($this->shouldGstinBeAdded($attributes)) or
            ($this->shouldVendorDetailsBeAdded($attributes)))
        {
            $attributes[self::GST_IN] = $this->gstIn === null ? $this->getAttribute(self::GST_IN):$this->gstIn;
        }
        else if (($basicAuth->isPrivateAuth() === true) and
                 ($this->isPSPayout() === false))
        {
            unset($attributes[self::GST_IN]);
        }
    }

    public function setPublicExpenseIdAttribute(array &$attributes)
    {
        if ($this->shouldVendorDetailsBeAdded($attributes))
        {
            $attributes[self::EXPENSE_ID] = $this->expenseId;
        }
    }

    public function setPublicTdsCategoryAttribute(array &$attributes)
    {
        if ($this->shouldVendorDetailsBeAdded($attributes))
        {
            $attributes[self::TDS_CATEGORY] = $this->tdsCategory;
        }
    }

    public function setPublicVendorAttribute(array &$attributes)
    {
        if ($this->shouldVendorDetailsBeAdded($attributes))
        {
            $attributes[self::VENDOR] = $this->vendor;
        }
    }

    public function setBatchId(string $batchId)
    {
        $this->setAttribute(self::BATCH_ID,$batchId);
    }

    // --------- End Public Setters ----------

    // --------------- Helpers ---------------

    public function isPSPayout() : bool
    {
        return ($this->isPSPayout === true);
    }

    public function isActive(): bool
    {
        return ($this->getActive() === true);
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

    public function fundAccounts()
    {
        return $this->hasMany(FundAccount\Entity::class, FundAccount\Entity::SOURCE_ID);
    }

    // ------------ End Relations ------------

    // -------------- Mutators ---------------

    // ------------ End Mutators -------------

    // -------------- Accessors --------------

    // ------------ End Accessors ------------

    private function shouldVendorDetailsBeAdded(array &$attributes): bool
    {
        if ($attributes[self::TYPE] != Type::VENDOR)
        {
            return false;
        }

        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        return $basicAuth->isProxyOrPrivilegeAuth() === true;
    }

    private function shouldGstinBeAdded(array &$attributes): bool
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        return $basicAuth->isProxyOrPrivilegeAuth() === true;
    }

}

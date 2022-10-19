<?php

namespace RZP\Models\QrCode;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Checkout\Order\Repository as CheckoutOrderRepository;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Customer;
use RZP\Models\FileStore;
use RZP\Models\VirtualAccount;
use RZP\Models\BharatQr\Tags;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Constants\Entity as Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Order\Repository as OrderRepository;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status as QrStatus;

class Entity extends Base\PublicEntity
{

    use NotesTrait;

    const ID          = 'id';
    const MERCHANT_ID = 'merchant_id';
    //
    // Reference will always be equal to id in case
    // qr code is generated before payment happens.
    // If qr code is generated after payment is done
    // we set the reference equal to the reference sent
    // by bank.
    //
    // There is no unique db constraint on reference.
    // This is so because if a virtual account associated
    // with a qr code is closed and we again get payment
    // notification on same reference we will generate qr code
    // again with same reference.
    //
    const REFERENCE       = 'reference';
    const PROVIDER        = 'provider';
    const ENTITY_ID       = 'entity_id';
    const ENTITY_TYPE     = 'entity_type';
    const AMOUNT          = 'amount';
    const QR_STRING       = 'qr_string';
    const SHORT_URL       = 'short_url';
    const MPANS_TOKENIZED = 'mpans_tokenized';
    const MIME_TYPE       = 'image/jpeg';
    const REQ_USAGE_TYPE  = 'usage_type';
    const STATUS          = 'status';
    const DESCRIPTION     = 'description';
    const CLOSE_BY        = 'close_by';
    const NAME            = 'name';
    const NOTES           = 'notes';
    const CUSTOMER_ID     = 'customer_id';

    protected static $sign = 'qr';

    protected $entity = 'qr_code';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::PROVIDER,
        self::REFERENCE,
        self::QR_STRING,
        self::MPANS_TOKENIZED,
        self::AMOUNT,
        self::REQ_USAGE_TYPE,
        self::STATUS,
        self::DESCRIPTION,
        self::CLOSE_BY,
        self::NAME,
        self::NOTES,
    ];

    protected $defaults = [
        self::CLOSE_BY                 => null,
    ];

    protected $visible = [
        self::ID,
        self::REFERENCE,
        self::AMOUNT,
        self::PROVIDER,
        self::SHORT_URL,
        self::QR_STRING,
        self::CREATED_AT,
        self::AMOUNT,
        self::REQ_USAGE_TYPE,
        self::STATUS,
        self::DESCRIPTION,
        self::CLOSE_BY,
        self::NAME,
        self::NOTES,
        self::CUSTOMER_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::REFERENCE,
        self::SHORT_URL,
        self::CREATED_AT,
        self::REQ_USAGE_TYPE,
        self::STATUS,
        self::DESCRIPTION,
        self::CLOSE_BY,
        self::NAME,
        self::NOTES,
        self::CUSTOMER_ID,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
        self::CLOSE_BY => 'int',
    ];

    protected static $generators = [
        self::ID,
        self::REFERENCE,
    ];

    /*
     * This contains parameters which needs to be removed from toArrayPublic() response
    if the merchant level feature flag `upiqr_v1_hdfc` is absent
    */

    protected $params = [
        self::REQ_USAGE_TYPE,
        self::STATUS,
        self::CLOSE_BY,
        self::NAME,
        self::NOTES,
        self::CUSTOMER_ID
    ];

    protected $ignoredRelations = [
        'source'
    ];

    // --------------------- RELATIONS ---------------------

    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function files()
    {
        return $this->morphMany(FileStore\Entity::class, 'entity');
    }

    public function payments()
    {
        return $this->morphMany(Payment\Entity::class, 'source');
    }

    public function virtualAccount()
    {
        return $this->hasOne(VirtualAccount\Entity::class);
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    // --------------------- END RELATIONS ---------------------

    public function generateReference($input)
    {
        if (isset($input[self::REFERENCE]) === false)
        {
            $this->setReference($this->getId());
        }
    }

    public function getSourceAttribute()
    {
        $source = null;

        if ($this->relationLoaded('source') === true)
        {
            $source = $this->getRelation('source');
        }

        if ($source !== null)
        {
            return $source;
        }

        if ($this->getEntityType() === Constants::ORDER)
        {
            $source = $this->source()->with('offers')->first();
        }
        else if ($this->getEntityId() !== null)
        {
            $source = $this->source()->first();
        }

        if (empty($source) === false)
        {
            return $source;
        }

        if ($this->getEntityType() === Constants::ORDER)
        {
            $order = (new OrderRepository())->findOrFailPublic($this->getEntityId());

            $this->source()->associate($order);

            return $order;
        }

        if ($this->getEntityType() === Constants::CHECKOUT_ORDER)
        {
            $checkoutOrder = (new CheckoutOrderRepository())->findOrFailPublic($this->getEntityId());

            $this->source()->associate($checkoutOrder);

            return $checkoutOrder;
        }

        return null;
    }

    public function toArrayPublic()
    {

        $publicArray = parent::toArrayPublic();

        if($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === false)
        {
            foreach ($this->params as $piiField)
            {
                unset($publicArray[$piiField]);
            }

        }

        return $publicArray;
    }

    // --------------------- GETTERS ---------------------

    /**
     * This function is used in case of polymorphic relations where we associate one entity
     * with multiple other entities using (entity_type and entity_id). It determines the string that
     * will be stored for entity_type when the association is with the QrCode entity.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->entity;
    }

    public function getCloseBy()
    {
        return ($this->getAttribute(self::CLOSE_BY));
    }

    /**
     * Gets the most recent qrcode file
     *
     * @return FileStore\Entity
     */
    public function qrCodeFile(): FileStore\Entity
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::QR_CODE_IMAGE)
                    ->firstOrFail();
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getReference()
    {
        return $this->getAttribute(self::REFERENCE);
    }

    // get original qr_string which is stored in db
    public function getOriginalQrString()
    {
        return $this->getAttribute(self::QR_STRING);
    }

    public function getUsageType()
    {
        return $this->getAttribute(self::REQ_USAGE_TYPE);
    }

    public function getQrString()
    {
        $qrStringFromDb = $this->getOriginalQrString();

        if ($this->getMpansTokenized() !== true)
        {
            return $qrStringFromDb;
        }

        return $this->getQrStringWithDetokenizedMpans($qrStringFromDb);
    }

    public function getProvider()
    {
        return $this->getAttribute(self::PROVIDER);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getFormattedAmount()
    {
        $amount = $this->getAmount();

        if (empty($amount) === true)
        {
            return null;
        }

        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * Returns string to be used a qrcode file path in s3 store.
     * Format: qrcode/{qrcodeid}_{epoch}
     *
     * @return string
     */
    public function getQrCodeFilename(): string
    {
        return 'qrcodes/' . $this->getId();
    }

    public function isGeneratedByMerchant()
    {
        return ($this->getReference() !== $this->getId());
    }

    public function isClosed()
    {
        return ($this->getAttribute(self::STATUS) === QrStatus::CLOSED) or
            (($this->getAttribute(self::CLOSE_BY) !== null) and
                (Carbon::now()->getTimestamp() >= $this->getAttribute(self::CLOSE_BY)));
    }

    // returns true if mpans stored in qr_string are tokenized
    public function getMpansTokenized()
    {
        return (bool) $this->getAttribute(self::MPANS_TOKENIZED);
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- SETTERS ---------------------

    public function setReference(string $reference)
    {
        $this->setAttribute(self::REFERENCE, $reference);
    }

    public function setShortUrl(string $shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setQrString(string $qrString)
    {
        // only bharat_qr provider have mpans in qr_string
        if ($this->getProvider() !== 'bharat_qr')
        {
            $this->setAttribute(self::QR_STRING, $qrString);

            return;
        }

        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'];

        $variant = $app['razorx']->getTreatment($app['request']->getTaskId(),
                                                RazorxTreatment::TOKENIZE_QR_STRING_MPANS, $mode, 2);

        if (strtolower($variant) !== 'on')
        {
            $this->setAttribute(self::QR_STRING, $qrString);

            return;
        }

        $tokenizedMpansQrString = self::getQrStringWithTokenizedMpans($qrString);

        $app['trace']->info(TraceCode::SETTING_QR_STRING_WITH_MPANS_TOKENIZED, [
            'qr_code_id'       => $this->getId(),
            'tokenized_string' => $tokenizedMpansQrString,
        ]);

        $this->setAttribute(self::QR_STRING, $tokenizedMpansQrString);

        if (strlen($qrString) !== strlen($tokenizedMpansQrString))
        {
            $app['trace']->info(TraceCode::SETTING_QR_CODE_MPANS_TOKENIZED_TO_TRUE, []);

            $this->setMpansTokenized(true);
        }
    }

    public function setMpansTokenized(bool $areMpansTokenized)
    {
        $this->setAttribute(self::MPANS_TOKENIZED, $areMpansTokenized);
    }

    public function setStatus(string $status)
    {
        NonVirtualAccountQrCode\Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setCloseBy(string $closeBy)
    {
        $this->setAttribute(self::CLOSE_BY, $closeBy);
    }

    public function setName(string $name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setReqUsageType(string $usageType)
    {
        $this->setAttribute(self::REQ_USAGE_TYPE, $usageType);
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function generateQrString()
    {
        $qrString = (new Generator)->generateQrString($this);

        $this->setQrString($qrString);

        return $this;
    }

    // --------------------- END SETTERS ---------------------
    public static function getQrStringWithTokenizedMpans($qrString)
    {
        if (empty($qrString) === true)
        {
            return $qrString;
        }

        $app = App::getFacadeRoot();

        $mpanVaultApp = $app['mpan.cardVault'];

        $tagValueMap = self::getTagValueMapFromQrString($qrString);

        foreach ($tagValueMap as $tag => $value)
        {
            if (in_array($tag, [Tags::VISA, TAGS::MASTERCARD, TAGS::RUPAY]) === true)
            {
                // tokenize the mpan, if not already tokenized
                // We store only 15 characters of mastercard mpan in qr_strings
                if ((strlen($value) === 16) or (($tag === Tags::MASTERCARD) and (strlen($value) === 15)))
                {
                    $tokenizedMpan = $mpanVaultApp->tokenize(['secret' => $value]);

                    $tagValueMap[$tag] = $tokenizedMpan;
                }
            }
        }

        $qrStringWithTokenizedMpans = self::getQrStringFromTagValueMap($tagValueMap);

        return $qrStringWithTokenizedMpans;
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public static function getQrStringWithDetokenizedMpans($qrString)
    {
        if (empty($qrString) === true)
        {
            return $qrString;
        }

        $app = App::getFacadeRoot();

        $mpanVaultApp = $app['mpan.cardVault'];

        $tagValueMap = self::getTagValueMapFromQrString($qrString);

        foreach ($tagValueMap as $tag => $value)
        {
            if (in_array($tag, [Tags::VISA, TAGS::MASTERCARD, TAGS::RUPAY]) === true)
            {
                // detokenize the mpan, if tokenized
                // We store only 15 characters of mastercard mpan in qr_strings
                if ((($tag !== Tags::MASTERCARD) and (strlen($value) !== 16))
                    or (($tag === Tags::MASTERCARD) and (strlen($value) !== 15)))
                {
                    $detokenizedMpan = $mpanVaultApp->detokenize($value);

                    /*
                    * Masterpass specifications indicate only 15 digits of mastercard mpan be populated in the qr
                    * string. The last digit is generated by the bank/app at the time of scanning and validated using
                    * luhn formula. Since many apps follows Masterpass specifications, we are making this change at
                    * our end as well.
                    */
                    if ($tag == Tags::MASTERCARD)
                    {
                        $detokenizedMpan = substr($detokenizedMpan, 0, 15);
                    }

                    $tagValueMap[$tag] = $detokenizedMpan;
                }
            }
        }

        $qrStringWithTokenizedMpans = self::getQrStringFromTagValueMap($tagValueMap);

        return $qrStringWithTokenizedMpans;
    }

    public static function getTagValueMapFromQrString(string $qrString)
    {
        $tlvArray = [];

        $length = strlen($qrString);

        $index = 0;

        while ($index < $length)
        {
            $tlvTag = substr($qrString, $index, 2);

            $index += 2;

            $tlvLength = (int) substr($qrString, $index, 2);

            $index += 2;

            $tlvArray[$tlvTag] = substr($qrString, $index, $tlvLength);

            $index += $tlvLength;
        }

        return $tlvArray;
    }

    public static function getQrStringFromTagValueMap(array $tagValueMap)
    {
        $qrString = '';

        foreach ($tagValueMap as $tag => $value)
        {
            $tagString = $tag . self::getLengthAndValue($value);

            $qrString .= $tagString;
        }

        return $qrString;
    }

    protected static function getLengthAndValue(string $str)
    {
        return str_pad(strlen($str), 2, '0', STR_PAD_LEFT) . $str;
    }
}

<?php

namespace RZP\Models\Payment;

use Carbon\Carbon;
use Lib\PhoneBook;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Refund;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const AMOUNT                = 'amount';
    const AMOUNT_AUTHORIZED     = 'amount_authorized';
    const AMOUNT_REFUNDED       = 'amount_refunded';
    const STATUS                = 'status';
    const ORDER_ID              = 'order_id';
    const INTERNATIONAL         = 'international';
    const METHOD                = 'method';
    const REFUND_STATUS         = 'refund_status';
    const CAPTURED              = 'captured';
    const CURRENCY              = 'currency';
    const DESCRIPTION           = 'description';
    const ERROR_CODE            = 'error_code';
    const INTERNAL_ERROR_CODE   = 'internal_error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const CUSTOMER_ID           = 'customer_id';
    const GLOBAL_CUSTOMER_ID    = 'global_customer_id';
    const APP_ID                = 'app_id';
    const APP_TOKEN             = 'app_token';
    const TOKEN                 = 'token';
    const TOKEN_ID              = 'token_id';
    const GLOBAL_TOKEN_ID       = 'global_token_id';
    const EMAIL                 = 'email';
    const CONTACT               = 'contact';
    const NOTES                 = 'notes';
    const BANK                  = 'bank';
    const CARD_ID               = 'card_id';
    const WALLET                = 'wallet';
    const EMI_PLAN_ID           = 'emi_plan_id';
    const EMI_DURATION          = 'emi_duration';
    const TRANSACTION_ID        = 'transaction_id';
    const AUTO_CAPTURED         = 'auto_captured';
    const AUTHORIZED_AT         = 'authorized_at';
    const CAPTURED_AT           = 'captured_at';
    const GATEWAY               = 'gateway';
    const TERMINAL_ID           = 'terminal_id';
    const SIGNED                = 'signed';
    const VERIFIED              = 'verified';
    const CALLBACK_URL          = 'callback_url';
    const SERVICE_TAX           = 'service_tax';
    const OTP_ATTEMPTS          = 'otp_attempts';
    const OTP_COUNT             = 'otp_count';
    const FEE                   = 'fee';
    const SAVE                  = 'save';
    const LATE_AUTHORIZED       = 'late_authorized';

    const CURRENCY_LENGTH       = 3;

    const MIN_PAYMENT_AMOUNT    = 100;

    protected static $sign      = 'pay';

    protected $entity           = 'payment';

    protected $table            = \RZP\Constants\Table::PAYMENT;

    protected $metadata         = array();

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::INTERNATIONAL,
        self::METHOD,
        self::EMI_PLAN_ID,
        self::BANK,
        self::WALLET,
        self::CURRENCY,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::CALLBACK_URL,
        self::FEE,
        self::SERVICE_TAX,
        self::SAVE);

    protected $visible = array(
        self::ID,
        self::PUBLIC_ID,
        self::METHOD,
        self::AMOUNT,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::CURRENCY,
        self::STATUS,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::DESCRIPTION,
        self::BANK,
        self::WALLET,
        self::EMI_PLAN_ID,
        self::CUSTOMER_ID,
        self::GLOBAL_CUSTOMER_ID,
        self::APP_TOKEN,
        self::TOKEN_ID,
        self::GLOBAL_TOKEN_ID,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::AUTHORIZED_AT,
        self::CAPTURED_AT,
        self::GATEWAY,
        self::CARD_ID,
        self::MERCHANT_ID,
        self::TERMINAL_ID,
        self::TRANSACTION_ID,
        self::AUTO_CAPTURED,
        self::ORDER_ID,
        self::INTERNATIONAL,
        self::SIGNED,
        self::VERIFIED,
        self::CALLBACK_URL,
        self::SAVE,
        self::FEE,
        self::SERVICE_TAX,
        self::OTP_ATTEMPTS,
        self::OTP_COUNT,
        self::LATE_AUTHORIZED,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::ORDER_ID,
        self::METHOD,
        self::AMOUNT_REFUNDED,
        self::REFUND_STATUS,
        self::CAPTURED,
        self::DESCRIPTION,
        self::CARD_ID,
        self::BANK,
        self::WALLET,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::FEE,
        self::SERVICE_TAX,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT);

    protected $publicSetters = array(
        self::ID, self::ENTITY, self::ORDER_ID, self::CARD_ID);

    protected $guarded = array(self::ID);

    protected $appends = array(self::PUBLIC_ID, self::CAPTURED);

    protected static $modifiers = array(
        self::CONTACT,
        self::BANK,
        'method_based_input',
        'convert_empty_strings_to_null');

    protected $dates = array(self::AUTHORIZED_AT, self::CAPTURED_AT);

    protected $defaults = array(
        self::STATUS            => Status::CREATED,
        self::REFUND_STATUS     => Refund\Status::NULL,
        self::NOTES             => [],
        self::AMOUNT_REFUNDED   => 0,
        self::SIGNED            => 0,
        self::VERIFIED          => null,
        self::CAPTURED_AT       => null,
        self::AUTO_CAPTURED     => 0,
        self::SAVE              => false,
        self::FEE               => null,
        self::SERVICE_TAX       => null,
        self::OTP_ATTEMPTS      => null,
        self::OTP_COUNT         => null,
        self::EMI_PLAN_ID       => null,
        self::LATE_AUTHORIZED   => null,
        self::INTERNATIONAL     => 0,
    );

    protected $amounts = array(
        self::AMOUNT,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::FEE,
        self::SERVICE_TAX
    );

// --------------------- Generators --------------------------------------------

// --------------------- Generators Ends ---------------------------------------

// --------------------- Modifiers ---------------------------------------------

    protected function modifyContact(& $input)
    {
        if (isset($input['contact']) === false)
        {
            return;
        }

        $contact = & $input['contact'];

        if (is_string($contact) === false)
        {
            return;
        }

        $contact = str_replace(' ', '', $contact);
        $contact = str_replace('-', '', $contact);
        $contact = str_replace('(', '', $contact);
        $contact = str_replace(')', '', $contact);

        // Remove the 0 at the start
        if ((strlen($contact) > 1) and
            ($contact[0] === '0'))
        {
            $contact = substr($contact, 1);
        }

        return $contact;
    }

    protected function modifyMethodBasedInput(& $input)
    {
        if (isset($input['method']) === false)
        {
            return;
        }

        if ($input['method'] !== Method::NETBANKING)
        {
            $input['bank'] = null;
        }

        if ($input['method'] !== Method::EMI)
        {
            $input['emi_duration'] = null;
        }

        if ($input['method'] !== Method::WALLET)
        {
            $input['wallet'] = null;
        }
    }

    protected function modifyConvertEmptyStringsToNull(& $input)
    {
        $array = array(
            Entity::CUSTOMER_ID,
            Entity::TOKEN,
            Entity::APP_TOKEN);

        foreach ($array as $key)
        {
            if (empty($input[$key]))
            {
                $input[$key] = null;
            }
        }
    }

    protected function modifyBank(& $input)
    {
        if ((isset($input['method'])) and
            ($input['method'] !== Method::NETBANKING))
        {
            $input['bank'] = null;
        }
    }

    protected function modifyWallet(& $input)
    {
        if ((isset($input['method'])) and
            ($input['method'] !== Method::WALLET))
        {
            $input['wallet'] = null;
        }
    }

// --------------------- Modifiers Ends ----------------------------------------

// ----------------------- Setters ---------------------------------------------

    public function setInternational($value)
    {
        $this->setAttribute(self::INTERNATIONAL, $value);
    }

    public function setCaptureAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setAmountAuthorized()
    {
        $authAmount = $this->getAttribute(self::AMOUNT);

        $this->setAttribute(self::AMOUNT_AUTHORIZED, $authAmount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setRefundStatus($status)
    {
        $this->setAttribute(self::REFUND_STATUS, $status);
    }

    public function setAmountRefunded($amount)
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, $amount);
    }

    /**
     * This should be kept as protected so the gateway is only
     * set via associateTerminal function
     */
    protected function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setError($errorCode, $errorDesc, $internalErrorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDesc);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    public function setInternalErrorCode($internalErrorCode)
    {
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    public function setCaptureTimestamp()
    {
        $this->setAttribute(self::CAPTURED_AT, time());
    }

    public function setAuthorizeTimestamp($authTimestamp = NULL)
    {
        if (is_null($authTimestamp))
        {
            $this->setAttribute(self::AUTHORIZED_AT, time());
        }
        else
        {
            $this->setAttribute(self::AUTHORIZED_AT, $authTimestamp);
        }
    }

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setSigned($signed = true)
    {
        $this->setAttribute(self::SIGNED, $signed);
    }

    public function setAutoCapturedTrue()
    {
        $this->setAttribute(self::AUTO_CAPTURED, true);
    }

    public function setAutoCaptured($autoCaptured)
    {
        $this->setAttribute(self::AUTO_CAPTURED, $autoCaptured);
    }

    public function setVerified($verified)
    {
        $this->setAttribute(self::VERIFIED, $verified);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function setFee($fee)
    {
        $this->setAttribute(self::FEE, $fee);
    }

    public function setErrorNull()
    {
        $this->setAttribute(self::ERROR_CODE, null);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, null);
        $this->setAttribute(self::ERROR_DESCRIPTION, null);
    }

    public function setEmiPlanId($planId)
    {
        $this->setAttribute(self::EMI_PLAN_ID, $planId);
    }

    public function setOtpAttempts($attempts)
    {
        $this->setAttribute(self::OTP_ATTEMPTS, $attempts);
    }

    public function setOtpCount($count)
    {
        $this->setAttribute(self::OTP_COUNT, $count);
    }

    public function setEmailAttribute($email)
    {
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    public function setSave($save)
    {
        $this->setAttribute(self::SAVE, $save);
    }

    public function incrementOtpAttempts()
    {
        $attempts = $this->getOtpAttemptsAttribute() + 1;

        $this->setOtpAttempts($attempts);
    }

    public function incrementOtpCount()
    {
        $count = $this->getOtpCountAttribute() + 1;

        $this->setOtpCount($count);
    }

    public function setLateAuthorized($lateAuthorized)
    {
        $this->setAttribute(self::LATE_AUTHORIZED, $lateAuthorized);
    }

// ----------------------- Setters Ends-----------------------------------------

// ----------------------- Mutator ---------------------------------------------

    protected function setAmountAttribute($amount)
    {
        $this->attributes[self::AMOUNT] = (int) $amount;
    }

    protected function setContactAttribute($contact)
    {
        $number = new PhoneBook($contact, true);

        if ($number->isValidNumber() === true)
        {
            $this->attributes[self::CONTACT] = $number->format();
        }
        else
        {
            $normalizedNumber = $number->getRawInput();

            // Hack for tracing new invalid numbers
            // to get the stats
            $app = \App::getFacadeRoot();

            $app['trace']->info(
                TraceCode::PAYMENT_INVALID_CONTACT_NUMBER,
                ['number' => $contact, 'normalized_number' => $normalizedNumber]);

            $this->attributes[self::CONTACT] = $normalizedNumber;
        }
    }

// ----------------------- Mutator Ends ----------------------------------------

// ----------------------- Accessor --------------------------------------------

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    // TODO: Return a phonebook instance (like carbon) instead of string
    protected function getContactAttribute()
    {
        $contact = $this->attributes[self::CONTACT];

        $phoneBook = new PhoneBook($contact, true);

        return (string) $phoneBook;
    }

    protected function getAmountAuthorizedAttribute()
    {
        return (int) $this->attributes[self::AMOUNT_AUTHORIZED];
    }

    protected function getAmountRefundedAttribute()
    {
        return (int) $this->attributes[self::AMOUNT_REFUNDED];
    }

    protected function getAutoCapturedAttribute()
    {
        return (bool) $this->attributes[self::AUTO_CAPTURED];
    }

    protected function getSignedAttribute()
    {
        return (bool) $this->attributes[self::SIGNED];
    }

    protected function getVerifiedAttribute()
    {
        $verified = $this->attributes[self::VERIFIED];

        if ($verified !== null)
        {
            $verified = (int) $verified;
        }

        return $verified;
    }

    protected function getCapturedAttribute()
    {
        return ($this->attributes[self::CAPTURED_AT] !== null);
    }

    protected function getFeeAttribute()
    {
        return (int) $this->attributes[self::FEE];
    }

    protected function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

    protected function getEmiPlanIdAttribute()
    {
        return $this->attributes[self::EMI_PLAN_ID];
    }

    protected function getSaveAttribute()
    {
        return (bool) $this->attributes[self::SAVE];
    }

    protected function getOtpAttemptsAttribute()
    {
        $attempts = $this->attributes[self::OTP_ATTEMPTS];

        if ($attempts !== null)
        {
            $attempts = (int) $attempts;
        }

        return $attempts;
    }

    protected function getOtpCountAttribute()
    {
        $count = $this->attributes[self::OTP_COUNT];

        if ($count !== null)
        {
            $count = (int) $count;
        }

        return $count;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

// ----------------------- Accessor Ends ---------------------------------------

    public function isCreated()
    {
        return ($this->getAttribute(self::STATUS) == Status::CREATED);
    }

    public function isAuthorized()
    {
        return ($this->getAttribute(self::STATUS) === Status::AUTHORIZED);
    }

    public function hasBeenAuthorized()
    {
        return ($this->isAttributeNull(self::AUTHORIZED_AT));
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNull(self::TRANSACTION_ID));
    }

    public function isCaptured()
    {
        return ($this->getAttribute(self::STATUS) === Status::CAPTURED);
    }

    public function isPartiallyOrFullyRefunded()
    {
        return ! ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::NULL);
    }

    public function isFullyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::FULL);
    }

    public function isPartiallyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::PARTIAL);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) === Status::FAILED);
    }

    public function isLateAuthorized()
    {
        return ($this->getAttribute(self::LATE_AUTHORIZED) === true);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) === $status);
    }

    public function isStatusCreatedOrFailed()
    {
        return (($this->isFailed()) or
                ($this->isCreated()));
    }

    public function hasBeenCaptured()
    {
        return ($this->getAttribute(self::CAPTURED_AT) !== null);
    }

    public function isNetbanking()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::NETBANKING);
    }

    public function isWallet()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::WALLET);
    }

    public function isEmi()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::EMI);
    }

    public function isCard()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::CARD);
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isMethod($method)
    {
        return ($this->getAttribute(self::METHOD) === $method);
    }

    public function isMethodCardOrEmi()
    {
        return (($this->isMethod(Payment\Method::CARD)) or
                ($this->isMethod(Payment\Method::EMI)));
    }

    public function isSigned()
    {
        return ((bool)$this->getAttribute(self::SIGNED) === true);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

// ----------------------- Getters ---------------------------------------------

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getAmountRefunded()
    {
        return (int) $this->getAttribute(self::AMOUNT_REFUNDED);
    }

    public function getAmountUnrefunded()
    {
        return (int) $this->getAmount() - $this->getAmountRefunded();
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getCallbackUrl()
    {
        return $this->getAttribute(self::CALLBACK_URL);
    }

    public function getCaptureTimestamp()
    {
        return $this->getAttribute(self::CAPTURED_AT);
    }

    public function getAuthorizeTimestamp()
    {
        return $this->getAttribute(self::AUTHORIZED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getBankName()
    {
        $bankId = $this->getBank();
        return Netbanking::getName($bankId);
    }

    public function getWallet()
    {
        return $this->getAttribute(self::WALLET);
    }

    public function getFormattedCard()
    {
        return $this->card->getFormatted();
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getAutoCaptured()
    {
        return $this->getAttribute(self::AUTO_CAPTURED);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function getGlobalTokenId()
    {
        return $this->getAttribute(self::GLOBAL_TOKEN_ID);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getDaysSinceAuthorized()
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $at = $this->getAuthorizeTimestamp();
        $diff = $now - $at;

        return floor($diff / (60*24*24));
    }

    public function getEmiPlanId()
    {
        return $this->getAttribute(self::EMI_PLAN_ID);
    }

    public function getSave()
    {
        return (bool) $this->getAttribute(self::SAVE);
    }

    public function isRecurring()
    {
        return false;
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    /**
     * This function returns the current payment method
     * and a detail string for that particular method
     * as a 2 length array. The array is numeric, instead
     * of associative because the detail key would be dependent
     * on the method itself otherwise (card.number, wallet.name, bank.name)
     * for eg.
     *
     * As such, we send a numeric array with the following details:
     *
     * ['card', $formattedCardNumber] (Just last 4 digits)
     * ['netbanking', $bankName] (Readable name for the bank)
     * ['wallet', $walletName] (Readable wallet name like PayTM)
     * @return array Payment Method Details
     */
    public function getMethodWithDetail()
    {
        $method = Method::formatted($this->getMethod());

        switch($this->getMethod())
        {
            case Method::CARD:
                return [$method, $this->getFormattedCard()];
                break;
            case Method::NETBANKING:
                return [$method, $this->getBankName()];
                break;
            case Method::WALLET:
                return [$method, ucfirst($this->getWallet())];
                break;
        }
    }

    public function getErrorDetails()
    {
        return [
            self::ERROR_CODE => $this->getAttribute(self::ERROR_CODE),
            self::ERROR_DESCRIPTION => $this->getAttribute(self::ERROR_DESCRIPTION),
        ];
    }

    /**
     * This is a heuristic method that tries to find
     * an order id the notes section
     * As of now, order_id is the first field inside notes
     * that ends with `_order_id`
     * We will shift to a standard field called `merchant_order_id`
     * as our ecommerce plugins are migrated
     * @return String order_id for the paymetn
     */
    public function getOrderId()
    {
        $notes = $this->getNotes();

        // Shortcut for direct order_id being set
        if (isset($notes['order_id']))
        {
            return $notes['order_id'];
        }

        foreach ($notes as $key => $value)
        {
            $orderIdSuffix = '_order_id';
            $ix = -1 * strlen($orderIdSuffix); // index from back
            if (substr($key, $ix) === $orderIdSuffix)
            {
                return $value;
            }
        }
        return false;
    }

    public function getApiOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function getGlobalOrLocalTokenEntity()
    {
        $token = null;

        if ($this->getTokenId() !== null)
        {
            $token = $this->token;
        }
        else if ($this->getGlobalTokenId() !== null)
        {
            $token = $this->globalToken;
        }

        return $token;
    }

    public function setPublicOrderIdAttribute(Array & $array)
    {
        if (isset($array[self::ORDER_ID]))
        {
            $array[self::ORDER_ID] =
                Order\Entity::getIdPrefix() . $this->getAttribute(self::ORDER_ID);
        }
    }

    public function setPublicCardIdAttribute(Array & $array)
    {
        if (isset($array[self::CARD_ID]))
        {
            $array[self::CARD_ID] =
                Card\Entity::getIdPrefix() . $this->getAttribute(self::CARD_ID);
        }
    }

    public function associateTerminal($terminal)
    {
        if ($terminal === null)
        {
            throw new Exception\RuntimeException(
                'Terminal should not be null',
                ['payment' => $this->toArrayAdmin()]);
        }

        $this->terminal()->associate($terminal);

        $this->setGateway($terminal->getGateway());
    }


// ----------------------- Getters Ends-----------------------------------------

    public function toArrayWithCard()
    {
        $data = $this->getAttributes();

        $card = $this->card()->first();

        if ($card === null)
        {
            throw new Exception\LogicException(
                'Associated card not found for the current payment entity');
        }

        $cardData = $card->getAttributes();

        $data['card'] = $cardData;

        return $data;
    }

    public function toArrayDashboard()
    {
        $data = $this->toArray();

        $data[self::ID] = $this->getPublicId();

        if ($this->isMethodCardOrEmi())
        {
            $card = $this->card()->firstOrFail();

            $network = $card->getNetwork();

            $data['network'] = $network;
        }

        return $data;
    }

    public function toArrayReport()
    {
        $data = parent::toArrayReport();

        $data[self::NOTES] = $this->getNotesJson();

        if ($this->isMethodCardOrEmi())
        {
            $data['card_type'] = $this->card->getType();
            $data['card_network'] = $this->card->getNetwork();
        }

        return $data;
    }

// --------------- Relation to other entities ----------------------------------

    public function card()
    {
        return $this->belongsTo('RZP\Models\Card\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }

    public function refunds()
    {
        return $this->hasMany('RZP\Models\Payment\Refund\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function hdfc()
    {
        return $this->hasOne('hdfc', 'trackid', 'id');
    }

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function globalCustomer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity', self::GLOBAL_CUSTOMER_ID);
    }

    public function token()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::TOKEN_ID);
    }

    public function globalToken()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity', self::GLOBAL_TOKEN_ID);
    }

    public function app()
    {
        return $this->belongsTo('RZP\Models\Customer\AppToken\Entity', self::APP_TOKEN);
    }

    public function emiPlan()
    {
        return $this->belongsTo('RZP\Models\Emi\Entity');
    }

// --------------- Relation to other entity section ends -----------------------

    public function refundAmount($amount)
    {
        if (is_int($amount) === false)
        {
            throw new Exception\InvalidArgumentException(
                'amount should be an integer ' . $amount);
        }

        $amount = (int) $amount;

        $amountUnrefunded = $this->getAmountUnrefunded();

        if ($amount < $amountUnrefunded)
        {
            $this->setRefundStatus(Refund\Status::PARTIAL);
        }
        else if ($amount === $amountUnrefunded)
        {
            $this->setRefundStatus(Refund\Status::FULL);

            $this->setStatus(Payment\Status::REFUNDED);
        }
        else
        {
            throw new Exception\LogicException(
                'Refund amount should be less than or equal to amount not refunded yet');
        }

        $amountRefunded = $this->getAmountRefunded() + $amount;

        $this->setAttribute(self::AMOUNT_REFUNDED, $amountRefunded);
    }

    public function toArrayTraceRelevant()
    {
        $fields = array(
            self::ID,
            self::MERCHANT_ID,
            self::CARD_ID,
            self::STATUS,
            self::AMOUNT,
            self::AUTO_CAPTURED,
            self::ERROR_CODE);

        $relevantData = array_intersect_key($this->attributes, array_flip($fields));

        return $relevantData;
    }

// --------------------- Query scopes section begin ----------------------------

    public function scopeStatus($query, $status)
    {
        return $query->where(Payment\Entity::STATUS, '=', $status);
    }

    public function scopeCreatedAtLessThan($query, $ts)
    {
        return $query->where(Payment\Entity::CREATED_AT, '<', $ts);
    }

// --------------------- Query scopes section ends -----------------------------

    public function resetOtpAttempts()
    {
        $this->setOtpAttempts(null);
    }
}

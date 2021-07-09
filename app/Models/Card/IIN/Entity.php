<?php

namespace RZP\Models\Card\IIN;

use App;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Bank\Name;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Type;
use RZP\Constants\Environment;
use RZP\Models\Payment\Gateway;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\QueryCache\Cacheable;

class Entity extends Base\PublicEntity
{
    use Cacheable;

    const IIN           = 'iin';
    const CATEGORY      = 'category';
    const NETWORK       = 'network';
    const TYPE          = 'type';
    const SUBTYPE       = 'sub_type';
    const PRODUCT_CODE  = 'product_code';
    const COUNTRY       = 'country';
    const ISSUER        = 'issuer';
    const ISSUER_CODE   = 'issuer_code';
    const ISSUER_NAME   = 'issuer_name';
    const EMI           = 'emi';
    const OTP_READ      = 'otp_read';
    const TRIVIA        = 'trivia';
    const FLOWS         = 'flows';
    const ENABLED       = 'enabled';
    const LOCKED        = 'locked';
    const NUMBER        = 'number';

    const INTERNATIONAL = 'international';
    const MESSAGE_TYPE  = 'message_type';
    const RECURRING     = 'recurring';
    const AVAILABLE     = 'available';
    const UNKNOWN       = 'unknown';

    // Used in card_issuer_validate route
    const FlOW          = 'flow';

    // Used in bulk edit route
    const IINS          = 'iins';
    const PAYLOAD       = 'payload';

    const ID_LENGTH      = 6;
    const COUNTRY_LENGTH = 2;

    protected $entity = 'iin';

    protected $primaryKey = self::IIN;

    protected $appends = [self::INTERNATIONAL];

    protected static $modifiers = ['inputRemoveBlanks'];

    protected static $generators = [
        self::ISSUER_NAME,
        self::RECURRING,
    ];

    protected $fillable = [
        self::IIN,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::SUBTYPE,
        self::PRODUCT_CODE,
        self::COUNTRY,
        self::ISSUER,
        self::ISSUER_NAME,
        self::TRIVIA,
        self::EMI,
        self::ENABLED,
        self::FLOWS,
        self::LOCKED,
        self::MESSAGE_TYPE,
        self::RECURRING
    ];

    protected $visible = [
        self::IIN,
        self::ENTITY,
        self::CATEGORY,
        self::NETWORK,
        self::TYPE,
        self::SUBTYPE,
        self::PRODUCT_CODE,
        self::COUNTRY,
        self::ISSUER,
        self::ISSUER_NAME,
        self::EMI,
        self::OTP_READ,
        self::TRIVIA,
        self::ENABLED,
        self::FLOWS,
        self::LOCKED,
        self::MESSAGE_TYPE,
        self::RECURRING,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::IIN,
        self::ENTITY,
        self::NETWORK,
        self::CATEGORY,
        self::TYPE,
        self::PRODUCT_CODE,
        self::COUNTRY,
        self::ISSUER,
        self::ISSUER_NAME,
        self::MESSAGE_TYPE
    ];

    protected $publicSetters = [
        self::FLOWS,
    ];

    protected $defaults = [
        self::EMI            => false,
        self::FLOWS          => [
            '3ds' => '1'
        ],
        self::ENABLED        => true,
        self::LOCKED         => false,
        self::MESSAGE_TYPE   => null,
        self::SUBTYPE        => Card\SubType::CONSUMER,
        self::CATEGORY       => null,
        self::ISSUER         => null,
        self::PRODUCT_CODE     => null,
    ];

    protected $casts = [
        self::IIN         => 'string',
        self::ENABLED     => 'bool',
        self::LOCKED      => 'bool',
        self::RECURRING   => 'bool',
    ];

    protected $issuerEnabledForCardMandate = [
        IFSC::RATN, // RBL
    ];

    public function supports($flows): bool
    {
        if (is_string($flows) === true)
        {
            $flows = Flow::$flows[$flows];
        }

        return (($this->getFlows() & $flows) === $flows);
    }

    public function isEmiAvailable()
    {
        return $this->getAttribute(self::EMI);
    }

    public function isDebitPin()
    {
        return $this->supports(Flow::PIN);
    }

    public function isHeadLessOtp()
    {
        return $this->supports(Flow::HEADLESS_OTP);
    }

    public function isIvr()
    {
        return $this->supports(Flow::IVR);
    }

    public function isIframeApplicable()
    {
        return $this->supports(Flow::IFRAME);
    }

    public function isOtp()
    {
        return $this->supports(Flow::OTP);
    }

    public function isInternational()
    {
        return $this->getInternationalAttribute();
    }

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getSubType()
    {
        return $this->getAttribute(self::SUBTYPE);
    }

    public function getMessageType()
    {
        return $this->getAttribute(self::MESSAGE_TYPE);
    }

    public function isRupaySMS()
    {
        return (($this->getNetworkCode() === Card\Network::RUPAY) and
                ($this->getMessageType() === 'SMS'));
    }

    public function isAmex()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::AMEX]);
    }

    public function setType($type)
    {
        Card\Type::checkType($type);

        $this->setAttribute(self::TYPE, $type);
    }

    public function setSubType(string $subtype)
    {
        Card\SubType::checkSubType($subtype);

        $this->setAttribute(self::SUBTYPE, $subtype);
    }

    public function setCountry($countryCode)
    {
        $this->setAttribute(self::COUNTRY, $countryCode);
    }

    public function getCountry()
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getNetworkCode()
    {
        return $this->getNetworkCodeAttribute();
    }

    public function isMagicEnabled()
    {
        return $this->supports(Flow::MAGIC);
    }

    protected function getNetworkCodeAttribute()
    {
        return Card\Network::getCode($this->getNetwork());
    }

    public function getTrivia()
    {
        return $this->getAttribute(self::TRIVIA);
    }

    public function getFlows()
    {
        return $this->getAttribute(self::FLOWS);
    }

    public function isEnabled()
    {
        return $this->getAttribute(self::ENABLED);
    }

    public function isLocked()
    {
        return $this->getAttribute(self::LOCKED);
    }

    public function isRecurring()
    {
        return $this->getAttribute(self::RECURRING);
    }

    public function isCardMandateApplicable(Merchant\Entity $merchant)
    {
        $app = App::getFacadeRoot();

        if ($app['env'] === Environment::PRODUCTION)
        {
            return false;
        }

        $issuer = $this->getIssuer();

        if (($merchant->isFeatureEnabled(Feature\Constants::RECURRING_CARD_MANDATE) === true) and
            (in_array($issuer, $this->issuerEnabledForCardMandate, true) === true))
        {
            return true;
        }

        return false;
    }

    public function setTrivia($trivia)
    {
        $this->setAttribute(self::TRIVIA, $trivia);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getIssuerName()
    {
        return $this->getAttribute(self::ISSUER_NAME);
    }

    protected function getIssuerNameAttribute()
    {
        $dbName = $this->attributes[self::ISSUER_NAME];

        $issuer = $this->attributes[self::ISSUER];

        if (IFSC::exists($issuer) === true)
        {
            return Name::getName($issuer) ?? $dbName;
        }

        return $dbName;
    }

    public function setIssuer($issuer)
    {
        $this->setAttribute(self::ISSUER, $issuer);
    }

    public function getOtpRead()
    {
        return $this->getAttribute(self::OTP_READ);
    }

    public function setOtpRead($flag)
    {
        $this->setAttribute(self::OTP_READ, $flag);
    }

    public function setFlows($bitmap)
    {
        $this->setAttribute(self::FLOWS, $bitmap);
    }

    public function disableFlow($flow)
    {
        $flows = $this->getFlows();

        $bitmap = Flow::disableFlow($flows, $flow);

        $this->setFlows($bitmap);
    }

    public function enableFlow($flow)
    {
        $flows = $this->getFlows();

        $bitmap = Flow::enableFlow($flows, $flow);

        $this->setFlows($bitmap);
    }

    protected function getOtpReadAttribute()
    {
        return (bool) $this->attributes[self::OTP_READ];
    }

    protected function getInternationalAttribute()
    {
        $country = $this->getCountry();

        return ($country !== 'IN');
    }

    protected function getEmiAttribute()
    {
        return (bool) $this->attributes[self::EMI];
    }

    protected function setPublicFlowsAttribute(array & $array)
    {
        if (isset($array[self::FLOWS]) === true)
        {
            $array[self::FLOWS] = Flow::getEnabledFlows($array[self::FLOWS]);
        }
    }

    protected function setFlowsAttribute($flows)
    {
        $this->attributes[self::FLOWS] = Flow::getHexValue($flows);
    }

    protected function generateIssuerName($input)
    {
        if ((isset($input[self::ISSUER]) === true) and
            (isset($input[self::ISSUER_NAME]) === false))
        {
            $this->setAttribute(self::ISSUER_NAME, Name::getName($input[self::ISSUER]));
        }
    }

    protected function generateRecurring($input)
    {
        if (isset($input[self::RECURRING]) === false)
        {
            $issuer = isset($input[self::ISSUER]) ? $input[self::ISSUER] : null;

            $isRecurring = $this->isRecurringOnNetworkrAndTypeAndIssuer($input[self::NETWORK], $input[self::TYPE], $issuer);

            $this->setAttribute(self::RECURRING, $isRecurring);
        }
    }

    protected function isRecurringOnNetworkrAndTypeAndIssuer($network, $type, $issuer): bool
    {
        if (in_array($network, Gateway::getNetworksSupportedForCardRecurring(), true) === false)
        {
            return false;
        }

        if ($type !== Type::DEBIT)
        {
            return true;
        }

        if(($issuer !== null) and (in_array($issuer, Gateway::getIssuersSupportedForDebitCardRecurring(), true)))
        {
            return true;
        }

        return false;
    }

    public function getIinCurrency()
    {
        return ($this->getCountry() !== null) ? Currency::getCurrencyForCountry($this->getCountry()) : null;
    }
}

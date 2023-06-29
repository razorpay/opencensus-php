<?php

namespace RZP\Models\Card\IIN;

use App;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Bank\Name;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Type;
use RZP\Models\Payment\Gateway;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\QueryCache\Cacheable;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;

class Entity extends Base\PublicEntity
{
    use Cacheable;

    const IIN                = 'iin';
    const CATEGORY           = 'category';
    const NETWORK            = 'network';
    const TYPE               = 'type';
    const SUBTYPE            = 'sub_type';
    const PRODUCT_CODE       = 'product_code';
    const COUNTRY            = 'country';
    const ISSUER             = 'issuer';
    const ISSUER_CODE        = 'issuer_code';
    const ISSUER_NAME        = 'issuer_name';
    const COBRANDING_PARTNER = 'cobranding_partner';
    const EMI                = 'emi';
    const OTP_READ           = 'otp_read';
    const TRIVIA             = 'trivia';
    const FLOWS              = 'flows';
    const ENABLED            = 'enabled';
    const LOCKED             = 'locked';
    const NUMBER             = 'number';
    const MANDATE_HUBS       = 'mandate_hubs';

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

    const TOKENISED     = 'tokenised';
    const CARD_IIN = 'card_iin';

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
        self::COBRANDING_PARTNER,
        self::TRIVIA,
        self::EMI,
        self::ENABLED,
        self::FLOWS,
        self::LOCKED,
        self::MESSAGE_TYPE,
        self::RECURRING,
        self::MANDATE_HUBS,
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
        self::COBRANDING_PARTNER,
        self::EMI,
        self::OTP_READ,
        self::TRIVIA,
        self::ENABLED,
        self::FLOWS,
        self::LOCKED,
        self::MESSAGE_TYPE,
        self::RECURRING,
        self::MANDATE_HUBS,
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
        self::COBRANDING_PARTNER,
        self::MESSAGE_TYPE
    ];

    protected $publicSetters = [
        self::FLOWS,
        self::MANDATE_HUBS,
    ];

    protected $defaults = [
        self::EMI                => false,
        self::FLOWS              => [
            '3ds' => '1'
        ],
        self::ENABLED            => true,
        self::LOCKED             => false,
        self::MESSAGE_TYPE       => null,
        self::SUBTYPE            => Card\SubType::CONSUMER,
        self::CATEGORY           => null,
        self::ISSUER             => null,
        self::COBRANDING_PARTNER => null,
        self::PRODUCT_CODE       => null,
        self::MANDATE_HUBS       => [],
    ];

    protected $casts = [
        self::IIN         => 'string',
        self::ENABLED     => 'bool',
        self::LOCKED      => 'bool',
        self::RECURRING   => 'bool',
    ];

    protected $editFormatKeys = [
        self::FLOWS,
        self::MANDATE_HUBS,
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

    public function isDCCBlacklisted()
    {
        return $this->supports(Flow::DCC_BLACKLISTED);
    }

    public function isTokenised()
    {
        return $this->getAttribute(self::TOKENISED);
    }

    public function getCardIin()
    {
        return $this->getAttribute(self::CARD_IIN);
    }

    public function isAVSSupportedIIN()
    {
        return ( (Country::isAVSSupportedCountry($this->getCountry())) &&
            (Card\Network::isAVSSupportedNetwork($this->getNetworkCode())));
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

    public function getCobrandingPartner()
    {
        return $this->getAttribute(self::COBRANDING_PARTNER);
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

    public function isDiners()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::DICL]);
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

    public function getMandateHubs()
    {
        return $this->getAttribute(self::MANDATE_HUBS);
    }

    public function isCardMandateApplicable(Merchant\Entity $merchant, bool $hasSubscription = false)
    {
        // CardMandate is built to fullfill Indian rbi guidelines , so returning false for any other countries.


        if(\RZP\Constants\Country::matches($merchant->getCountry() , Country::IN) == false){
            return false;
        }
        // For Optimizer merchants, Payu gateway acts as a mandatehub, meaning it creates mandate + payment in a single
        // payment API call. As of march 2023, We will not override the below IIN checks for Optimizer. We will allow
        // recurring payments for Optimizer only if below check passes + additionally during mandate creation we do BIN
        // check with Payu.


        if ($this->getMandateHubs() > 0)
        {
            $hubs = $this->getApplicableMandateHubs($merchant, $hasSubscription);

            if (count($hubs) > 0){
                return true;
            }
        }

        $iin = $this->getIin();

        $app = App::getFacadeRoot();

        return $app->mandateHQ->isBinSupported($iin);
    }

    public function getApplicableMandateHubs(Merchant\Entity $merchant, bool $hasSubscription = false)
    {
        $hubs = MandateHub::getEnabledMandateHubs($this->getMandateHubs());

        if ((in_array(MandateHubs::BILLDESK_SIHUB, $hubs, true) === true) &&
            ($merchant->isBilldeskSIHubEnabled() === false))
        {
            $hubs = array_diff($hubs, [MandateHubs::BILLDESK_SIHUB]);
        }

        return $hubs;
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

    public function setMandateHubs($bitmap)
    {
        $this->setAttribute(self::MANDATE_HUBS, $bitmap);
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

    protected function setPublicMandateHubsAttribute(array &$array)
    {
        if (isset($array[self::MANDATE_HUBS]) === true)
        {
            $array[self::MANDATE_HUBS] = MandateHub::getEnabledMandateHubs($array[self::MANDATE_HUBS]);
        }
    }

    protected function setFlowsAttribute($flows)
    {
        $this->attributes[self::FLOWS] = Flow::getHexValue($flows);
    }

    protected function setMandateHubsAttribute($mandateHubs)
    {
        $this->attributes[self::MANDATE_HUBS] = MandateHub::getHexValue($mandateHubs);
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

    public function getEditFormattableKeys()
    {
        return $this->editFormatKeys;
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }
}

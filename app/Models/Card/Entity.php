<?php

namespace RZP\Models\Card;

use App;
use Carbon\Carbon;
use RZP\Constants\Country;
use RZP\Exception;
use RZP\Constants\Timezone;

use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\Account;
use RZP\Models\Admin\Role\TenantRoles;
use RZP\Models\Base\Traits\ExternalOwner;
use RZP\Models\Base\Traits\ExternalEntity;
use RZP\Models\Base\Traits\ArchivedEntity;

/**
 * @property Merchant\Entity $merchant
 * @property IIN\Entity      $iinRelation
 */
class Entity extends Base\PublicEntity
{
    use ExternalOwner, ExternalEntity, ArchivedEntity;

    const ID                                    = 'id';
    const MERCHANT_ID                           = 'merchant_id';
    const GLOBAL_CARD_ID                        = 'global_card_id';
    const NAME                                  = 'name';
    const EXPIRY_MONTH                          = 'expiry_month';
    const EXPIRY_YEAR                           = 'expiry_year';
    const IIN                                   = 'iin';
    const LAST4                                 = 'last4';
    const LENGTH                                = 'length';
    const NETWORK                               = 'network';
    const TYPE                                  = 'type';
    const SUBTYPE                               = 'sub_type';
    const CATEGORY                              = 'category';
    const EMI                                   = 'emi';
    const ISSUER                                = 'issuer';
    const COUNTRY                               = 'country';
    const INTERNATIONAL                         = 'international';
    const VAULT_TOKEN                           = 'vault_token';
    const VAULT                                 = 'vault';
    const TRIVIA                                = 'trivia';
    const FLOWS                                 = 'flows';
    const GLOBAL_FINGERPRINT                    = 'global_fingerprint';
    const REFERENCE1                            = 'reference1';
    const REFERENCE2                            = 'reference2';
    const REFERENCE3                            = 'reference3';
    const REFERENCE4                            = 'reference4';
    const TOKEN_IIN                             = 'token_iin';
    const TOKEN_EXPIRY_MONTH                    = 'token_expiry_month';
    const TOKEN_EXPIRY_YEAR                     = 'token_expiry_year';
    const TOKEN_LAST_4                          = 'token_last4';
    const PROVIDER_REFERENCE_ID                 = 'provider_reference_id';
    const COBRANDING_PARTNER                    = 'cobranding_partner';

    /**
     * Number and cvv are never saved in the database
     * but are referenced at various points
     * and the values are held in-memory.
     */

    const NUMBER = 'number';
    const CVV    = 'cvv';
    const IS_CVV_OPTIONAL   = 'is_cvv_optional';
    const IS_TOKENIZED_CARD = 'is_tokenized_card';

    const TOKENISED         = 'tokenised';
    const CRYPTOGRAM_VALUE  = 'cryptogram_value';
    const TOKEN_PROVIDER    = 'token_provider';

    const COUNTRY_LENGTH = 2;

    const DUMMY_EXPIRY_YEAR      = '2099';
    const MAESTRO_EXPIRY_YEAR    = '2049';

    const DUMMY_EXPIRY_MONTH     = '12';
    const DUMMY_CVV              = '123';
    const DUMMY_CVV_AMEX         = '1234';
    const DUMMY_NAME             = 'dummy card';
    const DUMMY_AMEX_CARD        = '377400111111115';
    const DUMMY_MASTERCARD_CARD  = '2221000000511237';
    const DUMMY_VISA_CARD        = '4231560000511234';
    const DUMMY_RUPAY_CARD       = '5085000000521234';
    const DUMMY_AXIS_TOKENHQ_CARD   = '4532712890380420';

    const DUMMY_IIN                   = '999999';
    const DUMMY_CARD_EXPIRY_MONTH     = '01';
    const DUMMY_CARD_NAME             =  '';

    const NETWORK_CODE = 'network_code';

    const TOKEN                  = 'token';
    const TOKEN_ID               = 'token_id';
    const INPUT_TYPE             = 'input_type';
    const TOKEN_NUMBER           = 'token_number';

    const RELATION_GLOBAL_CARD = 'globalCard';

    const TEMP_VAULT_TOKEN_PREFIX = 'pay_';

    const TEMP_VAULT_KMS_TOKEN_PREFIX = 'pay2_';

    protected static $sign = 'card';

    protected $entity = 'card';

    /**
     * If $cardMetaData is null, this means that vault_bu_namespace_card_metadata_variant is not enabled
     * for the request.
     *
     * if $cardMetaData is an empty array, the request is eligible to check if vault_bu_namespace_card_metadata_variant
     * feature is enabled or not, so as to fetch card metaData from vault service
     */

    protected $cardMetadata = [];

    protected $fillable = [
        self::ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::NETWORK,
        self::COUNTRY,
        self::EMI,
        self::TYPE,
        self::SUBTYPE,
        self::CATEGORY,
        self::ISSUER,
        self::VAULT_TOKEN,
        self::VAULT,
        self::GLOBAL_FINGERPRINT,
        self::INTERNATIONAL,
        self::IIN,
        self::TOKEN_IIN,
        self::TOKEN_EXPIRY_MONTH,
        self::TOKEN_EXPIRY_YEAR,
        self::TOKEN_LAST_4,
        self::PROVIDER_REFERENCE_ID
    ];

    protected $guarded = [self::ID];

    protected static $modifiers = [
        self::EXPIRY_YEAR,
        self::EXPIRY_MONTH,
        self::NUMBER,
        self::NAME,
        self::TOKEN_EXPIRY_YEAR,
        self::TOKEN_EXPIRY_MONTH,
    ];

    protected static $generators = [
        self::ID,
        self::IIN,
        self::TYPE,
        self::LAST4,
        self::LENGTH,
        self::TRIVIA,
    ];

    protected $hidden = [];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::GLOBAL_CARD_ID,
        self::NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::IIN,
        self::LAST4,
        self::LENGTH,
        self::NETWORK,
        self::TYPE,
        self::SUBTYPE,
        self::CATEGORY,
        self::EMI,
        self::ISSUER,
        self::COUNTRY,
        self::INTERNATIONAL,
        self::FLOWS,
        self::VAULT_TOKEN,
        self::VAULT,
        self::GLOBAL_FINGERPRINT,
        self::NETWORK_CODE,
        self::TRIVIA,
        self::TOKEN_IIN,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TOKEN_EXPIRY_MONTH,
        self::TOKEN_EXPIRY_YEAR,
        self::TOKEN_LAST_4,
        self::PROVIDER_REFERENCE_ID
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::IIN,
        self::LAST4,
        self::NETWORK,
        self::TYPE,
        self::ISSUER,
        self::INTERNATIONAL,
        self::EMI,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::FLOWS,
        self::SUBTYPE,
        self::TOKEN_IIN,
    ];

    protected $fundAccount = [
        Card\Entity::LAST4,
        Card\Entity::NETWORK,
        Card\Entity::TYPE,
        Card\Entity::SUBTYPE,
        Card\Entity::ISSUER,
        Card\Entity::IIN,
        Card\Entity::INPUT_TYPE
    ];

    protected $payoutServiceFundAccount = [
        Card\Entity::ID,
        Card\Entity::LAST4,
        Card\Entity::NETWORK,
        Card\Entity::TYPE,
        Card\Entity::SUBTYPE,
        Card\Entity::ISSUER,
        Card\Entity::INPUT_TYPE,
        Card\Entity::VAULT_TOKEN,
        Card\Entity::VAULT,
        Card\Entity::TRIVIA,
        Card\Entity::TOKEN_IIN,
        Card\Entity::TOKEN_LAST_4,
    ];

    protected $appends = [self::NETWORK_CODE];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::IIN,
        self::EXPIRY_YEAR,
        self::EXPIRY_MONTH,
        self::LAST4
    ];

    protected $defaults = [
        self::INTERNATIONAL      => null,
        self::EMI                => false,
        self::GLOBAL_CARD_ID     => null,
        self::VAULT              => null,
        self::VAULT_TOKEN        => null,
        self::ISSUER             => null,
        self::COUNTRY            => null,
        self::TRIVIA             => null,
        self::TOKEN_IIN          => null,
        self::CATEGORY           => null,
        self::NAME               => '',
        self::LENGTH             => 0,
        self::TOKEN_EXPIRY_MONTH => null,
        self::TOKEN_EXPIRY_YEAR  => null,
        self::TOKEN_LAST_4        => null,
        self::PROVIDER_REFERENCE_ID  => null
    ];

    protected $casts = [
        self::IIN            => 'string',
        self::TOKEN_IIN      => 'string'
    ];

    public static $networkTokenCardUnsetAttributes = [
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::FLOWS,
        self::ENTITY,
        self::NAME,
    ];

    public function buildCard(array $input = [], string $operation = 'create')
    {
        $this->input = $input;

        $this->validateInput($operation,$input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function iinRelation()
    {
        return $this->belongsTo('RZP\Models\Card\IIN\Entity', 'iin', 'iin');
    }

    public function tokeniinRelation()
    {
        return $this->belongsTo('RZP\Models\Card\IIN\Entity', 'token_iin', 'iin');
    }

    public function globalCard()
    {
        return $this->belongsTo('RZP\Models\Card\Entity', self::GLOBAL_CARD_ID, self::ID);
    }

    public function hasGlobalCard(): bool
    {
        return $this->isAttributeNotNull(self::GLOBAL_CARD_ID);
    }

    protected function generateLast4($input)
    {
        if ( empty($input['number']) === false and empty($input['tokenised']) === false)
        {
            $tokenlast4 = substr($input['number'], -4);
            $this->setAttribute(self::TOKEN_LAST_4, $tokenlast4);
            $this->setAttribute(self::LAST4,'xxxx');
        }
        else if (empty($input['number']) === false)
        {
            $last4 = substr($input['number'], -4);

            $this->setAttribute(self::LAST4, $last4);
        }

        if (empty($input['last4']) === false)
        {
            $this->setAttribute(self::LAST4, $input['last4']);
        }
    }

    protected function generateIin($input)
    {
        if (empty($input['number']) === false)
        {
            $iin = substr($input['number'], 0, 6);

            if ((empty($input[self::IS_TOKENIZED_CARD]) === false) and
                ($input[self::IS_TOKENIZED_CARD] === true))
            {
                $tokenizedRange = substr($input['number'], 0, 9);
                $iin = Card\IIN\IIN::getTransactingIinforRange($tokenizedRange) ?? $iin;
            }

            $this->setAttribute(self::IIN, $iin);
        }

        if (empty($input['iin']) === false)
        {
            $this->setAttribute(self::IIN, $input['iin']);
        }
    }

    protected function generateType($input)
    {
        $this->setAttribute(self::TYPE, Card\Type::UNKNOWN);
    }

    protected function generateLength($input)
    {
        if (empty($input['number']) === false)
        {
            $length = strlen($input['number']);

            $this->setAttribute(self::LENGTH, $length);
        }

        if (empty($input['length']) === false)
        {
            $this->setAttribute(self::LENGTH, $input['length']);
        }
    }

    protected function generateTrivia($input)
    {
        if ((empty($input['tokenised']) === false) and
            (boolval($input['tokenised']) === true))
        {
            $this->setAttribute(self::TRIVIA, "1");
        }
    }

    protected function generateVaultToken($input)
    {
        if (isset($input[self::VAULT]) === true)
        {
            $tempInput['card'] = $input['number'];

            $tempInput['scheme'] = Card\Vault::RZP_VAULT_SCHEME;

            if ($input[self::VAULT] === Card\Vault::RZP_ENCRYPTION)
            {
                $tempInput['scheme'] = Card\Vault::RZP_ENCRYPTION_SCHEME;
            }

            $vaultToken = (new Card\CardVault)->getVaultToken($tempInput,$input);

            $this->setAttribute(self::VAULT_TOKEN, $vaultToken);
        }
    }

    public static function modifyMaestro(& $input)
    {
        if (empty($input['number']) === true)
        {
            return;
        }

        $iin         = substr($input['number'] ?? null, 0, 6);
        $cardNetwork = Network::detectNetwork($iin);

        if ($cardNetwork === Network::MAES)
        {
            if (empty($input[Entity::EXPIRY_YEAR]) === true)
            {
                $input[Entity::EXPIRY_YEAR] = self::MAESTRO_EXPIRY_YEAR;
            }

            if (empty($input[Entity::EXPIRY_MONTH]) === true)
            {
                $input[Entity::EXPIRY_MONTH] = self::DUMMY_EXPIRY_MONTH;
            }

            if (empty($input[Entity::CVV]) === true)
            {
                $input[Entity::CVV] = self::DUMMY_CVV;
            }
        }
    }

    public static function modifyBajajFinserv(& $input)
    {
        if (empty($input['number']) === true)
        {
            return;
        }

        $iin         = substr($input['number'] ?? null, 0, 6);

        $cardNetwork = Network::detectNetwork($iin);

        if ($cardNetwork === Network::BAJAJ)
        {
            $input[Entity::EXPIRY_YEAR]    = self::DUMMY_EXPIRY_YEAR;
            $input[Entity::EXPIRY_MONTH]   = self::DUMMY_EXPIRY_MONTH;
            $input[Entity::CVV]            = self::DUMMY_CVV;
        }
    }

    public function modifyName(& $input)
    {
        // Don't want empty strings of varying length
        // in the DB, replacing them all with null
        if ((isset($input[self::NAME]) === true) and
            (trim($input[self::NAME]) === ''))
        {
            $input[self::NAME] = '';
        }
    }

    public function modifyExpiryYear(& $input)
    {
        if ((isset($input['expiry_year'])) and
            (strlen($input['expiry_year']) === 2))
        {
            $input['expiry_year'] = '20' . $input['expiry_year'];
        }
    }

    public function modifyExpiryMonth(& $input)
    {
        if (isset($input['expiry_month']))
        {
            $input['expiry_month'] = ltrim($input['expiry_month'], '0');
        }
    }

    public function modifyTokenExpiryYear(& $input)
    {
        if ((isset($input['token_expiry_year'])) and
            (strlen($input['token_expiry_year']) === 2))
        {
            $input['token_expiry_year'] = '20' . $input['token_expiry_year'];
        }
    }

    public function modifyTokenExpiryMonth(& $input)
    {
        if (isset($input['token_expiry_month']))
        {
            $input['token_expiry_month'] = ltrim($input['token_expiry_month'], '0');
        }
    }

    public static function modifyNumber(& $input)
    {
        if (isset($input['number']))
        {
            $number = $input['number'];

            if (is_string($number) === false)
            {
                return $number;
            }

            $number = str_replace(' ', '', $number);
            $number = str_replace('-', '', $number);

            $input['number'] = $number;
        }
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getNetworkCode()
    {
        return $this->getNetworkCodeAttribute();
    }

    public function getNetworkColorCode()
    {
        return Card\Network::getColorCode($this->getNetworkCode());
    }

    protected function getNetworkCodeAttribute()
    {
        return Card\Network::getCode($this->getNetwork());
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getTokenIin()
    {
        return $this->getAttribute(self::TOKEN_IIN);
    }

    public function getFirstName()
    {
        $name = $this->getAttribute(self::NAME);

        $names = explode(' ', $name, 2);

        return $names[0];
    }

    public function getLastName()
    {
        $name = $this->getAttribute(self::NAME);

        $names = explode(' ', $name, 2);

        return $names[1] ?? '';
    }

    public function getType()
    {
        $type = $this->getAttribute(self::TYPE);

        if ($type === Type::UNKNOWN)
        {
            return Type::CREDIT;
        }

        return $type;
    }

    public function getSubType()
    {
        return $this->getAttribute(self::SUBTYPE);
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getLast4()
    {
        $last4= $this->getAttribute(self::LAST4);

        if ($last4 === 'xxxx')
            return null;

        return $last4;
    }

    public function getLength()
    {
        return $this->getAttribute(self::LENGTH);
    }

    public function getReference4()
    {
        return $this->getAttribute(self::REFERENCE4);
    }

    public function getMaskedCardNumber()
    {
        // $this->getIin() returns the first 6 digits
        // $this->getLast4() returns the last 4 digits
        return $this->getIin() . 'XXXXXX' . $this->getLast4();
    }

    /**
     * This method is used to identify if two cards belonging to the same
     * customer are the same or not.
     * We cannot use vaultToken or globalFingerprint over here as the two
     * values are different for tokenised & non-tokenised versions of same
     * card.
     *
     * @return string
     */
    public function getCardDetailsAsKey(): string
    {
        return implode('_', [
            $this->getIin(),
            $this->getLast4(),
            $this->getExpiryMonth(),
            $this->getExpiryYear(),
        ]);
    }

    /**
     * This method is used to group the card on flash checkout manage page.
     *
     * This method will be removed once PR with cardProviderRefId(used for grouping) is merged
     *
     * @return string
     */
    public function getCardDetailsAsKeyForGrouping(): string
    {
        return implode('_', [
            $this->getLast4(),
            $this->getIssuer(),
            $this->getNetworkCode(),
            $this->getType()
        ]);
    }

    public function getVaultToken()
    {
        return $this->getAttribute(self::VAULT_TOKEN);
    }
    public function getProviderReferenceId()
    {
        return $this->getAttribute(self::PROVIDER_REFERENCE_ID);
    }

    public function getVault()
    {
        return $this->getAttribute(self::VAULT);
    }

    public function getExpiryMonth()
    {
        return $this->getAttribute(self::EXPIRY_MONTH);
    }

    public function getExpiryYear()
    {
        return $this->getAttribute(self::EXPIRY_YEAR);
    }

    public function getTokenExpiryMonth()
    {
        return $this->getAttribute(self::TOKEN_EXPIRY_MONTH);
    }

    public function getTokenExpiryYear()
    {
        return $this->getAttribute(self::TOKEN_EXPIRY_YEAR);
    }

    public function getExpiryTimestamp()
    {
        $year = $this->getExpiryYear();

        $month = $this->getExpiryMonth();

        if($month === 0) {
            return null;
        }

        return Carbon::createFromDate($year, $month, 1, Timezone::IST)
                     ->endOfMonth()
                     ->getTimestamp();
    }

    public function getTokenExpiryTimestamp()
    {
        $year = $this->getTokenExpiryYear();

        $month = $this->getTokenExpiryMonth();

        if($month === 0) {
            return null;
        }

        // If token expiry year or token expiry month is null then fallback to card expiry year and card expiry month.
        //Only used in cases when token expiry year and token expiry month is not yet fetch from network.
        if($year === null || $month === null) {

            $app  = \App::getFacadeRoot();

            $app['trace']->info(TraceCode::DEFAULTING_TOKEN_EXPIRY_IF_NULL, [
                'message'  => 'Defaulting token expired at to card expiry details, as token expiry year/month are null'
            ]);

            $year = $this->getExpiryYear();

            $month = $this->getExpiryMonth();
        }

        return Carbon::createFromDate($year, $month, 1, Timezone::IST)
            ->endOfMonth()
            ->getTimestamp();
    }

    public function getGlobalFingerPrint()
    {
        return $this->getAttribute(self::GLOBAL_FINGERPRINT);
    }

    public function getUniqueProviderId()
    {
        return (new Card\CardVault)->getParValueForCard($this);
    }

    public function getTypeElseDefault()
    {
        // Fee based on the method type
        $cardType = $this->getType();

        if ($cardType === Card\Type::UNKNOWN)
        {
            $cardType = Card\Type::CREDIT;
        }

        return $cardType;
    }

    public function getTokenLast4()
    {
        return $this->getAttribute(self::TOKEN_LAST_4);
    }

    public function getMetadata()
    {
        return [
            'iin'          => $this->getIin(),
            'name'         => $this->getName(),
            'expiry_year'  => $this->getExpiryYear(),
            'expiry_month' => $this->getExpiryMonth()
        ];
    }

    public function setCountry($country)
    {
        $this->setAttribute(self::COUNTRY, $country);

        if ($country === 'IN')
        {
            $this->setAttribute(self::INTERNATIONAL, 0);
        }
    }

    public function setNetwork($network)
    {
        $this->setAttribute(self::NETWORK, $network);
    }

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setGlobalFingerprint($globalFingerPrint)
    {
        $this->setAttribute(self::GLOBAL_FINGERPRINT, $globalFingerPrint);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setIssuer($issuer)
    {
        $this->setAttribute(self::ISSUER, $issuer);
    }

    public function setCategory($category)
    {
        $this->setAttribute(self::CATEGORY, $category);
    }

    public function setTokenIin($tokenIin)
    {
        $this->setAttribute(self::TOKEN_IIN, $tokenIin);
    }

    public function setSubType($subtype)
    {
        $this->setAttribute(self::SUBTYPE, $subtype);
    }

    public function setInternational($flag)
    {
        $this->setAttribute(self::INTERNATIONAL, $flag);
    }

    public function setEmi($flag)
    {
        $this->setAttribute(self::EMI, $flag);
    }

    public function setVaultToken($vaultToken)
    {
        $this->setAttribute(self::VAULT_TOKEN, $vaultToken);
    }

    public function setLength($length)
    {
        $this->setAttribute(self::LENGTH, $length);
    }

    public function setTokenLast4($tokenLast4)
    {
        $this->setAttribute(self::TOKEN_LAST_4, $tokenLast4);
    }

    public function setVault($vault)
    {
        $this->setAttribute(self::VAULT, $vault);
    }

    public function setReference4($reference4)
    {
        $this->setAttribute(self::REFERENCE4, $reference4);
    }

    public function setTrivia($trivia)
    {
        $this->setAttribute(self::TRIVIA, $trivia);
    }

    public function getTrivia()
    {
        return $this->getAttribute(self::TRIVIA);
    }

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getFlows()
    {
        $iin = $this->iinRelation;

        // Allowing for Admin and App Auth(Privilege)
        $app  = \App::getFacadeRoot();

        $auth = $app['basicauth'];

        // card can be linked with the shared merchant since it could have been
        // saved via global card saving, hence use basic auth merchant
        $merchant = $auth->getMerchant();

        // if tokens are fetch on a auth where merchant context is not available
        // tokens are being fetched on admin auth use card merchant
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        return $merchant->getPaymentFlows($iin);
    }

    public function getIinRelationAttribute()
    {
        if ($this->relationLoaded('iinRelation') === true)
        {
            return $this->getRelation('iinRelation');
        }

        $iin = $this->getIin();

        $app = App::getFacadeRoot();

        $iinEntity = $app['repo']->iin->find($iin);

        if (isset($iinEntity) === false)
        {
            return $iinEntity;
        }

        $this->iinRelation()->associate($iinEntity);

        return $iinEntity;
    }

    public function getCobrandingPartner()
    {
        $cobranding_partner = null;

        if($this->iinRelation)
        {
            $cobranding_partner = $this->iinRelation->getCobrandingPartner();
        }
        else if(empty($this->getTokenIin())=== false)
        {
            $tokenIin = $this->getTokenIin();

            $cardActualIin = (string) Card\IIN\IIN::getTransactingIinforRange($tokenIin);

            $iin = (new Card\Repository)->retrieveIinDetails($cardActualIin);

            if($iin !== null)
            {
                $cobranding_partner = $iin->getCobrandingPartner();
            }
        }

        return $cobranding_partner;
    }

    public function setPublicIssuerAttribute(array & $array)
    {
        // Allowing only for policy bazaar and shared merchant account
        $allowedMerchantIds = [
            '7LAuMvKMcy7s0f', '10000000000000', Merchant\Account::SHARED_ACCOUNT
        ];

        $cardMerchant = $this->getMerchantId();

        if (($this->getEmi() === false) and
            (in_array($cardMerchant, $allowedMerchantIds, true) === false))
        {
            unset($array[self::ISSUER]);
        }
    }

    public function setPublicLast4Attribute(array & $array)
    {
            $array[self::LAST4] = $this->getLast4() ;
    }

    public function setProviderReferenceId($par)
    {
        $this->setAttribute(self::PROVIDER_REFERENCE_ID ,$par);
    }

    public function setPublicIinAttribute(array & $array)
    {
        // Allowing for Admin and App Auth(Privilege)
        $app  = \App::getFacadeRoot();
        $auth = $app['basicauth'];

        if (($auth->isPrivilegeAuth() === false) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_CARD_IIN) === false))
        {
            unset($array[self::IIN]);
            return;
        }

        if(($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_CARD_IIN)) === true)
        {
            $array[self::IIN] = self::DUMMY_IIN;
        }

    }

    public function setPublicExpiryMonthAttribute(array & $array)
    {
        if(($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_CARD_EXPIRY)) === true)
        {
            $array[self::EXPIRY_MONTH] = "01";
        }

        if ($this->isPublicExpiryAllowed() === false)
        {
            unset($array[self::EXPIRY_MONTH]);
        }
    }

    public function setTokenExpiryMonth($tokenExpiryMonth)
    {
        $this->setAttribute(self::TOKEN_EXPIRY_MONTH, $tokenExpiryMonth);
    }

    public function setTokenExpiryYear($tokenExpiryYear)
    {
        $this->setAttribute(self::TOKEN_EXPIRY_YEAR, $tokenExpiryYear);
    }

    public function setPublicExpiryYearAttribute(array & $array)
    {
        if(($this->merchant->isFeatureEnabled(Feature\Constants::EXPOSE_CARD_EXPIRY)) === true)
        {
            $array[self::EXPIRY_YEAR] = self::DUMMY_EXPIRY_YEAR;
        }

        if ($this->isPublicExpiryAllowed() === false)
        {
            unset($array[self::EXPIRY_YEAR]);
        }
    }

    public function getEmi()
    {
        if (is_null($this->iinRelation) === false)
        {
            return (bool) $this->iinRelation->isEmiAvailable();
        }

        return (bool) $this->getAttribute(self::EMI);
    }

    protected function getNameAttribute()
    {
        $name = $this->getCardMetadata(self::NAME);

        $name = (empty($name) === false) ? $name : $this->getAttributeFromArray(self::NAME);

        if ($name === null or $name === '0')
        {
          return '';
        }
        else
        {
            return $name;
        }
    }

    protected function getIinAttribute()
    {
        $iin = $this->getCardMetadata(self::IIN);

        $isVaultTokenEmpty = (empty($this->getVaultToken()) === true);

        $isTempVaultToken = false;

        if ((str_contains($this->getVaultToken(), self::TEMP_VAULT_TOKEN_PREFIX)) or
            (str_contains($this->getVaultToken(), self::TEMP_VAULT_KMS_TOKEN_PREFIX)))
        {
            $isTempVaultToken = true;
        }

        $isNetworkToken = $this->isNetworkTokenisedCard();


        if (empty($iin) === true )
        {
            (new Card\Metric)->pushCardMetaDataMetrics(METRIC::CARD_METADATA_FETCH_FROM_API_DB ,self::IIN, $isVaultTokenEmpty, $isTempVaultToken, $isNetworkToken);
        }
        else
        {
            (new Card\Metric)->pushCardMetaDataMetrics(METRIC::CARD_METADATA_FETCH_FROM_VAULT ,self::IIN, $isVaultTokenEmpty, $isTempVaultToken, $isNetworkToken);
        }

        $iin = (empty($iin) === false) ? $iin : $this->getAttributeFromArray(self::IIN);

        if ($iin === null or $iin === '0')
        {
            if (empty($this->getTokenIin()) === false)
            {
                $iinNumber = Card\IIN\IIN::getTransactingIinforRange($this->getTokenIin()) ?? substr($this->getTokenIin(),0,6) ;
                $this->cardMetadata['iin'] = $iinNumber;
                return $iinNumber;
            }
            else
            {
                return '';
            }
        }
        else
        {
            return $iin;
        }
    }

    protected function getExpiryMonthAttribute()
    {
        $expiryMonth = $this->getCardMetadata(self::EXPIRY_MONTH);

        $expiryMonth = (empty($expiryMonth) === false) ? $expiryMonth : $this->getAttributeFromArray(self::EXPIRY_MONTH);

        if ($expiryMonth === null or $expiryMonth === '0')
        {
            return '';
        }
        else
        {
            return (int)$expiryMonth;
        }
    }

    protected function getExpiryYearAttribute()
    {
        $expiryYear = $this->getCardMetadata(self::EXPIRY_YEAR);

        $expiryYear = (empty($expiryYear) === false) ? $expiryYear : $this->getAttributeFromArray(self::EXPIRY_YEAR);

        if ($expiryYear === null or $expiryYear === '0')
        {
            return '';
        }
        else
        {
            return (int)$expiryYear;
        }
    }

    protected function isPublicExpiryAllowed()
    {
        $app = \App::getFacadeRoot();

        $auth = $app['basicauth'];

        // Email Subject: Re: Managing NEFT transfers with Razorpay Virtual Accounts
        // https://razorpay.slack.com/archives/C3GF5LWJK/p1525965476000128
        $allowed = (($auth->isAdminAuth() === true) or
                    ($auth->isSubscriptionsApp() === true) or
                    (($auth->isPrivilegeAuth() === false) and
                     (($auth->getMerchant() !== null) and
                      ($auth->getMerchant()->isExposeCardExpiryEnabled() === true))));

        return $allowed;
    }

    protected function getInternationalAttribute()
    {
        $intl = $this->attributes[self::INTERNATIONAL];

        if ($intl === null)
        {
            return null;
        }

        return (bool) $this->attributes[self::INTERNATIONAL];
    }

    protected function getEmiAttribute()
    {
        return (bool) $this->attributes[self::EMI];
    }

    public function isUnsupported()
    {
        $network = Card\Network::getCode($this->getNetwork());

        return (Card\Network::isUnsupportedNetwork($network));
    }

    public function isNetworkUnknown(): bool
    {
        return ($this->getNetworkCode() === Card\Network::UNKNOWN);
    }

    public function isInternational()
    {
        return (bool) $this->getAttribute(self::INTERNATIONAL);
    }

    public function getFormatted()
    {
        return 'XXXX-XXXX-XXXX-' . $this->getLast4();
    }

    public function getCountry()
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function isAmex()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::AMEX]);
    }

    public function isMaestro()
    {
        $network = $this->getNetwork();

        return (Card\NetworkName::$codes[$network] === Card\Network::MAES);
    }

    public function isMasterCard()
    {
        $network = $this->getNetwork();

        return (Card\NetworkName::$codes[$network] === Card\Network::MC);
    }

    public function isRuPay()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::RUPAY]);
    }

    public function isBajaj(): bool
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::BAJAJ]);
    }

    public function isTokenPan()
    {
        return (empty($this->getAttribute(self::TRIVIA)) === false);
    }

    public function isDiners()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::DICL]);
    }

    public function isVisa()
    {
        $network = $this->getNetwork();

        return ($network === Card\Network::$fullName[Card\Network::VISA]);
    }

    public function isLocal()
    {
        return ($this->getMerchantId() !== Account::SHARED_ACCOUNT);
    }

    public function isRzpTokenisedCard()
    {
        $vault = $this->getVault();

        return (($vault === Card\Vault::RZP_VAULT) or
                ($vault === Card\Vault::RZP_ENCRYPTION));
    }

    public function isTokenisationCompliant(Merchant\Entity $merchant): bool

    {

        if($merchant == null){

            $app  = \App::getFacadeRoot();

            $auth = $app['basicauth'];

            $merchant = $auth->getMerchant();
        }

        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $isMalaysianMerchant = ($merchant !== null) ? Country::matches($merchant->getCountry(), Country::MY) : false;

        return (
            $this->isInternational() ||
            $this->isBajaj() ||
            ($this->isLocal() && $this->isNetworkTokenisedCard()) ||
            $isMalaysianMerchant
        );
    }

    public function isNetworkTokenisedCard()
    {
        return ($this->isRzpTokenisedCard() === false and empty($this->getVault()) === false);
    }

    public function isGlobalTokenCreationSupportedOnCard(): bool
    {
        /**
         * Can create Rzp global tokens on all networks till June 30th, 2022.
         * TODO: Remove the always true check around June 25th when we near the tokenisation deadline
         * so that we create global tokens only on supported networks
         */
        return true;

        return in_array($this->getNetworkCode(), Network::NETWORKS_SUPPORTING_GLOBAL_TOKENS, true);
    }

    public function isCredit()
    {
        return ($this->getType() === Type::CREDIT);
    }

    public function isDebit()
    {
        return ($this->getType() === Type::DEBIT);
    }

    public function isPrepaid()
    {
        return ($this->getType() === Type::PREPAID);
    }

    public function isRecurringSupported(bool $isInitial = true, bool $hasSubscription = false)
    {
        $iin = $this->iinRelation;

        return $this->isRecurringSupportedOnIIN($this->merchant, $iin, $isInitial, $hasSubscription);
    }

    public function fetchIinUsingTokenIinForRecurringIfApplicable()
    {
        $cardActualIin = null;
        $cardTokenIin = $this->getTokenIin();

        if (empty($cardTokenIin) === false)
        {
            if($this->isRuPay())
            {
                $cardTokenIin = substr($cardTokenIin, 0, 8);
            }

            $cardActualIin = Card\IIN\IIN::getTransactingIinforRange($cardTokenIin);

            if (empty($cardActualIin) === true)
            {
                $app  = \App::getFacadeRoot();

                $app['trace']->info(TraceCode::BIN_MAPPING_FOR_RECURRING_TOKEN_NOT_AVAILABLE, [
                    'token_iin' => $cardTokenIin,
                ]);

                // to be ramped up 100% post 30th Sept (or tokenisation deadline)
                $variant = $app['razorx']->getTreatment($this->getMerchantId(),
                                                        Merchant\RazorxTreatment::RECURRING_TOKENISATION_NOT_USING_ACTUAL_CARD_IIN,
                                                        $app['rzp.mode'] ?? 'live');

                if (strtolower($variant) === 'on')
                {
                    return null;
                }
            }
        }

        if (empty($cardActualIin) === true)
        {
            $cardActualIin = $this->getIin();
        }

        return $cardActualIin;
    }

    public function isRecurringSupportedOnTokenIINIfApplicable(bool $isInitial = true, bool $hasSubscription = false)
    {
        if ($isInitial === true)
        {
            // getIinAttribute() will be called internally
            $cardActualIin = $this->getIin();
        }
        else
        {
            $cardActualIin = $this->fetchIinUsingTokenIinForRecurringIfApplicable();

            if (empty($cardActualIin) === true)
            {
                return false;
            }
        }

        $app  = \App::getFacadeRoot();

        $iinEntity = $app['repo']->iin->find($cardActualIin);

        return $this->isRecurringSupportedOnIIN($this->merchant, $iinEntity, $isInitial, $hasSubscription);
    }

    public function isRzpSavedCard()
    {
        return ((empty($this->getAttribute(self::VAULT)) === true) or
                ($this->getAttribute(self::VAULT) === Card\Vault::RZP_ENCRYPTION) or
                ($this->getAttribute(self::VAULT) === Card\Vault::RZP_VAULT));
    }

    public function isRecurringSupportedOnIIN(Merchant\Entity $merchant, IIN\Entity $iin = null, bool $isInitial = true, bool $hasSubscription = false)
    {
        if($iin === null)
        {
            return false;
        }

        $app  = \App::getFacadeRoot();

        if(Country::matches($merchant->getCountry() , Country::MY))
        {
            $properties = [
                'id'            => $iin->getType(),
                'experiment_id' =>$app['config']->get('app.enabled_recurring_card_types_malaysia'),
            ];
            return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
        }

        if ($iin->isRecurring() === false)
        {
            return false;
        }

        // allow international IIN
        // allow domestic card if razorX is disabled
        // for fail safety, razorX retry count is 3
        if (((IIN\IIN::isDomesticBin($iin->getCountry(), $merchant->getCountry()) and ($app['rzp.mode'] !== Mode::TEST))
                or ($iin->isAmex() === true))
            and ($isInitial === true))
        {
            $isRecurringEnabled = $iin->isCardMandateApplicable($merchant, $hasSubscription);

            if ($isRecurringEnabled === true && $iin->getNetwork() === Network::getFullName(Network::RUPAY)) {
                return $this->IsMerchantEnabledForRupaySI($merchant->getId(), $iin->getIin());
            }

            return $isRecurringEnabled;
        }

        return true;
    }

    //Will remove this experiment after ramp-up
    protected function IsMerchantEnabledForRupaySI(string $mid, string $iin): bool
    {
        $app = \App::getFacadeRoot();

        $variant = $app['razorx']->getTreatment($mid,
            Merchant\RazorxTreatment::RECURRING_THROUGH_RUPAY_CARD_MID,
            $app['rzp.mode']);

        if ($variant != 'on')
        {
            return false;
        }

        $variant = $app['razorx']->getTreatment($iin,
            Merchant\RazorxTreatment::RECURRING_THROUGH_RUPAY_CARD_IIN,
            $app['rzp.mode']);

        if (strtolower($variant) !== 'on') {
            return false;
        }

        return true;
    }

    public function isBlocked()
    {
        // if iin is missing from database, allow transaction on it
        if ($this->iinRelation === null)
        {
            return false;
        }

        if ($this->iinRelation->isEnabled() === false)
        {
            return true;
        }

        return false;
    }

    public function isMagicEnabled()
    {
        if (($this->iinRelation !== null) and
            ($this->iinRelation->isMagicEnabled() === true))
        {
            return true;
        }

        return false;
    }

    public function isHeadLessOtp()
    {
        if ($this->iinRelation !== null)
        {
            return $this->iinRelation->isHeadLessOtp();
        }
        return false;
    }

    protected function getTokenRelevantAttributes()
    {
        $attributes = [
            self::EXPIRY_MONTH          => $this->getExpiryMonth(),
            self::EXPIRY_YEAR           => $this->getExpiryYear(),
            self::EMI                   => $this->getEmi(),
            self::ISSUER                => $this->getIssuer(),
            self::FLOWS                 => $this->getFlows(),
            self::COBRANDING_PARTNER    => $this->getCobrandingPartner()
        ];

        return $attributes;
    }

    public function toArrayToken()
    {
        $attributes = $this->toArrayPublic();

        $attributes = array_merge($attributes, $this->getTokenRelevantAttributes());

        $this->setCardCountryInTokenResponse($attributes);

        unset($attributes[self::ID]);

        return $attributes;
    }

    public function toArrayPublic()
    {
        $data = parent::toArrayPublic();

        if ($this->isInternational() === true || $this->isBajaj() === true)
        {
            return $data;
        }

        $this->setDummyCardData($data);

        return $data;
    }

    public function toArrayAdmin()
    {
        $data =  parent::toArrayAdmin();

        $app  = \App::getFacadeRoot();

        $auth = $app['basicauth'];

        if (($auth->isAdminAuth() === true) && ($data[Card\Entity::COUNTRY] === 'IN') )
        {
            $this->setDummyCardData($data);
        }

        return $data ;
    }

    public function toArray()
    {
        $data = parent::toArray();

        if (empty($data[Card\Entity::VAULT_TOKEN]) === true)
        {
            $data[Card\Entity::VAULT_TOKEN] = $this->getCardVaultToken();
        }

        $data['message_type'] = $this->iinRelation ? $this->iinRelation->getMessageType() : null;

        $data[Card\Entity::NAME]            = $this->getName();
        $data[Card\Entity::IIN]             = $this->getIin();
        $data[Card\Entity::EXPIRY_MONTH]    = $this->getExpiryMonth();
        $data[Card\Entity::EXPIRY_YEAR]     = $this->getExpiryYear();

        if ($data[Card\Entity::TRIVIA] === '1')
        {
            $data['token_iin']    = empty($data['token_iin']) ? $data['iin'] : substr($data['token_iin'],0,9);
            $data['expiry_month'] = empty($data['token_expiry_month']) ? $data['expiry_month'] : intval($data['token_expiry_month']);
            $data['expiry_year']  = empty($data['token_expiry_year']) ? $data['expiry_year'] : intval($data ['token_expiry_year']);
            $data['last4']        = empty($data['token_last4']) ? $data['last4'] : $data ['token_last4'];
        }

        // Changes to send the card's last4 instead of tokenlast4 for optimizer
        if(($this->merchant->isFeatureEnabled(Feature\Constants::RAAS)) === true)
        {
            $data['last4']   = empty($data['last4']) ? $data['token_last4'] : $data ['last4'];
        }

        return $data;
    }

    public function toArrayRefund()
    {
        $data = $this->toArray();

        if (empty($data[self::IIN]) === true && empty($this->getTokenIin()) === false)
        {
            $iin = Card\IIN\IIN::getTransactingIinforRange($this->getTokenIin());

            if (empty($iin) === false)
            {
                $data[self::IIN] = $iin;
            }
        }

        $tokenized = false;

        if (empty($data[self::TRIVIA]) === false && $data[self::TRIVIA] === "1")
        {
            $tokenized = true;
        }

        $data['tokenized'] = $tokenized;

        return $data;
    }


    public function toArrayFundAccount(bool $isPSPayout = false)
    {
        $isPayoutService = (app('basicauth')->isPayoutService()) ? true : $isPSPayout;

        if ($isPayoutService === true)
        {
            $attributes = $this->toArray();

            $attributes[self::ID] = $this->getPublicId();
        }
        else
        {
            $attributes = $this->toArrayPublic();
        }

        if ($this->isTokenPan() === true)
        {
            $attributes[self::LAST4] = $this->attributes[self::TOKEN_LAST_4];

            $attributes[self::INPUT_TYPE] = Card\InputType::SERVICE_PROVIDER_TOKEN;
        }
        else
        {
            if ($this->isNetworkTokenisedCard() === true)
            {
                $attributes[self::INPUT_TYPE] = Card\InputType::RAZORPAY_TOKEN;
            }
            else
            {
                $attributes[self::INPUT_TYPE] = Card\InputType::CARD;
            }
        }

        return ($isPayoutService === true) ? array_only($attributes, $this->payoutServiceFundAccount) :
            array_only($attributes, $this->fundAccount);
    }

    /**
     * ToDo: Cards team to add logic to get the latest card details for token iin.
     * Current logic only handles for iin not for token iin.
     */
    public function overrideIINDetails()
    {
        if (is_null($this->iinRelation) ===  false) {
            $this->setNetwork($this->iinRelation->getNetwork());
            $this->setType($this->iinRelation->getType());
            $this->setIssuer($this->iinRelation->getIssuer());
        }
    }

    public static function getDummyCvv(string $network = null)
    {
        $dummyCvv = self::DUMMY_CVV;

        if ($network === Network::AMEX)
        {
            $dummyCvv = self::DUMMY_CVV_AMEX;
        }

        return $dummyCvv;
    }

    public function getDummyCardArray(string $network = null, Card\IIN\Entity $iinEntity = null)
    {
        $card = [
            Card\Entity::CVV          => self::DUMMY_CVV,
            Card\Entity::NAME         => self::DUMMY_NAME,
            Card\Entity::EXPIRY_MONTH => self::DUMMY_EXPIRY_MONTH,
            Card\Entity::EXPIRY_YEAR  => self::DUMMY_EXPIRY_YEAR,
        ];

        switch ($network)
        {
            case Card\Network::MC:
                $card[Card\Entity::NUMBER] = self::DUMMY_MASTERCARD_CARD;
                break;

            case Card\Network::VISA:
                $card[Card\Entity::NUMBER] = self::DUMMY_VISA_CARD;
                break;

            case Card\Network::RUPAY:
                $card[Card\Entity::NUMBER] = self::DUMMY_RUPAY_CARD;
                break;

            default:
                break;
        }

        if (is_null($iinEntity) === false)
        {
            $card[Card\Entity::NUMBER] = $iinEntity->getIin() . '0000000000';

            $card[Card\Entity::NETWORK] = $iinEntity->getNetwork();

            $card[Card\Entity::TYPE] = $iinEntity->getType();

            $card[Card\Entity::ISSUER] = $iinEntity->getIssuer();

            $merchantCountry = $this->merchant != null ? $this->merchant->getCountry() : 'IN';

            $card[Card\Entity::INTERNATIONAL] = IIN\IIN::isInternational($iinEntity->getCountry(), $merchantCountry);

            $card[Card\Entity::VAULT_TOKEN] = 'XXXXXXXXXXX';

            $card[Card\Entity::PROVIDER_REFERENCE_ID] = 'XXXXXXXXXXX';
        }

        return $card;
    }


    /**
     * If card is used for the 1st time on a RZP gateway then a vault token is generated in card entity.
     * If vault has been already encountered then vault token is null and a global card id is present.
     * This contains the vault token generated.
     * If no vault token is present then null is returned to mark fta as failed.
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCardVaultToken()
    {
        $token = $this->getVaultToken();

        if ($token !== null)
        {
            return $token;
        }

        if ($this->globalCard !== null)
        {
            $card = $this->globalCard;

            $token = $card->getVaultToken();

            if ($token !== null)
            {
                return $token;
            }
        }

        return null;
    }

    public function buildTokenisedTokenForMandateHub($tokenNumber=null) : array
    {
        if ($tokenNumber !== null){

            $tokenData = null;
            $tokenData['token_provider']    = strtolower($this->getNetwork());
            $tokenData['number']            = $tokenNumber;
            $tokenData['expiry_month']      = is_int($this->getTokenExpiryMonth()) ? $this->getTokenExpiryMonth() : intval($this->getTokenExpiryMonth());
            $tokenData['expiry_year']       = is_int($this->getTokenExpiryYear()) ? $this->getTokenExpiryYear() : intval($this->getTokenExpiryYear());

            $input['token'] = $tokenData;

            return $input;
        }

        $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($this->getVaultToken(), $this->merchant);

        if (!empty($cryptogram)){

            $tokenData = null;
            $tokenData['token_provider']    = strtolower($this->getNetwork());
            $tokenData['number']            = $cryptogram['token_number'];
            $tokenData['expiry_month']      = is_int($this->getTokenExpiryMonth()) ? $this->getTokenExpiryMonth() : intval($this->getTokenExpiryMonth());
            $tokenData['expiry_year']       = is_int($this->getTokenExpiryYear()) ? $this->getTokenExpiryYear() : intval($this->getTokenExpiryYear());

            $input['token'] = $tokenData;

            return $input;
        }
        return array();
    }

    // Card Country would be set as part of token object only for the preferences API.
    protected function setCardCountryInTokenResponse(& $attributes)
    {
        $app = App::getFacadeRoot();

        $routeName = $app['api.route']->getCurrentRouteName();

        if($routeName == 'merchant_checkout_preferences' || $routeName == 'otp_verify' || $routeName == 'customer_fetch_tokens_internal')
        {
            $attributes[self::COUNTRY] = $this->getCountry()!==null?$this->getCountry():null;
        }
    }

    public function getFilterForRole(string $role): array
    {
        if ($role === TenantRoles::ENTITY_BANKING)
        {
            return [
                'fund_account_type'  =>   \RZP\Models\FundAccount\Type::CARD
            ];
        }

        return [];
    }

    public function getCardMetadata($key = null)
    {
        $app = \App::getFacadeRoot();

        if (($key === null) or
            ($this->cardMetadata === null))
        {
            return null;
        }

        $routeName = $app['request.ctx']->getRoute();

        $skip = $app['api.route']->skipCardMetaCall($routeName);

        $isAllowedIfInternalApp = $this->isAllowedInternalAppForGetCardMetaData();

        if (($skip === false) and
            (isset($this->cardMetadata[$key]) === false) and
            ((str_contains($this->getVaultToken(), self::TEMP_VAULT_TOKEN_PREFIX) === true) or
             (str_contains($this->getVaultToken(), self::TEMP_VAULT_KMS_TOKEN_PREFIX) === true)) and
            ($isAllowedIfInternalApp === true))
        {
            $this->cardMetadata = (new Card\CardVault)->getCardMetaData($this, $routeName);
        }

        return $this->cardMetadata[$key] ?? null;
    }

    public function isAllowedInternalAppForGetCardMetaData()
    {
        $app = \App::getFacadeRoot();
        $auth    = $app['basicauth'];

        if ($auth->isPayoutService() === true)
        {
            return false;
        }

        return true;
    }

    public function setCardMetaData($metaDataArray)
    {
        $this->cardMetadata = $metaDataArray;
    }

    public function setDummyCardName()
    {
        $this->setAttribute(self::NAME, "");
    }

    public function setDummyCardData(& $data)
    {
        if(isset($data[self::IIN])){
            $data[self::IIN] = self::DUMMY_IIN;
        }

        if(isset($data[self::NAME])){
            $data[self::NAME] = self::DUMMY_CARD_NAME;
        }

        if(isset($data[self::EXPIRY_YEAR])){
            $data[self::EXPIRY_YEAR] = self::DUMMY_EXPIRY_YEAR;
        }

        if(isset($data[self::EXPIRY_MONTH])){
            $data[self::EXPIRY_MONTH] = self::DUMMY_CARD_EXPIRY_MONTH;
        }

    }
}

<?php

namespace RZP\Models\Customer;

use Lib\PhoneBook;
use libphonenumber\PhoneNumberFormat;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Processor\CardlessEmi;

class Validator extends Base\Validator
{
    /**
     * Regular expression for valid names:
     * - Must start with a-z/A-Z/0-9
     * - Must end with a-z/A-Z/0-9/./)
     * - Can have anything from a-z/A-Z/0-9/'/-/–/./_/(/)/space in between
     */
    const NAME_REGEX = '/(^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–]+[a-zA-Z0-9.)]$)/';

    protected static $createRules = [
        Entity::CONTACT             => 'sometimes|nullable|contact_syntax',
        Entity::NAME                => 'sometimes|string|max:50|nullable|custom',
        Entity::EMAIL               => 'sometimes|nullable|email',
        Entity::GSTIN               => 'filled|gstin',
        Entity::NOTES               => 'sometimes|notes',
        Entity::SHIPPING_ADDRESS    => 'sometimes',
        Entity::BILLING_ADDRESS     => 'sometimes',
    ];

    protected static $editRules = [
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::NAME            => 'sometimes|string|max:50|nullable|custom',
        Entity::ACTIVE          => 'sometimes|in:0,1',
        Entity::EMAIL           => 'sometimes|email',
        Entity::GSTIN           => 'nullable|gstin',
    ];

    protected static $editGlobalCustomerRules = [
        Entity::EMAIL           => 'required|email',
    ];

    protected static $globalCreateRules = [
        Entity::CONTACT         => 'required|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line',
        Entity::EMAIL           => 'sometimes|email',
        'otp'                   => 'required|string|regex:"^\d{4,8}$"',
        'device_token'          => 'sometimes|string|max:14',
        '_'                     => 'sometimes|array',
        'method'                => 'sometimes|in:cardless_emi,paylater',
        'provider'              => 'required_if:method,cardless_emi,paylater',
        'payment_id'            => 'sometimes_if:method,cardless_emi,paylater',
        'language_code'         => 'sometimes',
        'address_consent'       => 'sometimes',
        'mode'                  => 'sometimes|in:live,test',
    ];

    protected static $addressConsentRules = [
        'unique_id'       => 'required|string|max:36'
    ];
    protected static $createGlobalAddressRules = [
        Entity::CONTACT           => 'required|contact_syntax',
        Entity::EMAIL             => 'sometimes|email',
        Entity::SHIPPING_ADDRESS  => 'sometimes',
        Entity::BILLING_ADDRESS   => 'sometimes'
    ];

    protected static $editGlobalAddressRules = [
        Entity::CONTACT           => 'required|contact_syntax',
        Entity::EMAIL             => 'sometimes|email',
        Entity::SHIPPING_ADDRESS  => 'sometimes',
        Entity::BILLING_ADDRESS   => 'sometimes'
    ];

    protected static $recordAddressConsent1ccRules = [
        'device_id'               => 'required|string|max:65',
    ];

    protected static $recordAddressConsent1ccAuditsRules = [
        'contact'           => 'required|contact_syntax',
        'unique_id'               => 'required|string|max:36',
    ];

    protected static $fetchAddressConsent1ccAuditsRules = [
        Entity::CONTACT           => 'required|contact_syntax',
    ];

    protected static $fetchAddressConsent1ccRules = [
        'customer_id'           => 'required',
    ];

    protected static $contactRules = [
        Entity::CONTACT         => 'required|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line',
        'language_code'         => 'sometimes',
    ];

    protected static $fetchPaymentsForGlobalCustomerRules = [
        'skip'                    => 'sometimes|integer',
        'count'                   => 'sometimes|integer|max:100',
        'mode'                    => 'sometimes|in:test,live',
    ];

    protected static $walletAppCreateRules = [
        Entity::CONTACT         => 'required|contact_syntax',
        Entity::EMAIL           => 'sometimes|email',
        Entity::NAME            => 'sometimes|string|max:50|nullable|custom',
        'otp'                   => 'required|string|regex:"^\d{4,8}$"',
    ];

   protected static $globalCreateValidators = [
       'provider',
   ];

   protected static $createGlobalCustomer1ccRules = [
       Entity::CONTACT => 'required|contact_syntax',
       Entity::EMAIL   => 'sometimes|email',
   ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();

        $this->merchant = $app['basicauth']->getMerchant();
    }

    protected function validateEmail($input)
    {
        if (($this->merchant->isEmailOptional() !== true) and
            (empty($input[Entity::EMAIL]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The Email field is required.',
                Entity::EMAIL);
        }
    }

    protected function validateName($attribute, $value)
    {
        if (preg_match(self::NAME_REGEX, trim($value)) !== 1)
        {
            throw new Exception\BadRequestValidationFailureException('The name format is invalid.');
        }
    }

    /**
     * - Validates given contact string using contact rules
     * - Parses and returns standard format(E164) string value
     *
     * @param string $contact
     *
     * @return string
     */
    public static function validateAndParseContact(string $contact): string
    {
        $input = [Entity::CONTACT => $contact];

        $input = self::validateAndParseContactInInput($input);

        return $input[Entity::CONTACT];
    }

    public static function validateSmsHash(array $input)
    {
        if (isset($input['sms_hash']) === true)
        {
            if (strlen($input['sms_hash']) > 20)
            {
                throw new Exception\BadRequestValidationFailureException('The SMS hash length should be less than 20');
            }
        }
    }

    /**
     * - Validates contact of given input array against contact rules
     * - Returns input array which contains parsed and formatted value
     *   of contact key.
     *
     * @param array $input
     *
     * @return array
     */
    public static function validateAndParseContactInInput(array $input): array
    {
        (new static)->validateInput('contact', array_only($input, [Entity::CONTACT]));

        $lib = App::getFacadeRoot()['libphonenumber'];

        $contact = & $input[Entity::CONTACT];

        $parsed = $lib->parse($contact, 'IN');

        $contact = $lib->format($parsed, PhoneNumberFormat::E164);

        return $input;
    }

    /**
     * Wallets can only be created for customers having
     * Indian mobile numbers
     *
     * @param null $number
     *
     * @throws Exception\BadRequestException
     * @throws \libphonenumber\NumberParseException
     */
    public function validateIndianContact($number = null)
    {
        if ($number === null)
        {
            $number = $this->entity->getContact();
        }

        if (empty($number) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED);
        }

        $number = new PhoneBook($number, true);

        $country = $number->getRegionCodeForNumber();

        if ($country !== 'IN')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED);
        }
    }

    public static function validateGlobalCustomerCreateInput($input)
    {
        (new static)->validateInput('globalCreate', $input);
    }

    public static function validateWalletAppCustomerCreateInput($input)
    {
        (new static)->validateInput('wallet_app_create', $input);
    }

    public function validateProvider($input)
    {
        if ((empty($input['method']) === true) or
            (empty($input['provider']) === true))
        {
            return;
        }

        switch ($input['method'])
        {
            case Payment\Method::CARDLESS_EMI:
                if (CardlessEmi::exists($input['provider']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for cardless emi',
                        'provider',
                        $input['provider']);
                }
                break;

            case Payment\Method::PAYLATER:
                if (Payment\Processor\PayLater::exists($input['provider']) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Provider is not supported for Pay Later',
                        'provider',
                        $input['provider']);
                }
                break;

            default:
                return ;
        }
    }

    public static function validateCreateGlobalAddress($input)
    {
        (new static)->validateInput('createGlobalAddress', $input);
    }

    public static function validateEditGlobalAddress($input)
    {
        (new static)->validateInput('editGlobalAddress', $input);
    }
    public static function validateRecordAddressConsent1cc($input)
    {
        (new static)->validateInput('recordAddressConsent1cc', $input);
    }
    public static function validateRecordAddressConsent1ccAudits($input)
    {
        (new static)->validateInput('recordAddressConsent1ccAudits', $input);
    }
    public static function validateFetchAddressConsent1ccAudits($input)
    {
        (new static)->validateInput('fetchAddressConsent1ccAudits', $input);
    }
    public static function validateFetchAddressConsent1cc($input)
    {
        (new static)->validateInput('fetchAddressConsent1cc', $input);
    }
    public static function validateAddressConsent($input)
    {
        (new static)->validateInput('addressConsent', $input);
    }
    public static function validateEditGlobalCustomer($input)
    {
        (new static)->validateInput('editGlobalCustomer', $input);
    }

}

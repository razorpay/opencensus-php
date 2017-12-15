<?php

namespace RZP\Models\Customer;

use Lib\PhoneBook;
use libphonenumber\PhoneNumberFormat;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const NAME_REGEX = '/(^[a-zA-Z0-9\s][a-zA-Z0-9-&\'._()\s–]+[a-zA-Z0-9\s.)]$)/';

    protected static $createRules = array(
        Entity::CONTACT             => 'sometimes|nullable|contact_syntax',
        Entity::NAME                => 'sometimes|string|nullable|custom',
        Entity::EMAIL               => 'sometimes|nullable|email',
        Entity::NOTES               => 'sometimes|notes',
        Entity::SHIPPING_ADDRESS    => 'sometimes',
        Entity::BILLING_ADDRESS     => 'sometimes',
    );

    protected static $editRules = array(
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::NAME            => 'sometimes|string|nullable|custom',
        Entity::ACTIVE          => 'sometimes|in:0,1',
        Entity::EMAIL           => 'sometimes|email',
    );

    protected static $globalCreateRules = array(
        Entity::CONTACT         => 'required|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line',
        Entity::EMAIL           => 'required|email',
        'otp'                   => 'required|string|regex:"^\d{4,8}$"',
        'device_token'          => 'sometimes|string|max:14',
        '_'                     => 'sometimes|array'
    );

    protected static $contactRules = array(
        Entity::CONTACT         => 'required|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line'
    );

    protected static $paymentRules = array(
        'skip'                  => 'sometimes|integer'
    );

    protected static $walletAppCreateRules = [
        Entity::CONTACT         => 'required|contact_syntax',
        Entity::EMAIL           => 'sometimes|email',
        Entity::NAME            => 'sometimes|string|nullable|custom',
        'otp'                   => 'required|string|regex:"^\d{4,8}$"',
    ];

    protected function validateName($attribute, $value)
    {
        if (strlen($value) > 50)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The name may not be greater than 50 characters.');
        }
        if (preg_match(self::NAME_REGEX, trim($value)) != 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The name format is invalid.');
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

    public static function validateFetchCustomerPaymentsInput($input)
    {
        (new static)->validateInput('payment', $input);
    }

    public static function validateGlobalCustomerCreateInput($input)
    {
        (new static)->validateInput('global_create', $input);
    }

    public static function validateWalletAppCustomerCreateInput($input)
    {
        (new static)->validateInput('wallet_app_create', $input);
    }
}

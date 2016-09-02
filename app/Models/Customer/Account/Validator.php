<?php

namespace RZP\Models\Customer;

use App;
use RZP\Models\Base;
use RZP\Models\Customer\Entity;
use libphonenumber\PhoneNumberFormat;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::NAME            => 'sometimes|alpha_space_num|max:50',
        Entity::EMAIL           => 'sometimes|email',
        Entity::NOTES           => 'sometimes|notes',
    );

    protected static $editRules = array(
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::NAME            => 'sometimes|alpha_space_num|max:50',
        Entity::ACTIVE          => 'sometimes|in:0,1',
        Entity::EMAIL           => 'sometimes|email',
    );

    protected static $globalCreateRules = array(
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::EMAIL           => 'sometimes|email',
        'otp'                   => 'sometimes|string|regex:"^\d{4,8}$"',
        'device_token'          => 'sometimes|',
        '_'                     => 'sometimes'
    );

    protected static $contactRules = array(
        Entity::CONTACT         => 'required|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line'
    );

    protected static $paymentRules = array(
        'skip'                  => 'sometimes|integer'
    );

    public static function validateAndParseContact($contact)
    {
        (new static)->validateInput('contact', ['contact' => $contact]);

        $app = App::getFacadeRoot();

        $phoneNumberLib = $app['libphonenumber'];

        // Second argument is a default country code
        $phoneNumber = $phoneNumberLib->parse($contact, 'IN');

        $contact = $phoneNumberLib->format($phoneNumber, PhoneNumberFormat::E164);

        return $contact;
    }

    public static function validateFetchCustomerPaymentsInput($input)
    {
        (new static)->validateInput('payment', $input);
    }

    public static function validateGlobalCustomerCreateInput($input)
    {
        (new static)->validateInput('global_create', $input);
    }
}
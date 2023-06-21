<?php

namespace RZP\Models\Contact;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Traits\TrimSpace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Type
 *
 * @package RZP\Models\Contact
 */
final class Type
{
    use TrimSpace;

    const CUSTOMER                             = 'customer';
    const EMPLOYEE                             = 'employee';
    const VENDOR                               = 'vendor';
    const SELF                                 = 'self';
    const RZP_FEES                             = 'rzp_fees';
    const TAX_PAYMENT_INTERNAL_CONTACT         = 'rzp_tax_pay';
    const XPAYROLL_INTERNAL                    = 'rzp_xpayroll';
    const CAPITAL_COLLECTIONS_INTERNAL_CONTACT = 'rzp_capital_collections';

    // Settings module key
    const TYPES = 'types';

    public static $defaults = [
        self::CUSTOMER,
        self::EMPLOYEE,
        self::VENDOR,
        self::SELF,
    ];

    public static $internal = [
        self::RZP_FEES,
        self::TAX_PAYMENT_INTERNAL_CONTACT,
        self::CAPITAL_COLLECTIONS_INTERNAL_CONTACT,
        self::XPAYROLL_INTERNAL,
    ];

    private $settingsAccessor;

    public static $internalAppToAllowedInternalContact = [
        'vendor_payments' => [
            self::TAX_PAYMENT_INTERNAL_CONTACT,
        ],
        'capital_collections_client' => [
            self::CAPITAL_COLLECTIONS_INTERNAL_CONTACT,
        ],
        'xpayroll' => [
            self::XPAYROLL_INTERNAL,
        ]
    ];

    public static function isInDefaults(string $type): bool
    {
        return (in_array($type, self::$defaults, true) === true);
    }

    public static function isInInternal(string $type = null): bool
    {
        return (in_array($type, self::$internal, true) === true);
    }

    //to check whether a contact type is internal and not rzp_fees
    public static function isInInternalNonRZPFees(string $type = null): bool
    {
        return ($type !== self::RZP_FEES) and self::isInInternal($type);
    }

    public function setTypeForContact(Entity $contact, string $type)
    {
        $merchant = $contact->merchant;

        $trimmedType = $this->trimSpaces($type);

        // If $type is one of the defaults, set and return
        if (self::isInDefaults($trimmedType) === true)
        {
            $contact->setType($trimmedType);

            return;
        }

        //
        // If type sent is not one of the defaults defined. We hence fetch and
        // check against the custom list, if available.
        //
        $custom = $this->getCustom($merchant);

        $trimmedCustom = $this->trimSpaces($custom);

        if (in_array($trimmedType, $trimmedCustom, true) === true)
        {
            $contact->setType($trimmedType);

            return;
        }

        //
        // If not found anywhere, throw an exception.
        // We expect type to be defined before being used.
        //
        throw new BadRequestValidationFailureException(
            'Invalid type: ' . $type,
            Entity::TYPE,
            ['contact_id' => $contact->getId()]);
    }

    public function setTypeForInternalContact(Entity $contact, string $type)
    {
        $merchantId = $contact->merchant->getId();

        $trimmedType = $this->trimSpaces($type);

        if (self::isInInternal($trimmedType) === true)
        {
            $contact->setType($trimmedType);

            return;
        }

        throw new BadRequestValidationFailureException(
            'Invalid type: ' . $type,
            Entity::TYPE,
            ['contact_id' => $contact->getId()]);
    }

    public function getAll(Merchant\Entity $merchant): array
    {
        $custom = array_keys($this->getSettingsAccessor($merchant)->all()->toArray());

        $all = array_merge(self::$defaults, $custom);

        $purposes = new PublicCollection;

        foreach ($all as $purpose => $type)
        {
            $purposes->push([Entity::TYPE => $type]);
        }

        return $purposes->toArrayWithItems();
    }

    public function getCustom(Merchant\Entity $merchant): array
    {
        return array_keys($this->getSettingsAccessor($merchant)->all()->toArray());
    }

    public function addNewCustom(string $type, Merchant\Entity $merchant)
    {
        $allCustomKeys = array_keys($this->getSettingsAccessor($merchant)->all()->toArray());

        $merchantId = $merchant->getId();

        $allCustomKeysTrimmed = $this->trimSpaces($allCustomKeys);

        $trimmedType = $this->trimSpaces($type);

        $maxTypes = Validator::MAX_TYPES_ALLOWED;

        if (count($allCustomKeysTrimmed) >= $maxTypes)
        {
            throw new BadRequestValidationFailureException(
                "You have reached the maximum limit ($maxTypes) of custom contact types that can be created.",
                Entity::TYPE);
        }

        // If type is 'rzp_fees' we won't allow adding it as a custom type
        if (self::isInInternal($trimmedType) === true)
        {
            throw new BadRequestValidationFailureException(
                "Type '$type' is an internal contact type used by Razorpay and cannot be added.",
                Entity::TYPE);
        }

        if ((self::isInDefaults(strtolower($trimmedType))) or
            (array_search_ci($trimmedType, $allCustomKeysTrimmed) !== false))
        {
            throw new BadRequestValidationFailureException(
                "Type '$type' is already defined and cannot be added.",
                Entity::TYPE);
        }

        $data = [
            $trimmedType => ''
        ];

        $this->getSettingsAccessor($merchant)
             ->upsert($data)
             ->save();
    }

    public function trimType(string $type, Merchant\Entity $merchant)
    {
        $this->getSettingsAccessor($merchant)
             ->delete($type)
             ->save();

        $trimmedType = trim(str_replace('\n', '', $type));

        $data = [
            $trimmedType => ''
        ];

        $allCustomKeys = $this->getCustom($merchant);

        if (array_search($trimmedType, $allCustomKeys, false) === false)
        {
            $this->getSettingsAccessor($merchant)
                 ->upsert($data)
                 ->save();
        }
    }

    protected function getSettingsAccessor(Merchant\Entity $merchant): Settings\Accessor
    {
        if($this->settingsAccessor == null)
        {
            $this->settingsAccessor = Settings\Accessor::for($merchant, Settings\Module::CONTACT_TYPE, Mode::LIVE);
        }
        return $this->settingsAccessor;
    }

    public static function validateInternalAppAllowedCreatingPayoutsOnType(string $contactType, string $internalAppName)
    {
        $listOfValidContactTypes = self::$internalAppToAllowedInternalContact[$internalAppName] ?? null;

        if (($listOfValidContactTypes === null) or
            (in_array($contactType, $listOfValidContactTypes, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_APP_NOT_PERMITTED_TO_CREATE_PAYOUT_ON_THIS_CONTACT_TYPE,
                null,
                [
                    'contact_type'      => $contactType,
                    'internal_app_name' => $internalAppName
                ]
            );
        }
    }

    //checks in "$internalAppToAllowedInternalContact" map whether given contact type is allowed for given app name
    public static function validateInternalAppAllowedContactType(string $contactType, string $internalAppName = null): bool
    {
        $listOfValidContactTypes = self::$internalAppToAllowedInternalContact[$internalAppName] ?? null;

        if ((empty($listOfValidContactTypes) === true) or
            (in_array($contactType, $listOfValidContactTypes, true) === false))
        {
            return false;
        }
        return true;
    }
}

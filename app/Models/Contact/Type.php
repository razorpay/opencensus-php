<?php

namespace RZP\Models\Contact;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Type
 *
 * @package RZP\Models\Contact
 */
final class Type
{
    const CUSTOMER = 'customer';
    const EMPLOYEE = 'employee';
    const VENDOR   = 'vendor';
    const SELF     = 'self';
    const RZP_FEES = 'rzp_fees';

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
    ];

    public static function isInDefaults(string $type): bool
    {
        return (in_array($type, self::$defaults, true) === true);
    }

    public static function isInInternal(string $type = null): bool
    {
        return (in_array($type, self::$internal, true) === true);
    }

    public function setTypeForContact(Entity $contact, string $type)
    {
        // If $type is one of the defaults, set and return
        if (self::isInDefaults($type) === true)
        {
            $contact->setType($type);

            return;
        }

        //
        // If type sent is not one of the defaults defined. We hence fetch and
        // check against the custom list, if available.
        //
        $custom = $this->getCustom($contact->merchant);

        if (in_array($type, $custom, true) === true)
        {
            $contact->setType($type);

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
        if (self::isInInternal($type) === true)
        {
            $contact->setType($type);

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

        $maxTypes = Validator::MAX_TYPES_ALLOWED;

        if (count($allCustomKeys) >= $maxTypes)
        {
            throw new BadRequestValidationFailureException(
                "You have reached the maximum limit ($maxTypes) of custom contact types that can be created.",
                Entity::TYPE);
        }

        // If type is 'rzp_fees' we won't allow adding it as a custom type
        if (self::isInInternal($type) === true)
        {
            throw new BadRequestValidationFailureException(
                "Type '$type' is an internal contact type used by Razorpay and cannot be added.",
                Entity::TYPE);
        }


        if ((self::isInDefaults(strtolower($type))) or
            (array_search_ci($type, $allCustomKeys) !== false))
        {
            throw new BadRequestValidationFailureException(
                "Type '$type' is already defined and cannot be added.",
                Entity::TYPE);
        }

        $data = [
            $type => ''
        ];

        $this->getSettingsAccessor($merchant)
             ->upsert($data)
             ->save();
    }

    protected function getSettingsAccessor(Merchant\Entity $merchant): Settings\Accessor
    {
        return Settings\Accessor::for($merchant, Settings\Module::CONTACT_TYPE, Mode::LIVE);
    }
}

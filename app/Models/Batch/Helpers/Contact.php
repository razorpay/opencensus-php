<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Batch\Header;
use RZP\Models\Contact as ContactModel;

class Contact
{
    public static function getContactInput(array $entry): array
    {
        $input = [
            ContactModel\Entity::TYPE         => $entry[Header::CONTACT_TYPE],
            ContactModel\Entity::NAME         => $entry[Header::CONTACT_NAME_2],
            ContactModel\Entity::EMAIL        => $entry[Header::CONTACT_EMAIL_2],
            ContactModel\Entity::CONTACT      => $entry[Header::CONTACT_MOBILE_2],
            ContactModel\Entity::REFERENCE_ID => $entry[Header::CONTACT_REFERENCE_ID],
            // Notes is optional.
            ContactModel\Entity::NOTES        => $entry[Header::NOTES] ?? [],
        ];

        // Returns removing attributes with empty values.
        return array_filter($input);
    }
}

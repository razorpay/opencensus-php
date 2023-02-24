<?php

namespace RZP\Models\Contact;

class BatchHelper
{
    // Contact Headers
    const ID                  = 'id';
    const TYPE                = 'type';
    const NAME                = 'name';
    const EMAIL               = 'email';
    const MOBILE              = 'mobile';
    const REFERENCE_ID        = 'reference_id';
    const NOTES               = 'notes';
    const CONTACT             = 'contact';
    const CONTACT_GSTIN       = 'contact_gstin';
    const CONTACT_PAN         = 'contact_pan';

    public static function getContactInput(array $entry): array
    {
        $input = [
            Entity::TYPE            => $entry[self::CONTACT][self::TYPE],
            Entity::NAME            => $entry[self::CONTACT][self::NAME],
            Entity::EMAIL           => $entry[self::CONTACT][self::EMAIL],
            Entity::CONTACT         => trim($entry[self::CONTACT][self::MOBILE]),
            Entity::REFERENCE_ID    => $entry[self::CONTACT][self::REFERENCE_ID],
            // Notes is optional.
            Entity::NOTES           => $entry[self::NOTES] ?? [],
            Entity::IDEMPOTENCY_KEY => $entry[Entity::IDEMPOTENCY_KEY],
            Entity::GST_IN          => $entry[self::CONTACT][self::CONTACT_GSTIN],
            Entity::PAN             => $entry[self::CONTACT][self::CONTACT_PAN],
        ];

        $input[Entity::NOTES] = self::formatNotesInput($input[Entity::NOTES]);

        // Returns removing attributes with empty values.
        return array_filter($input);
    }

    /**
     * Formats notes array. If any key has empty string, it removes it from array.
     *
     * @param array $notes
     *
     * @return array
     */
    private static function formatNotesInput(array $notes): array
    {
        $notesArray = [];

        foreach ($notes as $key => $value)
        {
            if (empty($value) === false)
            {
                $notesArray[$key] = $value;
            }
        }
        return $notesArray;
    }
}

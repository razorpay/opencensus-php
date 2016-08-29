<?php

namespace RZP\Trace;

/**
 * Scrubs any Card information before logging
 */
class CCProcessor
{
    // This regex is copied from https://adamcaudill.com/2011/10/20/masking-credit-cards-for-pci/
    const CC_REGEX = "/(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|" .
        "6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|" .
        "[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})/";

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $data = $record['context'];

        array_walk_recursive($data, function(&$item, $key)
        {
            if (preg_match(self::CC_REGEX, $item) === 1)
            {
                $item = "CREDIT_CARD_SCRUBBED";
            }
        });

        $record['context'] = $data;

        return $record;
    }
}

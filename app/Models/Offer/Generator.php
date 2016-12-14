<?php

namespace RZP\Models\Offer;

class Generator
{
    protected static $longDescriptionPrefix = 'Get';
    protected static $delimiter = ' ';

    const SHORT_DESCRIPTION_LENGTH = 50;
    const LONG_DESCRIPTION_LENGTH  = 200;

    protected $offer;
    protected $description;

    /**
     * Parsers to be called in defined order for generating a long description
     */
    protected static $longDescriptionParsers = [
        Parsers\CashbackDetailsParser::class,
        Parsers\PaymentMethodDetailsParser::class,
        Parsers\TransactionDetailsParser::class,
        Parsers\OfferTimeValidityParser::class,
        Parsers\ProcessingTimeParser::class,
        Parsers\AdditionalDetailsParser::class
    ];

    /**
     * Parsers to be called in defined order for generating a short description
     */
    protected static $shortDescriptionParsers = [
        Parsers\CashbackDetailsParser::class,
        Parsers\PaymentMethodDetailsParser::class
    ];

    public function __construct($offer)
    {
        $this->offer = $offer;

        $this->description = [];
    }

    public function generateLongDescription()
    {
        $this->description[] = self::$longDescriptionPrefix;

        foreach (self::$longDescriptionParsers as $parser)
        {
            $this->description[] = (new $parser)::parse($this->offer);
        }

        $descriptionStr = $this->generateDescription(self::LONG_DESCRIPTION_LENGTH);

        return $descriptionStr;
    }

    public function generateShortDescription()
    {
        foreach (self::$shortDescriptionParsers as $parser)
        {
            $this->description[] = (new $parser)::parse($this->offer);
        }

        $descriptionStr = $this->generateDescription(self::SHORT_DESCRIPTION_LENGTH);

        return $descriptionStr;
    }

    private function generateDescription($maxLength)
    {
        // Removes empty string from $description array
        $this->description = array_filter($this->description);

        $descriptionStr = implode(self::$delimiter, $this->description);

        if (strlen($descriptionStr) > $maxLength)
        {
            $description = substr($descriptionStr, 0, $maxLength);
        }

        return $descriptionStr;
    }
}

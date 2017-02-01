<?php

namespace RZP\Models\Offer;

class Generator
{
    protected $longDescriptionPrefix = 'Get';
    protected $delimiter = ' ';

    const SHORT_DESCRIPTION_LENGTH = 50;
    const LONG_DESCRIPTION_LENGTH  = 200;

    protected $offer;
    protected $description;

    /**
     * Parsers to be called in defined order for generating a long description
     */
    protected $longDescriptionParsers = [
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
    protected $shortDescriptionParsers = [
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
        $this->description[] = $this->longDescriptionPrefix;

        foreach ($this->longDescriptionParsers as $parser)
        {
            $this->description[] = (new $parser($this->offer))->parse();
        }

        $descriptionStr = $this->generateDescription(self::LONG_DESCRIPTION_LENGTH);

        return $descriptionStr;
    }

    public function generateShortDescription()
    {
        foreach ($this->shortDescriptionParsers as $parser)
        {
            $this->description[] = (new $parser($this->offer))->parse();
        }

        $descriptionStr = $this->generateDescription(self::SHORT_DESCRIPTION_LENGTH);

        return $descriptionStr;
    }

    private function generateDescription($maxLength)
    {
        // Removes empty string from $description array
        $this->description = array_filter($this->description);

        $descriptionStr = implode($this->delimiter, $this->description);

        if (strlen($descriptionStr) > $maxLength)
        {
            $description = substr($descriptionStr, 0, $maxLength);
        }

        return $descriptionStr;
    }
}

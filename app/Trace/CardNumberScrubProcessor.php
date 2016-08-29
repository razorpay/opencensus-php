<?php

namespace RZP\Trace;

use App;
use RZP\Http\Route;
use Request;
use RZP\Exception;

/**
 * Scrubs any Card information before logging
 */
class CardNumberScrubProcessor extends \Monolog\Processor\WebProcessor
{
    /**
     * This regex is copied from
     * https://adamcaudill.com/2011/10/20/masking-credit-cards-for-pci/
     */
    const CARD_REGEX = "/(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|" .
        "6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|" .
        "[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})/";

    protected $trace;

    protected $debug;

    public function __construct($trace, $debug)
    {
        $this->trace = $trace;

        $this->debug = $debug;
    }

    /**
     * @param  array $record
     * @throws RZP\Exception\CardNumberTraceException
     * @return array
     */
    public function __invoke(array $record)
    {
        $data = $record['context'];

        $scrubbed = false;

        array_walk_recursive($data, function(& $item) use (& $scrubbed)
        {
            if (is_string($item))
            {
                if (preg_match(self::CARD_REGEX, $item) === 1)
                {
                    $item = 'CREDIT_CARD_SCRUBBED';

                    $scrubbed = true;
                }
            }
        });

        if ($scrubbed)
        {
            $this->trace->error(TraceCode::CARD_NUMBER_SCRUBBED);

            if ($this->debug)
            {
                throw new Exception\CardNumberTraceException;
            }
        }

        $record['context'] = $data;

        return $record;
    }
}

<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive\Transformers;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Gateway\P2p\Upi\AxisOlive\Fields;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\TurboAction;

/**
 * Class Transformer
 * Common class which will be used by all the transformers
 * @package RZP\Gateway\P2p\Upi\AxisOlive\Transformers
 */
class Transformer
{
    public $input;

    public $action;

    public function transform(): array
    {
        // implementation will be provided in the child class
    }

    public function __construct(array $input, string $action = null)
    {
        $this->input  = $input;
        $this->action = $action;
    }

    public function put(string $key, $value)
    {
        $this->input[$key] = $value;

        return $this;
    }

    public function determineCallbackType()
    {
        $type = null;
        // if complaint type is set we are assuming that it is a notification callback else request complaint callback
        // ideally bank should give the name of callback so that we simply switch on that field

        if(isset($this->input[Fields::CRN]) === true)
        {
            if(isset($this->input[Fields::TYPE]) === true)
            {
                $type = TurboAction::REQUEST_COMPLAINT_CALLBACK;
            }
            else
            {
                $type = TurboAction::NOTIFICATION_COMPLAINT_CALLBACK;
            }
        }

        return $type;

    }
}

<?php

namespace Gateway\MockAtom;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\BaseGateway;
use Gateway\Atom;
use Models\Card;

class Gateway extends Atom\Gateway
{
    protected $url = 'http://203.114.240.183/paynetz/epi/fts';

    public function capture(array $input)
    {
        ;
    }
}

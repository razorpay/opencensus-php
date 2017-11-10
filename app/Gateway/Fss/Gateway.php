<?php

namespace RZP\Gateway\Fss;

use RZP\Constants;
use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = Constants\Entity::FSS;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $contentArray = $this->getAuthRequestContentArray($input);
    }

    private function getAuthRequestContentArray($input)
    {
    }
}
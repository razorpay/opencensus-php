<?php

namespace RZP\Models\Merchant\Consent\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Consent\Core;

class ConsentsTransformer extends Base\Transformer
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

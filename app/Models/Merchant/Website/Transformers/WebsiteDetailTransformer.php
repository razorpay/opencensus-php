<?php

namespace RZP\Models\Merchant\Website\Transformers;

use RZP\Base;
use RZP\Models\Merchant\Website\Core;

class WebsiteDetailTransformer extends Base\Transformer
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
}

<?php

namespace RZP\Models\P2p\Mandate;

use Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Mandate\Core;
use RZP\Models\P2p\Mandate\Status;
use RZP\Exception\RuntimeException;

/**
 *   * @property Core $core
 */
class Processor extends Base\Processor
{

    public function fetchAll(array $input): array
    {
        throw new RuntimeException("Implementation is on the way");
    }


}

<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Exception\RuntimeException;
use RZP\Models\P2p\Base;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function add(array $input): array
    {
        throw new RuntimeException("Not implemented, processor Implementation is on the way");
    }

    public function remove(array $input): array
    {
        throw new RuntimeException("Not implemented, processor Implementation is on the way");
    }
}

<?php

namespace RZP\Models\Payment\RefundFile;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'                    => 'sometimes|integer',
        'uploaded_file_url'         => 'sometimes|string|max:100',
        'download_file_url'         => 'sometimes|string|max:100',
        'total_count'               => 'sometimes|integer'

    );

    protected static $createValidators = array(

    );
}

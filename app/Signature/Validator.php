<?php

namespace RZP\Signature;

use RZP\Base;
use RZP\Signature\PfxSignature;
class Validator extends Base\Validator
{
    protected static $pfxSignatureRules = [
        PfxSignature::PUBLIC_KEY  => 'sometimes|string',
        PfxSignature::PRIVATE_KEY => 'sometimes|string',
    ];
}

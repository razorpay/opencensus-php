<?php

namespace Gateway\UPI\ICICI\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\UPI\ICICI;

class Gateway extends ICICI\Gateway
{
    use Base\Mock\GatewayTrait;

    const PUBLIC_KEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7uluHWUYpk4zW/RGtnOC
jgJcPObkj0mNlmmic8aZSKprRleal8bc1ZNpwWCXTAs/tdNdZbbVuhj75BqmQWVx
/qxDV3XDiNLgAowGqSAqBY92RsBLJy0e5e3Ib760YRfnO/TrH4JaxBKSFaGiThnn
nBfWUJziXeW82w8Zphl/1JUzr6dB4BW/xwK4Z2uzByTUTMc8b3pgkyAgHpTlruUs
DRQKc7GjMOJLpY5t3MwPKxu+tzhD7fURjDiuO4clpz6oNTdg3UAHoDtStPo+68kM
irmmTh1h/CC0/wXn/rBbRMJ4COeYvJChudLG56FnNP2NSWXbVev6maKvl14/OmgL
moy03jHtQX3EVyjA2pgKF2rl4oiwpjwmnKUa7y9CvD/7zchWLsq8mVsZReov7frn
1IvUaSz6YgsLMccA07E9YGMVsVsrHHIrFFVy1cy6QL8NXLJTTq9P3RwoYbG/hi1M
OmS1XMkpIY6LrmnqZ0yjfyg0/joV29n07j9ch0DZxoytMV1scoCdNRNUFV3sxHjD
jvj1L74WXAmA3s/Auq3vacaH+a11LFhg/UmdoFMW1BU1uHSotVZnizcp3RHK4xMF
XVFhVGIlpsm7hWdGuuUZpikIkuDRMCr7nmBL2oOTSntiYJyHs/x+AKty3f8u6SIO
5V+vcCpNBRYepMuLETBJCQECAwEAAQ==
-----END PUBLIC KEY-----
EOT;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}

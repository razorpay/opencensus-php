<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Bob\RequestFields;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function authorize($input)
    {
        parent::authorize($input);

        $encryptor = $this->getGatewayInstance()->getEncryptor();

        $content = $encryptor->decryptData($input[RequestFields::ENCRYPTED_DATA]);
        sd($content);

        $this->validateAuthorizeInput($input);
    }
}

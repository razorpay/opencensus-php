<?php

namespace RZP\Gateway\Fss\Mock;

use phpseclib\Crypt\TripleDES;
use RZP\Gateway\Fss;
use RZP\Gateway\Fss\Fields;
use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Fss\Repository;
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $this->request($input);

        $this->validateAuthorizeInput($input);

        $requestData = $this->getDecryptedData($input[Fields::TRAN_DATA]);

        // Validating Transaction data which we sent to server after encrypting.
        $this->validateActionInput($requestData, 'transaction_data');
    }

    /**
     * @param string $str
     *
     * @return array
     */
    protected function getDecryptedData(string $str): array
    {
        $secretKey = $this->getGatewayInstance()->getSecret();

        $crypto = new Fss\TripleDESCrypto(TripleDES::MODE_ECB, $secretKey);

        $decryptedString = $crypto->decryptString($str, true);

        // By default decrypted comes with only fields instead of nested, to let simple xml understand the data.
        //we wrap around response.

        $decryptedResult = (array) simplexml_load_string($decryptedString);

        return $decryptedResult;
    }
}
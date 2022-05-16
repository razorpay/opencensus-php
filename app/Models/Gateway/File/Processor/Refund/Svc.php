<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\AESCrypto;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Svc extends Base
{
    use FileHandler;

    const GATEWAY_MERCHANT_ID = 'MID';
    const PAYMENT_ID          = 'CRN';
    const BANK_REFERENCE_ID   = 'TID';
    const REFUND_AMOUNT       = 'TransactionAmount';

    const FILE_NAME                  = 'SVC_REFUND';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::SVC_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_SVC;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const GATEWAY_CODE               = IFSC::SVCB;
    const BASE_STORAGE_DIRECTORY     = 'Svc/Refund/Netbanking/';

    protected function formatDataForFile(array $data)
    {
        $content = [];

        foreach ($data as $row)
        {
            $content[] = [
                self::GATEWAY_MERCHANT_ID => $row[Entity::TERMINAL][Terminal::GATEWAY_MERCHANT_ID],
                self::PAYMENT_ID          => $row[Entity::PAYMENT][Payment\Entity::ID],
                self::BANK_REFERENCE_ID   => $row['gateway'][Netbanking::BANK_TRANSACTION_ID],
                self::REFUND_AMOUNT       => $this->getFormattedAmount($row[Entity::REFUND][Payment\Refund\Entity::AMOUNT]),
            ];
        }

        $formattedData = $this->getTextData($content, '', '^');

        return $this->encryptedData($formattedData);
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function encryptedData(string $formattedData)
    {
        $config = $this->config['gateway.netbanking_svc'];

        $key = $config['encryption_key'];

        $iv = $config['encryption_iv'];

        $masterKey = hex2bin(md5($key)); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto

        $aes = new AESCrypto(AES::MODE_CBC, $masterKey, base64_decode($iv));

        return bin2hex($aes->encryptString($formattedData));
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }
}

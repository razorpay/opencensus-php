<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Gateway extends Base\Gateway
{
    protected $gateway = 'enach_rbl';

    const FILE_NAME_FORMAT = 'MMS-CREATE-RATN-{$loginId}-{$datestr}-ESIGN{$count}-INP';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $input['gateway'] = $this->getGatewayInput($input);

        return $this->callAuthenticationGateway($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $authResponse = $this->callAuthenticationGateway($input);

        $content = $this->createFile($input, $authResponse);

        $data = [];

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $data = $this->getRecurringData();
        }

        return $data;
    }

    protected function getRecurringData()
    {
        $recurringData = [
            Token\Entity::RECURRING_STATUS         => Token\RecurringStatus::INITIATED,
        ];

        return $recurringData;
    }

    protected function getFileStoreBlock(array $input, array $response)
    {
        $fileName = 'rbl-enach/outgoing/' . $this->getFormattedFileName($input);

        $metadata = $this->getH2HMetadata();

        $content = $response['mandate'];

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XML)
                        ->content($content)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->entity($input['token'])
                        ->merchant($input['merchant'])
                        ->type(FileStore\Type::RBL_ENACH_REGISTRATION_FILE_SFTP)
                        ->metadata($metadata)
                        ->save();
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10006',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    // @todo: Validate if encryption is required
    protected function encryptSignedBlock(array $input, array $response)
    {
        $xml = $response['mandate'];

        $encryptor = new AESCrypto(AES::MODE_CBC, $input['terminal']->getSecureSecret());

        return $encryptor->encrypt($xml);
    }

    protected function getGatewayInput(array $input)
    {
        return [
            'next_working_dt' => $this->getNextWorkingDate($input)
        ];
    }

    // @todo: Fix this using the holiday schedule
    protected function getNextWorkingDate(array $input)
    {
        $currentTs = $input['payment']['created_at'];

        return Carbon::createFromTimestamp($currentTs, Timezone::IST);
    }

    protected function getFormattedFileName(array $input)
    {
        $replacePair = [
            '{$loginId}' => $input['terminal']->getTerminalId(),
            '{$datestr}' => $this->getNextWorkingDate($input)->format('dmY'),
            '{$count}'   => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT)
        ];

        return strtr(static::FILE_NAME_FORMAT, $replacePair);
    }

    public function refund(array $input)
    {
        throw new Exception\RuntimeException(
            'Refund is not implemented');
    }

    public function verify(array $input)
    {
        throw new Exception\RuntimeException(
            'Verify is not implemented');
    }

    protected function callAuthenticationGateway(array $input)
    {
        return $this->app['gateway']->call(
            Payment\Gateway::ESIGNER_DIGIO,
            $this->action,
            $input,
            $this->mode);
    }

    protected function getRepository()
    {
        return;
    }
}

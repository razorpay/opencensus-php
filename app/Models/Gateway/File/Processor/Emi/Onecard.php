<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Mail;
use Carbon\Carbon;

use RZP\Encryption;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Mail\Emi as EmiMail;
use RZP\Constants\Environment;
use RZP\Services\Beam\Service;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Gateway\File\Type;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Card\CobrandingPartner;
use RZP\Exception\GatewayFileException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Gateway\File\Constants as GatewayFileConstants;
use RZP\Trace\TraceCode;

class Onecard extends Base
{
    const COBRANDING_PARTNER = CobrandingPartner::ONECARD;
    const FILE_TYPE          = FileStore\Type::ONECARD_EMI_FILE;
    const FILE_NAME          = 'Razorpay_';
    const BEAM_FILE_TYPE     = 'emi';
    const DATE_FORMAT        = 'd/m/y H:i';
    const EXTENSION          = FileStore\Format::CSV;

    const TEST_ENCRYPTION_KEY = 'T8DIATjuwST8DIATjuwST8DIATjuwS22';

    const TEST_ENCRYPTION_IV = '123456789012';

    const S3_PATH = 'onecard_emi/';

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $iv;

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::fetchEntities().
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        return $this->repo
                   ->payment
                   ->fetchEmiPaymentsOfCobrandingPartnerWithRelationsBetween(
                        $begin,
                        $end,
                        static::COBRANDING_PARTNER,
                        [
                           'card.globalCard',
                           'emiPlan',
                           'merchant.merchantDetail',
                           'terminal'
                        ]);
    }

    public function generateEmiFilePassword()
    {
        if ($this->app->environment(Environment::TESTING) === true)
        {
            return self::TEST_ENCRYPTION_KEY;
        }

        return openssl_random_pseudo_bytes(32);
    }

    // Don't send the encryption key over email
    protected function sendEmiPassword($data)
    {
        return;
    }

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::createFile($data).
     * @param $data
     * @throws GatewayFileException
     */
    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = self::S3_PATH . $this->getFileToWriteName();

            $metadata = $this->getH2HMetadata();

            $creator = new FileStore\Creator;

            $this->iv = openssl_random_pseudo_bytes(12);

            if ($this->app->environment(Environment::TESTING) === true)
            {
                $this->iv = self::TEST_ENCRYPTION_IV;
            }

            $encryptionParams = [
                Encryption\AesGcmEncryption::SECRET => $data['password'],
                Encryption\AesGcmEncryption::IV     => $this->iv,
            ];

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->encrypt(
                        Encryption\Type::AES_GCM_ENCRYPTION,
                        $encryptionParams
                    )
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata($metadata);

            $creator->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
            ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'      => $this->gatewayFile->getId(),
                    'message' => $e->getMessage(),
                ],
                $e
            );
        }
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        $rrns = $this->fetchRrnDetails($data);

        foreach ($data['items'] as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $emiRate = $emiPayment->emiPlan['rate'];

            $emiPercent = $emiRate/100;

            $acquirer = '';

            if (empty($emiPayment->terminal->getGatewayAcquirer()) === false)
            {
                $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
            }

            $merchantDbaName =  $merchant->getDbaName() ?: 'Razorpay Payments';

            $formattedData[] = [
                'Card BIN'                     => '',
                'Card Hash'                    => '',
                'Last_Four'                    => $emiPayment->card->getLast4(),
                'Bin_Ident'                    => $emiPayment->card->getIssuer(),
                'Issuer'                       => 'One Card',
                'RRN'                          => $rrns[$emiPayment['id']]['rrn'] ?? '',
                'Auth Code'                    => $this->getAuthCode($emiPayment),
                'Tx Amount'                    => ($emiPayment->getAmount() / 100),
                'EMI_Offer'                    => $emiTenure,
                'Merchant Name'                => $merchantDbaName,
                'Address1'                     => '',
                'Store City'                   => '',
                'Store State'                  => '',
                'Acquirer'                     => $acquirer,
                'MID'                          => $emiPayment->terminal->getGatewayMerchantId(),
                'TID'                          => $emiPayment->terminal->getGatewayTerminalId(),
                'Tx Time'                      => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Currency'                     => 'INR',
                'Customer Processing Fee'      => '',
                'Customer Processing Amt'      => '',
                'Subvention payable to Issuer' => '',
                'Subvention Amount (Rs.)'      => '',
                'Interest Amnt'                => '',
                'Interest Rate'                => $emiPercent,
                'Tx Status'                    => 'captured',
                'Product Category'             => '',
                'Product Sub-Category 1'       => '',
                'Product Sub-Category 2'       => '',
                'Model Name'                   => '',
                'EMI Amount'                   => '',
                'Discount / Cashback %'        => '',
                'Discount / Cashback Amount'   => '',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::COBRANDING_PARTNER,
                ]
            );
        }

        return $formattedData;
    }

    // @codingStandardsIgnoreLine
    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    /**
     * @param $data
     * @throws GatewayErrorException
     */
    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data =  [
            Service::BEAM_PUSH_FILES         => $fileInfo,
            Service::BEAM_PUSH_JOBNAME       => BeamConstants::ONECARD_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            Service::BEAM_PUSH_DECRYPTION    => [
                Service::BEAM_PUSH_DECRYPTION_TYPE => Service::BEAM_PUSH_DECRYPTION_TYPE_AES256,
                Service::BEAM_PUSH_DECRYPTION_MODE => Service::BEAM_PUSH_DECRYPTION_MODE_GCM,
                Service::BEAM_PUSH_DECRYPTION_KEY  => bin2hex($data['password']),
                Service::BEAM_PUSH_DECRYPTION_IV   => bin2hex($this->iv),
            ]
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'tech_alerts',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'OneCard - File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'filestore_id'  => $this->file->getId(),
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'Bank'          => 'OneCard',
                ]
            );
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        return $config[$bucketType];
    }

    protected function getFileToWriteName()
    {
        return static::FILE_NAME . Carbon::now()->setTimezone(Timezone::IST)->format('d_m_Y');
    }

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is rate/100, say a
        // $monthlyRate is a/12 i.e should be treated as .14/12
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = ($annualRate / 100) / 12;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return (round($num / $den));
    }

    protected function replaceCardNumbers($data)
    {
        $delimiter = "\r\n";

        $data = explode($delimiter, $data);

        $out = [$data[0]];

        // Replace characters from 57 till 76 which represents card numbers
        for ($i = 1; $i < sizeof($data); $i++)
        {
            $out[] = substr_replace($data[$i], '0000000000000000000', 57, 19);
        }

        return implode($delimiter, $out);
    }

    //-------------------------- Helpers ------------------------------------//

    private function numpad($num, $count)
    {
        return strtoupper(str_pad($num, $count, '0', STR_PAD_LEFT));
    }

    private function strpad($str, $length)
    {
        return strtoupper(str_pad($str, $length, ' ', STR_PAD_RIGHT));
    }
}

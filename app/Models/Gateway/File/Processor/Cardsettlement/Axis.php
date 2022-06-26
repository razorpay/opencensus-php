<?php



namespace RZP\Models\Gateway\File\Processor\Cardsettlement;

use Mail;
use Config;
use Carbon\Carbon;

use RZP\Encryption;
use RZP\Encryption\PGPEncryption;
use RZP\Models\Gateway\File\Entity;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;


use RZP\Trace\TraceCode;

class Axis extends Base
{
    const BANK_CODE         = IFSC::UTIB;
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::AXIS_CARD_SETTLEMENT_FILE;
    const FILE_TYPE_OUTPUT  = FileStore\Type::AXIS_CARD_SETTLEMENT_OUTPUT_FILE;
    const FILE_NAME         = 'RZPY_MOTO';
    const BEAM_FILE_TYPE    = 'cardsettlement';
    const PIPE_SEPARATOR    = '|';
    const S3_PATH           = 'axis_cardsettlement/';
    const SHOULD_ENCRYPT    = true;

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
        //calculating end time based on window timings. Cron is running after 5 mins of window cutoff.
        //window timings for axis are 12 am , 12 pm , 2 pm , 5 pm
        $end = (int)(Carbon::now()->timestamp / 1800) * 1800;

        if (($end % 3600) === 0)
        {
            $end = $end - 1800;
        }

        $begin = (new AdminService)->getConfigKey([
            'key' => ConfigKey::CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP
        ]);

        $begin = $this->gatewayFile->getBegin() > 946684800 ? $this->gatewayFile->getBegin() : $begin;

        $end = $this->gatewayFile->getEnd() > 946684801 ? $this->gatewayFile->getEnd() : $end;

        $this->gatewayFile->setAttribute(Entity::END, $end);

        $this->gatewayFile->setAttribute(Entity::BEGIN, $begin);

        $this->repo->saveOrFail($this->gatewayFile);

        $featureEntries = $this->repo->feature->findMerchantsHavingFeatures([Feature\Constants::AXIS_SETTLEMENT_FILE]);

        $merchantIds = [];

        if ($featureEntries->isEmpty() === false)
        {
            $merchantIds = $featureEntries->pluck(Feature\Entity::ENTITY_ID)->toArray();
        }

        $settlementsForBank = new PublicCollection();

        $paymentSettlementsForBank = $this->fetchPaymentsSettlementsForBank($begin,
                                                                            $end,
                                                                            $merchantIds);

        $refundSettlementsForBank = $this->fetchRefundsForBank($begin,
                                                               $end,
                                                               $merchantIds);

        $settlementsForBank->put('payments', $paymentSettlementsForBank);

        $settlementsForBank->put('refunds', $refundSettlementsForBank);

        return $settlementsForBank;
    }

    protected function getEncryptionParams()
    {
        $publicKey = Config::get('applications.cardsettlement.axis_encryption_key');

        $publicKey = trim(str_replace('\n', "\n", $publicKey));

        return [PGPEncryption::PUBLIC_KEY => $publicKey];
    }

    public function fetchPaymentsSettlementsForBank($begin, $end, $merchantIds)
    {
        $this->trace->info(TraceCode::CARD_SETTLEMENT_PAYMENTS_FILE_TIMESTAMP,
            [
                'begin'       => $begin,
                'end'         => $end,
                'merchantIds' => $merchantIds
            ]);

        $payments = $this->repo
                         ->payment
                         ->fetchCardPaymentsForGatewayAndMerchantBetween($begin,
                                                                         $end,
                                                                         $merchantIds);

        if ($payments->isNotEmpty() === true)
        {
            $lastTimestampOfPayments = $payments[0]['captured_at'];

            (new AdminService)->setConfigKeys(
                [ConfigKey::CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $lastTimestampOfPayments]
            );
        }

        return $payments;
    }

    public function fetchRefundsForBank($begin, $end, $merchantIds)
    {
        $this->trace->info(TraceCode::CARD_SETTLEMENT_REFUNDS_FILE_TIMESTAMP,
            [
                'begin'       => $begin,
                'end'         => $end,
                'merchantIds' => $merchantIds
            ]);

        $refunds = $this->repo
                        ->refund
                        ->fetchCardRefundsForMerchantAndGatewayBetween($begin,
                                                                       $end,
                                                                       $merchantIds);

        return $refunds;
    }

    protected function getFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('dmYH_i_s');

        return self::FILE_NAME . '_' . $date;
    }

    // Don't send the encryption key over email
    protected function sendSettlementPassword($data)
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

            $encryptionParams = $this->getEncryptionParams();

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata($metadata)
                    ->encrypt(Encryption\Type::PGP_ENCRYPTION, $encryptionParams);

            $creator->save();

            $this->file = $creator->getFileInstance();

            $creator->name($fileName . '.txt')
                    ->extension(FileStore\Format::GPG)
                    ->save();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            /* uncomment this if we need to generate output file for debugging
            * but do rememeber to scrub sensitive from file
            */

//            $fileName = $this->getFileToWriteName();
//
//            $creator = new FileStore\Creator;
//
//            $creator->extension(static::EXTENSION)
//                    ->content($fileData)
//                    ->name($fileName)
//                    ->store(FileStore\Store::S3)
//                    ->type(static::FILE_TYPE_OUTPUT)
//                    ->entity($this->gatewayFile)
//                    ->metadata($metadata)
//                    ->save();

        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException
            (
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
        $content = [];

        $totalTransactions = 0;

        $rrns = $this->fetchRrnDetails($data);

        /**
         * @var $settlementPayment Payment\Entity
         */

        if(array_key_exists('payments',$data))
        {
            foreach ($data['payments'] as $settlementPayment)
            {
                try
                {
                    $totalTransactions++;

                    $rrn = $rrns[$settlementPayment->getId()]['rrn'] ?? '';

                    list($notesGST, $notesCorpName, $notesMTR) = $this->parseNotes($settlementPayment->getNotes());

                    $cardToken = $this->getCardToken($settlementPayment->card);

                    $cardTypeIdentifier = $settlementPayment->card->isCredit() ? 'C' : 'D';

                    $gatewayTID = $settlementPayment->terminal->getGatewayTerminalId();

                    $content[] =
                        $cardToken . self::PIPE_SEPARATOR .
                        $cardTypeIdentifier . self::PIPE_SEPARATOR .
                        'P' . self::PIPE_SEPARATOR .
                        $this->getFormattedAmount($settlementPayment->getAmount()) . self::PIPE_SEPARATOR .
                        $rrn . self::PIPE_SEPARATOR .
                        $gatewayTID . self::PIPE_SEPARATOR .
                        $settlementPayment->getAmount() . self::PIPE_SEPARATOR .
                        Carbon::createFromTimestamp($settlementPayment['captured_at'])->format('d-M-y H:i:s')  . self::PIPE_SEPARATOR .
                        $this->getAuthCode($settlementPayment) . self::PIPE_SEPARATOR .
                        $this->getCardTokenBIN($cardToken) . self::PIPE_SEPARATOR .
                        '5' . self::PIPE_SEPARATOR .
                        $notesMTR . self::PIPE_SEPARATOR .
                        $notesGST . ' ' . $notesCorpName;
                }
                catch (\Throwable $ex)
                {
                    $this->trace->error(TraceCode::CARD_SETTLEMENT_PAYMENTS_FILE_ROW_ERROR,
                        [
                            'payment_id'  => $settlementPayment->getId(),
                            'merchantIds' => $settlementPayment->getMerchantId(),
                        ]);

                    $totalTransactions--;
                }
            }
        }

        if(array_key_exists('refunds',$data))
        {
            foreach ($data['refunds'] as $settlementRefunds)
            {
                try
                {
                    $totalTransactions++;

                    $rrn = $rrns[$settlementRefunds->payment->getId()]['rrn'] ?? '';

                    list($notesGST, $notesCorpName, $notesMTR) = $this->parseNotes($settlementRefunds->payment->getNotes());

                    $cardToken = $this->getCardToken($settlementRefunds->payment->card);

                    $cardTypeIdentifier = $settlementRefunds->payment->card->isCredit() ? 'C' : 'D';

                    $gatewayTID = $settlementRefunds->payment->terminal->getGatewayTerminalId();

                    $content[] =
                        $cardToken . self::PIPE_SEPARATOR .
                        $cardTypeIdentifier . self::PIPE_SEPARATOR .
                        'P' . self::PIPE_SEPARATOR .
                        $this->getFormattedAmount($settlementRefunds->getBaseAmount()) . self::PIPE_SEPARATOR .
                        $rrn . self::PIPE_SEPARATOR .
                        $gatewayTID . self::PIPE_SEPARATOR .
                        $settlementRefunds->getBaseAmount() . self::PIPE_SEPARATOR .
                        Carbon::createFromTimestamp($settlementRefunds['processed_at'])->format('d-M-y H:i:s') . self::PIPE_SEPARATOR .
                        '' . self::PIPE_SEPARATOR .
                        $this->getCardTokenBIN($cardToken) . self::PIPE_SEPARATOR .
                        '6' . self::PIPE_SEPARATOR .
                        $notesMTR . self::PIPE_SEPARATOR .
                        $notesGST . ' ' . $notesCorpName;

                }
                catch(\Throwable $ex)
                {
                    $this->trace->error(TraceCode::CARD_SETTLEMENT_REFUNDS_FILE_ROW_ERROR,
                        [
                            'payment_id'  => $settlementRefunds->getId(),
                            'merchantIds' => $settlementRefunds->getMerchantId(),

                        ]);

                    $totalTransactions--;
                }
            }
        }

        $header = [
            'AXIS MOTO ' .
            Carbon::now()->setTimezone(Timezone::IST)->format('dmY') .
            Carbon::now()->setTimezone(Timezone::IST)->format('His') .
            $this->numpad($totalTransactions, 6) .
            ' START'
        ];

        $trailer = [
            'AXIS MOTO ' .
            Carbon::now()->setTimezone(Timezone::IST)->format('dmY') .
            Carbon::now()->setTimezone(Timezone::IST)->format('His') .
            ' END'
        ];

        $textRows = array_merge($header, $content,$trailer);

        return implode("\r\n", $textRows);
    }


    protected function getFormattedAmount($amount)
    {
        return number_format($amount * 1.0 / 100, 2, '.', '');
    }

    protected function parseNotes($notes)
    {
        // We are expecting 3 values in notes
        // 1. GST
        // 2. Corporate Name
        // 3. Payment ref num

        $notesGST = $notes['GST'] ?? '';

        $notesMTR = $notes['MTR'] ?? '';

        $notesCorpName  = $notes['CorporateName'] ?? '';

        return array($notesGST, $notesCorpName, $notesMTR);
    }

    protected function getRrnNumber($data)
    {
        $CPS_PARAMS = [
            \RZP\Reconciliator\Base\Constants::RRN
        ];

        $paymentIds = array();

        foreach ($data as $payment)
        {
            array_push($paymentIds, $payment->id);
        }

        $request = [
            'fields'        => $CPS_PARAMS,
            'payment_ids'   => $paymentIds,
        ];

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        return $response;
    }

    protected function getH2HMetadata()
    {
        return [];
    }


    /**
     * @param $data
     * @throws GatewayErrorException
     */

    protected function sendSettlementFile($data)
    {
        try
        {
            $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

            $fileInfo = [$fullFileName];

            $bucketConfig = $this->getBucketConfig();

            $data = [
                Service::BEAM_PUSH_FILES          => $fileInfo,
                Service::BEAM_PUSH_JOBNAME        => BeamConstants::AXIS_CARD_SETTLEMENT_FILE_JOB_NAME,
                Service::BEAM_PUSH_BUCKET_NAME    => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION  => $bucketConfig['region'],
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'settlements',
                'filetype'  => 'axis_card_settlement_file',
                'subject'   => 'File Send failure',
                'recipient' => Constants::MAIL_ADDRESSES[Constants::DEVELOPERS]
            ];

            $this->app['beam']->beamPush($data, $timelines, $mailInfo);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::BEAM_PUSH_FAILED,
                [
                    'job_name'  => BeamConstants::AXIS_CARD_SETTLEMENT_FILE_JOB_NAME,
                    'file_name' => $fullFileName,
                ]);
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
    }

    private function numpad($num, $count)
    {
        return strtoupper(str_pad($num, $count, '0', STR_PAD_LEFT));
    }

    private function strpad($str, $length)
    {
        return strtoupper(str_pad($str, $length, ' ', STR_PAD_RIGHT));
    }
}





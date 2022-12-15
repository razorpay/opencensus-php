<?php



namespace RZP\Models\Gateway\File\Processor\Cardsettlement;

use Mail;
use Config;
use Request;
use Carbon\Carbon;


use RZP\Encryption;
use RZP\Encryption\PGPEncryption;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Gateway\File\Entity;
use RZP\Constants\Mode;
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
    const BULK_LIMIT        = 500;

    const RAZORX_EXP_NAME   = 'axis_moto_new_column';

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $iv;

    protected $paymentsLastTimestamp;

    protected $paymentsFirstTimestamp;

    protected $totalPayments;

    protected $refundsLastTimestamp;

    protected $refundsFirstTimestamp;

    protected $totalRefunds;

    protected $isExplicitTimestampPassed;

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::fetchEntities().
     */
    public function fetchEntities(): PublicCollection
    {
        $now = Carbon::now()->timestamp;

        list($begin, $end) = $this->calculateBeginEndForFile($now);

        $this->isExplicitTimestampPassed = false;

        if( ($this->gatewayFile->getBegin() !== 946684800) or
            ($this->gatewayFile->getEnd() !== 946684801))
        {
            $this->isExplicitTimestampPassed = true;
        }

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

        $this->gatewayFile->setAttribute(Entity::BEGIN,
            min($this->paymentsFirstTimestamp, $this->refundsFirstTimestamp) );

        $pids  = $paymentSettlementsForBank->pluck(Payment\Entity::ID)->toArray();
        $rids  = $refundSettlementsForBank->pluck(Payment\Refund\Entity::ID)->toArray();

        $this->trace->info(TraceCode::CARD_SETTLEMENT_FILE_DETAILS, [
            'location'  => 'After DB fetch',
            'MIDs'      => $merchantIds,
            'payments'  => $pids,
            'refunds'   => $rids,
            'begin'     => $begin,
            'end'       => $end,
            'exp time'  => $this->isExplicitTimestampPassed,
        ]);

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
        if( $this->isExplicitTimestampPassed === false)
        {
            $beginFromCache = (new AdminService)->getConfigKey([
                    'key' => ConfigKey::CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP
                ]);

            if($beginFromCache < $begin)
            {
                $begin = $beginFromCache;
            }
        }

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

        $this->paymentsLastTimestamp = $begin;

        $this->paymentsFirstTimestamp = $begin;

        if(count($payments) > 0)
        {
            $this->paymentsLastTimestamp = $payments[0]['captured_at'];

            $this->paymentsFirstTimestamp = $payments[count($payments)-1]['captured_at'];
        }

        return $payments;
    }

    public function fetchRefundsForBank($begin, $end, $merchantIds)
    {

        if($this->isExplicitTimestampPassed === false)
        {
            $beginFromCache = (new AdminService)->getConfigKey([
                    'key' => ConfigKey::CARD_REFUNDS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP
                ]);

            if($beginFromCache < $begin)
            {
                $begin = $beginFromCache;
            }
        }

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

        $this->refundsLastTimestamp = $begin;

        $this->refundsFirstTimestamp = $begin;

        if(count($refunds) > 0)
        {
            $this->refundsLastTimestamp = $refunds[0]['processed_at'];

            $this->refundsFirstTimestamp = $refunds[count($refunds)-1]['processed_at'];
        }

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
            list($fileData, $debugFileData) = $this->formatDataForFile($data);

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

            if ( ($this->isExplicitTimestampPassed === false) and
                ($this->isDarkRequest() === false))
            {
                $paymentOffset = $this->totalPayments > 0 ? 1 : 0;
                $refundOffset = $this->totalRefunds > 0 ? 1 : 0;
                (new AdminService)->setConfigKeys(
                    [ConfigKey::CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $this->paymentsLastTimestamp + $paymentOffset]
                );

                (new AdminService)->setConfigKeys(
                    [ConfigKey::CARD_REFUNDS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => $this->refundsLastTimestamp + $refundOffset]
                );
            }

            /* uncomment this if we need to generate output file for debugging
            * but do rememeber to scrub sensitive from file
            */

            $fileName = self::S3_PATH . $this->getFileToWriteName() . '_debug';

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($debugFileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE_OUTPUT)
                    ->entity($this->gatewayFile)
                    ->metadata($metadata)
                    ->save();
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

        $debugFileContent = [];

        $totalTransactions = 0;

        $cpsAuthData = $this->fetchAuthorizationDetails($data);

        $scroogeGatewayKeys = $this->fetchGatewayKeysFromScrooge($data);

        $paymentIds = [];
        $refundIds = [];

        $razorXEnabled = ($this->app['razorx']->getTreatment(
            UniqueIdEntity::generateUniqueId(),
            self::RAZORX_EXP_NAME,
            Mode::LIVE) === 'on');

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

                    $gatewayRequestID = $cpsAuthData[$settlementPayment->getId()]['gateway_reference_id2'] ?? '';

                    list($notesGST, $notesCorpName, $notesMTR) = $this->parseNotes($settlementPayment->getNotes());

                    $cardToken = '';

                    try
                    {
                        $cardToken = $this->getCardToken($settlementPayment->card);
                    }
                    catch(\Throwable $ex)
                    {
                        $this->trace->error(TraceCode::CARD_SETTLEMENT_CARD_TOKEN_NOT_FOUND_ERROR,
                            [
                                'payment_id'  => $settlementPayment->getId(),
                                'merchantIds' => $settlementPayment->getMerchantId(),
                                'error'       => $ex->getMessage(),
                            ]);

                        if($razorXEnabled === false)
                        {
                            throw $ex;
                        }
                    }

                    $cardTypeIdentifier = $settlementPayment->card->isCredit() ? 'C' : 'D';

                    $gatewayTID = $settlementPayment->terminal->getGatewayTerminalId();

                    $row = $cardTypeIdentifier . self::PIPE_SEPARATOR .
                        'P' . self::PIPE_SEPARATOR .
                        $this->getFormattedAmount($settlementPayment->getAmount()) . self::PIPE_SEPARATOR .
                        $gatewayRequestID . self::PIPE_SEPARATOR .
                        $gatewayTID . self::PIPE_SEPARATOR .
                        $settlementPayment->getAmount() . self::PIPE_SEPARATOR .
                        Carbon::createFromTimestamp($settlementPayment['captured_at'])
                            ->setTimezone(Timezone::IST)
                            ->format('d-M-y H:i:s')  . self::PIPE_SEPARATOR .
                        $this->getAuthCode($settlementPayment) . self::PIPE_SEPARATOR .
                        $this->getCardTokenBIN($cardToken) . self::PIPE_SEPARATOR .
                        '5' . self::PIPE_SEPARATOR .
                        $notesMTR . self::PIPE_SEPARATOR .
                        $notesGST . ' ' . $notesCorpName . (($razorXEnabled === true) ? self::PIPE_SEPARATOR : '');

                    $content[] = $cardToken . self::PIPE_SEPARATOR . $row;

                    $debugFileContent[] = $settlementPayment->getId() . self::PIPE_SEPARATOR . $row;

                    $paymentIds[] = $settlementPayment->getId();
                }
                catch (\Throwable $ex)
                {
                    $this->trace->error(TraceCode::CARD_SETTLEMENT_PAYMENTS_FILE_ROW_ERROR,
                        [
                            'payment_id'  => $settlementPayment->getId(),
                            'merchantIds' => $settlementPayment->getMerchantId(),
                            'error'       => $ex->getMessage(),
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
                    if(empty($settlementRefunds->payment->getCapturedAt()) === true)
                    {
                        continue;
                    }

                    $totalTransactions++;

                    $gatewayRequestID = $cpsAuthData[$settlementRefunds->payment->getId()]['gateway_reference_id2'] ?? '';

                    $refundRequestId = $scroogeGatewayKeys[$settlementRefunds->getId()]['requestID'] ?? '';

                    list($notesGST, $notesCorpName, $notesMTR) = $this->parseNotes($settlementRefunds->payment->getNotes());

                    $cardToken = '';

                    try
                    {
                        $cardToken = $this->getCardToken($settlementRefunds->payment->card);
                    }
                    catch(\Throwable $ex)
                    {
                        $this->trace->error(TraceCode::CARD_SETTLEMENT_CARD_TOKEN_NOT_FOUND_ERROR,
                            [
                                'payment_id'  => $settlementRefunds->payment->getId(),
                                'refund_id' => $settlementRefunds->getId(),
                                'merchantIds' => $settlementRefunds->getMerchantId(),
                                'error'       => $ex->getMessage(),
                            ]);

                        if($razorXEnabled === false)
                        {
                            throw $ex;
                        }
                    }

                    $cardTypeIdentifier = $settlementRefunds->payment->card->isCredit() ? 'C' : 'D';

                    $gatewayTID = $settlementRefunds->payment->terminal->getGatewayTerminalId();

                    $row = $cardTypeIdentifier . self::PIPE_SEPARATOR .
                        'P' . self::PIPE_SEPARATOR .
                        $this->getFormattedAmount($settlementRefunds->getBaseAmount()) . self::PIPE_SEPARATOR .
                        $refundRequestId . self::PIPE_SEPARATOR .
                        $gatewayTID . self::PIPE_SEPARATOR .
                        $settlementRefunds->getBaseAmount() . self::PIPE_SEPARATOR .
                        Carbon::createFromTimestamp($settlementRefunds['processed_at'])
                            ->setTimezone(Timezone::IST)
                            ->format('d-M-y H:i:s') . self::PIPE_SEPARATOR .
                        '' . self::PIPE_SEPARATOR .
                        $this->getCardTokenBIN($cardToken) . self::PIPE_SEPARATOR .
                        '6' . self::PIPE_SEPARATOR .
                        $notesMTR . self::PIPE_SEPARATOR .
                        $notesGST . ' ' . $notesCorpName .
                        (($razorXEnabled === true) ? (self::PIPE_SEPARATOR . $gatewayRequestID) : '');

                    $content[] = $cardToken . self::PIPE_SEPARATOR . $row;

                    $debugFileContent[] = $settlementRefunds->payment->getId() . self::PIPE_SEPARATOR . $row;

                    $refundIds[] = $settlementRefunds->payment->getId();

                }
                catch(\Throwable $ex)
                {
                    $this->trace->error(TraceCode::CARD_SETTLEMENT_REFUNDS_FILE_ROW_ERROR,
                        [
                            'refund_id'  => $settlementRefunds->getId(),
                            'payment_id'  => $settlementRefunds->payment->getId(),
                            'merchantIds' => $settlementRefunds->getMerchantId(),
                            'error'       => $ex->getMessage(),

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

        $textDebugRows = array_merge($header, $debugFileContent, $trailer);

        $this->trace->info(TraceCode::CARD_SETTLEMENT_FILE_DETAILS, [
            'location' => 'After format for file',
            'payments' => $paymentIds,
            'refunds'  => $refundIds,
            'totalTxn' => $totalTransactions,
        ]);

        $this->totalPayments = count($paymentIds);

        $this->totalRefunds = count($refundIds);

        return array(implode("\r\n", $textRows), implode("\r\n", $textDebugRows));
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

        $notesCorpName = trim(preg_replace('/\s+/', ' ', $notesCorpName));

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
                'recipient' => Constants::MAIL_ADDRESSES[Constants::BANKING_POD_TECH]
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

    protected function fetchGatewayKeysFromScrooge($data)
    {
        $result = [];

        $refundIds = array_key_exists('refunds',$data) === true ? array_pluck($data['refunds'], 'id'): [];

        $refundIds = array_values($refundIds);

        $gatewayKeyNames = [
            'requestID',
        ];

        foreach (array_chunk($refundIds, self::BULK_LIMIT) as $refunds)
        {
            $input = [
                'refund_ids' => $refunds,
                'gateway_key_names' => $gatewayKeyNames,
            ];

            $response = $this->app['scrooge']->fetchBulkGatewayKeys($input);

            $result = array_merge($result, $response['data']);
        }

        $this->trace->info(TraceCode::CARD_SETTLEMENT_FILE_DETAILS, [
            'location'  => 'Scrooge response merged',
            'refundIds' => $refundIds,
            'response'  => $result,
        ]);

        if(count($refundIds) !== count($result))
        {
            throw new GatewayFileException
            (
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'            => $this->gatewayFile->getId(),
                    'message'       => 'Discrepancy in refund data fetch from scrooge',
                    'Refund IDs'   => $refundIds,
                    'Refund count'  => count($refundIds),
                    'Scrooge resp'  => $result,
                ]
            );
        }

        return $result;
    }

    /**
     * @param $now
     * @return array($begin, $end))
     *
     * The files have to be generated 4 times in a day. Each file will consist of txn from prev window.
     *
     * 1. 00:00 hrs
     * 2. 12:00 hrs
     * 3. 14:00 hrs
     * 4. 17:00 hrs
     *
     * Logic: Generate file for prev window wrt current timestamp
     */
    protected function calculateBeginEndForFile($now)
    {
        if ( ($now >= Carbon::today(Timezone::IST)->getTimestamp()) and
             ($now < Carbon::today(Timezone::IST)->addHours(12)->getTimestamp()))
        {
            return array(Carbon::yesterday(Timezone::IST)->addHours(17)->getTimestamp(),
                Carbon::today(Timezone::IST)->getTimestamp()-1);
        }

        if ( ($now >= Carbon::today(Timezone::IST)->addHours(12)->getTimestamp()) and
            ($now < Carbon::today(Timezone::IST)->addHours(14)->getTimestamp()))
        {
            return array(Carbon::today(Timezone::IST)->getTimestamp(),
                Carbon::today(Timezone::IST)->addHours(12)->getTimestamp()-1);
        }

        if ( ($now >= Carbon::today(Timezone::IST)->addHours(14)->getTimestamp()) and
            ($now < Carbon::today(Timezone::IST)->addHours(17)->getTimestamp()))
        {
            return array(Carbon::today(Timezone::IST)->addHours(12)->getTimestamp(),
                Carbon::today(Timezone::IST)->addHours(14)->getTimestamp()-1);
        }

        if ( ($now >= Carbon::today(Timezone::IST)->addHours(17)->getTimestamp()) and
            ($now < Carbon::tomorrow(Timezone::IST)->getTimestamp()))
        {
            return array(Carbon::today(Timezone::IST)->addHours(14)->getTimestamp(),
                Carbon::today(Timezone::IST)->addHours(17)->getTimestamp()-1);
        }

        throw new GatewayFileException
        (
            ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
            [
                'id'      => $this->gatewayFile->getId(),
                'message' => "Begin, End timestamps could not be generated for the file",
                'timestamp $now' => $now,
            ]
        );
    }

    public function isDarkRequest(): bool
    {
        $url = Request::url();

        return starts_with($url, 'https://api-dark.razorpay.com');
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





<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Constants\Environment;
use RZP\Services\Beam\Service;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Services\Beam\Constants as BeamConstants;

class Sbi extends Base
{
    const BANK_CODE         = IFSC::SBIN;
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::SBI_EMI_FILE;
    const FILE_NAME         = 'GGCMS1';
    const BEAM_FILE_TYPE    = 'emi';

    const TEST_ENCRYPTION_KEY = 'T8DIATjuwS';

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::fetchEntities().
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $emiPaymentsForBank = $this->repo
                                   ->payment
                                   ->fetchEmiPaymentsWithRelationsBetween(
                                        $begin,
                                        $end,
                                        static::BANK_CODE,
                                        [
                                           'card.globalCard',
                                           'emiPlan',
                                           'merchant.merchantDetail',
                                           'merchant.terminals'
                                        ]);

        return $emiPaymentsForBank;
    }

    public function generateEmiFilePassword()
    {
        if ($this->app->environment(Environment::TESTING))
        {
            return self::TEST_ENCRYPTION_KEY;
        }

        return bin2hex(openssl_random_pseudo_bytes(256));
    }

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::createFile($data).
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

            $fileName = $this->getFileToWriteName();

            $metadata = $this->getH2HMetadata();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->encrypt(
                        Service::ENCRYPTION_TYPE,
                        [
                            'mode'   => Service::ENCRYPTION_MODE,
                            'secret' => $data['password'],
                        ])
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
        $body = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        // date 6 chars + time 4 chars + 4 seq numbers
        $uniqueReferenceNum = Carbon::now()->setTimezone(Timezone::IST)->format('mdyHi') . '0000';

        /**
         * @var $emiPayment Payment\Entity
         */
        foreach ($data['items'] as $emiPayment)
        {
            try
            {
                $mid = null;

                $tid = null;

                $emiPlan = $emiPayment->emiPlan;

                $merchantDetail = $emiPayment->merchant->merchantDetail;

                $terminals = $emiPayment->merchant->terminals;

                /**
                 * @var $terminal Terminal\Entity
                 */
                foreach ($terminals as $terminal)
                {
                    if (($terminal[Terminal\Entity::GATEWAY] === Payment\Gateway::EMI_SBI) and
                        ($terminal->isEnabled() === true))
                    {
                        if (empty($mid) === true)
                        {
                            $mid = $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

                            $tid = $terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];
                        }
                        else
                        {
                            throw new LogicException(
                                'Multiple SBI MIDs found for merchant',
                                null,
                                [
                                    'gateway'       => 'emi_sbi',
                                    'payment_id'    => $emiPayment['id'],
                                    'merchant_id'   => $merchantDetail[Detail\Entity::MERCHANT_ID],
                                    'terminal'      => $terminal['id'],
                                ]);
                        }
                    }
                }

                if (empty($mid) === true)
                {
                    throw new LogicException(
                        'No SBI MID found for merchant',
                        null,
                        [
                            'gateway'       => 'emi_sbi',
                            'payment_id'    => $emiPayment['id'],
                            'merchant_id'   => $merchantDetail[Detail\Entity::MERCHANT_ID],
                        ]);
                }

                $totalTransactions++;

                $uniqueReferenceNum++;

                $principalAmount = $emiPayment->getAmount();

                $rate = $emiPlan->getRate() / 100;

                $tenure = $emiPlan->getDuration();

                $businessName = substr($merchantDetail[Detail\Entity::BUSINESS_NAME], 0, 40);

                $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

                $emiAmount = number_format($emiAmount / 100, 2, '.', '');

                $body[] =
                    'DD' .    // record type always DD
                    'R' . $this->numpad($uniqueReferenceNum, 14) .
                    $this->strpad('Razor Pay', 40) .
                    $this->numpad($this->getCardNumber($emiPayment->card), 19) .
                    $this->numpad($principalAmount, 17) .
                    $this->numpad($tenure, 3) .
                    $this->strpad($this->getAuthCode($emiPayment), 6) .
                    Carbon::createFromTimestamp($emiPayment['authorized_at'])->format('dmY') .
                    $this->strpad('Razor Pay', 40) .
                    $this->numpad($mid, 16) .
                    $this->strpad($businessName, 40) .
                    $this->strpad($tid, 8) .
                    str_pad($emiPlan->getRate(), 7, '0', STR_PAD_RIGHT) .
                    $this->strpad('', 40) .
                    $this->numpad($principalAmount, 17) .
                    'F' .
                    '0' .
                    ' ' .
                    $this->numpad('0', 7) .
                    $this->strpad('GG0001' . substr($mid, -4), 20) .
                    $this->numpad('0', 17) .
                    $this->numpad($emiAmount, 17) .
                    $this->strpad('', 108);

                $rowLength = strlen(end($body));

                if ($rowLength !== 450)
                {
                    throw new LogicException(
                        'Row not formatted properly',
                        null,
                        [
                            'gateway'       => 'emi_sbi',
                            'length'        => $rowLength,
                            'payment_id'    => $emiPayment['id'],
                        ]);
                }

                // If a row is not added in the file, then that row's principal amount
                // must not be added to the total amount
                $totalAmount = $totalAmount + $principalAmount;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

        $header = [
            'HH' .
            Carbon::now()->setTimezone(Timezone::IST)->format('dmY') .
            Carbon::now()->setTimezone(Timezone::IST)->format('His') .
            $this->numpad($totalTransactions, 5) .
            $this->numpad($totalAmount / 100, 17) .
            'F' .
            $this->strpad('', 411)
        ];

        $textRows = array_merge($header, $body);

        return implode("\r\n", $textRows);
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

    protected function sendEmiFile($data)
    {
        // todo: Push to beam once decryption is handled at beam side
        /*
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $data =  [
            Service::BEAM_PUSH_FILES   => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => BeamConstants::SBI_EMI_FILE_JOB_NAME
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'settlements',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'File Send failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::EMI]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
        */
    }

    protected function getFileToWriteName()
    {
        return static::FILE_NAME . Carbon::now()->setTimezone(Timezone::IST)->format('YmdHis');
    }

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is rate/100, say .14
        // $monthlyRate is a/12 i.e should be treated as .14/12
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = $annualRate / 1200;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return (round($num / $den));
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

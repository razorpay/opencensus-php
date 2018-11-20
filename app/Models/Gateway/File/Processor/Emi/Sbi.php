<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
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

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::createFile($data).
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
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE, [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile($data)
    {
        $body = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        // date 6 chars + time 4 chars + 4 seq numbers
        $uniqueReferenceNum = Carbon::now()->format('mdyHi') . '0000';

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
                                    'payment_id' => $emiPayment['id'],
                                    'merchant_id' => $merchantDetail[Detail\Entity::MERCHANT_ID],
                                    'terminal' => $terminal['id'],
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
                            'payment_id' => $emiPayment['id'],
                            'merchant_id' => $merchantDetail[Detail\Entity::MERCHANT_ID],
                        ]);
                }

                $totalTransactions++;

                $uniqueReferenceNum++;

                $principalAmount = $emiPayment->getAmount();

                $totalAmount = $totalAmount + $principalAmount;

                $rate = $emiPlan->getRate() / 100;

                $tenure = $emiPlan->getDuration();

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
                    $this->strpad($merchantDetail[Detail\Entity::BUSINESS_NAME], 40) .
                    $this->strpad($tid, 8) .
                    str_pad(str_pad($rate, 2, '0', STR_PAD_LEFT), 7, '0', STR_PAD_RIGHT) .
                    $this->strpad('', 40) .
                    $this->numpad($principalAmount, 17) .
                    'F' .
                    '0' .
                    ' ' .
                    $this->numpad('0', 7) .
                    $this->strpad('GG0001' . substr($mid, -4), 20) .
                    $this->numpad('0', 17) .
                    $this->numpad($this->getEmiAmount($principalAmount, $rate, $tenure), 17) .
                    $this->strpad('', 108);

                if (strlen(end($body)) !== 450)
                {
                    throw new LogicException('Row not formatted properly', null, ['length' => strlen(end($body))]);
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

        $header = [
            'HH' .
            Carbon::now()->format('dmY') .
            Carbon::now()->format('His') .
            $this->numpad($totalTransactions, 5) .
            $this->numpad($totalAmount, 17) .
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
            'channel'   => '',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'File Send failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::EMI]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }

    protected function getFileToWriteName()
    {
        return static::FILE_NAME . Carbon::now()->setTimezone(Timezone::IST)->format('YmdHis');
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

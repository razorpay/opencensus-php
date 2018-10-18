<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Models\Merchant\Detail\Entity as E;
use RZP\Services\Beam\Constants as BeamConstants;

class Sbi extends Base
{
    const BANK_CODE         = IFSC::SBIN;
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_TYPE         = FileStore\Type::SBI_EMI_FILE;
    const FILE_NAME         = 'Sbi_Emi_File';
    const BEAM_FILE_TYPE    = 'emi';

    protected $file;

    protected function formatDataForFile($data)
    {
        $body = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        // date 6 chars + time 4 chars + 4 seq numbers
        //mmddyy
        $uniqueReferenceNum = Carbon::now()->format('mdyHi') . '0000';

        foreach ($data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $merchant = $emiPayment->merchant;

            $merchantDetail = $merchant->merchantDetail;

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
                $this->strpad($merchantDetail[E::SBI_MID], 16) .
                $this->strpad($merchantDetail[E::BUSINESS_NAME], 40) .
                $this->strpad('38R00001', 8) .
                $this->formatRate($rate) .
                $this->strpad('', 40) .
                $this->numpad($principalAmount, 17) .
                'F' .
                '0' .
                ' ' .
                $this->numpad('0', 7) .
                $this->strpad('GG0001' . substr($merchantDetail[E::SBI_MID], -4), 20) .
                $this->numpad('0', 17) .
                $this->numpad($this->getEmiAmount($principalAmount, $rate, $tenure), 17) .
                $this->strpad('', 108);
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

        return $this->getTxtFromRows($textRows);
    }

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

    private function numpad($num, $count)
    {
        return strtoupper(str_pad($num, $count, '0', STR_PAD_LEFT));
    }

    private function strpad($str, $length)
    {
        return strtoupper(str_pad($str, $length, ' ', STR_PAD_RIGHT));
    }

    private function formatRate($rate)
    {
        if ($rate < 10)
        {
            $rate = '0' . $rate;
        }

        return str_pad($rate, 7, '0', STR_PAD_RIGHT);
    }

    private function getTxtFromRows(array $rows): string
    {
        $txt = '';

        $totalElements = count($rows);

        foreach ($rows as $index => $row)
        {
            $txt .= $row;

            // Don't add newline for the last line
            if ($index < $totalElements - 1)
            {
                //
                // Double quote is required to suggest new line
                // Single quote will NOT work
                //
                $txt .= "\r\n";
            }
        }
        return $txt;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }
}

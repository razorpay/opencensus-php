<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Register;

use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayFileException;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\EMandate\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;
use RZP\Gateway\Netbanking\Hdfc\Fields;
use ZipArchive;
use Carbon\Carbon;

class EnachRbl extends Base
{
    const STEP                  = 'register';
    const GATEWAY               = Payment\Gateway::ENACH_RBL;
    const FILE_NAME             = 'rbl-enach/outgoing/MNDT_INP/MMS-CREATE-RATN-RATNA0001-{$date}-ESIGN000001-INP';
    const INDIVIDUAL_FILE_NAME  = 'MMS-CREATE-RATN-RATNA0001-{$date}-ESIGN{$sequence}-INP';
    const EXTENSION             = FileStore\Format::ZIP;
    const INDIVIDUAL_EXTENSION  = FileStore\Format::XML;
    const FILE_TYPE             = FileStore\Type::RBL_ENACH_REGISTER;
    const FILE_METADATA         = [
                                    'gid'   => '10000',
                                    'uid'   => '10006',
                                    'mode'  => '33188'
                                  ];

    public function fetchEntities(): PublicCollection
    {
        // TODO: Fetch entities based on enach entity's registration date

        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $payments = $this->repo->payment->fetchPendingEmandateRegistrationForEnach($begin, $end);

        $paymentIds = $payments->pluck(Payment\Entity::ID)->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_REGISTER_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $payments;
    }

    public function generateData(PublicCollection $payments)
    {
        return $payments;
    }

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $zipFilePath = 'temp_enach_reg_zip_file_name.zip';

            $this->createZipFileWithData($fileData, $zipFilePath);

            $fileName = $this->getZipFileToWriteName(false);

            $creator = new FileStore\Creator;

            $file = $creator->extension(static::EXTENSION)
                            ->localFilePath($zipFilePath)
                            ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[static::EXTENSION][0])
                            ->name($fileName)
                            ->store(FileStore\Store::S3)
                            ->type(static::FILE_TYPE)
                            ->entity($this->gatewayFile)
                            ->metadata(static::FILE_METADATA)
                            ->save()
                            ->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ]);
        }
    }

    protected function formatDataForFile($payments)
    {
        $rows = [];

        foreach ($payments as $payment)
        {
            $signedXml = $payment->enach->getSignedXml();

            if (empty($signedXml) === true)
            {
                throw new LogicException(
                    'Found empty signed xml for enach entity',
                    ErrorCode::SERVER_ERROR_SIGNED_XML_EMPTY,
                    [
                        'payment_id'    => $payment->getId()
                    ]);
            }

            $rows[] = $signedXml;
        }

        return $rows;
    }

    protected function createZipFileWithData(array $data, string $zipFilePath)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true)
        {
            throw new RuntimeException(
                'Could not create enach zip file',
                [
                    'filename' => $zipFilePath
                ]);
        }

        foreach ($data as $index => $signedXml)
        {
            $fileName = $this->getIndividualFileToWriteNameWithExt($index + 1);

            $zip->addFromString($fileName, $signedXml);
        }

        $zip->close();
    }

    protected function getZipFileToWriteName($withExt = true)
    {
        // TODO: Instead of `now`, use the file date from enach entity

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date]);

        $fileName = $this->getStorageDir() . $fileName;

        if ($this->isTestMode() === true)
        {
            $fileName .= '_' . $this->mode;
        }

        if ($withExt === true)
        {
            $fileName .= '.' . static::EXTENSION;
        }

        return $fileName;
    }

    protected function getIndividualFileToWriteNameWithExt($index)
    {
        // TODO: Instead of `now`, use the file date from enach entity

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $sequence = str_pad($index, 6, '0', STR_PAD_LEFT);

        $fileName = strtr(static::INDIVIDUAL_FILE_NAME, ['{$date}' => $date, '{$sequence}' => $sequence]);

        if ($this->isTestMode() === true)
        {
            $fileName = $fileName . '_' . $this->mode;
        }

        $fileName .= '.' . static::INDIVIDUAL_EXTENSION;

        return $fileName;
    }

    protected function getStorageDir()
    {
        return storage_path(FileStore\Store::STORAGE_DIRECTORY);
    }
}

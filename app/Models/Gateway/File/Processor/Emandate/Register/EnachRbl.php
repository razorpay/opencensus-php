<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Register;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\EMandate\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;
use RZP\Gateway\Netbanking\Hdfc\Fields;

class EnachRbl extends Base
{
    const STEP          = 'register';
    const GATEWAY       = Payment\Gateway::ENACH_RBL;
    const FILE_NAME     = 'rbl-enach/outgoing/TXN_INP/ACH-DR-RATN-RATNA0001-{$date}-000001-INP';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::RBL_ENACH_REGISTER;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $payments = $this->repo->payment->fetchPendingEMandateRegistration(static::GATEWAY, $begin, $end);

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
        // TODO: - Get all enach entities of all the payments
        // - Loop through all enach entities and create files for
        //   each one of them using the signed_xml content
        // - Zip all of these files together and create a UFH entity
        //   and associate it with the gateway file.

        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata(static::FILE_METADATA)
                    ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile($payments)
    {

    }
}

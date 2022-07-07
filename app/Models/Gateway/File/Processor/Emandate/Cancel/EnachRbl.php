<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Cancel;

use Storage;
use DOMDocument;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Enach\Npci\Netbanking\CancelRequestTags;

class EnachRbl extends Base
{
    use FileHandler;

    const ACQUIRER  = Payment\Gateway::ACQUIRER_RATN;
    const GATEWAYS  = [Payment\Gateway::ENACH_RBL];
    const METHODS   = [Payment\Method::EMANDATE];
    const EXTENSION = FileStore\Format::ZIP;
    const FILE_TYPE = FileStore\Type::RBL_ENACH_CANCEL;
    const S3_PATH   = 'rbl/nach/input_file/';
    const FILE_NAME = 'MMS-CANCEL-RATN-RATNA0001-{$date}-ESIGN{$count}-INP';
    const ZIP_FILE  = 'MMS-CANCEL-RATN-RATNA0001-{$date}-ESIGN000001-INP';

    protected $fileStore = [];

    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }
        try
        {
            $xmlData = $this->generateXmls($data);

            $date = Carbon::now(Timezone::IST)->format('dmY');

            foreach ($xmlData as $key => $xmls)
            {
                foreach ($xmls as $count => $xml)
                {
                    $count = str_pad(++$count, 6, '0', STR_PAD_LEFT);

                    $fileName = strtr(self::FILE_NAME, ['{$date}' => $date, '{$count}' => $count]);

                    $dirName = strtr(self::ZIP_FILE, ['{$date}' => $date]);

                    $xmlFilePath = $dirName . DIRECTORY_SEPARATOR . $fileName . '.xml';

                    Storage::put($xmlFilePath, $xml);
                }

                $this->generateZipFile($dirName);
            }

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE, [
                'id' => $this->gatewayFile->getId(),
            ], $e);
        }
    }

    protected function generateXmls(PublicCollection $tokens): array
    {
        $xmls = [];

        foreach ($tokens as $token)
        {
            $utilityCode = $token->terminal->getGatewayMerchantId2();

            $sponsorBankIfsc = $token->terminal->getGatewayAccessCode();

            $umrn = $token->getGatewayToken();

            $destinationBankIfsc = $token->getIfsc();

            $createdDate = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s');

            $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?> <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.011.001.04"/>');

            $mandateRoot = $document->addChild(CancelRequestTags::MANDATE_CANCEL_REQUEST);

            $grp = $mandateRoot->addChild(CancelRequestTags::GROUP_HEADER);

            $grp->addChild(CancelRequestTags::MSG_ID, $token['id']);

            $grp->addChild(CancelRequestTags::CREATION_DATE_TIME, $createdDate);

            $finInstnId1 = $grp->addChild(CancelRequestTags::INSTG_AGT)
                ->addChild(CancelRequestTags::FINANCIAL_INST_ID);

            $finInstnId1->addChild(CancelRequestTags::CLR_SYS_MEMBER_ID)
                ->addChild(CancelRequestTags::MEMBER_ID, $sponsorBankIfsc);

            $finInstnId2 = $grp->addChild(CancelRequestTags::INSTD_AGT)
                ->addChild(CancelRequestTags::FINANCIAL_INST_ID);

            $finInstnId2->addChild(CancelRequestTags::CLR_SYS_MEMBER_ID)
                ->addChild(CancelRequestTags::MEMBER_ID, $destinationBankIfsc);

            $undrlygCxlDtls = $mandateRoot->addChild(CancelRequestTags::UNDERLYING_CANCEL_DETAILS);

            $undrlygCxlDtls->addChild(CancelRequestTags::CANCEL_RSN)
                ->addChild(CancelRequestTags::RSN)
                ->addChild(CancelRequestTags::PRTRY, 'C002');

            $undrlygCxlDtls->addChild(CancelRequestTags::ORIGINAL_MANDATE)
                ->addChild(CancelRequestTags::ORIGINAL_MANDATE_ID, $umrn);

            $dom = new DOMDocument("1.0");

            $dom->preserveWhiteSpace = false;

            $dom->formatOutput = true;

            $dom->loadXML($document->asXML());

            $xmls[$utilityCode][] = $dom->saveXML();
        }

        return $xmls;
    }

    public function sendFile($data)
    {
        $fileInfo = [];

        $files = $this->gatewayFile
                      ->files()
                      ->whereIn(FileStore\Entity::ID, $this->fileStore)
                      ->get();

        foreach ($files as $file)
        {
            $fullFileName = $file->getName() . '.' . $file->getExtension();

            $fileInfo[] = $fullFileName;
        }

        $bucketConfig = $this->getBucketConfig(self::FILE_TYPE);

        $data = [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::RBL_ENACH_DEBIT_FILE_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // Retry timelines in seconds
        $timelines = [60, 300, 900, 1800, 3600];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'emandate',
            'filetype'  => FileStore\Type::RBL_ENACH_CANCEL,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $this->sendBeamRequest($data, $timelines, $mailInfo, true);
    }
}

<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Base\RuntimeManager;
use RZP\Models\Gateway\File;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Gateway\File\Constants;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Netbanking\Sbi\Emandate\DebitFileHeadings as Headings;
use RZP\Trace\TraceCode;

class Sbi extends Base
{
    use FileHandler;

    const GATEWAY = Payment\Gateway::NETBANKING_SBI;

    const EXTENSION = FileStore\Format::TXT;

    const FILE_TYPE = FileStore\Type::SBI_EMANDATE_DEBIT;

    //<6 digit Corporate ID>_MMS_TXN_<Date in DDMMYYYY>_<Sequence no. of day for the file>.txt/csv/xls
    const FILE_NAME = '{$utilityCode}_MMS_TXN_{$date}_001';

    const STEP      = 'debit';

    const CORPORATE_NAME = 'Razorpay Software Pvt Ltd';

    const BASE_STORAGE_DIRECTORY = 'Sbi/Emandate/Netbanking/';

    public function __construct()
    {
        parent::__construct();

        $this->gatewayRepo = $this->repo->netbanking;
    }

    protected function formatDataForFile($tokens): array
    {
        $rows = [];
        $rowsTxtData = [];

        foreach ($tokens as $index => $token)
        {
            $terminal = $token->terminal;

            $merchant = $token->merchant;

            $paymentId = $token['payment_id'];

            $debitDate = Carbon::today(Timezone::IST)->format('d/m/Y');

            $gatewayMerchantId = $this->getCorporateId($terminal);

            if (isset($rows[$gatewayMerchantId]) === true)
            {
                $srNo = count($rows[$gatewayMerchantId]) + 1;
            }
            else
            {
                $srNo = 1;
            }

            $rows[$gatewayMerchantId][] = [
                Headings::SERIAL_NUMBER           => $srNo,
                Headings::UMRN                    => $token->getGatewayToken(),
                Headings::CORPORATE_CODE          => $gatewayMerchantId,
                Headings::CORPORATE_NAME          => $merchant->getFilteredDba(),
                Headings::MANDATE_HOLDER_NAME     => $token->getBeneficiaryName(),
                Headings::DEBIT_ACC_NO            => $token->getAccountNumber(),
                Headings::DEBIT_DATE              => $debitDate,
                Headings::AMOUNT                  => $this->getFormattedAmount($token['payment_amount']),
                Headings::CUSTOMER_REF_NO         => $paymentId,
            ];
        }

        $headers = implode(',', Headings::DEBIT_FILE_HEADERS) . "\r\n";

        foreach ($rows as $gatewayMerchantId => $row)
        {
            $rowsTxtData[$gatewayMerchantId] = $this->getTextData($row, $headers,',');;
        }

        return $rowsTxtData;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = static::BASE_STORAGE_DIRECTORY . strtr(static::FILE_NAME, ['{$utilityCode}' => $data['utilityCode'], '{$date}' => $date]);

        return $fileName;
    }

    protected function getNewGatewayPaymentEntity(): Netbanking\Base\Entity
    {
        return new Netbanking\Base\Entity;
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, '2', '.', '');
    }

    /**
     * @throws GatewayFileException
     */
    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                // since file data is grouped based on utility code, it will be part of the file name
                $fileName = $this->getFileToWriteNameWithoutExt(['utilityCode' => $key]);

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

                $fileStoreIds[] = $file->getId();

                $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());
            }

            $this->fileStore = $fileStoreIds;

            $this->gatewayFile->setStatus(File\Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::NACH_DEBIT_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);

            $this->generateMetric(Metric::EMANDATE_FILE_GENERATED);

            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "GEN_SBI");

        }
        catch (\Throwable $e)
        {
            $this->generateMetric(Metric::EMANDATE_FILE_GENERATION_ERROR);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function getCorporateId($terminal)
    {
        $id = $terminal->getGatewayMerchantId2();

        if (empty($id) === true)
        {
            $id = $this->config['gateway.netbanking_sbi.emandate_corporate_id'];
        }

        return $id;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('12288M'); // 12 GB

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }
}

<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Terminal\Type;
use RZP\Services\NbPlus\Emandate;
use RZP\Models\Gateway\File\Status;
use RZP\Services\NbPlus\Netbanking;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class Icici extends Base
{
    const FILE_NAME                   = 'Icici_Netbanking_Refunds';
    const EXTENSION                   = FileStore\Format::XLSX;
    const FILE_TYPE                   = FileStore\Type::ICICI_NETBANKING_REFUND;
    const FILE_TYPE_EMI               = FileStore\Type::ICICI_NETBANKING_REFUND_EMI;
    const FILE_TYPE_DIRECT_SETTLEMENT = FileStore\Type::ICICI_NETBANKING_REFUND_DIRECT_SETTLEMENT;
    const GATEWAY                     = Payment\Gateway::NETBANKING_ICICI;
    const PAYMENT_TYPE_ATTRIBUTE      = Payment\Entity::BANK;
    const GATEWAY_CODE                = [Payment\Processor\Netbanking::ICIC_C, IFSC::ICIC];
    const BASE_STORAGE_DIRECTORY      = 'Icici/Refund/Netbanking/';

    const DIRECT                      = 'direct';
    const EMI                         = 'emi';

    /*
     * Since File data generation is separated from mail data generation,
     * this helps in maintaining a record of file id's to gateway merchant id (PID / Payee ID)
     */
    protected $fileIdToPidMap;

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        $fileIdToPidMap = [];

        try
        {
            $allFilesData = $this->formatDataForFile($data);

            foreach ($allFilesData as $pid => $fileData)
            {
                if ($pid === self::DIRECT)
                {
                    $fileType = static::FILE_TYPE;
                }
                elseif ($pid === self::EMI)
                {
                    $fileType = static::FILE_TYPE_EMI;
                }
                else
                {
                    $fileType = static::FILE_TYPE_DIRECT_SETTLEMENT;
                }

                $fileName = $this->getFileNameWithPid($pid);

                $creator = new FileStore\Creator;

                $creator->extension(static::EXTENSION)
                        ->content($fileData)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type($fileType)
                        ->entity($this->gatewayFile)
                        ->save();

                $file = $creator->getFileInstance();

                $fileIdToPidMap[$file->getId()] = $pid;

                $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());
            }

            $this->fileIdToPidMap = $fileIdToPidMap;

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
        finally
        {
            $this->trace->info(TraceCode::GATEWAY_FILES_CREATED, $fileIdToPidMap);

        }
    }

    public function sendFile($data)
    {
        try
        {
            $recipients = $this->gatewayFile->getRecipients();

            $allMailData = $this->formatDataForMail($data);

            foreach ($allMailData as $mailData)
            {
                $refundFileMail = new RefundFileMail($mailData, static::GATEWAY, $recipients);

                Mail::queue($refundFileMail);
            }

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->reconcileNetbankingRefunds($data);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $bankRef = $this->fetchBankPaymentId($row);

            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], Timezone::IST)->format('jS F Y');

            if ($this->isDirectSettlementTerminal($row['terminal']) === true)
            {
                if (isset($formattedData[$row['terminal']['gateway_merchant_id']]) === true)
                {
                    $srNo = count($formattedData[$row['terminal']['gateway_merchant_id']]) + 1;
                }
                else
                {
                    $srNo = 1;
                }

                $formattedData[$row['terminal']['gateway_merchant_id']][] = [
                    'Sr No'                 => $srNo,
                    'Payee_id'              => $row['terminal']['gateway_merchant_id'],
                    'SPID'                  => $row['terminal']['gateway_merchant_id2'],
                    'Bank Reference No.'    => $bankRef,
                    'Transaction Date'      => $date,
                    'Transaction Amount'    => $row['payment']['amount'] / 100,
                    'Refund Amount'         => $row['refund']['amount'] / 100,
                    'Transaction Id'        => $row['payment']['id'],
                    'Reversal/Cancellation' => 'C',
                    'Remarks'               => '',
                ];
            }
            elseif ((strpos($bankRef, 'CFL-') !== false))
            {
                if (isset($formattedData[self::EMI]) === true)
                {
                    $srNo = count($formattedData[self::EMI]) + 1;
                }
                else
                {
                    $srNo = 1;
                }

                $formattedData[self::EMI][] = [
                    'Sr No'                 => $srNo,
                    'Payee_id'              => $row['terminal']['gateway_merchant_id'],
                    'SPID'                  => $row['terminal']['gateway_merchant_id2'],
                    'Bank Reference No.'    => $bankRef,
                    'Transaction Date'      => $date,
                    'Transaction Amount'    => $row['payment']['amount'] / 100,
                    'Refund Amount'         => $row['refund']['amount'] / 100,
                    'Transaction Id'        => $row['payment']['id'],
                    'Reversal/Cancellation' => 'C',
                    'Remarks'               => '',
                ];
            }
            else
            {
                if (isset($formattedData[self::DIRECT]) === true)
                {
                    $srNo = count($formattedData[self::DIRECT]) + 1;
                }
                else
                {
                    $srNo = 1;
                }

                $formattedData[self::DIRECT][] = [
                    'Sr No'                 => $srNo,
                    'Payee_id'              => $row['terminal']['gateway_merchant_id'],
                    'SPID'                  => $row['terminal']['gateway_merchant_id2'],
                    'Bank Reference No.'    => $bankRef,
                    'Transaction Date'      => $date,
                    'Transaction Amount'    => $row['payment']['amount'] / 100,
                    'Refund Amount'         => $row['refund']['amount'] / 100,
                    'Transaction Id'        => $row['payment']['id'],
                    'Reversal/Cancellation' => 'C',
                    'Remarks'               => '',
                ];
            }
        }

        return $formattedData;
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
        ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            if ($data['payment']['method'] === Payment\Method::EMANDATE)
            {
                return $data['gateway'][Emandate::BANK_REFERENCE_ID]; // emandate payments through nbplus service
            }
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id'];
    }

    protected function formatDataForMail(array $data)
    {
        $fileIds = array_keys($this->fileIdToPidMap);

        $files = $this->gatewayFile
                      ->files()
                      ->whereIn(FileStore\Entity::ID, $fileIds)
                      ->get();

        $today = Carbon::now(Timezone::IST)->format('jS F Y');

        $mailInfo = $this->fetchMailInfo($data);

        foreach ($files as $file)
        {
            $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

            $pid = $this->fileIdToPidMap[$file->getId()];

            $mailData[] = [
                'file_name'  => basename($file->getLocation()),
                'signed_url' => $signedUrl,
                'count'      => $mailInfo[$pid]['count'],
                'amount'     => $mailInfo[$pid]['amount'],
                'date'       => $today,
                'pid'        => $pid,
                'acc'        => $mailInfo[$pid]['account'] ?? null,
            ];
        }

        return $mailData;
    }

    protected function fetchMailInfo($data)
    {
        $mailInfo = [];

        foreach ($data as $row)
        {
            $bankRef = $this->fetchBankPaymentId($row);

            $pid = $row['terminal']['gateway_merchant_id'];

            if ($this->isDirectSettlementTerminal($row['terminal']) === true)
            {
                if (isset($mailInfo[$pid]) === false)
                {
                    $mailInfo[$pid]['amount']  = $row['refund']['amount'];
                    $mailInfo[$pid]['count']   = 1;
                    $mailInfo[$pid]['account'] = $row['bankAccount']['account_number'];
                }
                else
                {
                    $mailInfo[$pid]['count']++;
                    $mailInfo[$pid]['amount'] = $mailInfo[$pid]['amount'] + $row['refund']['amount'];
                }
            }
            elseif ((strpos($bankRef, 'CFL-') !== false))
            {
                if (isset($mailInfo[self::EMI]) === false)
                {
                    $mailInfo[self::EMI]['amount'] = $row['refund']['amount'];
                    $mailInfo[self::EMI]['count']  = 1;
                }
                else
                {
                    $mailInfo[self::EMI]['count']++;
                    $mailInfo[self::EMI]['amount'] = $mailInfo[self::EMI]['amount'] + $row['refund']['amount'];
                }
            }
            else
            {
                if (isset($mailInfo[self::DIRECT]) === false)
                {
                    $mailInfo[self::DIRECT]['amount'] = $row['refund']['amount'];
                    $mailInfo[self::DIRECT]['count']  = 1;
                }
                else
                {
                    $mailInfo[self::DIRECT]['count']++;
                    $mailInfo[self::DIRECT]['amount'] = $mailInfo[self::DIRECT]['amount'] + $row['refund']['amount'];
                }
            }
        }

        foreach ($mailInfo as $pid => $info)
        {

            $mailInfo[$pid]['amount'] = number_format(
                                       $mailInfo[$pid]['amount'] / 100,
                                       2,
                                       '.',
                                       ''
                                       );
        }

        return $mailInfo;
    }

    protected function isDirectSettlementTerminal($terminal)
    {
        return in_array(Type::DIRECT_SETTLEMENT_WITH_REFUND, $terminal['type'], true);
    }

    protected function collectPaymentData(Payment\Entity $payment): array
    {
        $terminal = $payment->terminal;

        $merchant = $payment->merchant;

        $bankAccount = $merchant->bankAccount;

        $col['payment'] = $payment->toArray();

        $col['terminal'] = $terminal->toArray();

        if (empty($bankAccount) === false)
        {
            $col['bankAccount'] = $bankAccount->toArray();
        }

        return $col;
    }

    protected function addNbplusGatewayEntitiesToDataWithNbPlusPaymentIds(array $data, array $nbplusPaymentIds, string $entity): array
    {
        $registrationPayments = $debitPayments = $netbankingPayments = [];

        foreach ($data as $record)
        {
            if ($record['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
            {
                if ($record['payment']['method'] === Payment\Method::EMANDATE)
                {
                    if ($record['payment']['recurring_type'] === Payment\RecurringType::INITIAL)
                    {
                        $registrationPayments[] =  $record['payment']['id'];
                    }
                    if ($record['payment']['recurring_type'] === Payment\RecurringType::AUTO)
                    {
                        $debitPayments[] = $record['payment']['id'];
                    }
                }
                if ($record['payment']['method'] === Payment\Method::NETBANKING)
                {
                    $netbankingPayments[] = $record['payment']['id'];
                }
            }
        }

        if (empty($registrationPayments) === false)
        {
            list($nbPlusGatewayEntities, $fetchSuccess) = $this->fetchNbPlusGatewayEntities($registrationPayments, 'emandate_registration');

            // Throwing an error in case of NBPlus fetch failure
            if ($fetchSuccess === false)
            {
                throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'id' => $this->gatewayFile->getId(),
                    ]
                );
            }

            $data = array_map(function($row) use ($nbPlusGatewayEntities)
            {
                $paymentId = $row['payment']['id'];

                if (isset($nbPlusGatewayEntities[$paymentId]) === true)
                {
                    $row['gateway'] = $nbPlusGatewayEntities[$paymentId];
                }

                return $row;
            }, $data);
        }

        if (empty($debitPayments) === false)
        {
            list($nbPlusGatewayEntities, $fetchSuccess) = $this->fetchNbPlusGatewayEntities($debitPayments, 'emandate_debit');

            // Throwing an error in case of NBPlus fetch failure
            if ($fetchSuccess === false)
            {
                throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'id' => $this->gatewayFile->getId(),
                    ]
                );
            }

            $data = array_map(function($row) use ($nbPlusGatewayEntities)
            {
                $paymentId = $row['payment']['id'];

                if (isset($nbPlusGatewayEntities[$paymentId]) === true)
                {
                    $row['gateway'] = $nbPlusGatewayEntities[$paymentId];
                }

                return $row;
            }, $data);
        }

       return parent::addNbplusGatewayEntitiesToDataWithNbPlusPaymentIds($data, $netbankingPayments, 'netbanking');
    }

    protected function getFileNameWithPid($pid)
    {
       $name = parent::getFileToWriteNameWithoutExt();

       return static::BASE_STORAGE_DIRECTORY . $pid . '/' . $name;
    }
}

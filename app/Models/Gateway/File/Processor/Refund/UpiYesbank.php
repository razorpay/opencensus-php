<?php


namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Gateway\File\Constants;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

/**
 * Class UpiYesbank
 * @package RZP\Models\Gateway\File\Processor\Refund
 */
class UpiYesbank extends Base
{

    const FILE_NAME = 'YesbankRefundFile';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::YESBANK_UPI_REFUND;
    const GATEWAY = Payment\Gateway::UPI_YESBANK;
    const BEAM_FILE_TYPE = 'refund';

    const BASE_STORAGE_DIRECTORY = 'upi/upi_yesbank/refund/normal_refund_file/';

    /**
     * @param int $begin
     * @param int $end
     * @return PublicCollection
     */
    protected function fetchRefundsFromAPI(int $begin, int $end): PublicCollection
    {
        //
        // Regular flow - fetching refunds from API DB
        //

        $refunds = $this->repo->refund->findBetweenTimestampsForGateway($begin, $end, static::GATEWAY);

        return $refunds;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY => static::GATEWAY,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];

        if (empty($refundIds) === false) {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    /** Checks whether the refund is initiated on UPS payment
     * @return bool
     */
    public function isUpsRefundGateway()
    {
        return true;
    }

    /** Build the refund file in the gateway manual refund file format
     * @param array $data
     * @return array
     */
    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row)
        {
            $bankadjref   = trim(RefundConstants::BANK_REF, '"');
            $flag         = trim(RefundConstants::FLAG, '"');
            $date         = trim(RefundConstants::DATE, '"');
            $adjAmt       = trim(RefundConstants::AMT, '"');
            $refId        = trim(RefundConstants::SHSER, '"');
            $uTxnId       = trim(RefundConstants::UTXID, '"');
            $fileName     = trim(RefundConstants::FILENAME, '"');
            $reason       = trim(RefundConstants::REASON, '"');
            $other        = trim(RefundConstants::SPECIFY_OTHER, '"');
            $refundId     = trim(RefundConstants::REFUND_ID,'"');

            $paymentAmount = $row['payment']['amount'] / 100;

            $refundAmount = $row['refund']['amount'] / 100;

            $npciReferenceId = $row['gateway']['npci_reference_id'] ?? $row['gateway']['customer_reference'] ?? '';

            $npciTxnId = $row['gateway']['npci_txn_id'] ?? '';

            $formattedData[] = [
                $bankadjref     => trim($npciReferenceId, '"'),
                $flag           => 'C',
                $date           => Carbon::createFromTimestamp($row['payment']['created_at'], Timezone::IST)->format('m/d/Y'),
                $adjAmt         => trim($refundAmount, '"'),
                $refId          => trim($npciReferenceId, '"'),
                $uTxnId         => trim($npciTxnId, '"'),
                $fileName       => 'REFUND_RAZORPAY',
                $reason         => 'Yesbank(Manual Refunds)',
                $other          => trim($paymentAmount, '"'),
                $refundId       => trim($row['refund']['id'], '"'),
            ];
        }

        return $formattedData;
    }

    /** Create the refund file and save it to
     *  S3 or filestore
     * @param $data
     * @throws Exception\GatewayFileException
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

            $fileName = $this->getFileToWriteNameWithoutExt();

            $metadata = $this->getH2HMetadata();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata($metadata)
                    ->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new Exception\GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'      => $this->gatewayFile->getId(),
                    'target'  => $this->gatewayFile->getTarget(),
                    'type'    => $this->gatewayFile->getType()
                ],
                $e);
        }
    }

    /** Format the generated file name with date appended to it
     * @return bool|string
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $dt = Carbon::now(Timezone::IST)->format('dmY_Hi');

        return (static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . '_' . $dt);
    }

    /** Metadata of file which includes the
     * last the modified time, gid, uid
     * @return array
     */
    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    /** Send the file to receipent mail
     * @param $data
     */
    public function sendFile($data)
    {
        $file = $this->gatewayFile
                    ->files()
                    ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                    ->first();

        try
        {
            $this->sendConfirmationMail();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'file_name' => $file->getName(),
                ]);
        }
    }

    /**
     * Send the file notification mail to the recipients
     */
    protected function sendConfirmationMail()
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl
        ];

        $recipients = ['finances.recon@razorpay.com'];

        $refundFileMail = new RefundFileMail($mailData, static::GATEWAY, $recipients);

        Mail::queue($refundFileMail);
    }

    /** This function fetches the gateway entity for all the list of paymentIds
     * @param array $data
     * @param array $paymentIds
     * @return array
     */
    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        $gatewayEntities = $this->repo->upi->fetchByPaymentIdsAndActionOnReplica(
            $paymentIds, Action::AUTHORIZE);

        // Creates new collection instance where key/value pairs are arranged by paymentIds
        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $data = array_map(function ($row) use ($gatewayEntities) {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId])) {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $data);

        return $data;
    }

    /** Fetch multiple gateway entities for paymentIds
     * @param array $paymentIds
     * @return mixed
     */
    protected function fetchMultipleUpsGatewayEntities(array $paymentIds)
    {
        $action = Constants::MULTIPLE_ENTITY_FETCH;

        $gateway = static::GATEWAY;

        $input = [
            Constants::MODEL            => Constants::AUTHORIZE,
            Constants::REQUIRED_FIELDS  => [
                Constants::CUSTOMER_REFERENCE,
                Constants::MERCHANT_REFERENCE,
                Constants::GATEWAY_MERCHANT_ID,
                Constants::GATEWAY_DATA,
                Constants::PAYMENT_ID,
                Constants::NPCI_TXN_ID,
            ],
            Constants::COLUMN_NAME      => Constants::PAYMENT_ID,
            Constants::VALUES           => $paymentIds,
        ];

        $response = $this->app['upi.payments']->action($action, $input, $gateway);

        return $response;
    }
}

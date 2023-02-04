<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    const BODY = 'body';
    const RESULTS = 'results';
    const MESSAGE = 'message';
    const IRN = 'Irn';
    const SIGNED_INVOICE = 'SignedInvoice';
    const INVOICE_NUMBER = 'InvoiceNumber';
    const INVOICE_NUMBER_ISSUE_DATE = 'InvoiceNumberIssueDate';
    const SIGNED_QR_CODE = 'SignedQRCode';
    const QR_CODE_URL = 'QRCodeUrl';
    const E_INVOICE_PDF_URL = 'EinvoicePdf';
    const GSP_STATUS = 'Status';
    const RESULT_STATUS = 'status';
    const SUCCESS_STATUS = 'Success';
    const FAILURE_STATUS = 'Failed';
    const ERROR_MESSAGE = 'errorMessage';
    const ERROR_DELIMITER = ':';
    const CALLOUT_MESSAGE   = 'callout_message';

    const E_INVOICE_COMPLETE_GENERATION_DATE   = 'e_invoice_complete_generation_date';

    // 1st Jan 2021 00:00:00 IST - Timestamp at which e-invoicing becomes mandatory.
    const EINVOICE_START_TIMESTAMP = 1609439400;

    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $eInvoiceEntity = new Entity;

        $eInvoiceEntity->setStatus(Status::STATUS_CREATED);

        $eInvoiceEntity->merchant()->associate($merchant);

        $eInvoiceEntity->build($input);

        $this->repo->saveOrFail($eInvoiceEntity);

        return $eInvoiceEntity;
    }

    public function updateQrCodeUrlForMerchantEinvoice($input)
    {
        if($input['data'] == null)
        {
            throw new BadRequestException(
                ErrorCode::INVALID_ACTION);
        }

        $eInvoiceLineItems = $input['data'];

        foreach ($eInvoiceLineItems as $lineItem)
        {
            $invoiceEntity = new Entity();

            $qrCodeUrl = $lineItem['gsp_qr_code_url'];

            $eInvoicePdfUrl = $lineItem['gsp_e_invoice_pdf'];

            $gspIrn = $lineItem['gsp_irn'];

            try {
                $invoiceEntity = $this->repo->merchant_e_invoice->fetchByGspIrn($gspIrn);

                $this->trace->info(
                    TraceCode::EINVOICE_DETAILS,
                    [
                        'gsp_irn' => $gspIrn,
                    ]);

                $invoiceEntity->setGspQRCodeUrl($qrCodeUrl);

                $invoiceEntity->setGspEInvoicePdf($eInvoicePdfUrl);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::EINVOICE_NOT_FOUND,
                    [
                        'qr_code_url' => $qrCodeUrl,
                        'gsp_irn' => $gspIrn,
                    ]);
            }

            $this->repo->saveOrFail($invoiceEntity);
        }
    }

    public function generateEInvoice(Entity $eInvoiceEntity, $sellerEntity = Constants::RSPL)
    {
        $input = null;
        $response = null;
        $failure = false;

        $attempts = $eInvoiceEntity->getAttempts() + 1;
        $eInvoiceEntity->setAttempts($attempts);

        try
        {
            $input = $this->getEInvoiceRequestData($eInvoiceEntity, $sellerEntity);

            $eInvoiceEntity->setStatus(Status::STATUS_INITIATED);

            $response =  app('einvoice_client')->getEInvoice($this->mode, $input);

            $this->updateEInvoiceFromResponse($eInvoiceEntity, $response);
        }
        catch (\Throwable $e)
        {
            $request = $input;
            unset($request[Constants::ACCESS_TOKEN]);
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EINVOICE_REQUEST_EXCEPTION,
                [
                    'request'   => $request,
                    'response'  => $response,
                ]);

            $failure = true;

            $eInvoiceEntity->setRzpError($e->getMessage());
            $eInvoiceEntity->setStatus(Status::STATUS_FAILED);

            $this->repo->saveOrFail($eInvoiceEntity);
        }

        return [$response, $failure];
    }

    protected function updateEInvoiceFromResponse(Entity $eInvoiceEntity, array $response)
    {
        $result = $response[self::BODY][self::RESULTS];
        $resultStatus = $result[self::RESULT_STATUS];

        if($resultStatus === self::SUCCESS_STATUS)
        {
            $message = $result[self::MESSAGE];

            $eInvoiceEntity->setGspStatus($message[self::GSP_STATUS]);
            $eInvoiceEntity->setGspIrn($message[self::IRN]);
            $eInvoiceEntity->setGspSignedInvoice($message[self::SIGNED_INVOICE]);
            $eInvoiceEntity->setGspSignedQrCode($message[self::SIGNED_QR_CODE]);
            $eInvoiceEntity->setGspQRCodeUrl($message[self::QR_CODE_URL]);
            $eInvoiceEntity->setGspEInvoicePdf($message[self::E_INVOICE_PDF_URL]);

            $errorMessage = isset($result[self::ERROR_MESSAGE]) ? $result[self::ERROR_MESSAGE] : null;
            $eInvoiceEntity->setGspError($errorMessage);

            $eInvoiceEntity->setStatus(Status::STATUS_GENERATED);
        }
        else
        {
            $eInvoiceEntity->setGspError($result[self::ERROR_MESSAGE]);
            $eInvoiceEntity->setStatus(Status::STATUS_FAILED);
        }

        $this->repo->saveOrFail($eInvoiceEntity);
    }

    public function getEInvoiceRequestData(Entity $eInvoiceEntity, $sellerEntity = Constants::RSPL)
    {
        [$itemList, $valueDetails] = $this->getItemList($eInvoiceEntity);

        $data = [
            Constants::ACCESS_TOKEN         => $this->getAccessToken($sellerEntity),
            Constants::USER_GSTIN           => $this->getUserGstin($sellerEntity),
            Constants::TRANSACTION_DETAILS  => [
                Constants::SUPPLY_TYPE => Constants::B2B,
            ],
            Constants::DOCUMENT_DETAILS => $this->getDocumentDetails($eInvoiceEntity),
            Constants::SELLER_DETAILS   => $this->getSellerDetails($sellerEntity),
            Constants::BUYER_DETAILS    => $this->getBuyerDetails($eInvoiceEntity),
            Constants::VALUE_DETAILS    => $valueDetails,
            Constants::ITEM_LIST        => $itemList,
        ];

        if($eInvoiceEntity->getType() === Types::BANKING && $eInvoiceEntity->getDocumentType() === DocumentTypes::CRN)
        {
            $data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_NUMBER] =
                $this->repo->merchant_invoice->getInvoiceNumber($eInvoiceEntity->getMerchantId(),
                    $eInvoiceEntity->getMonth(), $eInvoiceEntity->getYear(),
                    Merchant\Invoice\Type::RX_TRANSACTIONS)->getInvoiceNumber();

            $data[Constants::REFERENCE_DETAILS] = $this->getReferenceDetails($eInvoiceEntity);
        }
        return $data;
    }

    protected function getAccessToken($sellerEntity)
    {
        $config = $this->app['config']->get('applications.einvoice.access_token');

        return $config[$sellerEntity][$this->mode]['static_access_token'];
    }

    protected function getUserGstin($sellerEntity)
    {
        return Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::GSTIN];
    }

    protected function getDocumentDetails(Entity $eInvoiceEntity)
    {
        // Adding this to support the retry cases
        // to pass the last day for the merchant invoice creation
        // here we need to avoid the scenario of 12 AM midnight
        // to set the date to last day of previous month
        $documentDate = Carbon::createFromDate($eInvoiceEntity->getYear(), $eInvoiceEntity->getMonth(), 1, Timezone::IST)
                        ->endOfMonth()
                        ->format('d/m/Y');

        return [
            Constants::DOCUMENT_TYPE    => $eInvoiceEntity->getDocumentType(),
            Constants::DOCUMENT_NUMBER  => $eInvoiceEntity->getInvoiceNumber(),
            Constants::DOCUMENT_DATE    => $documentDate,
        ];
    }

    protected function getSellerDetails($sellerEntity)
    {
        [$address1, $address2] = $this->getFormattedAddress(
            Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::ADDRESS_1]);

        $data = [
            Constants::GSTIN        => Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::GSTIN],
            Constants::LEGAL_NAME   => Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::LEGAL_NAME],
            Constants::LOCATION     => Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::LOCATION],
            Constants::PINCODE      => Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::PINCODE],
            Constants::STATE_CODE   => Constants::SELLER_ENTITY_DETAILS[$sellerEntity][Constants::STATE_CODE],
            Constants::ADDRESS_1    => $address1,
        ];

        if(empty($address2) === false)
        {
            $data[] = [
                Constants::ADDRESS_2 => $address2,
            ];
        }

        return $data;
    }

    protected function getBuyerDetails(Entity $eInvoiceEntity)
    {
        $merchant = $eInvoiceEntity->merchant;

        $merchantDetails = $merchant->merchantDetail;

        $stateCode = $merchantDetails->getBusinessStateCode();

        [$address1, $address2] = $this->getFormattedAddress($merchantDetails->getBusinessRegisteredAddress());

        $buyerDetails = [
            Constants::GSTIN => $eInvoiceEntity->getGstin(),
            Constants::LEGAL_NAME => $merchantDetails->getBusinessName(),
            Constants::LOCATION => $merchantDetails->getBusinessRegisteredCity(),
            Constants::PINCODE  => (int) $merchantDetails->getBusinessRegisteredPin(),
            Constants::PLACE_OF_SUPPLY => $stateCode,
            Constants::STATE_CODE => $stateCode,
            Constants::ADDRESS_1 => $address1,
        ];

        if((empty($address2) === false) and (strlen($address2) >= 3))
        {
            $buyerDetails[Constants::ADDRESS_2] = $address2;
        }

        return $buyerDetails;
    }

    protected function getFormattedAddress(string $addressString)
    {
        $address = mb_str_split($addressString, 100);
        $address1 = $address[0];
        $address2 = null;

        if (sizeof($address) > 1)
        {
            $address2 = $address[1];
        }

        return [$address1, $address2];
    }

    public function getItemList(Entity $eInvoiceEntity)
    {
        return [null, null];
    }

    public function isInvalidDataError(string $errorString) : bool
    {
        $isInvalidDataError = false;

        foreach (StatusCodes::$invalidDataErrorCodes as $errorCode)
        {
            if(strpos($errorString, $errorCode . self::ERROR_DELIMITER) !== false)
            {
                $isInvalidDataError = true;

                break;
            }
        }

        return $isInvalidDataError;
    }

    public function isRetryableError(string $errorString) : bool
    {
        $isRetryableError = false;

        foreach (StatusCodes::$retryableErrorCodes as $errorCode)
        {
            if(strpos($errorString, $errorCode . self::ERROR_DELIMITER) !== false)
            {
                $isRetryableError = true;

                break;
            }
        }

        return $isRetryableError;
    }

    public function shouldGenerateB2C(string $errorMessage) : bool
    {
        return false;
    }

    public function shouldRetry($response) : bool
    {
        $result = $response[self::BODY][self::RESULTS];
        $resultStatus = $result[self::RESULT_STATUS];

        if($resultStatus === self::SUCCESS_STATUS)
        {
            return false;
        }

        $errorMessage = $result[self::ERROR_MESSAGE];

        return ($this->isRetryableError($errorMessage) === true);
    }

    public function shouldGenerateEInvoice(Merchant\Entity $merchant, $fromTimestamp) : bool
    {
        $merchantDetails = $merchant->merchantDetail;

        $gstin = $merchantDetails->getGstin();
        $pinCode = $merchantDetails->getBusinessRegisteredPin();

        $feeBearer = $merchant->getFeeBearer();

        if((empty($gstin) === true) or (empty($pinCode) === true) or ($feeBearer === Merchant\FeeBearer::CUSTOMER))
        {
            return false;
        }

        return ($fromTimestamp >= self::EINVOICE_START_TIMESTAMP);
    }

    public function shouldGenerateRevisedInvoice($merchantId, $month, $year) : bool
    {
        $eligibleMerchantId = $this->repo->merchant_e_invoice->checkIfMerchantIsEligibleForRevisedInvoice($month, $year, Types::PG, $merchantId);

        if(in_array($merchantId, $eligibleMerchantId) === true)
        {
            return true;
        }

        return false;
    }

    public function isEinvoiceSuccess(string $merchantId, int $month, int $year, string $type) : bool
    {
        $generatedCount = 0;

        [$count, $entityMap] = $this->getEInvoiceData($merchantId, $month, $year, $type);

        foreach ($entityMap as $documentType => $entity)
        {
            if ($entity->getStatus() === Status::STATUS_GENERATED)
            {
                $generatedCount++;
            }
        }

        if($count === 0)
        {
            return false;
        }

        return ($generatedCount === $count);
    }

    public function getEInvoiceData(string $merchantId, int $month, int $year, string $type, string $documentType = null)
    {
        $entities = $this->repo->merchant_e_invoice->fetchEInvoicesFromMonthAndType($merchantId, $month, $year,
            $type, $documentType);

        $count = $entities->count();
        $entityMap = [];

        foreach ($entities as $entity)
        {
            $entityMap[$entity->getDocumentType()] = $entity;
        }

        return [$count, $entityMap];
    }

    public function updateGstinForEinvoice(string $merchantId, string $invoiceNumber, string $currentGstin)
    {
        $entities = $this->repo->merchant_e_invoice->fetchByInvoiceNumber($merchantId, $invoiceNumber);

        $this->repo->transaction(function() use ($entities, $currentGstin)
        {
            foreach ($entities as $entity)
            {
                $entity->setGstin($currentGstin);

                $this->repo->saveOrFail($entity);
            }
        });
    }

    public function getInvoiceNumber(string $merchantId, int $month, int $year, string $type)
    {
        return $this->repo->merchant_e_invoice->getInvoiceNumber($merchantId, $month, $year, $type);
    }

    public function getLatestGeneratedEInvoiceData(string $merchantId, int $month, int $year, string $type, string $documentType)
    {
        return $this->repo->merchant_e_invoice->fetchLatestGeneratedEInvoiceFromMonthAndType($merchantId, $month, $year,
            $type, $documentType);
    }

    public function getReferenceDetails(Entity $eInvoiceEntity)
    {
        $invoiceEntity = $this->repo->merchant_e_invoice->fetchByInvoiceNumberAndDocumentType($eInvoiceEntity->getMerchantId(),
            $eInvoiceEntity->getInvoiceNumber(), DocumentTypes::INV);

        $referenceInvoiceDate = Carbon::createFromTimestamp($invoiceEntity->getCreatedAt(), Timezone::IST)
            ->format('d/m/Y');

        $dateArray = explode('/', $referenceInvoiceDate);

        $month = (int)$dateArray[1];
        $year  = (int)$dateArray[2];

        if(($month >= 9 and $year >= 2021) or $year >= 2022)
        {
            $referenceInvoiceDate = Carbon::createFromTimestamp($invoiceEntity->getCreatedAt(), Timezone::IST)
                ->subDay()
                ->format('d/m/Y');
        }

        $invoiceDate = Carbon::createFromTimestamp($eInvoiceEntity->getCreatedAt(), Timezone::IST)
            ->subDay()
            ->format('d/m/Y');
        $precedingDocumentDetails = [
            Constants::REFERENCE_OF_ORIGINAL_INVOICE     => $eInvoiceEntity->getInvoiceNumber(),
            Constants::PRECEDING_INVOICE_DATE            => $referenceInvoiceDate,
        ];

        return [
            Constants::INVOICE_REMARKS              =>   "Reference of Credit Note",
            Constants::INVOICE_PERIOD_START_DATE    =>   $invoiceDate,
            Constants::INVOICE_PERIOD_END_DATE      =>   $invoiceDate,
            Constants::PRECEDING_DOCUMENT_DETAILS   =>   $precedingDocumentDetails,
        ];
    }
}

<?php

namespace RZP\Models\Invoice;

use File;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Country;
use RZP\Constants\Entity as E;
use RZP\Jobs\PaymentEInvoice;
use RZP\Models\Batch;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Plan\Subscription;
use RZP\Trace\TraceCode;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class DccEInvoiceCore extends Core
{
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Subscription\Entity $subscription = null,
        Batch\Entity $batch = null,
        Base\Entity $externalEntity = null,
        string $batchId = null,
        Order\Entity $order = null): Entity
    {
        $eInvoiceEntity = new Entity;

        $eInvoiceEntity->merchant()->associate($merchant);

        $eInvoiceEntity->entity()->associate($externalEntity);

        $eInvoiceEntity->build($input);

        $eInvoiceEntity->setStatus(Status::CREATED);

        $this->repo->saveOrFail($eInvoiceEntity);

        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_CREATE,[
            'data' =>  $eInvoiceEntity,
        ]);

        return $eInvoiceEntity;
    }

    // creates new entry in invoice table
    public function createNewDCCEInvoice($payment, $baseEntity, $referenceType)
    {
        $invoiceAmount = $this->calculateInvoiceAmount($payment, $baseEntity, $referenceType);

        $input = [
            Entity::AMOUNT => $invoiceAmount,
            Entity::TYPE => Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType],
            Entity::REF_NUM => $baseEntity->getId(),
        ];

        return $this->create($input, $payment->merchant, null, null, $payment);
    }

    // fetch invoice entity from master DB to perform writes
    public function getEntityFromMaster($invoiceId)
    {
        return $this->repo->invoice->findOrFail($invoiceId);
    }

    // prepares data to send
    public function getEInvoiceRequestData($paymentEInvoice, $payment, $baseEntity)
    {
        $customerDetails = $this->getCustomerDetails($payment);
        if (empty($customerDetails)) return;
        $invoiceAmount = $this->getAmountInRupees($paymentEInvoice->getAmount());

        return [
            Constants::ACCESS_TOKEN => $this->getAccessToken(),
            Constants::USER_GSTIN => Constants::SELLER_ENTITY_DETAILS[Constants::GSTIN],
            Constants::TRANSACTION_DETAILS => [
                Constants::SUPPLY_TYPE => Constants::EXPWOP,
            ],
            Constants::DOCUMENT_DETAILS => [
                Constants::DOCUMENT_TYPE => Constants::TYPE_TO_DOC_TYPE_MAP[$paymentEInvoice->getType()],
                Constants::DOCUMENT_NUMBER => $paymentEInvoice->getRefNum(),
                Constants::DOCUMENT_DATE => Date('d/m/Y', $baseEntity->getCreatedAt()),
            ],
            Constants::SELLER_DETAILS => Constants::SELLER_ENTITY_DETAILS,
            Constants::BUYER_DETAILS => [
                Constants::GSTIN => Constants::UNREGISTERED_PERSON,
                Constants::LEGAL_NAME => $customerDetails[Constants::LEGAL_NAME],
                Constants::ADDRESS_1 => $customerDetails[Constants::ADDRESS_1],
                Constants::LOCATION => $customerDetails[Constants::LOCATION],
                Constants::PLACE_OF_SUPPLY => Constants::OUT_OF_INDIA,
            ],
            Constants::VALUE_DETAILS => [
                Constants::TOTAL_ASSESSABLE_VALUE => $invoiceAmount,
                Constants::TOTAL_INVOICE_VALUE => $invoiceAmount,
            ],
            Constants::ITEM_LIST => [
                [
                    Constants::ITEM_SERIAL_NUMBER => Constants::SERIAL_NUMBER,
                    Constants::PRODUCT_DESCRIPTION => Constants::DESCRIPTION,
                    Constants::IS_SERVICE => Constants::SERVICE,
                    Constants::HSN_CODE => Constants::ITEM_CODE,
                    Constants::UNIT_PRICE => $invoiceAmount,
                    Constants::TOTAL_AMOUNT => $invoiceAmount,
                    Constants::ASSESSABLE_VALUE => $invoiceAmount,
                    Constants::TOTAL_ITEM_VALUE => $invoiceAmount,
                ],
            ],
        ];
    }

    // makes call to Masters India to register invoice on GST portal
    public function registerDCCInvoice($requestData, $mode)
    {
        $response =  app('einvoice_client')->getEInvoice($mode, $requestData);
        if (isset($response) and
            isset($response['body']) and
            isset($response['body']['results']) and
            isset($response['body']['results']['message']))
        {
            // success response
            return $response['body']['results']['message'];
        }
        return $response;
    }

    // creates invoice PDF
    public function generateInvoicePDF($payment, $requestData, $response)
    {
        unset($requestData[Constants::ACCESS_TOKEN]);
        $requestData[Constants::IRN] = $response[Constants::IRN];
        $requestData[Constants::QR_CODE_URL] = $response[Constants::QR_CODE_URL];

        // this check is required to mock the PDF generation from testcases
        if (isset($this->app['invoice.dccEInvoice']) === true)
        {
            // testcase flow
            return $this->app['invoice.dccEInvoice']->generateDccInvoice($payment, $requestData);
        }
        // actual flow
        return (new DccEInvoicePdfGenerator)->generateDccInvoice($payment, $requestData);
    }

    // uploads invoice pdf to S3 using UFH service
    public function uploadInvoiceToUFH($filePath, $baseEntity, $entity, $referenceType = Constants::PAYMENT_FLOW)
    {
        $ufhService = $this->app['ufh.service'];
        $uploadedFileInstance = $this->getUploadedFileInstance($filePath);

        // ufh file type will be either dcc_inv_file or dcc_crn_file
        $fileType = Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType]  . '_file';

        $dateArray = explode('/', Date('d/m/Y',$baseEntity->getCreatedAt()));
        $ufhFilePath = join('/', [Constants::DCC_E_INVOICE, $dateArray[2], $dateArray[1], File::name($filePath)]);

        return $ufhService->uploadFileAndGetResponse($uploadedFileInstance, $ufhFilePath, $fileType, $entity);
    }

    // deletes local invoice file
    public function deleteLocalFile($filePath)
    {
        try
        {
            if (file_exists($filePath) === true)
            {
                $success = unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use

                if ($success === false)
                {
                    $this->trace->error(TraceCode::PAYMENT_E_INVOICE_FILE_DELETE_FAILED, ['file_path' => $filePath]);
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_E_INVOICE_FILE_DELETE_FAILED,[
                    'file_path' => $filePath,
                ]
            );
        }
    }

    // updates status and error of invoice entity
    public function updateStatusAndError($eInvoice, $status, $error='')
    {
        $eInvoice->setStatus($status);
        $eInvoice->setComment($error);

        $this->repo->saveOrFail($eInvoice);
    }

    // updates notes field of invoice entity
    public function updateEInvoiceEntity($eInvoice, $response)
    {
        $eInvoice->setNotes([
            Constants::IRN => $response[Constants::IRN],
            Constants::QR_CODE_URL => $response[Constants::QR_CODE_URL],
        ]);

        $this->repo->saveOrFail($eInvoice);
    }

    // pushes to queue for generating e-invoices (manual cron flow)
    public function processPayload($payload)
    {
        // check if the type is valid
        if (key_exists($payload[Constants::REFERENCE_TYPE], Constants::REFERENCE_TYPE_TO_TYPE_MAP) === false)
        {
            $this->trace->info(TraceCode::DCC_PAYMENT_E_INVOICE_CRON_FAILED, [
                'message' => 'provided request flow type is invalid',
                'payload' => $payload,
            ]);
            return;
        }
        // convert to list if single id is provided
        if (gettype($payload[Constants::REFERENCE_ID]) === 'string')
        {
            $payload[Constants::REFERENCE_ID] = [$payload[Constants::REFERENCE_ID]];
        }
        // push to the queue for processing
        foreach ($payload[Constants::REFERENCE_ID] as $rid)
        {
            $this->dispatchForInvoice($rid, $payload[Constants::REFERENCE_TYPE]);
        }
    }

    // pushes to queue for generating e-invoices (default cron flow)
    public function processFailedInvoices()
    {
        foreach (Constants::REFERENCE_TYPE_TO_TYPE_MAP as $referenceType => $type)
        {
            // fetch yesterday's failed invoices
            $paymentEInvoices = $this->repo->invoice->fetchYesterdaysFailedInvoicesByType($type);

            // push to the queue for processing
            foreach ($paymentEInvoices as $eInvoice)
            {
                $this->dispatchForInvoice($eInvoice->getRefNum(), $referenceType);
            }
        }
    }

    public function dispatchForInvoice($referenceId, $referenceType)
    {
        $data = [
            Constants::REFERENCE_ID => $referenceId,
            Constants::REFERENCE_TYPE => $referenceType,
            Constants::MODE => $this->mode,
        ];
        PaymentEInvoice::dispatch($data)->delay(rand(60,1000) % 601);

        $this->trace->info(TraceCode::DCC_PAYMENT_E_INVOICE_MESSAGE_DISPATCHED, [
            'data' => $data,
        ]);
    }

    // fetches access token
    protected function getAccessToken()
    {
        $config = $this->app['config']->get('applications.einvoice.access_token');

        return $config[Constants::RSPL][$this->mode]['static_access_token'];
    }

    // fetches customer details (name, address and location)
    protected function getCustomerDetails($payment)
    {
        // first check the address entity and then card entity
        $customerDetails = [];

        $address = $payment->fetchBillingAddress();
        $card = $payment->card;

        if (isset($address))
        {
            // fetch name, address1 and location from address entity
            $customerName = $address->getName();
            if (!empty($customerName))
            {
                $customerDetails[Constants::LEGAL_NAME] = $customerName;
            }

            $customerAddress = !empty($address->getLine1()) ? $address->getLine1() : $address->getLine2();
            if (!empty($customerAddress))
            {
                $customerDetails[Constants::ADDRESS_1] = $customerAddress;
            }

            $customerCountryName = $address->getCountryName();
            if (!empty($customerCountryName))
            {
                $customerDetails[Constants::LOCATION] = $customerCountryName;
                if(empty($customerDetails[Constants::ADDRESS_1]))
                {
                    $customerDetails[Constants::ADDRESS_1] = $customerCountryName;
                }
            }
        }
        if(isset($card))
        {
            // fetch name, address1 and location from card entity
            if (empty($customerDetails[Constants::LEGAL_NAME]))
            {
                $customerDetails[Constants::LEGAL_NAME] = $card->getName();
            }
            if (empty($customerDetails[Constants::ADDRESS_1]))
            {
                $countryCode = $card->getCountry();
                $customerCountryName = !empty($countryCode) ? Country::getCountryNameByCode(strtolower($countryCode)) : Country::getCountryNameByCode(Country::US);

                $customerDetails[Constants::ADDRESS_1] = $customerCountryName;
                $customerDetails[Constants::LOCATION] = $customerCountryName;
            }
        }
        // if any of these are not set then throw error as these are mandatory fields (ideally this should never happen)
        if (empty($customerDetails[Constants::LEGAL_NAME]) or
            empty($customerDetails[Constants::ADDRESS_1]) or
            empty($customerDetails[Constants::LOCATION]))
        {
            $this->trace->info(TraceCode::PAYMENT_E_INVOICE_MISSING_CUSTOMER_DETAILS,[
                'message'  => 'Unable to fetch Buyer details',
                'customerDetails' => $customerDetails,
            ]);
            return;
        }
        return $customerDetails;
    }

    // converts amount to rupees
    protected function getAmountInRupees($amount)
    {
        return number_format((abs($amount) /100), '2', '.', '');
    }

    // calculates invoice amount
    protected function calculateInvoiceAmount($payment, $baseEntity, $referenceType)
    {
        $paymentMeta = $payment->paymentMeta;
        $paymentAmount = $payment->getAmount();

        if ($referenceType === Constants::PAYMENT_FLOW)
        {
            $dccMarkUpPercent = $paymentMeta->getDccMarkUpPercent();
            $invoiceAmount = ($paymentAmount * $dccMarkUpPercent) / 100;
            // In case of MCC payment, multiply MCC forex rates with the amount
            if ($payment->getCurrency() !== Currency::INR)
            {
                $forexRate = $paymentMeta->getMccForexRate();
                $invoiceAmount *= !empty($forexRate) ? $forexRate : 1;
            }
        }
        else
        {
            // fetch DCC revenue from tax invoice
            $paymentEInvoice = $this->repo->invoice
                ->fetchInvoicesByEntityAndType($payment->getId(), E::PAYMENT, Type::DCC_INV)->first();
            $dccRevenue = $paymentEInvoice->getAmount();

            $refundAmount = $baseEntity->getAmount();
            $invoiceAmount = ($refundAmount / $paymentAmount) * $dccRevenue;
        }
        return round($invoiceAmount);
    }

    // creates file upload object
    protected function getUploadedFileInstance(string $path)
    {
        $name = File::name($path);
        $extension = File::extension($path);
        $originalName = $name . '.' . $extension;
        $mimeType = File::mimeType($path);
        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.
        $test = true;

        return new UploadedFile($path, $originalName, $mimeType, $error, $test);
    }
}

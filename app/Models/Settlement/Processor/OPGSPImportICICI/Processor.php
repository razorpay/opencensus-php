<?php

namespace RZP\Models\Settlement\Processor\OPGSPImportICICI;

use Carbon\Carbon;
use RZP\Constants\Country;
use RZP\Constants\Environment;
use RZP\Constants\Timezone;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use RZP\Exception\RecoverableException;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Merchant\HsCode\HsCodeList;
use RZP\Models\Merchant\InternationalIntegration\Service as MIIService;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Service as InvoiceService;
use RZP\Models\Transaction\Entity as TEntity;
use RZP\Models\Transaction\Type;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service;
use RZP\Services\UfhService;
use RZP\Trace\TraceCode;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\FileStore;
use RZP\Models\Merchant;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\GenericDocument;
use RZP\Mail\Base\Constants as MailConstants;


class Processor extends Base\Core
{

    protected $jobNameProd = BeamConstants::ICICI_OPGSP_PROD_JOB_NAME;

    protected $invoicesJobNameProd = BeamConstants::ICICI_OPGSP_INVOICES_PROD_JOB_NAME;

    protected $storageFileName;

    protected $chotaBeam = false;

    protected $mailAddress = MailConstants::MAIL_ADDRESSES[MailConstants::CROSS_BORDER_TECH];


    public function generateSettlementFileForICICIOpgspImport($input)
    {
        try {

            $merchantId = $input['merchant_id'];
            $sendFile = $input['send_file'];
            $from = $input['from'] ?? Carbon::yesterday(Timezone::IST)->getTimestamp();
            $to = $input['to'] ?? Carbon::today(Timezone::IST)->getTimestamp();
            $currentDate = Carbon::now(Timezone::IST)->isoFormat('DD-MM-YYYY');

            $settlements = $this->repo->settlement
                ->getProcessedSettlementsForTimePeriodForMid($merchantId, $from, $to, null);

            $merchantAccount = (new Merchant\Service())->getBankAccount($merchantId,[\RZP\Models\BankAccount\Type::ORG_SETTLEMENT]);

            $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

            $countryCode = $merchantDetail->getBusinessRegisteredCountry();

            $country = Country::getCountryNameByCode($countryCode);
            $currency = Currency::getCurrencyForCountry($merchantAccount['beneficiary_country']);
            $bankAccountCountry = Country::getCountryNameByCode($merchantAccount['beneficiary_country']);

            $hscode = (new MIIService())->getMerchantHsCode($merchantId);

            if(isset($hscode) === false)
            {
                $this->trace->info(TraceCode::INVALID_HS_CODE_FOR_MERCHANT, [
                    'input'           => $input,
                    'hscode'          => $hscode,
                ]);
                return;
            }

            $isGoodsMerchant = HsCodeList::isGoodsMerchant($hscode['hs_code']);
            $hsCodeDescription = HsCodeList::getHsCodeDescDescription($hscode['hs_code']);

            $fileCount = 1;
            foreach ($settlements as $settlement)
            {

                $consolidatedData = $this->getConsolidatedFileData($settlement, $currency, $country, $merchantAccount,
                    $merchantDetail, $bankAccountCountry,$isGoodsMerchant);

                $transactionCount = $this->repo->transaction
                    ->getCountBySettlementIdAndTypes($settlement->getId(), [Type::PAYMENT, Type::REFUND]);

                // get number of batches based on the batch size
                $numberOfBatches = intdiv($transactionCount, Constants::FILE_BATCH_SIZE) + 1;

                for ($batch = 0; $batch < $numberOfBatches; $batch++)
                {
                    $offset = $batch * Constants::FILE_BATCH_SIZE;

                    $transactions = $this->repo->transaction
                        ->getBySettlementIdAndTypesWithOffset($settlement->getId(), [Type::PAYMENT, Type::REFUND],
                            $offset, Constants::FILE_BATCH_SIZE);

                    // Add chargebacks in the last file
                    if($numberOfBatches - $batch === 1)
                    {
                        $adjustments = $this->repo->transaction
                            ->getDisputesBySettlementId($settlement->getId(), null);

                        if ($adjustments !== null) {
                            $transactions = $transactions->merge(TEntity::hydrate($adjustments->toArray()));
                        }
                    }

                    [$paymentIds, $refundIds, $adjustmentIds] = $this->getPaymentAndRefundIdsFromTransactions($transactions);

                    $refunds = [];
                    $payments = [];
                    $buyerAddresses = [];
                    $disputes = [];
                    if(!empty($refundIds))
                    {
                        $refunds = $this->repo->refund->fetchRefundByRefundIds($refundIds);
                    }

                    [$refundPaymentIds, $refundIdPaymentIdMap] = $this->getPaymentIdsForRefund($refunds);

                    $paymentIds = array_merge($paymentIds,$refundPaymentIds);

                    if(!empty($paymentIds))
                    {
                        $payments = $this->repo->payment->fetchPaymentsGivenIds($paymentIds,Constants::FILE_BATCH_SIZE);

                        $buyerAddresses = $this->repo->address->fetchByEntityIds($paymentIds, 'payment');
                    }

                    $paymentIdMap = $this->getIdMapFromArray($payments);

                    $addressMap = $this->getAddressMapFromArray($buyerAddresses);

                    if(!empty($adjustmentIds))
                    {
                        $disputes = $this->repo->dispute->getDisputesForAdjustmentIds($merchantId, $adjustmentIds);
                    }

                    $adjustmentIdDisputeMap = $this->getAdjustmentIdDisputeMapFromArray($disputes);

                    $transactionalData = $this->getTransactionalFileData($transactions, $currency, $country,
                        $merchantAccount, $bankAccountCountry, $merchantDetail,$isGoodsMerchant, $hscode,
                        $hsCodeDescription,$merchantId,$paymentIdMap,$refundIdPaymentIdMap, $addressMap, $adjustmentIdDisputeMap);

                    $fileName = 'Razorpay_settlement_'.$merchantId .'_' .$currentDate . '_'. $fileCount++;
                    $this->generateFile($consolidatedData, $transactionalData, $fileName,$sendFile);
                }
            }

        }
        catch (\Exception $e)
            {
                $this->trace->info(
                    TraceCode::OPGSP_IMPORT_FLOW_GENERATE_FILE_ERROR,
                    [
                        "error" => $e
                    ]);
                throw new RecoverableException($e->getMessage(), $e->getCode(), $e);
            }
    }

    private function getConsolidatedFileData($settlement, $currency, $country, $merchantAccount,
                                             $merchantDetail, $bankAccountCountry,$isGoodsMerchant)
    {

        $row = new ConsolidatedFileFormat();

        $settlementDate = Carbon::createFromTimestamp($settlement->getUpdatedAt())->isoFormat('DD-MM-YYYY');
        $row->Date = $settlementDate;
        $row->OPGSPTranRefNo = $settlement->getId();
        $amountInINR = number_format(($settlement->getAmount()/100),2);
        $row->INRAmount = $amountInINR;
        $row->CURRENCY = $currency;
        $row->BeneficiaryAccountNumber = $merchantAccount['account_number'];
        $row->BeneficiaryName = $merchantAccount['beneficiary_name'];
        $row->BeneficiaryAddress1 = $merchantDetail->getBusinessRegisteredAddress();
        $row->BeneficiaryCountry = $country;
        $row->BeneficiaryBankBICCode = $merchantAccount['ifsc_code'];
        $row->BeneficiaryBankName = $merchantAccount['notes']['bank_name'];
        $row->BeneficiaryBankAdd = $merchantAccount['beneficiary_address1'];
        $row->BeneficiaryBankCountry = $bankAccountCountry;
        $row->CommodityCode = $isGoodsMerchant? 'Goods':'Digital';
        $row->PurposeOfRemittance= $isGoodsMerchant? 'Goods':'Digital';

        $sheets = $row->getAssocArray();

        return $sheets;

    }

    private function getTransactionalFileData($transactions, $currency, $country, $merchantAccount, $bankAccountCountry,
                                              $merchantDetail,$isGoodsMerchant, $hscode, $hsCodeDescription,$merchantId,
                                              $paymentIdMap,$refundIdPaymentIdMap, $addressMap, $adjustmentIdDisputeMap)
    {
        $transactionalData = array();

        $transactionalData[] = $this->getHeaderRowForTransactionSheet();
        foreach ($transactions as $transaction)
        {
            $row = new TransactionalFileFormat();


            $row->Date = Carbon::createFromTimestamp($transaction->getCreatedAt())->isoFormat('DD-MM-YYYY');

            $netAmountValue = 0;

            switch ($transaction->getType())
            {
                case Type::PAYMENT:
                    $netAmountValue = $this->getCreditAmount($transaction);
                    $row->RequestedAction = Constants::REQUEST_ACTION_PAYMENT;
                    $row->OPGSPTransactionRefNo = $transaction->getEntityId();
                    $row->Mode = $paymentIdMap[$transaction->getEntityId()]['method'];
                    if(!empty($paymentIdMap[$transaction->getEntityId()]['notes']))
                    {
                        $row->InvoiceNumber = $row->AirwayBill = $paymentIdMap[$transaction->getEntityId()]['notes']['invoice_number'];
                    }
                    $address = $addressMap[$transaction->getEntityId()];
                    [$name,$consolidatedAddress] = $this->getBuyerAddressAndName($address);
                    $row->BuyerName = $name;
                    $row->BuyerAddress = $consolidatedAddress;
                    break;

                case Type::REFUND:
                    $netAmountValue = (-1) * $this->getDebitAmount($transaction);
                    $row->RequestedAction = Constants::REQUEST_ACTION_REFUND;
                    $paymentForRefund = $paymentIdMap[$refundIdPaymentIdMap[$transaction->getEntityId()]];
                    $row->Mode = $paymentForRefund['method'];
                    $row->OPGSPTransactionRefNo = $paymentForRefund['id'];
                    if(!empty($paymentForRefund['notes']))
                    {
                        $row->InvoiceNumber = $row->AirwayBill = $paymentForRefund['notes']['invoice_number'];
                    }
                    $address = $addressMap[$refundIdPaymentIdMap[$transaction->getEntityId()]];
                    [$name,$consolidatedAddress] = $this->getBuyerAddressAndName($address);
                    $row->BuyerName = $name;
                    $row->BuyerAddress = $consolidatedAddress;
                    break;

                case Type::ADJUSTMENT:
                    [$netAmountValue, $creditTypeValue] = $this->getAdjustmentDetails($transaction);
                    $netAmountValue = $creditTypeValue * $netAmountValue;
                    if($creditTypeValue === 1){
                        $row->RequestedAction = Constants::REQUEST_ACTION_CHARGEBACK_REVERSAL;
                    }else
                    {
                        $row->RequestedAction = Constants::REQUEST_ACTION_CHARGEBACK;
                    }
                    $dispute = $adjustmentIdDisputeMap[$transaction->getEntityId()];
                    $row->OPGSPTransactionRefNo = $dispute[DisputeEntity::PAYMENT_ID];

                    break;

                default:
                    // shouldn't have executed this
                    $this->trace->error(TraceCode::OPGSP_IMPORT_FILE_INCORRECT_TRANSACTION,
                        [
                            'transaction_id'    => $transaction->getId(),
                            'transaction_type'  => $transaction->getType(),
                        ]);
                    break;
            }

            $row->INRAmount = $netAmountValue;
            $row->CURRENCY = $currency;
            $row->BeneficiaryAccountNumber = $merchantAccount['account_number'];
            $row->BeneficiaryName = $merchantAccount['beneficiary_name'];
            $row->BeneficiaryAddress1 = $merchantDetail->getBusinessRegisteredAddress();
            $row->BeneficiaryCountry = $country;
            $row->BeneficiaryBankBICCode = $merchantAccount['ifsc_code'];
            $row->BeneficiaryBankName = $merchantAccount['notes']['bank_name'];
            $row->BeneficiaryBankAdd = $merchantAccount['beneficiary_address1'];
            $row->BeneficiaryBankCountry = $bankAccountCountry;
            $row->InvoiceDate = Carbon::createFromTimestamp($transaction->getCreatedAt())->isoFormat('DD-MM-YYYY');
            $row->CommodityCode = $isGoodsMerchant? 'Goods':'Digital';
            $row->HSCode = $hscode['hs_code'];
            $row->HSCodeDescription = $hsCodeDescription;
            $row->PurposeOfRemittance= $isGoodsMerchant? 'Goods':'Digital';
            $row->TransactionAmount = $netAmountValue;
            $row->RequestID = $transaction->getId();
            $row->MID = $merchantId;
            $tax = $transaction->getTax() ?? 0;
            $fee = $transaction->getFee() ?? 0;
            $row->ProcessingFee = ((float)($fee-$tax)) / 100;
            $row->GST = ((float)($tax)) / 100;

            $transactionalData[] =  $row->getAssocArray();
        }

        return $transactionalData;
    }

    private function generateFile($consolidatedData, $transactionalData, $fileName, $sendFile)
    {

        $sheets = [
            Constants::CONSOLIDATED_SHEET_FILE_NAME  => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    $consolidatedData
                ],
            ],
            Constants::TRANSACTIONAL_SHEET_FILE_NAME => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    $transactionalData
                ],
            ],
        ];

        $path  = storage_path('files/filestore').'/'. $fileName . '.xls';

        $excel = (new ExcelExport)->setSheets(function() use ($sheets) {
            $sheetsInfo = [];
            foreach ($sheets as $sheetName => $data)
            {
                $autoGenerateHeader = true;
                if($sheetName == Constants::TRANSACTIONAL_SHEET_FILE_NAME){
                    $autoGenerateHeader = false;
                }
                $sheetsInfo[$sheetName] = (new ExcelSheetExport($data['items']))->setTitle($sheetName)->setStartCell($data['config']['start_cell'])->generateAutoHeading($autoGenerateHeader);
            }

            return $sheetsInfo;
        })->store($path, 'local_storage');


        $file = new UploadedFile($path, $fileName.'.xls',null,null,true);

        $namespace = explode("\\", strtolower(get_class($this)));
        $subFolder = $namespace[count($namespace) - 2]; // Second last element represents the actual processor

        $this->storageFileName =  $subFolder . '/' . $fileName .'.xls';

        $response = (new UfhService($this->app))->uploadFileAndGetResponse($file, $this->storageFileName, FileStore\Type::ICICI_OPGSP_IMPORT_SETTLEMENT_FILE, null);

        $this->trace->info(TraceCode::OPGSP_IMPORT_FILE_SENT, [
            'ufhResponse'      => $response,
        ]);

        if($sendFile and count($response) !== 0)
        {
            $this->sendfile($response);
        }

    }

    protected function getCreditAmount($transaction)
    {
        return ((float)$transaction->getCredit()) / 100;
    }

    protected function getDebitAmount($transaction)
    {
        return ((float)$transaction->getDebit()) / 100;
    }

    /*
     * Returns amount with credit type;
     * Credit type = 1, if transaction type is credit
     * Credit type = -1, if transaction type is debit
     * Above value is multiplied with amount.
     */
    protected function getAdjustmentDetails($transaction)
    {
        $amount = 0;
        $creditType = 0;

        if ($transaction->isCredit() === true)
        {
            $amount = $this->getCreditAmount($transaction);
            $creditType = 1;
        }
        else if ($transaction->isDebit() === true)
        {
            $amount = $this->getDebitAmount($transaction);
            $creditType = -1;
        }

        return [$amount, $creditType];
    }

    private function getHeaderRowForTransactionSheet()
    {
        $row = new TransactionalFileFormat();

        $row->Date = 'Date';
        $row->OPGSPTransactionRefNo = 'OPGSP Transaction Ref No';
        $row->INRAmount = 'INR amount';
        $row->CURRENCY  = 'Currency in which remittance to be made';
        $row->IBANNumber = 'IBAN Number';
        $row->CNAPSCode = 'CNAPS Code';
        $row->POPSCode = 'POPS Code';
        $row->BeneficiaryBankCountry = 'Beneficiary Bank Country';
        $row->BeneficiaryBankAdd = 'Beneficiary Bank Address';
        $row->BeneficiaryAccountNumber = 'Beneficiary Account Number';
        $row->BeneficiaryName = 'Beneficiary Name';
        $row->BeneficiaryAddress1 = 'Beneficiary Address1';
        $row->BeneficiaryAddress2 = 'Beneficiary Address2';
        $row->BeneficiaryCountry = 'Beneficiary Country';
        $row->BeneficiaryBankBICCode = 'Beneficiary Bank BIC Code';
        $row->BeneficiaryBankName = 'Beneficiary Bank Name';
        $row->IntermediaryBankBICCode = 'Intermediary Bank BIC Code';
        $row->IntermediaryBankName = 'Intermediary Bank Name';
        $row->IntermediaryBankAddress = 'Intermediary Bank Address';
        $row->IntermediaryBankCountry = 'Intermediary Bank Country';
        $row->RemittanceInfo = 'Remittance Info';
        $row->InvoiceNumber = 'Invoice Number';
        $row->InvoiceDate = 'Invoice Date';
        $row->CommodityCode = 'Commodity Code';
        $row->CommodityDescription = 'Commodity Description';
        $row->Quantity = 'Quantity';
        $row->Rate = 'Rate';
        $row->HSCode = 'HS Code';
        $row->HSCodeDescription = 'HS Code Description';
        $row->BuyerName = 'Buyer Name';
        $row->BuyerAddress = 'Buyer Address';
        $row->PurposeOfRemittance = 'Purpose Of Remittance';
        $row->PaymentTerms = 'Payment Terms';
        $row->IECode = 'IE Code';
        $row->AirwayBill = 'Airway Bill';
        $row->TransactionAmount = 'Transaction Amount';
        $row->RequestedAction = 'Requested Action';
        $row->RequestID = 'Request ID';
        $row->ProductInfo = 'Product Info';
        $row->MID = 'MID';
        $row->ProcessingFee = 'Processing Fee';
        $row->GST = 'GST';
        $row->MerchantTransactionId = 'Merchant Transaction Id';
        $row->PAN = 'PAN';
        $row->DOB = 'DOB';
        $row->Mode = 'Mode';
        $row->PgLabel = 'Pg Label';
        $row->CardType = 'Card Type';
        $row->IssuingBank = 'Issuing Bank';
        $row->BankRefNumber = 'Bank Ref Number';

        return $row->getAssocArray();

    }

    protected function getPaymentAndRefundIdsFromTransactions($data)
    {
        $paymentIds = [];
        $refundIds = [];
        $adjustmentIds = [];

        foreach ($data as $transaction)
        {
            switch ($transaction->getType())
            {
                case Type::PAYMENT:
                    $paymentIds[] = $transaction->getEntityId();
                    break;

                case Type::REFUND:
                    $refundIds[] = $transaction->getEntityId();
                    break;

                case Type::ADJUSTMENT:
                    $adjustmentIds[] = $transaction->getEntityId();
                    break;
            }

        }

        return [$paymentIds, $refundIds, $adjustmentIds];
    }

    protected function getPaymentIdsForRefund($data)
    {
        $ids = [];
        $refundIdPaymentIdMap = array();

        foreach ($data as $datum)
        {
            array_push($ids, $datum->getPaymentId());
            $refundIdPaymentIdMap[$datum->getId()] = $datum->getPaymentId();
        }

        return [$ids, $refundIdPaymentIdMap];
    }

    protected function getIdMapFromArray($data)
    {
        $idMap = array();

        foreach ($data as $datum)
        {
            $idMap[$datum->getId()] = $datum;
        }

        return $idMap;
    }

    protected function getAddressMapFromArray($data)
    {
        $idMap = array();

        foreach ($data as $datum)
        {
            $idMap[$datum->getEntityId()] = $datum;
        }

        return $idMap;
    }

    protected function getAdjustmentIdDisputeMapFromArray($data)
    {
        $idMap = array();

        foreach ($data as $datum)
        {
            $idMap[$datum[DisputeEntity::DEDUCTION_SOURCE_ID]] = $datum;
        }

        return $idMap;
    }

    protected  function getBuyerAddressAndName($address)
    {

        $name = $address['name'];
        $consolidatedAddress = $address['line1'] . ', ' . $address['line2']
            . ', ' . $address['city']  . ', ' . $address['state']
            . ', ' . $address['country']  . ', ' . $address['zipcode'] ;

        return [$name,$consolidatedAddress];
    }

    public function sendfile($response = null)
    {
        $ufhResponse = [
            'file_id' => $response['id'],
            'success' => isset($response['id']),
            'status' => $response['status'],
            'bucket' => $response['bucket'],
            'region' => $response['region']
        ];

        if($this->app->environment() === Environment::PRODUCTION)
        {
            $this->pushFileToBeam($this->jobNameProd,FileStore\Type::ICICI_OPGSP_IMPORT_SETTLEMENT_FILE, $ufhResponse);
        }

        $this->trace->info(TraceCode::OPGSP_IMPORT_FILE_SENT, [
            'ufhResponse'      => $ufhResponse,
        ]);
    }

    public function pushFileToBeam(string $jobName, $filetype, $ufhResponse = null, $fileName = null)
    {
        try
        {
            if(isset($fileName))
            {
                $fileInfo = [$fileName];
            }else
            {
                $fileInfo = [$this->storageFileName];
            }

            $bucketConfig = $this->getBucketConfig();

            if(isset($ufhResponse) === true)
            {
                $bucketConfig['name']   = $ufhResponse['bucket'];
                $bucketConfig['region'] = $ufhResponse['region'];
            }

            $data =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => $jobName,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
                Service::CHOTABEAM_FLAG          => $this->chotaBeam,
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'settlements',
                'filetype'  => $filetype,
                'subject'   => 'File Send failure',
                'recipient' => $this->mailAddress,
            ];

            $this->app['beam']->beamPush($data, $timelines, $mailInfo);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::BEAM_PUSH_FAILED,
                [
                    'job_name'  => $jobName,
                    'file_name' => $fileInfo,
                    'error'     => $e,
                ]);
        }
    }

    public function sendInvoicesForICICIOpgspImport($input)
    {
        try{

            $merchantId = $input['merchant_id'];
            $from = $input['from'] ?? Carbon::yesterday(Timezone::IST)->getTimestamp();
            $to = $input['to'] ?? Carbon::today(Timezone::IST)->getTimestamp();

            $settlements = $this->repo->settlement
                ->getProcessedSettlementsForTimePeriodForMid($merchantId, $from, $to);

            foreach ($settlements as $settlement)
            {
                $transactionCount = $this->repo->transaction
                    ->getCountBySettlementIdAndTypes($settlement->getId(), [Type::PAYMENT]);

                // get number of batches for sending invoices
                $numberOfBatches = intdiv($transactionCount, Constants::INVOICE_BATCH_SIZE) + 1;

                for ($batch = 0; $batch < $numberOfBatches; $batch++) {
                    $offset = $batch * Constants::INVOICE_BATCH_SIZE;

                    $transactions = $this->repo->transaction
                        ->getBySettlementIdAndTypesWithOffset($settlement->getId(), [Type::PAYMENT],
                            $offset, Constants::INVOICE_BATCH_SIZE);

                    [$paymentIds, $refundIds, $adjustmentIds] = $this->getPaymentAndRefundIdsFromTransactions($transactions);

                    $paymentDocuments = (new InvoiceService())->findByPaymentIds($paymentIds, $merchantId);

                    $fileIds = [];
                    foreach ($paymentDocuments as $paymentDoc)
                    {
                        if(isset($paymentDoc[InvoiceEntity::REF_NUM]))
                        {
                            $fileIds[] = 'file_'.$paymentDoc[InvoiceEntity::REF_NUM];
                        } else
                        {
                            $this->trace->error(TraceCode::OPGSP_IMPORT_INVOICE_NOT_PRESENT,
                                [
                                    'payment_document'    => $paymentDoc,
                                ]);
                        }

                    }
                    $files = (new GenericDocument\Service)->fetchFiles($fileIds, $merchantId);
                    $this->sendInvoiceFiles($files, $merchantId);

                }
            }

        }catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::OPGSP_IMPORT_SEND_INVOICES_ERROR,
                [
                    "error" => $e
                ]);
            throw new RecoverableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function sendInvoiceFiles($files, $merchantId)
    {

        foreach ($files['items'] as $file)
        {
            $ufhResponse = [
                'file_id' => $file['id'],
                'success' => isset($file['id']),
                'status' => $file['status'],
                'bucket' => $file['bucket'],
                'region' => $file['region']
            ];

            if($this->app->environment() === Environment::PRODUCTION)
            {
                $this->pushFileToBeam($this->invoicesJobNameProd, $file['type'], $ufhResponse, $file['name']);
            }

            $this->trace->info(TraceCode::OPGSP_IMPORT_INVOICE_SENT, [
                'file_id' => $file['id'],
            ]);

        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(FileStore\Type::ICICI_OPGSP_IMPORT_SETTLEMENT_FILE, $this->env);

        return $config[$bucketType];
    }
}

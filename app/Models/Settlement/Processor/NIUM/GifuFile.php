<?php

namespace RZP\Models\Settlement\Processor\NIUM;

use Carbon\Carbon;
use Exception;
use RZP\Base\ConnectionType;
use RZP\Constants\Environment;
use RZP\Constants\Timezone;
use RZP\Exception\RecoverableException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\FileStore;
use RZP\Models\Settlement\Processor\Base;
use RZP\Models\Transaction\Entity as TEntity;
use RZP\Models\Transaction\Type;
use RZP\Trace\TraceCode;

class GifuFile extends Base\BaseGifuFile
{
    protected $fileToWriteName;

    protected $bankName = 'Nium';

    protected $totalAmount;

    protected $store = FileStore\Store::LOCAL;

    protected $mailAddress = Constants::MAIL_ADDRESSES[Constants::CROSS_BORDER_TECH];

    protected $jobNameStage = BeamConstants::NIUM_STAGE_JOB_NAME;

    protected $jobNameProd = BeamConstants::NIUM_PROD_JOB_NAME;

    protected $type = FileStore\Type::NIUM_SETTLEMENT_FILE;

    protected $connectionType = null; // @Todo, move this to data warehouse

    public function __construct()
    {
        parent::__construct();

        $this->transferMode = Base\TransferMode::SFTP;
        $this->fileToWriteName = "transactions_".Carbon::now(Timezone::IST)->isoFormat('YYYYMMDD')."T".Carbon::now(Timezone::IST)->isoFormat('HHmm');

    }

    protected function customFormattingForFile($path,$creator = null)
    {
        // Do nothing
    }

    /**
     * @throws RecoverableException
     */
    public function getGifuData($input, $from, $to): array
    {
        $data = [];

        foreach ($input as $mid)
        {
            $from = $from ?? Carbon::yesterday(Timezone::IST)->getTimestamp();
            $to = $to ?? Carbon::today(Timezone::IST)->getTimestamp();

            $merchantIntegrationInfo = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity($mid, 'nium');
            $settlements = $this->repo->settlement
                ->getProcessedSettlementsForTimePeriodForMid($mid, $from, $to, $this->connectionType);
            $settlementIds = $this->getSettlementIds($settlements);

            foreach ($settlementIds as $settlementId)
            {
                $transactions = $this->repo->transaction
                    ->getBySettlementIdAndTypes($settlementId, [Type::PAYMENT, Type::REFUND], $this->connectionType);
                $adjustments = $this->repo->transaction
                    ->getDisputesBySettlementId($settlementId, $this->connectionType);
                if($adjustments !== null)
                {
                    $transactions = $transactions->merge(TEntity::hydrate($adjustments->toArray()));
                }

                try {
                    foreach ($transactions as $transaction) {
                        $row = new FileFormat();
                        $row->ID = $transaction->getId();
                        $row->MerchantCountry = $merchantIntegrationInfo->getNotes()['country'];
                        $row->EMID = $merchantIntegrationInfo->getIntegrationKey();
                        $row->Amount = ((float)$transaction->getAmount()) / 100;
                        $row->BankPaidAmount = ((float)$transaction->getAmount()) / 100;
                        $row->Currency = $transaction->getCurrency(); // Should always be INR, but not hard-coding for now
                        $row->invoiceNumber = $transaction->getEntityId();
                        $row->PD = Carbon::now(Timezone::IST)->isoFormat('MM/DD/YYYY');
                        $row->DebitReferenceCode = $settlementId;
                        $tax = $transaction->getTax() ?? 0;
                        $fee = $transaction->getFee() ?? 0;
                        $row->DebitFeeTax = ((float)$tax) / 100;
                        $row->DebitFee = ((float)($fee-$tax)) / 100;
                        $row->PayforText = $merchantIntegrationInfo->getNotes()['payForText'];

                        $creditTypeValue = 0;
                        $netAmountValue = 0;
                        $chargebackValue = 0;

                        switch ($transaction->getType())
                        {
                            case Type::PAYMENT:
                                $netAmountValue = $this->getAmountInRupee($transaction->getCredit());
                                $creditTypeValue = 1;
                                break;

                            case Type::REFUND:
                                $netAmountValue = $this->getAmountInRupee($transaction->getDebit());
                                $creditTypeValue = 0;
                                break;

                            case Type::ADJUSTMENT:
                                [$netAmountValue, $creditTypeValue] = $this->getAdjustmentDetails($transaction);
                                $chargebackValue = 1;
                                break;

                            default:
                                // should not have come here
                                $this->trace->error(TraceCode::NIUM_GIFU_FILE_INCORRECT_TRANSACTION,
                                [
                                    'transaction_id'    => $transaction->getId(),
                                    'transaction_type'  => $transaction->getType(),
                                ]);
                                break;
                        }

                        $row->CreditType = $creditTypeValue;
                        $row->NetAmount = $netAmountValue;
                        $row->IsChargeback = $chargebackValue;

                        array_push($data, $row->getAssocArray());
                    }
                }
                catch (Exception $e)
                {
                    $this->trace->info(
                        TraceCode::ERROR_EXCEPTION,
                        [
                            "error" => $e
                        ]);
                    throw new RecoverableException($e->getMessage(), $e->getCode(), $e);
                }
            }
        }
        return $data;
    }

    protected function getSettlementIds($data)
    {
        $ids = [];

        foreach ($data as $datum)
        {
            array_push($ids, $datum->getId());
        }

        return $ids;
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($this->type, $this->env);

        return $config[$bucketType];
    }

    protected function getAmountInRupee($amount)
    {
        return ((float)$amount) / 100;
    }

    protected function getAdjustmentDetails($transaction)
    {
        $amount = 0;
        $creditType = 0;

        if ($transaction->isCredit() === true)
        {
            $amount = $this->getAmountInRupee($transaction->getCredit());
            $creditType = 1;
        }
        else if ($transaction->isDebit() === true)
        {
            $amount = $this->getAmountInRupee($transaction->getDebit());
            $creditType = 0;
        }

        return [$amount, $creditType];
    }
}

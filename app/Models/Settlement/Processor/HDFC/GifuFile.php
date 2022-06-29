<?php

namespace RZP\Models\Settlement\Processor\HDFC;

use Carbon\Carbon;
use Exception;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Mail\Base\Constants;
use RZP\Models\BankAccount\Type;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Merchant;
use RZP\Models\Settlement\Processor\Base;
use RZP\Models\FileStore;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Trace\TraceCode;

class GifuFile extends Base\BaseGifuFile
{
    protected $fileToWriteName;

    protected $bankName  = 'HDFCCollectNow';

    protected $store = FileStore\Store::S3;  // Adding this so that a file store entity gets created

    protected $totalAmount;

    protected $mailAddress = Constants::MAIL_ADDRESSES[Constants::DEVELOPERS];

    protected $jobNameStage = BeamConstants::HDFC_COLLECT_NOW_JOB_NAME;

    protected $jobNameProd = BeamConstants::HDFC_COLLECT_NOW_JOB_NAME;

    protected $type = FileStore\Type::HDFC_COLLECT_NOW_SETTLEMENT_FILE;

    public function __construct()
    {
        parent::__construct();

        $date = Carbon::now()->format('d-m-Y');

        $fileDate = Carbon::parse($date)->isoFormat("DD_MM");

        $this->fileToWriteName = 'GEFU' . '_' . $fileDate ;

        $this->transferMode = Base\TransferMode::SFTP;
    }

    protected function customFormattingForFile($path,FileStore\Creator $creator = null)
    {

        /*
         * Converts csv file
         * from
         * "this","is","a","test","file"
         * to
         * this,is,a,test,file
         * */

        $stringDataFromFile = file_get_contents($path,FILE_USE_INCLUDE_PATH);

        $stringDataForFile = str_replace('"', "", $stringDataFromFile);

        file_put_contents($path,$stringDataForFile);

        if(is_null($creator) === false)
        {
            $creator->localFilePath($path);

            $creator->save();
        }
    }


    /**
     * @throws \RZP\Exception\BadRequestException
     */
    public function getGifuData($input, $fromTimestamp = null, $toTimestamp = null): array
    {
        $data = [];

        $failedMids = [];

        $totalAmount = 0;

        $date = Carbon::now()->format('d-m-Y');

        $from = $fromTimestamp ?? Carbon::yesterday(Timezone::IST)->addHour(13)->getTimestamp(); // 1 pm

        $to = $toTimestamp ?? Carbon::now(Timezone::IST)->getTimestamp();

        $dataFetch = $this->repo->settlement->getSettlementsBetweenTimePeriodForMerchantIds($input,$from,$to);

        $modData = $this->groupSettlementsByMid($dataFetch);

        $this->trace->info(TraceCode::SETTLEMENT_FILE_MERCHANTS_TO_PROCESS,
            [
                'Mids with data' => array_keys($modData)
            ]
        );

        foreach ($modData as $mid=>$value)
        {
            try{
                $accountNumber = (new Merchant\Service())->getBankAccount($mid,[Type::ORG_SETTLEMENT])['account_number'];

                if(isset($accountNumber) === false)
                {
                    $failedMids[] = $mid;
                    continue;
                }

                $amount = $this->getAggregatedSettlementAmount($value);

                $narration = $this->getNarration($value,$mid);

                $brCode = $this->getBrCode($accountNumber);
            }
            catch (Exception $exception)
            {
                $failedMids[] = $mid;

                $this->trace->info(
                    TraceCode::SETTLEMENT_FILE_CREATE_ERROR,
                    [
                        'exception'      => $exception->getMessage(),
                        'Failed mid'     => $mid
                    ]
                );

                continue;
            }

            $currency = 1;

            $totalAmount = $totalAmount + $amount;

            $dataAdd = [
                'A/C No'        =>  $accountNumber,
                'D/C'           =>  'C',
                'AMT'           =>  number_format($amount,2),
                'NARRATION'     =>  substr($narration,0,40),
                'BR CODE'       =>  $brCode,
                'Currency'      =>  $currency,
                'Value Date'    =>  $date
            ];

            $data[] = $dataAdd;
        }

        $this->totalAmount = $totalAmount;

        $poolAcNo = '';

        try{
            $poolAcNo = (new Merchant\Service())->getBankAccount(array_key_first($modData),[Type::MERCHANT])['account_number'];
        }
        catch (BadRequestException $exception)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_POOL_ACCOUNT_NOT_FOUND ,
                [
                    'Mid' => array_key_first($modData)
                ]
            );
        }

        $brCodeForPool = $this->getBrCode($poolAcNo);

        $narrationDate = Carbon::parse($date)->isoFormat("DDMMYY");

        $dataDebit = [
            'A/C No'        => $poolAcNo,
            'D/C'           => 'D',
            'AMT'           => number_format($this->totalAmount,2),
            'NARRATION'     => 'GIB Settlement_'.$narrationDate,
            'BR CODE'       => $brCodeForPool,
            'Currency'      => 1,
            'Value Date'    => $date
        ];

        array_unshift($data,$dataDebit);

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_CREATE_MERCHANT_FAILURES,
            [
                'Failed mids'    => $failedMids
            ]
        );

        return $data;

    }

    protected function groupSettlementsByMid($data): array
    {
        $newData = [];
        foreach ($data as $datum)
        {
            $key = $datum['merchant_id'];
            $newData[$key][] = $datum;
        }
        return $newData;
    }

    protected function getBrCode($data)
    {
        if(empty($data) === false)
            return substr($data,0,4);

        return '';
    }

    protected function getAggregatedSettlementAmount($data)
    {
        $totalSum = 0;

        foreach ($data as $datum)
        {
            $totalSum = $totalSum + $datum->amount;
        }

        return $totalSum/100;
    }

    protected function getNarration($data,$mid): string
    {

        // narration -> mid:setl_id:cards_tid/upi_tid

        $params['status'] = 'activated';
        $params['enabled'] = '1';
        $method = 'card';
        $params['gateway'] = 'hdfc';

        $terminals = $this->repo->terminal->fetch($params,$mid);

        if($terminals->count() === 0)
        {
           $method = 'upi';
           $params['gateway'] = 'upi_mindgate';
           $terminals = $this->repo->terminal->fetch($params,$mid);
        }

        $terminal = $this->filterTerminalBasedOnMethod($terminals,$method);

        $tId = '';

        if(is_null($terminal) === false)
            $tId = $terminal['gateway_terminal_id'];

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_TERMINAL_FETCH,
            [
                'Terminals Fetch Params' => $params,
                'Terminals Count'        => $terminals->count(),
                'Terminal picked'        => $terminals,
            ]
        );

        $setlId = $data[0]->id;

        return $mid . ":" . $setlId . ":" . $tId;
    }

    protected function filterTerminalBasedOnMethod($terminals,$method)
    {
        if($terminals->count() === 0)
            return null;

        foreach ($terminals as $terminal)
        {
            if($terminal->$method === true)
            {
               return $terminal;
            }
        }

        return null;
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws'); //TODO : Update

        $bucketType = Bucket::getBucketConfigName($this->type, $this->env);

        return $config[$bucketType];
    }
}

<?php

namespace RZP\Models\Settlement\Processor\HDFCCollectNow;

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
    protected $fileToWriteName = 'hdfc_settlements_file';

    protected $bankName  = 'HDFCCollectNow';

    protected $totalAmount;

    protected $mailAddress = Constants::MAIL_ADDRESSES[Constants::DEVELOPERS];

    protected $jobNameStage = BeamConstants::HDFC_COLLECT_NOW_JOB_NAME;

    protected $jobNameProd = BeamConstants::HDFC_COLLECT_NOW_JOB_NAME;

    protected $type = FileStore\Type::HDFC_COLLECT_NOW_SETTLEMENT_FILE;

    public function __construct()
    {
        parent::__construct();

        $date = Carbon::now()->format('d-m-Y');

        $this->fileToWriteName = $this->fileToWriteName . '-' . $date ;

        $this->transferMode = Base\TransferMode::SFTP;
    }

    protected function customFormattingForFile($path)
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

        $from = $fromTimestamp ?? Carbon::yesterday(Timezone::IST)->addHour(12)->getTimestamp();

        $to = $toTimestamp ?? Carbon::now(Timezone::IST)->addHour(12)->getTimestamp();

        $dataFetch = $this->repo->settlement->getSettlementsBetweenTimePeriodForMerchantIds($input,$from,$to);

        $modData = $this->groupSettlementsByMid($dataFetch);

        foreach ($input as $mid)
        {
            try{
                $accountNumber = (new Merchant\Service())->getBankAccount($mid,[Type::ORG_SETTLEMENT])['account_number'];

                if(isset($accountNumber) === false)
                {
                    $failedMids[] = $mid;
                    continue;
                }

                $amount = $this->getAggregatedSettlementAmount($modData[$mid]);

                $narration = $this->getNarration($modData[$mid],$mid);

                $brCode = $this->getBrCode($accountNumber);
            }
            catch (Exception $exception)
            {
                $failedMids[] = $mid;

                $this->trace->info(
                    TraceCode::SETTLEMENT_FILE_CREATE_ERROR,
                    [
                        'exception'      => $exception->getMessage(),
                        'Failed mids'    => $failedMids
                    ]
                );

                continue;
            }

            $currency = 1;

            $totalAmount = $totalAmount + $amount;

            $dataAdd = [
                'A/C No'        =>  $accountNumber,
                'D/C'           =>  'C',
                'AMT'           =>  $amount,
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
            $poolAcNo = (new Merchant\Service())->getBankAccount($input[0],[Type::MERCHANT])['account_number'];
        }
        catch (BadRequestException $exception)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_POOL_ACCOUNT_NOT_FOUND
            );
        }

        $brCodeForPool = $this->getBrCode($poolAcNo);

        $org = $this->repo->merchant->getMerchantOrg($input[0]);

        $dataDebit = [
            'A/C No'        => $poolAcNo, //Add from env
            'D/C'           => 'D',
            'AMT'           => $this->totalAmount,
            'NARRATION'     => substr($org,0,40),
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


    /**
     * @throws \RZP\Exception\BadRequestException
     */
    protected function getAccountNumber($id){

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        return $this->repo->bank_account->getBankAccount($merchant,[Type::ORG_SETTLEMENT]);

    }

    protected function getBrCode($data)
    {
        return substr($data,0,4);
    }

    protected function getAggregatedSettlementAmount($data)
    {
        $totalSum = 0;

        foreach ($data as $datum)
        {
            $totalSum = $totalSum + $datum->amount;
        }

        return $totalSum;
    }

    protected function getNarration($data,$mid): string
    {

        // narration -> mid:setl_id:cards_tid/upi_tid

        $params['status'] = 'activated';
        $params['enabled'] = true;
        $params['card'] = true;
        $params['gateway'] = 'hdfc';

        $terminals = $this->repo->terminal->fetch($params,$mid);

        if($terminals->count() === 0)
        {
            $params['upi'] = true;
            unset($params['card']);
            $params['gateway'] = 'upi_mindgate';

            $terminals = $this->repo->terminal->fetch($params,$mid);
        }

        $tId = '';

        if($terminals->count()>0)
            $tId = $terminals[0]->id;

        $this->trace->info(
            TraceCode::SETTLEMENT_FILE_TERMINAL_FETCH,
            [
                'Terminals Fetch Params' => $params,
                'Terminals Count'        => $terminals->count()
            ]
        );

        $setlId = $data[0]->id;

        return $mid . ":" . $setlId . ":" . $tId;
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws'); //TODO : Update

        $bucketType = Bucket::getBucketConfigName($this->type, $this->env);

        return $config[$bucketType];
    }
}

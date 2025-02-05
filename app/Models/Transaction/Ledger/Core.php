<?php

namespace RZP\Models\Transaction\Ledger;

use App;

use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Transaction\Service;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Transaction\Processor\Ledger as LedgerProcessor;

class Core extends Base\Core
{
    /**
     * This method should be used when we are sure of serving data from ledger in the respective flow
     * @param $id
     * @param $merchantID
     * @param null $apiResponse
     * @return mixed
     * @throws \Throwable
     * @throws Exception\BadRequestException
     */
    public function findByIdFromLedger($id, $merchantID, $apiResponse = null)
    {
        try
        {
            if ($this->validateExternalFetchEnabled($merchantID) == true)
            {
                $ledgerResponse =  $this->fetchExternalEntity($id);

                if ($apiResponse !== null)
                {
                    (new Service())->compareTransactionsAndLogDifference(
                        [$apiResponse->toArray()],
                        [$ledgerResponse->toArray()],
                        ['method_name' => __FUNCTION__]);
                }

                if ($this->validateExternalFetchAndReturnEnabled($merchantID) == true)
                {
                    return $ledgerResponse;
                }

                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_TXN_FETCH_FAILURE,
                [
                    'id' => $id,
                ]);

            // Suppressing the error and returning null in case of failures at Ledger (CLS) service
            return null;
        }

        return $apiResponse;
    }

    /**
     * This method is used to check if RX_TRANSACTION_LOAD_FROM_LEDGER experiment is enable for the merchant
     * @param $merchantID
     * @return bool
     * @throws \Throwable
     */
    private function validateExternalFetchEnabled($merchantID)
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        // ramp up will be based on percentage so it will not be having any merchant_id
        $experimentName = RazorxTreatment::RX_TRANSACTION_LOAD_FROM_LEDGER;
        if($mode === Mode::TEST)
        {
            $experimentName = RazorxTreatment::RX_TRANSACTION_LOAD_FROM_LEDGER_TEST;
        }
        $requestPayload = [
            "id" =>  $merchantID,
            "experiment_name" => $experimentName,
            'request_data'  => json_encode(['id' =>  $merchantID])
        ];

        $isExperimentEnabled = (new Merchant\Core)->isSplitzExperimentEnable($requestPayload,RazorxTreatment::VARIANT_ENABLE);

        $result = ($isExperimentEnabled === true) ? RazorxTreatment::VARIANT_ENABLE : RazorxTreatment::VARIANT_DISABLE;

        $this->trace->info(
            TraceCode::RX_TRANSACTION_LOAD_FROM_LEDGER_EXPERIMENT,
            [
                'result'    => $result,
                'mode'      => $mode,
            ]);

        return  (strtolower($result) === RazorxTreatment::VARIANT_ENABLE);
    }

    /**
     * This method is used to check if RX_TRANSACTION_LOAD_AND_RETURN_FROM_LEDGER_EXPERIMENT experiment is enable for the merchant
     * @param $merchantID
     * @return bool
     * @throws \Throwable
     */
    public function validateExternalFetchAndReturnEnabled($merchantID)
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $experimentName = RazorxTreatment::RX_TRANSACTION_LOAD_AND_RETURN_FROM_LEDGER;
        if($mode === Mode::TEST)
        {
            $experimentName = RazorxTreatment::RX_TRANSACTION_LOAD_AND_RETURN_FROM_LEDGER_TEST;
        }

        $requestPayload = [
            "id" =>  $merchantID,
            "experiment_name" =>$experimentName,
            'request_data'  => json_encode(['id' =>  $merchantID])
        ];

        $isExperimentEnabled = (new Merchant\Core)->isSplitzExperimentEnable($requestPayload,RazorxTreatment::VARIANT_ENABLE);

        $result = ($isExperimentEnabled === true) ? RazorxTreatment::VARIANT_ENABLE : RazorxTreatment::VARIANT_DISABLE;

        $this->trace->info(
            TraceCode::RX_TRANSACTION_LOAD_AND_RETURN_FROM_LEDGER_EXPERIMENT,
            [
                'result'    => $result,
                'mode'      => $mode,
            ]);

        return (strtolower($result) === RazorxTreatment::VARIANT_ENABLE);
    }
    public function fetchLedgerEntryById($id){
        return $this->fetchExternalEntity($id);
    }

    /**
     * This method is used to fetch journal from ledger service for the journal id
     * @param $id
     * @param string $merchantId
     * @param array $input
     * @return mixed
     * @throws Exception\BadRequestException
     */
    private function fetchExternalEntity($id, $merchantId = '', $input = [])
    {
        try
        {
            $request = [
                'id' => $id,
            ];

            $requestHeaders = [
                LedgerProcessor\Base::LEDGER_TENANT_HEADER => LedgerProcessor\Base::X
            ];

            return $this->app['ledger']->fetchTransactionFromLedger($request, $requestHeaders);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'attributes' => $id,
                    'data'       => $e->getMessage()
                ]);
        }

        $data = [
            'attributes' => $id,
            'operation'  => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }
}

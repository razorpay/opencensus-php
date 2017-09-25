<?php

namespace RZP\Http\Controllers;

use Request;
use Redirect;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Gateway\Rule;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Gateway\Netbanking\Corporation;
use RZP\Models\Gateway\Priority as GatewayPriority;

class GatewayController extends Controller
{
    public function callbackAxis()
    {
        $this->callbackGateway('axis');
    }

    protected function processServerCallback($input, $gateway)
    {
        $gateway = $this->app['gateway']->gateway($gateway);

        // Some gateways may need some pre-processing on the input
        // to be able to call the next few methods.
        //
        // Eg: gateway request needs to be decrypted
        $input = $gateway->preProcessServerCallback($input);

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'Payment id not found in either database: ' . $paymentId);
        }

        \Database\DefaultConnection::set($mode);

        $this->app['basicauth']->setMode($mode);

        $paymentId = Payment\Entity::getSignedId($paymentId);

        return (new Payment\Service)->s2sCallback($paymentId, $input);
    }

    protected function callbackEbs($input)
    {
        $gateway = $this->app['gateway']->gateway('ebs');

        //TODO validate callback

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        \Database\DefaultConnection::set($mode);

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'Payment id not found in either database: ' . $paymentId);
        }

        $this->app['basicauth']->setMode($mode);

        $paymentId = Payment\Entity::getSignedId($paymentId);

        return $this->service('payment')->s2sCallback($paymentId, $input);
    }

    public function callbackGateway($gateway)
    {
        $input = Request::all();

        $data = [];

        $trace = $this->app['trace'];

        $trace->info(
            TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK,
            [
                'input'     => $input,
                'body'      => Request::getContent(),
                'headers'   => Request::header(),
                'gateway'   => $gateway,
            ]);

        switch ($gateway)
        {
            // Standard Cases
            case 'upi_mindgate':
            case 'wallet_freecharge':
            case 'billdesk':
                $data = $this->processServerCallback($input, $gateway);
                break;

            // Only logs the response
            case 'wallet_olamoney':
                break;

            //Special case because gateway is upi_mindgate
            case 'upi_hdfc':
                $data = $this->processServerCallback($input, Payment\Gateway::UPI_MINDGATE);

                break;

            // Special case because we need the raw request body
            case 'upi_icici':
                $input = Request::getContent();

                $data = $this->processServerCallback($input, $gateway);

                break;

        }

        // $input['gateway'] = $gateway;

        return ApiResponse::json($data);
    }

    public function callbackKotakCancel()
    {
        return $this->callbackKotak();
    }

    public function callbackKotak()
    {
        $inputMsg = Request::get('msg');
        $input = explode('|', $inputMsg);

        $app = \App::getFacadeRoot();

        $result = $this->getNetbankingEntityAndModeByTraceId($input[3]);

        $nb = $result['nb'];

        $mode = $result['mode'];

        $trace = $app['trace'];

        // check mode before search
        $trace->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input_all' => Request::all(),
                'input_msg' => Request::input('msg'),
                'input_arr' => $input
            ]);

        if ($nb === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite trace id: ' . $input[3]);
        }

        $paymentId = $nb->getPaymentId();
        $publicPaymentId = $nb->getPublicPaymentId();

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $keys = $this->repo->key->getKeysForMerchant($payment->getMerchantId());
        $publicKey = $keys->first()->getPublicKey($mode);

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $url = $url . '?msg=' . $inputMsg;

        return Redirect::to($url);
    }

    public function callbackCorporation()
    {
        $input = Request::all();

        $this->app['trace']->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [ 'input' => $input ]
        );

        $paymentId = $input[Corporation\ResponseFields::PAYMENT_ID];

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        $this->app['config']->set('database.default', $mode);

        $netbanking = $this->app['repo']->netbanking->findByPaymentIdAndAction(
            $paymentId,
            \RZP\Gateway\Base\Action::AUTHORIZE
        );

        if ($netbanking === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite payment id: ' . $paymentId);
        }

        $publicPaymentId = $netbanking->getPublicPaymentId();

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $keys = $this->repo->key->getKeysForMerchant($payment->getMerchantId());

        $publicKey = $keys->first()->getPublicKey($mode);

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $inputMsg = http_build_query($input);

        $url = $url . '?' . $inputMsg;

        return Redirect::to($url);
    }

    protected function getNetbankingEntityAndModeByTraceId($traceId)
    {
        $app = $this->app;

        $repo = $app['repo']->netbanking;

        $mode = 'test';

        $app['config']->set('database.default', $mode);

        $nb = $repo->findByTraceIdAndAction($traceId, \RZP\Gateway\Base\Action::AUTHORIZE);

        if ($nb === null)
        {
            $mode = 'live';

            $app['config']->set('database.default', $mode);

            $nb = $repo->findByTraceIdAndAction($traceId, \RZP\Gateway\Base\Action::AUTHORIZE);
        }

        return ['nb' => $nb, 'mode' => $mode];
    }

    /**
     * Method to create a gateway downtime entity
     *
     * @param Downtime\Service $service
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGatewayDowntime(Downtime\Service $service)
    {
        $input = Request::all();

        $data = $service->create($input);

        return ApiResponse::json($data);
    }

    /**
     * Method to update gateway downtime entity
     *
     * @param Downtime\Service $service
     * @param string $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putGatewayDowntime(Downtime\Service $service, string $id)
    {
        $input = Request::all();

        $data = $service->edit($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * Method to handle webhook from statuscake
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGatewayDowntimeWebhook(Downtime\Service $service, $source)
    {
        $input = Request::all();

        $data = $service->processGatewayDowntimeWebhook($source, $input);

        return ApiResponse::json($data);
    }

    /**
     * Single use function - Fills provider field in the UPI table with bank code
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fillUpiBank()
    {
        RuntimeManager::setMaxExecTime(1800);

        RuntimeManager::setMemoryLimit('1024M');

        $batchSize = 500;
        $lastId = 0;

        $totalRecords = $failedCount = $successCount = 0;
        $failedIds = [];

        while (true)
        {
            $recordsToUpdate = $this->repo->upi->fetchAllForBankUpdate($batchSize, $lastId);

            $currentBatchCount = count($recordsToUpdate);

            $totalRecords += $currentBatchCount;

            if ($currentBatchCount === 0)
            {
                break;
            }

            foreach ($recordsToUpdate as $upiRecord)
            {
                $provider = $upiRecord->extractProviderFromVpa();

                $bankCode = ProviderCode::getBankCode($provider);

                if ($bankCode === null)
                {
                    $failedCount++;

                    $failedIds[] = $upiRecord->getId();

                    $lastId = $upiRecord->getId();

                    continue;
                }

                $upiRecord->setBank($bankCode);

                try
                {
                    $this->repo->saveOrFail($upiRecord);

                    $successCount++;
                }
                catch (\Exception $ex)
                {
                    $failedCount++;

                    $failedIds[] = $upiRecord->getId();
                }

                $lastId = $upiRecord->getId();
            }

            if ($currentBatchCount < $batchSize)
            {
                break;
            }
        }

        return ApiResponse::json([
            'total_processed'    => $totalRecords,
            'total_success'      => $successCount,
            'total_fail'         => $failedCount,
            'failed_ids'         => implode(', ', $failedIds)
        ]);
    }

    public function createGatewayPriority(string $method)
    {
        $input = Request::all();

        $data = (new GatewayPriority\Service)->createPriorityForMethod($method, $input);

        return ApiResponse::json($data);
    }

    public function getGatewayPriority()
    {
        $data = (new GatewayPriority\Service)->fetchPriority();

        return ApiResponse::json($data);
    }

    public function addOrUpdateGatewayPriority(string $method)
    {
        $input = Request::all();

        $data = (new GatewayPriority\Service)->addOrUpdatePriorityForMethod($method, $input);

        return ApiResponse::json($data);
    }

    public function removeGatewayPriority(string $method)
    {
        $input = Request::all();

        $data = (new GatewayPriority\Service)->removePriorityForMethod($method, $input);

        return ApiResponse::json($data);
    }

    public function createGatewayRule(Rule\Service $service)
    {
        $input = Request::all();

        $data = $service->create($input);

        return ApiResponse::json($data);
    }

    public function deleteGatewayRule(Rule\Service $service, string $id)
    {
        $data = $service->delete($id);

        return ApiResponse::json($data);
    }

    public function updateGatewayRule(Rule\Service $service, string $id)
    {
        $input = Request::all();

        $data = $service->update($id, $input);

        return ApiResponse::json($data);
    }
}

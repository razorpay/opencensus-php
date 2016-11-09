<?php

namespace App\Generic;

use App\Base;
use App\Admin;

class Service extends Base\Service
{
    public function __construct()
    {
        $app = \App::getFacadeRoot();
        $this->trace = $app['trace'];
    }

    public function call(array $input, $route)
    {
        $response = $this->makeRawApiCall($input, $route);
        return [null, $response];
    }

    public function makeRawApiCall($input, $path)
    {
        $input['mode'] = 'live';
        $input['file'] = null;
        $request = new Admin\RawApiRequest($input, $path);
        return $request->send();
    }

    public function tagMerchant($merchantId, $input)
    {
        $error = (new Admin\Validator)->validateInput('add_tags', $input)
            ->messages();

        if (empty($error))
        {
            $merchant = Merchant\Entity::findOrFail($merchantId);
            $merchant->retag(explode(',', $input['tags']));
            $merchant['tags'] = $merchant->tags;
            $this->logActionToSlack($merchant, Actions::TAGGED, ['tags' => $input['tags']]);

            return [null, $merchant->toArray()];
        }
        else
        {
            return [$error, null];
        }
    }

    protected function addTagToMerchant($merchantId, $tag)
    {
        $merchant = Merchant\Entity::findOrFail($merchantId);
        $merchant->tag($tag);
    }

    public function addEntityFeatures($entityType, $entityId, $input)
    {
        $error = $response = array();

        if (!empty($error))
        {
            return array($error, null);
        }

        $this->setApiCredentials();

        try
        {
            $params = array('names'             => explode(",", $input['features']),
                            'entity_type'       => $entityType,
                            'entity_id'         => $entityId);

            $response = $this->api->feature->setFeatures($params);

            $features = $this->api->feature->getFeatures($entityId);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if (empty($error))
        {
            $this->retagMerchant($entityId, $features);

            return [null, $features];
        }

        return array($error, null);
    }

    public function deleteEntityFeature($entityId, $featureName)
    {
        $this->setApiCredentials();

        try
        {
            $response = $this->api->feature->deleteFeature($entityId, $featureName);

            $features = $this->api->feature->getFeatures($entityId);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if (empty($error))
        {
            $this->removeMerchantTag($entityId, $featureName);

            return [null, $features];
        }

        return [$error, null];
    }

    private function removeMerchantTag($entityId, $featureName)
    {
        $merchant = Merchant\Entity::findOrFail($entityId);

        $merchant->untag($featureName);
    }

    private function retagMerchant($entityId, $features)
    {
        $merchant = Merchant\Entity::findOrFail($entityId);

        $featureNames = $this->getFeatureNames($features['assigned_features']);

        $merchant->retag(array_merge($featureNames, $merchant->tags));
    }

    private function getFeatureNames($features)
    {
        $featureNames = array_map(function ($feature)
        {
            return $feature['name'];
        }, $features);

        return $featureNames;
    }

    public function getMerchantTags($merchantId)
    {
        $merchant = Merchant\Entity::findOrFail($merchantId);

        return [null, $merchant->tagNames()];
    }

    public function confirmMerchant($merchantId)
    {
        (new Merchant\Service)->confirmMerchantById($merchantId);

        $this->logActionToSlack($merchantId, Actions::CONFIRMED);

        return [null, 'Merchant Confirmed'];
    }

    public function editIIN($iin, $input)
    {
        // Auth as admin, live mode
        $this->setApiCredentials(null);

        $this->api->IIN->edit($iin, $input);
        return [null, 'IIN Edit successful'];
    }

    /**
     * deletes an EMI Plan
     * @param  string $emiId EMI Plan Id
     * @return array
     */
    public function deleteEmi($emiId)
    {
        $this->setApiCredentials(null);
        $error = $data = [];

        try
        {
            $data = $this->api->EMI->setId($emiId)->delete($emiId);
        }
        catch (ApiError $e)
        {
            $error = $e->getMessage();
        }

        return [$error, $data];
    }

    public function addEMI($input)
    {
        // EMI Plans are modeless so we don't care about live or test
        $this->setApiCredentials(null);
        $error = $data = [];

        try
        {
            $data = $this->api->EMI->create($input);
        }
        catch (ApiError $e)
        {
            $error = $e->getMessage();
        }

        return [$error, $data];
    }

    /**
     * See the data params at
     * https://razorpay.slack.com/services/20502106306?updated=1#service_setup
     *
     * The token is matched in the filter stage, so we just parse the message here
     * @param  array  $input Slack input
     */
    public function querySlack(array $input)
    {
        $slack = new Slack($input['text'], $input['user_name'], $input['channel_name']);

        return $slack->getResponse();
    }

    public function getMerchantAggregations($mode, $resource, $input)
    {
        $error = (new Admin\Validator)->validateInput('merchant_stats', $input)->messages();

        if (empty($error))
        {
            $sort = \Input::get('sort', 'total_amount');
            return [null, (new Transaction\Service)->getAllAggregations($mode, $resource, $sort)];
        }
        else
        {
            return [$error, null];
        }

    }

    public function getSingleMerchantAggregations($merchantId, $mode, $resource)
    {
        $data = [
            'merchant_id'   =>  $merchantId,
            'resource'      =>  $resource
        ];

        $response = Merchant\Entity::getAggregations($data, $mode);

        return [null, $response];
    }

    public function makeReconciliateRequest($input)
    {
        $this->setApiCredentials();

        $error = $data = null;

        try
        {
            $data = $this->api->admin->makeReconciliateRequest($input);
        }
        catch(BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

       return [$error, $data];
    }

    public function fetchPaymentNetworks()
    {
        $this->setApiCredentials(null, 'live');

        $data = $this->api->pricing->fetchPaymentNetworks();

        return $data;
    }

    public function updateMerchantDayAggregations($mode, $input)
    {
        $total_payments = $this->fetchPaymentsToAggregate($input, $mode);

        $this->trace->info(TraceCode::MISC_TRACE_CODE, array_keys($total_payments));

        list($error, $response) = (new Transaction\Service)->processDayAggregations($total_payments, $mode);

        return array($error, $response);
    }

    protected function fetchPaymentsToAggregate($input, $mode)
    {
        $dateFrom = Carbon::parse($input['date'])->timestamp;

        $dateTo = $dateFrom + TransactionService::TIME_INTERVALS['day'];

        $count_done = 0;

        $params['status'] = 'captured,refunded';
        $params['from'] = $dateFrom;
        $params['count'] = self::PAGE_SIZE;
        $params['to'] = $dateTo;
        if (isset($input['merchant_id']))
        {
            $params['merchant_id'] = $input['merchant_id'];
        }

        $total_payments = [];

        while (1)
        {
            $params['skip'] = $count_done;

            list($error, $payments) = $this->fetchMultipleEntities($mode, 'payment', $params);

            $count = $payments['count'];

            $payments = $payments['items'];

            foreach ($payments as $payment)
            {
                if ($payment['captured_at'] === NULL)
                {
                    continue;
                }

                $payment = $this->cleanUpPayment($payment);

                $total_payments[$payment['merchant_id']][] = $payment;
            }

            if ($count < self::PAGE_SIZE)
            {
                break;
            }

            $count_done += self::PAGE_SIZE;
        }

        return $total_payments;
    }

    protected function cleanUpPayment($payment)
    {
        $minimal_keys = ['merchant_id', 'amount', 'created_at', 'updated_at'];

        $minimal_payment = array_filter($payment, function($key) use($minimal_keys) {
            return in_array($key, $minimal_keys);
        }, ARRAY_FILTER_USE_KEY);

        return $minimal_payment;
    }

    // ----- Credits -----

    public function getMerchantCreditsLog($merchantId, $mode)
    {
        $error = $data = null;

        $this->setApiCredentials($merchantId, $mode);

        try
        {
            $data = $this->api->merchant->getMerchantCreditLogs();
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function addMerchantCredits($merchantId, $input)
    {
        $error = $data = null;

        $this->setApiCredentials(null, $input['mode']);

        unset($input['mode']);

        try
        {
            $data = $this->api->merchant->addMerchantCredits($merchantId, $input)->toArray();
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function deleteMerchantCredit($merchantId, $creditId, $input)
    {
        $error = $data = null;

        $this->setApiCredentials(null, $input['mode']);

        unset($input['mode']);

        try
        {
            $data = $this->api->merchant->deleteMerchantCredits($merchantId, $creditId);
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

}

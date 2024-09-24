<?php

namespace RZP\Mail\Base;

use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;

class EmailHelper
{
    // check stork migration 
    // userID = user ID or merchant ID
    // orgID = org id
    // razorX = experiment name

    public function isStorkSupported($userID, $orgID, $razorX)
    {
        try
        {
            $app = \App::getFacadeRoot();
            $mode = $app['rzp.mode'] ?? Mode::LIVE;
            $razorxFeature = RazorxTreatment::API_STORK_BANKING_EMAIL . $razorX;
            $traceCode = TraceCode::API_STORK_BANKING_EMAIL;

            // check the experiment
            $userVariant = $app['razorx']->getTreatment($userID,
            $razorxFeature, $mode);

            $orgVariant = $app['razorx']->getTreatment($orgID,
            $razorxFeature, $mode);

            $app['trace']->info($traceCode, [
                'mode' => $mode,
                'userID' => $userID,
                'orgID' => $orgID,
                'userVariant' => $userVariant,
                'orgVariant' => $orgVariant
            ]);


            if (strtolower($userVariant) === 'on' or strtolower($orgVariant) === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                null,
                $traceCode
            );
        }

        return false;
    }

    public function getSplitzResponse(string $id, string $experimentName)
    {

        $app = \App::getFacadeRoot();

        try
        {
            $experimentId = $app->config->get($experimentName);

            $response = $app['splitzService']->evaluateRequest([
                'id'            => $id,
                'experiment_id' => $experimentId,
            ]);
        }
        catch (\Throwable $e)
        {
            $app->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id'   => $id,
                'experiment_id' => $app->config->get($experimentName) ?? null,
                'experiment_name' => $experimentName
            ]);
        }

        return $response['response']['variant']['name'] ?? '';
    }

}
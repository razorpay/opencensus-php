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
    
    //    check wether certain template is migrated to stork using splitz
    //    to get id of experiment will be prefixed with banking_mail_ and suffixed with _exp_id
    //    example : $experiment = test
    //    banking_mail_test_exp_id
    public function isStorkSupportedCheckViaSplitz($userID, $orgID, $experiment)
    {
        try
        {
            $app = \App::getFacadeRoot();
            $traceCode = TraceCode::API_STORK_BANKING_EMAIL;

            $experimentName = 'app.banking_mail_' . $experiment . '_exp_id';

            $userVariant = $this->getSplitzResponse($userID,$experimentName);

            $orgVariant = $this->getSplitzResponse($orgID,$experimentName);

            $app['trace']->info($traceCode, [
                'userID' => $userID,
                'orgID' => $orgID,
                'userVariant' => $userVariant,
                'orgVariant' => $orgVariant,
                'experimentName' => $experimentName
            ]);


            if (strtolower($userVariant) === 'enable' or strtolower($orgVariant) === 'enable')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                null,
                $traceCode,
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
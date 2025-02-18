<?php

namespace RZP\Mail\Base;

use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Admin\Org;
use RZP\Models\Feature;

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

            $trimmedOrgId = str_replace('org_', '', $orgID);
            $org = (new Org\Repository())->findOrFailPublic($trimmedOrgId);

            $isStorkEmailVASEnabled = $org->isFeatureEnabled(Feature\Constants::ENABLE_STORK_EMAIL) === true;

            $app['trace']->info($traceCode, [
                'userID' => $userID,
                'orgID' => $orgID,
                'userVariant' => $userVariant,
                'orgVariant' => $orgVariant,
                'experimentName' => $experimentName,
                'isStorkEmailVASEnabled' => $isStorkEmailVASEnabled,
            ]);


            if (strtolower($userVariant) === 'enable' or strtolower($orgVariant) === 'enable' or $isStorkEmailVASEnabled)
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

    public function isSendingPayoutServiceMailsSupported($merchantId,$view) : bool {

        $traceCode = TraceCode::PAYOUT_SERVICE_EMAIL_ATTEMPT_VIA_SPLITZ;

        $experimentId = 'app.send_payout_service_emails_via_stork_all';

        try
        {
            $app = \App::getFacadeRoot();

            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get($experimentId),
                'request_data'  => json_encode(['merchant_id' => $merchantId , 'template_name' => $view])
            ];

            $response = $app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $app['trace']->info($traceCode, [
                'splitzUserResult' => $response,
            ]);

            return  $variant == "enable";
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException($e, null, TraceCode::PAYOUT_SERVICE_EMAIL_ATTEMPT_STORK_EXCEPTION);
        }

        return false;
    }
}

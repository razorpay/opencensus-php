<?php

namespace App\User;

use Session;
use App\Lib\Util;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\RZP\PublicCollection;
use App\Providers\GenericUser;
use App\Merchant\GenericMerchant;
use App\Metrics\Constants as MetricConstants;
use const App\Http\Controllers\EVENT_TRIGGER_COUNT;

class Helper
{
    public function getCurrentMerchant(GenericUser $user)
    {
        $sessionMerchantId = Session::get('current_merchant_id');
        $currentMerchant = null;

        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        // Primary role
        $productRole = 'role';
        // Banking role
        $switchProductRole = 'banking_role';

        if ($isBankingRequest === true)
        {
            // Banking role
            $productRole = 'banking_role';
            // Primary role
            $switchProductRole = 'role';
        }

        if ($sessionMerchantId !== null)
        {
            // Check if user is associated to a merchant on given product
            $currentMerchant = $user->merchants->where('id', $sessionMerchantId)
                                               ->filter(function ($item) use ($productRole)
                                                 {
                                                     return ($item->$productRole !== null);
                                                 })
                                               ->first();

            if ($currentMerchant === null)
            {
                // Update the user and check if he accepted any new invites after logging in.
                list($error, $updatedUser) = (new Service())->getUserFromApi($user->id);

                if (empty($error) === true)
                {
                    $currentMerchant = $updatedUser->merchants->where('id', $sessionMerchantId)
                                                              ->filter(function ($item) use ($productRole)
                                                                {
                                                                    return ($item->$productRole !== null);
                                                                })
                                                              ->first();
                }
            }
        }

        if ($currentMerchant === null)
        {
            // Check if user is associated to a merchant on given product
            $currentMerchant = $user->merchants->filter(function ($item) use ($productRole)
                                                 {
                                                     return ($item->$productRole !== null);
                                                 })
                                               ->first();

            // If current merchant is still null, that means that the user is not linked to a merchant on given product
            // We'll allow only owner on switch product to login
            if ($currentMerchant === null)
            {
                $currentMerchant = $user->merchants->filter(function ($item) use ($switchProductRole)
                                                     {
                                                         return ($item->$switchProductRole === 'owner');
                                                     })
                                                   ->first();
            }
        }

        if ($currentMerchant !== null)
        {
            Session::put('current_merchant_id', $currentMerchant->id);
        }

        return $currentMerchant;
    }

    public function getOwnerMerchant(GenericUser $user)
    {
        $currentMerchant = $this->getCurrentMerchant($user);

        $ownerMerchant = $user->merchants->where('id', $currentMerchant->id)
                                         ->where('role', 'owner')
                                         ->first();

        return $ownerMerchant;
    }

    public function createGenericUsers(array $users)
    {
        $genericUsers = new PublicCollection;

        foreach ($users as $user)
        {
            $genericUsers->push($this->createdGenericUser($user));
        }

        return $genericUsers;
    }

    public function createdGenericUser(array $user)
    {
        $merchants = new PublicCollection;

        if (isset($user['merchants']) === true)
        {
            foreach ($user['merchants'] as $merchant)
            {
                $merchant['activated'] = (int) ($merchant['activated'] ?? 0);

                $merchants->push(new GenericMerchant($merchant));
            }
        }

        $genericUser = new GenericUser($user);

        $genericUser->merchants = $merchants;

        return $genericUser;
    }

    public function isOwner($user)
    {
        return $user->role === 'owner';
    }

    /**
     * @param string $flow which flow is pushing the metrics
     * @param array $input input array; sensitive data is masked off before usage
     * @param array|null $error error array as received from API requests;
     * @param float $duration duration of the complete call
     */
    public static function pushSignUpLoginMetrics(string $flow, array $input, array $error = null, float $duration = 0)
    {
        $app        = \App::getFacadeRoot();
        $metrics    = $app['metrics'];
        $trace      = $app['trace'];

        try
        {
            if (empty($error) === true)
            {
                $traceDetails = Constants::TRACE_DETAILS_MAP[$flow][$flow.Constants::SUCCESS_SUFFIX];
                $traceDetails[Constants::SUCCESS] = true;
            }
            else
            {
                $traceDetails = Constants::TRACE_DETAILS_MAP[$flow][$flow.Constants::FAILED_SUFFIX];
                $traceDetails[Constants::SUCCESS] = false;
                if(array_key_exists(Constants::INTERNAL_ERROR_CODE, $error))
                {
                    $traceDetails[Constants::ERROR_CODE] = $error[Constants::INTERNAL_ERROR_CODE];
                }
                else
                {
                    $firstElement = reset($error);

                    if((is_array($firstElement) === true) and (array_key_exists(Constants::INTERNAL_ERROR_CODE, $firstElement) === true))
                    {
                        $traceDetails[Constants::ERROR_CODE] = $firstElement[Constants::INTERNAL_ERROR_CODE];
                    }
                    else
                    {
                        $traceDetails[Constants::ERROR_CODE] = "UNKNOWN_ERROR";
                    }
                }
            }

            $traceDetails[Constants::DURATION] = $duration;

            $product        = ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY;
            $mediumLabel    = $traceDetails[Constants::IS_LOGIN] ? MetricConstants::LOGIN_MEDIUM : MetricConstants::SIGNUP_MEDIUM;
            $methodLabel    = $traceDetails[Constants::IS_LOGIN] ? MetricConstants::LOGIN_METHOD : MetricConstants::SIGNUP_METHOD;
            $medium         = "MEDIUM_NA";
            $mediumValue    = "UNKNOWN";

            if(isset($input[Constants::EMAIL]) === true)
            {
                $medium         = MetricConstants::EMAIL;
                $mediumValue    = Util::mask_email($input[Constants::EMAIL]);
            }
            elseif (isset($input[Constants::CONTACT_MOBILE]) === true)
            {
                $medium         = MetricConstants::CONTACT_MOBILE;
                $mediumValue    = Util::mask_phone($input[Constants::CONTACT_MOBILE]);
            }

            $traceData = [
                $medium                     => $mediumValue,
                $mediumLabel                => $medium,
                $methodLabel                => $traceDetails[Constants::METHOD],
                MetricConstants::PRODUCT    => $product,
            ];

            $traceCode  = $traceDetails[Constants::TRACE_CODE];
            $metricName = $traceDetails[Constants::METRIC_CONSTANT];

            $metricDimensions = [
                $mediumLabel                => $medium,
                $methodLabel                => $traceDetails[Constants::METHOD],
                MetricConstants::PRODUCT    => $product,
            ];

            if($traceDetails[Constants::SUCCESS] === false)
            {
                $traceData[Constants::ERROR_CODE]           = $traceDetails[Constants::ERROR_CODE];
                $traceData[Constants::ERROR]                = $error;
                $metricDimensions[Constants::ERROR_CODE]    = $traceDetails[Constants::ERROR_CODE];
            }

            $metrics->count($metricName, EVENT_TRIGGER_COUNT, $metricDimensions);

            $trace->info($traceCode, $traceData);

            $metrics->histogram(
                $traceDetails[Constants::METRIC_DURATION_CONSTANT],
                $traceDetails[Constants::DURATION],
                [
                    $mediumLabel                => $medium,
                    MetricConstants::PRODUCT    => $product,
                ]
            );
        }
        catch(\Exception $e)
        {
            $trace->error(
                TraceCode::LOGIN_SIGNUP_METRIC_TRACE_PUSH_FAILED,
                [
                    "exception" => $e->getMessage()
                ]
            );
        }
    }
}

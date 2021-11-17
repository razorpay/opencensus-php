<?php

namespace App\Merchant\Notifications;

use App\Base;
use App\Lib\Util;

class Service extends Base\Service
{
    /**
     * To get the list of old notifications to be shown to a user.
     *
     * @param array $user User details.
     *
     * @return array List of notifications for a user.
     */
    public function getOldNotificationsForUser(array $user, array $org): array
    {
        $notifications = [];
        if ($this->isOrgRZP($org)) {
            $notifications = Constants::getNotifications();
        }
        return $this->notificationsFiltered($notifications, $user);
    }

    /**
     * To get the list of new notifications to be shown to a user.
     *
     * @param array $user User details.
     *
     * @return array List of notifications for a user.
     */
    public function getNewNotificationsForUser(array $user, array $org): array
    {
        if ($this->isOrgRZP($org)) {
            $notifications = Constants::getSplitzBasedNotifications();
            return $this->notificationsFiltered($notifications, $user);
        }
        return [];
    }

    /**
     * It returns filtered notifications to be shown to user.
     *
     * @param array $notifications
     * @param array $user
     *
     * @return array Filtered notifications.
     */
    private function notificationsFiltered(array $notifications, array $user): array
    {
        $filteredNotifications = [];

        array_walk($notifications, function(&$value, $key) use ($user, &$filteredNotifications) {
            $isUserEligible = false;

            if ((isset($value['filters']) === false) or
                (empty($value['filters']) === true) or
                ($this->userEligibleForNotification($value['filters'], $user, false) === true))
            {
                $this->populateCampaignDetailsBasedOnFilters($value,$user);
                unset($value['filters']);
                $isUserEligible = true;
            }

            if ($isUserEligible and
                ((isset($value['inverseFilters']) === false) or
                (empty($value['inverseFilters']) === true) or
                ($this->userEligibleForNotification($value['inverseFilters'], $user, true) === true)))
            {
                unset($value['inverseFilters']);

                $filteredNotifications[] = $value;
            }
        });

        return $filteredNotifications;
    }

    /**
     * Checks if the user is eligible for a given notification.
     *
     * @param array $notificationFilters Filter values inside notification.
     * @param array $user                User details.
     * @param bool  $inverse             Signifies whether the function behaves inversely or not. i.e. The user should pass the filter checks or fail them
     *
     * @return bool
     */
    private function userEligibleForNotification(array $notificationFilters, array $user, bool $inverse): bool
    {
        $isUserEligible = true;

        foreach ($notificationFilters as $key => $value)
        {
            switch ($key)
            {
                case 'tags':
                case 'features':
                case 'campaigns':

                    if (isset($user[$key]) === false)
                    {
                        return false;
                    }

                    $userFilterValue = $user[$key];

                    $userFilterValue = array_map('strtolower', $userFilterValue);

                    $value = array_map('strtolower', $value);

                    $isUserEligible = (empty(array_intersect($userFilterValue, $value)) === false);

                    break;

                case 'not_tags':
                case 'not_features':

                    $actualKey = explode("_", $key)[1];

                    if (isset($user[$actualKey]) === true)
                    {
                        $userFilterValue = $user[$actualKey];

                        $userFilterValue = array_map('strtolower', $userFilterValue);

                        $value = array_map('strtolower', $value);

                        $isUserEligible = (empty(array_intersect($userFilterValue, $value)) === true);
                    }

                    break;

                case 'activated':

                    if (isset($user[$key]) === false)
                    {
                        return false;
                    }

                    $userFilterValue = $user[$key];

                    $isUserEligible = ($value === $userFilterValue);

                    break;

                case 'activation_status':
                case 'role':

                    if (isset($user[$key]) === false)
                    {
                        return false;
                    }

                    $userFilterValue = $user[$key];

                    $value = array_map('strtolower', $value);

                    $isUserEligible = in_array(strtolower($userFilterValue), $value, true);

                    break;

                case 'experiments':

                    if (isset($user[$key]) === false)
                    {
                        return $inverse;
                    }

                    $userFilterValue = $user[$key];

                    foreach ($userFilterValue as $key => $subValue)
                    {
                        $userFilterValue[strtolower($key)] = $userFilterValue[$key]['result'];

                        if (strtolower($key) !== $key)
                        {
                            unset($userFilterValue[$key]);
                        }
                    }

                    $userFilterValue = array_map('strtolower', $userFilterValue);

                    $value = array_map('strtolower', $value);

                    foreach ($value as $key => $subValue)
                    {
                        if (isset($userFilterValue[$subValue]) === true)
                        {
                            $value[$key] = $userFilterValue[$subValue];
                        }
                    }

                    $isUserEligible = $inverse ? !in_array("on", $value) : in_array("on", $value);

                    break;

                case 'splitz_experiments':
                    $this->replaceExperimentPlaceholdersWithId($value);
                    if (isset($user[$key]) === false) {
                        return false;
                    }
                    $userFilterValue = $user[$key];
                    foreach ($userFilterValue as $key => $subValue) {
                        $userFilterValue[$key] = (isset($userFilterValue[$key]) &&
                            isset($userFilterValue[$key]['variables'])) ?
                            $userFilterValue[$key]['variables']['result'] : [];
                    }
                    foreach ($value as $key => $subValue) {
                        if (isset($userFilterValue[$subValue]) === true) {
                            $value[$key] = $userFilterValue[$subValue];
                        }
                    }
                    $isUserEligible = in_array("on", $value);
                    break;

                case 'business_category':
                case 'business_type':

                    if (isset($user[$key]) === false)
                    {
                        return false;
                    }

                    $businessType = $user[$key];

                    $isUserEligible = in_array($businessType, $value, true);

                    break;

                case 'live_transaction_done':

                    if (isset($user[$key]) === false)
                    {
                        return false;
                    }

                    $liveTransactionDone = $user[$key];

                    $isUserEligible = ($liveTransactionDone >= $value);

                    break;

                case 'experiments_with_variant':

                    if (isset($user['experiments']) === false)
                    {
                        return false;
                    }

                    $userFilterValue = $user['experiments'];

                    foreach ($userFilterValue as $key => $subValue)
                    {
                        $userFilterValue[strtolower($key)] = strtolower($userFilterValue[$key]['result']);
                        if (strtolower($key) !== $key)
                        {
                            unset($userFilterValue[$key]);
                        }
                    }

                    foreach ($value as $key => $subValue)
                    {
                        $value[strtolower($key)] = strtolower($value[$key]);
                        if (strtolower($key) !== $key)
                        {
                            unset($value[$key]);
                        }
                    }

                    $isUserEligible = (empty(array_intersect_assoc($userFilterValue, $value)) === false);

                    break;
            }

            if ($isUserEligible === false)
            {
                return false;
            }
        }

        return true;
    }


    /**
     *   populate campaign specific details in the fetched announcement.
     *   (currently enabled for razorx experiments and splitz experiments filter).
     *
     * @param array|mixed $value
     * @param array|mixed $user
     */
    private function populateCampaignDetailsBasedOnFilters(array &$value, array $user)
    {
        $experiments = array_key_exists('experiments', $user) ? $user['experiments'] : [];
        $splitzExperiments = array_key_exists('splitz_experiments', $user) ? $user['splitz_experiments'] : [];

        $this->populateCampaignDetailsBasedOnRazorxExperiments($value, $experiments);
        $this->populateCampaignDetailsBasedOnSplitzExperiments($value, $splitzExperiments);
    }


    /**
     *   Gets filtered campaign data based on the razorX experiments or splitz experiment.
     *   This method takes the experiments and based on the filter condition for the type
     *   of the experiment, it returns the dynamic data that needs to be added dynamically.
     *
     * @param array|mixed $campaignDetails
     * @param array|mixed $experiments
     * @param array|mixed $announcementExperiments
     *
     * @return array|mixed
     */

    private function getFilteredCampaignDetails(array $campaignDetails, array $experiments, array $announcementExperiments): array
    {
        $filteredCampaignDetail = [];

        foreach ($campaignDetails as $detail)
        {
            array_walk($experiments, function ($experiment, $key) use ($detail, &$filteredCampaignDetail,$announcementExperiments) {
                if (($this->isCampaignAValidRazorXExperiment($detail, $key, $experiment, $announcementExperiments) or
                    $this->isCampaignAValidSplitzExperiment($detail, $key, $experiment, $announcementExperiments)))
                {
                    $filteredCampaignDetail = $detail['data'];
                    return;
                }
            });
        }
        return $filteredCampaignDetail;
    }

    /**
     *   populate campaign specific details in the fetched announcement
     *   based on the razorx experiments.
     *   As we are dynamically populating campaign details to the announcement that
     *   has experiment as a filter, this method merges the data from announcement
     *   and campaign data, based on the experiment value.
     *
     * @param array|mixed $value announcement that needs to be updated
     * @param array|mixed $experiments list of razor experiments based on which the data
     *   is mapped between announcement and campaign data.
     */

    private function populateCampaignDetailsBasedOnRazorxExperiments(array &$value, array $experiments)
    {
        if(!isset($experiments) or empty($experiments))
            return;

        $announcementToCampaignDetailMap = Constants::getAnnouncementToSubCampaignDetailsMapping();

        if (!isset($value['filters']) or
            !isset($value['filters']['experiments']) or
            !isset($value['id']) or
            !array_key_exists($value['id'], $announcementToCampaignDetailMap))
            return;

        $campaignDetails = $announcementToCampaignDetailMap[$value['id']];

        //getting filtered campaign details based on the experiments result on and control variant
        $filteredCampaignDetail = $this->getFilteredCampaignDetails($campaignDetails, $experiments, $value['filters']['experiments']);

        if (!isset($filteredCampaignDetail))
            return;

        //adding campaign details in the value object
        foreach ($filteredCampaignDetail as $key => $val)
        {
            $value[$key] = $val;
        }
    }

    /**
     *   populate campaign specific details in the fetched announcement
     *   based on the splitz experiments.
     *   As we are dynamically populating campaign details to the announcement that
     *   has splitz experiment as a filter, this method merges the data from announcement
     *   and campaign data, based on the experiment value.
     *
     * @param array|mixed $value announcement that needs to be updated
     * @param array|mixed $splitzExperiments list of splitz experiments based on which the data
     *   is mapped between announcement and campaign data.
     */

    private function populateCampaignDetailsBasedOnSplitzExperiments(array &$value, array $splitzExperiments)
    {
        if(!isset($splitzExperiments) or empty($splitzExperiments))
            return;

        $announcementToCampaignDetailMap = Constants::getAnnouncementToSubCampaignDetailsMapping();

        if (!isset($value['filters']) or
            !isset($value['filters']['splitz_experiments']) or
            !isset($value['id']) or
            !array_key_exists($value['id'], $announcementToCampaignDetailMap))
            return;

        //replacing announcement experiment placeholders with experiment Id
        $this->replaceExperimentPlaceholdersWithId($value['filters']['splitz_experiments']);

        $campaignDetails = $announcementToCampaignDetailMap[$value['id']];

        //replacing campaign details placeholders with experiment Id
        foreach ($campaignDetails as &$campaignDetail)
        {
            if (array_key_exists('splitz_experiments', $campaignDetail)) {
                $this->replaceExperimentPlaceholdersWithId($campaignDetail['splitz_experiments']);
            }
        }

        //getting filtered campaign details based on the splitz experiments result on and control variant
        $filteredCampaignDetail = $this->getFilteredCampaignDetails($campaignDetails, $splitzExperiments, $value['filters']['splitz_experiments']);

        if (!isset($filteredCampaignDetail))
            return;

        //adding campaign details in the value object
        foreach ($filteredCampaignDetail as $key => $val)
        {
            $value[$key] = $val;
        }
    }

    /**
     *   replaces the placeholder of splitz experiment name present in
     *   announcement with the env specific experiment id loaded from the config.
     *
     *   PS. added two checks one when splitz_experiment is an object array
     *       with key value pair, and another if it is a contiguous string array.
     * @param array $splitzExperiments list of splitz experiments whose placeholder
     *   needs to be updated
     */
    private function replaceExperimentPlaceholdersWithId(array &$splitzExperiments)
    {
        foreach (config('splitz.experiments') as $key => $experimentFeatureFlag)
        {
            if (array_key_exists($key, $splitzExperiments))
            {
                $splitzExperiments[$experimentFeatureFlag] = $splitzExperiments[$key];
                unset($splitzExperiments[$key]);
            }

            if (in_array($key, $splitzExperiments))
            {
                $splitzExperiments = array_replace($splitzExperiments,
                    array_fill_keys(
                        array_keys($splitzExperiments, $key),
                        $experimentFeatureFlag
                    )
                );
            }
        }
    }

    /**
     *   condition to check whether the given campaign qualifies
     *   for a Splitz experiment.
     *   For an experiment to be a splitz experiment,should meet
     *   following requirements:
     *   1. The splitz_experiment should be present in that campaign.
     *   2. The splitz_experiment result should be on in the user object.
     *   3. the splitz_experiment id should match in the present announcement value.
     *
     * @param array $detail dynamic campaign detail whose data needs to be updated in the announcement
     * @param string $key experiment name on which we want to test the validation
     * @param array $experiment experiment object array fetched from user object
     * @param array $announcementExperiments experiment object array fetched from announcement value
     *
     * @return bool
     */
    private function isCampaignAValidSplitzExperiment(array $detail, string $key, array $experiment, array $announcementExperiments): bool
    {
        return ((array_key_exists('splitz_experiments', $detail) and
                (in_array($key, $detail['splitz_experiments']))) and
                (array_key_exists('variables', $experiment)) and
                (array_key_exists('result', $experiment['variables'])) and
                ($experiment['variables']['result'] === "on")) and
                (in_array($key, $announcementExperiments));
    }

    /**
     *   condition to check whether the given campaign qualifies
     *   for a razorx experiment.
     *   For an experiment to be a razorx experiment,should meet
     *   following requirements:
     *   1. The experiment should be present in that campaign.
     *   2. The experiment result should be on in user object.
     *   3. the experiment should match in the present announcement value.
     *
     * @param array $detail dynamic campaign detail whose data needs to be updated in the announcement
     * @param string $key experiment name on which we want to test the validation
     * @param array $experiment experiment object array fetched from user object
     * @param array $announcementExperiments experiment object array fetched from announcement value
     *
     * @return bool
     */

    private function isCampaignAValidRazorXExperiment(array $detail, string $key, array $experiment, array $announcementExperiments): bool
    {
        return ((array_key_exists('experiments', $detail) and
                (in_array($key, $detail['experiments']))) and
                (array_key_exists('result', $experiment)) and
                ($experiment['result'] === "on")) and
                (in_array($key, $announcementExperiments));
    }

    private function isOrgRZP(array $org): bool
    {
        return empty($org['custom_code'] === false) and ($org['custom_code'] === 'rzp');
    }
}

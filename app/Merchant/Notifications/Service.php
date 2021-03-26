<?php

namespace App\Merchant\Notifications;

use App\Base;
use App\Lib\Util;

class Service extends Base\Service
{
    /**
     * To get the list of notifications to be shown to a user.
     *
     * @param array $user User details.
     *
     * @return array List of notifications for a user.
     */
    public function getNotificationsForUser(array $user): array
    {
        $notifications = Constants::getNotifications();

        return $this->notificationsFiltered($notifications, $user);
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
                $this->populateCampaignDetailsBasedOnFilters($value,$user['experiments']);
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

                    if (isset($user[$key]) === false)
                    {
                        return false;
                    }

                    $userFilterValue = $user[$key];

                    foreach ($userFilterValue as $key => $subValue)
                    {
                        $userFilterValue[$key] = isset($userFilterValue[$key]['variables']) === true ? $userFilterValue[$key]['variables'] : '';
                    }

                    $isUserEligible = (empty(Util::array_recursive_diff($value, $userFilterValue)) === true);

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
     * @param $value
     * @param $experiments
     */
    private function populateCampaignDetailsBasedOnFilters(&$value,$experiments)
    {
        $announcementToCampaignDetailMap = Constants::getAnnouncementToSubCampaignDetailsMapping();

        if (!isset($value['filters']) || !isset($value['filters']['experiments']) || !array_key_exists($value['id'],$announcementToCampaignDetailMap))
            return;

        $campaignDetails = $announcementToCampaignDetailMap[$value['id']];

        //getting filtered campaign details based on the experiments result on and control variant
        $filteredCampaignDetail = $this->getFilteredCampaignDetails($campaignDetails, $experiments, $value['filters']['experiments']);

        if(!isset($filteredCampaignDetail))
            return;

        //adding campaign details in the value object
        foreach ($filteredCampaignDetail as $key => $val) {
            $value[$key] = $val;
        }
    }

    /**
     * @param array $campaignDetails
     * @param $experiments
     * @param $announcementExperiments
     * @return array|mixed
     */
    private function getFilteredCampaignDetails(array $campaignDetails, $experiments, $announcementExperiments)
    {
        $filteredCampaignDetail = [];
        foreach ($campaignDetails as $detail) {
            array_walk($experiments, function ($experiment, $key) use ($detail, &$filteredCampaignDetail,$announcementExperiments) {
                if ((in_array($key, $detail['experiments']) && (array_key_exists('result', $experiment)) &&
                    ($experiment['result'] == "on")) && in_array($key, $announcementExperiments)) {
                    $filteredCampaignDetail = $detail['data'];
                    return;
                }
            });
        }
        return $filteredCampaignDetail;
    }
}

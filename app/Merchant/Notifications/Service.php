<?php

namespace App\Merchant\Notifications;

use App\Base;

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

            if ((isset($value['filters']) === false) or
                (empty($value['filters']) === true) or
                ($this->userEligibleForNotification($value['filters'], $user) === true))
            {
                unset($value['filters']);

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
     *
     * @return bool
     */
    private function userEligibleForNotification(array $notificationFilters, array $user): bool
    {
        $isUserEligible = true;

        foreach ($notificationFilters as $key => $value)
        {
            switch ($key)
            {
                case 'tags':
                case 'features':

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
                        return false;
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

                    $isUserEligible = in_array("on", $value);

                    break;
            }

            if ($isUserEligible === false)
            {
                return false;
            }
        }

        return true;
    }
}

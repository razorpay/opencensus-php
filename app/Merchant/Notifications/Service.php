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

            $filters = ['tags', 'features', 'activated', 'role'];

            if ((isset($value['filters']) === false) or
                (empty($value['filters']) === true) or
                ($this->userEligibleForNotification($filters, $value['filters'], $user) === true))
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
     * @param array $filters             Filters used for checking the user eligibility.
     * @param array $notificationFilters Filter values inside notification.
     * @param array $user                User details.
     *
     * @return bool
     */
    private function userEligibleForNotification(array $filters, array $notificationFilters, array $user): bool
    {
        $isUserEligible = true;

        foreach ($filters as $filter)
        {
            //
            // If notification doesn't have this filter, then this filter will
            // not be a deciding factor for showing/hiding the notification.
            //
            if (isset($notificationFilters[$filter]) === false)
            {
                continue;
            }

            //
            // If the user does not contain this filter and notification contains it,
            // do not show the notification to user.
            //
            if (isset($user[$filter]) === false)
            {
                return false;
            }

            $notificationFilterValue = $notificationFilters[$filter];

            $userFilterValue = $user[$filter];

            // Based on type of filter, check if user is eligible for a given notification.
            switch ($filter)
            {
                case 'tags':
                case 'features':

                    $userFilterValue = array_map('strtolower', $userFilterValue);

                    $notificationFilterValue = array_map('strtolower', $notificationFilterValue);

                    $isUserEligible = (empty(array_intersect($userFilterValue, $notificationFilterValue)) === false);

                    break;

                case 'activated':

                    $isUserEligible = ($notificationFilterValue === $userFilterValue);

                    break;

                case 'role':

                    $notificationFilterValue = array_map('strtolower', $notificationFilterValue);

                    $isUserEligible = in_array(strtolower($userFilterValue), $notificationFilterValue, true);

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

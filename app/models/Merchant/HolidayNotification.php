<?php

namespace Models\Merchant;

use Carbon\Carbon;
use Constants\Mode;
use Services\SlackPoster;
use Models\Admin\Newsletter;
use Models\Settlement\Holidays;

class HolidayNotification
{
    use SlackPoster;

    // Action to send test email to one email id
    const TEST_EMAIL  = 'test_email';

    // Action to add the active email ids to mailing list
    const ADD_TO_LIST = 'add_to_list';

    // Action to send the email to mailing list
    const EMAIL       = 'email';

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];
    }

    public function send($input)
    {
        $response = [];

        if (isset($input['action']))
        {
           $response = $this->sendMerchantNotifyHolidayEmail($input);
        }

        return $response;
    }

    /**
     * sendMerchantNotifyHolidayEmail - Based on action sets the
     *     mailer params to send a test email,
     *     add members to mailing list,
     *     set a mailing list name.
     *
     * @param  [type] $input [description]
     */
    protected function sendMerchantNotifyHolidayEmail($input)
    {
        list($msg, $holidays) = $this->getHolidayNotificationMsg($input);

        $tomorrow = Carbon::tomorrow('Asia/Kolkata');

        if (empty($errors))
        {
            $mailer = new Newsletter(
                $input['lists'],
                'Notification of Bank Holiday',
                $msg);

            switch ($input['action'])
            {
                case self::TEST_EMAIL:
                    $mailer->setTestEmail($input['lists']);
                    break;

                case self::ADD_TO_LIST:
                    $mailer->setTestListMembersAdd();
                    $mailer->setMailingListName($input['lists']);
                    break;

                // For live mode, check if tomorrow is a holiday and send mail
                case self::EMAIL:
                    if (($this->mode === MODE::LIVE) and
                        (Holidays::isSpecifiedBankHoliday($tomorrow) === false))
                    {
                        return ['message' => 'Not a holiday tomorrow! Nothing to send.'];
                    }

                    $this->notifySettlementsChannel($holidays);

                    $mailer->setMailingListName($input['lists']);
                    break;

                default:
                    return ['message' => 'No Appropriate action has been set. Nothing done.'];
                    break;
            }

            return $mailer->send();
        }
        else
        {
            return $errors;
        }

    }

    protected function notifySettlementsChannel($holidays)
    {
        $slackMsg = "Bank Holiday Notification";

        $slackData = ['holidays' => $holidays];

        $slackSettings = ['channel' => '#settlements'];

        $this->slackPost($slackMsg, $slackData, $slackSettings);
    }

    protected function getHolidayNotificationMsg($input)
    {
        $today = Carbon::today('Asia/Kolkata');

        $nextWorkingDay = Holidays::getNextWorkingDay($today);

        $nextWorkingDayString = $nextWorkingDay->toFormattedDateString();

        $holidays = Holidays::getSpecifiedBankHolidaysBetween($today, $nextWorkingDay);

        if ($input['action'] !== self::EMAIL)
        {
            $holidays = $this->getTestHolidayMessage();
        }

        $msg  = \View::make('emails.partials.holiday_notification')
                     ->with('holidays',$holidays)
                     ->with('nextWorkingDayString', $nextWorkingDayString)
                     ->render();

        return [$msg,$holidays];
    }

    protected function getTestHolidayMessage()
    {
        $testHoliday = [
            'date'   => Carbon::tomorrow('Asia/Kolkata'),
            'reason' => 'Testing reason for holiday notification.',
        ];

        return [$testHoliday];
    }
}

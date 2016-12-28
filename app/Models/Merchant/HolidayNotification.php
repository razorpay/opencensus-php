<?php

namespace RZP\Models\Merchant;

use Config;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Admin\Newsletter;
use RZP\Models\Settlement\Holidays;

class HolidayNotification
{
    // Action to send test email to one email id
    const TEST_EMAIL  = 'test_email';

    // Action to add email ids to mailing list
    const ADD_TO_LIST = 'add_to_list';

    // Action to send the email to mailing list
    const EMAIL       = 'email';

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];
    }

    public function send($input)
    {
        $response = ['success' => true];

        if (isset($input['action']))
        {
           $response = $this->sendMerchantNotifyHolidayEmail($input);
        }

        return $response;
    }

    /**
     * Based on action sets the mailer params to :
     * 1) Send a test email or,
     * 2) Add members to mailing list or,
     * 3) Send live email to mailing list.
     *
     * 1) Send a test email : TEST_EMAIL
     *    The email passed in the `lists` param will be sent a test holiday
     *    notification email
     *
     * 2) Add members to mailing list : ADD_TO_LIST
     *    Members of the `lists` will be added to corresponding lists on
     *    mailgun. eg. live or all. DOES NOT SEND EMAIL.
     *
     * 3) Send live email to mailing list : EMAIL
     *    Mail the list in the `lists` parameter. eg. live or all.
     *
     * @param  array $input Requires an action, lists params
     * @return array Can contain a message or email, count keys.
     */
    protected function sendMerchantNotifyHolidayEmail($input)
    {
        list($msg, $holidays) = $this->getHolidayNotificationMsg($input);

        $mailer = new Newsletter(
            'Notification of Bank Holiday',
            $msg,
            'holiday_notification');

        // Handle based on action
        switch ($input['action'])
        {
            // Action to send test email to one email id
            case self::TEST_EMAIL:
                // Set the email to which test email is to be sent.
                $mailer->setTestEmail($input['lists']);

                $msg = $mailer->send();
                break;

            // Action to add email ids to mailing list
            case self::ADD_TO_LIST:
                $mailer->setRecipient($input['lists']);

                // Set the mailer to add the members to mailing list
                $mailer->setTestListMembersAdd();

                // Set the mailing list name.
                $mailer->setMailingListName($input['lists']);

                $msg = $mailer->send();
                break;

            // Action to send the email to mailing list
            case self::EMAIL:
                $mailer->setRecipient($input['lists']);

                // Adds logic to send only on specific days
                list($send, $returnMessage) = $this->isMailToBeSent();

                if ($send === false)
                {
                    $msg = ['message' => $returnMessage];
                }
                else
                {
                    $msg = $this->sendEmail($mailer, $holidays, $input['lists']);
                }
                break;

            default:
                $msg = ['message' => 'No Appropriate action has been set. Nothing done.'];
                break;
        }

        return $msg;
    }

    /**
     * Adding more rules for when a mail is to be sent
     *
     * 0) Mails are to be sent in test mode, check only in live
     * 1) Mails are to be sent only on a working day
     * 2) Mails are to be sent for series of holidays.
     */
    protected function isMailToBeSent()
    {
        $today = Carbon::today('Asia/Kolkata');

        if ($this->mode === Mode::TEST)
        {
            return [true, 'Test Mode. Mail to be sent.'];
        }

        // Mails not to sent on holidays in live mode
        if (Holidays::isWorkingDay($today) === false)
        {
            return [false, 'Not a working day today. Nothing to send.'];
        }

        // Get Next working day that is not a bank holiday
        $ignoreBankHolidays = true;

        $nextWorkingDay = Holidays::getNextWorkingDay($today, $ignoreBankHolidays);

        // Ensure if that is a settlement holiday then send mail
        if (Holidays::isSpecifiedBankHoliday($nextWorkingDay) === true)
        {
            return [true, 'Next working day is a bank holiday. Mail to be sent.'];
        }

        return [false, 'Next working day is not a bank holiday. Nothing to send.'];

    }

    protected function notifySettlementsChannel($holidays)
    {
        $slackMsg = "Bank Holiday Notification";

        $slackData = ['holidays' => $holidays];

        $slackSettings = ['channel' => Config::get('slack.channels.settlements')];

        $this->app['slack']->queue($slackMsg, $slackData, $slackSettings);
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

    protected function sendEmail($mailer, $holidays, $lists)
    {
        // Send a notification to slack
        $this->notifySettlementsChannel($holidays);

        // Set the mailing list name.
        $mailer->setMailingListName($lists);

        return $mailer->send();
    }
}

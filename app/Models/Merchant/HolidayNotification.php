<?php

namespace RZP\Models\Merchant;

use Config;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Jobs\CreateMailingList;
use RZP\Models\Admin\Newsletter;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Settlement\SlackNotification;

class HolidayNotification
{
    // Action to send test email to one email id
    const TEST_EMAIL  = 'test_email';

    // Action to add email ids to mailing list
    const ADD_TO_LIST = 'add_to_list';

    // Action to send the email to mailing list
    const EMAIL       = 'email';

    const SUB_LIST    = ['settlement_default', 'settlement_on_demand'];

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
        list($holidays, $nextWorkingDayString) = $this->getHolidayNotificationMsg($input);

        if($input['action'] === self::ADD_TO_LIST)
        {
            CreateMailingList::dispatch($this->mode);

            return ["response" => "CreateMailingList job dispatched"];
        }
        else
        {
            $finalResponse = [];

            foreach (self::SUB_LIST as $subList)
            {
                switch ($subList)
                {
                    case 'settlement_default':
                        $msg = $this->getMsgForOnDemandNotEnabled($holidays, $nextWorkingDayString);
                        break;
                    case 'settlement_on_demand':
                        $msg = $this->getMsgForOnDemandEnabled($holidays, $nextWorkingDayString);
                        break;
                }

                $mailer = new Newsletter(
                    'Bank Holiday - Settlement Update',
                    $msg,
                    'holiday_notification');

                // Handle based on action
                switch ($input['action'])
                {
                    // Action to send test email to one email id
                    case self::TEST_EMAIL:
                        // Set the email to which test email is to be sent.
                        $mailer->setTestEmail($input['lists'].'_'.$subList);

                        $response = $mailer->send();

                        array_push($finalResponse, [$subList => $response]);
                        break;

                    // Action to send the email to mailing list
                    case self::EMAIL:
                        $mailer->setRecipient($input['lists'].'_'.$subList);

                        // Adds logic to send only on specific days
                        list($send, $returnMessage) = $this->isMailToBeSent();

                        if ($send === false)
                        {
                            $response = ['message' => $returnMessage];

                            array_push($finalResponse, [$subList => $response]);
                        }
                        else
                        {
                            $response = $this->sendEmail($mailer, $holidays, $input['lists'].'_'.$subList, $input['action']);

                            array_push($finalResponse, [$subList => $response]);
                        }
                        break;

                    default:
                        $response = ['message' => 'No Appropriate action has been set. Nothing done.'];

                        array_push($finalResponse, [$subList => $response]);
                }
            }
        }
        return $finalResponse;
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
        $today = Carbon::today(Timezone::IST);

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

        (new SlackNotification)->send($slackMsg, $slackData);
    }

    protected function getHolidayNotificationMsg($input)
    {
        $today = Carbon::today(Timezone::IST);

        $nextWorkingDay = Holidays::getNextWorkingDay($today);

        $nextWorkingDayString = $nextWorkingDay->toFormattedDateString();

        $holidays = Holidays::getSpecifiedBankHolidaysBetween($today, $nextWorkingDay);

        if ($input['action'] !== self::EMAIL)
        {
            $holidays = $this->getTestHolidayMessage();
        }

        return [$holidays, $nextWorkingDayString];
    }

    protected function getMsgForOnDemandEnabled($holidays, $nextWorkingDayString)
    {
        $settleNowEnabled = 'enabled';

        $msg  = \View::make('emails.partials.holiday_notification')
                     ->with('holidays',$holidays)
                     ->with('nextWorkingDayString', $nextWorkingDayString)
                     ->with('settleNowEnabled', $settleNowEnabled)
                     ->render();

        return $msg;
    }

    protected function getMsgForOnDemandNotEnabled($holidays, $nextWorkingDayString)
    {
        $settleNowEnabled = 'not_enabled';

        $msg  = \View::make('emails.partials.holiday_notification')
                     ->with('holidays',$holidays)
                     ->with('nextWorkingDayString', $nextWorkingDayString)
                     ->with('settleNowEnabled', $settleNowEnabled)
                     ->render();

        return $msg;
    }

    protected function getTestHolidayMessage()
    {
        $testHoliday = [
            'date'   => Carbon::tomorrow(Timezone::IST),
            'reason' => 'Testing reason for holiday notification.',
        ];

        return [$testHoliday];
    }

    protected function sendEmail($mailer, $holidays, $list, $action)
    {
        // Send a notification to slack
        $this->notifySettlementsChannel($holidays);

        // Set the mailing list name.
        $mailer->setMailingListName($list);

        return $mailer->send($action);
    }
}

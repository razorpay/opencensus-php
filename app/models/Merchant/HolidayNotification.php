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

    const TEST_EMAIL  = 'test_email';
    const ADD_TO_LIST = 'add_to_list';
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

    protected function sendMerchantNotifyHolidayEmail($input)
    {
        list($msg, $holidays) = $this->getHolidayNotificationMsg();

        $tomorrow = Carbon::tomorrow('Asia/Kolkata');

        if (empty($errors))
        {
            $mailer = new Newsletter(
                $input['lists'],
                'Razorpay | Notification of Bank Holiday',
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

    protected function getHolidayNotificationMsg()
    {
        $today = Carbon::today('Asia/Kolkata');

        $nextWorkingDay = Holidays::getNextWorkingDay($today);

        $nextWorkingDayString = $nextWorkingDay->toFormattedDateString();

        $holidays = Holidays::getSpecifiedBankHolidaysBetween($today, $nextWorkingDay);

        $holidaysTableTemplate = $this->getHolidaysTableTemplate($holidays);

        $msg  = <<<EOT
Settlements will not be processed on the following days due to bank holidays:
$holidaysTableTemplate
<br>
<p><b>Settlements will next be processed on $nextWorkingDayString.<b></p>
<p>Thank you for partnering with Razorpay.</p>
EOT;

        return [$msg,$holidays];
    }

    protected function getHolidaysTableTemplate($holidays)
    {
        $template = '<table cellpadding=\'5\'><tr><th width=\'90\'>Date</th><th width\'290\'>Reason</th></tr>';

        foreach ($holidays as $holiday)
        {
            $template .= '<tr><td width=\'90\'>'.$holiday['date']->toFormattedDateString().'</td>';
            $template .= '<td width=\'290\'>'.$holiday['reason'].'</td></tr>';
        }

        $template .= '</table>';

        return $template;
    }
}

<?php

namespace RZP\Models\Merchant\Balance;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Merchant\NegativeBalanceBreachReminder;
use RZP\Models\Merchant\Reminders\Notifier as RemindersNotifier;
use RZP\Mail\Merchant\NegativeBalanceAlert as NegativeBalanceAlertMail;
use RZP\Mail\Merchant\BalancePositiveAlert as BalancePositiveAlertMail;
use RZP\Mail\Merchant\ReserveBalanceActivate as ReserveBalanceActivateMail;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert as NegativeBalanceThresholdAlertMail;

class NegativeReserveBalanceMailers extends Base\Core
{
    const REMINDERS_NAMESPACE = 'negative_balance';

    const REMINDER_INTERVAL = 72; //hours

    /**
     * Send Negative Balance Alert Mail, if the balance satisfies
     * some conditions of Negative Balance Alerts.
     *
     * @param Merchant\Entity $merchant
     * @param int $oldBalance
     * @param int $newBalance
     * @param int $maxNegativeAllowed
     * @param string $balanceSource
     * @param string $txnType
     */
    public function sendNegativeBalanceMailIfApplicable(Merchant\Entity $merchant,
                                                        int $oldBalance,
                                                        int $newBalance,
                                                        int $maxNegativeAllowed,
                                                        string $balanceSource,
                                                        string $txnType)
    {
        if (($oldBalance < 0) and
            ($newBalance >= 0))
        {
            $this->sendMailBalanceBecamePositive($merchant, $newBalance, $balanceSource);
        }
        else
        {
            if (($newBalance >= 0) or
                ($oldBalance < $newBalance))
            {
                return;
            }

            if ($maxNegativeAllowed === 0)
            {
                return;
            }

            //percentage thresholds below which negative balance threshold breached mail should be sent
            $negativeThresholdAlerts = [100, 90, 80, 70, 50];

            $oldPercentage = (int) abs(($oldBalance * 100) / $maxNegativeAllowed);

            $newPercentage = (int) abs(($newBalance * 100) / $maxNegativeAllowed);

            $thresholdBreached = false;

            foreach ($negativeThresholdAlerts as $threshold)
            {
                if (($newPercentage >= $threshold) and
                    ($oldPercentage < $threshold))
                {
                    $this->sendMailNegativeBalanceThresholdBreached($merchant, $newBalance, $threshold,
                        $maxNegativeAllowed, $balanceSource, $txnType);

                    $thresholdBreached = true;
                    break;
                }
            }

            if (($oldBalance >= 0) and
                ($newBalance < 0))
            {
                if ($thresholdBreached === false)
                {
                    $this->sendMailBalanceBecameNegative($merchant, $newBalance, $balanceSource);
                }

                try
                {
                    //Create Reminders if balance went negative
                    (new RemindersNotifier($merchant))->createOrUpdateReminder(self::REMINDERS_NAMESPACE,
                        microtime(true));
                }
                catch(\Exception $e)
                {
                    $this->trace->traceException($e,
                        Trace::ERROR,
                        TraceCode::MERCHANT_REMINDER_SAVE_FAILURE);
                }
            }

        }
    }

    /**
     * Send Negative Balance Alert Mail, if the balance goes beyond
     * threshold percentages of Balance Negative Limit.
     *
     * @param Merchant\Entity $merchant
     * @param int $balance
     * @param int $threshold
     * @param int $maxNegativeAllowed
     * @param string $balanceSource
     * @param string $txnType
     */
    private function sendMailNegativeBalanceThresholdBreached(Merchant\Entity $merchant,
                                                              int $balance,
                                                              int $threshold,
                                                              int $maxNegativeAllowed,
                                                              string $balanceSource,
                                                              string $txnType)
    {
        $customBranding = isset($merchant) ? (new Merchant\Core())->isOrgCustomBranding($merchant):false;

        $data = [
            'email'                  => $merchant->getEmail(),
            'merchant_id'            => $merchant->getId(),
            'merchant_name'          => $merchant->getName(),
            'timestamp'              => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'percentage'             => $threshold,
            'max_negative_allowed'   => ($maxNegativeAllowed / 100) . ' INR',
            'balance_source'         => $balanceSource,
            'balance'                => ($balance / 100) . 'INR',
            'headers'                => MailTags::NEGATIVE_BALANCE_THRESHOLD_ALERT,
            '$customBranding'        => $customBranding,
        ];

        if ($customBranding === true)
        {
            $org = $merchant->org;

            $data['email_logo'] = $org->getEmailLogo();
        }

        $this->trace->info(TraceCode::NEGATIVE_BALANCE_THRESHOLD_ALERT, $data);

        $dimensions = (new Metric)->getBalanceNegativeThresholdBreachedDimensions($merchant->getId(), $balance, $threshold,
            $txnType);

        $this->trace->count(Metric::BALANCE_NEGATIVE_THRESHOLD, $dimensions);

        $negativeBalanceAlertMail = new NegativeBalanceThresholdAlertMail($data);

        Mail::queue($negativeBalanceAlertMail);
    }

    /**
     * Send Negative Balance Alert Mail,
     * if the balance goes Negative from Positive value.
     *
     * @param Merchant\Entity $merchant
     * @param int $balance
     * @param string $balanceSource
     */
    private function sendMailBalanceBecameNegative(Merchant\Entity $merchant,
                                                   int $balance,
                                                   string $balanceSource)
    {
        $customBranding = isset($merchant) ? (new Merchant\Core())->isOrgCustomBranding($merchant):false;

        $data = [
            'email'                 => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'balance'               => ($balance) / 100 . ' INR',
            'balance_source'        => $balanceSource,
            'headers'               => MailTags::BALANCE_NEGATIVE_ALERT,
            '$customBranding'       => $customBranding,
        ];

        if ($customBranding === true)
        {
            $org = $merchant->org;

            $data['email_logo'] = $org->getEmailLogo();
        }

        $negativeBalanceAlertMail = new NegativeBalanceAlertMail($data);

        Mail::queue($negativeBalanceAlertMail);
    }

    /**
     * Send Negative Balance Alert Mail, if the balance goes Positive from Negative value.
     *
     * @param Merchant\Entity $merchant
     * @param int $newBalance
     * @param string $balanceSource
     */
    private function sendMailBalanceBecamePositive(Merchant\Entity $merchant,
                                                   int $newBalance,
                                                   string $balanceSource)
    {

        $customBranding = isset($merchant) ? (new Merchant\Core())->isOrgCustomBranding($merchant):false;

        $data = [
            'email'                 => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'merchant_name'         => $merchant->getName(),
            'balance'               => ($newBalance) / 100 . ' INR',
            'balance_source'        => $balanceSource,
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'headers'               => MailTags::BALANCE_POSITIVE_ALERT,
            '$customBranding'       => $customBranding,
        ];

        if ($customBranding === true)
        {
            $org = $merchant->org;

            $data['email_logo'] = $org->getEmailLogo();
        }

        $balancePositiveAlertMail = new BalancePositiveAlertMail($data);

        (new RemindersNotifier($merchant))->deleteReminder(self::REMINDERS_NAMESPACE);

        Mail::queue($balancePositiveAlertMail);
    }

    /**
     * Send Reserve Balance Activate Mail, if the Reserve balance is added for the first time.
     *
     * @param Merchant\Entity $merchant
     * @param Entity $balance
     */
    public function sendReserveBalanceActivatedMail(Merchant\Entity $merchant, Entity $balance)
    {
        $data = [
            'email'                 => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'reserve_limit'         => ($balance->getBalance()) / 100 . ' INR',
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'headers'               => MailTags::RESERVE_BALANCE_ACTIVATED,
        ];

        $reserveBalanceActivateMail = new ReserveBalanceActivateMail($data);

        Mail::queue($reserveBalanceActivateMail);
    }

    public function sendNegativeBalanceBreachReminders(Merchant\Entity $merchant, int $reminderCount, int $balanceAmount)
    {
        $dayCount = $reminderCount * (self::REMINDER_INTERVAL / 24);
        $since = date('Y-m-d', mktime(0, 0, 0, date("m") ,
                                    date("d") - $dayCount, date("Y")));

        $customBranding = isset($merchant) ? (new Merchant\Core())->isOrgCustomBranding($merchant):false;

        $data = [
            'email'                 => $merchant->getEmail(),
            'merchant_id'           => $merchant->getId(),
            'since'                 => $since,
            'balance'               => ($balanceAmount) / 100 . ' INR',
            'timestamp'             => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'headers'               => MailTags::NEGATIVE_BALANCE_BREACH_REMINDER,
            '$customBranding'       => $customBranding,
        ];

        if ($customBranding === true)
        {
            $org = $merchant->org;

            $data['email_logo'] = $org->getEmailLogo();
        }

        $negativeBalanceBreachReminder = new NegativeBalanceBreachReminder($data);

        Mail::queue($negativeBalanceBreachReminder);
    }
}

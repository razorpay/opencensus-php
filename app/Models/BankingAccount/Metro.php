<?php

namespace RZP\Models\BankingAccount;

use RZP\Jobs;
use Throwable;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Metro\MetroHandler;
use Razorpay\Trace\Logger as Trace;

class Metro extends Base\Service
{
    //Metro Constants
    const CURRENT = 'current';

    const RBL_CA_UPDATES = 'rbl_ca_updates';

    const APPLICATION = 'application';

    const ID = 'id';

    /**
     * Publishes message to metro topic when banking_account is updated
     *
     * @param Entity $bankingAccount
     * @return void
     * @throws Throwable
     */

    public function publishToMetro(Entity $bankingAccount, int $retryCount = 1)
    {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        $caller = $dbt[7]['function'] ?? null;

        // Don't publish to metro if is bankingAccount isn't a current_account or if bankingAcccount update is triggered by master-onboarding (MOB).
        // (This metro event is sent to make MOB update its DB to reflect updates made to an application where MOB is not the source. Therefore, it is
        // not required to send a metro event if the source of application update is MOB because MOB would already have the updated version of the application.)
        if ($bankingAccount->getAccountType() !== self::CURRENT or
            $this->app['basicauth']->isMobApp() or
            $caller === 'fetchGatewayBalance')
        {
            return;
        }

        Jobs\BankingAccount\BankingAccountNotifyMob::dispatch($bankingAccount);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_NOTIFY_MOB_JOB_DISPATCHED,
                [
                    'banking_account_id'     => $bankingAccount->getId(),
                    'merchant_id' => $bankingAccount->getMerchantId(),
                ]);

//        $metroHandler = (new MetroHandler());
//
//        $id = $bankingAccount->getId();
//
//        $topic = self::RBL_CA_UPDATES;
//
//        $data = [
//            self::APPLICATION => [
//                self::ID => $id
//            ]
//        ];
//
//        $publishData['data'] = json_encode($data);

//        try {
//            $response = $metroHandler->publish($topic, $publishData);
//
//            $this->trace->info(TraceCode::BANKING_ACCOUNT_METRO_MESSAGE_PUBLISHED,
//                [
//                    'topic'     => $topic,
//                    'application_id' => $id,
//                    'response'  => $response,
//                ]);
//
//        } catch (Throwable $e) {
//
//            if ($retryCount > 0)
//            {
//                $this->trace->warning(TraceCode::BANKING_ACCOUNT_METRO_MESSAGE_PUBLISH_RETRY,
//                    [
//                        'topic'          => $topic,
//                        'application_id' => $id,
//                    ]);
//
//                $this->publishToMetro($bankingAccount, $retryCount - 1);
//
//                return;
//            }
//
//            $this->trace->traceException(
//                $e,
//                Trace::CRITICAL,
//                TraceCode::BANKING_ACCOUNT_METRO_MESSAGE_PUBLISH_ERROR,
//                [
//                    'topic'     => $topic,
//                    'application_id' => $id,
//                ]);
//
//            throw $e;
//        }
    }
}

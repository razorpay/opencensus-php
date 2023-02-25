<?php

namespace RZP\Jobs\BankingAccount;

use Monolog\Logger;
use RZP\Jobs\RequestJob;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount as BankingAccountModel;

class BankingAccountNotifyMob extends RequestJob
{
    public const RELEASE_WAIT_SECS = 600;

    protected $metricsEnabled = true;

    protected BankingAccountModel\Entity $bankingAccount;

    protected $traceCodeError = TraceCode::BANKING_ACCOUNT_NOTIFY_MOB_JOB_ERROR;

    public function __construct(BankingAccountModel\Entity $bankingAccount)
    {
        parent::__construct([]);

        $this->bankingAccount = $bankingAccount;

    }

    /**
     * @throws \Throwable
     */
    public function handleRequest()
    {

        $bankingAccountId = $this->bankingAccount->getId();

        $merchantId = $this->bankingAccount->getMerchantId();

        $startTime = microtime(true);

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'banking_account_id' => $bankingAccountId,
            'merchant_id' => $merchantId,
            'start_time' => $startTime,
        ];

        $this->trace->info(TraceCode::BANKING_ACCOUNT_NOTIFY_MOB_JOB_STARTED, $tracePayload);

        $userId = '';

        $repo = new MerchantRepo();

        $merchant = $repo->findOrFail($merchantId);

        $user = $merchant->owners(ProductType::BANKING)->first();

        $userId = optional($user)->getId();

        if (empty($userId) === true)
        {
            // Adding this since ops team can start onboarding for PG merchants as well
            $user = $merchant->owners(ProductType::PRIMARY)->first();

            $userId = $user->getId();
        }

        app('basicauth')->setMerchant($merchant);
        app('basicauth')->setUser($user);

        $data = [
            'application' => [
                'id' => $bankingAccountId
            ]
        ];

        // Encode the associative array as a JSON string
        $jsonString = json_encode($data);

        // Convert the JSON string to a byte slice in Go format
        $goByteSlice = [];
        foreach (str_split($jsonString) as $char) {
            $goByteSlice[] = ord($char);
        }

        $payload = [
            'message' => [
                'data' => $goByteSlice,
            ],
        ];

        /** @var \RZP\Services\MasterOnboardingService $mobService */
        $mobService = app('master_onboarding');
        $mobService->sendRequestAndParseResponse('update_ca_workflow', 'POST', $payload, false);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_NOTIFY_MOB_JOB_SUCCESS, array_merge($tracePayload, [
            'duration'          => (microtime(true) - $startTime) * 1000,
        ]));
    }

}

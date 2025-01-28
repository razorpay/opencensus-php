<?php
namespace RZP\Jobs\Kafka;

use App;
use Neves\Events\TransactionalClosureEvent;
use RZP\Base\RepositoryManager;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\AccessMap\Core;
use RZP\Models\Merchant\AccessMap\Service;
use RZP\Models\Merchant\Detail\Status;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Detail\Core as MerchantDetailsCore;
use RZP\Models\Merchant\Detail\Constants as MerchantDetailsConstants;
use RZP\Jobs\CrossBorder\CrossBorderCommonUseCases;
use RZP\Models\BankTransfer\Service as BankTransferService;

class BvsVideoKYCEventsJob extends Job
{
    // VKYC Status
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    // Common
    const STATUS = 'status';
    const MERCHANT_ID = 'merchant_id';
    const ACCOUNT_ID = 'account_id';

    // EDD Status is updated only in case Video KYC Status
    // is sent as Approved or Rejected
    const VALID_BVS_VIDEO_KYC_STATUS_FOR_EDD_UPDATE = [self::APPROVED, self::REJECTED];

    /* EDD Status have only 3 States
        1. not_verified (Is Sent to FE in case NULL in DB Column)
        2. verified (if video kyc is approved)
        3. failed (if video kyc is rejected)
    */
    const BVS_VIDEO_KYC_TO_EDD_STATUS_MAPPING = [
        self::APPROVED => MerchantDetailsConstants::VERIFIED,
        self::REJECTED => MerchantDetailsConstants::FAILED
    ];

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;


    /**
     * @throws \Throwable
     */
    public function handle()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        try
        {
            $tracePayload = [
                'job_attempts' => $this->attempts(),
                'mode' => $this->getJobMode(),
                'payload' => $this->getPayload(),
                'task_id' => $taskId
            ];

            $this->trace->info(TraceCode::BVS_VIDEO_KYC_EVENTS_JOB_REQUEST, $tracePayload);

            $vKYCStatus = $this->payload[self::STATUS];

            $merchantID = $this->payload[self::ACCOUNT_ID];

            if(isset($merchantID) === true && isset($vKYCStatus) === true && $this->shouldUpdateEDDStatus($vKYCStatus))
            {
                $merchantDetailsCore = new MerchantDetailsCore;

                $eddStatus = $this->getVKycToEDDStatusMapping($vKYCStatus);

                $requestPayload = [
                    self::STATUS        =>  $eddStatus,
                    self::MERCHANT_ID   =>  $merchantID
                ];

                $response = $merchantDetailsCore->updateEDDStatus($requestPayload);

                /*
                 *  Activate money saver account post vcip activation
                 */
                $this->activateVirtualActivationInternally($merchantID, $eddStatus);

                $this->sendPACBVkycWebhhok($eddStatus);
                $this->trace->info(TraceCode::BVS_VIDEO_KYC_EVENTS_UPDATE_EDD_STATUS_PROCESSED, [
                    "response" => $response,
                ]);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->error(TraceCode::BVS_VIDEO_KYC_EVENTS_JOB_PROCESSING_FAILED,
            [
                'merchantId' => $this->payload[self::ACCOUNT_ID],
                'vkyc_status' => $this->payload[self::STATUS],
                'error'      => $e->getMessage()
            ]);
        }
    }

    private function shouldUpdateEDDStatus($vKYCstatus) : bool
    {
        return in_array($vKYCstatus, self::VALID_BVS_VIDEO_KYC_STATUS_FOR_EDD_UPDATE);
    }

    private function getVKycToEDDStatusMapping($vKYCstatus) : string
    {
        return self::BVS_VIDEO_KYC_TO_EDD_STATUS_MAPPING[$vKYCstatus];
    }

    private function sendPACBVkycWebhhok($eddStatus)
    {
        $merchantID = $this->payload[self::ACCOUNT_ID];
        $isPACBPartnerSubMerchant = (new Core())->isPACBPartnerSubMerchant($merchantID);
        if (!$isPACBPartnerSubMerchant) {
            return;
        }
        $merchant = $this->repo->merchant->find($merchantID);
        $activationStatus = $merchant->merchantDetail->getActivationStatus();
        if ($activationStatus === Status::ACTIVATED and $eddStatus === MerchantDetailsConstants::VERIFIED) {
            $eventPayload = [
                ApiEventSubscriber::MAIN => $merchant,
            ];

            $event = 'api.account.' . $activationStatus;

            \Event::dispatch(new TransactionalClosureEvent(function () use ($event, $eventPayload) {
                $this->app['events']->dispatch($event, $eventPayload);
            }));
        }

        if ($eddStatus === MerchantDetailsConstants::FAILED) {
            $eventPayload = [
                ApiEventSubscriber::MAIN => $merchant,
                ApiEventSubscriber::WITH => [
                    'vkyc' => 'VKYC was rejected. Please try VKYC again.'
                ]
            ];

            $event = 'api.account.needs_clarification';

            \Event::dispatch(new TransactionalClosureEvent(function () use ($event, $eventPayload) {
                $this->app['events']->dispatch($event, $eventPayload);
            }));
        }

    }

    private function getJobMode() : string
    {
        if (isset($this->mode) === true)
        {
            return $this->mode;
        }

        return Mode::LIVE;
    }


    private function activateVirtualActivationInternally(string $merchantID, string $eddStatus): void
    {
        $merchant = $this->repo->merchant->find($merchantID);
        $activationStatus = $merchant->merchantDetail->getActivationStatus();
        $bankTransferService = new BankTransferService();
        try {
            if ($activationStatus === Status::ACTIVATED and $eddStatus === MerchantDetailsConstants::VERIFIED) {

                $this->trace->info(TraceCode::ACTIVATE_INTERNATIONAL_VA_INTERNALLY, [
                    'merchantId' => $merchantID,
                    'vkyc_status' => $eddStatus,
                ]);

                if ($merchant->hasValidPurposeCodeForGlobalBankTransfer() === false || empty($merchant->getIecCode()) === true) {
                    $this->trace->info(TraceCode::ACTIVATE_INTERNATIONAL_VA_REQUEST_MISSING_PURPOSE_CODE, [
                        'merchantId' => $merchantID,
                        'vkyc_status' => $eddStatus,
                    ]);
                    return;
                }

                $experimentEnabled = $bankTransferService->isAsyncInternationalVirtualAccountActivationEnabled($merchantID);

                if ($experimentEnabled === true ) {
                    $payload = [
                        'action' => CrossBorderCommonUseCases::CREATE_INTERNATIONAL_VIRTUAL_ACCOUNT_INTERNALLY,
                        'merchant_id' => $merchantID,
                        'mode' =>  Mode::LIVE,
                    ];
                    CrossBorderCommonUseCases::dispatch($payload)->delay(rand(5, 10));
                }
            }

            if ($eddStatus === MerchantDetailsConstants::FAILED) {
                $this->trace->error(TraceCode::ACTIVATE_INTERNATIONAL_VA_INTERNALLY_FAILED,
                    [
                        'merchantId' => $merchantID,
                        'vkyc_status' => $eddStatus,
                    ]);
                return;
            }

        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::ACTIVATE_INTERNATIONAL_VA_INTERNALLY_FAILED,
                [
                    'merchantId' => $merchantID,
                    'vkyc_status' => $eddStatus,
                    'error' => $e->getMessage()
                ]);
        }

    }

}

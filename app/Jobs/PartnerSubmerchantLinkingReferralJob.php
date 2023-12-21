<?php

namespace RZP\Jobs;

use Throwable;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\Merchant\Metric;
use Jitendra\Lqext\TransactionAware;
use RZP\Models\Merchant\CapitalSubmerchantUtility;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Referral\Core as ReferralCore;
use RZP\Models\Merchant\Detail\Service as DetailService;

class PartnerSubmerchantLinkingReferralJob extends Job
{
    use TransactionAware;

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected        $queueConfigKey = 'commission';

    protected        $metricsEnabled = true;

    protected string $subMerchantId;

    protected array  $referralInput;

    protected bool   $isSignupFlow;

    public function __construct($mode, string $subMerchantId, array $referralInput, bool $isSignupFlow)
    {
        parent::__construct($mode);
        $this->subMerchantId = $subMerchantId;
        $this->referralInput = $referralInput;
        $this->isSignupFlow  = $isSignupFlow;
    }

    public function getReferralInput(): array
    {
        return $this->referralInput;
    }

    public function getIsSignupFlow(): bool
    {
        return $this->isSignupFlow;
    }

    public function getMerchantId(): string
    {
        return $this->subMerchantId;
    }

    public function handle(): void
    {
        parent::handle();

        $this->trace->info(
            TraceCode::REFERRAL_MERCHANT_ACCESS_MAP_ASYNC_JOB,
            [
                "submerchant_id" => $this->subMerchantId,
                "referral_input" => $this->referralInput,
                "is_signup_flow" => $this->isSignupFlow,
            ]
        );

        try
        {
            $subMerchant = $this->repoManager->merchant->findOrFailPublic($this->subMerchantId);

            $referral = (new ReferralCore())->fetchReferralByReferralCode($this->referralInput['referral_code']);

            $accessMaps = $this->repoManager->merchant_access_map->fetchAccessMapForMerchantIdAndOwnerId($subMerchant->getId(), $referral->getMerchantId());

            $detailService = new DetailService();

            $detailService->applyReferralPartner($subMerchant, $this->referralInput, $this->isSignupFlow);

            if ($referral->getProduct() == Product::CAPITAL and $this->isSignupFlow === false)
            {
                $partner = $this->repoManager->merchant->findOrFailPublic($referral->getMerchantId());

                //Disable commissions only if merchant was not a sub-merchant for the partner before referral
                if($accessMaps->isEmpty() === true)
                {
                    (new CapitalSubmerchantUtility())->createPartnerConfigForExistingMerchantsInvitedForLOC($partner, $subMerchant);
                }

                (new CapitalSubmerchantUtility())->trackPartnershipsCapitalInviteExistingSubmerchantLinkedEvent($partner, $subMerchant->getId(), PartnerConstants::REFERRAL);

                $detailService->createCapitalApplicationIfApplicable($subMerchant, $referral);
            }

            $this->delete();
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::REFERRAL_MERCHANT_ACCESS_MAP_ASYNC_JOB_FAILED,
                [
                    "submerchant_id" => $this->subMerchantId,
                    "referral_input" => $this->referralInput,
                    "is_signup_flow" => $this->isSignupFlow,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(Throwable $e): void
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::REFERRAL_MERCHANT_ACCESS_MAP_ASYNC_JOB_MESSAGE_DELETE,
                [
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}

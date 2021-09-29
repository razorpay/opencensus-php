<?php

namespace RZP\Jobs;

use RZP\Diag\EventCode;
use RZP\Models\TrustedBadge\Core;
use RZP\Models\TrustedBadge\Entity;
use RZP\Trace\TraceCode;
use App;

class TrustedBadge extends Job
{
    protected $queueConfigKey = 'trusted_badge';

    public $timeout = 60;

    protected $merchantId;

    protected $eligibilityChecks;

    public function __construct(string $mode, string $merchantId, array $eligibilityChecks)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->eligibilityChecks = $eligibilityChecks;
    }

    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        try
        {
            $this->trace->info(
                TraceCode::RTB_MESSAGE_RECEIVED,
                [
                    'mode'        => $this->mode,
                    'merchantId'  => $this->merchantId,
                ]);

            $isMerchantEligibleForRTB = $this->eligibilityChecks[Entity::STANDARD_CHECKOUT_ELIGIBLE] === true &&
                                        $this->eligibilityChecks[Entity::IS_DMT_MERCHANT] === false &&
                                        $this->eligibilityChecks[Entity::IS_DISPUTE_MERCHANT] === false;

            $status  = $isMerchantEligibleForRTB ? Entity::ELIGIBLE : Entity::INELIGIBLE;

            (new Core())->upsertStatus($this->merchantId, $status);

            $this->trace->info(
                TraceCode::RTB_MESSAGE_PROCESSED,
                [
                    'mode'            => $this->mode,
                    'merchantId'      => $this->merchantId,
                    'status'          => $status,
                    'eligibilityChecks' => $this->eligibilityChecks,
                ]);

            $app['diag']->trackTrustedBadgeEvent(EventCode::TRUSTED_BADGE_CRON_RESULT, [
                'merchantId'      => $this->merchantId,
                'status'          => $status,
            ]);

            $this->delete();

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::RTB_MESSAGE_PROCESSING_FAILED,
                [
                    'mode'        => $this->mode,
                    'merchantId'  => $this->merchantId,
                    'eligibilityChecks' => $this->eligibilityChecks,
                ]
            );

            $app['diag']->trackTrustedBadgeEvent(EventCode::TRUSTED_BADGE_CRON_FAILURE, [
                'merchantId'      => $this->merchantId,
            ]);
        }
    }
}

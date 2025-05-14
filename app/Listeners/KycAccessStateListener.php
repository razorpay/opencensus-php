<?php
namespace RZP\Listeners;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Listeners\BaseListener;
use RZP\Models\Partner\KycAccessState;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Metric as PartnerMetric;


class KycAccessStateListener extends BaseListener
{
    public function onSaved(KycAccessState\EventSaved $event)
    {
        $entity = $event->entity;

        $partnerId = $entity[KycAccessState\Entity::PARTNER_ID];


        try {

            $shouldSyncSkip = !$this->isPkycSyncExpEnabled($partnerId) or $this->isRouteBlackListed();

            if ($shouldSyncSkip) {
                $this->trace->info(TraceCode::KYC_ACCESS_STATE_EVENT_SAVED_SKIPPED, $this->getTraceInfo($entity));

                $dimensions = [
                    "action" => "upsert",
                ];
                $this->trace->count(PartnerMetric::PARTNER_KYC_ACCESS_STATE_SYNC_SKIPPED, $dimensions);

                return;
            }

            app('partnerships')->upsertPartnerKycAccessState(['partner_kyc_access_state' => $entity->toArray()]);
            $dimensions = [
                "action" => "upsert",
                "status" => true
            ];
    
            $this->trace->count(PartnerMetric::PARTNER_KYC_ACCESS_STATE_SYNC_TOTAL_REQUESTS, $dimensions);
    
            $this->trace->info(TraceCode::KYC_ACCESS_STATE_EVENT_SAVED, $this->getTraceInfo($entity));
        } catch (\Throwable $e) {

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::KYC_ACCESS_STATE_SYNC_ERROR,
                ['message' => $e->getMessage(), 'entity' => $entity->toArray()]
            );

            $dimensions = [
                "action" => "upsert",
                "status" => false
            ];
            $this->trace->count(PartnerMetric::PARTNER_KYC_ACCESS_STATE_SYNC_TOTAL_REQUESTS, $dimensions);

        }
    }

    public function onDeleted(KycAccessState\EventDeleted $event)
    {
        $entity = $event->entity;

        $partnerId = $entity[KycAccessState\Entity::PARTNER_ID];

        try {
            $shouldSyncSkip = !$this->isPkycSyncExpEnabled($partnerId) or $this->isRouteBlackListed();

            if ($shouldSyncSkip) {
                $this->trace->info(TraceCode::KYC_ACCESS_STATE_EVENT_DELETED_SKIPPED, $this->getTraceInfo($entity));
                $dimensions = [
                    "action" => "delete",
                ];
                $this->trace->count(PartnerMetric::PARTNER_KYC_ACCESS_STATE_SYNC_SKIPPED, $dimensions);
                return;
            }

            app('partnerships')->deletePartnerKycAccessState(['partner_kyc_access_state' => $entity->toArray()]);
            $dimensions = [
                "action" => "delete",
                "status" => true
            ];
            $this->trace->count(PartnerMetric::PARTNER_KYC_ACCESS_STATE_SYNC_TOTAL_REQUESTS, $dimensions);

            $this->trace->info(TraceCode::KYC_ACCESS_STATE_EVENT_DELETED, $this->getTraceInfo($entity));
        } catch (\Throwable $e) {

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::KYC_ACCESS_STATE_SYNC_ERROR,
                ['message' => $e->getMessage(), 'entity' => $entity->toArray()]
            );
            $dimensions = [
                "action" => "delete",
                "status" => false
            ];
            $this->trace->count(PartnerMetric::PARTNER_KYC_ACCESS_STATE_SYNC_TOTAL_REQUESTS, $dimensions);

        }
    }

    private function isRouteBlacklisted(): bool
    {
        $routeOrJob = app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName();

        $this->trace->info(TraceCode::KYC_ACCESS_STATE_ROUTE_JOB_NAME, ['routeOrJob' => $routeOrJob]);

        $blacklistedRouteAndJob = ["internal_upsert_partner_kyc_access", 'worker:partnerships_outbox_event_handler_job'];

        return (array_key_exists($routeOrJob, $blacklistedRouteAndJob));
    }

    private function isPkycSyncExpEnabled(string $partnerId): bool
    {
        $merchantCore = new Merchant\Core();

        $properties = [
            'id' => $partnerId,
            'experiment_id' => app('config')->get('app.partner_kyc_access_state_partnership_service_sync'),
        ];

        return $merchantCore->isSplitzExperimentEnable($properties, 'enable');
    }

    private function getTraceInfo(KycAccessState\Entity $entity): array
    {
        $fields = [
            KycAccessState\Entity::ID,
            KycAccessState\Entity::PARTNER_ID,
            KycAccessState\Entity::ENTITY_ID,
            KycAccessState\Entity::ENTITY_TYPE,
            KycAccessState\Entity::REJECTION_COUNT,
            KycAccessState\Entity::TOKEN_EXPIRY,
            KycAccessState\Entity::STATE,
        ];

        return array_merge(
            [
                'entity' => $entity->only($fields),
            ],
            $this->getActor(),
        );
    }
}
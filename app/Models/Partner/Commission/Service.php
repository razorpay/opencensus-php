<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Partner\Metric;
use RZP\Exception;
use RZP\Models\Base\Repository as BaseRepository;

class Service extends Base\Service
{
    /**
     * Get per transaction commission list for a merchant
     *
     * @param array $input
     *
     * @return array
     */
    public function list(array $input): array
    {
        $commissions = $this->core()->list($this->merchant, $input);

        return $commissions->toArrayPublic();
    }

    public function fetch(string $id): array
    {
        $partner = $this->merchant;

        $input = [
            BaseRepository::EXPAND => [Entity::SOURCE_MERCHANT]
        ];

        //
        // findByPublicIdAndMerchant() function here, filters by partner_id.
        // Refer Commission\Entity::scopeMerchantId() for more details.
        //
        $commission = $this->repo->commission->findByPublicIdAndMerchant($id, $partner, $input);

        return $commission->toArrayPublic();
    }

    public function captureByPartner(string $partnerId): int
    {
        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        return $this->core()->captureByPartner($partner);
    }

    public function bulkCaptureByPartner(array $input): int
    {
        return $this->core()->bulkCaptureByPartner($input);
    }

    public function capture(string $id): array
    {
        $commission = $this->repo->commission->findByPublicId($id);

        return Tracer::inspan(['name' => HyperTrace::COMMISSIONS_CAPTURE_CORE], function () use ($commission, $id) {

            $commission = $this->core()->capture($commission)->toArrayPublic();
            $this->core()->dispatchCommissionCaptureToPRTS($commission[Entity::PARTNER_ID], [$id]);
            return $commission;

        });
    }

    public function clearOnHoldForPartner(string $partnerId, array $input): array
    {
        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        return Tracer::inspan(['name' => HyperTrace::CLEAR_ON_HOLD_FOR_PARTNER_CORE], function () use ($partner, $input) {

            return $this->core()->clearOnHoldForPartner($partner, $input);
        });
    }

    public function fetchAggregateCommissionDetails(string $partnerId, array $input)
    {
        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        return $this->core()->fetchAggregateCommissionDetails($partner, $input);
    }

    /**
     * Fetches required commission config for the payment
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException| \Throwable
     */
    public function fetchCommissionConfigsForPayment(array $input): array
    {
        $paymentId = $input['payment_id'];

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        return $this->core()->fetchCommissionConfigsForPayment($payment);
    }

    public function fetchAnalytics(array $input): array
    {
        (new Merchant\Validator)->validateIsPartner($this->merchant);

        (new Validator)->validateInput('analytics', $input);

        $showAggregateCommReport = $this->core()->shouldShowAggregateCommissionReportForPartner($this->merchant);

        // if partner is not allowed to see report, then he should not see aggregate analytics
        if ($showAggregateCommReport === false)
        {
            return [
                'limit' => Constants::RESELLER_SUBMERCHANT_LIMIT,
            ];
        }

        $queryType = $input[Constants::QUERY_TYPE];

        $func = 'fetchAnalyticsFor' . studly_case($queryType) . 'Query';

        $commissionAnalytics = new Analytics;

        if (method_exists($commissionAnalytics, $func) === false)
        {
            throw new Exception\LogicException('Invalid Query type');
        }

        $query = $commissionAnalytics->$func($input);

        // send mode in query if its only test
        if ($this->mode === Mode::TEST)
        {
            foreach ($query['aggregations'] as $aggregateType => $aggregateQuery)
            {
                $query['aggregations'][$aggregateType]['details']['mode'] = Mode::TEST;
            }
        }

        $query = (new Merchant\Core)->processMerchantAnalyticsQuery($this->merchant->getId(), $query);

        $response = $this->app['eventManager']->query($query);

        $this->trace->count(Metric::COMMISSION_ANALYTICS_FETCH, ['query_type' => $queryType]);

        return $response;
    }
}

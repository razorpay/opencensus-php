<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Constants\HttpRequestHeader;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $uAgent;

    public function __construct()
    {
        parent::__construct();

        $this->uAgent = $this->app['agent'];
    }

    public function createAuditLog($input)
    {
        s($input);
        $action = (new Analytics\Core)->create($input);
        sd($action->toArrayPublic());
        return $action->toArrayPublic();
    }

    public function getAuditsForTerminal($id)
    {
        $audits = $this->repo->payment_analytics->findForTerminal($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPaymentAndTerminal($paymentId, $terminalId)
    {
        $audits = $this->repo->payment_analytics->findForPayment($paymentId, $terminalId);

        return $audits->toArrayPublic();
    }

    public function getAuditsForTerminalBetween($from, $to, $id)
    {
        $audits = $this->repo->payment_analytics->findBetweenTimestampsForTerminal($from, $to, $id);

        return $audits->toArrayPublic();
    }

    public function recordPaymentRequestData($rawData, array & $log)
    {
        // get data from request
        $this->setHttpRequestData($log);

        // get data from frontend
        if (isset($rawData['input']) === false)
        {
            return;
        }

        $input = $rawData['input'];

        if (isset($input['_']) === false)
        {
            return;
        }

        $metadata = $input['_'];

        $this->trace->info(
            TraceCode::PAYMENT_METADATA,
            ['metadata' => $metadata, 'payment_id' => $rawData['payment_id']]);

        $entities = [
            Entity::CHECKOUT_ID,
            Entity::LIBRARY,
            Entity::LIBRARY_VERSION,
            Entity::PLATFORM,
            Entity::PLATFORM_VERSION,
            Entity::INTEGRATION,
            Entity::INTEGRATION_VERSION,
        ];

        // set analytics data
        foreach ($entities as $entity) {
            $log[$entity] = isset($metadata[$entity]) ? $metadata[$entity] : null;
        }

        if (isset($metadata[Entity::CHECKOUT_ID]))
        {
            $log[Entity::ATTEMPTS] = $this->calculatePaymentAttempts($metadata[Entity::CHECKOUT_ID]);
        }

        $anomalies = [];

        $entities = [
            Entity::BROWSER,
            Entity::OS,
            Entity::OS_VERSION,
            Entity::DEVICE,
        ];

        // Give preference to value passed from frontend over that parsed from user-agent
        foreach ($entities as $entity) {
            if (isset($metadata[$entity]) and isset($log[$entity]))
            {
                // collect anomalies
                $this->collectMismatch($log[$entity], $metadata[$entity], $entity, $anomalies);

                $log[$entity] = $metadata[$entity];
            }
        }

        // log anomalies
        if (empty($anomalies) === false)
        {
            $this->trace->info(TraceCode::PAYMENT_USER_AGENT_ANOMALY, $anomalies);
        }
    }

    /**
     * @param string $davalueFromFrontend
     * @param string $valueFromUserAgent
     * @param string $dataPoint
     */
    protected function collectMismatch($valueFromFrontend, $valueFromUserAgent,
                                        $dataPoint, array & $anomalies)
    {
        if (isset($valueFromFrontend) and
            isset($valueFromUserAgent) and
            strcasecmp($valueFromFrontend, $valueFromUserAgent) !== 0)
        {
             $anomalies[$dataPoint] = [
                                        'valueFromFrontend' => $valueFromFrontend,
                                        'valueFromUserAgent' => $valueFromUserAgent
                                      ];
        }
    }

    protected function calculatePaymentAttempts($checkoutId)
    {
        if ($checkoutId === null)
        {
            return;
        }

        $oldPayments = $this->repo->payment_analytics
                                    ->getRecentMerchantPaymentsForCheckoutId($checkoutId);

        $oldPaymentsGroupedByPaymentId = $oldPayments->groupBy(Entity::PAYMENT_ID);

        $count = $oldPaymentsGroupedByPaymentId->count();

        if (($count > 0) and
            ($count !== $oldPayments->first()->getAttempts()))
        {
            $this->trace->warning(
                TraceCode::PAYMENT_CHECKOUT_INVALID_ID,
                $checkoutId);

            return;
        }

        $attempts = $count + 1;

        return $attempts;
    }

    protected function setHttpRequestData(array & $log)
    {
        // get user-agent service
        $uAgent = $this->uAgent;

        $log[Entity::BROWSER] = $uAgent->browser();

        if (isset($log[Entity::BROWSER]))
        {
            $log[Entity::PLATFORM_VERSION] = $uAgent->version($uAgent->browser());
        }

        $log[Entity::OS] = $uAgent->platform();

        $log[Entity::OS_VERSION] = $uAgent->version($uAgent->platform());

        $log[Entity::DEVICE] = $this->getDeviceValue($uAgent);

        // get the HTTP request
        $request = $this->app['request'];

        $log[Entity::IP] = $request->ip();

        if ($request->header(HttpRequestHeader::REFERER) !== null)
        {
            $log[Entity::REFERER] = $request->header(HttpRequestHeader::REFERER);
        }

        if ($request->header(HttpRequestHeader::USER_AGENT) !== null)
        {
            $log[Entity::USER_AGENT] = $request->header(HttpRequestHeader::USER_AGENT);
        }
    }

    protected function getDeviceValue($uAgent)
    {
        if ($uAgent->isMobile())
        {
            $device = Metadata::MOBILE;
        }
        else if($uAgent->isDesktop())
        {
            $device = Metadata::DESKTOP;
        }
        else if ($uAgent->isTablet())
        {
            $device = Metadata::TABLET;
        }

        return $device;
    }

}
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
        $action = (new Analytics\Core)->create($input);

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

    public function getAuditsForPaymentAndTerminal($payment_id, $terminal_id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($paymentId, $terminalId);

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

        if (isset($metadata[Entity::CHECKOUT_ID]))
        {
            $log[Entity::CHECKOUT_ID] = $metadata[Entity::CHECKOUT_ID];

            $log[Entity::ATTEMPTS] = $this->calculatePaymentAttempts($metadata[Entity::CHECKOUT_ID]);
        }

        $log[Entity::LIBRARY] = isset($metadata[Entity::LIBRARY]) ? $metadata[Entity::LIBRARY] : null;

        $log[Entity::LIBRARY_VERSION] = isset($metadata[Entity::LIBRARY_VERSION]) ? $metadata[Entity::LIBRARY_VERSION] : null;

        $log[Entity::PLATFORM] = isset($metadata[Entity::PLATFORM]) ? $metadata[Entity::PLATFORM] : null;

        $log[Entity::PLATFORM_VERSION] = isset($metadata[Entity::PLATFORM_VERSION]) ? $metadata[Entity::PLATFORM_VERSION] : null;

        $log[Entity::INTEGRATION] = isset($metadata[Entity::INTEGRATION]) ? $metadata[Entity::INTEGRATION] : null;

        $log[Entity::INTEGRATION_VERSION] = isset($metadata[Entity::INTEGRATION_VERSION]) ? $metadata[Entity::INTEGRATION_VERSION] : null;

        $anomalies = [];

        // Give preference to value passed from frontend over that parsed from user-agent
        if (isset($metadata[Entity::BROWSER]) and isset($log[Entity::BROWSER]))
        {
            // log if  frontend value is different from user-agent value
            $this->collectMismatch($log[Entity::BROWSER], $metadata[Entity::BROWSER], Entity::BROWSER, $anomalies);

            $log[Entity::BROWSER] = $metadata[Entity::BROWSER];
        }

        if (isset($metadata[Entity::OS]) and isset($log[Entity::OS]))
        {
            $this->collectMismatch($log[Entity::OS], $metadata[Entity::OS], Entity::OS, $anomalies);

            $log[Entity::OS] = $metadata[Entity::OS];
        }

        if (isset($metadata[Entity::OS_VERSION]) and isset($log[Entity::OS_VERSION]))
        {
            $this->collectMismatch($log[Entity::OS_VERSION], $metadata[Entity::OS_VERSION], Entity::OS_VERSION, $anomalies);

            $log[Entity::OS_VERSION] = $metadata[Entity::OS_VERSION];
        }

        if (isset($metadata[Entity::DEVICE]) and isset($log[Entity::DEVICE]))
        {
            $this->collectMismatch($log[Entity::DEVICE], $metadata[Entity::DEVICE], Entity::DEVICE, $anomalies);

            $log[Entity::DEVICE] = $metadata[Entity::DEVICE];
        }

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
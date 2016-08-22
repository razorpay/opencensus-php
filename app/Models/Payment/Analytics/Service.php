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
        if (isset($rawData['input']))
        {
            $input = $rawData['input'];

            $metadata = isset($input['_']) ? $input['_'] : [];

            $this->trace->info(
                TraceCode::PAYMENT_METADATA,
                ['metadata' => $metadata, 'payment_id' => $rawData['payment_id']]);

            // set checkout_id
            if (isset($metadata[Entity::CHECKOUT_ID]))
            {
                $log[Entity::CHECKOUT_ID] = $metadata[Entity::CHECKOUT_ID];

                // set attempts
                $log[Entity::ATTEMPTS] = $this->calculatePaymentAttempts($metadata[Entity::CHECKOUT_ID]);
            }

            // set library
            if (isset($metadata[Entity::LIBRARY]))
            {
                $log[Entity::LIBRARY] = $metadata[Entity::LIBRARY];
            }

            // set platform
            if (isset($metadata[Entity::PLATFORM]))
            {
                $log[Entity::PLATFORM] = $metadata[Entity::PLATFORM];
            }
        }

        $this->setHttpRequestData($log);
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
        $app = \App::getFacadeRoot();

        $uAgent = $app['agent'];

        // set browser
        $log[Entity::BROWSER] = $uAgent->browser();

        // set os
        $log[Entity::OS] = $uAgent->platform();

        // set device
        $device = $this->getDeviceValue($uAgent);

        $log[Entity::DEVICE] = $device;

        // get the HTTP request
        $request = $app['request'];

        // set ip
        $ip = $request->ip();

        $log[Entity::IP] = $ip;

        // set referer
        if ($request->header(HttpRequestHeader::REFERER) !== null)
        {
            $log[Entity::REFERER] = $request->header(HttpRequestHeader::REFERER);
        }

        // set user-agent
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
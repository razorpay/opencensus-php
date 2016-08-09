<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Constants\HttpRequestHeader;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;

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
        $audits = $this->repo->payment_analytics->findForPayment($payment_id, $terminal_id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForTerminalBetween($from, $to, $id)
    {
        $audits = $this->repo->payment_analytics->findBetweenTimestampsForTerminal($from, $to, $id);

        return $audits->toArrayPublic();
    }

    public function setPaymentAnalyticData($metadata, array & $data)
    {
        // set checkout_id
        if (isset($metadata[Entity::CHECKOUT_ID]))
        {
            $data[Entity::CHECKOUT_ID] = $metadata[Entity::CHECKOUT_ID];

            // set attempts
            $data = $this->setPaymentAttempts($data, $metadata[Entity::CHECKOUT_ID]);
        }

        // set library
        if (isset($metadata[Entity::LIBRARY]))
        {
            $data[Entity::LIBRARY] = $metadata[Entity::LIBRARY];
        }

        // set platform
        if (isset($metadata[Entity::PLATFORM]))
        {
            $data[Entity::PLATFORM] = $metadata[Entity::PLATFORM];
        }

        $data = $this->setHttpRequestData($data);
    }

    protected function setPaymentAttempts(array & $data, $checkoutId)
    {
        if ($checkoutId === null)
        {
            return;
        }

        $oldPayments = $this->repo->payment_analytics->getRecentMerchantPaymentsForCheckoutId($checkoutId);

        $count = $oldPayments->count();

        if (($count > 0) and
            ($count !== $oldPayments->first()->getAttempt()))
        {
            $this->trace->warning(
                TraceCode::PAYMENT_CHECKOUT_INVALID_ID,
                $checkoutId);

            return;
        }

        $attempt = $count + 1;

        $data[Entity::ATTEMPTS] = $attempt;

        return $data;
    }

    protected function setHttpRequestData(array & $data)
    {
        // get user-agent service
        $app = \App::getFacadeRoot();

        $uAgent = $app['agent'];

        // set browser
        $data[Entity::BROWSER] = $uAgent->browser();

        // set os
        $data[Entity::OS] = $uAgent->platform();

        // set device
        $device = $this->getDeviceValue($uAgent);

        $data[Entity::DEVICE] = $device;

        // get the HTTP request
        $request = $app['request'];

        // set ip
        $ip = $request->ip();

        $data[Entity::IP] = $ip;

        // set referer
        if ($request->header(HttpRequestHeader::REFERER) !== null)
        {
            $data[Entity::REFERER] = $request->header(HttpRequestHeader::REFERER);
        }

        // set user-agent
        if ($request->header(HttpRequestHeader::USER_AGENT) !== null)
        {
            $data[Entity::USER_AGENT] = $request->header(HttpRequestHeader::USER_AGENT);
        }

        return $data;
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
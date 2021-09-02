<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;


use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\FreshdeskTicket;
use RZP\Models\Merchant\FreshdeskTicket\Constants;
use RZP\Exception\BadRequestValidationFailureException;

class SupportTicketFirstAgentReply extends Base
{
    const RAZORPAY_FROM_EMAILS = [
        'Integration <integrations@razorpay.com>',
        'Razorpaysandbox <support@razorpaysandbox.freshdesk.com>',
    ];

    const SECOND    = 1;
    const MINUTE    = 60 * self::SECOND;
    const HOUR      = 60 * self::MINUTE;
    const DAY       = 24 * self::HOUR;

    const FIRST_RESPONSE_TIME_STATISTICS_WINDOW = 7 * self::DAY;
    const FIRST_RESPONSE_TIME_DEFAULT           = 3 * self::DAY;

    //  mutex resources
    const MUTEX_RESOURCE_FIRST_RESPONSE_TIME_UPDATE    = 'support_dashboard_first_response_time_update_%s_%s';

    //  cache keys
    const CACHE_KEY_FIRST_RESPONSE_TIME_DATA    = 'support_dashboard_fr_time_data_cache_key_%s_%s';

    // in seconds
    const TTL = 30 * self::DAY;

    const RESPONSE_CREATED_AT = 'created_at';
    const FIRST_RESPONSE_TIME = 'first_response_time';

    public function __construct($event)
    {
        parent::__construct($event);

        $this->repo = (new FreshdeskTicket\Repository);
    }

    public function processEvent($freshdeskTicket)
    {
        $ticketId = $freshdeskTicket[FreshdeskTicket\Constants::TICKET_ID];

        $ticketEntity = $this->repo->fetch([
            FreshdeskTicket\Constants::TICKET_ID    => $ticketId,
        ])->firstOrFail();

        (new Merchant\Detail\Core)->getMerchantAndSetBasicAuth($ticketEntity->getMerchantId());


        $converations = (new FreshdeskTicket\Service)->getConversations($ticketEntity->getId(), [], FreshdeskTicket\Type::SUPPORT_DASHBOARD);

        try
        {
            $firstResponseTime = $this->calculateFirstResponseTime($ticketEntity, $converations);

            $this->updateFirstResponseTimeStatistics($firstResponseTime, $freshdeskTicket);
        }
        catch (BadRequestValidationFailureException $exception)
        {
            $this->trace->info(TraceCode::FRESHDESK_SUPPORT_DASHBOARD_WEBHOOK_NO_FIRST_RESPONSE, []);
        }

        return [Constants::SUCCESS => true];
    }

    protected function getRedactedInput($input)
    {
        return $input;
    }

    protected function calculateFirstResponseTime($ticketEntity, $conversations)
    {
        $firstRazorpayResponse = $this->getFirstRazorpayResponse($conversations);

        $firstRazorpayRespondedAt = strtotime($firstRazorpayResponse[self::RESPONSE_CREATED_AT]);

        $ticketCreatedAt = $ticketEntity->getCreatedAt();

        if ($ticketCreatedAt > $firstRazorpayRespondedAt)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                Merchant\FreshdeskTicket\Entity::CREATED_AT,
                null,
                'first response time cannot be negative'
            );
        }

        return $firstRazorpayRespondedAt - $ticketCreatedAt;
    }

    protected function updateFirstResponseTimeStatistics(int $firstResponseTime, array $freshdeskTicket)
    {
        $dimensions = $this->getFirstResponseTimeDimensions($freshdeskTicket);

        $mutexResource = $this->getFirstResponseTimeDataMutexResource($dimensions);

        $this->app['api.mutex']->acquireAndRelease(
            $mutexResource, function () use ($firstResponseTime, $freshdeskTicket, $dimensions) {

                $firstResponseTimeData = $this->updateFirstResponseTimeData($firstResponseTime, $dimensions);

                $this->updateFirstResponseTimeAverage($firstResponseTimeData, $dimensions);
        });

    }

    protected function getFirstRazorpayResponse($conversations)
    {
        foreach ($conversations as $conversation)
        {
            if ($this->isRazorpayResponse($conversation) === true)
            {
                return $conversation;
            }
        }

        throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_ERROR);
    }

    protected function isRazorpayResponse($conversation)
    {
        $fromEmail = $conversation[FreshdeskTicket\Constants::FROM_EMAIL] ?? '';

        return (in_array($fromEmail, self::RAZORPAY_FROM_EMAILS, true) === true);
    }

    protected function updateFirstResponseTimeData(int $firstResponseTime, array $dimensions)
    {
        $cacheKey = sprintf(self::CACHE_KEY_FIRST_RESPONSE_TIME_DATA,
            $dimensions[FreshdeskTicket\Constants::CF_REQUESTOR_SUBCATEGORY],
            $dimensions[FreshdeskTicket\Constants::PRIORITY]);

        $firstResponseTimeData = $this->app['cache']->get($cacheKey) ?? [];

        $firstResponseTimeData = $this->removeResponseTimeDataBeforeWindowStart($firstResponseTimeData);

        array_push($firstResponseTimeData, [
            self::RESPONSE_CREATED_AT            => time(),
            self::FIRST_RESPONSE_TIME            => $firstResponseTime,
        ]);

        $this->app['cache']->put($cacheKey, $firstResponseTimeData, self::TTL);

        return $firstResponseTimeData;
    }

    protected function updateFirstResponseTimeAverage($firstResponseTimeData, array $dimensions)
    {
        if (count ($firstResponseTimeData) === 0)
        {
            return;
        }

        $average = $this->calculateFirstResponseTimeAverage($firstResponseTimeData);

        $this->setFirstResponseTimeAverage($dimensions, $average);

        $this->trace->info(TraceCode::FRESHDESK_FIRST_RESPONSE_TIME_UPDATED, [
            'dimensions'    => $dimensions,
            'average'       => $average,
        ]);
    }

    protected function removeResponseTimeDataBeforeWindowStart($firstResponseTimeData)
    {
        $result = [];

        $windowStart = time() - self::FIRST_RESPONSE_TIME_STATISTICS_WINDOW;

        foreach ($firstResponseTimeData as $data)
        {
            if ($data[self::RESPONSE_CREATED_AT] < $windowStart)
            {
                continue;
            }

            array_push($result, $data);
        }

        return $result;
    }

    protected function getFirstResponseTimeDataMutexResource($dimensions)
    {
        return sprintf(self::MUTEX_RESOURCE_FIRST_RESPONSE_TIME_UPDATE,
            $dimensions[FreshdeskTicket\Constants::CF_REQUESTOR_SUBCATEGORY],
            $dimensions[FreshdeskTicket\Constants::PRIORITY]);
    }

    protected function calculateFirstResponseTimeAverage($firstResponseTimeData)
    {
        $sum = 0;

        foreach ($firstResponseTimeData as $data)
        {
            $sum += $data[self::FIRST_RESPONSE_TIME];
        }

        $average = $sum / count($firstResponseTimeData);

        return $average;
    }


    protected function setFirstResponseTimeAverage(array $dimensions, float $average): void
    {
        $cacheKey = $this->getFirstResponseTimeAverageCacheKey($dimensions);

        $this->app['cache']->put($cacheKey, $average, self::TTL);
    }
}

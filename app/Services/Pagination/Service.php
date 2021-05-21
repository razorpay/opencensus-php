<?php

namespace RZP\Services\Pagination;

use App;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Service as MerchantService;

class Service
{
    public function __construct()
    {
        $this->app =  App::getFacadeRoot();

        $this->entity = new Entity();

        $this->trace = $this->app['trace'];
    }

    public function startTrimProcess(): array
    {
        $this->entity->setAttribute(Entity::RUN_FOR, 'trim_space');

        $response = $this->init();

        if (is_null($response) !== true)
        {
            return $response;
        }

        $result = (new MerchantService())->fixData($this->entity);

        $this->moveAheadIfApplicable();

        return $result;
    }

    /**
     * Complete setup for pagination service.
     * i.e Fetch pagination parameters,
     * set pagination entity
     *
     * @throws Exception\LogicException
     */
    public function init(): ?array
    {
        $response = $this->setPaginationParameters();

        if (is_null($response) !== true)
        {
            return $response;
        }

        $this->entity->build();

        return null;
    }

    /**
     * Fetch pagination parameters and set all the attributes in
     * pagination entity.
     *
     * @throws Exception\LogicException
     */
    public function setPaginationParameters(): ?array
    {
        $paginationAttributes = (new AdminService)->getConfigKey(
            ['key' => $this->entity->getRedisKey()]);

        if (empty($paginationAttributes) === true)
        {
            throw new Exception\LogicException(
                'Pagination parameters are not set.');
        }

        $this->trace->info(TraceCode::PAGINATION_ATTRIBUTE_FROM_REDIS,
            [
                'data' => $paginationAttributes
            ]);

        $data = $paginationAttributes;

        if ((isset($data[Entity::JOB_COMPLETED]) === true) and
            ($data[Entity::JOB_COMPLETED] === true))
        {
            return [
                'message' => "Job is complete please stop cron."
            ];
        }

        (new Validator)->validateInput(Validator::PAGINATION_PARAMETERS_FROM_REDIS, $data);

        $this->entity->setAttribute(Entity::DURATION, ((int) $data[Entity::DURATION]) ?? null);

        $this->entity->setAttribute(Entity::LIMIT, ((int)  $data[Entity::LIMIT]) ?? null);

        $this->entity->setAttribute(Entity::START_TIME, ((int)  $data[Entity::START_TIME])?? null);

        $this->entity->setAttribute(Entity::END_TIME, ((int)  $data[Entity::END_TIME]) ?? null);

        $this->entity->setAttribute(Entity::WHITELIST_MERCHANT_IDS, $data[Entity::WHITELIST_MERCHANT_IDS] ?? null);

        $this->entity->setAttribute(Entity::BLACKLIST_MERCHANT_IDS,  $data[Entity::BLACKLIST_MERCHANT_IDS] ?? null);

        $this->trace->info(TraceCode::PAGINATION_ATTRIBUTE_POPULATED,
            [
                'data' => $this->entity->getAllParams()
            ]);

        return null;
    }

    /**
     * check if end_time reached for constrains and move constrains accordingly.
     *
     */
    public function moveAheadIfApplicable()
    {
        $configKey = $this->entity->getRedisKey();

        $paginationAttributes = (new AdminService)->getConfigKey(
            ['key' => $configKey]);

        $this->trace->info(TraceCode::PAGINATION_ATTRIBUTE_FROM_REDIS,
            [
                'data' => $paginationAttributes
            ]);

        $data = $paginationAttributes;

        if ($this->entity->IsEndTimeGreaterThanCurrent() !== true)
        {
            $data[Entity::JOB_COMPLETED] = true;

            $this->sendProcessCompletionSlackAlert($data);
        }

        $data[Entity::START_TIME] = $this->entity->getCurrentEndTime();

        $setConfigInput[$configKey] = $data;

        (new AdminService)->setConfigKeys($setConfigInput);
    }

    protected function sendProcessCompletionSlackAlert($data = null)
    {
        (new SlackNotification)->send(
            'Pagination process complete for ' . $this->entity->getRunFor(),
            $data,
            null,
            1,
            'x-payouts-core-alerts'
        );
    }

    public function populateRedisKey($input): array
    {
        $this->entity->setAttribute(Entity::RUN_FOR, 'trim_space');

        $configKey = $this->entity->getRedisKey();

        $setConfigInput[$configKey] = $input;

        return (new AdminService)->setConfigKeys($setConfigInput);
    }
}

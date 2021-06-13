<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;


use RZP\Trace\TraceCode;
use RZP\Models\Workflow;
use Illuminate\Cache\RedisStore;
use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\Fraud\WebsiteChecker\Constants;

class WebsiteCheckerReply extends Base
{
    /**
     * @var RedisStore
     */
    private $redis;

    public function __construct($event)
    {
        parent::__construct($event);

        $this->redis = $this->app['cache'];
    }

    public function processEvent($freshdeskTicket)
    {
        $fdTicketId = $freshdeskTicket[Entity::TICKET_ID];

        $fdTicketIdTag = sprintf(Constants::FD_TICKET_ID_TAG_FMT, $fdTicketId);

        $wfActionSearchTags = [$fdTicketIdTag];

        $workflowActions = Workflow\Action\Entity::withAllTags($wfActionSearchTags)->get();

        $this->trace->debug(TraceCode::WEBSITE_CHECKER_DEBUG, ['workflow_actions' => $workflowActions]);

        if (count($workflowActions) !== 1)
        {
            $this->trace->alert(TraceCode::WEBSITE_CHECKER_INVALID_WORKFLOW_ACTION_COUNT, ['workflow_actions' => $workflowActions]);
            return ['success' => false];
        }

        /** @var Workflow\Action\Entity $workflowAction */
        $workflowAction = $workflowActions[0];
        $workflowAction->tag(Constants::MERCHANT_REPLIED_TAG);

        $merchantId = $workflowAction->getMerchantId();

        // remove relevant redis keys
        $this->redis->connection()->hdel(Constants::REDIS_REMINDER_MAP_NAME, $merchantId);

        return ['success' => true];
    }

    protected function getRedactedInput($input)
    {
        return $input;
    }
}

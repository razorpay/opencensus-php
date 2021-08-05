<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;


use RZP\Trace\TraceCode;
use RZP\Models\Workflow;
use RZP\Exception;
use Illuminate\Cache\RedisStore;
use RZP\Models\Merchant\FreshdeskTicket\Entity;
use RZP\Models\Merchant\Fraud\HealthChecker\Constants;

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

    public static function checkerTypeFromWorkflowTag($workflowTags): string
    {
        foreach ($workflowTags as $tag)
        {
            if (strpos(strtolower($tag), Constants::WEBSITE_CHECKER) !== false)
            {
                return Constants::WEBSITE_CHECKER;
            }
            if (strpos(strtolower($tag), Constants::APP_CHECKER) !== false)
            {
                return Constants::APP_CHECKER;
            }
        }
        throw new Exception\LogicException(
            'Workflow tag does not contain any of the checker types ' . Constants::WEBSITE_CHECKER . ' or ' . Constants::APP_CHECKER
        );
    }

    public function workflowActionsWithFdTicketId($fdTicketId)
    {
        $fdWebsiteTicketIdTag = sprintf(Constants::FD_TICKET_ID_TAG_FMT[Constants::WEBSITE_CHECKER], $fdTicketId);

        $workflowActionsForWebsite = Workflow\Action\Entity::withAllTags([$fdWebsiteTicketIdTag])->get();

        if (count($workflowActionsForWebsite) === 1)
        {
            return $workflowActionsForWebsite;
        }

        $fdAppTicketIdTag = sprintf(Constants::FD_TICKET_ID_TAG_FMT[Constants::APP_CHECKER], $fdTicketId);

        return Workflow\Action\Entity::withAllTags([$fdAppTicketIdTag])->get();
    }

    public function processEvent($freshdeskTicket)
    {
        $fdTicketId = $freshdeskTicket[Entity::TICKET_ID];

        $workflowActions = $this->workflowActionsWithFdTicketId($fdTicketId);

        $this->trace->debug(TraceCode::HEALTH_CHECKER_DEBUG, ['workflow_actions' => $workflowActions]);

        if (count($workflowActions) !== 1)
        {
            $this->trace->alert(TraceCode::HEALTH_CHECKER_INVALID_WORKFLOW_ACTION_COUNT, ['workflow_actions' => $workflowActions]);
            return ['success' => false];
        }

        /** @var Workflow\Action\Entity $workflowAction */
        $workflowAction = $workflowActions[0];

        // remove relevant redis keys
        $merchantId = $workflowAction->getMerchantId();
        $workflowTags = $workflowAction->tagNames();
        $checkerType = $this->checkerTypeFromWorkflowTag($workflowTags);

        $this->redis->connection()->hdel(Constants::REDIS_REMINDER_MAP_NAME[$checkerType], $merchantId);

        $workflowAction->tag(Constants::MERCHANT_REPLIED_TAG);

        return ['success' => true];
    }

    protected function getRedactedInput($input)
    {
        return $input;
    }
}

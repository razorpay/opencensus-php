<?php

namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class PaymentMethodChangeObserver implements WorkflowObserverInterface
{
    protected $workflowService;

    protected $entityId;

    protected $payload;

    protected $repo;

    protected $fdService;

    public function __construct($input)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->fdService        = new FDService();

        $this->entityId         = $input[Entity::ENTITY_ID];

        $this->payload          = $input [Entity::PAYLOAD];
    }

    public function onApprove(array $observerData)
    {
        $merchantId = $this->getMerchantId();

        if (key_exists(FDConstants::TICKET_ID, $observerData) &&
            key_exists(FDConstants::FD_INSTANCE, $observerData))
        {
            $fdInstance = $observerData[FDConstants::FD_INSTANCE];

            $ticket_id = $observerData[FDConstants::TICKET_ID];

            $this->fdService->postTicketReplyOnAgentBehalf($ticket_id,
                implode("<br><br>",$this->getTicketReplyContent(Constants::APPROVE, $merchantId)), $fdInstance, $merchantId);

            $this->fdService->resolveAndAddAutomatedResolvedTagToTicket($observerData[FDConstants::FD_INSTANCE], $observerData[FDConstants::TICKET_ID] );
        }
    }

    public function onClose(array $observerData)
    {

    }

    public function onReject(array $observerData)
    {

    }

    public function onCreate(array $observerData)
    {

    }

    public function getMerchantId()
    {
        return $this->entityId;
    }

    public function getTicketReplyContent(string $workflowAction, string $merchantId) : array
    {
        $merchantName = $this->repo->merchant->findOrFailPublic($merchantId)->getName() ?? "";

        if ($workflowAction === Constants::APPROVE)
        {
            $actionsPerformed = $this->getActionsPerformedOnApproval();

            return [
                "Hi {$merchantName},",
                "Thank you for raising a request with us.",
                "We would like to inform you that we have successfully " . $actionsPerformed . " for your account",
                "Do reach out to us in case of any further queries and we will be glad to assist you. ",
                "Please do take up the satisfaction survey and share your valuable feedback. Your feedback will help us serve you better ",
                "We have enhanced our support options, please visit our Support page for more details: https://razorpay.com/support.",
                "Regards,<br>Razorpay Team."
            ];
        }
    }

    protected function getActionsPerformedOnApproval()
    {
        $enabledMethods = $this->getMethods(["1", 1, true]);

        $disabledMethods = $this->getMethods(["0", 0, false]);

        $enabledString = (empty($enabledMethods) === false) ? " enabled the requested methods ".$enabledMethods : "";

        $disabledString = (empty($disabledMethods) === false) ? " disabled the requested methods ".$disabledMethods : "";

        $finalString = "";

        if ( false === empty($enabledString) && false === empty($disabledString)  )
        {
            $finalString = $enabledString ." and  ". $disabledString;
        }
        else if ((empty($enabledString) === false))
        {
            $finalString = $enabledString ;
        }
        else if ((empty($disabledString) === false))
        {
            $finalString = $disabledString ;
        }

        return $finalString;
    }

    public function getMethods($valuesToCheck) : string
    {
        $methods = array();

        foreach ($this->payload as $key => $value)
        {
            if (true === in_array($value, $valuesToCheck, true))
            {
                $methods[] = $key;
            }
        }

        return implode(", ", $methods);
    }
}

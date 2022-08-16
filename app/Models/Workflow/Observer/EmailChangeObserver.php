<?php


namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class EmailChangeObserver implements WorkflowObserverInterface
{
    protected $fdService;

    protected $entityId;

    protected $repo;

    public function __construct($input)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->entityId         = $input[Entity::ENTITY_ID];

        $this->fdService        = new FDService();
    }

    public function onApprove(array $observerData)
    {
        $merchantId = $this->getMerchantId();

        if (key_exists(FDConstants::TICKET_ID, $observerData) and
            key_exists(FDConstants::FD_INSTANCE, $observerData))
        {
            $fdInstance = $observerData[FDConstants::FD_INSTANCE];

            $ticket_id = $observerData[FDConstants::TICKET_ID];

            $this->fdService->postTicketReplyOnAgentBehalf($ticket_id,
                implode("<br><br>",$this->getTicketReplyContent(Constants::APPROVE, $merchantId)), $fdInstance,
                $merchantId);

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

    public function onExecute(array $observerData)
    {

    }

    public function getMerchantId()
    {
        return $this->entityId;
    }

    public function getTicketReplyContent(string $workflowAction, string $merchantId) : array
    {
        $merchantName = $this->repo->merchant->findOrFailPublic($merchantId)->getName() ?? "";

        if($workflowAction === Constants::APPROVE)
        {
            return [
                "Hi {$merchantName},",
                "Thank you for raising a request with us.",
                "We have successfully updated the registered email id for your account as per your request.",
                " Do reach out to us in case of any further queries and we will be glad to assist you.",
                "Please do take up the satisfaction survey and share your valuable feedback. Your feedback will help us serve you better ",
                "We have enhanced our support options, please visit our Support page for more details: https://razorpay.com/support.",
                "Regards,<br>Razorpay Team."
            ];
        }
        return array();
    }
}

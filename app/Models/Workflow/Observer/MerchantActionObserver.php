<?php


namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Models\Merchant\Action;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class MerchantActionObserver implements WorkflowObserverInterface
{

    protected $workflowService;

    protected $merchantAction;

    protected $entityId;

    protected $repo;

    protected $fdService;

    public function __construct($input)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        $this->fdService        = new FDService();

        $this->merchantAction   = $input[Entity::PAYLOAD]['action'] ?? "";

        $this->entityId         = $input[Entity::ENTITY_ID];
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
        // TODO: Implement onClose() method.
    }

    public function onReject(array $observerData)
    {
        // TODO: Implement onReject() method.
    }

    public function onCreate(array $observerData)
    {
        // TODO: Implement onCreate() method.
    }

    public function getMerchantId()
    {
        return $this->entityId;
    }

    public function getTicketReplyContent(string $workflowAction, string $merchantId) : array
    {
        $merchantName = $this->repo->merchant->findOrFailPublic($merchantId)->getName() ?? "";

        if ($this->merchantAction === Action::RELEASE_FUNDS)
        {
            if ($workflowAction === Constants::APPROVE)
            {

                return [
                    "Hi {$merchantName},",
                    "We have processed the releasing of funds for your account with our banking partners. You will receive settlements as per the said settlement cycle. We look forward to transacting with you soon! ",
                    "Please note that settlements will not be processed to your account on bank holidays. ",
                    "Also, we'd love to hear from you! You can leave your feedback through our satisfaction survey that will reach your inbox soon! ",
                    "We have enhanced our support options, please visit our Support page for more details: https://razorpay.com/support.",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        if ($this->merchantAction === Action::HOLD_FUNDS)
        {
            if ($workflowAction === Constants::APPROVE)
            {
                return [
                    "Hi {$merchantName},",
                    "Thank you for raising a request with us.",
                    "We would like to inform you that we have successfully held the funds for your account as per your request.",
                    "Do reach out to us once you wish to have the funds released again.",
                    "We have enhanced our support options, please visit our Support page for more details: https://razorpay.com/support.",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        return array();
    }
}

<?php


namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AccountStatus;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\FreshdeskTicket\Priority as FDPriority;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;


class MerchantActivationStatusObserver implements WorkflowObserverInterface
{
    protected $entityId;

    protected $activationStatus;

    protected $fdService;

    protected $repo;

    protected $app;

    protected $merchant;

    public function __construct($input)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->fdService  = new FDService();

        $this->entityId         = $input[DifferEntity::ENTITY_ID];

        $this->activationStatus   = $input[DifferEntity::PAYLOAD][MerchantDetailEntity::ACTIVATION_STATUS] ?? "";
    }

    public function onApprove(array $observerData)
    {
        $fdInstance = null;

        $ticket_id = null;

        $merchant_id = $this->getMerchantId();

        if (key_exists(FDConstants::TICKET_ID, $observerData) === false or
            key_exists(FDConstants::FD_INSTANCE, $observerData) === false)
        {
            $response = $this->fdService->postTicketOnMerchantBehalf($this->buildTicketBodyForActivationStatusChange($merchant_id),
                $merchant_id);

            $fdInstance = FDConstants::RZP;

            $ticket_id = $response[FDConstants::TICKET_ID];

            $this->app["trace"]->info(TraceCode::PERFORM_ACTION_OBSERVER_DATA, [
                FDConstants::TICKET_ID    => $ticket_id,
            ]);
        }
        else
        {
            $fdInstance = $observerData[FDConstants::FD_INSTANCE];

            $ticket_id = $observerData[FDConstants::TICKET_ID];
        }

        $this->fdService->postTicketReplyOnAgentBehalf($ticket_id,
            implode("<br><br>",$this->getTicketReplyContent(Constants::APPROVE, $merchant_id)), $fdInstance,
            $this->getMerchantId());

        $this->fdService->resolveAndAddAutomatedResolvedTagToTicket($fdInstance, $ticket_id);
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

    public function getMerchant($merchantId)
    {
        if (empty($this->merchant) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }

        return $this->merchant;
    }

    public function getTicketReplyContent(string $approve_reject, $merchantId) : array
    {
        $merchantName = $this->getMerchant($merchantId)->getName() ?? "";

        if ($this->activationStatus === AccountStatus::ACTIVATED)
        {
            if ($approve_reject === Constants::APPROVE)
            {

                return [
                    "Hi {$merchantName},",
                    "Greetings for the Day!! Thank You for Choosing Razorpay.",
                    "Your activation form has been accepted by our banking partners The bank usually takes 2 business days to enable settlements post which the funds will be settled basis your settlement cycle.",
                    " [Follow these steps to download the combined report from your dashboard to view the settlement schedule of the payments that you've accepted] : https://i.imgur.com/fcpunly.gif.We are happy to have you on-boarded on our platform and strive to deliver the best experience at Razorpay. ",
                    "Let us know how your experience has been so far by taking the survey that will be sent to your email ID.Happy transacting at Razorpay! ",
                    "We're just an email away in case you need help. [steps to raise a request with us] : https://i.imgur.com/8aeofAz.gif ",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        if ($this->activationStatus === AccountStatus::REJECTED)
        {
            if ($approve_reject === Constants::APPROVE)
            {

                return [
                    "Hi {$merchantName},",
                    "We regret to inform you that, unfortunately we would not be able to support your business as the bank has not approved your activation form.In order to mitigate future losses to Razorpay as a result of chargebacks, a reserve has been set in place on your account.",
                    "Based on the above findings we would need to terminate your account effective immediately with a hold on the funds for the chargeback period of 120 days.",
                    "We request you to kindly look for any other alternative and wish you all the best.",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        return array();
    }

    protected function buildTicketBodyForActivationStatusChange($merchant_id)
    {
        $merchant = $this->getMerchant($merchant_id);

        return [
            'email'                         =>      $merchant->getEmail(),
            'name'                          =>      $merchant->getName(),
            'description'                   =>      FDConstants::ACTIVATION_SUBJECT,
            'subject'                       =>      '[Merchant] Activation',
            FDConstants::TYPE               =>      FDConstants::SERVICE_REQUEST_TICKET_TYPE,
            FDConstants::GROUP_ID           =>      (int)$this->app['config']->get('applications.freshdesk.activation.rzp.groupId'),
            FDConstants::RESPONDER_ID       =>      (int)$this->app['config']->get('applications.freshdesk.activation.rzp.agentId'),
            FDConstants::PRIORITY           =>      FDPriority::getValueForPriorityString(FDPriority::LOW),
            FDConstants::CUSTOM_FIELDS=>
                [
                    FDConstants::CF_REQUESTOR_CATEGORY    => 'Merchant',
                    FDConstants::CF_REQUESTOR_SUBCATEGORY => 'Activation',
                    FDConstants::CF_CATEGORY              => FDConstants::ACTIVATION_CF_CATEGORY,
                    FDConstants::CF_SUBCATEGORY           => FDConstants::ACTIVATION_CF_SUBCATEGORY,
                    FDConstants::CF_TICKET_QUEUE          => FDConstants::MERCHANT_TICKET_QUEUE,
                    FDConstants::CF_PRODUCT               => FDConstants::PAYMENT_GATEWAY_CF_PRODUCT,
                ]
        ];
    }

}

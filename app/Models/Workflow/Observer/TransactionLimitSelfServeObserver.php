<?php


namespace RZP\Models\Workflow\Observer;

use App;
use Illuminate\Support\Facades\Mail;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Models\Workflow\Action\Differ\Entity;

class TransactionLimitSelfServeObserver implements WorkflowObserverInterface
{
    protected $entityId;

    protected $repo;

    public function __construct($input)
    {
        $app            = App::getFacadeRoot();

        $this->repo     = $app['repo'];

        $this->entityId = $input[Entity::ENTITY_ID];
    }

    public function onApprove(array $observerData)
    {

    }

    public function onClose(array $observerData)
    {

    }

    public function onReject(array $observerData)
    {
        if (key_exists(Constants::REJECTION_REASON, $observerData) === true)
        {
            $rejectionReason      = json_decode($observerData[Constants::REJECTION_REASON], true);

            $messageBody          = $rejectionReason[Constants::MESSAGE_BODY];

            $messageSubject       = $rejectionReason[Constants::MESSAGE_SUBJECT];

            $merchant             = $this->getMerchant();

            $merchantPrimaryOwner = $merchant->primaryOwner()->toArrayPublic();

            $mailClassInstance    = (new MerchantMail\RejectionReasonNotification($merchantPrimaryOwner, $messageSubject, $messageBody));

            Mail::queue($mailClassInstance);
        }
    }

    public function onCreate(array $observerData)
    {

    }

    public function getMerchantId()
    {
        return $this->entityId;
    }

    public function getMerchant()
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->getMerchantId());

        return $merchant;
    }
}

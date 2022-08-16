<?php

namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Models\Card\Network;
use RZP\Models\Merchant\Methods\UpiType;
use RZP\Models\Merchant\Methods\EmiType;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Methods\Core as MethodsCore;
use RZP\Models\Merchant\Methods\Entity as MethodEntity;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class PaymentMethodChangeObserver implements WorkflowObserverInterface
{
    protected $workflowService;

    protected $entityId;

    protected $payload;

    protected $repo;

    protected $fdService;

    protected $fullNames;

    protected $app;

    protected $merchantMethods;

    protected $paymentMethodWithNestedStructure = [MethodEntity::CARD_NETWORKS];

    public function __construct($input)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->fdService = new FDService();

        $this->entityId = $input[Entity::ENTITY_ID];

        $this->payload = $input [Entity::PAYLOAD];

        $this->fullNames = Network::$fullName;

        (new DetailCore())->getMerchantAndSetBasicAuth($this->getMerchantId());

        $this->merchantMethods = (new MethodsCore())->getPaymentMethods($this->app['basicauth']->getMerchant());
    }

    public function getMerchantId()
    {
        return $this->entityId;
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
                                                           implode("<br><br>", $this->getTicketReplyContent(Constants::APPROVE, $merchantId)), $fdInstance, $merchantId);

            $this->fdService->resolveAndAddAutomatedResolvedTagToTicket($observerData[FDConstants::FD_INSTANCE], $observerData[FDConstants::TICKET_ID]);
        }
    }

    public function getTicketReplyContent(string $workflowAction, string $merchantId): array
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
        $enabledMethods = $this->getMethods(["1", 1, true], false);

        $disabledMethods = $this->getMethods(["0", 0, false], true);

        $enabledString = (empty($enabledMethods) === false) ? " enabled the requested methods " . $enabledMethods : "";

        $disabledString = (empty($disabledMethods) === false) ? " disabled the requested methods " . $disabledMethods : "";

        $finalString = "";

        if (empty($enabledString) === false and
            empty($disabledString) === false)
        {
            $finalString = $enabledString . " and  " . $disabledString;
        }
        else
        {
            if ((empty($enabledString) === false))
            {
                $finalString = $enabledString;
            }
            else
            {
                if ((empty($disabledString) === false))
                {
                    $finalString = $disabledString;
                }
            }
        }

        return $finalString;
    }

    public function getMethods($valuesToCheck, $previousState): string
    {
        $methods = [];

        foreach ($this->payload as $key => $value)
        {
            if (in_array($value, $valuesToCheck, true) === true)
            {
                $methods[] = $key;
            }
        }

        /* normally
         * netbanking => true
         * debit_card => true
         *
         * but cards has
         * card_networks => {"AMEX"=>1, "DICL":0,"MC"=>1}
         * nested structure
         *
         * but emi has
         * emi => ["debit","credit"]
         * */
        $nestedMethodsArray = $this->checkForNestedMethods($valuesToCheck, $previousState);

        $methods = array_merge($methods, $nestedMethodsArray);

        return implode(", ", $methods);
    }

    protected function checkForNestedMethods($valuesToCheck, $previousState)
    {
        $methods = [];

        foreach ($this->paymentMethodWithNestedStructure as $nestedNetwork)
        {
            if (array_key_exists($nestedNetwork, $this->payload) === true)
            {
                $methodsPerNetwork = [];

                foreach ($this->payload[$nestedNetwork] as $key => $value)
                {
                    if (in_array($value, $valuesToCheck, true) === true)
                    {
                        $methodsPerNetwork[] = $this->fullNames[$key];
                    }
                }

                if (empty($methodsPerNetwork) === false)
                {
                    $methods[] = $nestedNetwork . " (" . implode(", ", $methodsPerNetwork) . ")";
                }
            }
        }

        if (array_key_exists(MethodEntity::EMI, $this->payload) === true)
        {
            $methods = array_merge($methods, $this->handleEmi($previousState, $valuesToCheck));
        }
        if (array_key_exists(MethodEntity::UPI_TYPE, $this->payload) === true)
        {
            $methods = array_merge($methods, $this->handleUpiType($previousState, $valuesToCheck));
        }

        return $methods;
    }

    protected function handleEmi(bool $previousState, $valuesToCheck)
    {
        $methods = [];

        if ($this->merchantMethods->isCreditEmiEnabled() === $previousState)
        {
            if (array_key_exists(EmiType::CREDIT, $this->payload[MethodEntity::EMI]) === true and
                in_array($this->payload[MethodEntity::EMI][EmiType::CREDIT], $valuesToCheck, true) === true)
            {
                $methodsPerNetwork[] = EmiType::CREDIT;
            }
        }

        if ($this->merchantMethods->isDebitEmiEnabled() === $previousState)
        {
            if (array_key_exists(EmiType::DEBIT, $this->payload[MethodEntity::EMI]) === true and
                in_array($this->payload[MethodEntity::EMI][EmiType::DEBIT], $valuesToCheck, true) === true)
            {
                $methodsPerNetwork[] = EmiType::DEBIT;
            }
        }

        if (empty($methodsPerNetwork) === false)
        {
            $methods[] = "emi (" . implode(", ", $methodsPerNetwork) . ")";
        }

        return $methods;
    }

    protected function handleUpiType(bool $previousState, $valuesToCheck)
    {
        $methods = [];

        if ($this->merchantMethods->isUpiIntentEnabled() === $previousState)
        {
            if (array_key_exists(UpiType::INTENT, $this->payload[MethodEntity::UPI_TYPE]) === true and
                in_array($this->payload[MethodEntity::UPI_TYPE][UpiType::INTENT], $valuesToCheck, true) === true)
            {
                $methodsPerNetwork[] = UpiType::INTENT;
            }
        }

        if ($this->merchantMethods->isUpiCollectEnabled() === $previousState)
        {
            if (array_key_exists(UpiType::COLLECT, $this->payload[MethodEntity::UPI_TYPE]) === true and
                in_array($this->payload[MethodEntity::UPI_TYPE][UpiType::COLLECT], $valuesToCheck, true) === true)
            {
                $methodsPerNetwork[] = UpiType::COLLECT;
            }
        }

        if (empty($methodsPerNetwork) === false)
        {
            $methods[] = "upi type (" . implode(", ", $methodsPerNetwork) . ")";
        }

        return $methods;
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
}

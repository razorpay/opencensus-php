<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Dispute\Core as DisputeCore;
use RZP\Models\Dispute\Reason\Network;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Dispute\Reason\Entity as DisputeReasonEntity;
use RZP\Models\Dispute\Reason\Service as DisputeReasonService;


class Processor
{
	use Renderer, Ticket;

	private $app;

	private $repo;

	private $trace;

	private $freshdeskTicket;

	private $freshdeskCustomerDisputeConfig;

	private $payment;

	private $merchant;

	public function __construct(Entity $freshdeskTicket)
    {
    	$this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

    	$this->freshdeskTicket = $freshdeskTicket;

        $this->freshdeskCustomerDisputeConfig = $this->app['config']->get('applications.freshdesk.customer.dispute');
    }

    public function process()
    {
    	$paymentId = $this->freshdeskTicket->getPaymentId();

    	try
        {
            // The format validation is done as part of entity build
            PaymentEntity::stripSignWithoutValidation($paymentId);

            $this->assignAutomationAgentToTicket();

            $this->payment = $this->repo->payment->findWithRelations($paymentId, ['merchant']);

            if (is_null($this->payment) === true)
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_PAYMENT_NOT_FOUND]);

                $this->changeTicketGroupToCustomerSupport();

                return ['success' => true];
            }

            $this->merchant = $this->payment->merchant;

            if ($this->payment->isFailed() === true)
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_PAYMENT_FAILED]);

                $this->handlePaymentFailed();
            }
            else if (is_null($this->payment->getCapturedAt()) === true)
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_PAYMENT_NOT_CAPTURED]);

                $this->handlePaymentNotCaptured();
            }
            else if ($this->payment->isFullyRefunded() === true)
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_PAYMENT_FULLY_REFUNDED]);

                $this->handlePaymentFullyRefunded();
            }
            else if ($this->payment->isDisputed() === true)
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_PAYMENT_DISPUTED]);

                $this->handlePaymentAlreadyDisputed();
            }
            else if (
                ($this->merchant->isFundsOnHold() === true || $this->merchant->isActivated() === false) &&
                ($this->payment->getAmount() > $this->merchant->primaryBalance->getBalance())
            )
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_MERCHANT_DISABLED]);

                $this->handleMerchantDisabled();
            }
            else
            {
                $this->trace->info(TraceCode::FRESHDESK_DISPUTE_TICKET_ACTION, ['action' => Constants::ACTION_CREATE_DISPUTE]);

                $this->handleCreateDispute();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FRESHDESK_DISPUTE_REQUEST_ERROR
            );

            $this->changeTicketGroupToCustomerSupport();

            return ['success' => false];
        }

        return ['success' => true];
    }

    private function handlePaymentFailed()
    {
        $this->changeTicketGroupToCustomerSupport();
    }

    private function handlePaymentNotCaptured()
    {
        $renderedBody = $this->renderPaymentNotCapturedBody();

        $this->replyToTicket($renderedBody);

        $this->closeTicket();
    }

    private function handlePaymentFullyRefunded()
    {
        $renderedBody = $this->renderPaymentFullyRefundedBody();

        $this->replyToTicket($renderedBody);

        $this->closeTicket();
    }

    private function handlePaymentAlreadyDisputed()
    {
        $renderedBody = $this->renderPaymentAlreadyDisputedBody();

        $this->replyToTicket($renderedBody);

        $this->closeTicket();
    }

    private function handleMerchantDisabled()
    {
        $renderedBody = $this->renderMerchantDisabledBody();

        $this->replyToTicket($renderedBody);

        $this->closeTicket();
    }

    private function handleCreateDispute()
    {
        $reasonDetail = ReasonCode::REASON_CODE_MAP[$this->freshdeskTicket->getSubcategory()][$this->freshdeskTicket->getReasonCode()];

        $reason = (new DisputeReasonService())->getReasonByNetworkAndGatewayCode(Network::RZP, $reasonDetail[DisputeReasonEntity::GATEWAY_CODE]);

        $createDisputeInput = [
            DisputeEntity::GATEWAY_DISPUTE_ID => 'DISPUTE' . $this->freshdeskTicket->getTicketId(),
            DisputeEntity::RAISED_ON          => Carbon::now()->format('U'),
            DisputeEntity::EXPIRES_ON         => Carbon::now()->addDays(Constants::DISPUTE_EXPIRES_AFTER)->format('U'),
            DisputeEntity::PHASE              => $reasonDetail[DisputeEntity::PHASE],
            DisputeEntity::GATEWAY_AMOUNT     => $this->payment->getAmount(),
            DisputeEntity::GATEWAY_CURRENCY   => $this->payment->getCurrency(),
            DisputeEntity::REASON_ID          => $reason->getId(),
            DisputeEntity::BACKFILL           => false,
        ];

        (new DisputeCore())->create($this->payment, $reason, $createDisputeInput);

        $renderedBody = $this->renderCreateDisputeBody();

        $this->replyToTicket($renderedBody);

        $this->changeTicketGroupToCspWithRelevantTags();
    }
}

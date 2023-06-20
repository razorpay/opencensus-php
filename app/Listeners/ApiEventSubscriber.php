<?php

namespace RZP\Listeners;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Event;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payout;
use RZP\Models\QrCode;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Terminal;
use Razorpay\Trace\Logger;
use RZP\Models\PaymentLink;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\CardMandate;
use RZP\Models\Customer\Token;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Downtime;
use RZP\Models\Merchant\Product;
use RZP\Models\Order\ProductType;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\ServerErrorException;
use RZP\Jobs\Invoice\Job as InvoiceJob;
use RZP\Jobs\OneCCShopifyCreateOrder;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;
use RZP\Models\Merchant\Account\Entity as AccountEntity;
use RZP\Models\Merchant\WebhookV2\Metric as WebhookMetric;

class ApiEventSubscriber extends Base\Core
{
    /**
     * Event being fired
     * @var string
     */
    protected $event;

    /**
     * For invoice.paid, the mainEntity would consist of the invoice entity.
     * The data from mainEntity is used to get updated_at and merchant.
     *
     * @var Base\PublicEntity
     */
    protected $mainEntity;

    /**
     * For invoice.paid, the withPayload can consist of payment and order.
     * These are like helper entities or extra information for the merchant.
     *
     * @var array
     */
    protected $withPayload;

    /**
     * For most events, merchant can be derived from the entity itself.
     * For others, the merchant may be passed in the listener input.
     *
     * @var Merchant\Entity
     */
    protected $listeningMerchant;

    /**
     * Webhook\Stork is initialized with a product(i.e. banking, primary).
     * @var string|null
     */
    protected $storkProduct = Constants\Product::PRIMARY;

    const MAIN        = 'main';
    const WITH        = 'with';
    const MERCHANT_ID = 'merchant_id';

    const WORKFLOW_SERVICE = 'workflow_service';
    const API_WORKFLOW     = 'api_workflow';
    const BARRICADE_ACTION = 'merchant_integration_verify';
    const BARRICADE_MERCHANT_INTEGRATION = 'barricade_merchant_integration';

    public function getMode()
    {
        return $this->app['rzp.mode'];
    }

    public function onEvent($event, $params)
    {
        $startAt = millitime();

        $event = $this->getFiringEvent($event);

        $this->trace->count(WebhookMetric::WEBHOOK_EVENTS_TRIGGERED_TOTAL, compact('event'));

        //
        // sequential_array check is present here only
        // to ensure backward compatibility.
        //
        if (is_sequential_array($params) === true)
        {
            $this->mainEntity = $params[0];
        }
        else
        {
            $this->mainEntity  = $params[self::MAIN];
            $this->withPayload = $params[self::WITH] ?? [];

            //
            // Webhooks can be triggered for shared entities,
            // i.e. entities that do not belong to a specific
            // merchant. In this case, merchant will be part of the input.
            //
            if (isset($params[self::MERCHANT_ID]) === true)
            {
                $merchantId = $params[self::MERCHANT_ID];

                $this->listeningMerchant = $this->repo->merchant->findOrFail($merchantId);
            }
        }

        //
        // Some events can send multiple entities in the array.
        // The first entity should be the main entity and the others
        // should be helper entities only.
        // For example, if invoice events sends 3 entities,
        // the first one should be of invoice and the second
        // and third should be of payment and order.
        //
        // We use allParams to construct the payload.
        // We use the first param to get the associated merchant,
        // updated_at and other things like that.
        //

        $event = str_replace('.', '_', $event);

        $func = 'on' . studly_case($event);

        $this->$func($this->mainEntity);

        $this->trace->histogram(
            WebhookMetric::EVENT_PROCESS_DURATION_MILLISECONDS,
            millitime() - $startAt,
            ['event' => $event, 'via_stork' => true]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     *
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('api.*', 'RZP\Listeners\ApiEventSubscriber@onEvent');
    }

    protected function getFiringEvent($event)
    {
        // This is being done because the event names start with "api."
        $event = substr($event, 4);

        $this->event = $event;

        return $event;
    }

    protected function onAccountSuspended($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);

        // disable all direct settlement terminals of this merchant
        $terminals = $this->repo->terminal->getActivatedDirectSettlementTerminalsByMerchant($merchant->getId());

        $terminalCore = new Terminal\Core;

        foreach ($terminals as $terminal)
        {
            try
            {
                $terminalCore->disableTerminal($terminal);
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::TERMINAL_DISABLE_EXCEPTION,
                    [
                        'merchant_id' => $merchant->getId(),
                        'terminal_id' => $terminal->getId(),
                    ]);
            }
        }

        $this->dispatchEventToPlService($merchant);
    }

    protected function onProductPaymentGatewayActivated($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentGatewayNeedsClarification($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentGatewayInstantlyActivated($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentGatewayUnderReview($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentGatewayRejected($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentGatewayActivatedKycPending($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentLinksActivated($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentLinksNeedsClarification($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentLinksInstantlyActivated($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentLinksUnderReview($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentLinksRejected($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductPaymentLinksActivatedKycPending($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductRouteActivated($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductRouteNeedsClarification($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductRouteUnderReview($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onProductRouteRejected($merchantProduct)
    {
        $payload = $this->getMerchantProductPayload($merchantProduct);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountNoDocOnboardingGmvLimitWarning($merchant)
    {
        $payload = $this->withPayload;

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountInstantActivationGmvLimitWarning($merchant)
    {
        $payload = $this->withPayload;

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountUnsuspended($merchant)
    {
        $this->dispatchEventToPlService($merchant);
    }

    protected function onAccountActivatedMccPending($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountKycQualifiedUnactivated($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountActivatedKycPending($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountInstantlyActivated($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountUnderReview($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountNeedsClarification($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountActivated($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountRejected($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountInternationalEnabled($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountInternationalDisabled($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountFundsHold($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountFundsUnhold($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountPaymentsEnabled($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountPaymentsDisabled($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountUpdated(Merchant\Account\Entity $account)
    {
        $payload = $this->getAccountPayload($account);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountMappedToPartner($merchant)
    {
        $payload = $this->getMerchantPayload($merchant);

        $this->dispatchEventToStork($payload);
    }

    protected function onAccountAppAuthorizationRevoked($merchant)
    {
        $this->dispatchEventToStork($this->withPayload, Constants\Entity::APPLICATION);
    }

    protected function onPaymentAuthorized($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        if (($payment->hasSubscription() === true) and
            ($payment->isApiBasedEmandateAsyncPayment() === false))
        {
            $paymentPayload = $this->constructPaymentPayloadForSubscriptionNotification($payment);

            $this->app['module']->subscription->paymentProcess($paymentPayload, $this->getMode());
        }

        // Removed reportInitialPayment from here,
        // Moved it to postTokenisationRecurringPaymentProcessingIfApplicable
        if (($payment->isTokenisationUnhappyFlowHandlingApplicable() === false) and
            ($payment->isCardMandateRecurringInitialPayment() === true))
        {
            (new CardMandate\Core)->reportInitialPayment($payment);
        }
        elseif ($payment->hasCardMandateNotification() === true)
        {
            (new CardMandate\Core)->reportSubsequentPayment($payment);
        }

        $this->notifySubscriptionRegistrationPaymentAuthorized($payment);

        if(($payment->merchant->isFeatureEnabled(Feature\Constants::SILENT_REFUND_LATE_AUTH) === true)
            and $payment->isLateAuthorized() === true)
        {
            $this->trace->info(TraceCode::SKIP_NOTIFY_ON_LATE_AUTH);
        }
        else
        {
            $this->dispatchEventToStork($payload);
        }

        $this->dispatchOrderFor1ccShopify($payment);
    }

    protected function onPaymentFailed($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        if ($payment->hasSubscription() === true)
        {
            $paymentPayload = $this->constructPaymentPayloadForSubscriptionNotification($payment);

            $this->app['module']->subscription->paymentProcess($paymentPayload, $this->getMode());
        }

        if ($payment->isCardMandateRecurringInitialPayment() === true)
        {
            (new CardMandate\Core)->reportInitialPayment($payment);
        }
        elseif ($payment->hasCardMandateNotification() === true)
        {
            (new CardMandate\Core)->reportSubsequentPayment($payment);
        }

        $this->pushForRevival($payment);

        $this->dispatchEventToStork($payload);
    }


    /**
     * Dispatch an SQS job that attempts to create an order in Shopify for those that
     * failed due to network issues at the customer end
     * @param Payment\Entity $payment
     * @return void
     * @throws none
     */
    protected function dispatchOrderFor1ccShopify(Payment\Entity $payment)
    {
        $start = millitime();

        try
        {
            if ($payment->hasOrder() === false)
            {
                return;
            }

            $order = $payment->order;

            // Certain orders are not being dispatched to the queue
            // Splitting up the conditions to check the status temporarily
            if ($order->is1ccShopifyOrder() === true)
            {
                $dispatched = false;

                if ($payment->isAuthorized() === true)
                {
                    $dispatched = true;

                    OneCCShopifyCreateOrder::dispatch([
                        'mode'                => $this->mode,
                        'razorpay_order_id'   => $order->getPublicId(),
                        'razorpay_payment_id' => $payment->getPublicId(),
                        'merchant_id'         => $payment->getMerchantId(),
                        'type'                => 'create_order',
                        'dispatch_time'       => millitime() - $start,
                    ])->delay(now()->addMinutes(5));

                    // To debug payloads not being handled properly in sqs
                    $this->trace->info(
                        TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB,
                        [
                            'step'                => 'dispatch',
                            'type'                => 'create_order',
                            'dispatched'          => $dispatched,
                            'mode'                => $this->mode,
                            'razorpay_order_id'   => $order->getPublicId(),
                            'razorpay_payment_id' => $payment->getPublicId(),
                            'payment_method'      => $payment->getMethod(),
                            'payment_status'      => $payment->getStatus(),
                            'merchant_id'         => $payment->getMerchantId(),
                            'from'                => 'ApiEventSubscriber',
                        ]);
                }
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_DISPATCH_JOB_FAILED,
                [
                    'payment_id' => $payment->getPublicId()
                ]);
        }
    }

    private function pushForRevival(Payment\Entity $payment)
    {
        (new Payment\Core())->pushFailedPaymentForRevival($payment);
    }

    protected function onPaymentCaptured($payment)
    {
        if ($payment->hasPaymentLink() === true)
        {
            (new PaymentLink\Core)->postPaymentCaptureUpdatePaymentPage($payment);
        }

        if ($this->isForNocodeApps($payment))
        {
            (new PaymentLink\Core)->handleNocodeAppsPaymentEvent($payment);
        }

        if($payment->order !== null)
        {
            try
            {
                $invoice = $payment->order->invoice;

                if($invoice !== null)
                {
                    (new Invoice\Core)->updateInvoiceAfterCapture($invoice, $payment);
                }
                else if ($payment->order->getProductType() === ProductType::INVOICE)
                {
                    $invoiceId = $payment->order->getProductId();

                    $invoice = $this->repo->invoice->findOrFailPublic($invoiceId);

                    (new Invoice\Core)->updateInvoiceAfterCapture($invoice, $payment);
                }
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::INVOICE_ACTION_JOB_ERROR,
                    [
                        'payment_id' => $payment->getId(),
                    ]);
            }
        }

        try
        {
            $this->pushForPaymentLinks($payment);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::ORDER_NOTIFY_FAILED_FOR_PAYMENT_LINK_V2,
                [
                    'payment_id' => $payment->getId(),
                ]);
        }

        $payload = $this->getPaymentPayload($payment);
        $this->dispatchEventToStork($payload);
    }

    protected function isForNocodeApps(Payment\Entity $payment): bool
    {
        return $payment->hasOrder() === true and ($payment->order->getProductType() === ProductType::PAYMENT_STORE);
    }


    protected function pushForPaymentLinks(Payment\Entity $payment)
    {

        if ($payment->hasOrder() === true and
            ($payment->order->getProductType() === ProductType::PAYMENT_LINK_V2))
        {
            $order = $payment->order;

            $this->trace->info(
                TraceCode::ORDER_PAID_FOR_PAYMENT_LINK_V2,
                [
                    'order'    => $order,
                ]);

            $plService = $this->app['paymentlinkservice'];

            try
            {
                $plService->notifyOrderPaid($order, $payment);
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::ORDER_NOTIFY_FAILED_FOR_PAYMENT_LINK_V2,
                    [
                        'payment_id' => $payment->getId(),
                        'order_id'   => $order->getId(),
                    ]);
            }

        }

    }

    protected function onPaymentCreated($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentPending($payment)
    {
        $payload = $this->getPaymentPayload($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDisputeCreated($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDisputeLost($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDisputeWon($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDisputeClosed($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDisputeUnderReview($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDisputeActionRequired($payment)
    {
        $payload = $this->getPaymentPayloadWithDispute($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onFundAccountValidationCompleted(FundAccount\Validation\Entity $fundAccountValidation)
    {
        $payload = $this->getFundAccountValidationPayload($fundAccountValidation);

        $this->dispatchEventToStork($payload);
    }

    protected function onFundAccountValidationFailed(FundAccount\Validation\Entity $fundAccountValidation)
    {
        $payload = $this->getFundAccountValidationPayload($fundAccountValidation);

        $this->dispatchEventToStork($payload);
    }

    protected function onOrderPaid($payment)
    {
        $payload = $this->getOrderPayload($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onTransferProcessed(Transfer\Entity $transfer)
    {
        $payload = $this->getTransferPayload($transfer);

        $this->dispatchEventToStork($payload);
    }

    protected function onTransferFailed(Transfer\Entity $transfer)
    {
        $payload = $this->getTransferPayload($transfer);

        $this->dispatchEventToStork($payload);
    }

    protected function onTransferSettled($transfer)
    {
        $payload = $this->getTransferPayloadWithSettlement($transfer);

        $this->dispatchEventToStork($payload);
    }

    protected function onVirtualAccountCredited(Payment\Entity $payment)
    {
        $payload = $this->getVirtualAccountPaymentPayload($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onVirtualAccountCreated(VirtualAccount\Entity $virtualAccount)
    {
        $payload = $this->getVirtualAccountPayload($virtualAccount);

        $this->dispatchEventToStork($payload);
    }

    protected function onVirtualAccountClosed(VirtualAccount\Entity $virtualAccount)
    {
        $payload = $this->getVirtualAccountPayload($virtualAccount);

        $this->dispatchEventToStork($payload);
    }

    protected function onQrCodeClosed(QrCode\Entity $qrCode)
    {
        $payload = $this->getQrCodePayload($qrCode);

        $this->dispatchEventToStork($payload);
    }

    protected function onQrCodeCreated(QrCode\Entity $qrCode)
    {
        $payload = $this->getQrCodePayload($qrCode);

        $this->dispatchEventToStork($payload);
    }

    protected function onQrCodeCredited(Payment\Entity $payment)
    {
        $payload = $this->getQrCodePaymentPayload($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onInvoicePartiallyPaid($payment)
    {
        //
        // It is safe to just call the other method which gets called with
        // invoice.paid event. The web hook payload is same in both event (it's
        // invoice, order, payment entities), just the event name differs.
        //
        $this->onInvoicePaid($payment);
    }

    protected function onInvoicePaid($payment)
    {
        // Pulls customer info from payment and updates invoice's if not set
        (new Invoice\Core)->setCustomerDetailsFromPaymentIfAbsent($payment);

        // Fires a job so in async pdf can be refreshed
        InvoiceJob::dispatch($this->getMode(), InvoiceJob::CAPTURED, $payment->getInvoiceId());

        $payload = $this->getInvoicePayloadWithPayment($payment);
        $this->dispatchEventToStork($payload);
    }

    protected function onInvoiceExpired($invoice)
    {
        $payload = $this->getInvoicePayload($invoice);

        $this->dispatchEventToStork($payload);
    }

    protected function onSubscriptionActivated($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->dispatchEventToStork($payload);
    }

    protected function onSubscriptionPending($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->dispatchEventToStork($payload);
    }

    protected function onSubscriptionHalted($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->dispatchEventToStork($payload);
    }

    protected function onSubscriptionCancelled($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->dispatchEventToStork($payload);
    }

    protected function onSubscriptionCompleted($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->dispatchEventToStork($payload);
    }

    protected function onSubscriptionCharged($subscription)
    {
        $payload = $this->getSubscriptionPayload($subscription);

        $this->dispatchEventToStork($payload);
    }

    // protected function onSubscriptionExpired($subscription)
    // {
    //     $payload = $this->getSubscriptionPayload($subscription);
    //
    //     $this->dispatchEventToStork($payload);
    // }

    protected function onVpaEdited($vpa)
    {
        $payload = $this->getVpaPayload($vpa);

        $this->dispatchEventToStork($payload);
    }

    protected function onP2pCreated($p2p)
    {
        $payload = $this->getP2pPayload($p2p);

        $this->dispatchEventToStork($payload);
    }

    protected function onP2pRejected($p2p)
    {
        $payload = $this->getP2pPayload($p2p);

        $this->dispatchEventToStork($payload);
    }

    protected function onP2pTransferred($p2p)
    {
        $payload = $this->getP2pPayload($p2p);

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenConfirmed($token)
    {
        $payload = $this->getTokenPayload($token);

        $this->triggerCallToSubscriptionForEmandateAsyncGateway($token, 'onTokenConfirmed');

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenRejected($token)
    {
        $payload = $this->getTokenPayload($token);

        $this->triggerCallToSubscriptionForEmandateAsyncGateway($token, 'onTokenRejected');

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenPaused($token)
    {
        $payload = $this->getTokenPayload($token);

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenCancelled($token)
    {
        $payload = $this->getTokenPayload($token);

        $this->dispatchEventToStork($payload);
    }

    protected function onSettlementProcessed($settlement)
    {
        $payload = $this->getSettlementPayload($settlement);

        $this->dispatchEventToStork($payload);
    }

    protected function onTransactionCreated(Transaction\Entity $txn)
    {
        $payload = $this->getTransactionPayload($txn);

        $this->dispatchEventToStork($payload);
    }

    protected function onTransactionUpdated(Transaction\Entity $txn)
    {
        $payload = $this->getTransactionPayload($txn);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutLinkIssued(PayoutLinkEntity $payoutLink)
    {
        $payload = $this->getPayoutLinkPayload($payoutLink);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutLinkProcessed(PayoutLinkEntity $payoutLink)
    {
        $payload = $this->getPayoutLinkPayload($payoutLink);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutLinkProcessing(PayoutLinkEntity $payoutLink)
    {
        $payload = $this->getPayoutLinkPayload($payoutLink);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutLinkAttempted(PayoutLinkEntity $payoutLink)
    {
        $payload = $this->getPayoutLinkPayload($payoutLink);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutLinkCancelled(PayoutLinkEntity $payoutLink)
    {
        $payload = $this->getPayoutLinkPayload($payoutLink);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutCreated(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);

        $this->dispatchEventToStork($payload);
    }

    protected function onRefundProcessed(RefundEntity $refund)
    {
        if ($refund->payment->hasPaymentLink() === true)
        {
            (new PaymentLink\Core)->postPaymentRefundUpdatePaymentPageDispatcher($refund);
        }

        if ($this->isForNocodeApps($refund->payment))
        {
            (new PaymentLink\Core)->handleNocodeAppsPaymentEvent($refund->payment);
        }

        $payload = $this->getRefundPayload($refund);

        $this->dispatchEventToStork($payload);

        $this->handleQrPaymentUpdate($refund);
    }

    protected function onRefundCreated(RefundEntity $refund)
    {
        $payload = $this->getRefundPayload($refund);

        $this->dispatchEventToStork($payload);
    }

    protected function onRefundFailed(RefundEntity $refund)
    {
        $payload = $this->getRefundPayload($refund);

        $this->dispatchEventToStork($payload);
    }

    protected function onRefundSpeedChanged(RefundEntity $refund)
    {
        $payload = $this->getRefundPayload($refund);

        $this->dispatchEventToStork($payload);
    }

    protected function onRefundArnUpdated(RefundEntity $refund)
    {
        $payload = $this->getRefundPayload($refund);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutProcessed(Payout\Entity $payout)
    {
        if ($payout->isOfMerchantTransaction() === true)
        {
            (new Transaction\Notifier($payout->transaction, $this->event))->notify();
        }

        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutQueued(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutUpdated(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutRejected(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);

        $payload = $this->getPayoutRejectCommentInPayload($payout, $payload);

        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutPending(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutInitiated(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutReversed(Payout\Entity $payout)
    {
         if (($payout->isOfMerchantTransaction() === true) and
             ($payout->isBalanceAccountTypeDirect() === false))
         {
             (new Transaction\Notifier($payout->transaction, $this->event))->notify();
         }

        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPayoutFailed(Payout\Entity $payout)
    {
        $payload = $this->getPayoutPayload($payout);
        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDowntimeStarted(Downtime\Entity $downtime)
    {
        $payload = $this->getPaymentDowntimePayload($downtime);
        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDowntimeUpdated(Downtime\Entity $downtime)
    {
        $payload = $this->getPaymentDowntimePayload($downtime);
        $this->dispatchEventToStork($payload);
    }

    protected function onPaymentDowntimeResolved(Downtime\Entity $downtime)
    {
        $payload = $this->getPaymentDowntimePayload($downtime);
        $this->dispatchEventToStork($payload);
    }

    protected function onTerminalCreated(Terminal\Entity $terminal)
    {
        $payload = $this->getTerminalCreatedPayload($terminal);
        $this->dispatchEventToStork($payload);
    }

    protected function onTerminalActivated(Terminal\Entity $terminal)
    {
        $payload = $this->getTerminalActivatedPayload($terminal);
        $this->dispatchEventToStork($payload);
    }

    protected function onTerminalFailed(Terminal\Entity $terminal)
    {
        $payload = $this->getTerminalFailedPayload($terminal);
        $this->dispatchEventToStork($payload);
    }

    protected function onZapierPaymentPagePaidV1(Payment\Entity $payment)
    {
        $this->onPaymentPagePartnerWebhookEvent($payment);
    }

    protected function onShiprocketPaymentPagePaidV1(Payment\Entity $payment)
    {
        $this->onPaymentPagePartnerWebhookEvent($payment);
    }

    protected function onPaymentPagePartnerWebhookEvent(Payment\Entity $payment)
    {
        $paymentPageCore = new PaymentLink\Core();

        $payload = $paymentPageCore->constructPayloadForPartnerWebhook($payment);

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenServiceProviderActivated($token)
    {
        $payload = $this->getTokenServiceProviderPayload($token);

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenServiceProviderCancelled($token)
    {
        $payload = $this->getTokenServiceProviderPayload($token);

        $this->dispatchEventToStork($payload);
    }

    protected function onTokenServiceProviderDeactivated($token)
    {
        $payload = $this->getTokenServiceProviderPayload($token);

        $this->dispatchEventToStork($payload);
    }

    // payouts can be rejected with comment in workflows. passing that comment in payload for consumption by merchant.
    protected function getPayoutRejectCommentInPayload(Payout\Entity $payout, array $payload): array
    {
        $merchantId = $this->getMerchantFromEntity($this->mainEntity)->getId();

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::PAYOUTS_REJECT_COMMENT_IN_WEBHOOK_FILTER,
            $this->mode,
            Payout\Entity::RAZORX_RETRY_COUNT
        );

        if (strtolower($variant) === 'on')
        {
            // For merchants who are onboarded to WFS, reject comment can be found by doing ->toArrayPublic on payout entity.
            $payoutArrayPublic = $payout->toArrayPublic();

            if ((array_key_exists(Adapter\Constants::WORKFLOW_HISTORY, $payoutArrayPublic) === true) and
                (array_key_exists(Adapter\Constants::WORKFLOW_STATES, $payoutArrayPublic[Adapter\Constants::WORKFLOW_HISTORY]) === true))
            {
                $workflowStates = $payoutArrayPublic[Adapter\Constants::WORKFLOW_HISTORY][Adapter\Constants::WORKFLOW_STATES];

                $userComment = $this->processWorkflowServiceStatesForUserComment($workflowStates);

                $payload[Payout\Entity::PAYOUT][Payout\Entity::ENTITY][Payout\Entity::FAILURE_REASON] = $userComment;

                $this->trace->info(
                    TraceCode::REJECT_PAYOUT_WITH_COMMENT_IN_WEBHOOK,
                    [
                        self::MERCHANT_ID               => $merchantId,
                        Payout\Entity::ID               => $payout->getId(),
                        Adapter\Constants::COMMENT      => $userComment,
                        Adapter\Constants::SERVICE      => self::WORKFLOW_SERVICE,
                    ]);

                return $payload;
            }

            // For merchants who aren't onboarded to WFS reject comment can be found from action checker table.
            $userComment = $this->repo->workflow_action->fetchUserComment($payout->getId(), Payout\Entity::PAYOUT);

            $payload[Payout\Entity::PAYOUT][Payout\Entity::ENTITY][Payout\Entity::FAILURE_REASON] = $userComment;

            $this->trace->info(
                TraceCode::REJECT_PAYOUT_WITH_COMMENT_IN_WEBHOOK,
                [
                    self::MERCHANT_ID               => $merchantId,
                    Payout\Entity::ID               => $payout->getId(),
                    Adapter\Constants::COMMENT      => $userComment,
                    Adapter\Constants::SERVICE      => self::API_WORKFLOW,
                ]);
        }

        return $payload;
    }

    protected function processWorkflowServiceStatesForUserComment(array $workflowStates)
    {
        foreach ($workflowStates as $workflowState)
        {
            if (array_key_exists(Adapter\Constants::WORKFLOW_ACTIONS, $workflowState) === true)
            {
                $userComment = $this->processWorkflowServiceActionsForUserComment($workflowState[Adapter\Constants::WORKFLOW_ACTIONS]);

                // This if condition is required as function processWorkflowServiceActionsForUserComment returns null when comment is not found but we want to continue traversing.
                if ($userComment !== null)
                {
                    return $userComment;
                }
            }
        }

        return null;
    }

    protected function processWorkflowServiceActionsForUserComment(array $workflowActions)
    {
        foreach ($workflowActions as $workflowAction)
        {
            if (($workflowAction[Adapter\Constants::ACTION_TYPE] == Adapter\Constants::REJECTED) and
                array_key_exists(Adapter\Constants::COMMENT, $workflowAction))
            {
                return $workflowAction[Adapter\Constants::COMMENT];
            }
        }

        return null;
    }

    protected function onBankingAccountsIssued($merchant)
    {
        $merchantId = $merchant->getId();

        $bankingAccounts = $this->repo->banking_account->fetchMerchantBankingAccounts($merchantId);

        $va = current(array_filter($bankingAccounts, function($account) {
            return $account['account_type'] === 'nodal';
        }));

        $ca = array_filter($bankingAccounts, function($account) {
            return ($account['account_type'] === 'current' and
                    $account['status'] === 'activated');
        });

        $caPayload = $this->generateCurrentAccountsPayload($ca);

        $vaPayload = [
            Entity::ACCOUNT_NUMBER => $va[Entity::ACCOUNT_NUMBER],
        ];

        $payload = [
            'accounts' => [
                'virtual' => $vaPayload,
            ]
        ];

        if (empty($caPayload) === false)
        {
            $payload['accounts'] += ['current' => $caPayload];
        }

        $this->storkProduct = Constants\Product::BANKING;

        return $this->dispatchEventToStork($payload);
    }

    protected function generateCurrentAccountsPayload(array $bankingAccounts = [])
    {
        $ca = [];

        foreach ($bankingAccounts as $ba)
        {
            array_push($ca, [Entity::CHANNEL => $ba[Entity::CHANNEL], Entity::ACCOUNT_NUMBER => $ba[Entity::ACCOUNT_NUMBER]]);
        }

        return $ca;
    }

    protected function getP2pPayload($p2p)
    {
        $source = $p2p->source;

        $sink = $p2p->sink;

        $partialPayload[Constants\Entity::P2P] = [
            'entity' => $p2p->toArrayPublic()
        ];

        $partialPayload['source'] = [
            'entity' => $source->toArrayPublic()
        ];

        $partialPayload['sink'] = [
            'entity' => $sink->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getTokenPayload(Token\Entity $token)
    {
        $payload = [
            Constants\Entity::TOKEN => [
                'entity' => $token->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getSubscriptionPayload($subscription)
    {
        $partialPayload[Constants\Entity::SUBSCRIPTION] = [
            'entity' => $subscription->toArrayPublic()
        ];

        $this->addExtraDataToPayload($partialPayload);

        return $partialPayload;
    }

    protected function getVpaPayload($vpa)
    {
        $customer = $vpa->customer;

        $bankAccount = $vpa->bankAccount;

        $partialPayload[Constants\Entity::VPA] = [
            'entity' => $vpa->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::CUSTOMER] = [
            'entity' => $customer->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::BANK_ACCOUNT] = [
            'entity' => $bankAccount->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getOrderPayload($payment)
    {
        $order = $payment->order;

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::ORDER] = [
            'entity' => $order->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getTransferPayload(Transfer\Entity $transfer)
    {
        $partialPayload[Constants\Entity::TRANSFER] = [
            'entity' => $transfer->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getQrCodePaymentPayload(Payment\Entity $payment)
    {
        $receiver = $payment->getReceiver();

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic()
        ];

        $qrCodeArray = $receiver->toArrayPublic();

        $partialPayload[Constants\Entity::QR_CODE] = [
            'entity' => $qrCodeArray,
        ];

        return $partialPayload;
    }

    protected function getVirtualAccountPaymentPayload(Payment\Entity $payment)
    {
        $receiver = $payment->receiver;

        if ($payment->isOffline() === true) {

            $virtualAccount = $receiver->virtualAccount;
        }
        else {
            $virtualAccount = $receiver->source;
        }


        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic()
        ];

        $virtualAccountArray = $virtualAccount->toArrayPublic();

        //
        // The virtual account array received here will contain
        // all the receivers but we only want that receiver on
        // which the payment is received
        //
        unset($virtualAccountArray[VirtualAccount\Entity::RECEIVERS]);

        $virtualAccountArray[VirtualAccount\Entity::RECEIVERS] = [
            $receiver->toArrayPublic()
        ];

        $partialPayload[Constants\Entity::VIRTUAL_ACCOUNT] = [
            'entity' => $virtualAccountArray,
        ];

        if ($payment->isBankTransfer() === true)
        {
            $bankTransfer = $payment->bankTransfer;

            $partialPayload[$bankTransfer->getEntity()] = [
                'entity' => $bankTransfer->toArrayPublic(),
            ];
        }

        if ($payment->isUpiTransfer() === true)
        {
            $upiTransfer = $payment->upiTransfer;

            $partialPayload[$upiTransfer->getEntity()] = [
                'entity' => $upiTransfer->toArrayPublic(),
            ];
        }

        return $partialPayload;
    }

    protected function getVirtualAccountPayload(VirtualAccount\Entity $virtualAccount)
    {
        $partialPayload[Constants\Entity::VIRTUAL_ACCOUNT] = [
            'entity' => $virtualAccount->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getQrCodePayload(QrCode\Entity $qrCode)
    {
        $partialPayload[Constants\Entity::QR_CODE] = [
            'entity' => $qrCode->toArrayPublic()
        ];

        return $partialPayload;
    }

    protected function getFundAccountValidationPayload(FundAccount\Validation\Entity $fundAccountValidation)
    {
        $partialPayload['fund_account.validation'] = [
            'entity' => $fundAccountValidation->toArrayPublic()
        ];

        try
        {
            $this->setMerchantProductForStork($fundAccountValidation->merchant,
                optional($fundAccountValidation->balance)->getType());
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e, Logger::WARNING, TraceCode::STORK_PRODUCT_SET_FAILED);
        }

        return $partialPayload;
    }

    protected function getInvoicePayload(Invoice\Entity $invoice)
    {
        $payload = [
            Constants\Entity::INVOICE => [
                'entity' => $invoice->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getInvoicePayloadWithPayment($payment)
    {
        $order   = $payment->order;
        $invoice = $order->invoice;

        $partialPayload[Constants\Entity::PAYMENT] = [
            'entity' => $payment->toArrayPublic(),
        ];

        $partialPayload[Constants\Entity::ORDER] = [
            'entity' => $order->toArrayPublic(),
        ];

        $partialPayload[Constants\Entity::INVOICE] = [
            'entity' => $invoice->toArrayPublic(),
        ];

        return $partialPayload;
    }

    protected function getMerchantPayload($merchant)
    {
        //loading merchantDetail in memory
        $merchant->merchantDetail;

        $payload = [
            Constants\Entity::ACCOUNT => [
                'entity' => $merchant->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getAccountPayload($account)
    {
        $payload = [
            Constants\Entity::ACCOUNT => [
                'entity' => $account->toArrayPublic(),
            ],
            Constants\Entity::BANK_ACCOUNT => [
                'entity' => $this->withPayload->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getMerchantProductPayload(Product\Entity $merchantProduct)
    {
        $entity = [];

        $entity[Product\Entity::ID]                = $merchantProduct->getPublicId();
        $entity[Product\Entity::MERCHANT_ID]       = AccountEntity::getSignedId($merchantProduct->getMerchantId());
        $entity[Product\Entity::ACTIVATION_STATUS] = $merchantProduct->getStatus();

        $payload = [
            Constants\Entity::MERCHANT_PRODUCT => [
                'entity' => $entity,
                'data'   => $this->withPayload
            ],
        ];

        return $payload;
    }

    protected function getPaymentPayload($payment)
    {
        $payload = [
            Constants\Entity::PAYMENT => [
                'entity' => $payment->toArrayWebhook(),
            ],
        ];

        $order = $payment->order;

        $merchant = $payment->merchant;

        // for backward compatibility with new pl service, payment entity needs to have invoice_id in webhook payload
        // as few merchants depend on this field.
        if ((isset($order) === true) and
            (isset($merchant) === true) and
            ($order->getProductType() === ProductType::PAYMENT_LINK_V2) and
            ($merchant->isFeatureEnabled(Feature\Constants::PAYMENTLINKS_COMPATIBILITY_V2) === true))
        {
            $invoiceId = $order->getProductId();

            $payload[Constants\Entity::PAYMENT]['entity'][Payment\Entity::INVOICE_ID] = Invoice\Entity::getSignedId($invoiceId);
        }

        return $payload;
    }

    protected function getRefundPayload(RefundEntity $refund)
    {
        $payment = $refund->payment;

        $payload = [
            Constants\Entity::REFUND => [
                'entity' => $refund->toArrayPublic(),
            ],
            Constants\Entity::PAYMENT => [
                'entity' => $payment->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getSettlementPayload($settlement)
    {
        $payload = [
            Constants\Entity::SETTLEMENT => [
                'entity' => $settlement->toArrayPublic(),
            ],
        ];

        return $payload;
    }

    protected function getTransferPayloadWithSettlement(Transfer\Entity $transfer)
    {
        $notes = $transfer->getNotes();

        $data = [
            Transfer\Entity::NOTES                  => ($notes !== null) ? $notes->toArray() : [],
            Transfer\Entity::LINKED_ACCOUNT_NOTES   => $transfer->getLinkedAccountNotes(),
        ];

        $payload = [
            Constants\Entity::SETTLEMENT => [
                'entity' => $this->withPayload->toArrayPublic(),
            ],
            Constants\Entity::TRANSFER => [
                'entity' => [
                    Transfer\Entity::ID                     => $transfer->getPublicId(),
                    Transfer\Entity::LINKED_ACCOUNT_NOTES   => (new Transfer\Core())->getLinkedAccountNotes($data),
                ],
            ],
        ];

        return $payload;
    }

    protected function getTransactionPayload(Transaction\Entity $txn): array
    {
        $payload = [
            Constants\Entity::TRANSACTION => [
                'entity' => $txn->toStatement()->toArrayPublic(),
            ],
        ];

        try
        {
            $this->setMerchantProductForStork($txn->merchant, optional($txn->accountBalance)->getType());
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e, Logger::WARNING, TraceCode::STORK_PRODUCT_SET_FAILED);
        }

        return $payload;
    }

    protected function getPayoutLinkPayload(PayoutLinkEntity $payoutLink): array
    {
        try
        {
            $this->setMerchantProductForStork($payoutLink->merchant, $payoutLink->balance->getType());
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e, Logger::WARNING, TraceCode::STORK_PRODUCT_SET_FAILED);
        }

        return [
            Constants\Entity::PAYOUT_LINK => [
                'entity' => $payoutLink->toArrayPublic(),
            ],
        ];
    }

    protected function getPayoutPayload(Payout\Entity $payout): array
    {
        try
        {
            $this->setMerchantProductForStork($payout->merchant, $payout->balance->getType());
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e, Logger::WARNING, TraceCode::STORK_PRODUCT_SET_FAILED);
        }

        $payload[Constants\Entity::PAYOUT] = [
            'entity' => $payout->toArrayWebhook(),
        ];

        return $payload;
    }

    protected function getPaymentPayloadWithDispute($payment)
    {
        $partialPayload = $this->getPaymentPayload($payment);

        // Add dispute entity defined in `withPayload`
        $this->addExtraDataToPayload($partialPayload);

        return $partialPayload;
    }

    protected function getPaymentDowntimePayload(Downtime\Entity $downtime): array
    {
        $downtimeArrayPublic = $downtime->toArrayPublic();

        $sendMerchantDowntimesInWebhooks = (bool) $this->withPayload;

        if($sendMerchantDowntimesInWebhooks === true)
        {
            $sendMerchantDowntimesInWebhooks = $this->listeningMerchant->isFeatureEnabled(Feature\Constants::ENABLE_GRANULAR_DOWNTIMES);
        }

        if(!$sendMerchantDowntimesInWebhooks)
        {
            (new Downtime\Service())->removeGranularDowntimeKeysFromEntity($downtimeArrayPublic);
        }

        $payload = [
            Constants\Entity::PAYMENT_DOWNTIME => [
                'entity' => $downtimeArrayPublic,
            ]
        ];

        return $payload;
    }

    protected function getTerminalCreatedPayload(Terminal\Entity $terminal): array
    {
        $payload = [
            Constants\Entity::TERMINAL => [
                'entity' => $terminal->toArrayPublic(),
            ]
        ];

        return $payload;
    }

    protected function getTerminalActivatedPayload(Terminal\Entity $terminal): array
    {
        $payload = [
            Constants\Entity::TERMINAL => [
                'entity' => $terminal->toArrayPublic(),
            ]
        ];

        return $payload;
    }

    protected function getTerminalFailedPayload(Terminal\Entity $terminal): array
    {
        $payload = [
            Constants\Entity::TERMINAL => [
                'entity' => $terminal->toArrayPublic(),
            ]
        ];

        return $payload;
    }

    protected function createEventEntity(array $payload): Event\Entity
    {
        $eventFired = $this->event;
        $entity     = $this->mainEntity;
        $merchant   = $this->getMerchantFromEntity($entity);
        //
        // Send the signed account id of the merchant associated with the entity, along with the payload
        // In case of settlements, $entity->merchant is the the merchant to whom the settlement is processed
        //
        $listeningMerchant = $this->getListeningMerchant($entity);
        $signedAccountId = Merchant\Account\Entity::getSignedId($listeningMerchant->getId());

        $attributes = array(
            Event\Entity::EVENT      => $eventFired,

            //
            // The same event may or may not contain some entities, based on the state.
            // For example, if subscription.pending is fired on an auth failure,
            // the payload will contain only subscription entity not contain `payment` entity.
            // If it's fired on capture failure, it'll contain both subscription and payment
            // entity. For this reason, we cannot have a static list of contains array.
            //
            Event\Entity::ACCOUNT_ID => $signedAccountId,
            Event\Entity::CONTAINS   => array_keys($payload),
            Event\Entity::CREATED_AT => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);
        $event->generateId();

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);

        return $event;
    }

    protected function addExtraDataToPayload(array & $partialPayload)
    {
        foreach ($this->withPayload as $withKey => $withValue)
        {
            //
            // This check is required since sometimes, the entity could be null.
            // In those cases, we don't want to send the entity key at all
            // in the webhook payload.
            //
            if ($withValue instanceof Base\PublicEntity)
            {
                $partialPayload[$withKey] = [
                    'entity' => $withValue->toArrayPublic()
                ];
            }
        }
    }

    /**
     * Returns the entity's merchant.
     * If the merchant is a linked account, returns the parent merchant.
     *
     * @param Base\PublicEntity $entity
     *
     * @return Merchant\Entity
     */
    protected function getMerchantFromEntity(Base\PublicEntity $entity): Merchant\Entity
    {
        $merchant = $this->getListeningMerchant($entity);

        if ($merchant->isLinkedAccount() === true)
        {
            $merchant = $merchant->parent;
        }

        return $merchant;
    }

    protected function getListeningMerchant(Base\PublicEntity $entity): Merchant\Entity
    {
        if ($this->listeningMerchant !== null)
        {
            return $this->listeningMerchant;
        }

        if ((($entity instanceof Merchant\Account\Entity) === true) or
            (($entity instanceof Merchant\Entity) === true))
        {
            $merchant = $entity;
        }
        else
        {
            $merchant = $entity->merchant;
        }

        return $merchant;
    }

    protected function constructPaymentPayloadForSubscriptionNotification(Payment\Entity $payment): array
    {
        $payload = $payment->toArrayAdmin();

        $payload['merchant'] = [
            Merchant\Entity::BILLING_LABEL => $payment->merchant->getBillingLabel(),
            Merchant\Entity::WEBSITE       => $payment->merchant->getWebsite(),
            Merchant\Entity::EMAIL         => $payment->merchant->getTransactionReportEmail(),
        ];

        $customerEmail = null;
        $customerContact = null;

        if ($payment->customer !== null)
        {
            $customerEmail = $payment->customer->getEmail();
            $customerContact = $payment->customer->getContact();
        }

        $payload['customer'] = [
            'email' => $customerEmail,
            'phone' => $customerContact,
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;
            $expiryMonth = str_pad($card->getExpiryMonth(), 2, '0', STR_PAD_LEFT);

            $payload['card'] = [
                'number'  => '**** **** **** ' . $card->getLast4(),
                'expiry'  => $expiryMonth . '/' . $card->getExpiryYear(),
                'network' => $card->getNetworkCode(),
                'color'   => $card->getNetworkColorCode()
            ];
        }

        if ($payment->hasInvoice() === true)
        {
            $payload['invoice'] = [
                Invoice\Entity::BILLING_START => $payment->invoice->getBillingStart(),
                Invoice\Entity::BILLING_END   => $payment->invoice->getBillingEnd()
            ];
        }

        $paidOffer = $payment->getOffer();

        if($paidOffer !== null)
        {
            $discountAmount = $paidOffer->getDiscountAmountForPayment($payment->order->getAmount(), $payment);

            $paidOfferSubscription = $this->repo->offer->fetchSubscriptionOfferById($paidOffer->getId());

            // TODO Change to gettter after offer team approval
            $paidOfferSubscriptionDetails = [
                'id'              => $paidOffer->getId(),
                'name'            => $paidOfferSubscription->getName(),
                'payment_method'  => $paidOfferSubscription->getPaymentMethod(),
                'applicable_on'   => $paidOfferSubscription['applicable_on'],
                'redemption_type' => $paidOfferSubscription['redemption_type'],
                'no_of_cycles'    => $paidOfferSubscription['no_of_cycles'],
            ];

            $payload['offer'] = [
                'order_amount'      => $payment->order->getAmount(),
                'discounted_amount' => $discountAmount,
                'offer_details'     => $paidOfferSubscriptionDetails,
            ];
        }

        $token = $payment->localToken;
        if (empty($token) === false)
        {
            $cardMandateId = $token->getCardMandateId();

            if ($cardMandateId !== null)
            {
                $payload['card_mandate_id'] = $cardMandateId;
            }
        }

        return $payload;
    }

    /**
     * Dispatches event to stork where dispatch-able webhooks are resolved and
     * events are fired to all of them.
     * @param array  $payload
     * @param string $ownerType
     */
    protected function dispatchEventToStork(array $payload, string $ownerType = Constants\Entity::MERCHANT)
    {
        $event = $this->createEventEntity($payload);
        (new Stork($this->getMode(), $this->storkProduct))->processEventSafe($event, $ownerType);
    }

    /**
     * Sets storkProduct attribute to banking if balanceType is such but temporarily
     * controlled via a feature. This should be removed later.
     *
     * @param Merchant\Entity $merchant
     * @param string|null     $balanceType
     *
     * @return void
     */
    protected function setMerchantProductForStork(Merchant\Entity $merchant, $balanceType)
    {
        // feature flag check is for stork migration
        if ($balanceType === Merchant\Balance\Type::BANKING)
        {
            $this->trace->info(TraceCode::STORK_DISPATCH_EVENT_REQUEST_PRODUCT_BANKING,
                               ['merchant_id' => $merchant->getId()]);

            $this->storkProduct = Constants\Product::BANKING;

            return;
        }

        // default value
        $this->storkProduct = Constants\Product::PRIMARY;
    }

    /**
     * Send events to pl service where some actions are to be done.
     * eg : evict merchant cache in case merchant is suspended
     * @param Merchant\Entity  $merchant
     * @param array $payload
     */
    protected function dispatchEventToPlService(Merchant\Entity $merchant, array $payload = [])
    {
        $plService = $this->app['paymentlinkservice'];

        $plService->notifyMerchantStatusAction($merchant);
    }

    /**
     * Associating token with subscription registration once payment is authorized
     * @param Payment\Entity  $payment
     */
    protected function notifySubscriptionRegistrationPaymentAuthorized(Payment\Entity $payment)
    {
        try
        {
            if (($payment->hasInvoice() === true) &&
                ($payment->invoice !== null) &&
                ($payment->invoice->getEntityType() === Constants\Entity::SUBSCRIPTION_REGISTRATION))
            {
                $token = $payment->getGlobalOrLocalTokenEntity();

                if ($token === null)
                {
                    return;
                }

                $subscriptionRegistration = $payment->invoice->entity;

                if ($subscriptionRegistration !== null)
                {
                    (new SubscriptionRegistration\Core)->associateToken($subscriptionRegistration, $token);
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::SUBSCRIPTION_REGISTRATION_TOKEN_ASSOCIATION,
                [
                    'payment_id' => $payment->getId(),
                ]);
        }
    }

    private function handleQrPaymentUpdate(RefundEntity $refund)
    {
        $payment = $refund->payment;

        if ($payment->qrPayment === null)
        {
            return;
        }

        $this->repo->qr_payment->syncToEs($payment->qrPayment, Base\EsRepository::UPDATE);
    }

    protected function getTokenServiceProviderPayload($token): array
    {
        $serviceProviderTokens = $this->withPayload;

        $publicToken = $token->toArrayPublicTokenizedCard($serviceProviderTokens);

        $customerId = $token->getCustomerId();

        $customer = $this->repo->customer->findById($customerId);

        $partialPayload[Constants\Entity::TOKEN] = [
            'entity' => $publicToken,
        ];

        $partialPayload[Constants\Entity::SERVICE_PROVIDER_TOKEN] = [
            'entity' => $serviceProviderTokens,
        ];

        if($token->getSource() === Token\Constants::ISSUER && isset($customer) === true)
        {
            $partialPayload[Constants\Entity::CUSTOMER] = [
                'entity' => [
                    Base\UniqueIdEntity::ID  => $customer->getId(),
                    Customer\Entity::EMAIL   => $customer->getEmail(),
                    Customer\Entity::CONTACT => $customer->getContact()
                ],
            ];
        }

        return $partialPayload;
    }

    // Dispatches notification to Subscriptions service.
    // This is for emandate API based gateways where token is confirmed in async.
    // In such cases, payment capture is trigerred only by Subscr service.
    private function triggerCallToSubscriptionForEmandateAsyncGateway($token, $notifyEvent = '')
    {
        try
        {
            if ((empty($token) === false) and ($token->getMethod() === Payment\Method::EMANDATE))
            {
                $payment = $this->repo->payment->getRecurringInitialPayment($token->getId(),
                $token->getMerchantId(), $token->getMethod());
                $currentRecurringStatus = $token->getRecurringStatus();

                if ((empty($payment) === false) and
                    (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true) and
                    (Payment\Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === true))
                {
                    $paymentPayload = $this->constructPaymentPayloadForSubscriptionNotification($payment);
                    $this->app['module']->subscription->paymentProcess($paymentPayload, $this->getMode());
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::SUBSCRIPTION_NOTIFY_FAILED,
                [
                    'payment_id'   => $payment->getId(),
                    'token_id'     => $token->getId(),
                    'notify_event' => $notifyEvent,
                ]);
        }

        return;
    }

}

<?php

namespace RZP\Gateway\Upi\Base;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Axis;
use Razorpay\Trace\Logger;
use RZP\Gateway\Base\Action;
use RZP\Http\Request\Requests;
use RZP\Exception\RuntimeException;

class Gateway extends Base\Gateway
{
    const ACQUIRER = null;
    const X_RZP_TESTCASE_ID = 'X-RZP-TESTCASE-ID';

    const RETRIABLE_ACTIONS = [
        Action::AUTHENTICATE,
        Action::VALIDATE_VPA,
        Axis\Action::FETCH_TOKEN,
        Axis\Action::COLLECT,
        Action::AUTHORIZE,
    ];

    /**
     * Used in Mock\GatewayTrait, but defined here because
     * traits can't define constants
     */
    const MOCK_ROUTE    = 'mock_upi_payment';

    protected $shouldMapLateAuthorized = true;

    protected $shouldRetryForAction = true;

    /**
     * @var string Hexadecimal for UPI
     */
    protected $gatewaySanitizeKey = '555049';

    public function isRunningOnDark(): bool
    {
        $url = $this->app['config']->get('applications.mozart.live.url');

        return starts_with($url, 'https://mozart-dark.razorpay.com');
    }

    public function disableRetryForAction()
    {
        $this->shouldRetryForAction = false;
    }

    public function isRunningOnHallmark(): bool
    {
        $url = $this->app['config']->get('applications.mozart.live.url');

        return starts_with($url, 'https://mozart-hallmark.razorpay.com');
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $createdAt = $input['payment']['created_at'] ?? null;

        // Return if created_at is null.
        if (isset($createdAt) === false)
        {
            return;
        }

        $currentTime = Carbon::now()->getTimestamp();

        // Time elapsed between payment creation and callback received.
        $totalTime = $currentTime - $createdAt;

        $this->pushGatewayMetrics($totalTime*1000);
    }

    public function redirectCallbackIfRequired(array $response, $content, $headers)
    {
        false;
    }

    protected function sendProxyRequestToDark($url, $content, $headers)
    {
        $method = 'POST';

        $options = [
            'timeout'           => 30,
            'connect_timeout'   => 10,
        ];

        $headers = flatten_array($headers);

        try
        {
            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            throw $e;
        }

        return $response;
    }

    protected function createGatewayPaymentEntity($attributes, $action = null, $shouldMap = true)
    {
        $attr = $attributes;

        if ($shouldMap === true)
        {
            $attr = $this->getMappedAttributes($attributes);
        }

        $entity = $this->getNewGatewayPaymentEntity();

        $action = $action ?? $this->action;

        switch ($action)
        {
            case Base\Action::REFUND:

                $entity->setRefundId($this->input['refund']['id']);

                $entity->setAmount($this->input['refund']['amount']);

                $entity->setPaymentId($this->input['payment']['id']);

                break;

            case Base\Action::PAYOUT:

                $entity->setPaymentId($this->input['gateway_input']['ref_id']);

                $entity->setAmount($this->input['gateway_input']['amount']);

                break;

            default:
                $entity->setAmount($this->input['payment']['amount']);

                $entity->setPaymentId($this->input['payment']['id']);
        }

        $entity->setAction($action);

        $entity->setAcquirer(static::ACQUIRER);

        $entity->setGateway($this->gateway);

        $entity->generate($attr);

        $entity->fill($attr);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    protected function createGatewayEntityForMozartGateway($attributes, $action = null)
    {
        $entity = $this->getNewGatewayPaymentEntity();

        $action = $action ?? $this->action;

        $entity->setAmount($this->input['payment']['amount']);

        $entity->setPaymentId($this->input['payment']['id']);

        $entity->setAction($action);

        $entity->setAcquirer(static::ACQUIRER);

        $entity->setGateway($this->input['payment']['gateway']);

        $entity->generate($attributes);

        $entity->fill($attributes);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    protected function getActionsToRetry()
    {
        if ($this->shouldRetryForAction === true)
        {
            return self::RETRIABLE_ACTIONS;
        }

        return [];
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    /**
     * @return Entity
     */
    protected function getUpiEntityForAction(array $input, string $action)
    {
        $entity = $this->getRepository()->findByPaymentIdAndAction($input['payment']['id'], $action);

        return $entity;
    }

    protected function generateIntentString(array $content)
    {
        $url = 'upi://pay?' . str_replace(' ', '', urldecode(http_build_query($content)));

        // Since payment(Id, gateway and terminal) are already in trace, we don't need to add here
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, [
            'type'          => Type::INTENT,
            'url'           => $url,
            'content'       => $content,
        ]);

        return $url;
    }

    /*
     * * * * * * * * * * SIGNED INTENT * * * * * * * * * * * *
     */

    /**
     * We are using SI Private Key Check as SI is migration change.
     * Once we move all terminals to SI, this check can be removed.
     *
     * @return bool
     */
    protected function shouldSignIntentRequest(): bool
    {
        return (empty($this->getSignIntentPrivateKey()) === false);
    }

    /**
     * Private key is merchant dependent, thus can only be retrieved
     * from terminal, Starting with MindGate where it's store in
     * gateway_terminal_password2, Later gateways can override this.
     *
     * @return mixed
     */
    protected function getSignIntentPrivateKey()
    {
        return $this->terminal['gateway_terminal_password2'];
    }

    /**
     * Create an instance of Secure modes in UPI which are SI and SQR
     * Method must not be overridden, if there are gateway specific
     * changes required, Add a getter and override that.
     *
     * @return Secure
     */
    protected function getSecureInstance(): Secure
    {
        $config = [
            Secure::PRIVATE_KEY => $this->getSignIntentPrivateKey(),
        ];

        $secure = new Secure($config);

        return $secure;
    }

    protected function getPaymentRemark(array $input)
    {
        $paymentDescription = $input['payment']['description'] ?? '';
        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = parent::getStandardRequestArray($content,$method,$type);

        if ($this->app->environment('production') === false)
        {
            $testCaseId = $this->app['request']->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $request['headers'][self::X_RZP_TESTCASE_ID] = $testCaseId;
            }
        }

        return $request;
    }

    public function traceAnomalies(string $message, Anomalies $anomalies)
    {
        if ($anomalies->hasAnomalies() === false)
        {
            return;
        }

        $this->trace->traceException(
            new RuntimeException($message),
            $anomalies->getLevel(),
            // Can be passed as 3rd parameter is needed
            TraceCode::PAYMENT_UPI_RECURRING_ANOMALY,
            $anomalies->toArray());
    }
}

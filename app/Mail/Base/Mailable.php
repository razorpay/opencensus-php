<?php

namespace RZP\Mail\Base;

use App;
use RZP\Constants\HyperTrace;
use RZP\Trace\Tracer;
use \Swift_Mailer;

use Illuminate\Bus\Queueable;
use RZP\Constants\Environment;
use Symfony\Component\Mime\Email;
use Illuminate\Container\Container;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;

use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\MailTags;
use RZP\Constants\HashAlgo;

class Mailable extends BaseMailable
{
    use Queueable;

    #TODO : decrease the number of attempts after daily files are fixed
    public $tries = 5;

    public $taskId;

    public $mode;

    // This is useful in tracking X logs and exceptions
    public $originProduct;

    protected $emailValidator;

    protected $mid;

    protected $data;

    const MESSAGE_ID_TAG = 'X-SES-Message-ID';

    protected $emailDriverName;
    protected $defaultEmailDriverName;

    const SES_EMAIL_DRIVER     = 'ses';
    const MAILGUN_EMAIL_DRIVER = 'mailgun';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->taskId           = $app['request']->getTaskId();
        $this->mode             = $app['basicauth']->getMode();
        $this->originProduct    = $app['basicauth']->getProduct();
        $this->queue            = $this->getQueueName();
        $this->mid              = $app['basicauth']->getMerchantId();

        $this->defaultEmailDriverName  = config('mail.driver');
    }

    public function build()
    {
        return $this->addSender()
                    ->addRecipients()
                    ->addCc()
                    ->addBcc()
                    ->addReplyTo()
                    ->addHtmlView()
                    ->addTextView()
                    ->addSubject()
                    ->addMailData()
                    ->addAttachments()
                    ->addHeaders();
    }

    protected function shouldSendEmailViaStork(): bool
    {
        $storkWhitelistedTemp = config('mail_template.stork_whitelist');

        // 1. check if email template is to be sent via stork
        if (isset($storkWhitelistedTemp[$this->view]) === false)
        {
            return false;
        }

        // 2. check if razorx experiment is turned on
        $app  = App::getFacadeRoot();
        $id   = $this->mid ?? $app['request']->getTaskId() ?? '';
        $exp  = $storkWhitelistedTemp[$this->view];
        $mode = $this->mode ?? Mode::LIVE;
        return (strtolower(app('razorx')->getTreatment($id, $exp, $mode)) === 'on');
    }

    public function send($mailer)
    {
        $mailer = $mailer instanceof MailFactory
            ? $mailer->mailer($this->mailer)
            : $mailer;
        $app = App::getFacadeRoot();

        // If mailer is not enabled in config for org but org entry exists then block
        if ($this->isEmailEnabledForOrg() === false)
        {
            return;
        }

        Tracer::inSpan(['name' => HyperTrace::MAILABLE_SEND], function () use($mailer, $app) {
            try
            {
                $trace = $app['trace'];
                $msgID = '';
                $eventProperties = [];
                $eventProperties['merchant_id']   = $this->mid ?? '';

                Container::getInstance()->call([$this, 'build']);

                $toEmail = empty($this->to[0]['address']) ? '' : (is_string($this->to[0]['address']) ? $this->to[0]['address'] : '' );
                $toEmailHash = hash(HashAlgo::SHA256, $toEmail);

                Tracer::inSpan(['name' => HyperTrace::MAILABLE_EVALUATE_MAIL_DRIVER], function () use($mailer) {
                    $this->evaluateAndSetMailDriver($mailer);
                });

                if ($this->isValidRecipient() === false)
                {
                    $trace->info(TraceCode::SEND_EMAIL_FAILED_INVALID_RECIPIENT, [
                        'email'      => $toEmail,
                        'email_hash' => $toEmailHash,
                        'mailable'   => get_class($this)
                        ]);
                    return;
                }

                // same html template can have different texts. Hence sending both in data lake.
                $eventProperties['text_template'] = $this->textView ?? '';
                $eventProperties['html_template'] = $this->view ?? '';
                $eventProperties['recipient_email'] = $toEmailHash;
                $eventProperties['email_driver'] = $this->emailDriverName;

                $app['diag']->trackEmailEvent(EventCode::EMAIL_ATTEMPTED, $eventProperties);

                $trace->info(TraceCode::SEND_EMAIL_ATTEMPT, [
                    'email'    => $toEmailHash,
                    'mailable' => get_class($this),
                    'view'     => $this->view,
                    ]);

                $shouldSendEmailViaStork = Tracer::inSpan(['name' => HyperTrace::MAILABLE_SHOULD_SEND_VIA_STORK], function () {
                    return $this->shouldSendEmailViaStork();
                });

                // if email is to be sent via stork
                if ($shouldSendEmailViaStork === true)
                {
                    $eventProperties['email_driver'] = 'stork';
                    // we can override any base param by adding the param in `getParamsForStork()`
                    $paramsPayload = array_merge($this->getBaseParamsForStork(), $this->getParamsForStork());
                    $trace->info(TraceCode::SEND_EMAIL_ATTEMPT_STORK,
                                 [
                                     'template_name' => $paramsPayload['template_name'] ?? '',
                                     'view'          => $this->view,
                                 ]);

                    try
                    {
                        $res = Tracer::inSpan(['name' => HyperTrace::MAILABLE_SEND_VIA_STORK], function () use($paramsPayload) {
                            return (new Stork($this->mode, $this->originProduct))->sendEmail($paramsPayload);
                        });
                        $trace->info(TraceCode::SEND_EMAIL_ATTEMPT_STORK_SUCCESSFUL,
                                     [
                                         'stork_response' => $res
                                     ]);
                    }
                    catch (\Throwable $e)
                    {
                        $trace->traceException($e,
                                               Trace::ERROR,
                                               TraceCode::SEND_EMAIL_ATTEMPT_STORK_EXCEPTION,
                                               [
                                                   'email_params' => $paramsPayload
                                               ]);
                    }

                    $msgID = $res['message_id'] ?? '';
                    if ($msgID === '')
                    {
                        $trace->info(TraceCode::SEND_EMAIL_ATTEMPT_STORK_FAILED,
                        [
                            'template_name'         => $paramsPayload['template_name'] ?? '',
                            'view'    => $this->view,
                        ]);
                    }
                }

                if (empty($msgID) === true)
                {
                    $msg = null;
                    Tracer::inSpan(['name' => HyperTrace::MAILABLE_MAILER_SEND], function () use($mailer, &$msg) {
                        $mailer->send($this->buildView(), $this->buildViewData(), function ($message) use (&$msg) {
                            $msg = $message;
                            $this->buildFrom($message)
                                ->buildRecipients($message)
                                ->buildSubject($message)
                                ->buildAttachments($message)
                                ->runCallbacks($message);
                        });
                    });

                    if ((empty($msg) === false) && (empty($msg->getHeaders()) === false) && (empty($msg->getHeaders()->get(self::MESSAGE_ID_TAG)) === false))
                    {
                        $msgID = $msg->getHeaders()->get(self::MESSAGE_ID_TAG)->getValue() ?? '';
                    }
                }

                $eventProperties['message_id'] = $msgID;
                $app['diag']->trackEmailEvent(EventCode::EMAIL_SUCCESS, $eventProperties);

                if (isset($this->data['rewards']) === true)
                {
                    $rewards = $this->data['rewards'];

                    $rewardEventProperties = [];

                    $rewardEventProperties['merchant_id'] = $this->data['merchant']['id'];

                    $rewardEventProperties['payment_id'] = $this->data['payment']['id'];

                    foreach ($rewards as $reward)
                    {
                        $rewardEventProperties['reward_ids'][] = $reward['id'];
                    }

                    if(isset($this->data['email_variant']))
                    {
                        $rewardEventProperties['email_variant'] = $this->data['email_variant'];
                    }

                    $app['diag']->trackEmailEvent(EventCode::EMAIL_REWARD_SENT, $rewardEventProperties);
                }

                $trace->info(TraceCode::SEND_EMAIL_SUCCESSFUL,
                    [
                        'email' => $toEmailHash,
                        'message_id' => $msgID,
                        'mailable' => get_class($this)
                    ]
                );
            }
            catch (\Throwable $e)
            {
                $app['diag']->trackEmailEvent(EventCode::EMAIL_ATTEMPT_FAILED, $eventProperties, $e);

                $trace->traceException($e,
                                       Trace::ERROR,
                                       TraceCode::MAILER_JOB_ERROR,
                                       [
                                            'from'    => $this->from,
                                            'to'      => $this->to,
                                            'subject' => $this->subject,
                                            'mailable' => get_class($this)
                                       ]);

                // After logging the exception caught, we rethrow it so that the
                // retry mechanism for mails is triggerred unless the exception
                // was a guzzle client exception (i.e 4XX errors), in which case,
                // retrying the request would just cause the request to fail.
                if (($e instanceof GuzzleClientException) !== true && $app->environment(Environment::BETA) === false)
                {
                    throw $e;
                }
            }
        });
    }

    /**
     * Overridden: We use sub classed SendQueuedMailable which sets request id
     * and task id for tracing.
     *
     * Queue the message for sending.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function queue(Queue $queue)
    {
        return Tracer::inSpan(['name' => HyperTrace::MAILABLE_QUEUE], function () use($queue) {
            // If mailer is not enabled in config for org but org entry exists then block
            if ($this->isEmailEnabledForOrg() === false)
            {
                return;
            }

            $connection = property_exists($this, 'connection') ? $this->connection : null;
            $queueName  = property_exists($this, 'queue') ? $this->queue : null;

            return $queue
                ->connection($connection)
                ->pushOn($queueName ?: null, new SendQueuedMailable($this));
        });
    }

    protected function evaluateAndSetMailDriver(MailerContract &$mailer)
    {
        $app = App::getFacadeRoot();
        $trace = $app['trace'];

        // if not production, route the mail via default
        if ($app->environment(Environment::PRODUCTION) === false)
        {
            $this->emailDriverName = $this->defaultEmailDriverName;
            $this->setDefaultDriver($mailer);
            return;
        }

        // 1. custom logic for routing certain emails via an explicit gateway.
        // checking if mail explicitly needs to be sent via mailgun.
        if ($this->shouldRouteEmailViaMailgun() === true)
        {
            $this->emailDriverName = self::MAILGUN_EMAIL_DRIVER;

            try
            {
                $this->setMailgunDriver($mailer);
                return;
            }
            catch (\Throwable $e)
            {
                $trace->traceException($e, Trace::ERROR, TraceCode::MAILER_INVALID_DRIVER, ['driver' => self::MAILGUN_EMAIL_DRIVER]);
            }
        }

        // 2. else it will be sent via the default driver
        $this->emailDriverName = $this->defaultEmailDriverName;
        $this->setDefaultDriver($mailer);
    }

    /**
     * Returns true when
     * 1. If the default is mailgun
     * 2. If the env is production & razorx experiment returns 'on' &
     *    template is whitelisted for mailgun
     *
     * @return bool
     */
    protected function shouldRouteEmailViaMailgun(): bool
    {
        if ($this->defaultEmailDriverName == self::MAILGUN_EMAIL_DRIVER)
        {
            return true;
        }
        return ($this->isEmailTemplateWhitelistedForMailgun() === true);
    }

    /**
     * Email driver is a transport layer wrapped inside Swift_Mailer.
     * MailerContract contains this Swift_Mailer object. This method sets
     * a swift mailer with ses driver on the passed MailerContract
     *
     * @param MailerContract &$mailerContract reference to the mailerContract object on which
     *                       ses driver needs to be set.
     *
     * @throws \InvalidArgumentException if the driver is invalid (thrown by Illuminate\Support\Manager)
     */
    private function setMailgunDriver(MailerContract &$mailerContract)
    {
        $transport = app('mail.manager')->createSymfonyTransport(['transport' => self::MAILGUN_EMAIL_DRIVER]);
        $mailerContract->setSymfonyTransport($transport);
    }

    private function setSesDriver(MailerContract &$mailerContract)
    {
        $this->replaceMailgunHeadersWithSesHeaders();
        $transport = app('mail.manager')->createSymfonyTransport(['transport' => self::SES_EMAIL_DRIVER]);
        $mailerContract->setSymfonyTransport($transport);
    }

    private function setDefaultDriver(MailerContract &$mailerContract)
    {
        switch ($this->defaultEmailDriverName)
        {
            case self::SES_EMAIL_DRIVER:
                $this->setSesDriver($mailerContract);
                return;
            case self::MAILGUN_EMAIL_DRIVER:
                $this->setMailgunDriver($mailerContract);
                return;
        }

        $transport = app('mail.manager')->createSymfonyTransport(['transport' => config('mail.driver')]);
        $mailerContract->setSymfonyTransport($transport);
    }

    /**
     * Checks the config if the template has been whitelisted for mailgun.
     */
    private function isEmailTemplateWhitelistedForMailgun(): bool
    {
        $whitelistedViews = config('mail_template.mailgun_whitelist');
        return in_array($this->view ?? '', $whitelistedViews, true);
    }

    /**
     * Stub method to add mail sender. To be implemented by child classes
     * Use the mailable from() method to add senders
     */
    protected function addSender()
    {
        return $this;
    }

    /**
     * Stub method to add mail recipients. To be implemented by child classes
     * Use the mailable to() method to add recipients
     */
    protected function addRecipients()
    {
        return $this;
    }

    /**
     * Stub method to add reply to addresses. To be implemented by child classes.
     * Use the mailable replyTo() method to add this
     */
    protected function addReplyTo()
    {
        return $this;
    }

    /**
     * Stub method to add cc addresses. To be implemented by child classes.
     * Use the mailable cc() method to add mail addresses in cc
     */
    protected function addCc()
    {
        return $this;
    }

    /**
     * Stub method to add bcc addresses. To be implemented by child classes.
     * Use the mailable bcc() method to add mail addresses in bcc
     */
    protected function addBcc()
    {
        return $this;
    }

    /**
     * Stub method to add HTML mail view. To be implemented by child classes
     * Use the mailable view() method to add html view to mail.
     */
    protected function addHtmlView()
    {
        return $this;
    }

    /**
     * Stub method to add text view for mail. To be implemented by child classes
     * Use the mailable text() method to add text view to mail
     */
    protected function addTextView()
    {
        return $this;
    }

    /**
     * Stub method to add subject to mail. To be implemented by child classes.
     * Use the mailable subject() method to add subject to mail
     */
    protected function addSubject()
    {
        return $this;
    }

    /**
     * Stub method to add mail data for use by the template. To be implemented by child classes.
     * Use the mailable with() method to attach any data to the mail body
     */
    protected function addMailData()
    {
        $app = App::getFacadeRoot();

        $merchant  = $app['basicauth']->getMerchant();

        $this->data = $this->data ?? [];

        $this->data = array_merge(OrgWiseConfig::getOrgDataForEmail($merchant), $this->data);

        return $this;
    }

    /**
     * Stub method to add mail data for use by the template. To be implemented by child classes.
     * Use the mailable with() method to attach any data to the mail body
     */
    protected function getMailDataForAdmin()
    {
        $app = App::getFacadeRoot();

        $orgId  = $app['basicauth']->getAdminOrgId();

        return $this->getOrgData($orgId);
    }

    protected function getOrgData($orgId)
    {
        $customBranding = false;

        $orgData = [];

        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $org = $repo->org->findOrFail($orgId);

        if (($orgId !== Org\Entity::RAZORPAY_ORG_ID) &&
            ($org->isFeatureEnabled(Feature\Constants::ORG_CUSTOM_BRANDING)))
        {
            $customBranding = true;
        }

        $orgData['org_name'] = $org->getDisplayName();

        $orgData['checkout_logo'] = $org->getCheckoutLogo();

        if ($customBranding === true)
        {
            $orgData['email_logo'] = $org->getEmailLogo();
        }
        else
        {
            $orgData['email_logo'] = $org->getMainLogo();
        }

        $orgData['custom_branding'] = $customBranding;

        return $orgData;
    }

    protected function getUserOrgData($userId)
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $user = $repo->user->findOrFailPublic($userId);

        $totalMerchants = $user->primaryMerchants()->count();

        $merchant = $totalMerchants === 1 ? $user->primaryMerchants()->first() : null;

        return OrgWiseConfig::getOrgDataForEmail($merchant);
    }


    /**
     * Stub method to handle attachments. To be implemented by child classes
     * Use the mailable attach method to attach any file or raw content to mail.
     */
    protected function addAttachments()
    {
        return $this;
    }

    /**
     * Stub method to add mail headers. To be implemented by child clases
     * Use the withSymfonyMessage method to get the underlying swift message and
     * add any mail headers like Mailgun header
     */
    protected function addHeaders()
    {
        return $this;
    }

    /**
     * This method removes all mailgun headers with SES headers.
     * Most of the classes implement a method addHeaders() which
     * adds mailgun headers. Since the method is tightly coupled
     * to the mailgun driver, hence have to replace the headers.
     */
    protected function replaceMailgunHeadersWithSesHeaders()
    {
        $textTemplate = $this->textView ?? '';
        $htmlTemplate = $this->view ?? '';

        $this->withSymfonyMessage(function (Email $message) use ($textTemplate, $htmlTemplate)
        {
            $allHeaders = $message->getHeaders();

            // 1. Add header for ses for the kinesis event stream for all emails.
            $configHeader = config('aws.ses_configuration_header');
            if (empty($configHeader) === false)
            {
                $allHeaders->addTextHeader(MailTags::SES_CONFIGURATION_HEADER, $configHeader);
            }

            // 2. Maintain an array for ses headers and push all ses headers into the array
            $sesHeaders = [];

            // 2.1 Push all additional headers for template info
            array_push($sesHeaders, sprintf('html_template_%s', $htmlTemplate));
            if (empty($textTemplate) === false)
            {
                array_push($sesHeaders, sprintf('text_template_%s', $textTemplate));
            }

            // 2.2. Push all mailgun headers to the array
            $mailgunHeaders = $allHeaders->all(MailTags::HEADER);
            foreach ($mailgunHeaders as $header)
            {
                array_push($sesHeaders, $header->getValue());
            }

            // 3. Prepare the final header value of the format - `0=html_template_emails_webhook_deactivate,1=webhook,2=FGlwDQqCIkI5hx`
            // Note: ses header only supports alphanumeric, `_` and `-` and hence all other are replaced with `_`.
            $sesHeadersCommaSep = implode(',', array_map(
                function ($v, $k) {
                    $v = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $v);
                    return $k.'='.$v;
                },
                $sesHeaders,
                array_keys($sesHeaders)
            ));

            // 4. remove mailgun headers and ses headers
            $allHeaders->remove(MailTags::HEADER);
            $allHeaders->addTextHeader(MailTags::SES_HEADER, $sesHeadersCommaSep);
        });
    }

    /**
     * Checks if the recipient email is not void@razorpay.com and is a valid email
     * by checking MX records. Check details here https://github.com/nojacko/email-validator
     *
     * @return boolean
     */
    protected function isValidRecipient(): bool
    {
        if (filled($this->to) === true)
        {
            $emailValidator = new Validator;
            $recipientEmail = $this->to[0]['address'];

            return (($recipientEmail !== 'void@razorpay.com') and
                ($emailValidator->isSendable($recipientEmail) === true));
        }

        return false;
    }

    /**
     * Returns on which queue this mailable should be pushed to.
     * Refer to config/queue.php's mail block for the data structure.
     *
     * @return string
     */
    protected function getQueueName(): string
    {
        $key     = snake_case(class_basename($this));
        $default = config('queue.mail.default');

        return config("queue.mail.{$key}", $default);
    }

    protected function getView($newView, $oldView)
    {
        $variant  =  app('razorx')->getTreatment(
                            $this->data['merchant']['id'],
                            Merchant\RazorxTreatment::MJML_BASED_MAILERS,
                            $this->mode);

        return strtolower($variant) === 'on' ? $newView : $oldView;
    }

    protected function isEmailEnabledForOrg(): bool
    {
        $app = App::getFacadeRoot();

        /** @var Trace $trace */
        $trace = $app['trace'];

        /** @var Merchant\Entity $merchant */
        $merchant = $app['basicauth']->getMerchant();

        $class = get_class($this);

        // Not a merchant flow so let the email be sent, we only block based
        // on merchant context in the flow and not based on the recipient
        if (empty($merchant) === true)
        {
            $trace->info(TraceCode::NO_MERCHANT_CONTEXT_MAIL, ['mail' => $class]);

            return true;
        }

        $orgCode = $merchant->org->getCustomCode();

        $res = OrgWiseConfig::getEmailEnabledForOrg($orgCode, $class, $merchant);

        if ($res === false)
        {
            $trace->info(TraceCode::ORG_MAILER_BLOCKED, ['org_code' => $orgCode, 'mail' => $class]);
        }

        return $res;
    }

    protected function getParamsForStork(): array
    {
        return [];
    }

    protected function getBaseParamsForStork(): array
    {
        $app = App::getFacadeRoot();
        $orgId = $app['basicauth']->getOrgId() ?? '';

        return [
            'owner_id'           => $this->mid,
            'owner_type'         => 'merchant',
            'org_id'             => $orgId,
            'template_name'      => $this->view,
            'template_namespace' => '',
            'context'            => json_decode ('{}'),
            'from'               => $this->from[0] ?? [],
            'to'                 => $this->to ?? [],
            'cc'                 => $this->cc ?? [],
            'bcc'                => $this->bcc ?? [],
            'reply_to'           => $this->replyTo ?? [],
            'subject'            => $this->subject ?? '',
        ];
    }
}

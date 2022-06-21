<?php

namespace RZP\Models\AMPEmail;
use App;
use Request;
use RZP\Trace\TraceCode;
use Cache;
use Razorpay\Trace\Logger as Trace;
class MailModoService extends MailService
{

    private $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = config(Constants::APPLICATIONS_MAILMODO);

        $mock = $this->config['mock'];

        $this->vendor = Constants::MAILMODO;

        if ($mock === true)
        {
            //
            // This config is not defined in application config , this is used in test case only
            //
            $mockStatus = $this->config['response'] ?? Constants::SUCCESS;

            $this->client = new MailModoClientMock($mockStatus);
        }
        else
        {
            $this->client = (new MailModoClient());
        }
    }

    public function triggerEmail(EmailRequest $request): ?EmailResponse
    {
        $response = null;

        try
        {
            $response = $this->client->triggerEmail($request);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL,
                                         TraceCode::MAILMODO_SERVICE_REQUEST_FAILED,
                                         [
                                             Constants::PAYLOAD => $request->getPayload(),
                                             Constants::PATH    => $request->getPath()
                                         ]
            );
        }

        return $response;
    }


    public function isAllowedSender(string $email): bool
    {
        return trim(strtoupper($email)) === Constants::RAZORPAY_EMAIL;
    }

    public function isAllowedOrigin(string $origin): bool
    {
        return trim(strtoupper($origin)) === Constants::GMAIL_ORIGIN;
    }

    public function validateCors()
    {
        if (isset($_SERVER['HTTP_AMP_EMAIL_SENDER']))
        {
            $senderEmail = $_SERVER['HTTP_AMP_EMAIL_SENDER'];

            if (!$this->isAllowedSender($senderEmail))
            {
                die('invalid sender');
            }

            header("AMP-Email-Allow-Sender:noreply@razorpay.com");
        }
        elseif (isset($_SERVER['HTTP_ORIGIN']))
        {
            $requestOrigin = $_SERVER['HTTP_ORIGIN'];

            if (!$this->isAllowedOrigin($requestOrigin))
            {
                die('invalid sender');
            }

            if (empty($_GET['__amp_source_origin']))
            {
                die('invalid request');
            }

            $senderEmail = $_GET['__amp_source_origin'];

            if (!$this->isAllowedSender($senderEmail))
            {
                die('invalid sender');
            }

            header("Access-Control-Allow-Origin:https://mail.google.com");
            header('Access-Control-Expose-Headers:AMP-Access-Control-Allow-Source-Origin');
            header("AMP-Access-Control-Allow-Source-Origin:noreply@razorpay.com");
        }
        else
        {
            die('invalid request');
        }
    }
}

<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;
use RZP\Constants\Product;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;

class PasswordReset extends Base\Mailable
{
    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $token;

    protected $product;

    protected $unified_hostname;

    public function __construct($user, $org, $product = Product::PRIMARY, $unified_hostname = null)
    {
        parent::__construct();

        $this->user = $user;

        $this->token = (new User\Service)->getTokenWithExpiry(
                            $this->user['id'],
                            User\Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME
                        );

        $this->org = $org;

        $this -> unified_hostname = $unified_hostname;

        $this->product = $product;
    }

    protected function addRecipients()
    {
        $email = $this->user['email'];

        $name = $this->user['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSender()
    {
        $this->from($this->org['from_email'], $this->org['display_name']);

        return $this;
    }

    protected function addSubject()
    {
        $orgName = $this->org['display_name'];

        $source = ($this->product === Product::BANKING) ? "RazorpayX" : "Razorpay";

        $subject = sprintf("Reset your %s password. ", $source);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'token'      => $this->token,
            'org'        => $this->org,
            'unified_hostname' => $this->unified_hostname,
            'email'      => urlencode($this->user['email']),
            'product'    => $this->product,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.password_reset');

        return $this;
    }

    protected function isStorkSupported($user, $org)
    {
        try
        {
            $app = \App::getFacadeRoot();
            $mode = $app['rzp.mode'] ?? Mode::LIVE;
            $userID = $user['id'];
            $orgID = $org['id'];
            $razorxFeature = RazorxTreatment::API_STORK_BANKING_EMAIL .'_reset_password';
            $traceCode = TraceCode::API_STORK_BANKING_EMAIL;

            // check the experiment
            $userVariant = $app['razorx']->getTreatment($userID,
            $razorxFeature, $mode);

            $orgVariant = $app['razorx']->getTreatment($orgID,
            $razorxFeature, $mode);

            $app['trace']->info($traceCode, [
                'mode' => $mode,
                'userID' => $userID,
                'orgID' => $orgID,
                'userVariant' => $userVariant,
                'orgVariant' => $orgVariant
            ]);


            if (strtolower($userVariant) === 'on' or strtolower($orgVariant) === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException(
                $e,
                null,
                $traceCode
            );
        }

        return false;
    }

    //The email should be sent via stork only if the product is banking.
    public function shouldSendEmailViaStork(): bool
    {
        $app = \App::getFacadeRoot();
        $trace = $app['trace'];
        $isExperimentEnabled = $this->isStorkSupported($this->user, $this->org);
        $isProductBanking = $this->product === Product::BANKING;

        return $isProductBanking or $isExperimentEnabled;
    }

    public function getParamsForStork(): array
    {
        $isProductBanking = $this->product === Product::BANKING;
        if ($isProductBanking === true)
        {
            $storkParams = [
                'template_namespace' => 'razorpayx_payouts_core',
                'template_name' => 'razorpayx.password_reset',
                'params' => [
                    'password_reset_url' =>
                        'https://' .
                        parse_url(
                            config('applications.banking_service_url'),
                            PHP_URL_HOST
                        ) .
                        '/forgot-password#token=' .
                        $this->token .
                        '&email=' .
                        $this->user['email'],
                    'display_name' => $this->org['display_name'],
                    'login_logo_url' => $this->org['login_logo_url'],
                ],
            ];

            if ($this->org['showAxisSupportUrl'] !== true)
            {
                $storkParams['template_name'] =
                    'razorpayx.password_reset.show_axis_support_url';
            }

            return $storkParams;
        }
        else
        {
            $isUnified = $this->unified_hostname !== null;
            $showContactUs =
                $this->org['showAxisSupportUrl'] !== true &&
                $this->org['isCustomOnboardingEmail'] !== true;
            $storkParams = [
                'template_name' => 'banking_mail_reset_password',
                'template_namespace' => 'payments_banking',
                'params' => [
                    'org' => $this->org,
                    'showContactUs' => $showContactUs,
                ],
            ];

            if ($isUnified === true)
            {
                $storkParams['params']['password_reset_url'] =
                    $this->unified_hostname .
                    '/forgotpwd/#token=' .
                    $this->token .
                    '&email=' .
                    urlencode($this->user['email']);
            }
            elseif ($isProductBanking === true)
            {
                $storkParams['params']['password_reset_url'] =
                    'https://' .
                    parse_url(
                        config('applications.banking_service_url'),
                        PHP_URL_HOST
                    ) .
                    '/forgot-password#token=' .
                    $this->token .
                    '&email=' .
                    $this->user['email'];
            }
            else
            {
                $storkParams['params']['password_reset_url'] =
                    'https://' .
                    $this->org['hostname'] .
                    '/#/access/resetpassword?email=' .
                    urlencode($this->user['email']) .
                    '&token=' .
                    $this->token;
            }
            return $storkParams;
        }
    }
}


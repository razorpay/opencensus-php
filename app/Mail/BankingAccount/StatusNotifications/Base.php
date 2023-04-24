<?php

namespace RZP\Mail\BankingAccount\StatusNotifications;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount;
use Illuminate\Foundation\Application;

class Base extends Mailable
{
    const TEMPLATE_PATH = '';

    const SUBJECT       = '';

    /** @var array $bankingAccount */
    protected $bankingAccount;

    /** @var \RZP\Models\Merchant\Entity $merchant */
    protected $merchant;

    protected $config;

    /**
     * @param array $bankingAccount
     */
    public function __construct(array $bankingAccount)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->config = $this->getRequiredConfigParamsFromApp($app);

        $this->bankingAccount = $bankingAccount;

        $this->merchant = app('repo')->merchant->findOrFail($bankingAccount[BankingAccount\Entity::MERCHANT_ID]);
    }

    protected function getRequiredConfigParamsFromApp(Application $app)
    {
        $requiredConfig = [];

        $requiredConfigParams = $this->getRequiredConfigParams();

        foreach ($requiredConfigParams as $requiredConfigParam)
        {
            $requiredConfig[$requiredConfigParam] = $app['config']->get($requiredConfigParam);
        }

        return $requiredConfig;
    }

    protected function getRequiredConfigParams()
    {
        return [
            'applications.banking_service_url'
        ];
    }

    protected function addRecipients()
    {
        $toEmail = $this->merchant->getEmail();

        $toName = $this->merchant->getName();

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(static::TEMPLATE_PATH);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(static::SUBJECT);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::BANKING_ACCOUNT_STATUS_UPDATED;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }
}

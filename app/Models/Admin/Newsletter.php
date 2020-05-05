<?php

namespace RZP\Models\Admin;

use Carbon\Carbon;
use cebe\markdown\MarkdownExtra;
use Config;
use Mail;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Class used for mass mailing
 */
class Newsletter
{
    protected $lists;
    protected $count;
    protected $recipient;
    protected $email;
    protected $listName;
    protected $testListMemberAdd;

    const WAIT_BEFORE_RETRY = 10;

    const EMAIL = 'email';

    function __construct(
        $subject,
        $msg,
        $template = 'newsletter')
    {
        $this->app = \App::getFacadeRoot();

        $this->config = Config::get('applications.mailgun');

        $this->data = $this->setupData($msg, $subject);

        $this->template = $template;

        $this->count = 0;
    }

    protected function setupData($msg, $subject = 'Razorpay Newsletter')
    {
        return [
            'subject'   =>  $subject,
            'body'      =>  $this->getBody($msg)
        ];
    }

    public function setRecipient($recipient)
    {
        $this->lists = $recipient;
    }

    /**
     * Gets the mailgun listAddress corresponding to listName
     * By default uses listName as 'newsletter'
     * @return string generated email address of mailing list
     */
    protected function getMailgunListAddress()
    {
        if (isset($this->listName) === false)
        {
            $this->setMailingListName('newsletter');
        }

        $listAddress = $this->listName.'@'.$this->config['url'];

        return $listAddress;
    }

    /**
     * Set the mailing list name.
     * This is used to create the mailing list address if set.
     */
    public function setMailingListName($listName)
    {
        $this->listName = $listName;
    }

    /**
     * Uploads additional merchants to the mailing list
     * @param  string $lists list of applied filters in csv
     * @param  $action
     * @return null
     */
    public function createMailingListAndGetEmails($lists, $action = null)
    {
        $listAddress = $this->getMailgunListAddress();

        return $listAddress;
    }

    public function setTestEmail($email)
    {
        $this->lists = null;

        $this->email = $email;

        $this->count = 1;
    }

    protected function getMailgunInstance()
    {
        return $this->app['mailgun']->getMailgunInstance();
    }

    public function send($action = null)
    {
        if (isset($this->lists))
        {
            // This also sets the count internally
            $this->email = $this->createMailingListAndGetEmails($this->lists, $action);
        }
    
        return $this->sendEmail();
    }

    public function sendEmail()
    {
        $view = 'emails.merchant.' . $this->template;
        $view = ['html' => $view];

        $config = $this->config;
        $data   = $this->data;

        $data['email'] = $this->email;

        Mail::send($view, $this->data, function($message) use ($config, $data)
        {
            $message->to($data['email']);

            $from = 'support@' . $config['url'];

            $message->from($from, $config['from_name']);

            $message->subject('Razorpay | '.$this->data['subject']);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::HOLIDAY_NOTIFICATION);
        });

        return [
            'email' => $this->email,
            'count' => $this->count
        ];
    }

    protected function getBody($msg)
    {
        $msg = (new MarkdownExtra())->parse($msg);

        $msg = <<<EOT
<div class="newsletter">
$msg
</div>
EOT;
        $viewDirectory = app_path().'/../resources/views/';
        $ink_css =      file_get_contents($viewDirectory.'css/ink.css');
        $cssContent =   file_get_contents($viewDirectory.'css/email.css')
            . PHP_EOL
            . file_get_contents($viewDirectory . 'css/newsletter.css');

        $cssContent = $ink_css. PHP_EOL . $cssContent;

        $convertor = new CssToInlineStyles;

        // @note: Commented during upgrade to laravel5.4
        //      The CssToInlineStyles library in version 2.2 is behaving
        //      differently from older 1.5 version which was previously being
        //      used.
        //
        // $convertor->setHTML($msg);
        // $convertor->setCleanup(false);
        // $convertor->setExcludeMediaQueries(false);
        // $convertor->setCSS($cssContent);

        return $convertor->convert($msg, $cssContent);
    }
}

<?php

namespace RZP\Models\Admin;

use Carbon\Carbon;
use cebe\markdown\MarkdownExtra;
use Config;
use Mail;
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

    function __construct(
        $subject,
        $msg,
        $template = 'newsletter')
    {
        $this->app = \App::getFacadeRoot();

        $this->config = Config::get('applications.mailgun');

        $this->data = $this->setupData($msg, $subject);

        $this->template = $template;

        //$this->testListMemberAdd = false;

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
     * Returns all the merchants who match a particular filter
     * @param  string $list valid list name
     * @return array
     */
    protected function getEmailList($list)
    {
        $repo = new Merchant\Repository;
        $merchants = [];

        switch($list)
        {
            case 'all':
                $merchants = $repo->fetchAllMerchantContacts()->toArray();
                break;

            case 'live':
                $merchants = $repo->fetchAllLiveMerchants()
                    ->select(['email', 'name', 'transaction_report_email'])->get();
                break;

            case 'recent':
                $merchants = $repo->fetchRecentMerchants()
                    ->select(['email', 'name','transaction_report_email'])->get();
                break;

            case 'default':
                break;
        }

        $response = [];

        // Need to switch array key from email to address
        // And convert from collection to plain array
        foreach ($merchants as $merchant) {
            // We store every merchant as string
            // because array_unique only works on strings
            // This isn't precise but it doesn't matter
            // because mailgun is set to ignore duplicate entries
            $this->encodeMerchantDetails($merchant, $response);
        }

        return $response;
    }

    protected function encodeMerchantDetails($merchant, & $response)
    {
        $response[] = json_encode([
                'address' => $merchant['email'],
                'name'    => $merchant['name']
            ]);

        // Attaching the Transaction Report Emails
        if (isset($merchant['transaction_report_email']))
        {
            foreach ($merchant['transaction_report_email'] as $email)
            {
                $response[] = json_encode([
                        'address' => $email,
                        'name'    => $merchant['name']
                    ]);
            }

        }
    }

    /**
     * Given multiple lists in csv format, returns all merchants fulfilling
     * any of the filters, with duplicates. Returned values are in a sub-array
     * that is chunked in batch size of 1000
     * @param  string $lists csv of valid list names
     * @return array chunked list of merchants
     */
    protected function getMerchantListChunks($lists)
    {
        if (strpos($lists, ',') !== false)
        {
            $lists = explode(',', $lists);
        }
        else
        {
            $lists = [$lists];
        }

        $merchants = [];

        foreach ($lists as $list) {
            $merchants = array_merge($merchants, $this->getEmailList($list));
        }

        // Make it unique and then run json_decode
        $merchants = array_map('json_decode', array_unique($merchants));

        $this->count = count($merchants);

        // Chunks of 1000
        return array_chunk($merchants, 1000);
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
     * @return null
     */
    public function createMailingListAndGetEmails($lists)
    {
        $listAddress = $this->getMailgunListAddress();

        if ($this->testListMemberAdd === false)
        {
            return $listAddress;
        }

        $this->addListMembersToMailgun($lists, $listAddress);

        return $listAddress;
    }

    /**
     * Adds the members of the given list to the Mailgun List
     * Address provided.
     **/
    protected function addListMembersToMailgun($lists, $listAddress)
    {
        $chunks = $this->getMerchantListChunks($lists);

        $this->app['trace']->info(
            TraceCode::MERCHANT_NEWSLETTER_MAILING_LIST_CREATED,
            [
                'pre_upsert_timestamp' => Carbon::now()->getTimestamp(),
                'merchant_count'       => $this->count,
            ]);

        foreach ($chunks as $merchants)
        {
            // We take this list and push it to mailgun

            $relativeUrl = 'lists/'.$listAddress.'/members.json';

            $this->getMailgunInstance()->post($relativeUrl,[
                'upsert'     => true,
                'members'    => json_encode($merchants)
            ]);
        }

        $this->app['trace']->info(
            TraceCode::MERCHANT_NEWSLETTER_MAILING_LIST_CREATED,
            ['post_upsert_timestamp' => Carbon::now()->getTimestamp()]);
    }

    public function setTestEmail($email)
    {
        $this->lists = null;

        $this->email = $email;

        $this->count = 1;
    }

    public function setTestListMembersAdd()
    {
        $this->testListMemberAdd = true;
    }

    protected function getMailgunInstance()
    {
        return $this->app['mailgun']->getMailgunInstance();
    }

    public function send()
    {
        if (isset($this->lists))
        {
            // This also sets the count internally
            $this->email = $this->createMailingListAndGetEmails($this->lists);
        }

        if ($this->testListMemberAdd)
        {
            return [
                'email' => $this->lists.' created and timestamps recorded.'
            ];
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

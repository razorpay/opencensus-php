<?php

namespace Models\Admin;

use Carbon\Carbon;
use cebe\markdown\MarkdownExtra;
use Config;
use Models\Merchant;
use Mail;
use Mailgun\Mailgun;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Trace\Trace;
use Trace\TraceCode;

/**
 * Class used for mass mailing
 */
class Newsletter
{
    protected $email;
    protected $listName;
    protected $testListMemberAdd;
    // 30 seconds
    const WAIT_BEFORE_RETRY = 30;

    function __construct($recipient,
        $subject = "Razorpay Newsletter",
        $msg,
        $template = 'newsletter',
        $test = false)
    {
        $this->app = \App::getFacadeRoot();

        $this->config = Config::get('applications.mailgun');

        if($test === true)
        {
            $this->email = $recipient;
            $this->count = 1;
        }
        else
        {
            $this->lists = $recipient;
        }

        $this->data = $this->setupData($subject, $msg);
        $this->template = $template;
    }

    protected function setupData($subject, $msg)
    {
        return [
            'subject'   =>  $subject,
            'body'      =>  $this->getBody($msg)
        ];
    }

    /**
     * Returns all the merchants who match a particular filter
     * @param  string $list valid list name
     * @return array
     */
    protected function getEmailList($list)
    {
        $repo = new Merchant\Repository();
        $merchants = [];

        switch($list)
        {
            case 'all':
                $merchants = $repo->fetchAllMerchantContacts()->toArray();
                break;

            case 'live':
                $merchants = $repo->fetchAllLiveMerchants()
                    ->select(['email', 'name'])->get();
                break;

            case 'recent':
                $merchants = $repo->fetchRecentMerchants()
                    ->select(['email', 'name'])->get();
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
            $response[] = json_encode([
                'address' => $merchant['email'],
                'name'    => $merchant['name']
            ]);
        }

        return $response;
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
        if(strpos($lists, ',') !== false)
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
     * Creates a new mailing list on mailgun and returns the
     * generated mailing list address
     * @return string generated email address of mailing list
     */
    protected function createNewListOnMailgun()
    {
        $listAddress = 'newsletter@'.$this->config['url'];

        return $listAddress;
    }

    /**
     * Creates a new mailing list on mailgun and returns the
     * generated mailing list address
     * @return string generated email address of mailing list
     */
    protected function createListOnMailgun($listName)
    {
        $listAddress = $listName.'@'.$this->config['url'];

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
    protected function createMailingList($lists)
    {
        if (isset($this->listName))
        {
            $listAddress = $this->createListOnMailgun($this->listName);
        }
        else
        {
            $listAddress = $this->createNewListOnMailgun();
        }

        $chunks = $this->getMerchantListChunks($lists);

        $this->app['trace']->info(
            TraceCode::MERCHANT_NEWSLETTER_MAILING_LIST_CREATED,
            ['pre_upsert_timestamp' => Carbon::now('Asia/Kolkata')]);

        foreach ($chunks as $merchants) {
            // We take this list and push it to mailgun

            $relativeUrl = "lists/$listAddress/members.json";

            $this->getMailgunInstance()->post($relativeUrl,[
                'upsert'     => true,
                'members'    => json_encode($merchants)
            ]);
        }

        $this->app['trace']->info(
            TraceCode::MERCHANT_NEWSLETTER_MAILING_LIST_CREATED,
            ['post_upsert_timestamp' => Carbon::now('Asia/Kolkata')]);

        do
        {
            $relativeUrl = "lists/$listAddress";

            $listInfo = $this->getMailgunInstance()->get($relativeUrl);

            $count =  $listInfo['list']['members_count'];

            // Wait here till the count of the members in list
            // matches the internal count
            sleep(self::WAIT_BEFORE_RETRY);
        }
        while ($count < $this->count);

        $this->app['trace']->info(
            TraceCode::MERCHANT_NEWSLETTER_MAILING_LIST_CREATED,
            ['count_match_timestamp' => Carbon::now('Asia\Kolkata')]);

        return $listAddress;
    }

    public function setTestListMembersAdd()
    {
        $this->testListMemberAdd = true;
    }

    protected function getMailgunInstance()
    {
        return new Mailgun($this->config['key']);
    }

    public function send()
    {
        //No need to do anything if we are mocking
        if ($this->config['mock'] === true)
        {
            return [
                'email' =>  'nobody, mocked'
            ];
        }

        $data = $this->data;
        $config = $this->config;

        if (isset($this->lists))
        {
            // This also sets the count internally
            $this->email = $this->createMailingList($this->lists);
        }

        if ($this->testListMemberAdd)
        {
            return [
                'email' =>  $this->email.' created and timestamps recorded'
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

            $message->subject($this->data['subject']);
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
        $viewDirectory = app_path()."/views/";
        $ink_css =      file_get_contents($viewDirectory.'css/ink.css');
        $cssContent =   file_get_contents($viewDirectory.'css/email.css')
            . PHP_EOL
            . file_get_contents($viewDirectory . 'css/newsletter.css');

        $cssContent = $ink_css. PHP_EOL . $cssContent;


        $convertor = new CssToInlineStyles();
        $convertor->setHTML($msg);
        $convertor->setCleanup(false);
        $convertor->setExcludeMediaQueries(false);
        $convertor->setCSS($cssContent);

        return $convertor->convert();
    }
}

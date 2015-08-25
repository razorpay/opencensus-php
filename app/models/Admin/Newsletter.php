<?php

namespace Models\Admin;

use Carbon\Carbon;
use cebe\markdown\MarkdownExtra;
use Config;
use Models\Merchant;
use Mail;
use Mailgun\Mailgun;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Class used for mass mailing
 */
class Newsletter
{
    protected $email;

    function __construct($recipient, array $subject = [], $msg, $test = false)
    {
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
                $merchants = $repo->fetch([])->toArray();
                break;

            case 'live':
                $merchants = $repo->fetch([Merchant\Entity::LIVE => 1])
                    ->toArray();
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
        $timestamp = Carbon::now("Asia/Kolkata")->format('Y_m_d_H_i');
        $listAddress = $timestamp.'@'.$this->config['url'];

        $this->getMailgunInstance()->post('lists', [
            'address'       => $listAddress,
            'description'   => $this->getSubject(),
            'name'          => "Newsletter at $timestamp"
        ]);

        return $listAddress;
    }

    /**
     * Creates a new mailing list for the given filters
     * @param  string $lists list of applied filters in csv
     * @return null
     */
    protected function createMailingList($lists)
    {
        $listAddress = $this->createNewListOnMailgun();

        $chunks = $this->getMerchantListChunks($lists);

        foreach ($chunks as $merchants) {
            // We take this list and push it to mailgun

            $relativeUrl = "lists/$listAddress/members.json";

            $this->getMailgunInstance()->post($relativeUrl,[
                'upsert'     => true,
                'members'    => json_encode($merchants)
            ]);
        }

        return $listAddress;
    }

    protected function getMailgunInstance()
    {
        return new Mailgun($this->config['key']);
    }

    public function send()
    {
        // No need to do anything if we are mocking
        if($this->config['mock'] === true)
        {
            return ['Email is mocked'];
        }

        $data = $this->data;
        $config = $this->config;

        if(isset($this->lists))
        {
            // This also sets the count internally
            $this->email = $this->createMailingList($this->lists);
        }

        return $this->sendEmail();
    }

    public function sendEmail()
    {
        $view = ['html' => 'emails.merchant.newsletter'];
        $config = $this->config;
        $data   = $this->data;
        $data['email'] = $this->email;

        Mail::send($view, $this->data, function($message) use ($config, $data)
        {
            $message->to($data['email']);

            $message->from($config['from_email'], $config['from_name']);

            $message->subject($this->getSubject());
        });

        return [
            'email' => $this->email,
            'count' => $this->count
        ];
    }

    protected function getSubject()
    {
        return implode(' ', $this->data['subject']);
    }

    protected function getBody($msg)
    {
        $msg = (new MarkdownExtra())->parse($msg);

        $msg = <<<EOT
<div class="newsletter">
$msg
</div>
EOT;
        $view_directory = app_path()."/views/";
        // $ink_css =      file_get_contents($view_directory.'css/ink.css');
        $cssContent =   file_get_contents($view_directory.'css/email.css');

        // $cssContent = $ink_css. PHP_EOL . $common_css;


        $convertor = new CssToInlineStyles();
        $convertor->setHTML($msg);
        $convertor->setCleanup(false);
        $convertor->setExcludeMediaQueries(false);
        $convertor->setCSS($cssContent);

        return $convertor->convert();
    }
}

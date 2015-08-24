<?php

namespace Models\Admin;

use cebe\markdown\MarkdownExtra;
use Config;
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
            'body'      =>  $this->getBody($msg),
            'email'     =>  $this->email
        ];
    }

    protected function getEmailList()
    {
        $repo = new Merchant\Repository();
        $merchants = [];
        switch($this->list)
        {
            case 'all':
                $repo->select(['email', 'name'])->toArray();
                break;

            case 'live':
                $repo->fetch([Entity::LIVE => 1])
                    ->select(['email', 'name'])->toArray();
                break;
        }

        // Need to switch array key from email to address
        foreach ($merchants as &$merchant) {
            $merchant['address'] = $merchant['email'];
            unset($merchant['email']);
        }

        return array_chunk($merchants, 1000);
    }

    protected function updateMailingList()
    {
        // $chunks = $this->getEmailList();

        // // This contains the complete list addresss, not the alias
        // $list = $this->data['list'];

        // foreach ($chunks as $merchants) {
        //     // We take this list and push it to mailgun

        //     $this->getMailgunInstance()->post("lists/$list/members.json",[
        //         'upsert'     => true,
        //         'members'    => json_encode($merchants);
        //     ]);
        // }
    }

    protected function getMailgunInstance()
    {
        return new Mailgun($this->config['key']);
    }

    public function send()
    {
        //return $this->data['body'];
        // No need to do anything if we are mocking
        if($this->config['mock'] === true)
        {
            return;
        }

        $data = $this->data;
        $config = $this->config;

        if(isset($this->email))
        {
            return $this->sendSingleEmail($this->email);
        }
        else
        {
            $this->updateMailingList();
        }
    }

    public function sendSingleEmail($email)
    {
        $view = ['html' => 'emails.merchant.newsletter'];
        $config = $this->config;
        $data   = $this->data;

        Mail::send($view, $this->data, function($message) use ($config, $data)
        {
            $message->to($data['email']);

            $message->from($config['from_email'], $config['from_name']);

            $message->subject(implode(' ', $data['subject']));
        });

        return [];
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

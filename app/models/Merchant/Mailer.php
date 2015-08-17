<?php

namespace Models\Merchant;

use cebe\markdown\MarkdownExtra;
use Mailgun\Mailgun;

/**
 * Class used for mass mailing
 */
class Mailer
{
    function __construct($list, $subject, $text)
    {
        $this->list = $list;
        $this->config = Config::get('applications.mailgun');
        $this->data = $this->setupData($subject, $text);
    }

    protected function setupData($subject, $text)
    {
        return [
            'subject'   =>  $subject,
            'body'      =>  (new MarkdownExtra())->parse($text),
            'list'      =>  $list."@".$this->config['url']
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
        $chunks = $this->getEmailList();

        // This contains the complete list addresss, not the alias
        $list = $this->data['list'];

        foreach ($chunks as $merchants) {
            // We take this list and push it to mailgun

            $this->getMailgunInstance()->post("lists/$list/members.json",[
                'upsert'     => true,
                'members'    => json_encode($merchants);
            ]);
        }
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
            return;
        }

        $this->updateMailingList();

        $data = $this->data;
        $config = $this->config;

        $view = ['html' => 'emails.merchant.newsletter'];

        Mail::send($view, $this->data, function($message) use ($config, $data)
        {
            $message->to($data['list']);

            $message->from($config['from_email'], $config['from_name']);

            $message->subject($data['subject']);
        });
    }
}

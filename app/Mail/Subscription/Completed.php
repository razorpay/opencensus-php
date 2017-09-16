<?php

    namespace RZP\Mail\Subscription;

    use RZP\Constants\MailTags;

    class Completed extends Base
    {
        protected function addTextView()
        {
            $this->text('emails.subscription.completed_text');

            return $this;
        }

        protected function addHtmlView()
        {
            $this->view('emails.subscription.completed');

            return $this;
        }

        protected function getResult()
        {
            return 'Subscription completed successfully';
        }

        protected function getMailTag()
        {
            return MailTags::SUBSCRIPTION_COMPLETED;
        }
    }

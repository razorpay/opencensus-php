<?php

namespace App\Mailers;

use Mail;
use Config;

abstract class Mailer
{
    /**
     * Email from which to send
     *
     * @var string
     */
    protected $fromEmail;

    /**
     * From name to be used
     * @var string
     */
    protected $fromName;

    /**
     * The name of the person to send the email to
     *
     * @var string
     */
    protected $to;

    /**
     * The email of the person to send the email to
     *
     * @var string
     */
    protected $email;

    /**
     * The subject of the email
     *
     * @var string
     */
    protected $subject;

    /**
     * The view representing the email content
     *
     * @var string
     */
    protected $view;

    /**
     * The data to be passed to the view
     *
     * @var string
     */
    protected $data;

    /**
     * Additional callback that can be passed to the subclass
     *
     * @var Closure
     */
    protected $callback;

    /**
     * Indicates where the mail job should be queued or not.
     *
     * @var boolean
     */
    protected $queue = false;

    /**
     * The method make sures that the mail job is queued
     *
     * @return self
     */
    public function queue()
    {
        // TODO: IMPORTANT HAVE TO CHANGE THIS
        // $this->queue = true;
        $this->queue = false;
        return $this;
    }

    /**
     * The method that delivers the email
     *
     * @return boolean Result of the Mail::queue call
     */
    public function deliver()
    {
        $method = $this->queue ? 'queue' : 'send';

        $email = $this->email;
        $to = $this->to;
        $subject = $this->subject;
        $callback = $this->callback;
        $fromEmail = $this->fromEmail;
        $fromName = $this->fromName;

        return Mail::$method(
            $this->view,
            $this->data,
            function($message) use($email, $to, $subject, $callback, $fromEmail, $fromName)
            {
                $message->to($email, $to)->subject($subject);

                if ($fromEmail !== null)
                {
                    $message->from($fromEmail, $fromName);
                }

                if (is_callable($callback))
                {
                    call_user_func($callback, $message);
                }
            }
        );
    }

    /**
     * Sends the mail after enabling the queue.
     * @return boolean Result of the Mail::queue call
     */
    public function queueAndDeliver()
    {
        return $this->queue()->deliver();
    }

    /**
     * Get the email configuration option for the given type from config/razorpay.php
     *
     * @return string
     */
    protected function getEmailFor($department)
    {
        return Config::get("razorpay.emails.$department");
    }
}

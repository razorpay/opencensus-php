<?php 

namespace Razorpay\Mailers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;

abstract class Mailer
{
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
     * Additional options that can be passed to the subclass
     *
     * @var Closure
     */
    protected $options;

    /**
     * Indicates where the mail job should be queued or not.
     *
     * @var boolean
     */
    protected $queue = false;

    /**
     * The method make sures that the mail job is queued
     *
     * @return boolean
     */
    public function queue()
    {
        $this->queue = true;
        return $this;
    }

    /**
     * The method that delivers the email
     *
     * @return boolean
     */
    public function deliver()
    {
        $method = $this->queue ? 'queue' : 'send';

        $email = $this->email;
        $to = $this->to;
        $subject = $this->subject;
        $options = $this->options;

        return Mail::$method($this->view,$this->data,function($message) 
            use($email,$to,$subject,$options)
        {
            $message->to($email,$to)->subject($subject);
            
            if(is_callable($options)){
                call_user_func($options,$message);
            }
        }); 
    }

    /**
     * Get the email configuration option for the given type from config/razorpay.php
     *
     * @return string
     */
    protected function getEmailFor($department)
    {
        $emails = App::make('config')->get('razorpay.emails');
        foreach ($emails as $email) 
        {
            if($email['name'] == $department)
                return $email['email'];
        }
    }
}
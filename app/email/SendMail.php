<?php

namespace Email;

class SendMail
{
    protected $mail;

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function sendHdfcMprMail($job, $data)
    {
        $mprFile = $data['mprFile'];

        $this->mail->send('hdfc.mpr', array(), function($message) use ($mprFile)
        {
            $message->from('hdfc_mpr_generator@mg.razorpay.com', 'hdfcMprGenerator');

            $message->to('hdfc_mpr_test@mg.razorpay.com')->cc('settlement@razorpay.com');

            $message->attach($mprFile);
        });

        $job->delete();
    }
}
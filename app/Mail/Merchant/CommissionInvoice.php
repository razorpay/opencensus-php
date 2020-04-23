<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base\Mailable;

class CommissionInvoice extends Mailable
{
    protected $data;

    protected static $body        = 'Please find attached commission file.';

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addMailData()
    {
        $data = ['body' => static::$body];

        $this->with($data);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Commission Payout Invoice '. $this->data['status'] . ' ' . $this->data['month_year'];

        $this->subject($subject);

        return $this;
    }

    protected function addAttachments()
    {
        $outputFileLocalPath = $this->data['filePath'];

        if ($outputFileLocalPath !== null)
        {
            $this->attach($outputFileLocalPath);
        }

        return $this;
    }
}

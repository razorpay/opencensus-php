<?php



namespace RZP\Mail\Dispute\Admin;


use RZP\Mail\Base\Mailable;

class DisputePresentmentRiskOpsReview extends Mailable
{
    protected $data;

    const RECIPIENTS = [
        'payments-onlinepayments-txn-risk@razorpay.com',
        'disputes@razorpay.com',
    ];

    public function __construct($data)
    {
        parent::__construct();

        parent::addMailData();

        $this->data = $data;
    }

    protected function addSubject(): DisputePresentmentRiskOpsReview
    {
        $disputeId = $this->data['dispute']['id'];

        $subject = "DisputePresentment | RiskOps Review needed for {$disputeId}";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.dispute.dispute_presentment_risk_ops_review');

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to(self::RECIPIENTS);

        return $this;
    }
}
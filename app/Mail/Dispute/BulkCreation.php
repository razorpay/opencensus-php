<?php

namespace RZP\Mail\Dispute;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\Dispute\Phase;

class BulkCreation extends Base
{
    protected function createViewTableData()
    {
        $tableData = [];

        foreach ($this->data[Constants::DISPUTES] as $dispute)
        {
            $tableRow['dispute_id']          = $dispute['id'];
            $tableRow['payment_id']          = $dispute['payment_id'];
            $tableRow['amount']              = 'Rs. ' . floatval(sprintf('%0.2f', ($dispute['amount'] / 100)));
            $tableRow['case_id']             = $dispute['gateway_dispute_id'];
            $tableRow['phase']               = $dispute['phase'];
            $tableRow['respond_by']          = date('d F Y', $dispute['respond_by']);

            $tableData[] = $tableRow;
        }

        $this->data['disputesDataTable'] = $tableData;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->createViewTableData();

        $this->view('emails.dispute.bulk_creation');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTES_CREATED_IN_BULK);
        });

        return $this;
    }

    protected function getSubject()
    {
        $phase = $this->data['phase'];

        $merchantName = $this->data['merchant']['name'];

        switch($phase)
        {
            case Phase::CHARGEBACK:
                return 'Razorpay | Chargeback Alert - ' . $merchantName;
            case Phase::RETRIEVAL:
                return 'Razorpay | Retrieval Request Alert - ' . $merchantName;
            case Phase::PRE_ARBITRATION:
                return 'Razorpay | Pre-Arbitration Chargeback Alert - ' . $merchantName;
            case Phase::ARBITRATION:
                return 'Razorpay | Arbritration Alert - ' . $merchantName;
            case Phase::FRAUD:
                return 'Razorpay | Fraud Chargeback Alert - ' . $merchantName;
        }
    }
}

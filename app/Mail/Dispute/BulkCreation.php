<?php

namespace RZP\Mail\Dispute;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Dispute\Phase;

class BulkCreation extends Base
{
    const BULK_DISPUTE_ATTACHMENT_FILE_NAME = 'bulk_disputes_list';

    protected function createViewTableData()
    {
        if (isset($this->data['disputesDataTable']) === false)
        {
            $tableData = [];

            foreach ($this->data[Constants::DISPUTES] as $dispute)
            {
                $tableRow['dispute_id']          = $dispute['id'];
                $tableRow['payment_id']          = $dispute['payment_id'];
                $tableRow['amount']              = $this->getFormattedAmount($dispute['amount'], $dispute['currency']);
                $tableRow['case_id']             = $dispute['gateway_dispute_id'];
                $tableRow['phase']               = $dispute['phase'];
                $tableRow['respond_by']          = date('d F Y', $dispute['respond_by']);
                $tableRow['gateway_code']        = $dispute['gateway_code'];
                $tableRow['gateway_description'] = $dispute['gateway_description'];
                $tableRow['notes']               = json_encode($dispute['payment_notes']);
                $tableRow['customer_contact']    = $dispute['customer_contact'];
                $tableRow['order_receipt']       = $dispute['order_receipt'];
                $tableData[] = $tableRow;
            }

            $this->data['disputesDataTable'] = $tableData;
        }
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

        $this->view($this->getViewName());

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DISPUTES_CREATED_IN_BULK);
        });

        return $this;
    }

    protected function addAttachments()
    {
        $this->createViewTableData();

        $time = Carbon::now(Timezone::IST)->format('d-m-Y_H:i:s');

        $fileName = sprintf('%s_%s_%s.csv', self::BULK_DISPUTE_ATTACHMENT_FILE_NAME, $this->mode, $time);

        $fileData = $this->data['disputesDataTable'];

        $fileDataString = $this->convertFileDataToString($fileData);

        $this->attachData($fileDataString, $fileName, ['mime' => 'application/csv']);

        return $this;
    }

    private function convertFileDataToString(array $arrayOfArrays) : string
    {
        $finalString = '';

        if (count($arrayOfArrays) > 0)
        {
            $headers = array_keys($arrayOfArrays[0]);

            $finalString .= implode(',', $headers) . PHP_EOL;
        }

        foreach ($arrayOfArrays as $array)
        {
            $finalString .= implode(',', $array) . PHP_EOL;
        }

        return $finalString;
    }

    private function getSubject()
    {
        $phase = $this->data['phase'];

        $isFraud = $this->data['isFraud'] ?? false;

        $merchantId = $this->data['merchant']['id'];

        $merchantName = $this->data['merchant']['name'];

        $currentDate = Carbon::now(Timezone::IST)->format('d/m/Y');

        switch($phase)
        {
            case Phase::CHARGEBACK:
                if ($isFraud === false) {
                    return sprintf('Razorpay | Service Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
                }
                return sprintf('Razorpay | Fraud Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::RETRIEVAL:
                return sprintf('Razorpay | Retrieval Request Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::PRE_ARBITRATION:
                return sprintf('Razorpay | Pre-Arbitration Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::ARBITRATION:
                return sprintf('Razorpay | Arbritration Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
            case Phase::FRAUD:
                return sprintf('Razorpay | Fraud Chargeback Alert - %s [%s] | %s', $merchantName, $merchantId, $currentDate);
        }
    }

    protected function getViewName(): string
    {
        try
        {
            $merchantId = $this->data['merchant']['id'];

            $merchant = (new Merchant\Repository)->findOrFailPublic($merchantId);

            if ($merchant->isFeatureEnabled(Feature\Constants::EXCLUDE_DISPUTE_PRESENTMENT) === false)
            {
                return 'emails.dispute.bulk_creation_dispute_presentment_enabled';
            }

            return 'emails.dispute.bulk_creation';
        }
        catch (\Exception $exception)
        {
            return 'emails.dispute.bulk_creation';
        }
    }
}

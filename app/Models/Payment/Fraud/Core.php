<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\CyberCrimeHelpDesk\Service as CyberHelpDeskService;
use RZP\Services\Shield;
use RZP\Models\Merchant\Fraud\BulkNotification;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use View;

use RZP\Models\CyberCrimeHelpDesk\Constants as CyberHelpdeskConstants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;
use mikehaertl\tmp\File;
use RZP\Models\FileStore;
class Core extends Base\Core
{
    public function notifyFraud($fraudEntity)
    {
        $payment = $this->repo->payment->findOrFailPublic($fraudEntity->getPaymentId());

        $fraudRowResult = BulkNotification\Processor::getFraudNotificationRowData($payment, $fraudEntity);

        if (in_array($fraudEntity->getReportedBy(), Constants::CARD_NETWORK_SOURCES, true) === true)
        {
            $isCardNetworkRequest = true;
            $fraudRowResult[Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION] = Constants::SOURCE_BANK;
        }
        else
        {
            $isCardNetworkRequest = false;
            $fraudRowResult[Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION] = Constants::SOURCE_CYBERCELL;
        }

        (new BulkNotification\Freshdesk(new BulkNotification\Entity(), null))->notifySingle([$fraudRowResult], $payment->getMerchantId(), $isCardNetworkRequest);
    }

    public function notifyFraudVIAWhatsAPP($fraudEntity, $merchantId, $type){

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['trace']->info(
            TraceCode::WHATSAPP_FRAUD_MESSAGE_FOR_SINGLE_MERCHANT,
            [
                MerchantConstants::FRAUD_ENTITY          => $fraudEntity,
            ]);

        $contact = $merchant->merchantDetail->getContactMobile();
        try
        {
            if (isset($contact)===true)
            {
                $this->generatePDFAndSendWhatsapp($merchant, $fraudEntity, $contact, $type);
            }
            else
            {
                $this->app['trace']->info(
                    TraceCode::WHATSAPP_FRAUD_NOTIFICATION_NOT_SENT,
                    [
                        MerchantConstants::MERCHANT                     => 'Contact details does not exists.',
                        MerchantConstants::MERCHANT_DETAILS             => $merchant->merchantDetail,
                    ]);
            }
        }
        catch (Exception\BaseException $e)
        {
            $this->app['trace']->info(
                TraceCode::WHATSAPP_FRAUD_NOTIFICATION_ERROR,
                [
                    MerchantConstants::MESSAGE  => 'Exception Occured while process Whatsapp Notification'." ".$e->getMessage(),
                ]);
        }

        $this->app['trace']->info(TraceCode::WHATSAPP_FRAUD_NOTIFICATION_SENDING_COMPLETE);

    }

    public function generatePDFAndSendWhatsapp($merchant, $fraudEntity, $contact, $type)
    {
        $options = [
            'print-media-type',
            'header-html' => new File(CyberHelpdeskConstants::HEADER_FILE_NAME, '.html'),
            'header-spacing' => '-18',
            'footer-font-size' => '6',
            'footer-right' => 'Page [page] of [topage]',
            'footer-left' => 'Date and Time: ' . Carbon::createFromTimestamp(Carbon::now()->getTimestamp(),
                    Timezone::IST)
                    ->format(CyberHelpdeskConstants::DATE_FORMAT),
            'dpi' => 290,
            'zoom' => 1,
            'ignoreWarnings' => false,
            'encoding' => 'UTF-8',
        ];

        $viewTemplate = CyberHelpdeskConstants::CYBER_HELPDESK_WHATSAPP_TEMPLATE;

        if($type == 'single')
        {
            $html = View::make($viewTemplate, $fraudEntity)->with('paymentsDataTable', $this->createPaymentsDataTable($fraudEntity))->render();
        }
        else
        {
            $html = View::make($viewTemplate, $fraudEntity)->with('paymentsDataTable', $this->createPaymentsDataTableBulk($fraudEntity))->render();
        }

        (new CyberHelpDeskService())->createdPDFAndSendWhatsApp($options, $html, $merchant, $contact);
    }

    public function createPaymentsDataTable($fraudEntity){
        $payment = $fraudEntity;

        $tableData = [];

        $tableRow = array();
        $tableRow[CyberHelpdeskConstants::PAYMENT_ID]       = $payment[CyberHelpdeskConstants::PAYMENT_ID];
        $tableRow[CyberHelpdeskConstants::AMOUNT]           = $payment[CyberHelpdeskConstants::AMOUNT];
        $tableRow[CyberHelpdeskConstants::SOURCE]           = $payment[CyberHelpdeskConstants::REPORTED_BY];
        $tableRow[CyberHelpdeskConstants::CREATED_DATE]     = date('Y-m-d H:i:s', $payment[CyberHelpdeskConstants::CREATED_DATE]);
        $tableRow[CyberHelpdeskConstants::RESPOND_BY]       = 'Within 24 hrs.';

        $tableData[] = $tableRow;
        return $tableData;
    }

    public function createPaymentsDataTableBulk($fraudEntity){
        $tableData = [];

        foreach ($fraudEntity as $payment){

            $tableRow = array();
            $tableRow[CyberHelpdeskConstants::PAYMENT_ID]       = $payment[CyberHelpdeskConstants::PAYMENT_ID];
            $tableRow[CyberHelpdeskConstants::AMOUNT]           = $payment[CyberHelpdeskConstants::AMOUNT];
            $tableRow[CyberHelpdeskConstants::SOURCE]           = $payment[CyberHelpdeskConstants::SOURCE_OF_NOTIFICATION];
            $tableRow[CyberHelpdeskConstants::CREATED_DATE]     = date('Y-m-d H:i:s', $payment[CyberHelpdeskConstants::CREATED_DATE]);
            $tableRow[CyberHelpdeskConstants::RESPOND_BY]       = $payment[CyberHelpdeskConstants::RESPOND_BY];
            $tableData[] = $tableRow;
        }
        return $tableData;
    }

    public function createOrUpdateFraudEntity($input): array
    {
        (new Validator())->validateInput('create_or_update_entity', $input);

        $paymentId = $input[Entity::PAYMENT_ID];

        $reportedBy = $input[Entity::REPORTED_BY];

        $fraudEntity = $this->repo->payment_fraud->fetch([
            Entity::PAYMENT_ID  => $input[Entity::PAYMENT_ID],
            Entity::REPORTED_BY => $input[Entity::REPORTED_BY],
        ])->first();

        if (isset($fraudEntity) === true)
        {
            $this->repo->payment_fraud->update($paymentId, $reportedBy, $input);

            return [false, $fraudEntity->refresh()];
        }
        else
        {
            $fraudEntity = (new Entity)->build($input);

            $this->repo->payment_fraud->saveOrFail($fraudEntity);

            $event = $this->app['diag']->trackPaymentFraudEvent(EventCode::PAYMENT_FRAUD_CREATED, $fraudEntity);

            (new Shield($this->app))->enqueueShieldEvent($event);

            return [true, $fraudEntity];
        }
    }
}

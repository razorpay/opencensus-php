<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;

use Mail;
use RZP\Constants\Entity as EntityConstants;
use RZP\lib\TemplateEngine;
use RZP\Mail\Merchant\SelfServeEscalationEmail;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;
use RZP\Models\Merchant\AutoKyc\Escalations\Entity;
use RZP\Models\Merchant\AutoKyc\Escalations\Utils;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Models\Admin\Permission;
use RZP\Trace\TraceCode;


class Email extends BaseEscalationType
{
    const ESCALATION_MAIL_SUBJECT_TEMPLATE = 'Self Serve Escalation: {type} | Level: {level}';

    public function triggerEscalation($merchants,$merchantsGmvList, string $type, int $level)
    {
        $this->createEscalationsV1($merchants, $type, $level,Constants::EMAIL);

        $this->sendEscalationEmail($merchants, $type, $level);
    }

    private function sendEscalationEmail($merchants,string $type, string $level)
    {
        $actions = (new ActionCore())->fetchOpenActionOnEntityListOperation(
            Utils::getMerchantIdList($merchants),
            EntityConstants::MERCHANT_DETAIL,
            Permission\Name::AUTO_KYC_SOFT_LIMIT_BREACH
        );

        $merchantData = [];
        $merchantActionMap = [];

        foreach ($actions as $action)
        {
            $merchantActionMap[$action->getEntityId()] = $action;
        }

        foreach ($merchants as $merchant)
        {
            $merchantData[] = $this->getDataForMerchant($merchant, $merchantActionMap);
        }

        $data = [
            'recipients'    => $this->getEmailRecipientsForEscalation($type, $level),
            'merchants'     => $merchantData
        ];

        $email = new SelfServeEscalationEmail($this->getSubject($type, $level), $data);

        Mail::queue($email);
    }

    private function getDataForMerchant($merchant, $merchantActionMap)
    {
        $dashboardUrl = $this->app['config']->get('applications.dashboard.url');
        $url = '';
        $merchantId = $merchant->getId();
        if (isset($merchantActionMap[$merchantId]))
        {
            $url = $dashboardUrl . "admin/requests/w_action_" . $merchantActionMap[$merchantId]->getId();
        }
        return [
            'merchantId'    => $merchantId,
            'activationStatus' => $merchant->merchantDetail->getActivationStatus(),
            'workflowUrl'   => $url,
            'businessType' => $merchant->merchantDetail->getBusinessType()
        ];
    }

    private function getSubject(string $type, int $level)
    {
        return (new TemplateEngine)->render(self::ESCALATION_MAIL_SUBJECT_TEMPLATE, [
            'type'  => $type,
            'level' => $level
        ]);
    }

    private function getEmailRecipientsForEscalation(string $type, string $level)
    {
        $emailStr = env(strtoupper($type). "_ESCALATION_LEVEL_". $level. "_MAILING_LIST");

        $emailList = [];
        if(empty($emailStr) === false)
        {
            $emailList = explode(',', $emailStr);
        }

        return array_merge($emailList, Constants::ADMIN_EMAIL_LIST);
    }
}

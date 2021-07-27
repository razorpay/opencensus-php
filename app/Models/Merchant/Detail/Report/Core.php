<?php


namespace RZP\Models\Merchant\Detail\Report;

use Mail;
use App;
use RZP\Exception;
use RZP\Mail\Merchant\Report\MerchantReport;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Http\BasicAuth\BasicAuth;

class Core extends Base\Core
{
    /**
     * BasicAuth entity
     * @var BasicAuth
     */
    protected $auth;

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];
    }


    public function sendReport(array $input)
    {
        $orgId = $this->auth->getOrgId();
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        (new Validator)->validateReportInput($orgId, $input);

        $report = $input[Constants::REPORT];

        $config = Constants::REPORT_CONFIG[$report];

        switch ($config[Constants::MODE])
        {
            case Constants::EMAIL:
                $this->sendEmail($config, $report);
                break;
            default:
                throw new Exception\LogicException('invalid mode for report');
        }
    }

    public function sendEmail($config, $report)
    {
        $processorClazz = $config[Constants::DATA_PROCESSOR];

        $data = (new $processorClazz)->process();

        if(empty($data) === false)
        {
            $email = new MerchantReport(
                $config[Constants::TEMPLATE],
                $config[Constants::SUBJECT],
                $this->getReportRecipients($report),
                $data
            );

            Mail::queue($email);
        }
    }

    private function getReportRecipients($report)
    {
        $emailStr = env(strtoupper($report) . "_MAILING_LIST");
        $emailList = [];
        if(empty($emailStr) === false)
        {
            $emailList = explode(',', $emailStr);
        }

        return array_merge($emailList, Constants::ADMIN_EMAIL_LIST);
    }
}

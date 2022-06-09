<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use Redirect;
use ApiResponse;
use Illuminate\Support\Facades\File;

use RZP\Constants\Environment;
use RZP\Constants\Entity;
use RZP\Models\Admin;
use RZP\Models\Merchant\Account;
use RZP\Models\Report;
use RZP\Models\Terminal;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class AdminController extends Controller
{
    protected $service = Admin\Service::class;

    public function getEntities()
    {
        $input = Request::all();

        $data = (new Admin\Service)->getAllEntities($input);

        return ApiResponse::json($data);
    }

    public function getEntitiesAxis()
    {
        $input = Request::all();

        $data = (new Admin\Service)->getAllEntitiesAxisAdmin($input);

        return ApiResponse::json($data);
    }

    public function getEntitiesForExternalAdmin()
    {
        $input = Request::all();

        $data = (new Admin\Service)->getAllEntities($input, true);

        return ApiResponse::json($data);
    }

    public function getAxisPaysecurePayments()
    {
        $input = Request::all();

        $entity = Entity::PAYMENT;

        $data = (new Admin\Service)->fetchAxisPaysecurePayments($entity, $input);

        return ApiResponse::json($data);
    }

    public function getEntityMultiple($type)
    {
        $input = Request::all();

        $data = $this->service()->fetchMultipleEntities($type, $input);

        return ApiResponse::json($data);
    }

    public function getEntityMultipleForExternalAdmin($type)
    {
        $input = Request::all();

        $data = $this->service()->fetchMultipleEntities($type, $input, true);

        return ApiResponse::json($data);
    }

    public function getTerminalById($id)
    {
        //In production all the terminals are fetched from the terminals service, a prod check included for unit testing cases
        if( in_array($this->app['env'], [Environment::PRODUCTION, Environment::AUTOMATION, Environment::BVT, Environment::BETA, Environment::FUNC], true) === true ) {

            $path = "v1/admin/terminals/" . $id;

            try
            {
                $headers = $this->app['terminals_service']->getTerminalServiceOrgHeaders();
            }
            catch (\Exception $e)
            {
                $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::TERMINAL_ORG_HEADERS_EXCEPTION, [
                    'message' => $e->getMessage(),
                    'location' => 'getTerminalById',
                ]);
            }

            $this->trace->info(TraceCode::ENTITY_ORG_ID, [
                'headers' => $headers,
            ]);
            //Increasing the timeout value to 40 Seconds as terminals api default timeout is 30 seconds.
            $options = [];
            $options['timeout'] = 30;
            $options['connect_timeout'] = 10;

            $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path, $options, $headers);

            return ApiResponse::json($response);
        }
        else {
            // For Single Terminal fetch we want to display sub merchants
            // Setting sub_merchant will give sub_merchant associated with terminal
            $subMerchantFlag = true;

            $type = 'terminal';

            $data = $this->service()->fetchTerminalEntityByIdWithFlag($type, $id, $subMerchantFlag);

            return ApiResponse::json($data);
        }
    }

    public function getTerminalMultiple()
    {
        $input = Request::all();

        $type = 'terminal';

        $data = $this->service()->fetchMultipleEntities($type, $input);

        return ApiResponse::json($data);
    }

    public function getEntityById($type, $id)
    {
        $input = Request::all();

        $data = $this->service()->fetchEntityById($type, $id, $input);

        return ApiResponse::json($data);
    }

    public function getEntityByIdAxis($id)
    {
        $input = Request::all();

        $type = 'payment';

        $data = $this->service()->fetchEntityByIdForAxisRupayAdmin($type, $id, $input);

        return ApiResponse::json($data);
    }

    public function syncEntityById($type, $id)
    {
        $input = Request::all();

        $data = $this->service()->syncEntityById($type, $id, $input);

        return ApiResponse::json($data);
    }

    public function getEntityByIdForExternalAdmin($type, $id)
    {
        $input = Request::all();

        $data = $this->service()->fetchEntityById($type, $id, $input, true);

        return ApiResponse::json($data);
    }

    public function postSendTestNewsletter()
    {
        $input = Request::all();

        $data = $this->service()->sendTestNewsletter($input);

        return ApiResponse::json($data);
    }

    public function postSendNewsletter()
    {
        $input = Request::all();

        $data = $this->service()->sendNewsletter($input);

        return ApiResponse::json($data);
    }

    public function getTransparentRedirect()
    {
        $input = Request::all();

        if (isset($input['url']))
        {
            $url = $input['url'];
            unset($input['url']);

            $query = http_build_query($input);
            $url .= '?'.$query;

            return Redirect::to($url);
        }
    }

    public function postTransparentRedirect()
    {
        $input = Request::all();
    }

    public function setConfigKeys()
    {
        $input = Request::all();

        $data = $this->service()->setConfigKeys($input);

        return ApiResponse::json($data);
    }

    public function updateConfigKey()
    {
        $input = Request::all();

        $data = $this->service()->updateConfigKey($input);

        return ApiResponse::json($data);
    }

    public function getConfigKey()
    {
        $input = Request::all();

        $data = $this->service()->getConfigKey($input);

        return ApiResponse::json($data);
    }

    public function deleteConfigKey()
    {
        $input = Request::all();

        $data = $this->service()->deleteConfigKey($input);

        return ApiResponse::json($data);
    }

    public function getConfigKeys()
    {
        $data = $this->service()->getConfigKeys();

        return ApiResponse::json($data);
    }

    public function getQueryCacheCounts()
    {
        $data = $this->service()->getQueryCacheCounts();

        return ApiResponse::json($data);
    }

    public function getScorecard()
    {
        $input = Request::all();

        $data = $this->service()->generateScorecard($input);

        return ApiResponse::json($data);
    }

    public function generateBankingScorecard()
    {
        $input = Request::all();

        $data = $this->service()->generateBankingScorecard($input);

        return ApiResponse::json($data);
    }

    public function postMailgunCallback($type)
    {
        $input = Request::all();

        $responseStatus = $this->service()->processMailgunCallback($type, $input);

        return ApiResponse::json([], $responseStatus);
    }

    public function postSetCronJobCallback()
    {
        $input = Request::all();

        $this->service()->processSetCronJobCallback($input);

        return ApiResponse::json([]);
    }

    public function updateEntityTax($entity)
    {
        $input = Request::all();

        $limit = $input['limit'];

        $data = $this->service()->updateTaxColumnValue($entity, $limit);

        return ApiResponse::json($data);
    }

    public function updateGeoIps()
    {
        $input = Request::all();

        $data = $this->service()->updateGeoIps($input);

        return ApiResponse::json($data);
    }

    public function updateMdr()
    {
        $data = $this->service()->updateMdr();

        return ApiResponse::json($data);
    }

    public function dbMetaDataQuery()
    {
        $input = Request::all();

        $data = $this->service()->dbMetaDataQuery($input);

        return ApiResponse::json($data);
    }

    public function getDailyReconciliationStatusSummary()
    {
        $input = Request::all();

        $data = $this->service()->fetchReconciliationSummary($input);

        return ApiResponse::json($data);
    }

    public function getHourlyReconciliationStatusSummary()
    {
        $input = Request::all();

        $data = $this->service()->fetchHourlyReconciliationSummary($input);

        return ApiResponse::json($data);
    }

    public function createAdminBatch()
    {
        $input = Request::all();

        $data = $this->service()->createBatch($input);

        return ApiResponse::json($data);
    }

    public function validateFile()
    {
        $input = Request::all();

        $data = $this->service()->validateFile($input);

        return ApiResponse::json($data);
    }

    public function updateAdminThroughBatch()
    {
        $input = Request::all();

        $data = $this->service()->updateFromBatchService($input);

        return ApiResponse::json($data);
    }

    public function uploadFileAdmin(string $type)
    {
        $input = Request::all();

        $data = $this->service()->uploadFile($type, $input);

        return ApiResponse::json($data);
    }

    public function getOpsReportTypes()
    {
        $report = new Report\Types\OpsReport;

        $data = $report->getOpsReportTypes();

        return ApiResponse::json($data);
    }

    public function getOpsReport($type)
    {
        $report = new Report\Types\OpsReport;

        $data = $report->getOpsReport($type);

        return ApiResponse::json($data);
    }

    public function updateEntityBalanceIdInBulk(string $entity)
    {
        $response = $this->service()->updateEntityBalanceIdInBulk($entity, Request::all());

        return ApiResponse::json($response);
    }

    public function setRedisKeys()
    {
        $input  = Request::all();

        $data = $this->service()->setRedisKeys($input);

        return ApiResponse::json($data);
    }

    public function createVaultToken()
    {
        $input = Request::all();

        $response = $this->app['card.cardVault']->createVaultToken($input);

        return ApiResponse::json($response);
    }

    public function renewVaultToken()
    {
        $response = $this->app['card.cardVault']->renewVaultToken();

        return ApiResponse::json($response);

    }

    public function setGatewayDowntimeConf()
    {
        $input  = Request::all();

        $data = $this->service()->setGatewayDowntimeConf($input);

        return ApiResponse::json($data);
    }

    public function getGatewayDowntimeConf()
    {
        $data = $this->service()->getGatewayDowntimeConf();

        return ApiResponse::json($data);
    }

    public function sendTestSms()
    {
        $input = Request::all();

        $data = $this->service()->sendTestSms($input);

        return ApiResponse::json($data);
    }

    public function bulkCreateEntity()
    {
        $input = Request::all();

        $data  = $this->service()->bulkCreate($input);

        return ApiResponse::json($data);
    }

    public function updateMerchantPoc()
    {
        $input = Request::all();

        $this->service()->updateMerchantPoc($input);

        return ApiResponse::json([]);
    }
    public function updateMerchantPocWithTimeStamp()
    {
        $input = Request::all();

        $this->service()->updateMerchantPocWithTimeStamp($input);

        return ApiResponse::json([]);
    }

    public function unclaimedMerchantPoc()
    {
        $input = Request::all();

        $this->service()->unclaimedMerchantPoc($input);

        return ApiResponse::json([]);
    }

    public function getPvtResponse()
    {
        $input = Request::all();

        $response = $this->service()->getPvtResponse($input);

        return ApiResponse::json($response);
    }

    /**
     * Posts request to stork on given path.
     */
    public function postStork(string $path)
    {
        $mode  = $this->ba->getMode();
        $input = Request::all();
        $this->trace->info(TraceCode::STORK_ADMIN_REQUEST, compact('mode', 'input'));

        $service = new Stork;
        $service->init($mode);

        $response = $service->request($path, $input);
        $code     = $response->status_code;
        $body     = json_decode($response->body, true);
        $response = compact('code', 'body');

        $this->trace->info(TraceCode::STORK_ADMIN_RESPONSE, $response);
        return ApiResponse::json($response);
    }

    public function getModeConfigInstruments()
    {
        $data = $this->service()->getModeConfigInstruments();

        return ApiResponse::json($data);
    }

    public function setWaitTimeout()
    {
        $input = Request::all();

        $oldValue = File::get(base_path() . '/database/wait_timeout', true);

        if (empty($input['value']) === false)
        {
            File::put(base_path() . '/database/wait_timeout', $input['value']);
        }

        $value = File::get(base_path() . '/database/wait_timeout', true);

        $data = [
            'value' => $value,
            'old_value' => $oldValue,
        ];

        return ApiResponse::json($value);
    }

    public function enableInstantRefunds($id)
    {
        $input = Request::all();

        $response = $this->service()->enableInstantRefunds($id,$input);

        return ApiResponse::json($response);
    }

    public function disableInstantRefunds($id)
    {
        $input = Request::all();

        $response = $this->service()->disableInstantRefunds($id,$input);

        return ApiResponse::json($response);
    }
    public function toggleWhatsappNotification(string $id)
    {
        $input = Request::all();

        $response  = $this->service()->toggleWhatsappNotification($id,$input);

        return ApiResponse::json($response);
    }


}

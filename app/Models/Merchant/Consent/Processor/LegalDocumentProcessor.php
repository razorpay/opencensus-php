<?php

namespace RZP\Models\Merchant\Consent\Processor;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;
use RZP\Models\Merchant\Consent\Processor\Processor;
use RZP\Models\Merchant\AutoKyc\Response as Response;
use RZP\Models\Merchant\Detail\Service as DetailService;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ConsentDocumentBaseResponse;

class LegalDocumentProcessor implements Processor
{
    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->trace = $this->app['trace'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
    }

    /**
     * @param null $merchant
     * @param array|null $input
     * @param string $platform
     * @param bool $isExpEnabled
     * @return LegalDocumentBaseResponse|ConsentDocumentBaseResponse
     * @throws IntegrationException
     */
    public function processLegalDocuments($merchant, array $input = null, string $platform = 'pg', bool $isExpEnabled = false): LegalDocumentBaseResponse|ConsentDocumentBaseResponse
    {
        if ($merchant === null) {
            $this->trace->info(TraceCode::ERROR_FETCHING_MERCHANT_DETAILS);
            throw new IntegrationException('Merchant context not present in the request', ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND);
        }

        $documents_detail = $input[DEConstants::DOCUMENTS_DETAIL];

        $notificationDetails = $input[DEConstants::NOTIFICATION_DETAILS];

        // RazorpayX has no concept of PromoterPan Name during signup so, we will be using merchant name instead.
        $signatory_name = $platform === 'rx' ? $merchant->getName() : ($merchant->merchantDetail->getPromoterPanName()) ?? ($merchant->getName());

        if (isset($input[DEConstants::SIGNATORY_NAME]) === true)
        {
            $signatory_name = $input[DEConstants::SIGNATORY_NAME];
        }

        $ownerName = $input[DEConstants::OWNER_NAME] ?? $merchant->merchantDetail->getBusinessName();

        $ownerDetails = [
            "owner_id"             => $merchant->getMerchantId(),
            "ip_address"           => $input[DEConstants::IP_ADDRESS] ?? $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip(),
            "acceptance_timestamp" => $input[DEConstants::DOCUMENTS_ACCEPTANCE_TIMESTAMP] ?? Carbon::now()->getTimestamp(),
            "signatory_name"       => $signatory_name,
            "owner_name"           => $ownerName,
            "contact_number"       => $merchant->merchantDetail->getContactMobile(),
            "email"                => $merchant->getEmail(),
            "time_zone"            => Timezone::getTimeZoneAbbrevation($merchant->getTimeZone()),
        ];

        if($isExpEnabled === true && (new AccessMapCore)->isSubMerchant($ownerDetails['owner_id']) === true)
        {
            (new DetailService())->addPartnerDetailsAsOwner($ownerDetails, $input);
        }

        $body = [
            "client_details"        => ['platform' => $platform],
            "owner_details"         => $ownerDetails,
            "documents_detail"      => $documents_detail,
            "send_email"            => $notificationDetails['send_email'],
            "send_sms"              => $notificationDetails['send_sms']
        ];

        if ($isExpEnabled === false)
        {
            $response = app('bvs_legal_document_manager')->createLegalDocument($body);

            $this->trace->info(TraceCode::BVS_RESPONSE_CREATE_CONSENTS, [
                'id'     => $response->getId(),
                'status' => $response->getStatus()
            ]);

            return new LegalDocumentBaseResponse($response);
        }
        else
        {
            if (isset($notificationDetails['send_email']) === true and $notificationDetails['send_email'] === true)
            {
                $body["email_details"] = $notificationDetails["email_details"] ?? null;
            }

            if (isset($notificationDetails['send_sms']) === true and $notificationDetails['send_sms'] === true)
            {
                $body["sms_details"] = $notificationDetails["sms_details"] ?? null;
            }

            $response = app('bvs_legal_document_manager')->createLegalDocumentV2($body, $merchant);

            $this->trace->info(TraceCode::BVS_RESPONSE_CREATE_CONSENTS_V2, [
                'id'     => $response->getId(),
                'status' => $response->getStatus()
            ]);

            return new ConsentDocumentBaseResponse($response);
        }
    }

    public function setMerchant(Merchant\Entity $merchant)
    {
        $this->merchant = $merchant;
    }
}

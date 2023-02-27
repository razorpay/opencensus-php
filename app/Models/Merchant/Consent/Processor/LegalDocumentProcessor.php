<?php

namespace RZP\Models\Merchant\Consent\Processor;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\Consent\Processor\Processor;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant;

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
     * @param array|null $input
     * @param string     $platform
     *
     * @return LegalDocumentBaseResponse
     */
    public function processLegalDocuments(array $input = null, string $platform = 'pg')
    {
        $documents_detail = $input[DEConstants::DOCUMENTS_DETAIL];

        // RazorpayX has no concept of PromoterPan Name during signup so, we will be using merchant name instead.
        $signatory_name = $platform === 'rx' ? $this->merchant->getName() : ($this->merchant->merchantDetail->getPromoterPanName())??($this->merchant->getName());

        if (isset($input[DEConstants::SIGNATORY_NAME]) === true)
        {
            $signatory_name = $input[DEConstants::SIGNATORY_NAME];
        }

        $ownerName = $input[DEConstants::OWNER_NAME] ?? $this->merchant->merchantDetail->getBusinessName();

        $ownerDetails = [
            "owner_id"             => $this->merchant->getMerchantId(),
            "ip_address"           => $input[DEConstants::IP_ADDRESS] ?? $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip(),
            "acceptance_timestamp" => $input[DEConstants::DOCUMENTS_ACCEPTANCE_TIMESTAMP] ?? Carbon::now()->getTimestamp(),
            "signatory_name"       => $signatory_name,
            "owner_name"           => $ownerName,
            "contact_number"       => $this->merchant->merchantDetail->getContactMobile(),
            "email"                => $this->merchant->getEmail(),
        ];

        $body = [
            "client_details"   => ['platform' => $platform],
            "owner_details"    => $ownerDetails,
            "documents_detail" => $documents_detail
        ];

        $response = app('bvs_legal_document_manager')->createLegalDocument($body);

        $this->trace->info(TraceCode::BVS_RESPONSE_CREATE_CONSENTS, [
            'id'     => $response->getId(),
            'status' => $response->getStatus()
        ]);

        return new LegalDocumentBaseResponse($response);
    }

    public function setMerchant(Merchant\Entity $merchant)
    {
        $this->merchant = $merchant;
    }
}

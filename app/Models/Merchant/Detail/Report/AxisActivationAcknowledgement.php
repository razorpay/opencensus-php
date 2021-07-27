<?php


namespace RZP\Models\Merchant\Detail\Report;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Base\RepositoryManager;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Entity as MEntity;
use RZP\Models\Merchant\Detail\Entity as DEntity;

class AxisActivationAcknowledgement implements Processor
{
    const REPORT_TIMES_IN_HOURS = [10, 14, 18, 22];

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];
    }

    public function process()
    {
        $merchantsData = [];

        [$from, $to] = $this->getTimeFrame();

        $createdMerchants = $this->repo->merchant->fetchMerchantsCreatedBetweenForOrg($from, $to, MEntity::AXIS_ORG_ID);
        $activatedMerchants = $this->repo->merchant->fetchMerchantsActivatedBetweenForOrg($from, $to, MEntity::AXIS_ORG_ID);

        $this->setMerchantsData($createdMerchants,$merchantsData);
        $this->setMerchantsData($activatedMerchants,$merchantsData);

        return [
            'merchants' => $merchantsData
        ];
    }

    private function setMerchantsData($merchants, & $merchantsData)
    {
        foreach ($merchants as $merchant)
        {
            $merchantsData[$merchant->getId()] = [
                DEntity::MERCHANT_ID    => $merchant->getId(),
                DEntity::CONTACT_NAME   => $merchant->merchantDetail->getContactName(),
                DEntity::ACTIVATION_STATUS  => $merchant->merchantDetail->getActivationStatus(),
                DEntity::CREATED_AT         => Carbon::createFromTimestamp($merchant->getCreatedAt())->toDateTimeString(),
                MEntity::ACTIVATED_AT       => Carbon::createFromTimestamp($merchant->getActivatedAt())->toDateTimeString()
            ];
        }
    }

    private function getTimeFrame()
    {
        $from = Carbon::createMidnightDate()->getTimestamp();
        $to = Carbon::createMidnightDate()->addDay(1)->getTimestamp();

        return [$from, $to];
    }
}

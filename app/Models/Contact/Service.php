<?php

namespace RZP\Models\Contact;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Services\Segment\EventCode as SegmentEvent;
use Symfony\Component\HttpFoundation\Response;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount\Service as FundAccountService;

/**
 * Class Service
 *
 * @package RZP\Models\Contact
 */
class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    /**
     * @var FundAccountService
     */
    protected $fundAccountService;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->contact;

        $this->fundAccountService = new FundAccountService;
    }

    /**
     * The contact creation logic checks if there is a duplicate present and whether to
     * return the duplicate contact or create a new one. By default,
     * if some merchant wants duplicate creation, he will inform RZP
     * and we will put him behind razorx feature. In this case duplicate will be created.
     *
     * ToDo https://razorpay.atlassian.net/browse/RX-848
     *
     * @param array $input
     *
     * @return array
     */
    public function create(array $input): array
    {
        $entity = $this->core->create($input, $this->merchant);

        $responseCode = ($entity->wasRecentlyCreated === true) ? Response::HTTP_CREATED : Response::HTTP_OK;

        if($responseCode === Response::HTTP_CREATED){

            $merchant = $this->app['basicauth']->getMerchant();
            $user = $this->app['basicauth']->getUser() ?? $merchant->users()->first();
            if(empty($user) === false and empty($merchant) === false)
            {
                $customProperties = [
                    'phone' => ($user['contact_mobile'] === null) ? null : ('+'.$user['contact_mobile']),
                    'email' => $user['email'],
                ];

                //$this->app['x-segment']->pushIdentifyandTrackEvent($merchant, $customProperties, SegmentEvent::CONTACT_CREATED);
            } else{
                $this->trace->info(TraceCode::USER_FETCH_FAILED_FOR_SEGMENT_EVENT,
                    [
                        'merchant_id' => $merchant['id']?? null,
                    ]);
            }
        }

        $this->trace->info(TraceCode::CONTACT_CREATION_RESPONSE,
            [
                Constants\Entity::CONTACT => $entity->getId(),
                Entity::RESPONSE_CODE     => $responseCode,
            ]);

        return [
            Constants\Entity::CONTACT => $entity->toArrayPublic(),
            Entity::RESPONSE_CODE     => $responseCode,
        ];
    }

    public function createForCompositePayout(array $input,
                                             array $traceData,
                                             Merchant\Entity $merchant,
                                             bool $compositePayoutSaveOrFail = true,
                                             array $metadata = []): Entity
    {
        return $this->core->createForCompositeRequest($input, $merchant, $traceData, $compositePayoutSaveOrFail, $metadata);
    }

    public function fetch(string $id, array $input): array
    {
        $merchant = $this->merchant;

        $contact = $this->core->fetch($id, $merchant, $input);

        return $contact->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {

        // This is a temporary solution to hide junk data from X Demo accounts.
        if ($this->merchant->isXDemoAccount())
        {
            $prevDt = isset($input['from']) ? (int)$input['from'] : 0;

            $maxFrom = max($prevDt, Carbon::now(Timezone::IST)->timestamp - Constants\BankingDemo::MAX_TIME_DURATION);

            $input['from'] = (string)$maxFrom;
        }

        $startTimeMs = round(microtime(true) * 1000);

        $entities = $this->core
            ->fetchMultiple($this->merchant, $input);

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        if($totalFetchTime > 500) {

            $this->trace->info(TraceCode::CONTACT_API_FETCH_DURATION, [
                'duration_ms' => $totalFetchTime,
                'merchant_id' => $this->merchant->getId(),
            ]);
        }
        return $entities->toArrayPublic();
    }

    public function getTypes(): array
    {
        return (new Type)->getAll($this->merchant);
    }

    public function postType(array $input): array
    {
        (new Validator)->validateInput('create_type', $input);

        $typeObj = new Type;

        $typeObj->addNewCustom($input[Entity::TYPE], $this->merchant);

        return $typeObj->getAll($this->merchant);
    }
}

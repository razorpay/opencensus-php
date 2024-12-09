<?php

namespace RZP\Models\Contact;

use Carbon\Carbon;
use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount\Entity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use Symfony\Component\HttpFoundation\Response;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Address;
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
    const CONTACT_MUTEX_LOCK_TIMEOUT = 180;

    const MUTEX_RESOURCE             = 'CONTACT_%s_%s';

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

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->contact;

        $this->fundAccountService = new FundAccountService;

        $this->mutex = $this->app['api.mutex'];
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
        $batchId = (isset($input[Entity::BATCH_ID]) === true) ? $input[Entity::BATCH_ID] : null;

        $properties = [
            'id'            => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.mutex_lock_contact_experiment_id'),
            'request_data' => json_encode(['merchant_id' => $this->merchant->getId()])
        ];
        $isMutexLockForContactExperimentEnabled = $this->core->isSplitzExperimentEnabled($properties, 'variables', TraceCode::MUTEX_LOCK_CONTACT_SPLITZ_ERROR);

        if ($isMutexLockForContactExperimentEnabled) {
            $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->merchant->getId(), $this->core->getHashOfRequestBody($input));

            $entity = $this->mutex->acquireAndRelease(
                $mutexResource,
                function () use ($input, $batchId) {
                    return $this->core->create($input, $this->merchant, $batchId);
                },
                self::CONTACT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_CONTACT_OPERATION_IN_PROGRESS
            );
        } else {
            $entity = $this->core->create($input, $this->merchant, $batchId);
        }

        $responseCode = ($entity->wasRecentlyCreated === true) ? Response::HTTP_CREATED : Response::HTTP_OK;

        $this->trace->info(TraceCode::CONTACT_CREATION_RESPONSE,
            [
                Constants\Entity::CONTACT => $entity->getId(),
                Entity::RESPONSE_CODE     => $responseCode,
            ]);

        if ($input['isComposite'] === true)
        {
            return [
                Constants\Entity::CONTACT => $entity->toArrayPublic(),
                Entity::CONTACT_ENTITY    => $entity,
                Entity::RESPONSE_CODE     => $responseCode,
            ];
        }
        else
        {
            return [
                Constants\Entity::CONTACT => $entity->toArrayPublic(),
                Entity::RESPONSE_CODE => $responseCode,
            ];
        }
    }

    public function createForCompositePayout(array $input,
                                             array $traceData,
                                             Merchant\Entity $merchant,
                                             bool $compositePayoutSaveOrFail = true,
                                             array $metadata = []): \RZP\Models\Contact\Entity
    {
        return $this->core->createForCompositeRequest($input, $merchant, $traceData, $compositePayoutSaveOrFail, $metadata);
    }

    public function fetch(string $id, array $input): array
    {
        $merchant = $this->merchant;

        $contact = $this->core->fetch($id, $merchant, $input);

        return $contact->toArrayPublic();
    }

    public function getContactDetailsForCheckout(string $id, $input): array
    {
        $contact = $this->fetch($id, $input);

        // send empty object in response instead of empty array
        $contact['notes'] = (object) ($contact['notes'] ?? []);

        if (isset($contact['vendor'])) {
            $contact['vendor'] = (object) ($contact['vendor'] ?? []);
        }

        if (isset($contact['fund_accounts'])) {
            foreach ($contact['fund_accounts'] as $i => $fundAccount) {
                if (isset($fundAccount['bank_account'])) {
                    $contact['fund_accounts'][$i]['bank_account']['account_number'] = mask_except_last4(
                        $contact['fund_accounts'][$i]['bank_account']['account_number']
                    );
                    $contact['fund_accounts'][$i]['bank_account']['notes'] =
                        (object)($fundAccount['bank_account']['notes'] ?? []);
                }
            }
        }

        return $contact;
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

        $typeObj->addNewCustom($input[\RZP\Models\Contact\Entity::TYPE], $this->merchant);

        return $typeObj->getAll($this->merchant);
    }

    public function createAddress($contactId, array $input)
    {
        $contact = $this->repo->contact->findByPublicIdAndMerchant(
            $contactId, $this->merchant);

        $address = (new Address\Core)->create($contact, Address\Type::CONTACT, $input);

        return $address->toArrayPublic();
    }

    public function fetchAddresses($contactId, array $input)
    {
        Entity::verifyIdAndStripSign($contactId);

        $contact = $this->repo->contact->findByIdAndMerchant($contactId, $this->merchant);

        $addresses = $this->repo->address->fetchAddressesForEntity($contact, $input);

        return $addresses->toArrayPublic();
    }

    public function fetchAddress($contactID, $addressID)
    {
        Entity::verifyIdAndStripSign($contactID);

        Address\Entity::verifyIdAndStripSign($addressID);

        $contact = $this->repo->contact->findByIdAndMerchant($contactID, $this->merchant);

        $addresses = $this->repo->address->findByEntityAndId($addressID, $contact);

        return $addresses->toArrayPublic();
    }
}

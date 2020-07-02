<?php

namespace RZP\Models\Contact;

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
        $createDuplicate = ($this->shouldCreateDuplicateContacts() === true);

        $entity = $this->core->create($input, $this->merchant, null, $createDuplicate);

        $responseCode = ($entity->wasRecentlyCreated === true) ? Response::HTTP_CREATED : Response::HTTP_OK;

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

    public function fetch(string $id, array $input): array
    {
        $merchant = $this->merchant;

        $contact = $this->core->fetch($id, $merchant, $input);

        return $contact->toArrayPublic();
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

    // ToDo https://razorpay.atlassian.net/browse/RX-849
    protected function shouldCreateDuplicateContacts()
    {
        $merchant = $this->merchant;

        $variant  = $this->app['razorx']->getTreatment($merchant->getId(),
                                                       Merchant\RazorxTreatment::X_CONTACT_AND_FUND_ACCOUNT_CREATION,
                                                       $this->mode);

        $flag = ($variant === 'create_duplicate') ? true : false;

        return $flag;
    }

    public function trimContactNameAndType(Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        $contacts = $this->repo->contact->fetchContactWithMerchantIdAndLimit1000($merchantId);

        while (count($contacts) > 0)
        {
            $lastContactCreatedAt = 0;

            foreach ($contacts as $contact)
            {
                $contactType = $contact->getType();

                $trimmedContactType = trim($contactType);

                $contactName = $contact->getName();

                $trimmedContactName = trim($contactName);

                if (($contactType > $trimmedContactType) ||
                    ($contactName > $trimmedContactName))
                {
                    $contact->setType($trimmedContactType);

                    $contact->setName($trimmedContactName);

                    $contact->saveOrFail();

                    $this->trace->info(
                        TraceCode::CONTACT_NAME_AND_TYPE_TRIMMED,
                        [
                            'contact_id' => $contact->getId(),
                        ]
                    );
                }

                $lastContactCreatedAt = $contact->getCreatedAt();
            }

            $contacts = $this->repo->contact->fetchContactWithMerchantIdAndLimit1000($merchantId, $lastContactCreatedAt);
        }

        $this->trace->info(
            TraceCode::CONTACT_NAME_AND_TYPE_TRIMMED_FOR_MERCHANT,
            [
                'merchant_id' => $merchant->getId()
            ]
        );
    }
}

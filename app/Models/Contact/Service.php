<?php

namespace RZP\Models\Contact;

use Symfony\Component\HttpFoundation\Response;

use RZP\Constants;
use RZP\Models\Base;
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

    public function create(array $input): array
    {
        // The contact creation logic checks if there is a duplicate present and whether to
        // return the duplicate contact or create a new one. This decision will be based
        // on where the request is coming from. If the request comes from the dashboard
        // every time a new contact will be created and if from API then a duplicate will be
        // returned if found. The choice is made as we want the contact creation flow to be
        // same for now on dashboard. Eventually once the designs will be ready, contact
        // creation flow will be different for the dashboard

        $responseCode  = Response::HTTP_OK;

        // request comes from API
        if ($this->auth->isStrictPrivateAuth() === true)
        {
            $entity = $this->core->create($input, $this->merchant, true);

            $responseCode = $entity->wasRecentlyCreated === true ? Response::HTTP_CREATED : $responseCode;
        }
        else
        {
            $entity = $this->core->create($input, $this->merchant);
        }

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
}

<?php

namespace RZP\Models\Contact;

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

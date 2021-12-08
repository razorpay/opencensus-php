<?php
namespace RZP\Models\CorporateCard;

use RZP\Models\Base;
use Symfony\Component\HttpFoundation\Response;
use RZP\Models\Merchant\Request\Service as MerchantRequestService;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input, string $token)
    {
        $this->consumeToken($token);

        $entity = $this->core->create($input, $this->merchant);

        return [
            \RZP\Constants\Entity::CORPORATE_CARD => $entity->toArrayPublic(),
            Entity::RESPONSE_CODE                 => Response::HTTP_CREATED,
        ];
    }

    public function update(string $id, array $input)
    {
        /** @var Entity $card */
        $card = $this->repo->corporate_card->findByPublicIdAndMerchant($id, $this->merchant);

        $entity = $this->core->edit($card, $input);

        return $entity->toArrayPublic();
    }

    public function fetchById(string $id)
    {
        /** @var Entity $card */
        $card = $this->repo->corporate_card->findByPublicIdAndMerchant($id, $this->merchant);

        return $card->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        /** @var Base\PublicCollection $cards */
        $cards = $this->repo->corporate_card->fetch($input, $this->merchant->getId());

        return $cards->toArrayPublic();
    }

    public function consumeToken(string $token)
    {
        $this->validateInputToken([Entity::TOKEN => $token]);

        $merchantRequestService = new MerchantRequestService();

        $this->merchant = $merchantRequestService->consumeOneTimeToken($token);
    }

    public function isValidToken(array $input) : bool {
        $this->validateInputToken($input);
        return $this->isTokenExpired($input['token']);
    }

    protected function isTokenExpired(string $token) : bool {
        $merchantRequestService = new MerchantRequestService();
        return $merchantRequestService->isValidOneTimeToken($token);
    }

    protected function validateInputToken(array $input) {
        $validator = new Validator();
        $validator->validateInput('token', $input);
    }
}

<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use Illuminate\Http\Response;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;

class Service extends Base\Service
{
    public function postInheritanceParent($merchantId, $input)
    {
        (new Validator())->validateInput('post_inheritance_parent', $input);

        $parentMerchantId = $input['id'];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->checkExistingParent($merchant, $parentMerchantId);

        $partner = $this->checkPartnerConstraintAndReturnPartner($merchantId, $parentMerchantId);

        $inheritanceMap = (new Core)->create($merchant, $partner);
        
        return $inheritanceMap;
    }

    public function postInheritanceParentBulk($input)
    {
        $validationData = ["input" => $input];

        (new Validator())->validateInput('post_inheritance_parent_bulk', $validationData);

        $response = new PublicCollection();

        foreach ($input as $row)
        {
            $merchantId = $row['merchant_id'];

            $parentMerchantId = $row['parent_merchant_id'];

            $idempotencyKey = $row[Entity::IDEMPOTENCY_KEY];

            try
            {
                $parentData = ["id" => $parentMerchantId];

                $this->postInheritanceParent($merchantId, $parentData);

                $data = [
                    Entity::MERCHANT_ID        => $merchantId,
                    Entity::PARENT_MERCHANT_ID => $parentMerchantId,
                    Entity::IDEMPOTENCY_KEY    => $idempotencyKey,
                    Entity::SUCCESS            => true,
                ];
    
                $response->push($data);
            }
            catch (Exception\BaseException $exception)
            {
                $exceptionData = $this->getExceptionDataArray(
                    $idempotencyKey,
                    $exception,
                    $exception->getError()->getHttpStatusCode());

                $response->push($exceptionData);
            }
            catch(\Throwable $throwable)
            {
                $exceptionData = $this->getExceptionDataArray(
                    $idempotencyKey,
                    $throwable,
                    RESPONSE::HTTP_INTERNAL_SERVER_ERROR);

                $response->push($exceptionData);
            }
        }

        return $response->toArrayWithItems();
    }

    public function getInheritanceParent($merchantId)
    {
        $inheritanceMap = $this->repo->merchant_inheritance_map->findInheritanceMapByMerchantIdOrFailPublic($merchantId);

        return $inheritanceMap;
    }

    public function deleteInheritanceParent($merchantId)
    {
        $inheritanceMap = $this->repo->merchant_inheritance_map->findInheritanceMapByMerchantIdOrFailPublic($merchantId);

        $this->repo->transactionOnLiveAndTest(function() use ($inheritanceMap)
        {
            $this->repo->merchant_inheritance_map->deleteOrFail($inheritanceMap);
        });

        return $inheritanceMap->toArrayDeleted();
    }

    protected function getExceptionDataArray(string $idempotencyKey, \Throwable $throwable, int $statusCode): array
    {
        $exceptionData = [
            Entity::IDEMPOTENCY_KEY => $idempotencyKey,
            'error'                 => [
                Error::DESCRIPTION       => $throwable->getMessage(),
                Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
            ],
            Error::HTTP_STATUS_CODE => $statusCode,
            Entity::SUCCESS         => false,
        ];

        return $exceptionData;
    }

    protected function checkExistingParent(Merchant\Entity $merchant, $parentMerchantId)
    {
        $merchantInheritanceMap = $merchant->merchantInheritanceMap;

        if(isset($merchantInheritanceMap) === true)
        {
            $existingParentMerchantId = $merchantInheritanceMap->parentMerchant->getId();

            $this->trace->info(
                TraceCode::SET_MERCHANT_INHERITANCE_PARENT_FAILED,
                [
                    'merchant_id'                   =>  $merchant->getId(),
                    'parent_merchant_id'            =>  $parentMerchantId,
                    'existingParentMerchantId'      =>  $existingParentMerchantId,
                    'message'                       =>  'Merchant already has parent set'
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INHERITANCE_PARENT_ALREADY_SET_FOR_SUBMERCHANT);
        }
    }

    protected function checkPartnerConstraintAndReturnPartner(string $merchantId, string $parentMerchantId)
    {
        $partners = (new Merchant\Core())->fetchAffiliatedPartners($merchantId);

        //submerchant can belong to only one aggregator or fully managed at a time
        $partner = $partners->filter(function(Merchant\Entity $partner)
        {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if (($partner === null) or
            ($partner->getId() !== $parentMerchantId))
        {
            $this->trace->info(
                TraceCode::SET_MERCHANT_INHERITANCE_PARENT_FAILED,
                [
                    'merchant_id'                   =>  $merchantId,
                    'parent_merchant_id'            =>  $parentMerchantId,
                    'message'                       =>  'Parent not aggregator or fully-managed partner'
                ]
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INHERITANCE_PARENT_SHOULD_BE_PARTNER_PARENT_OF_SUBMERCHANT);
        }

        return $partner;
    }
}

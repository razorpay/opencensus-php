<?php

namespace RZP\Services\FavService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\FundAccount\Entity as FaEntity;
use RZP\Models\FundAccount\Validation\Entity as FavEntity;

class Create extends Base
{
    const CREATE_FAV_SERVICE_URI = '/internal/validations';
    const FAV_SERVICE_CREATE     = 'fav_service_create';

    public function createFavViaMicroservice(array $input,
                                             FaEntity $fundAccount,
                                             string $merchantId,
                                             bool $isCompositeFavRequest,
                                             string $validationMethod): array
    {
        $requestInput = $this->createRequestInputForFavMicroService($input, $fundAccount, $merchantId, $isCompositeFavRequest, $validationMethod);

        $this->trace->info(TraceCode::FAV_CREATE_VIA_MICROSERVICE_REQUEST, [
                'request'       => $requestInput,
                'merchant_id'   => $merchantId
            ]);

        $headers = $this->getHeadersWithJwt();

        $response = $this->makeRequestAndGetContent(
            $requestInput,
            self::CREATE_FAV_SERVICE_URI,
            Requests::POST,
            $headers
        );

        $this->trace->info(TraceCode::FAV_CREATE_VIA_MICROSERVICE_RESPONSE, [
            'response'      => $response,
            'merchant_id'   => $merchantId
        ]);

        return $response;
    }


    protected function createRequestInputForFavMicroService(array $input, FaEntity $fundAccount, string $merchantId, bool $isCompositeFavRequest, string $validationMethod): array
    {
        $requestInput = [
            FavEntity::MERCHANT_ID              => $merchantId,
            FavEntity::BALANCE_ID               => $input[FavEntity::BALANCE_ID],
            FavEntity::FUND_ACCOUNT             => $this->fixFundAccountNotes($fundAccount->toArrayPublic()),
            FavEntity::VALIDATION_TYPE          => $input[FavEntity::VALIDATION_TYPE],
            FavEntity::AMOUNT                   => $input[FavEntity::AMOUNT],
            FavEntity::REFERENCE_ID             => $input[FavEntity::REFERENCE_ID],
            FavEntity::NOTES                    => $this->fixNotesField($input[FavEntity::NOTES] ?? []),
            FavEntity::FAV_TYPE                 => $isCompositeFavRequest ? "composite" : "non_composite",
            FavEntity::VALIDATION_METHOD        => $validationMethod,
            FavEntity::SOURCE_ACCOUNT_NUMBER    => $input[FavEntity::SOURCE_ACCOUNT_NUMBER],
            FavEntity::CURRENCY                 => $input[FavEntity::CURRENCY],
            'pricing_info'               => [
                FavEntity::FEES => $input[FavEntity::FEES] ?? 0,
                FavEntity::TAX => $input[FavEntity::TAX] ?? 0,
                FavEntity::PRICING_RULE_ID => $input[FavEntity::PRICING_RULE_ID] ?? "",
            ],
        ];

        if ($fundAccount->contact != null)
        {
            $requestInput[FavEntity::FUND_ACCOUNT][FavEntity::CONTACT] = $fundAccount->contact->toArrayPublic();
        }

        return $requestInput;
    }

    /**
     * Fix the notes field in fund account data to ensure empty notes are objects, not arrays
     * This prevents protobuf syntax errors when sending to FAV service
     */
    protected function fixFundAccountNotes(array $fundAccountData): array
    {
        // Fix bank_account notes if it exists and is empty array
        if (isset($fundAccountData['bank_account']['notes']) && 
            is_array($fundAccountData['bank_account']['notes']) && 
            empty($fundAccountData['bank_account']['notes'])) {
            $fundAccountData['bank_account']['notes'] = (object) [];
        }

        // Fix contact notes if it exists and is empty array
        if (isset($fundAccountData['contact']['notes']) && 
            is_array($fundAccountData['contact']['notes']) && 
            empty($fundAccountData['contact']['notes'])) {
            $fundAccountData['contact']['notes'] = (object) [];
        }

        return $fundAccountData;
    }

    /**
     * Fix the main notes field to ensure it's an object when empty, not an array
     * This prevents protobuf syntax errors when sending to FAV service
     */
    protected function fixNotesField($notes): object|array
    {
        if (is_array($notes) && empty($notes)) {
            return (object) [];
        }
        
        return $notes;
    }

}

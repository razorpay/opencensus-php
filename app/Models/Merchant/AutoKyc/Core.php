<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\AutoKyc\Verifiers\POIVerifier;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\AutoKyc\KycService\ProcessorFactoryImpl as KycProcessorFactory;

class Core extends Base\Core
{
    /**
     * @param KycEntity $entity
     *
     * @throws LogicException
     */
    public function registerKyc(KycEntity $entity)
    {
        if ($entity->getKycId() !== null)
        {
            return;
        }

        $input = [
            DEConstants::ENTITY_ID => $entity->getEntityId(),
        ];

        $response = $this->process($input, DEConstants::REGISTER);

        //
        // we have to register only when talking to kyc service .
        //
        if (empty($response) === true)
        {
            return;
        }

        $entity->setKycId($response[DEConstants::KYC_ID]);
    }

    /**
     * @param KycEntity $entity
     *
     * @param array     $input
     *
     * @return string
     * @throws LogicException
     */
    public function verifyPOI(KycEntity $entity, array $input): string
    {
        $this->registerKyc($entity);

        $poiInput = [
            DEConstants::PAN_NUMBER => $input[DEConstants::PAN_NUMBER],
            DEConstants::ENTITY_ID  => $entity->getEntityId(),
            DEConstants::KYC_ID     => $entity->getKycId(),
        ];

        $response = $this->process($poiInput, DEConstants::POI);

        $poiVerifier = new POIVerifier($input[DEConstants::PROMOTER_PAN_NAME], $response);

        (new Events())->sendServiceVerifierEvents($response);

        return $poiVerifier->verify();
    }

    /**
     * Returns kyc details from kyc service
     * @param array $input
     *
     * @return mixed
     */
    public function getKycDetailsFromKycService(array $input)
    {
        $processorFactory = new KycProcessorFactory();

        $processor = $processorFactory::getKYCDetailProcessor($input);

        $processorResponse = $processor->process();

        $data = $processorResponse->getResponseData();

        return $data;
    }

    public function isDocumentAlreadyPresentInKycService(array $input, string $documentType)
    {
        $kycDetails = $this->getKycDetailsFromKycService($input);

        $documentsList = $kycDetails[DEConstants::DOCUMENTS] ?? [];

        return in_array($documentType, $documentsList, true);
    }

    /**
     * @param array  $input
     * @param string $processorType
     *
     * @return array
     * @throws LogicException
     */
    private function process(array $input, string $processorType)
    {
        $kycVerifierFactory = ServiceFactory::getVerifierServiceFactory($input[DEConstants::ENTITY_ID],
                                                                        $this->mode);

        $processor = self::getProcessor($input, $processorType, $kycVerifierFactory);

        if ($processor === null)
        {
            return [];
        }

        $processorResponse = $processor->process();

        $data = $processorResponse->getResponseData();

        return $data;

    }

    /**
     * @param array            $input
     * @param string           $processorType
     * @param ProcessorFactory $kycVerifierFactory
     *
     * @return null|Processor
     * @throws LogicException
     */
    private function getProcessor(array $input,
                                  string $processorType,
                                  ProcessorFactory $kycVerifierFactory): ?Processor
    {

        switch ($processorType)
        {
            case  DEConstants::POI :

                return $kycVerifierFactory::getPOIProcessor($input);

            case  DEConstants::POA :

                return $kycVerifierFactory::getPOAProcessor($input);

            case DEConstants::REGISTER :

                return $kycVerifierFactory::getRegisterProcessor($input);

            default :
                throw new LogicException(ErrorCode::UNHANDLED_KYC_PROCESSOR_TYPE, null, [
                    DEConstants::PROCESSOR_TYPE => $processorType
                ]);
        }
    }
}

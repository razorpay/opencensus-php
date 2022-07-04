<?php

namespace RZP\Models\PaymentLink\CustomDomain;

interface IDomainClient extends ICDSClientAPI {
    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function createDomain(array $data): array;

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function listDomain(array $data): array;

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function deleteDomain(array $data): array;

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function isSubDomain(array $data): array;
}

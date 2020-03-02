<?php

namespace RZP\Models\Merchant\Document\FileHandler;

interface FileHandlerInterface
{
    public function uploadFile(array $input): array;

    public function getSource(): string;

    public function getSignedUrl(string $fileStoreId, string $merchantId): string;
}

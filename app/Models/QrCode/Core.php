<?php

namespace RZP\Models\QrCode;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use  RZP\Models\BharatQr\Tags;
use RZP\Tests\Functional\Fixtures\Entity\QrCode;

class Core extends Base\Core
{
    public function fetchQrCodePath(Entity $qrCode, Merchant\Entity $merchant)
    {
        $qrCodeImage = $qrCode->qrCodeFile();

        return (new FileStore\Accessor)
                    ->id($qrCodeImage->getId())
                    ->merchantId($merchant->getId())
                    ->getFile();
    }

    public function edit(Entity $qrCode, array $input)
    {
        $qrCode->edit($input);

        $this->repo->saveOrFail($qrCode);

        return $qrCode;
    }

    public function tokenizeExistingQrCodeMpans(Entity $qrCode)
    {
        $qrStringTokenized = Entity::getQrStringWithTokenizedMpans($qrCode->getOriginalQrString());

        $editInput = [
            Entity::QR_STRING       => $qrStringTokenized,
            Entity::MPANS_TOKENIZED => true,
        ];

        $this->edit($qrCode, $editInput);
    }
}

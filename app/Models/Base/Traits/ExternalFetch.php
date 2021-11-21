<?php

namespace RZP\Models\Base\Traits;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;


trait ExternalFetch
{
    protected $entityName;

    private function fetchExternalEntity($id, $merchantId, $input = [])
    {
        $class = Entity::getexternalRepoSingleton($this->entity);

        try
        {
            $entity = $class->fetch($this->entity, $id, $merchantId, $input);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

                return $entity;
            }

        }
        catch (\Throwable $e)
        {
            App::getFacadeRoot()['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_FETCH_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);
        }

        $data = [
            'model' => $this->entityName,
            'attributes' => $id,
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }
}

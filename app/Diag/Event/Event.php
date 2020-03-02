<?php

namespace RZP\Diag\Event;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Models\Base;

abstract class Event
{
    const EVENT_TYPE = 'default';
    const EVENT_VERSION = 'v1';

    protected $entity = null;

    protected $customProperties = [];

    protected $metaDetails = [];

    protected $exception = null;

    protected $properties = [];

    protected $app = null;

    public function __construct(Base\PublicEntity $entity = null,
                                \Throwable $ex = null,
                                array $customProperties = [],
                                array $metaDetails =[]
    )
    {
        $this->app = App::getFacadeRoot();

        $this->entity = $entity;

        $this->customProperties = $customProperties;

        $this->metaDetails = $metaDetails;

        $this->exception = $ex;
    }

    public function getProperties()
    {
        $this->addEventDetails();

        $this->addErrorDetails();

        $this->removeSenstiveFields();

        $this->properties['properties'] = $this->customProperties;

        return $this->properties;
    }

    public function getMetaDetails()
    {
        return $this->getEventMetaDetails();
    }

    protected function addEventDetails()
    {
        $properties = [];

        if ($this->entity !== null)
        {
            $properties = $this->getEventProperties();
        }

        $this->properties += $properties;
    }

    protected function getEventProperties()
    {
        return [];
    }

    // add error details to the diag properties based on excpetion
    protected function addErrorDetails()
    {
        $properties['error_code'] = ErrorCode::SUCCESS;

        if ($this->exception !== null)
        {
            if ($this->exception instanceof BaseException)
            {
                $properties['error_code'] = $this->exception->getCode();
            }
            else
            {
                $properties['error_code'] = ErrorCode::SERVER_ERROR;
            }
        }

        $this->properties += $properties;
    }

    protected function removeSenstiveFields()
    {
        return ;
    }
}

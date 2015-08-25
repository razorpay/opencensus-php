<?php

namespace RZP;

class Admin extends Entity
{
    public function fetchEntityById($entity, $id)
    {
        $relativeUrl = $this->getEntityUrl().$entity.'/'.$id;

        return $this->request('GET', $relativeUrl);
    }

    public function fetchMultipleEntities($entity, $options = array())
    {
        $relativeUrl = $this->getEntityUrl().$entity;

        return $this->request('GET', $relativeUrl, $options);
    }

    public function sendTestNewsletter($params)
    {
        $relativeUrl = $this->getEntityUrl(). 'newsletter/test';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function sendNewsletter($params)
    {
        $relativeUrl = $this->getEntityUrl(). 'newsletter/mail';

        return $this->request('POST', $relativeUrl, $params);
    }

    protected function getEntityUrl()
    {
        $fullClassName = get_class($this);
        $pos = strrpos($fullClassName, '\\');
        $className = substr($fullClassName, $pos + 1);
        $className = lcfirst($className);
        return $className.'/';
    }
}

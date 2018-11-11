<?php

namespace Rzp\Models\P2p\Device;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const ID           = 'id';

    const IMEI         = 'imei';

    const OS           = 'os';

    const OS_VERSION   = 'os_version';

    const APP_NAME     = 'app_name';

    const HANDLE       = 'handle';

    const CL_TOKEN     = 'cl.token';

    const CL_PAYLOAD   = 'cl.payload';

    const CREATED_AT   = 'created_at';


    /**************** GETTER *******************/

    /**
     * @return id
     */
    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    /**
     * @return imei
     */
    public function getImei()
    {
        return $this->getAttribute(self::IMEI);
    }

    /**
     * @return os
     */
    public function getOs()
    {
        return $this->getAttribute(self::OS);
    }

    /**
     * @return os_version
     */
    public function getOsVersion()
    {
        return $this->getAttribute(self::OS_VERSION);
    }

    /**
     * @return app_name
     */
    public function getAppName()
    {
        return $this->getAttribute(self::APP_NAME);
    }

    /**
     * @return handle
     */
    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    /**
     * @return cl.token
     */
    public function getClToken()
    {
        return $this->getAttribute(self::CL_TOKEN);
    }

    /**
     * @return cl.payload
     */
    public function getClPayload()
    {
        return $this->getAttribute(self::CL_PAYLOAD);
    }

    /**
     * @return created_at
     */
    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    /**************** SETTER *******************/

    /**
     * @return $this
     */
    public function setId($id)
    {
        return $this->setAttribute(self::ID, $id);
    }

    /**
     * @return $this
     */
    public function setImei($imei)
    {
        return $this->setAttribute(self::IMEI, $imei);
    }

    /**
     * @return $this
     */
    public function setOs($os)
    {
        return $this->setAttribute(self::OS, $os);
    }

    /**
     * @return $this
     */
    public function setOsVersion($osVersion)
    {
        return $this->setAttribute(self::OS_VERSION, $osVersion);
    }

    /**
     * @return $this
     */
    public function setAppName($appName)
    {
        return $this->setAttribute(self::APP_NAME, $appName);
    }

    /**
     * @return $this
     */
    public function setHandle($handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    /**
     * @return $this
     */
    public function setClToken($clToken)
    {
        return $this->setAttribute(self::CL_TOKEN, $clToken);
    }

    /**
     * @return $this
     */
    public function setClPayload($clPayload)
    {
        return $this->setAttribute(self::CL_PAYLOAD, $clPayload);
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }
}

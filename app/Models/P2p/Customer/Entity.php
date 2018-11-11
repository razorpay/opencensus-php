<?php

namespace Rzp\Models\P2p\Customer;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const ID           = 'id';

    const CONTACT      = 'contact';

    const EMAIL        = 'email';

    const ACTIVE       = 'active';

    const NOTES        = 'notes';

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
     * @return contact
     */
    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    /**
     * @return email
     */
    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    /**
     * @return active
     */
    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    /**
     * @return notes
     */
    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
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
    public function setContact($contact)
    {
        return $this->setAttribute(self::CONTACT, $contact);
    }

    /**
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setAttribute(self::EMAIL, $email);
    }

    /**
     * @return $this
     */
    public function setActive($active)
    {
        return $this->setAttribute(self::ACTIVE, $active);
    }

    /**
     * @return $this
     */
    public function setNotes($notes)
    {
        return $this->setAttribute(self::NOTES, $notes);
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }
}

<?php

namespace App\User;

use Exception;

/**
 * This class is currently limited in its usage
 * to just the User\Service class. Only that class
 * has the right to raise this exception.
 */
class RecoverableException extends Exception
{
}

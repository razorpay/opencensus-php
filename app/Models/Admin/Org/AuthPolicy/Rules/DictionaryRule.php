<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

use StupidPass;

class DictionaryRule extends Base
{
    // Validate password against a list of words from a dictionary
    public function validate(string $password)
    {
        $sp = new StupidPass();

        // Additional options
        // Keep enabled on 'common' that will verify password against a dictionary
        $options = array(
          'disable' => array('length'),
          'disable' => array('upper'),
          'disable' => array('lower'),
          'disable' => array('numeric'),
          'disable' => array('special'),
          'disable' => array('environ'),
        );

        if ($sp->validate($password) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password invalid!');
        }
    }
}
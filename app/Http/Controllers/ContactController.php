<?php

namespace RZP\Http\Controllers;

use RZP\Models\Contact;

/**
 * Class ContactController
 *
 * @package RZP\Http\Controllers
 */
class ContactController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Contact\Service::class;
}

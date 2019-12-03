<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    const LONG_URL_FORMAT = '%s/payout-links/%s/view';

    protected $elfin;

    public function __construct()
    {
        parent::__construct();

        $this->elfin = $this->app['elfin'];
    }

    public function create(array $input): Entity
    {
        array_pull($input, 'XDEBUG_SESSION_START');

        $this->trace->info(
            TraceCode::PAYOUT_LINK_CREATE_REQUEST,
            $input);

        $validator = (new Entity())->getValidator();

        $validator->validateInput(Validator::COMPOSITE_CREATE, $input);

        $this->processContact($input);

        $payoutLink = (new Entity)->build($input);

        // Doing this because we need the Id for generating short URL
        $payoutLink->generateId();

        $this->generateAndSetShortUrl($payoutLink);

        $payoutLink->merchant()->associate($this->merchant);

        $payoutLink->saveOrFail();

        return $payoutLink;
    }

    protected function generateAndSetShortUrl(Entity &$payoutLink)
    {
        $targetUrl = sprintf(self::LONG_URL_FORMAT,
                             $this->config['url.api.production'],
                             $payoutLink->getPublicId());

        $params = [
            'metadata'       => [
                'mode'   => $this->mode,
                'entity' => $payoutLink->getEntity(),
                'id'     => $payoutLink->getPublicId(),
            ]
        ];

        try
        {
            $shortUrl = $this->elfin->shorten($targetUrl, $params, false);

            $payoutLink->setShortUrl($shortUrl);
        }
        catch (BaseException $e)
        {
            $this->trace->error(
                TraceCode::PAYOUT_LINK_SHORT_URL_GENERATION_FAILED,
                [
                    'message'        => $e->getMessage(),
                    'payout_link_id' => $payoutLink->getId()
                ]
            );

            throw $e;
        }
    }

    /**
     * Either creates a new contact or associates the supplied contact_id
     * @param array $input
     * @throws BadRequestException
     */
    protected function processContact(array &$input)
    {
        $contact = array_pull($input, 'contact');

        $contactId = array_pull($contact, 'contact_id');

        if ($contactId === null)
        {
            $this->trace->info(TraceCode::PAYOUT_LINK_PROCESS_CONTACT_REQUEST,
                               $contact);

            $contactClient = new Clients\Contact();

            try
            {
                $contactEntity = $contactClient->createContact($contact,  $this->merchant);

                $input['contact_id'] = $contactEntity->getId();
            }
            catch(\Exception $e)
            {
                throw new BadRequestException(
                    $e->getMessage(),
                    ErrorCode::BAD_REQUEST_CONTACT_ADD_FAILED,
                    $input,
                    $e
                );
            }
        }
    }
}

<?php

namespace RZP\Models\Batch\Processor;

use Mail;
use Razorpay\OAuth;

use RZP\Models\User;
use RZP\Models\Partner;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Batch\Type;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Mail\User as UserMail;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Batch\Helpers\SubMerchant as Helper;
use RZP\Exception\BadRequestValidationFailureException;

class SubMerchant extends Base
{
    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * @var BankAccount\Core
     */
    protected $bankAccountCore;

    /**
     * @var MerchantDetail\Core
     */
    protected $merchantDetailCore;

    /**
     * @var OAuth\Application\Entity
     */
    protected $partnerApp;

    /**
     * @var array
     */
    protected $orgData;

    const SUB_MERCHANT_USER_DEFAULT_PASSWORD = 'password123';

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantCore       = new Merchant\Core;
        $this->bankAccountCore    = new BankAccount\Core;
        $this->merchantDetailCore = new MerchantDetail\Core;
    }

    protected function processEntry(array & $entry)
    {
        $this->repo->transactionOnLiveAndTest(function() use (& $entry)
        {
            $subMerchant = $this->createSubMerchantForEntry($entry);

            $this->processPartnerAppIfApplicable($subMerchant, $entry);

            $this->unsetExtraOutputKeys($entry);
        });
    }

    protected function performPreProcessingActions()
    {
        $this->setPartnerAppIfApplicable();

        return parent::performPreProcessingActions();
    }

    protected function setPartnerAppIfApplicable()
    {
        $appId = $this->params[Entity::APPLICATION_ID] ?? null;

        if (empty($appId) === true)
        {
            return;
        }

        /** @var OAuth\Application\Entity $app */
        $app = (new OAuth\Application\Repository)->findOrFailPublic($appId);

        $appType = $app->getType();

        if ($appType !== OAuth\Application\Type::PARTNER)
        {
            throw new BadRequestValidationFailureException(
                'Application is not of type partner',
                Entity::APPLICATION_ID,
                ['type' => $appType]);
        }

        $this->partnerApp = $app;

        // Org data is required for the sub-merchant user confirmation email
        /** @var Org\Entity $razorpayOrg */
        $razorpayOrg   = $this->repo->org->getRazorpayOrg();
        $this->orgData = array_merge(
            $razorpayOrg->toArrayPublic(),
            [
                Org\Hostname\Entity::HOSTNAME => $razorpayOrg->getPrimaryHostName()
            ]);
    }

    /**
     * @param  array $entry
     *
     * @return Merchant\Entity
     */
    protected function createSubMerchantForEntry(array & $entry) : Merchant\Entity
    {
        $input = Helper::getSubMerchantInput($entry);

        // Create Sub-merchant account
        $merchantService = new Merchant\Service;
        $subMerchant     = $this->merchantCore->createSubMerchant($input, $this->merchant, false);

        $merchantService->addLinkedAccountReferral($this->merchant, $subMerchant);

        $merchantService->attachSubMerchantOwner($this->merchant->primaryOwner()->getId(), $subMerchant);

        $this->repo->saveOrFail($subMerchant);

        $status = Status::SUCCESS;

        // Fill in merchant details (activation form)
        $detailInput = Helper::getSubMerchantDetailInput($entry);
        $this->merchantDetailCore->saveMerchantDetails($detailInput, $subMerchant);

        // Add files and submit for non-partner flow
        if ($this->partnerApp === null)
        {
            // Save files
            $this->merchantDetailCore->saveDummyActivationFiles($subMerchant);

            // Submit activation form
            $submitData = [MerchantDetail\Entity::SUBMIT => '1'];
            $response   = $this->merchantDetailCore->saveMerchantDetails($submitData, $subMerchant);

            $status = ($response[MerchantDetail\Entity::SUBMITTED] === true) ?
                Status::SUCCESS : Status::FAILURE;
        }

        $entry[Header::MERCHANT_ID] = $subMerchant->getId();
        $entry[Header::STATUS]      = $status;

        return $subMerchant;
    }

    protected function processPartnerAppIfApplicable(Merchant\Entity $subMerchant, array & $entry)
    {
        if ($this->partnerApp === null)
        {
            return;
        }

        $token = (new Partner\Core)->connectMerchant($this->partnerApp, $this->merchant, $subMerchant);

        $status = ((empty($token)) === true) ? Status::FAILURE : Status::SUCCESS;

        $this->createSubMerchantUserAndEmail($subMerchant);

        $entry[Header::STATUS]      = $status;
        $entry[Header::PARTNER_TOKEN] = $token;
    }

    protected function createSubMerchantUserAndEmail(Merchant\Entity $subMerchant)
    {
        $userInput = [
            User\Entity::NAME                  => $subMerchant->getName(),
            User\Entity::EMAIL                 => $subMerchant->getEmail(),
            User\Entity::PASSWORD              => self::SUB_MERCHANT_USER_DEFAULT_PASSWORD,
            User\Entity::PASSWORD_CONFIRMATION => self::SUB_MERCHANT_USER_DEFAULT_PASSWORD,
            User\Entity::CAPTCHA_DISABLE       => User\Validator::DISABLE_CAPTCHA_SECRET,
        ];

        // Create a new user
        $subMerchantUser = (new User\Core)->create($userInput);

        // Attach the user as an owner on the sub_merchant account
        (new Merchant\Service)->attachSubMerchantOwner($subMerchantUser->getPublicId(), $subMerchant);

        // Sent the user an email for confirmation
        $this->sendSubmerchantUserEmail($subMerchantUser);
    }

    protected function sendSubmerchantUserEmail(User\Entity $user)
    {
        $confirmationMail = new UserMail\AccountVerification($user, $this->orgData);

        Mail::queue($confirmationMail);
    }

    protected function unsetExtraOutputKeys(array & $entry)
    {
        $entry = array_only($entry, Header::HEADER_MAP[Type::SUB_MERCHANT][Header::OUTPUT]);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}

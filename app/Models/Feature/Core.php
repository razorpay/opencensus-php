<?php

namespace RZP\Models\Feature;

use Config;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Core extends Base\Core
{
    /**
     * Create feature
     *
     * @param array $input
     * @param bool  $shouldSync Should the entity be save on both test and live
     *
     * @return Entity
     */
    public function create(array $input, bool $shouldSync = false): Entity
    {
        $feature = (new Entity)->build($input);

        $feature->generateId();

        $existingFeatures = $this->repo->feature->findByEntityId($feature->getEntityId());

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_EDIT_REQUEST,
            [
                PublicEntity::MERCHANT_ID => $feature->getEntityId(),
                Entity::OLD_FEATURES      => $assignedFeatureNames,
                Entity::NEW_FEATURE       => $feature->getName(),
                Entity::SHOULD_SYNC       => $shouldSync
            ]);

        $this->repo->feature->saveAndSyncIfApplicableOrFail(
            $feature,
            $assignedFeatureNames,
            $shouldSync);

        $this->notifyFeatureUpdateOnSlack($feature);

        return $feature;
    }

    /**
     * Delete feature
     *
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    public function delete(Entity $feature, bool $shouldSync = false)
    {
        $this->trace->info(
            TraceCode::FEATURE_DELETE_REQUEST,
            [
                Entity::FEATURE     => $feature->toArrayPublic(),
                Entity::SHOULD_SYNC => $shouldSync
            ]);

        // Workflow
        list($original, $dirty) = [
            ['feature' => $feature->getName()],
            ['feature' => null],
        ];

        $this->app['workflow']
             ->setEntity($feature->getEntity())
             ->handle($original, $dirty);

        $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);

        $this->notifyFeatureUpdateOnSlack($feature, true);
    }

    /**
     * Send a slack notification on feature create/delete
     *
     * @param Entity $feature
     * @param bool   $featureDeleted
     */
    protected function notifyFeatureUpdateOnSlack(Entity $feature, bool $featureDeleted = false)
    {
        $message = $feature->getDashboardEntityLinkForSlack($feature->getName());

        if ($featureDeleted === true)
        {
            $message .= ' deleted from ';
        }
        else
        {
            $message .= ' added to ';
        }

        $user = $this->getInternalUsernameOrEmail();

        $message .= $feature->getEntityId() . ' by ' . $user;

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }

    /**
     * Notifies slack about the new onboarding responses submitted
     *
     * @param string $featureName
     */
    public function notifyOnboardingResponseCreationOnSlack(string $featureName)
    {
        $newLineChar = "\n";

        $merchant = $this->merchant;

        $message = $merchant->getDashboardEntityLinkForSlack();

        $message .= ' has submitted responses for activating ' . $featureName . ' ' .$newLineChar;

        if (((int)$merchant->isActivated()) === 1)
        {
            $activatedAtEpoch = $merchant->getAttribute(MerchantEntity::ACTIVATED_AT);

            $activatedAtInIST = Carbon::createFromTimestamp($activatedAtEpoch, Timezone::IST);
            $activationDate   = $activatedAtInIST->format('d/m/y');
            $activationTime   = $activatedAtInIST->format('h:i:s');

            $message .= 'The merchant\'s account has been activated ' .
                ' on ' . $activationDate .
                ' at ' . $activationTime .
                ' ' . $newLineChar;
        }
        else
        {
            $message .= 'The merchant\'s account has not been activated. ' .
                $newLineChar . $merchant->isActivated();
        }

        if (((int) $merchant->isLive()) === 1)
        {
            $message .= 'The account is live ' . $newLineChar;
        }
        else
        {
            $message .= 'The account is not live ' . $newLineChar;
        }

        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails !== null)
        {
            $submittedAtEpoch = $merchantDetails->getSubmittedAt();

            if ($submittedAtEpoch !== null)
            {
                $submittedAtEpoch = $merchant->getAttribute(MerchantEntity::ACTIVATED_AT);

                $submittedAtInIST = Carbon::createFromTimestamp($submittedAtEpoch, Timezone::IST);
                $submissionDate   = $submittedAtInIST->format('d/m/y');
                $submissionTime   = $submittedAtInIST->format('h:i:s');

                $message .= 'The details were submitted' .
                    ' on ' . $submissionDate .
                    ' at ' . $submissionTime;
            }
        }

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.activations_prod_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }
}

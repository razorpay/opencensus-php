<?php

namespace RZP\Models\Feature;

use Mail;
use Config;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use RZP\Mail\Merchant\FeatureEnabled;
use RZP\Models\Merchant\SlackActions;
use RZP\Models\Merchant\Notify as NotifyTrait;

class Core extends Base\Core
{
    use NotifyTrait;

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

        $merchantId = $input['entity_id'];

        $this->notifyMerchantIfApplicable($merchantId, $feature, $shouldSync);

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
     * @param string $productName
     */
    public function notifyOnboardingResponseCreationOnSlack(string $productName)
    {
        $merchant = $this->merchant;

        $isLive = ($merchant->isLive() === true) ? "true" : "false";

        $isActivated = ($merchant->isActivated() === true) ? "true" : "false";

        $merchantDetails = $merchant->merchantDetail;

        $submitted = (($merchantDetails !== null) and
            ($merchantDetails->isSubmitted() === true)) ? "true"  : "false";

        $data = [
            'id'                         => $merchant->getId(),
            'activated'                  => $isActivated,
            'activation_form_submitted'  => $submitted,
            'live'                       => $isLive,
            'product'                    => $productName
        ];

        $this->logActionToSlack($this->merchant, SlackActions::PRODUCT_ACTIVATION, $data);
    }

    /**
     * Sends an email to the merchant if a
     * notifyFeature is enabled on Live mode
     *
     * @param string $merchantId
     * @param Entity $feature
     * @param bool   $shouldSync
     */
    public function notifyMerchantIfApplicable(
        string $merchantId,
        Entity $feature,
        bool $shouldSync)
    {
        $isLiveMode = $this->isLiveMode();

        if (($feature->isNotifyFeature() === true) and
            (($shouldSync === true) or ($isLiveMode === true)))
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $visibleFeatures = Constants::$visibleFeaturesMap;
            $featureName     = $feature->getName();

            $data['feature']       = $visibleFeatures[$featureName]['display_name'];
            $data['documentation'] = $visibleFeatures[$featureName]['documentation'];
            $data['contact_email'] = $merchant->getEmail();
            $data['contact_name']  = $merchant->getName();

            $featureUpdateEmail = new FeatureEnabled($data);

            Mail::queue($featureUpdateEmail);

            $this->trace->info(
                TraceCode::FEATURE_ENABLED_MERCHANT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $merchantId,
                    Entity::SHOULD_SYNC       => $shouldSync,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                ]);
        }
        else
        {
            $this->trace->info(
                TraceCode::FEATURE_ENABLED_MERCHANT_NOT_NOTIFIED,
                [
                    PublicEntity::MERCHANT_ID => $merchantId,
                    Entity::SHOULD_SYNC       => $shouldSync,
                    Mode::LIVE                => $isLiveMode,
                    Entity::NEW_FEATURE       => $feature,
                ]);
        }
    }

}

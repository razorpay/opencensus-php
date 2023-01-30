<?php

namespace RZP\Models\Merchant\XChannelDefinition;

use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Models\Base;
use RZP\Models\Merchant\Attribute as Attribute;
use RZP\Models\Merchant\Attribute\Entity as AttributeEntity;
use RZP\Models\Merchant\Entity;
use RZP\Trace\TraceCode;
use Throwable;

class Service extends Base\Service
{

    protected $attributeCore;

    protected $attributeService;

    public function __construct($attributeCore = null, $attributeService = null)
    {
        parent::__construct();

        $this->attributeCore = $attributeCore ?? new Attribute\Core();

        $this->attributeService = $attributeService ?? new Attribute\Service();
    }


    /**
     * Get channel and subchannel given UTM params array. Fields used: website, final_utm_source, final_utm_medium,
     * final_utm_campaign
     *
     * @param array $input
     *
     * @return array
     */
    public function getChannelAndSubchannel(array $input): array
    {
        // Default values in case there's no matching channel or sub-channel
        $result = [
            Constants::CHANNEL    => Channels::UNMAPPED,
            Constants::SUBCHANNEL => Channels::UNMAPPED,
        ];

        $refWebsite       = $input['website'] ?? '';
        $finalUtmSource   = $input['final_utm_source'] ?? '';
        $finalUtmMedium   = $input['final_utm_medium'] ?? '';
        $finalUtmCampaign = $input['final_utm_campaign'] ?? '';
        $lcsCategory      = $this->getLastClickSourceCategory($finalUtmSource, $finalUtmMedium);

        foreach (Channels::getChannelSubchannelMapping() as $channel => $subchannelDetails)
        {
            // If URL patterns for the channel are specified, we find channel using that and use other parameters for
            // finding sub-channel
            $channelUrlPatterns = Channels::$channelRefWebsiteMapping[$channel];

            if (!empty($channelUrlPatterns))
            {
                $urlMatch = $this->checkExactOrRegexMatchAgainstPatterns($refWebsite, $channelUrlPatterns);

                // Channel has matched, next we need to check for sub-channels
                if ($urlMatch)
                {
                    $result[Constants::CHANNEL]    = $channel;
                    $result[Constants::SUBCHANNEL] = $this->getSubchannel($channel, $lcsCategory, $finalUtmCampaign, $refWebsite);

                    return $result;
                }
                // If no match found, continue looking.
            }
            else
            {
                $subchannels = Channels::getChannelSubchannelMapping()[$channel];
                if (!empty($subchannels))
                {
                    $subchannel = $this->getSubchannel($channel, $lcsCategory, $finalUtmCampaign, $refWebsite);
                    if (!empty($subchannel) && $subchannel !== Channels::UNMAPPED)
                    {
                        $result[Constants::CHANNEL]    = $channel;
                        $result[Constants::SUBCHANNEL] = $subchannel;

                        return $result;
                    }
                }
                // If current channel doesn't have any sub-channels, continue looking.
            }
        }

        return $result;
    }

    /**
     * @param string $lastClickSource
     * @param string $lastClickMedium
     *
     * @return string
     */
    protected function getLastClickSourceCategory(string $lastClickSource, string $lastClickMedium): string
    {
        $lastClickSource = $lastClickSource ?? '';
        $lastClickMedium = $lastClickMedium ?? '';

        $inputLcSourceMedium = $lastClickSource . ' ' . $lastClickMedium;

        foreach (Constants::LCS_CATEGORY_TO_SOURCE_MEDIUM_MAPPING as $lcsCategory => $lastClickSourceMediums)
        {
            if ($this->checkExactOrRegexMatchAgainstPatterns($inputLcSourceMedium, $lastClickSourceMediums))
            {
                return $lcsCategory;
            }
        }

        return Constants::LCS_CATEGORY_UNKNOWN;
    }

    protected function getChannelPriority(?string $channelName): int
    {
        return Channels::$channelPriorities[$channelName] ?? -1;
    }

    public function getCurrentChannelDetails(Entity $merchant): array
    {
        $originalMode = $this->app['rzp.mode'];
        // Use live DB to fetch channel details
        $this->app['rzp.mode'] = Mode::LIVE;

        $attributes = $this->attributeCore->fetchKeyValues($merchant, Product::BANKING, Attribute\Group::X_SIGNUP,
                                                           [Attribute\Type::CHANNEL, Attribute\Type::SUBCHANNEL]);

        $channel    = '';
        $subchannel = '';

        foreach ($attributes as $attribute)
        {
            if ($attribute[Attribute\Entity::TYPE] === Attribute\Type::CHANNEL)
            {
                $channel = $attribute[Attribute\Entity::VALUE];
            }
            elseif ($attribute[Attribute\Entity::TYPE] === Attribute\Type::SUBCHANNEL)
            {
                $subchannel = $attribute[Attribute\Entity::VALUE];
            }
        }

        // Reset mode back to original value (or live mode as fallback if empty)
        $this->app['rzp.mode'] = $originalMode ?? Mode::LIVE;

        return [
            Constants::CHANNEL    => $channel,
            Constants::SUBCHANNEL => $subchannel,
        ];
    }

    public function addChannelDetailsInPreSignupSFPayload(Entity $merchant, array &$eventPayload)
    {
        $channelDetails = $this->getCurrentChannelDetails($merchant);

        // Lowercase keys used below are later mapped to capitalized versions before sending to SF
        $eventPayload['x_channel']    = empty($channelDetails[Constants::CHANNEL]) ? Channels::UNMAPPED : $channelDetails[Constants::CHANNEL];
        $eventPayload['x_subchannel'] = empty($channelDetails[Constants::SUBCHANNEL]) ? Channels::UNMAPPED : $channelDetails[Constants::SUBCHANNEL];

        $this->trace->info(TraceCode::X_CHANNEL_DEFINITION_UPDATING_SF_PAYLOAD, [
            'channel'    => $eventPayload['x_channel'],
            'subchannel' => $eventPayload['x_subchannel'],
        ]);
    }

    public function addChannelDetailsInSFPayloadIfNotPresent(Entity $merchant, array &$eventPayload)
    {
        // Fetch and add current value of channel and sub-channel in SF payload
        $channelDetails = $this->getCurrentChannelDetails($merchant);

        // If the fields are already set, don't override them to avoid inconsistencies due to replica lag
        if (!empty($channelDetails[Constants::CHANNEL]) && !isset($eventPayload['X_Channel']))
        {
            $eventPayload['X_Channel'] = $channelDetails[Constants::CHANNEL];
        }

        if (!empty($channelDetails[Constants::SUBCHANNEL]) && !isset($eventPayload['X_Subchannel']))
        {
            $eventPayload['X_Subchannel'] = $channelDetails[Constants::SUBCHANNEL];
        }
    }

    /**
     * Store channel and sub-channel details which can be used to attribute where the signup came from and which
     * products the merchant is interested in. Returns existing or newly-assigned channel and sub-channel.
     *
     * @param Entity $merchant
     * @param array  $utmParams
     */
    public function storeChannelDetails(Entity $merchant, array $utmParams)
    {
        $existingChannel = null;
        $signupSource    = null;
        $channelDetails  = [];

        try
        {
            $signupSourceAttribute = $this->attributeCore->fetchKeyValues($merchant, Product::BANKING,
                                                                          Attribute\Group::X_MERCHANT_PREFERENCES,
                                                                          [Attribute\Type::X_SIGNUP_PLATFORM])->first();
            if (!empty($signupSourceAttribute))
            {
                $signupSource = $signupSourceAttribute[AttributeEntity::VALUE];
            }

            $existingChannelAttribute = $this->attributeCore->fetchKeyValues($merchant, Product::BANKING,
                                                                             Attribute\Group::X_SIGNUP,
                                                                             [Attribute\Type::CHANNEL])->first();
            if (!empty($existingChannelAttribute))
            {
                $existingChannel = $existingChannelAttribute[AttributeEntity::VALUE];
            }
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::X_CHANNEL_DEFINITION_MERCHANT_ATTRIBUTES_FETCH_FAILURE);

            return;
        }

        $this->trace->info(TraceCode::X_CHANNEL_DEFINITION_EXISTING_ATTRIBUTE_VALUES, [
            "signup_source"    => $signupSource,
            "existing_channel" => $existingChannel,
        ]);

        // If existing channel is Unmapped, we can override it
        if ($existingChannel === Channels::UNMAPPED)
        {
            $existingChannel = null;
        }

        try
        {
            // Check if channel is not present
            if (empty($existingChannel))
            {
                $channelDetails = $this->getChannelAndSubchannel($utmParams);

                /*
                 * If channel was calculated to be UNMAPPED but merchant signed-up on PG,
                 * set the channel explicitly as PG. This happens when UTM params are missing/mangled
                 */
                if ($channelDetails[Constants::CHANNEL] === Channels::UNMAPPED and
                    $merchant->getSignupSource() === Product::PRIMARY)
                {
                    $channelDetails[Constants::CHANNEL] = Channels::PG;
                }

                // Check if signed up via X mobile app
                if (!empty($signupSource) && $signupSource === Constants::X_MOBILE_APP)
                {
                    $this->updateChannelDetailsIfMobilePriorityIsHigher($existingChannel, $channelDetails);
                }

                $this->upsertChannelDetailsInMerchantAttributes($channelDetails, $utmParams, $merchant);
            }
            else
            {
                // Channel is already present, we only need to update it if user signed up via mobile app and current
                // channel's priority is less preferred

                // Check if signed up via X mobile app
                if (!empty($signupSource) && $signupSource === Constants::X_MOBILE_APP)
                {
                    if ($this->updateChannelDetailsIfMobilePriorityIsHigher($existingChannel, $channelDetails))
                    {
                        $this->upsertChannelDetailsInMerchantAttributes($channelDetails, $utmParams, $merchant);
                    }
                }
            }
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::X_CHANNEL_DEFINITION_FAILED_TO_STORE_CHANNEL_DETAILS);

            return;
        }
    }

    /**
     * Checks if mobile channel's priority is higher than given existing channel. Also updates the channel and
     * sub-channel in $channelDetails if so. Priority is higher if it has a lower value.
     *
     * @param string|null $existingChannel
     * @param array       $channelDetails
     *
     * @return bool
     */
    protected function updateChannelDetailsIfMobilePriorityIsHigher(?string $existingChannel, array &$channelDetails): bool
    {
        $existingChannelPriority = $this->getChannelPriority($existingChannel);
        $xMobileChannelPriority  = $this->getChannelPriority(Channels::MOBILE_APP_SIGNUPS);

        // If priority of mobile app signup channel is preferred over existing channel, update channel details
        if ($existingChannelPriority !== -1 && ($xMobileChannelPriority < $existingChannelPriority))
        {
            $channelDetails[Constants::CHANNEL]    = Channels::MOBILE_APP_SIGNUPS;
            $channelDetails[Constants::SUBCHANNEL] = Channels::MOBILE_APP_SIGNUPS_DIRECT;

            return true;
        }
        // If it's a non-existent channel or Unmapped, we can safely replace channel with Mobile
        elseif ($existingChannelPriority === -1)
        {
            $channelDetails[Constants::CHANNEL]    = Channels::MOBILE_APP_SIGNUPS;
            $channelDetails[Constants::SUBCHANNEL] = Channels::MOBILE_APP_SIGNUPS_DIRECT;

            return true;
        }

        return false;
    }

    protected function getArrayValueOrDefault(array $arr, string $key, $defaultValue)
    {
        if (isset($arr[$key]) && !empty($arr[$key]))
        {
            return $arr[$key];
        }

        return $defaultValue;
    }

    protected function upsertChannelDetailsInMerchantAttributes(array $channelDetails, array $utmParams, Entity $merchant)
    {
        $channel                 = $this->getArrayValueOrDefault($channelDetails, Constants::CHANNEL, Channels::UNMAPPED);
        $subchannel              = $this->getArrayValueOrDefault($channelDetails, Constants::SUBCHANNEL, Channels::UNMAPPED);
        $finalUtmSource          = $this->getArrayValueOrDefault($utmParams, Constants::FINAL_UTM_SOURCE, Constants::UNKNOWN);
        $finalUtmMedium          = $this->getArrayValueOrDefault($utmParams, Constants::FINAL_UTM_MEDIUM, Constants::UNKNOWN);
        $finalUtmCampaign        = $this->getArrayValueOrDefault($utmParams, Constants::FINAL_UTM_CAMPAIGN, Constants::UNKNOWN);
        $refWebsite              = $this->getArrayValueOrDefault($utmParams, Constants::WEBSITE, Constants::UNKNOWN);
        $lastClickSourceCategory = $this->getLastClickSourceCategory($finalUtmSource, $finalUtmMedium);

        $this->trace->info(TraceCode::X_CHANNEL_DEFINITION_SAVING_DETAILS, [
            'channel'                    => $channel,
            'subchannel'                 => $subchannel,
            'final_utm_source'           => $finalUtmSource,
            'final_utm_medium'           => $finalUtmMedium,
            'final_utm_campaign'         => $finalUtmCampaign,
            'ref_website'                => $refWebsite,
            'last_click_source_category' => $lastClickSourceCategory,
        ]);

        $data = [
            [
                Attribute\Entity::TYPE  => Attribute\Type::CHANNEL,
                Attribute\Entity::VALUE => $channel,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::SUBCHANNEL,
                Attribute\Entity::VALUE => $subchannel,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_SOURCE,
                Attribute\Entity::VALUE => $finalUtmSource,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_MEDIUM,
                Attribute\Entity::VALUE => $finalUtmMedium,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_CAMPAIGN,
                Attribute\Entity::VALUE => $finalUtmCampaign,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::REF_WEBSITE,
                Attribute\Entity::VALUE => $refWebsite,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::LAST_CLICK_SOURCE_CATEGORY,
                Attribute\Entity::VALUE => $lastClickSourceCategory,
            ],
        ];

        try
        {
            $this->attributeService->upsert(Attribute\Group::X_SIGNUP, $data, Product::BANKING, $merchant);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::X_CHANNEL_DEFINITION_MERCHANT_ATTRIBUTES_SAVE_FAILURE, $data);
        }
    }

    /**
     * Check if item is an exact or regex match against a list of patterns. If a pattern starts with a / character, it
     * would be treated as regex, else only string equality would be checked. Results of this function are
     * case-insensitive.
     *
     * @param string $item
     * @param array  $patterns
     *
     * @return bool
     */
    protected function checkExactOrRegexMatchAgainstPatterns(string $item, array $patterns): bool
    {
        $item = trim(strtolower($item ?? ''));

        foreach ($patterns as $pattern)
        {
            // Treat pattern as case-insensitive, ignore surrounding whitespaces
            $pattern = trim(strtolower($pattern));

            // Check exact match
            if ($pattern === $item)
            {
                return true;
            }

            // If pattern starts with "/", evaluate it as regex
            if ((substr($pattern, 0, 1) === '/') && (preg_match($pattern, $item) === 1))
            {
                return true;
            }
        }

        return false;
    }

    protected function getSubchannel(string $channel, string $lcsCategory, string $finalUtmCampaign, string $refWebsite)
    {
        $subchannels = Channels::getChannelSubchannelMapping()[$channel];
        if (empty($subchannels))
        {
            return Channels::UNMAPPED; // If the channel doesn't have any sub-channels, we'll consider sub-channel as Unmapped
        }

        foreach ($subchannels as $subchannel => $subchannelDetails)
        {
            $sourceCategories = $subchannelDetails[Constants::LAST_CLICK_SOURCE_CATEGORY];
            $campaigns        = $subchannelDetails[Constants::FINAL_UTM_CAMPAIGN];
            $refWebsites      = $subchannelDetails[Constants::REF_WEBSITE];

            $match = true;

            // If any of the filters are non-empty, we need to consider them to find if the sub-channel is a match
            if (!empty($sourceCategories))
            {
                $match = $this->checkExactOrRegexMatchAgainstPatterns($lcsCategory, $sourceCategories);
            }
            if (!empty($campaigns))
            {
                $match = $match && in_array($finalUtmCampaign, $campaigns);
            }
            if (!empty($refWebsites))
            {
                $match = $match && $this->checkExactOrRegexMatchAgainstPatterns($refWebsite, $refWebsites);
            }

            // If match is true and at least one of the filters was non-empty, then we consider sub-channel as a match.
            if ($match && !(empty($sourceCategories) && empty($campaigns) && empty($refWebsites)))
            {
                return $subchannel;
            }
        }

        return Channels::UNMAPPED; // Return sub-channel as Unmapped if there's no match
    }

    public function storeChannelAndSubchannel(Entity $merchant, string $channel, string $subchannel)
    {
        $existingChannelAttribute = $this->attributeCore->fetchKeyValues($merchant, Product::BANKING,
                                                                         Attribute\Group::X_SIGNUP,
                                                                         [Attribute\Type::CHANNEL])->first();

        $channelDetails = [
            Constants::CHANNEL    => Channels::UNMAPPED,
            Constants::SUBCHANNEL => Channels::UNMAPPED,
        ];

        if (!empty($existingChannelAttribute))
        {
            $existingChannel = $existingChannelAttribute[AttributeEntity::VALUE];
            // If existing channel is empty or Unmapped, override
            if (empty($existingChannel) === true || $existingChannel === Channels::UNMAPPED)
            {
                $channelDetails[Constants::CHANNEL]    = $channel;
                $channelDetails[Constants::SUBCHANNEL] = $subchannel;
                $this->upsertChannelDetailsInMerchantAttributes($channelDetails, [], $merchant);
            }
        }
        else
        {
            // If channel attribute is absent, we can set it now
            $channelDetails[Constants::CHANNEL]    = $channel;
            $channelDetails[Constants::SUBCHANNEL] = $subchannel;
            $this->upsertChannelDetailsInMerchantAttributes($channelDetails, [], $merchant);
        }
    }
}

import React, { useMemo, useState, useEffect } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { isMobileDevice } from '@libs/shared-utils';
import { Box, Link, Divider, Text, EditIcon, LayersIcon } from '@razorpay/blade/components';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { INTEGRATION_GUIDE } from '@FTUX/constants/accordion';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';
import SelectableOptionCard, {
  SelectableOptionCardProps,
} from '@OnboardingExperienceCommons/components/SelectableOptionCard';
import {
  hasAcceptedAnyPaymentChannel,
  hasAddedWebsite,
} from '@OnboardingExperienceCommons/utils/merchant';
import {
  AvailablePlatformTypesEnum,
  getBusinessPlatformType,
} from '@OnboardingExperienceCommons/utils/website';
import customWebsiteIntegrationIcon from '@OnboardingExperienceAssets/CustomWebsiteIntegrationIcon.svg';
import websiteIntegratedIcon from '@OnboardingExperienceAssets/WebsiteIntegratedIcon.svg';
import pluginWebsiteIntegrationIcon from '@OnboardingExperienceAssets/PluginWebsiteIntegrationIcon.svg';
import WebsitePluginModal from '@OnboardingExperienceCommons/components/WebsitePluginModal';
import AppIntegrationGuide from './AppIntegrationGuide';
import { DASHBOARD_TEAMS } from '@libs/shared-types';

const IntegrationGuide = () => {
  const isMobile = isMobileDevice();
  const { merchantData, onboardingData, addMerchantWebsitePlugin } = useMerchantContext();
  const [selectedWebsitePlugin, setSelectedWebsitePlugin] = useState<string | null>(null);
  const [websitePluginModalVisible, setWebsitePluginModalVisible] = useState(false);

  const paymentChannels = merchantData?.merchantById?.business?.paymentAcceptanceChannels;

  // Check if the merchant has added a website, has API key access and is activated
  const hasApiKeyAccess = Boolean(merchantData?.merchantById?.hasApiKeyAccess);

  const websiteUrl =
    merchantData?.merchantById?.business?.paymentAcceptanceChannels?.[
      PAYMENT_CHANNEL_OPTIONS.Websites
    ]?.urls?.[0]?.value;
  const platformType = getBusinessPlatformType(websiteUrl);

  const hasWebsiteIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const hasAndroidIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Android,
  ]);
  const hasIOSIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [PAYMENT_CHANNEL_OPTIONS.IOS]);

  // Check if the merchant intends to use a website for payment acceptance
  // Returns true if platform type is a website or if they've selected website payment channel
  let showWebsiteGuide = websiteUrl && platformType === AvailablePlatformTypesEnum.WEBSITE;

  // Determine whether to display the Android integration guide based on:
  // 1. If platform type is Android, or
  // 2. If they've specifically added an Android website, or
  // 3. If no website guide and intent, show this guide atleast if they have Android intent
  const showAndroidGuide =
    platformType === AvailablePlatformTypesEnum.ANDROID ||
    hasAddedWebsite(paymentChannels, [PAYMENT_CHANNEL_OPTIONS.Android]) ||
    (!showWebsiteGuide && !hasWebsiteIntent && hasAndroidIntent);

  // Determine whether to display the iOS integration guide based on:
  // 1. If platform type is iOS, or
  // 2. If they've specifically added an iOS website, or
  // 3. If no website guide and intent, show this guide atleast if they have IOS intent
  const showIOSGuide =
    platformType === AvailablePlatformTypesEnum.IOS ||
    hasAddedWebsite(paymentChannels, [PAYMENT_CHANNEL_OPTIONS.IOS]) ||
    (!showWebsiteGuide && !hasWebsiteIntent && hasIOSIntent);

  // In case of fallback, show website guide always
  if (!showAndroidGuide && !showIOSGuide) {
    showWebsiteGuide = true;
  }

  const handleAddPlugin = async (newPlugin: string) => {
    // Find if there's a plugin used for this website
    const previouslySelectedPlugin = selectedWebsitePlugin;

    try {
      setSelectedWebsitePlugin(newPlugin);

      // Set the chosen plugin and mutate parallely in case the merchant is activated
      // Else the user can toggle plugin integration guide on FE only
      if (hasApiKeyAccess && !!websiteUrl) {
        await addMerchantWebsitePlugin({ websiteUrl: websiteUrl || '', pluginName: newPlugin });
      }
    } catch (error) {
      // Rollback to previous plugin in case of error
      setSelectedWebsitePlugin(previouslySelectedPlugin);

      errorService.captureError(error, {
        tags: {
          team: DASHBOARD_TEAMS.ONBOARDING_EXPERIENCE,
          module: 'FTUX_INTEGRATION_GUIDE',
        },
        rank: errorService.ErrorRank.P0,
        extra: {
          info: error,
        },
      });

      throw new Error('An error occurred while selecting the plugin! Please try again later');
    }
  };

  const websiteIntegrationOptions: SelectableOptionCardProps[] = useMemo(() => {
    if (!showWebsiteGuide) {
      return [];
    }

    // If selected plugin is empty, it means the merchant has selected "None Of the Above"
    const websitePluginName =
      selectedWebsitePlugin === '' ? 'Custom Website' : selectedWebsitePlugin;

    if (websitePluginName) {
      const activePluginConfig = onboardingData?.merchantOnboardingData?.supportedPlugins?.find(
        (plugin) => plugin?.name === websitePluginName,
      );

      const integrationGuide =
        activePluginConfig?.integrationGuide ||
        INTEGRATION_GUIDE[AvailablePlatformTypesEnum.WEBSITE];

      return [
        {
          customTitle: (
            <Box display="flex" flexWrap="wrap" rowGap="spacing.1" columnGap="spacing.4">
              <Text size={isMobile ? 'small' : 'medium'} weight="semibold">
                Here is a detailed set up guide{' '}
              </Text>
              <Link
                href={integrationGuide}
                size={isMobile ? 'small' : 'medium'}
                icon={LayersIcon}
                target="_blank"
              >
                {selectedWebsitePlugin
                  ? `Build on ${selectedWebsitePlugin}`
                  : 'Build API Integration'}
              </Link>
            </Box>
          ),
          subTitle: `${websitePluginName}`,
          cardImageUrl: activePluginConfig?.icon || websiteIntegratedIcon,
          handleClick: () => {
            window.open(integrationGuide, '_blank');
          },
          analyticsName: 'web-integration-guide-card',
        },
      ];
    }

    // Define integration options based on platform type
    return [
      {
        title: 'Custom website',
        subTitle: 'Built on my own',
        cardImageUrl: customWebsiteIntegrationIcon,
        handleClick: () => handleAddPlugin(''),
        analyticsName: 'custom-website-integration-card',
      },
      {
        title: 'Used a web builder',
        subTitle: 'Like Shopify, WooCommerce, Wix.',
        cardImageUrl: pluginWebsiteIntegrationIcon,
        handleClick: () => setWebsitePluginModalVisible(true),
        analyticsName: 'web-builder-integration-card',
      },
    ];
  }, [websiteUrl, platformType, selectedWebsitePlugin, isMobile]);

  useEffect(() => {
    // In case of KLA or no website, we will only show the plugin integration guide on Client side
    if (!hasApiKeyAccess || !websiteUrl) {
      return;
    }

    // Find if there's a plugin used for this website
    const selectedPlugin = onboardingData?.merchantOnboardingData?.selectedPlugins?.find(
      (plugin) => plugin?.website === websiteUrl,
    )?.selectedPlugin;
    setSelectedWebsitePlugin(typeof selectedPlugin === 'string' ? selectedPlugin : null);
  }, [onboardingData?.merchantOnboardingData?.selectedPlugins]);

  return (
    <Box display="flex" flexDirection="column" gap={{ base: 'spacing.5', m: 'spacing.7' }}>
      {websiteIntegrationOptions?.length > 0 && (
        <Box>
          <Box
            display="flex"
            alignItems="center"
            justifyContent="space-between"
            flexWrap="wrap"
            gap="spacing.4"
          >
            <Text
              color="surface.text.gray.subtle"
              weight="medium"
              size={isMobile ? 'small' : 'medium'}
            >
              {typeof selectedWebsitePlugin === 'string'
                ? `You selected ${selectedWebsitePlugin || 'Custom Website'}`
                : 'What did you use to build your website?'}
            </Text>
            {websiteIntegrationOptions?.length === 1 && (
              <Link
                color="neutral"
                icon={EditIcon}
                variant="button"
                onClick={() => setSelectedWebsitePlugin(null)}
                size={isMobile ? 'small' : 'medium'}
              >
                Edit
              </Link>
            )}
          </Box>
          <Box
            display="flex"
            flexDirection={{ base: 'column', l: 'row' }}
            gap={{ base: 'spacing.5', m: 'spacing.7' }}
            paddingTop="spacing.4"
          >
            {websiteIntegrationOptions.map((option) => (
              <SelectableOptionCard
                key={option.subTitle}
                title={option.title}
                customTitle={option.customTitle}
                subTitle={option.subTitle}
                cardImageUrl={option.cardImageUrl}
                handleClick={option.handleClick}
                analyticsName={option.analyticsName}
              />
            ))}
          </Box>
        </Box>
      )}

      {(showAndroidGuide || showIOSGuide) && (
        <>
          {websiteIntegrationOptions?.length > 0 && <Divider />}
          <AppIntegrationGuide hasAndroidIntent={showAndroidGuide} hasIOSIntent={showIOSGuide} />
        </>
      )}

      {websitePluginModalVisible && (
        <WebsitePluginModal
          onDismiss={() => {
            setWebsitePluginModalVisible(false);
          }}
          supportedPlugins={onboardingData?.merchantOnboardingData?.supportedPlugins}
          handleAddPlugin={handleAddPlugin}
        />
      )}
    </Box>
  );
};

export default IntegrationGuide;

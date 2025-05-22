import React, { useMemo, useState, useEffect, Suspense, lazy } from 'react';
import { Box, Link, Divider, Text, EditIcon, LayersIcon } from '@razorpay/blade/components';
import { PAYMENT_CHANNEL_OPTIONS } from 'apps/onboarding-experience/src/common/types/merchant';
import SelectableOptionCard, {
  SelectableOptionCardProps,
} from 'apps/onboarding-experience/src/common/components/SelectableOptionCard';
import IntegrationOptionIcon from 'apps/onboarding-experience/src/assets/IntegrationOption.svg';
import { getSpecificPlatformType } from 'apps/onboarding-experience/src/common/utils/merchant';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { INTEGRATION_GUIDE } from '@FTUX/constants/accordion';

const WebsitePluginModal = lazy(
  () => import('apps/onboarding-experience/src/common/components/WebsitePluginModal'),
);

const IntegrationGuide = () => {
  const { merchantData, onboardingData, addMerchantWebsitePlugin, isAddingWebsitePlugin } =
    useMerchantContext();
  const [selectedWebsitePlugin, setSelectedWebsitePlugin] = useState<string | null>(null);
  const [websitePluginModalVisible, setWebsitePluginModalVisible] = useState(false);

  const websiteUrl =
    merchantData?.merchantById?.business?.paymentAcceptanceChannels?.[
      PAYMENT_CHANNEL_OPTIONS.Websites
    ]?.urls?.[0]?.value;
  const platformType = getSpecificPlatformType(websiteUrl);

  const hasAndroidIntegration =
    platformType === 'android' ||
    merchantData?.merchantById?.business?.paymentAcceptanceChannels?.[
      PAYMENT_CHANNEL_OPTIONS.Android
    ]?.accept;

  const hasIOSIntegration =
    platformType === 'ios' ||
    merchantData?.merchantById?.business?.paymentAcceptanceChannels?.[PAYMENT_CHANNEL_OPTIONS.IOS]
      ?.accept;

  const handleAddPlugin = async (newPlugin: string) => {
    try {
      await addMerchantWebsitePlugin({ websiteUrl: websiteUrl || '', pluginName: newPlugin });
    } catch (err) {
      console.error(err);
    }
  };

  const websiteIntegrationOptions: SelectableOptionCardProps[] = useMemo(() => {
    if (!websiteUrl || platformType !== 'website') {
      return [];
    }

    // If selected plugin is empty, it means the merchant has selected "None Of the Above"
    const websitePluginName =
      selectedWebsitePlugin === '' ? 'Custom Website' : selectedWebsitePlugin;

    if (websitePluginName) {
      const activePluginConfig = onboardingData?.merchantOnboardingData?.supportedPlugins?.find(
        (plugin) => plugin?.name === websitePluginName,
      );

      const integrationGuide = activePluginConfig?.integrationGuide || INTEGRATION_GUIDE['website'];

      return [
        {
          customTitle: (
            <Box display="flex" flexDirection="row">
              <Link href={integrationGuide} size="medium" icon={LayersIcon} target="_blank">
                Build on {websitePluginName}
              </Link>
              <Text size="medium"> - step-by-step guide to set up</Text>
            </Box>
          ),
          subTitle: `${websitePluginName}`,
          cardImageUrl: activePluginConfig?.icon || IntegrationOptionIcon,
          handleClick: () => {
            window.open(integrationGuide, '_blank');
          },
        },
      ];
    }

    // Define integration options based on platform type
    return [
      {
        title: 'Custom website',
        subTitle: 'On my own CMS',
        cardImageUrl: IntegrationOptionIcon,
        handleClick: () => handleAddPlugin(''),
      },
      {
        title: 'Used a web builder',
        subTitle: 'Like Shopify, WooCommerce, Wix.',
        cardImageUrl: IntegrationOptionIcon,
        handleClick: () => setWebsitePluginModalVisible(true),
      },
    ];
  }, [websiteUrl, platformType, selectedWebsitePlugin]);

  useEffect(() => {
    // Find if there's a plugin used for this website
    const selectedPlugin = onboardingData?.merchantOnboardingData?.selectedPlugins?.find(
      (plugin) => plugin?.website === websiteUrl,
    )?.selectedPlugin;
    setSelectedWebsitePlugin(typeof selectedPlugin === 'string' ? selectedPlugin : null);
  }, [onboardingData?.merchantOnboardingData?.selectedPlugins]);

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6">
      {websiteIntegrationOptions?.length > 0 && (
        <Box>
          <Box
            display="flex"
            flexDirection={{ base: 'column', l: 'row' }}
            alignItems={{ base: 'flex-start', l: 'center' }}
            justifyContent={{ l: 'space-between' }}
            gap="spacing.4"
          >
            <Text color="surface.text.gray.subtle" weight="medium">
              What did you use to build your website?
            </Text>
            {websiteIntegrationOptions?.length === 1 && (
              <Link
                color="neutral"
                icon={EditIcon}
                variant="button"
                onClick={() => setSelectedWebsitePlugin(null)}
              >
                Edit
              </Link>
            )}
          </Box>
          <Box
            display="flex"
            flexDirection={{ base: 'column', l: 'row' }}
            gap="40px"
            paddingTop="spacing.5"
          >
            {websiteIntegrationOptions.map((option) => (
              <SelectableOptionCard
                key={option.subTitle}
                title={option.title}
                customTitle={option.customTitle}
                subTitle={option.subTitle}
                cardImageUrl={option.cardImageUrl}
                handleClick={option.handleClick}
                isDisabled={isAddingWebsitePlugin}
              />
            ))}
          </Box>
        </Box>
      )}
      {(hasAndroidIntegration || hasIOSIntegration) && (
        <>
          {websiteIntegrationOptions?.length > 0 && <Divider />}
          <Box>
            <Text color="surface.text.gray.subtle" weight="medium">
              Resources for Apps
            </Text>
            <Box paddingTop="spacing.5">
              <SelectableOptionCard
                customTitle={
                  <Box display="flex" flexDirection="row">
                    <Text size="medium">Here is a detailed set up guide - </Text>
                    {hasAndroidIntegration ? (
                      <Link size="medium" icon={LayersIcon} href={INTEGRATION_GUIDE['android']}>
                        Build API Integration on Android
                      </Link>
                    ) : null}
                    {hasIOSIntegration ? (
                      <Link size="medium" icon={LayersIcon} href={INTEGRATION_GUIDE['ios']}>
                        Build API Integration on iOS
                      </Link>
                    ) : null}
                  </Box>
                }
                subTitle={'App integration'}
                cardImageUrl={IntegrationOptionIcon}
              />
            </Box>
          </Box>
        </>
      )}
      {websitePluginModalVisible && (
        <Suspense fallback={<Box>Loading...</Box>}>
          <WebsitePluginModal
            onDismiss={() => {
              setWebsitePluginModalVisible(false);
            }}
            supportedPlugins={onboardingData?.merchantOnboardingData?.supportedPlugins}
            handleAddPlugin={handleAddPlugin}
          />
        </Suspense>
      )}
    </Box>
  );
};

export default IntegrationGuide;

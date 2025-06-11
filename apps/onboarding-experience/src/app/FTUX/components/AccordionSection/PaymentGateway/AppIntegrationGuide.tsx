import React from 'react';
import { Box, Link, Text, LayersIcon } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { INTEGRATION_GUIDE } from '@FTUX/constants/accordion';
import SelectableOptionCard from '@OnboardingExperienceCommons/components/SelectableOptionCard';
import websiteIntegratedIcon from '@OnboardingExperienceAssets/WebsiteIntegratedIcon.svg';
import { AvailablePlatformTypesEnum } from '@OnboardingExperienceCommons/utils/website';

const AppIntegrationGuide = ({
  hasAndroidIntent,
  hasIOSIntent,
}: {
  hasAndroidIntent?: boolean;
  hasIOSIntent?: boolean;
}) => {
  const isMobile = isMobileDevice();
  const textSize = isMobile ? 'small' : 'medium';

  return (
    <Box>
      <Text color="surface.text.gray.subtle" weight="medium" size={textSize}>
        Resources for Apps
      </Text>
      <Box paddingTop="spacing.4">
        <SelectableOptionCard
          customTitle={
            <Box
              display="flex"
              flexDirection="row"
              flexWrap="wrap"
              rowGap="spacing.1"
              columnGap="spacing.4"
            >
              <Text size={textSize} weight="semibold" color="surface.text.gray.normal">
                Here is a detailed set up guide{' '}
              </Text>
              {hasAndroidIntent ? (
                <Link
                  size={textSize}
                  icon={LayersIcon}
                  href={INTEGRATION_GUIDE[AvailablePlatformTypesEnum.ANDROID]}
                  target="_blank"
                >
                  Integration guide set up (Android)
                </Link>
              ) : null}
              {hasIOSIntent ? (
                <Link
                  size={textSize}
                  icon={LayersIcon}
                  href={INTEGRATION_GUIDE[AvailablePlatformTypesEnum.IOS]}
                  target="_blank"
                >
                  Integration guide set up (iOS)
                </Link>
              ) : null}
            </Box>
          }
          subTitle={isMobile ? undefined : 'App integration'}
          cardImageUrl={websiteIntegratedIcon}
          analyticsName="app-integration-guide-card"
        />
      </Box>
    </Box>
  );
};

export default AppIntegrationGuide;

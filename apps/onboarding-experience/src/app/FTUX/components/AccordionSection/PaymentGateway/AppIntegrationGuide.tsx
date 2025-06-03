import React from 'react';
import { Box, Link, Text, LayersIcon } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import { INTEGRATION_GUIDE } from '@FTUX/constants/accordion';
import SelectableOptionCard from '@OnboardingExperienceCommons/components/SelectableOptionCard';
import websiteIntegratedIcon from '@OnboardingExperienceAssets/WebsiteIntegratedIcon.svg';

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
      <Box paddingTop={{ base: 'spacing.4', m: 'spacing.5' }}>
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
                  href={INTEGRATION_GUIDE['android']}
                  target="_blank"
                >
                  Integration guide set up (Android)
                </Link>
              ) : null}
              {hasIOSIntent ? (
                <Link
                  size={textSize}
                  icon={LayersIcon}
                  href={INTEGRATION_GUIDE['ios']}
                  target="_blank"
                >
                  Integration guide set up (iOS)
                </Link>
              ) : null}
            </Box>
          }
          subTitle={'App integration'}
          cardImageUrl={websiteIntegratedIcon}
        />
      </Box>
    </Box>
  );
};

export default AppIntegrationGuide;

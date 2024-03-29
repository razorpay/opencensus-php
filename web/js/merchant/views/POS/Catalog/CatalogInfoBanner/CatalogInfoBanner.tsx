import React from 'react';
import { Box, Heading, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import BannerImage from 'assets/pos/bannerimage.webp';

import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

import Timeline from './Timeline';
import { BannerImageStyled, Ellipse1, Ellipse2 } from './styles';

const CatalogInfoBanner = (): JSX.Element => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isLargeScreen = ['m', 'l', 'xl'].includes(matchedBreakpoint ?? '');
  const foldRef = React.useRef<HTMLDivElement>(null);

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 3,
      section: 'Catalog Info',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
    });
  });

  return (
    <Box
      position="relative"
      display="flex"
      justifyContent="center"
      marginBottom="spacing.5"
      ref={foldRef}
    >
      <Box
        minHeight="400px"
        width="100%"
        position="relative"
        display={{ base: 'block', l: 'grid' }}
        gridTemplateColumns="55% 45%"
        overflow="hidden"
        backgroundColor="surface.background.gray.subtle"
        borderRadius="large"
      >
        <Box
          marginX={{ base: 'spacing.5', l: 'spacing.11' }}
          marginTop={{ base: 'spacing.8', l: 'spacing.11' }}
          marginBottom={{ base: 'spacing.0', l: 'spacing.11' }}
        >
          <Heading
            color="surface.text.gray.normal"
            textAlign={isLargeScreen ? 'left' : 'center'}
            marginBottom="spacing.3"
            size="xlarge"
          >
            Your POS is Just a Few Clicks Away!
          </Heading>
          <Text
            weight="regular"
            color="surface.text.gray.subtle"
            textAlign={isLargeScreen ? 'left' : 'center'}
            marginBottom="spacing.8"
            size="large"
          >
            Easily order your POS devices online with just a few clicks, and receive them within 2-3
            days after KYC approval.
          </Text>
          <Timeline />
        </Box>
        <Box
          position="relative"
          width="100%"
          right="spacing.0"
          display="flex"
          alignItems="flex-end"
          justifyContent="right"
          paddingLeft="spacing.5"
        >
          <BannerImageStyled src={BannerImage} alt="banner image" />
        </Box>
        <Ellipse1 />
        <Ellipse2 />
      </Box>
    </Box>
  );
};

export default CatalogInfoBanner;

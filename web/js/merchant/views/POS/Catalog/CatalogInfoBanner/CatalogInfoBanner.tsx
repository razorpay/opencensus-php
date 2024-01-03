import React from 'react';
import { Box, Title, Heading, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import BannerImage from 'assets/pos/bannerimage.webp';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

import Timeline from './Timeline';
import { Ellipse1, Ellipse2, BannerImageStyled } from './styles';

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
        maxWidth="1600px"
        width="100%"
        position="relative"
        display={{ base: 'block', l: 'grid' }}
        gridTemplateColumns="55% 45%"
        overflow="hidden"
        backgroundColor="surface.background.level1.lowContrast"
        borderRadius="large"
      >
        <Box
          marginX={{ base: 'spacing.5', l: 'spacing.11' }}
          marginTop={{ base: 'spacing.8', l: 'spacing.11' }}
          marginBottom={{ base: 'spacing.0', l: 'spacing.11' }}
        >
          <Title
            size="large"
            color="surface.text.normal.lowContrast"
            textAlign={isLargeScreen ? 'left' : 'center'}
            marginBottom="spacing.3"
          >
            Your POS is Just a Few Clicks Away!
          </Title>
          <Heading
            weight="regular"
            color="surface.text.subtle.lowContrast"
            textAlign={isLargeScreen ? 'left' : 'center'}
            marginBottom="spacing.8"
          >
            Easily order your POS devices online with just a few clicks, and receive them within 2-3
            days after KYC approval.
          </Heading>
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

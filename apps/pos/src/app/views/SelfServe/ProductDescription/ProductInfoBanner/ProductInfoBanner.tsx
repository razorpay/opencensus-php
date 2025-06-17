import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';
import { InfoBanner } from 'apps/pos/src/app/views/SelfServe/types';
import { useScrollObserver } from 'apps/pos/src/app/views/SelfServe/utils/ScrollObserver';

type ProductInfoBanner = {
  infoBanner: InfoBanner;
};

const ProductInfoBanner = ({ infoBanner }: ProductInfoBanner): JSX.Element => {
  const { isLargeScreen } = useBladeBreakpoints();
  const { image, mobileImage, features } = infoBanner;
  const foldRef = React.useRef<HTMLDivElement>(null);

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 3,
      section: 'Product Info Banner',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  });

  return (
    <Box position="relative" display={{ base: 'flex', xl: 'block' }} flexDirection="column-reverse">
      <Box
        position={{ base: 'static', l: 'absolute' }}
        display="flex"
        flexDirection="column"
        justifyContent="center"
        height="100%"
        paddingY="spacing.7"
        maxWidth={{ base: '100%', l: '50%' }}
        paddingX="spacing.5"
        marginLeft={{ base: 'spacing.3', xl: 'spacing.10' }}
      >
        {features.map(({ text, icon }, index) => (
          <Box
            key={text}
            marginBottom={
              index !== features.length - 1
                ? { base: 'spacing.8', l: 'spacing.5', xl: 'spacing.8' }
                : 'spacing.0'
            }
            display="flex"
            flexDirection="column"
            alignItems={isLargeScreen ? 'left' : 'center'}
            width="285px"
          >
            <img src={icon} width="28" />
            <Text
              weight="semibold"
              textAlign={isLargeScreen ? 'left' : 'center'}
              marginTop="spacing.2"
              color="surface.text.gray.subtle"
              size="large"
            >
              {text}
            </Text>
          </Box>
        ))}
      </Box>
      <img
        src={isLargeScreen ? image : mobileImage}
        height="100%"
        width="100%"
        alt="pdp info banner"
      />
    </Box>
  );
};

export default ProductInfoBanner;

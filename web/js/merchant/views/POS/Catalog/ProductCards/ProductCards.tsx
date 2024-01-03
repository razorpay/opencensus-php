import React from 'react';
import { Box } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import MiniPosMobile from 'assets/pos/main-banner/minipos-mobile.webp';
import MPos from 'assets/pos/main-banner/mpos-mobile.webp';
import { PRODUCT_DESCRIPTIONS } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

import AndroidMiniPos from './AndroidMiniPos';
import MobileCardContainer from './MobileCardContainer';
import MobilePos from './MobilePos';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

const ProductCards = (): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const foldRef = React.useRef<HTMLDivElement>(null);

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 2,
      section: 'Product Cards',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
    });
  });

  return (
    <Box display="flex" justifyContent="center" ref={foldRef}>
      <Box maxWidth="1600px" width="100%" display={{ base: 'block', xl: 'flex' }} gap="spacing.5">
        {isMobile ? (
          <MobileCardContainer
            code={PRODUCT_DESCRIPTIONS.a910.code}
            image={MiniPosMobile}
            cardDescription="Feature packed and portable"
          />
        ) : (
          <AndroidMiniPos />
        )}
        {isMobile ? (
          <MobileCardContainer
            code={PRODUCT_DESCRIPTIONS.d180.code}
            image={MPos}
            cardDescription="Pocket Sized and Affordable"
          />
        ) : (
          <MobilePos />
        )}
      </Box>
    </Box>
  );
};

export default ProductCards;

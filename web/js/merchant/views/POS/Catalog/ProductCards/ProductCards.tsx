import React from 'react';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import MiniPosMobile from 'assets/pos/main-banner/minipos-mobile.webp';
import MPos from 'assets/pos/main-banner/mpos-mobile.webp';
import { ANDROID_MINI_POS, MOBILE_POS } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

import AndroidMiniPos from './AndroidMiniPos';
import MobileCardContainer from './MobileCardContainer';
import MobilePos from './MobilePos';
import { ProductCardsContainer } from './styles';

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
    <ProductCardsContainer>
      {isMobile ? (
        <React.Fragment>
          <MobileCardContainer
            code={ANDROID_MINI_POS.code}
            image={MiniPosMobile}
            cardDescription="Feature packed and portable"
          />
          <MobileCardContainer
            code={MOBILE_POS.code}
            image={MPos}
            cardDescription="Pocket Sized and Affordable"
          />
        </React.Fragment>
      ) : (
        <React.Fragment>
          <AndroidMiniPos />
          <MobilePos />
        </React.Fragment>
      )}
    </ProductCardsContainer>
  );
};

export default ProductCards;

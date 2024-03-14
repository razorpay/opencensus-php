import React, { ReactNode } from 'react';
import { Box } from '@razorpay/blade/components';

import PartnerExclusivePrice from 'assets/partner-dashboard/PartnerExclusivePrice.svg';
import PartnerProductExclusivePrice from 'assets/partner-dashboard/PartnerProductExclusivePrice.svg';

import {
  PartnerExclusiveGradientContainer,
  PartnerExclusivePriceImage,
  PartnerExclusiveBannerGradientContainer,
} from './styles';

type PartnerExclusivePriceContainerProps = {
  isPartnerPricing: boolean;
  isMobileCard?: boolean;
  isMobile?: boolean;
  children: ReactNode;
  type: 'MAIN_BANNER' | 'PRODUCT_CARD';
};

export const PartnerExclusivePriceContainer = ({
  isPartnerPricing,
  isMobileCard,
  isMobile,
  children,
  type,
}: PartnerExclusivePriceContainerProps): JSX.Element => {
  if (type === 'MAIN_BANNER') {
    return (
      <Box width={isMobile ? 'auto' : 'fit-content'}>
        {isPartnerPricing ? (
          <PartnerExclusivePriceImage src={PartnerExclusivePrice} alt="Partner Exclusive" />
        ) : null}
        <PartnerExclusiveBannerGradientContainer isPartnerPricing={isPartnerPricing}>
          <Box
            paddingLeft={isPartnerPricing ? 'spacing.4' : 'spacing.1'}
            paddingTop={isPartnerPricing ? 'spacing.6' : 'spacing.0'}
            marginBottom="spacing.7"
            paddingBottom={isMobile ? 'spacing.3' : 'spacing.1'}
          >
            {children}
          </Box>
        </PartnerExclusiveBannerGradientContainer>
      </Box>
    );
  }
  return (
    <Box
      marginX={isMobileCard ? 'spacing.11' : 'spacing.0'}
      width={isMobileCard ? 'auto' : 'fit-content'}
    >
      {isPartnerPricing ? <img src={PartnerProductExclusivePrice} alt="Partner Exclusive" /> : null}
      <PartnerExclusiveGradientContainer isPartnerPricing={isPartnerPricing}>
        <Box
          padding={isPartnerPricing ? 'spacing.2' : 'spacing.0'}
          marginBottom={isMobileCard ? 'spacing.7' : 'spacing.0'}
        >
          {children}
        </Box>
      </PartnerExclusiveGradientContainer>
    </Box>
  );
};

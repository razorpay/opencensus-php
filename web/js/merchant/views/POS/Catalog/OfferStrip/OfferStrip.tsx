import React from 'react';
import { InfoIcon, OffersIcon, Text } from '@razorpay/blade/components';

import { OfferStripContainer } from './styles';

type OfferStripProps = {
  text: string;
  type?: 'light' | 'dark';
  variant?: 'offer' | 'info';
};

const OfferStrip = ({ text, type = 'light', variant = 'offer' }: OfferStripProps): JSX.Element => {
  return (
    <OfferStripContainer type={type}>
      {variant === 'offer' ? (
        <OffersIcon
          color={type === 'dark' ? 'feedback.icon.neutral.lowContrast' : 'brand.primary.500'}
          size="medium"
          marginRight="spacing.3"
        />
      ) : (
        <InfoIcon
          color={type === 'dark' ? 'feedback.icon.neutral.lowContrast' : 'brand.primary.500'}
          size="medium"
          marginRight="spacing.3"
        />
      )}
      <Text
        color={type === 'dark' ? 'surface.text.subtle.lowContrast' : 'brand.primary.500'}
        size="small"
        weight="bold"
      >
        {text}
      </Text>
    </OfferStripContainer>
  );
};

export default OfferStrip;

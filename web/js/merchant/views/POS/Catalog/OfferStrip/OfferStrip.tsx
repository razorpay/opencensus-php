import React from 'react';
import { InfoIcon, OffersIcon, Text, IconProps, TextProps } from '@razorpay/blade/components';

import { OfferStripContainer } from './styles';

type OfferStripProps = {
  text: string;
  type?: 'light' | 'dark';
  variant?: 'offer' | 'info';
  isPartnerPricing?: boolean;
};

type getOfferTextAndColorProps = {
  type: 'light' | 'dark';
  isPartnerPricing: boolean;
};

const getOfferAndInfoIconColor = ({
  type,
  isPartnerPricing,
}: getOfferTextAndColorProps): IconProps['color'] => {
  let offerAndInfoIconColor: IconProps['color'] = 'brand.primary.500';
  if (type === 'dark') {
    offerAndInfoIconColor = 'feedback.icon.neutral.lowContrast';
  } else if (isPartnerPricing) {
    offerAndInfoIconColor = 'feedback.icon.notice.lowContrast';
  }
  return offerAndInfoIconColor;
};

const getOfferTextColor = ({
  type,
  isPartnerPricing,
}: getOfferTextAndColorProps): TextProps<{ variant: 'body' }>['color'] => {
  let textColor: TextProps<{ variant: 'body' }>['color'] = 'brand.primary.500';
  if (type === 'dark') {
    textColor = 'surface.text.subtle.lowContrast';
  } else if (isPartnerPricing) {
    textColor = 'feedback.text.notice.lowContrast';
  }
  return textColor;
};
const OfferStrip = ({
  text,
  type = 'light',
  variant = 'offer',
  isPartnerPricing = false,
}: OfferStripProps): JSX.Element => {
  return (
    <OfferStripContainer type={type} isPartnerPricing={isPartnerPricing}>
      {variant === 'offer' ? (
        <OffersIcon
          color={getOfferAndInfoIconColor({ type, isPartnerPricing })}
          size="medium"
          marginRight="spacing.3"
        />
      ) : (
        <InfoIcon
          color={getOfferAndInfoIconColor({ type, isPartnerPricing })}
          size="medium"
          marginRight="spacing.3"
        />
      )}
      <Text color={getOfferTextColor({ type, isPartnerPricing })} size="small" weight="bold">
        {text}
      </Text>
    </OfferStripContainer>
  );
};

export default OfferStrip;

import React from 'react';
import { InfoIcon, OffersIcon, Text, IconProps, TextProps } from '@razorpay/blade/components';

import { OfferStripContainer } from './styles';

type OfferStripProps = {
  text: string;
  type?: 'light' | 'dark';
  variant?: 'offer' | 'info';
  isPartnerPricing?: boolean;
  isRounded?: boolean;
  size?: 'small' | 'large';
};

type getOfferTextAndColorProps = {
  type: 'light' | 'dark';
  isPartnerPricing: boolean;
};

const getOfferAndInfoIconColor = ({
  type,
  isPartnerPricing,
}: getOfferTextAndColorProps): IconProps['color'] => {
  let offerAndInfoIconColor: IconProps['color'] = 'surface.icon.primary.normal';
  if (type === 'dark') {
    offerAndInfoIconColor = 'feedback.icon.neutral.intense';
  } else if (isPartnerPricing) {
    offerAndInfoIconColor = 'feedback.icon.notice.intense';
  }
  return offerAndInfoIconColor;
};

const getOfferTextColor = ({
  type,
  isPartnerPricing,
}: getOfferTextAndColorProps): TextProps<{ variant: 'body' }>['color'] => {
  let textColor: TextProps<{ variant: 'body' }>['color'] = 'surface.text.primary.normal';
  if (type === 'dark') {
    textColor = 'surface.text.gray.subtle';
  } else if (isPartnerPricing) {
    textColor = 'feedback.text.notice.intense';
  }
  return textColor;
};
const OfferStrip = ({
  text,
  type = 'light',
  variant = 'offer',
  isPartnerPricing = false,
  isRounded = false,
  size = 'large',
}: OfferStripProps): JSX.Element | null => {
  if (!text) return null;
  return (
    <OfferStripContainer
      size={size}
      isRounded={isRounded}
      type={type}
      isPartnerPricing={isPartnerPricing}
    >
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
      <Text
        testID="offer-strip-text"
        color={getOfferTextColor({ type, isPartnerPricing })}
        size="small"
        weight="semibold"
      >
        {text}
      </Text>
    </OfferStripContainer>
  );
};

export default OfferStrip;

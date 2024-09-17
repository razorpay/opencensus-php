import React from 'react';
import { Amount, Box, Text } from '@razorpay/blade/components';

import {
  PosPricingDescription,
  PricingType,
} from 'merchant/views/POS/Catalog/ProductCards/PosProductCard';
import { PartnerExclusivePriceContainer } from 'merchant/views/POS/PartnerExclusiveContainer';

interface PricingDescriptionProps {
  isValidOffer: boolean | null;
  isPartnerPricing: boolean;
  hasOnlyLifetimePricing: boolean;
  monthly: number;
  setupFee: number;
  lifetime: number;
  tncText: string;
  pricingDescription: PosPricingDescription[];
  rentalDiscountPeriod: number;
}
const PricingDescription = ({
  isValidOffer,
  isPartnerPricing,
  monthly = 0,
  setupFee = 0,
  lifetime = 0,
  tncText,
  pricingDescription,
  hasOnlyLifetimePricing,
  rentalDiscountPeriod,
}: PricingDescriptionProps) => {
  const getPricingDescription = (pricingDescription: PosPricingDescription) => {
    switch (pricingDescription.type as PricingType) {
      case 'free-item':
        return (
          <Text
            key={pricingDescription.type}
            marginBottom="spacing.2"
            testID="free-pricing-text"
            color="surface.text.primary.normal"
          >
            <Amount
              value={0}
              isAffixSubtle={false}
              suffix="none"
              marginRight="spacing.2"
              size="medium"
              weight="semibold"
            />{' '}
            Get it for FREE!
          </Text>
        );
      case 'monthly-rental':
        return (
          <Text key={pricingDescription.type} marginBottom="spacing.2" testID="monthy-pricing-text">
            <Amount
              value={isValidOffer ? pricingDescription.amount?.offer?.nextMonthly ?? 0 : monthly}
              suffix="none"
              isAffixSubtle={false}
              size="large"
              weight="semibold"
            />{' '}
            {pricingDescription.amount?.offer?.prevMonthly ? (
              <Amount
                value={pricingDescription.amount?.offer?.prevMonthly ?? 0}
                isAffixSubtle={false}
                suffix="none"
                marginRight="spacing.2"
                isStrikethrough={true}
                color="surface.text.gray.muted"
                size="medium"
                weight="semibold"
              />
            ) : null}{' '}
            /month after {rentalDiscountPeriod} {rentalDiscountPeriod > 1 ? 'months' : 'month'}*
          </Text>
        );
      case 'setup-fee':
        return (
          <Text key={pricingDescription.type} marginBottom="spacing.3" testID="setup-pricing-text">
            <Amount
              value={setupFee}
              suffix="none"
              isAffixSubtle={false}
              size="large"
              weight="semibold"
            />{' '}
            {pricingDescription.amount?.offer?.prevSetupFee ? (
              <Amount
                value={pricingDescription.amount?.offer?.prevSetupFee ?? 0}
                isAffixSubtle={false}
                suffix="none"
                marginRight="spacing.2"
                isStrikethrough={true}
                color="surface.text.gray.muted"
                size="medium"
                weight="semibold"
              />
            ) : null}{' '}
            setup fee
          </Text>
        );
      case 'lifetime-pricing':
        return (
          <Text key={pricingDescription.type} marginBottom="spacing.3" testID="setup-pricing-text">
            <Amount
              value={lifetime}
              suffix="none"
              isAffixSubtle={false}
              size="large"
              weight="semibold"
            />{' '}
            {pricingDescription.amount?.offer?.prevLifetime ? (
              <Amount
                value={pricingDescription.amount?.offer?.prevLifetime}
                isAffixSubtle={false}
                suffix="none"
                marginRight="spacing.2"
                isStrikethrough={true}
                color="surface.text.gray.muted"
                size="medium"
                weight="semibold"
              />
            ) : null}{' '}
            Lifetime / One Time Price
          </Text>
        );
      case 'no-offer-pricing':
        return (
          <Text weight="semibold" testID="pricing-details-text">
            <Amount
              value={monthly}
              suffix="none"
              isAffixSubtle={false}
              size="medium"
              weight="semibold"
            />{' '}
            /month +{' '}
            <Amount
              value={setupFee}
              suffix="none"
              isAffixSubtle={false}
              type="body"
              size="medium"
              weight="semibold"
            />{' '}
            setup fee
          </Text>
        );
      default:
        return null;
    }
  };

  return (
    <PartnerExclusivePriceContainer
      isPartnerPricing={isPartnerPricing && !isValidOffer}
      type="PRODUCT_CARD"
    >
      <Box>{pricingDescription.map((item) => getPricingDescription(item))}</Box>
      {!hasOnlyLifetimePricing ? <Text color="surface.text.gray.subtle">{tncText}</Text> : null}
    </PartnerExclusivePriceContainer>
  );
};

export default PricingDescription;

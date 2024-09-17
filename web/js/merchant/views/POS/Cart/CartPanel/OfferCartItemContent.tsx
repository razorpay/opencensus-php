import React from 'react';
import { Amount, Box, Text } from '@razorpay/blade/components';

import { CART_OFFER_CONTENT } from 'merchant/views/POS/constants';
import { ProductDescriptionPricing } from 'merchant/views/POS/types';

type OfferCartItemContentProps = {
  pricing: ProductDescriptionPricing;
  rentalDiscountPeriod: number;
};

const OfferCartItemContent = ({
  pricing,
  rentalDiscountPeriod,
}: OfferCartItemContentProps): JSX.Element => {
  const offerContent = CART_OFFER_CONTENT[pricing.type];
  return (
    <Box>
      {offerContent.map((offer, index) => {
        const offerPrices = offer.pricing(pricing);
        const id = `pricing-${pricing.type}-offer-${index}`;
        const rentalPeriodTxt = offer.text(rentalDiscountPeriod);
        //null is added to hide the first 3 months offer line if rental_discount_period is 0 in api response
        if (rentalPeriodTxt === null) return null;
        return (
          <Text key={id} testID={id} color="surface.text.gray.muted">
            <Amount value={offerPrices?.currentValue} suffix="none" isAffixSubtle={false} />{' '}
            {offerPrices?.prevValue !== null ? (
              <Amount
                value={offerPrices?.prevValue}
                isAffixSubtle={false}
                suffix="none"
                marginRight="spacing.2"
                isStrikethrough={true}
                color="surface.text.gray.muted"
                size="medium"
                weight="semibold"
              />
            ) : null}
            {'  '}
            {offer?.text(rentalDiscountPeriod)}
          </Text>
        );
      })}
    </Box>
  );
};

export default OfferCartItemContent;

import React from 'react';
import { Amount, Box, Text } from '@razorpay/blade/components';

import AmountWithStrikeThrough from 'merchant/views/POS/ProductDescription/ProductPriceCards/AmountWithStrikeThrough';
import { CART_OFFER_CONTENT } from 'merchant/views/POS/constants';
import { ProductDescriptionPricing } from 'merchant/views/POS/types';

type OfferCartItemContentProps = {
  pricing: ProductDescriptionPricing;
};

const OfferCartItemContent = ({ pricing }: OfferCartItemContentProps): JSX.Element => {
  const offerContent = CART_OFFER_CONTENT[pricing.type];

  return (
    <Box>
      {offerContent.map((offer, index) => {
        const offerPrices = offer.pricing(pricing);
        const id = `pricing-${pricing.type}-offer-${index}`;

        return (
          <Text key={id} testID={id} type="subdued">
            <Amount value={offerPrices?.currentValue} suffix="none" isAffixSubtle={false} />{' '}
            {offerPrices?.prevValue !== null ? (
              <AmountWithStrikeThrough value={offerPrices?.prevValue} />
            ) : null}
            {'  '}
            {offer?.text}
          </Text>
        );
      })}
    </Box>
  );
};

export default OfferCartItemContent;

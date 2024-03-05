import React, { useContext } from 'react';
import { Box, CheckCircleIcon, Text, Amount } from '@razorpay/blade/components';

import { OFFER_CARDS_STRUCT } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { ProductDescriptionPricing } from 'merchant/views/POS/types';

import AmountWithStrikeThrough from './AmountWithStrikeThrough';

type OfferPriceCardContentProps = {
  pricing: ProductDescriptionPricing;
  showHeaders?: boolean;
};

type CurrencyTypes = 'INR';

const OfferPriceCardContent = ({
  pricing,
  showHeaders = true,
}: OfferPriceCardContentProps): JSX.Element => {
  const { name, type } = pricing;
  const { state } = useContext(PosDeviceStoreContext);
  const { user } = state;

  return (
    <Box testID="pos-offer-price-cards">
      {showHeaders ? (
        <Text color="brand.primary.500" marginBottom="spacing.4" weight="bold">
          {name.toUpperCase()}
        </Text>
      ) : null}
      <Box>
        {OFFER_CARDS_STRUCT?.[type].map((offerObj, index) => {
          const offerPricing = offerObj.pricing(pricing);

          return (
            <Box
              display="flex"
              alignItems="center"
              marginBottom="spacing.3"
              key={`offer-card-${index}`}
            >
              <CheckCircleIcon size="medium" color="brand.primary.500" marginRight="spacing.4" />
              <Text color="surface.text.subdued.lowContrast">
                <Amount
                  value={offerPricing.currentValue}
                  currency={user?.merchant?.currency as CurrencyTypes}
                  isAffixSubtle={false}
                  suffix="none"
                  marginRight="spacing.2"
                  size="heading-small-bold"
                />
                {offerPricing.prevValue !== null && !isNaN(offerPricing.prevValue) ? (
                  <AmountWithStrikeThrough value={offerPricing.prevValue} />
                ) : null}
                {offerObj.text}
              </Text>
            </Box>
          );
        })}
      </Box>
    </Box>
  );
};

export default OfferPriceCardContent;

import React from 'react';
import { Box, Title, Text, Divider } from '@razorpay/blade/components';

import OfferStrip from 'merchant/views/POS/Catalog/OfferStrip';
import { DETAILED_PRICING, OFFER_DETAILED_PRICING } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { ProductDescription } from 'merchant/views/POS/types';

type DetailedPricingProps = {
  product: ProductDescription;
};

type ShowOfferProps = {
  banner: 'offer' | 'info';
  offer: {
    offerText: string;
    partnerOfferText: string;
  };
  isPartnerPricing: boolean;
};
const ShowOffer = ({ banner, offer, isPartnerPricing }: ShowOfferProps): JSX.Element => {
  let offerText = offer.offerText;
  if (banner === 'info') {
    offerText = isPartnerPricing
      ? 'Offer ends after 3 months'
      : 'After 1L GMV, Below Rates to Apply';
  } else if (isPartnerPricing) {
    offerText = offer.partnerOfferText;
  }
  return (
    <Box marginBottom="spacing.3">
      <OfferStrip text={offerText} variant={banner} isPartnerPricing={isPartnerPricing} />
    </Box>
  );
};
const DetailedPricing = ({ product }: DetailedPricingProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const isShowOffer = !!product?.offer;
  const isPartnerPricing = product?.isPartnerPricing;

  return (
    <Box
      width="100%"
      maxWidth="1200px"
      marginBottom="spacing.8"
      marginX={{ base: 'spacing.5', l: 'spacing.3' }}
    >
      <Box marginBottom="spacing.5">
        <Title size="medium" textAlign="center">
          Detailed Pricing
        </Title>
      </Box>
      {[...(isShowOffer ? OFFER_DETAILED_PRICING : []), ...DETAILED_PRICING].map(
        ({ title, rows, banner }, index) => (
          <Box key={title ?? `heading-${index}`}>
            {banner && product?.offer ? (
              <ShowOffer
                banner={banner}
                offer={product.offer}
                isPartnerPricing={isPartnerPricing}
              />
            ) : null}
            {title ? (
              <Text weight="bold" size="large" marginBottom="spacing.5">
                {title}
              </Text>
            ) : null}
            <Box
              backgroundColor="surface.background.level2.lowContrast"
              marginBottom={
                index === OFFER_DETAILED_PRICING.length - 1 ? 'spacing.10' : 'spacing.5'
              }
              borderRadius="medium"
            >
              {rows.map(({ name, value, text, prevValue, isOfferOnlyField }, index) =>
                (isOfferOnlyField && isShowOffer) || !isOfferOnlyField ? (
                  <React.Fragment key={name}>
                    <Box display="flex" width="100%" padding="spacing.5">
                      <Box width="70%">
                        <Text weight={title !== null ? 'regular' : 'bold'}>{name}</Text>
                      </Box>
                      <Box display="flex">
                        <Text weight="bold" marginRight="spacing.2">
                          {value}
                        </Text>
                        {prevValue && isShowOffer ? (
                          <Text textDecorationLine="line-through">{value}</Text>
                        ) : null}
                        {text ? <Text weight="bold">{text}</Text> : null}
                      </Box>
                    </Box>
                    {index !== rows.length - 1 && rows.length > 1 ? (
                      <Divider marginX="spacing.5" />
                    ) : null}
                  </React.Fragment>
                ) : null,
              )}
            </Box>
          </Box>
        ),
      )}
      <Box testID="detailed-pricing-footer-text" marginX={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Text>*Transaction Value</Text>
        <Text>
          #Note: Any value added services (Ex- EMI) required by the Merchant shall be charged
          separately, as per the agreed terms. Also, devices once ordered cannot be canceled.
        </Text>
      </Box>
    </Box>
  );
};

export default DetailedPricing;

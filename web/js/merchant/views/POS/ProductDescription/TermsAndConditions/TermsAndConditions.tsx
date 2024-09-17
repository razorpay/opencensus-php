import React, { useMemo } from 'react';
import {
  Box,
  Divider,
  Heading,
  List,
  ListItem,
  ArrowRightIcon,
  Text,
} from '@razorpay/blade/components';

import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import OfferPriceCardContent from 'merchant/views/POS/ProductDescription/ProductPriceCards/OfferPriceCardContent';
import {
  OFFER_CARDS_STRUCT,
  TERMS_AND_CONDITIONS,
  WD_10_OFFER_CARDS_STRUCT,
  SOUNDBOX_OFFER_TNC,
} from 'merchant/views/POS/constants';
import SOUNDBOX from 'merchant/views/POS/constants/Soundbox';
import { ProductDescription, TncTypes } from 'merchant/views/POS/types';

type TermsAndConditionsProps = {
  title: string;
  types?: TncTypes[];
  product: ProductDescription;
  isShowPricing?: boolean;
};

const TermsAndConditions = ({
  title,
  types = ['normal'],
  product,
  isShowPricing,
}: TermsAndConditionsProps): JSX.Element | null => {
  const pricingMap = useMemo(() => {
    return isShowPricing
      ? (product?.pricing || []).reduce((acc, curr) => {
          acc[curr.type] = curr;
          return acc;
        }, {})
      : {};
  }, [product, isShowPricing]);

  const getTncComponent = () => {
    if (product.code === SOUNDBOX.code) {
      return SOUNDBOX_OFFER_TNC.filter((tnc) => {
        return product.pricing.find((pricingPlan) => pricingPlan.type === tnc.pricingType);
      });
    }
    return TERMS_AND_CONDITIONS.filter((tnc) => {
      return product.pricing.find((pricingPlan) => pricingPlan.type === tnc.pricingType);
    });
  };

  if (!product) return null;
  return (
    <Box width="100%" maxWidth="1200px" marginBottom="spacing.8">
      <Box marginBottom="spacing.5">
        <Heading textAlign="center" size="xlarge">
          {title}
        </Heading>
      </Box>
      <Box
        display={{ base: 'block', m: 'flex' }}
        backgroundColor="surface.background.gray.intense"
        padding="spacing.5"
        borderRadius="medium"
      >
        {getTncComponent().map(({ criteria, pricingType, rows }, index) => (
          <Box key={criteria} marginBottom="spacing.5" display="flex" flexDirection="column">
            <Text marginBottom="spacing.4" marginLeft="spacing.8" size="large">
              {criteria}
            </Text>

            <Divider marginBottom="spacing.4" />
            <Box
              borderRightWidth={{
                base: 'none',
                m: index !== getTncComponent().length - 1 ? 'thin' : 'none',
              }}
              borderRightColor="surface.border.gray.muted"
              paddingX={{ base: 'spacing.0', m: 'spacing.8' }}
              height="100%"
              paddingBottom="spacing.5"
              display="flex"
              flexDirection="column"
            >
              {isShowPricing ? (
                <Box
                  marginX="spacing.2"
                  marginY="spacing.6"
                  backgroundColor="surface.background.gray.moderate"
                  padding="spacing.5"
                  borderRadius="medium"
                  minHeight="160px"
                >
                  <OfferPriceCardContent
                    offers={
                      product.code === SOUNDBOX.code ? WD_10_OFFER_CARDS_STRUCT : OFFER_CARDS_STRUCT
                    }
                    pricing={pricingMap?.[pricingType]}
                    showHeaders={false}
                    rentalDiscountPeriod={product.rentalDiscountPeriod}
                  />
                </Box>
              ) : null}
              <Box marginBottom="spacing.5">
                <List variant="ordered">
                  {rows
                    .filter(({ tncType }) => types.includes(tncType))
                    .map(({ text, subpoints }) => (
                      <ListItem key={text}>
                        {text}
                        {subpoints ? (
                          <List variant="unordered">
                            {subpoints.map((subpoint, index) => (
                              <ListItem key={index}>{subpoint}</ListItem>
                            ))}
                          </List>
                        ) : null}
                      </ListItem>
                    ))}
                </List>
              </Box>
              {isShowPricing ? (
                <Box marginTop="auto">
                  <AddToCartButton
                    productCode={product.code as string}
                    plan={pricingType}
                    btnVariant="secondary"
                    icon={ArrowRightIcon}
                    iconPosition="right"
                    size="medium"
                    btnText={`Proceed with ${pricingType} pricing`}
                    openCartOnUpdate
                  />
                </Box>
              ) : null}
            </Box>
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default TermsAndConditions;

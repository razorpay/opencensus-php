import React, { useMemo } from 'react';
import {
  Box,
  Divider,
  Heading,
  Title,
  List,
  ListItem,
  ArrowRightIcon,
} from '@razorpay/blade/components';

import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import OfferPriceCardContent from 'merchant/views/POS/ProductDescription/ProductPriceCards/OfferPriceCardContent';
import { TERMS_AND_CONDITIONS } from 'merchant/views/POS/constants';
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
}: TermsAndConditionsProps): JSX.Element => {
  const pricingMap = useMemo(() => {
    return isShowPricing
      ? (product?.pricing || []).reduce((acc, curr) => {
          acc[curr.type] = curr;
          return acc;
        }, {})
      : {};
  }, [product, isShowPricing]);

  return (
    <Box width="100%" maxWidth="1200px" marginBottom="spacing.8">
      <Box marginBottom="spacing.5">
        <Title size="medium" textAlign="center">
          {title}
        </Title>
      </Box>
      <Box
        display={{ base: 'block', m: 'flex' }}
        backgroundColor="surface.background.level2.lowContrast"
        padding="spacing.5"
        borderRadius="medium"
      >
        {TERMS_AND_CONDITIONS.map(({ criteria, pricingType, rows }, index) => (
          <Box
            key={criteria}
            width={{ base: '100%', m: '50%' }}
            marginBottom="spacing.5"
            display="flex"
            flexDirection="column"
          >
            <Heading marginBottom="spacing.4" marginLeft="spacing.8">
              {criteria}
            </Heading>

            <Divider marginBottom="spacing.4" />
            <Box
              borderRightWidth={{
                base: 'none',
                m: index !== TERMS_AND_CONDITIONS.length - 1 ? 'thin' : 'none',
              }}
              borderRightColor="brand.gray.400.lowContrast"
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
                  backgroundColor="surface.background.level3.lowContrast"
                  padding="spacing.5"
                  borderRadius="medium"
                  minHeight="160px"
                >
                  <OfferPriceCardContent pricing={pricingMap?.[pricingType]} showHeaders={false} />
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

import React, { useContext } from 'react';
import { Amount, Box, CheckIcon, CloseIcon, Heading, Text } from '@razorpay/blade/components';

import { ProductListFeatureIcon } from 'merchant/views/POS/Catalog/ProductFeatureTable/styles';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';
import { Feature, ProductFeaturesColumn, ProductTableProduct } from 'merchant/views/POS/types';

type CustomRow = {
  feature: Feature;
  code: string;
};

type FeatureRows = {
  featureColumns: ProductFeaturesColumn[];
  isColumn: boolean;
  product?: ProductTableProduct;
};

const BOX_SIZE_MAP = {
  small: 60,
  medium: 80,
  large: 90,
  xlarge: 120,
};

const CustomRow = ({ feature, boxSize, code }): JSX.Element | null => {
  const { state } = useContext(PosDeviceStoreContext);
  const { productDescriptions } = state;
  switch (feature.name) {
    case 'Pricing Plan': {
      const product = getProductFromProductDescriptions({ code, productDescriptions });
      if (!product?.pricing) return null;

      const pricings = getPricingByProduct({ productDescription: product });
      return (
        <Box paddingX="spacing.5" height={`${BOX_SIZE_MAP[boxSize ?? 'medium']}px`}>
          <Heading weight="regular" marginX="spacing.2">
            Subscription Pricing:
          </Heading>
          <Heading weight="regular">
            <Amount
              value={pricings.monthly}
              suffix="none"
              size="heading-small"
              isAffixSubtle={false}
            />
            /month +{' '}
            <Amount
              value={pricings.setupFee}
              suffix="none"
              size="heading-small"
              isAffixSubtle={false}
            />{' '}
            setup fee
          </Heading>
          <Heading weight="regular" marginX="spacing.2">
            Lifetime Pricing:{' '}
            <Amount
              value={pricings.lifetime}
              suffix="none"
              size="heading-small"
              isAffixSubtle={false}
            />
          </Heading>
        </Box>
      );
    }
    default:
      return null;
  }
};

const ProductFeatureRows = ({ featureColumns, isColumn, product }: FeatureRows): JSX.Element => (
  <Box width="100%">
    {featureColumns.map(({ key, name, icon, boxSize }) => {
      if (isColumn && !product)
        return (
          <Box
            key={`${key}-${name}`}
            display="flex"
            paddingY="spacing.3"
            paddingX="spacing.5"
            height={`${BOX_SIZE_MAP[boxSize ?? 'medium']}px`}
          >
            <ProductListFeatureIcon src={icon} alt="feature icon" />
            <Text size="large" marginLeft="spacing.3">
              {name}
            </Text>
          </Box>
        );
      else {
        const feature = product?.features?.[key];

        return feature?.isCustomComponent ? (
          <CustomRow
            key={`${key}-${name}`}
            feature={feature}
            code={product?.code}
            boxSize={boxSize}
          />
        ) : (
          <Box
            key={`${key}-${name}`}
            display="flex"
            paddingY="spacing.3"
            paddingX="spacing.5"
            height={`${BOX_SIZE_MAP[boxSize ?? 'medium']}px`}
          >
            <Box>
              {feature?.isAvailable ? (
                <CheckIcon size="large" color="currentColor" />
              ) : (
                <CloseIcon size="large" color="surface.text.muted.lowContrast" />
              )}
            </Box>
            <Text
              size="large"
              marginLeft="spacing.3"
              color={
                feature?.isAvailable
                  ? 'surface.text.normal.lowContrast'
                  : 'surface.text.muted.lowContrast'
              }
            >
              {feature?.name}
            </Text>
          </Box>
        );
      }
    })}
  </Box>
);

export default ProductFeatureRows;

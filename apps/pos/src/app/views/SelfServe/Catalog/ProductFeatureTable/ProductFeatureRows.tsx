import React, { useContext } from 'react';
import { Amount, Box, CheckIcon, CloseIcon, Text } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { ProductListFeatureIcon } from 'apps/pos/src/app/views/SelfServe/Catalog/ProductFeatureTable/styles';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getPricingByProduct,
  getProductFromProductDescriptions,
  fetchProductOffers,
  getAvailablePricingPlans,
} from 'apps/pos/src/app/views/SelfServe/helpers';
import {
  Feature,
  ProductFeaturesColumn,
  ProductTableProduct,
} from 'apps/pos/src/app/views/SelfServe/types';

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
  const { abExperiments } = useSplitzService();
  const { isEnabled: isOfferEnabled } = fetchProductOffers({ abExperiments });
  const { productDescriptions } = state;

  switch (feature.name) {
    case 'Pricing Plan': {
      const product = getProductFromProductDescriptions({ code, productDescriptions });
      if (!product?.pricing) return null;
      const isPartnerPricing = product?.isPartnerPricing && !isOfferEnabled;
      const pricings = getPricingByProduct({ productDescription: product });
      const { hasMonthlyPlan, hasLifetimePlan } = getAvailablePricingPlans(product);
      const hasOnlyLifeTimePlan = hasLifetimePlan && !hasMonthlyPlan;
      const amountTextIntent = isPartnerPricing ? 'feedback.text.notice.intense' : undefined;
      return (
        <Box paddingX="spacing.5" height={`${BOX_SIZE_MAP[boxSize ?? 'medium']}px`}>
          <Text weight="regular" marginX="spacing.2" size="large">
            {hasOnlyLifeTimePlan ? 'Subscription Pricing: NA' : 'Subscription Pricing:'}
          </Text>
          {!hasOnlyLifeTimePlan ? (
            <Text
              weight="regular"
              color={isPartnerPricing ? 'feedback.text.notice.intense' : 'surface.text.gray.normal'}
              size="large"
            >
              <Amount
                value={pricings.monthly}
                suffix="none"
                isAffixSubtle={false}
                color={amountTextIntent}
                type="body"
                size="large"
              />
              /month +{' '}
              <Amount
                value={pricings.setupFee}
                suffix="none"
                isAffixSubtle={false}
                color={amountTextIntent}
                type="body"
                size="large"
              />{' '}
              setup fee
            </Text>
          ) : null}
          <Text weight="regular" marginX="spacing.2" size="large">
            Lifetime Pricing:{' '}
            <Amount
              value={pricings.lifetime}
              suffix="none"
              isAffixSubtle={false}
              color={amountTextIntent}
              type="body"
              size="large"
            />
          </Text>
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
                <CloseIcon size="large" color="interactive.icon.gray.muted" />
              )}
            </Box>
            <Text
              size="large"
              marginLeft="spacing.3"
              color={feature?.isAvailable ? 'surface.text.gray.normal' : 'surface.text.gray.muted'}
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

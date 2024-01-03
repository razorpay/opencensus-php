import React from 'react';
import {
  Box,
  Collapsible,
  CollapsibleBody,
  Divider,
  Heading,
  Title,
} from '@razorpay/blade/components';

import {
  ProductImage,
  ProductItemContainer,
} from 'merchant/views/POS/Catalog/ProductFeatureTable/styles';
import { ProductFeaturesColumn, ProductTableProduct } from 'merchant/views/POS/types';

import ProductFeatureRows from './ProductFeatureRows';

type ProductListItem = {
  schema: ProductFeaturesColumn[][];
  isColumn: boolean;
  product?: ProductTableProduct;
  isExpanded: boolean;
  collapsibleIndex: number;
  isElevated?: boolean;
};

const ProductTableItem = ({
  schema,
  isColumn,
  product,
  isExpanded,
  collapsibleIndex,
  isElevated,
}: ProductListItem): JSX.Element => {
  const nonCollapsibleFeatures = schema.slice(0, collapsibleIndex);
  const collapsibleFeatures = schema.slice(collapsibleIndex);

  return (
    <ProductItemContainer>
      {isColumn ? (
        <Box minHeight="230px" paddingTop="spacing.5">
          <Title size="large">Choose the best devices for your business</Title>
        </Box>
      ) : null}
      <Box
        backgroundColor={
          isElevated
            ? 'surface.background.level2.lowContrast'
            : 'surface.background.level1.lowContrast'
        }
        borderTopRightRadius={isColumn ? 'none' : 'large'}
        borderBottomRightRadius={isColumn ? 'none' : 'large'}
        borderTopLeftRadius="large"
        borderBottomLeftRadius="large"
        minWidth="220px"
      >
        {isColumn ? null : (
          <Box
            height="230px"
            overflow="hidden"
            display="flex"
            alignItems="center"
            justifyContent="center"
          >
            <ProductImage src={product?.image} alt="Product image" />
          </Box>
        )}

        <Box height="50px">
          {isColumn ? (
            <>
              <Box paddingY="spacing.4" paddingX="spacing.5">
                <Heading>Features</Heading>
              </Box>
              <Divider marginBottom="spacing.3" />
            </>
          ) : (
            <Heading size="large" textAlign="center">
              {product?.productTitle}
            </Heading>
          )}
        </Box>
        {nonCollapsibleFeatures.map((rows, index) => (
          <>
            <ProductFeatureRows
              key={`${index}-feature-row`}
              featureColumns={rows}
              product={product}
              isColumn={isColumn}
            />
            {index !== collapsibleIndex - 1 || isExpanded ? (
              <Divider
                key={`${index}-divider`}
                marginTop="spacing.3"
                testID="non-collapsible-item-divider"
                marginX="spacing.5"
              />
            ) : null}
          </>
        ))}
        {collapsibleFeatures.length > 0 ? (
          <Collapsible isExpanded={isExpanded}>
            <CollapsibleBody>
              {collapsibleFeatures.map((rows, index) => (
                <>
                  <ProductFeatureRows
                    featureColumns={rows}
                    product={product}
                    isColumn={isColumn}
                    key={`${index}-feature-row`}
                  />
                  {index !== collapsibleFeatures.length - 1 ? (
                    <Divider marginTop="spacing.3" marginX="spacing.5" key={`${index}-divider`} />
                  ) : null}
                </>
              ))}
            </CollapsibleBody>
          </Collapsible>
        ) : null}
      </Box>
    </ProductItemContainer>
  );
};

export default ProductTableItem;

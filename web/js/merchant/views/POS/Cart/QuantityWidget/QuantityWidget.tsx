import React from 'react';
import { Box, IconButton, MinusIcon, PlusIcon, Text } from '@razorpay/blade/components';

import { CartItem, ProductUpdateTypes, Product } from 'merchant/views/POS/types';

type QuantityWidget = {
  size?: 'small' | 'large';
  cartItem: CartItem;
  isMinZero?: boolean;
  onProductQuantityUpdate: (
    updateType: ProductUpdateTypes,
    product: Product,
    productTitle: string,
  ) => void;
  productTitle?: string;
};

const QuantityWidget = ({
  cartItem,
  productTitle = '',
  size,
  isMinZero,
  onProductQuantityUpdate,
}: QuantityWidget): JSX.Element => {
  const { code, plan, quantity } = cartItem;
  const handleProductUpdate = ({ updateType }: { updateType: ProductUpdateTypes }) => {
    const product = {
      productCode: code,
      plan,
    };
    onProductQuantityUpdate(updateType, product, productTitle);
  };
  return (
    <Box
      backgroundColor="surface.background.level1.lowContrast"
      display="flex"
      justifyContent="space-between"
      alignItems="center"
      width={size === 'small' ? '100px' : '140px'}
      paddingX={size === 'small' ? 'spacing.4' : 'spacing.5'}
      paddingY={size === 'small' ? 'spacing.3' : 'spacing.4'}
      borderColor="brand.primary.500"
      borderWidth={size === 'small' ? 'none' : 'thin'}
      borderRadius="small"
    >
      <IconButton
        icon={MinusIcon}
        size="large"
        contrast="low"
        accessibilityLabel="reduce cart quantity"
        onClick={() => handleProductUpdate({ updateType: 'reduce' })}
        isDisabled={quantity < (isMinZero ? 1 : 2)}
      />
      <Text
        marginX="spacing.4"
        testID="quantity-value"
        size={size === 'small' ? 'medium' : 'large'}
        weight={size === 'small' ? 'regular' : 'bold'}
      >
        {quantity ?? 0}
      </Text>
      <IconButton
        icon={PlusIcon}
        size="large"
        contrast="low"
        accessibilityLabel="increase cart quantity"
        onClick={() => handleProductUpdate({ updateType: 'add' })}
      />
    </Box>
  );
};

export default QuantityWidget;

import React, { useContext } from 'react';
import { Amount, Box, IconButton, Text, TrashIcon } from '@razorpay/blade/components';

import QuantityWidget from 'merchant/views/POS/Cart/QuantityWidget';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getCartItemTotal, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';
import {
  CartItem as CartItemType,
  ProductPlans,
  Product as ProductType,
  ProductUpdateTypes,
} from 'merchant/views/POS/types';

import OfferCartItemContent from './OfferCartItemContent';
import { PricingCard } from './styles';

type CartItemProps = {
  cartItem: CartItemType;
  onPricingUpdate: (selectedPlan: ProductPlans, Product: ProductType, productTitle: string) => void;
  onProductRemove: (product: ProductType, productTitle: string) => void;
  onProductQuantityUpdate: (
    updateType: ProductUpdateTypes,
    updateproduct: ProductType,
    productTitle: string,
  ) => void;
};

const CartItem = ({
  cartItem,
  onPricingUpdate,
  onProductRemove,
  onProductQuantityUpdate,
}: CartItemProps): JSX.Element | null => {
  const { state } = useContext(PosDeviceStoreContext);
  const { productDescriptions } = state;
  const { code, quantity, plan } = cartItem;
  const product = getProductFromProductDescriptions({ code, productDescriptions });

  if (!product?.pricing) {
    return null;
  }

  const { productTitle, pricing, cartImage } = product;
  const total = getCartItemTotal({ pricing, quantity, selectedPlan: plan });

  const handlePricingCardClick = (selectedPlan: ProductPlans) => {
    if (selectedPlan === plan) return;
    const product = {
      productCode: code,
      plan,
    };
    onPricingUpdate(selectedPlan as ProductPlans, product, productTitle);
  };

  const handleOnRemoveClick = () => {
    const product = {
      productCode: code,
      plan,
    };
    onProductRemove(product, productTitle);
  };

  return (
    <Box
      key={code}
      padding="spacing.5"
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      testID={`${code}-${plan}-cart-item`}
      marginBottom="spacing.5"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" alignItems="center">
          <Box
            backgroundImage={`url("${cartImage}")`}
            backgroundRepeat="no-repeat"
            backgroundSize="cover"
            height={{ base: '65px', l: '70px' }}
            width={{ base: '65px', l: '70px' }}
            backgroundPosition="center center"
            backgroundColor="surface.background.gray.moderate"
            borderRadius="large"
            marginRight="spacing.4"
          />
          <Text size="large" color="surface.text.gray.subtle">
            {productTitle}
          </Text>
        </Box>
        <Box paddingTop="spacing.5">
          <IconButton
            icon={TrashIcon}
            size="large"
            onClick={handleOnRemoveClick}
            accessibilityLabel="product delete icon"
          />
        </Box>
      </Box>
      <Box display={{ base: 'block', l: 'flex' }} gap="spacing.3" marginTop="spacing.5">
        {pricing.map(({ name, breakups, type }, index) => (
          <PricingCard
            key={name}
            aria-selected={type === plan}
            isSelected={type === plan}
            data-testid={`${type}-card`}
            onClick={() => handlePricingCardClick(type as ProductPlans)}
          >
            <Text marginBottom="spacing.2" weight="semibold" color="surface.text.gray.muted">
              {name}
            </Text>
            {product?.offer ? (
              <OfferCartItemContent pricing={pricing[index]} />
            ) : (
              <Box>
                {breakups.map(({ key, value, isExtraFee, suffix }) => (
                  <React.Fragment key={key}>
                    {isExtraFee ? ' ( ' : null}
                    <Amount
                      value={value}
                      suffix="none"
                      marginLeft="-2px"
                      isAffixSubtle={false}
                    />{' '}
                    {suffix} {isExtraFee ? ')' : null}
                  </React.Fragment>
                ))}
              </Box>
            )}
          </PricingCard>
        ))}
      </Box>
      <Box display="flex" justifyContent="space-between" alignItems="center">
        <Box>
          <Text marginBottom="spacing.2">Quantity</Text>
          <QuantityWidget
            cartItem={cartItem}
            onProductQuantityUpdate={onProductQuantityUpdate}
            productTitle={productTitle}
            size="small"
          />
        </Box>
        <Box>
          <Text>Device charge</Text>
          <Amount
            value={total.value ?? 0}
            suffix="none"
            isAffixSubtle={false}
            type="body"
            size="large"
            weight="semibold"
          />
        </Box>
      </Box>
    </Box>
  );
};

export default CartItem;

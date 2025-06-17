import React, { useContext } from 'react';
import { Amount, Badge, Box, Divider, InfoIcon, Text, TrashIcon } from '@razorpay/blade/components';

import QuantityWidget from 'apps/pos/src/app/views/SelfServe/Cart/QuantityWidget';
import { SOUNDBOX, STANDEEANDSTICKER } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getCartItemTotal,
  getOrderQuantityError,
  getProductFromProductDescriptions,
} from 'apps/pos/src/app/views/SelfServe/helpers';
import {
  CartItem as CartItemType,
  ProductPlans,
  Product as ProductType,
  ProductUpdateTypes,
} from 'apps/pos/src/app/views/SelfServe/types';

import CartHeader from './CartHeader';
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

  const { productTitle, pricing, cartImage, linkedItems, offer, isPartnerPricing, maxOrder } =
    product;
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

  const getCartHeaderOfferStrip = () => {
    switch (code) {
      case STANDEEANDSTICKER.code:
        return {
          text: 'Offer Applied',
          isPartnerPricing,
        };
      case SOUNDBOX.code:
        if (!!offer) {
          return {
            text: 'Combo Offer',
            isPartnerPricing,
          };
        }
        return null;
      default:
        return null;
    }
  };

  const orderQuantityError = getOrderQuantityError({
    maxQuantity: maxOrder,
    currQuantity: cartItem.quantity,
    errMsg: product?.maxQuantityErrMsg?.(maxOrder) ?? '',
  });

  return (
    <Box
      key={code}
      padding="spacing.5"
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      testID={`${code}-${plan}-cart-item`}
      marginBottom="spacing.5"
    >
      <CartHeader
        productTitle={productTitle}
        icon={TrashIcon}
        iconClickHandler={handleOnRemoveClick}
        cartImage={cartImage}
        offerStrip={getCartHeaderOfferStrip()}
      />
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
              <OfferCartItemContent
                rentalDiscountPeriod={product.rentalDiscountPeriod}
                pricing={pricing[index]}
              />
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
      <Box display="flex" justifyContent="space-between" alignItems="center" gap="spacing.3">
        <Box maxWidth="65%">
          <Text marginBottom="spacing.2">Quantity</Text>
          <QuantityWidget
            cartItem={cartItem}
            onProductQuantityUpdate={onProductQuantityUpdate}
            productTitle={productTitle}
            size="small"
            maxOrder={maxOrder}
          />
          {orderQuantityError ? (
            <Box
              marginTop="spacing.2"
              display="flex"
              justifyContent="center"
              alignItems="center"
              gap="spacing.3"
            >
              <InfoIcon size="small" color="feedback.icon.information.intense" />
              <Text size="small" variant="caption" color="feedback.text.information.intense">
                {orderQuantityError}
              </Text>
            </Box>
          ) : null}
        </Box>
        <Box>
          <Text>Device charge</Text>
          <Box
            display="flex"
            alignItems="center"
            gap="spacing.3"
            marginLeft="auto"
            width="fit-content"
          >
            {plan === 'free' ? (
              <Badge color="primary" emphasis="intense">
                Free
              </Badge>
            ) : null}
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
      {linkedItems?.length ? (
        <Box paddingTop="spacing.4">
          <Divider />
          {linkedItems.map((item, idx) => (
            <Box marginTop="spacing.3" marginBottom="spacing.3" key={item.title}>
              <CartHeader
                offerStrip={{ text: item.offerLabel, isPartnerPricing }}
                productTitle={item.title}
                cartImage={item.image}
              />
              <Box
                display="flex"
                justifyContent="space-between"
                alignItems="center"
                marginTop="spacing.3"
                marginBottom="spacing.3"
              >
                <Text>Quantity: {quantity * item.quantity}</Text>
                <Box
                  display="flex"
                  justifyContent="space-between"
                  gap="spacing.3"
                  alignItems="center"
                >
                  <Badge color="primary" emphasis="intense">
                    Free
                  </Badge>
                  <Amount
                    value={0}
                    suffix="none"
                    isAffixSubtle={false}
                    type="body"
                    size="large"
                    weight="semibold"
                  />
                </Box>
              </Box>
              {idx !== linkedItems.length - 1 ? <Divider /> : null}
            </Box>
          ))}
        </Box>
      ) : null}
    </Box>
  );
};

export default CartItem;

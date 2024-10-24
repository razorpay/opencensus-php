import React, { useContext } from 'react';
import { Divider } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { ACTIONS, UPDATE_CART_ACTIONS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import {
  getProductFromCart,
  getProductFromProductDescriptions,
  updateCart,
} from 'merchant/views/POS/helpers';
import { ProductPlans, Product as ProductType, ProductUpdateTypes } from 'merchant/views/POS/types';

import CartItem from './CartItem';

type CartProps = {
  onCartUpdate?: (cart) => void;
  isCartModal?: boolean;
  isOrderDetails?: boolean;
};

const Cart = ({ onCartUpdate, isCartModal, isOrderDetails }: CartProps): JSX.Element => {
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const { cartItems, productDescriptions } = state;

  const onPricingUpdate = (
    selectedPlan: ProductPlans,
    product: ProductType,
    productTitle: string,
  ) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: selectedPlan === 'lifetime' ? 'Lifetime plan' : 'Monthly Subscription',
      whatsAppUpdates: 'No',
      section: isCartModal ? 'Cart' : 'Pre-checkout',
      subSection: productTitle,
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: isCartModal ? 'Cart' : 'Pre-checkout - Edit Order',
      previousSelection: isCartModal
        ? selectedPlan === 'lifetime'
          ? 'Monthly Subscription'
          : 'Lifetime plan'
        : undefined,
    });
    const productDescription = getProductFromProductDescriptions({
      code: product.productCode,
      productDescriptions,
    });
    const newCartItems = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.TOGGLE_PLAN,
      product,
      maxOrder: productDescription?.maxOrder,
    });

    onCartUpdate?.(newCartItems);
    dispatch({
      type: ACTIONS.UPDATE_CART,
      payload: {
        cartItems: newCartItems,
      },
    });
  };

  const onProductRemove = (product: ProductType, productTitle: string) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
      type: 'Delete Icon',
      noOfItems: getProductFromCart({ cart: cartItems, product })?.quantity,
      section: isCartModal ? 'Cart' : 'Pre-checkout',
      subSection: productTitle,
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: isCartModal ? 'Cart' : 'Pre-checkout - Edit Order',
    });

    const newCartItems = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.REMOVE_ITEM,
      product,
    });
    onCartUpdate?.(newCartItems);
    dispatch({
      type: ACTIONS.UPDATE_CART,
      payload: {
        cartItems: newCartItems,
      },
    });
  };

  const onProductQuantityUpdate = (
    updateType: ProductUpdateTypes,
    product: ProductType,
    productTitle: string,
  ) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
      type: updateType === 'add' ? 'Add' : 'Remove',
      noOfItems: getProductFromCart({ cart: cartItems, product })?.quantity,
      section: isCartModal ? 'Cart' : 'Pre-checkout',
      subSection: productTitle,
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: isCartModal ? 'Cart' : 'Pre-checkout - Edit Order',
    });
    const productDescription = getProductFromProductDescriptions({
      code: product.productCode,
      productDescriptions,
    });
    const newCartItems = updateCart({
      cart: cartItems,
      type:
        updateType === 'add'
          ? UPDATE_CART_ACTIONS.INCREASE_QUANTITY
          : UPDATE_CART_ACTIONS.DECREASE_QUANTITY,
      product,
      maxOrder: productDescription?.maxOrder,
    });
    onCartUpdate?.(newCartItems);
    dispatch({
      type: ACTIONS.UPDATE_CART,
      payload: {
        cartItems: newCartItems,
      },
    });
  };

  return (
    <>
      {cartItems.map((cartItem, index) => (
        <>
          <CartItem
            key={`${cartItem.code}-${cartItem.plan}`}
            cartItem={cartItem}
            onPricingUpdate={onPricingUpdate}
            onProductRemove={onProductRemove}
            onProductQuantityUpdate={onProductQuantityUpdate}
          />
          {index !== cartItems.length - 1 && isOrderDetails ? (
            <Divider marginX="spacing.4" />
          ) : null}
        </>
      ))}
    </>
  );
};

export default Cart;

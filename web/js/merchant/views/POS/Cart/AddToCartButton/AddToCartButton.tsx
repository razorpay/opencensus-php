import React, { useContext } from 'react';
import { Button, ShoppingCartIcon } from '@razorpay/blade/components';

import { ACTIONS, UPDATE_CART_ACTIONS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { updateCart } from 'merchant/views/POS/helpers';
import { ProductPlans } from 'merchant/views/POS/types';

type AddToCartButtonProps = {
  productCode: string;
  plan: ProductPlans;
  openCartOnUpdate?: boolean;
  size?: 'small' | 'medium' | 'large';
  onCtaClick?: () => void;
};

const AddToCartButton = ({
  productCode,
  plan,
  openCartOnUpdate,
  onCtaClick,
  size,
}: AddToCartButtonProps): JSX.Element => {
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const { cartItems } = state;

  const handleAddToCart = (e) => {
    e.stopPropagation();
    if (!productCode) return;
    const newCartItems = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.ADD_TO_CART,
      product: {
        productCode,
        plan,
      },
    });
    dispatch({
      type: ACTIONS.UPDATE_CART,
      payload: {
        cartItems: newCartItems,
      },
    });

    if (openCartOnUpdate) {
      onCtaClick?.();
      dispatch({
        type: ACTIONS.OPEN_CART,
        payload: {
          cartItems: newCartItems,
        },
      });
    }
  };

  return (
    <Button size={size ?? 'large'} type="button" icon={ShoppingCartIcon} onClick={handleAddToCart}>
      Add to cart
    </Button>
  );
};

export default AddToCartButton;

import React, { useContext } from 'react';
import { Button, IconComponent, ShoppingCartIcon } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';

import {
  ACTIONS,
  SOUNDBOX,
  STANDEE_SOUNDBOX_CART_ERR,
  STANDEEANDSTICKER,
  UPDATE_CART_ACTIONS,
} from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getProductFromProductDescriptions,
  isItemPresentInCart,
  updateCart,
} from 'apps/pos/src/app/views/SelfServe/helpers';
import { ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';

type AddToCartButtonProps = {
  productCode: string;
  plan: ProductPlans;
  openCartOnUpdate?: boolean;
  size?: 'small' | 'medium' | 'large';
  btnVariant?: 'primary' | 'secondary';
  btnText?: string;
  icon?: IconComponent;
  iconPosition?: 'left' | 'right';
  onCtaClick?: () => void;
};

const AddToCartButton = ({
  productCode,
  plan,
  openCartOnUpdate,
  onCtaClick,
  btnVariant = 'primary',
  icon,
  iconPosition = 'left',
  size,
  btnText = 'Add to cart',
}: AddToCartButtonProps): JSX.Element => {
  const showNotification = useStore((state) => state.showNotification);
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const { cartItems, productDescriptions } = state;
  const product = getProductFromProductDescriptions({ code: productCode, productDescriptions });

  const handleAddToCart = (e) => {
    e.stopPropagation();
    if (!productCode) return;
    if (productCode === STANDEEANDSTICKER.code && isItemPresentInCart(SOUNDBOX.code, cartItems)) {
      showNotification({
        message: STANDEE_SOUNDBOX_CART_ERR,
        type: 'error',
      });
      return;
    }
    if (productCode === SOUNDBOX.code && isItemPresentInCart(STANDEEANDSTICKER.code, cartItems)) {
      showNotification({
        message: STANDEE_SOUNDBOX_CART_ERR,
        type: 'error',
      });
      return;
    }
    const newCartItems = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.ADD_TO_CART,
      product: {
        productCode,
        plan,
      },
      maxOrder: product?.maxOrder,
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
    <Button
      variant={btnVariant}
      size={size ?? 'large'}
      type="button"
      icon={icon || ShoppingCartIcon}
      iconPosition={iconPosition}
      onClick={handleAddToCart}
    >
      {btnText}
    </Button>
  );
};

export default AddToCartButton;

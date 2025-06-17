import React, { useContext, useEffect, useRef, useMemo } from 'react';
import {
  Box,
  ShoppingCartIcon,
  Divider,
  Counter,
  IconButton,
  ArrowLeftIcon,
  Text,
  Drawer,
} from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { useNavigate } from 'react-router-dom';

import {
  ACTIONS,
  MAX_ORDERABLE_ITEMS,
  PAGE_READ_SUCCESS_MS,
} from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import { useBladeBreakpoints, useExecuteAfterDelay } from 'apps/pos/src/app/views/SelfServe/hooks';
import { CartItem } from 'apps/pos/src/app/views/SelfServe/types';

import Cart from './Cart';
import CartEmpty from './CartEmpty';
import CartFooter from './CartFooter';
import { CartButtonContainer } from './styles';

type CartPanelProps = {
  isHidden: boolean;
};

interface ModalContent {
  handleCartClose: () => void;
  isCartItemsAvailable: boolean;
  cartItems: CartItem[];
  isMaxReached: boolean;
  handleShopMoreClick: () => void;
}

const ModalContent = ({
  handleCartClose,
  isCartItemsAvailable,
  cartItems,
  isMaxReached,
  handleShopMoreClick,
}: ModalContent) => {
  const handlePageReadSuccess = () => {
    // Trigger pageReadSuccess event for page viewed after 15 seconds
    analytics.track_EXPERIMENTAL(SignUpEvents.pageReadSuccess, {
      pageType: 'Cart',
    });
  };

  useExecuteAfterDelay({ callback: handlePageReadSuccess, delay: PAGE_READ_SUCCESS_MS });

  useEffect(() => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType: 'Cart',
      orderId: '',
    });
  }, []);

  return (
    <Box
      display="flex"
      flexDirection="column"
      backgroundColor="surface.background.gray.intense"
      minWidth="300px"
      zIndex={10}
      flex={1}
      maxHeight="100vh"
    >
      <Box paddingX="spacing.5" paddingY="spacing.6" display="flex" alignItems="center">
        <IconButton
          icon={ArrowLeftIcon}
          onClick={handleCartClose}
          size="large"
          accessibilityLabel="cart close button"
        />
        <Text marginX="spacing.4" size="large">
          Your Cart
        </Text>
        <Text weight="regular" size="large" color="surface.text.gray.muted">
          {isCartItemsAvailable
            ? `${cartItems.length} ${cartItems.length > 1 ? 'Items' : 'Item'}`
            : null}
        </Text>
      </Box>

      <Box flexGrow={0}>
        <Divider />
      </Box>
      {isCartItemsAvailable ? (
        <Box
          display="flex"
          flexDirection="column"
          backgroundColor="surface.background.gray.subtle"
          position="relative"
          width="100%"
          justifyContent="space-between"
          flex={1}
          minHeight="spacing.0"
        >
          <Box flex={1} padding="spacing.5" overflowY="scroll">
            <Cart isCartModal={true} />
          </Box>
          <CartFooter isMaxReached={isMaxReached} />
        </Box>
      ) : (
        <CartEmpty onShopMoreClick={handleShopMoreClick} />
      )}
    </Box>
  );
};

const CartPanel = ({ isHidden = false }: CartPanelProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const navigate = useNavigate();
  const { isCartOpen, cartItems } = state;

  const totalCartQuantity = useMemo(
    () => cartItems.reduce((acc, { quantity }) => acc + quantity, 0),
    [cartItems],
  );

  const handleCartOpen = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
      type: 'Cart Icon',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
      section: 'POS Catalog',
      subSection: 'POS Catalog',
    });
    dispatch({
      type: ACTIONS.OPEN_CART,
    });
  };

  const handleCartClose = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
      type: 'Back Icon',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'Cart',
      section: 'Cart',
      subSection: 'Cart',
    });

    if (isCartOpen) {
      dispatch({
        type: ACTIONS.CLOSE_CART,
      });
    }
  };

  const handleShopMoreClick = () => {
    handleCartClose();
    navigate('/pos/catalog');
  };

  const isCartItemsAvailable = cartItems.length > 0;
  const isMaxReached = totalCartQuantity > MAX_ORDERABLE_ITEMS;

  return (
    <>
      <CartButtonContainer onClick={handleCartOpen} data-testid="cart-button" isMobile={isMobile}>
        {!isHidden ? (
          <Box display="flex" alignItems="center" paddingX="spacing.4" position="relative">
            <ShoppingCartIcon size="xlarge" color="interactive.icon.gray.normal" />
            {isCartItemsAvailable ? (
              <Box position="absolute" bottom="spacing.0" left="30px">
                <Counter
                  emphasis="intense"
                  marginRight="spacing.3"
                  marginTop="spacing.2"
                  size="medium"
                  value={cartItems.length}
                  color="primary"
                  testID="cart-panel-counter"
                />
              </Box>
            ) : null}
          </Box>
        ) : null}
      </CartButtonContainer>
      <Drawer
        isOpen={isCartOpen}
        onDismiss={handleCartClose}
        accessibilityLabel="SliderModal"
        // TODO: Remove this once the issue is fixed
        zIndex={1000}
      >
        <ModalContent
          handleCartClose={handleCartClose}
          isCartItemsAvailable={isCartItemsAvailable}
          cartItems={cartItems}
          handleShopMoreClick={handleShopMoreClick}
          isMaxReached={isMaxReached}
        />
      </Drawer>
    </>
  );
};

export default CartPanel;

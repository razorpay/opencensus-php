import React, { useContext, useEffect, useRef, useMemo } from 'react';
import {
  Box,
  ShoppingCartIcon,
  Divider,
  Counter,
  IconButton,
  ArrowLeftIcon,
  Text,
} from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import Modal from 'react-modal';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';

import useOnClickOutside from 'common/hooks/useOnClickOutside';
import { toggleHelpWidget } from 'merchant/reducers/session';
import { ACTIONS, MAX_ORDERABLE_ITEMS, PAGE_READ_SUCCESS_MS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { useBladeBreakpoints, useExecuteAfterDelay } from 'merchant/views/POS/hooks';
import { CartItem } from 'merchant/views/POS/types';

import Cart from './Cart';
import CartEmpty from './CartEmpty';
import CartFooter from './CartFooter';
import { CartBackdropStyled, CartButtonContainer } from './styles';

type CartPanelProps = {
  toggleHelpWidget: ({ showWidget }: { showWidget: boolean }) => void;
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
}) => {
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
      minHeight="100vh"
      backgroundColor="surface.background.gray.intense"
      minWidth="300px"
      zIndex={10}
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
      <Divider />
      {isCartItemsAvailable ? (
        <Box
          backgroundColor="surface.background.gray.subtle"
          height="90vh"
          position="relative"
          width="100%"
        >
          <Box padding="spacing.5" maxHeight="80vh" overflowY="scroll">
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

const CartPanel = ({ toggleHelpWidget, isHidden = false }: CartPanelProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const navigate = useNavigate();
  const cartOverlayRef = useRef();
  const { isCartOpen, cartItems } = state;

  const totalCartQuantity = useMemo(
    () => cartItems.reduce((acc, { quantity }) => acc + quantity, 0),
    [cartItems],
  );

  const handleCartOpen = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
      type: 'Cart Icon',
      l1FunnelStage: 'Device Consideration',
      l2FunnelStage: 'POS Product Description',
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

  useOnClickOutside([cartOverlayRef], handleCartClose);

  useEffect(() => {
    toggleHelpWidget({ showWidget: !isCartOpen });

    return () => toggleHelpWidget({ showWidget: true });
  }, [toggleHelpWidget, isCartOpen]);

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
      {isCartOpen ? (
        <Modal
          isOpen={isCartOpen}
          closeTimeoutMS={300}
          className="ModalSlider__Content"
          contentLabel="SliderModal"
          ariaHideApp={false}
          overlayClassName="ModalSlider__Overlay"
          shouldCloseOnOverlayClick={true}
          overlayRef={(node) => (cartOverlayRef.current = node)}
        >
          <ModalContent
            handleCartClose={handleCartClose}
            isCartItemsAvailable={isCartItemsAvailable}
            cartItems={cartItems}
            handleShopMoreClick={handleShopMoreClick}
            isMaxReached={isMaxReached}
          />
        </Modal>
      ) : null}
      {isCartOpen ? (
        <CartBackdropStyled onClick={handleCartClose} data-testid="pos-cart-overlay" />
      ) : null}
    </>
  );
};

export default connect(null, {
  toggleHelpWidget,
})(CartPanel);

import React, { SyntheticEvent, useContext, useEffect, useState } from 'react';
import {
  ArrowRightIcon,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
  Box,
  Button,
  Divider,
  EditIcon,
  Heading,
  Link,
  ShoppingCartIcon,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import Cart from 'merchant/views/POS/Cart/CartPanel/Cart';
import OrderCollapsible from 'merchant/views/POS/OrderSummary/OrderCollapsible';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

import OrderItem from './OrderItem';

const EmptyCartState = (): JSX.Element => {
  const navigate = useNavigate();
  return (
    <Box
      display="flex"
      flexDirection="column"
      alignItems="center"
      justifyContent="center"
      padding="spacing.5"
      minHeight="200px"
    >
      <Heading type="subtle" weight="regular" textAlign="center">
        Looks like you haven’t made your choices yet..
      </Heading>
      <Link
        icon={ArrowRightIcon}
        iconPosition="right"
        variant="button"
        onClick={() => navigate('/pos/catalog')}
      >
        Show More
      </Link>
    </Box>
  );
};

type OrderItemsProps = {
  defaultIsExpanded?: boolean;
};

const OrderItems = ({ defaultIsExpanded }: OrderItemsProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const { state } = useContext(PosDeviceStoreContext);
  const { cartItems } = state;
  const [isExpanded, setIsExapanded] = useState(!!defaultIsExpanded);
  const [isEditCartMode, setIsEditCartMode] = useState(false);

  useEffect(() => {
    // call entity update with debounce.
  }, [cartItems]);

  const handleOnCartToggle = (e?: SyntheticEvent) => {
    e?.stopPropagation?.();

    if (!isEditCartMode) {
      analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
        whatsAppUpdates: 'No',
        label: 'Edit',
        section: 'Pre-checkout - Edit Order',
        subSection: 'Pre-checkout - Edit Order',
        l1FunnelStage: 'Purchase Intention',
        l2FunnelStage: 'Pre-checkout - Edit Order',
      });
    } else {
      analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
        label: isMobile ? 'Proceed' : 'Done',
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Purchase Intention',
        l2FunnelStage: 'Pre-checkout - Edit Order',
        section: 'Pre-checkout',
        subSection: 'Order Summary',
      });
    }

    setIsEditCartMode((isEditCartMode) => !isEditCartMode);
  };

  const getExtraWidgets = (): JSX.Element | null => {
    if (cartItems.length !== 0 && isExpanded) {
      return isEditCartMode ? (
        <Link variant="button" onClick={handleOnCartToggle}>
          Done
        </Link>
      ) : (
        <Link icon={EditIcon} variant="button" onClick={handleOnCartToggle}>
          Edit
        </Link>
      );
    }
    return null;
  };

  const handleOnCollapsibleChange = (expandedState) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
      type: isExpanded ? 'Shrink Icon' : 'Expand Icon',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Pre-checkout',
      section: 'Pre-checkout',
      subSection: 'Order Summary',
      pageType: 'Pre-checkout - Add POS Details',
    });
    setIsExapanded(expandedState);
    if (!expandedState) setIsEditCartMode(false);
  };

  const isCartEmptyAndBottomSheetClosed = cartItems.length === 0 && !(isMobile && isEditCartMode);
  const isEditCartModeAndDesktop = isEditCartMode && !isMobile;

  const extraWidgets = getExtraWidgets();
  return (
    <OrderCollapsible
      icon={
        <ShoppingCartIcon
          size="large"
          color={
            isExpanded ? 'action.icon.secondary.active' : 'surface.action.icon.active.lowContrast'
          }
        />
      }
      title={
        <Box display={{ base: 'block', l: 'flex' }}>
          <Heading marginRight="spacing.3">Order Summary</Heading>
          <Heading type="subtle" weight="regular">
            {cartItems.length} {cartItems.length <= 1 ? 'Item' : 'Items'}
          </Heading>
        </Box>
      }
      headerWidgets={extraWidgets}
      testID="order-summary-container"
      onCollapsibleChange={handleOnCollapsibleChange}
      defaultIsExpanded={isExpanded}
    >
      <Box>
        {isCartEmptyAndBottomSheetClosed ? <EmptyCartState /> : null}

        {!isEditCartMode || isMobile
          ? cartItems.map((cartItem, index) => (
              <React.Fragment key={`${cartItem.code}-${cartItem.plan}`}>
                <OrderItem orderItem={cartItem} />
                {index !== cartItems.length - 1 ? <Divider marginX="spacing.4" /> : null}
              </React.Fragment>
            ))
          : null}

        {isEditCartModeAndDesktop ? <Cart isOrderDetails /> : null}

        {isMobile ? (
          <BottomSheet
            isOpen={isEditCartMode}
            snapPoints={[0.5, 0.7, 0.8]}
            onDismiss={handleOnCartToggle}
          >
            <BottomSheetHeader title="Edit Order" />
            <BottomSheetBody padding="spacing.0">
              {cartItems.length === 0 ? <EmptyCartState /> : <Cart isOrderDetails />}
            </BottomSheetBody>
            <BottomSheetFooter>
              <Button isFullWidth type="button" onClick={handleOnCartToggle} size="large">
                Proceed
              </Button>
            </BottomSheetFooter>
          </BottomSheet>
        ) : null}
      </Box>
    </OrderCollapsible>
  );
};

export default OrderItems;

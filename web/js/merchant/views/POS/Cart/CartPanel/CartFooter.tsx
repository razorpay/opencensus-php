import React, { useContext, useMemo } from 'react';
import { Alert, Amount, Box, Button, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useLocation, useNavigate } from 'react-router-dom';

import { ACTIONS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { processPrecheckoutPricing } from 'merchant/views/POS/helpers';

import { CartFooterContainer } from './styles';

type CartFooterProps = {
  isMaxReached: boolean;
};

const CartFooter = ({ isMaxReached }: CartFooterProps): JSX.Element => {
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const { pathname } = useLocation();
  const { cartItems, productDescriptions } = state;
  const navigate = useNavigate();

  const { deviceCharges } = useMemo(
    () => processPrecheckoutPricing({ cartItems, productDescriptions }),
    [cartItems, productDescriptions],
  );

  const handleOnProceedClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'Place Order',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Cart',
      section: 'Cart',
      subSection: 'Cart',
    });

    navigate('/pos/catalog/order-summary', {
      state: {
        breadcrumbsPath: pathname,
      },
    });

    dispatch({
      type: ACTIONS.CLOSE_CART,
    });
  };

  return (
    <CartFooterContainer>
      {isMaxReached ? (
        <Alert
          color="negative"
          title="This order can accommodate a maximum of 9 items"
          description="Reduce the total number of items for this order"
          marginBottom="spacing.5"
          isDismissible={false}
        />
      ) : null}
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        paddingBottom="spacing.5"
      >
        <Box>
          <Amount
            size="heading-large-bold"
            value={deviceCharges}
            suffix="none"
            isAffixSubtle={false}
          />
          <Text>Not inclusive of tax</Text>
        </Box>
        <Button
          size="large"
          isDisabled={isMaxReached}
          onClick={handleOnProceedClick}
          testID="place-order-btn"
        >
          Place Order
        </Button>
      </Box>
    </CartFooterContainer>
  );
};

export default CartFooter;

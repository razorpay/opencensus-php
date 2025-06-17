import React, { useContext, useMemo, useEffect } from 'react';
import { Alert, Amount, Box, Button, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useLocation, useNavigate } from 'react-router-dom';

import { ACTIONS } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import { processPrecheckoutPricing } from 'apps/pos/src/app/views/SelfServe/helpers';

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

  useEffect(() => {
    if (isMaxReached) {
      analytics.track_EXPERIMENTAL(SignUpEvents.formPageResponseReceived, {
        formName: 'Cart',
        fieldName: 'Cart',
        fieldType: 'Text Box',
        l1FunnelStage: 'Purchase Intention',
        l2FunnelStage: 'Error Check',
        status: 'Failure',
        errorMessage: 'This order can accommodate a maximum of 9 items',
      });
    }
  }, [isMaxReached]);

  return (
    <Box
      flexGrow={0}
      alignItems="center"
      backgroundColor="surface.background.gray.intense"
      bottom="spacing.0"
      width="100%"
      minHeight="100px"
      elevation="highRaised"
      paddingX={{ base: 'spacing.5', m: 'spacing.7' }}
      paddingY={{ base: 'spacing.6', m: 'spacing.7' }}
    >
      {isMaxReached ? (
        <Alert
          color="negative"
          title="This order can accommodate a maximum of 9 items"
          description="Reduce the total number of items for this order"
          marginBottom="spacing.5"
          isDismissible={false}
        />
      ) : null}
      <Box display="flex" alignItems="center" justifyContent="space-between">
        <Box>
          <Amount
            value={deviceCharges}
            suffix="none"
            isAffixSubtle={false}
            type="heading"
            size="medium"
            weight="semibold"
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
    </Box>
  );
};

export default CartFooter;

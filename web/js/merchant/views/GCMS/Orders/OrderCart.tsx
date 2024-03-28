import React, { useContext, useEffect } from 'react';
import {
  Title,
  Box,
  Button,
  Link,
  ChevronLeftIcon,
  Heading,
  Text,
  Divider,
  CloseIcon,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useLocation, useNavigate } from 'react-router-dom';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import ResellerDetailsHeader from 'merchant/views/GCMS/Resellers/ResellerDetailsHeader';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { showNotification } from 'merchant_common/reducers/notifications';

import OrderCartBillingSection from './OrderCartBillingSection';
import OrderCartDeliveryTypeSection from './OrderCartDeliveryTypeSection';
import OrderCartItemsSection from './OrderCartItemsSection';
import OrderCartStatusSection from './OrderCartStatusSection';
import OrderCartSummarySection from './OrderCartSummarySection';
import { GCMSOrderSession, OrderSessionContext } from './context';
import {
  trackOrderCartPageLoadSuccess,
  trackOrderCartVerifyFailure,
  trackOrderCartVerifySuccess,
} from './events';
import { orderSubmit, orderUpdate } from './queries';

type Props = {
  showNotification: ({ type, message }: { type: string; message: string }) => void;
};
const OrderCart = ({ showNotification }: Props) => {
  const navigate = useNavigate();
  const location = useLocation();
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId, resellerId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const { mutate: orderSubmitMutation, isLoading: isLoadingOrderSubmit } = useMutation({
    mutationFn: orderSubmit,
    onSuccess: (data) => {
      navigate(`/gcms/orders/${orderId}`);
      queryClient.setQueryData(['gcms:order', merchantId, orderId, mode], data);
      trackOrderCartVerifySuccess({ resellerId, orderId });
      showNotification({
        type: 'success',
        message: 'Order has been submitted successfully',
      });
    },
    onError: (error) => {
      trackOrderCartVerifyFailure({ resellerId, orderId });
      showNotification({
        type: 'error',
        /* @ts-expect-error error-message-check */
        message: error?.message || 'Error submitting the order',
      });
    },
  });
  const { mutate: orderUpdateMutation, isLoading: isLoadingOrderUpdate } = useMutation({
    mutationFn: orderUpdate,
    onSuccess: (data) => {
      queryClient.setQueryData(['gcms:order', merchantId, orderId, mode], data);
      showNotification({
        type: 'success',
        message: 'Order has been cancelled successfully',
      });
      navigate(`/gcms/orders/${orderId}`);
    },
    onError: (error) => {
      showNotification({
        type: 'error',
        /* @ts-expect-error error-message-check */
        message: error?.message || 'Error updating the order',
      });
    },
  });

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};

    if (prevPath) {
      return navigate(-1);
    }
    return navigate('/gcms/resellers');
  };

  const handleOrderSubmit = async () => {
    await orderSubmitMutation({ orderId, merchantId, mode });
  };

  const handleOrderCancel = async () => {
    await orderUpdateMutation({ orderId, merchantId, mode, status: 'cancelled' });
  };
  useEffect(() => {
    trackOrderCartPageLoadSuccess({ resellerId, orderId });
  }, [orderId, resellerId]);
  return (
    <Box>
      <div className="tabbed-container">
        <Box padding={['spacing.4', 'spacing.0']}>
          <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
            Go back
          </Link>
        </Box>
        <Box display="flex" flexDirection="row" justifyContent="space-between" alignItems="end">
          <Title color="surface.text.subtle.lowContrast">Cart</Title>
        </Box>
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <Box display="flex" flexDirection="column" flex={1}>
            <Box
              display="flex"
              flexDirection="row"
              flexWrap="wrap"
              maxWidth={{
                m: '100%',
                s: '100%',
              }}
            >
              <Box paddingRight="spacing.4" display="flex" flexDirection="column" flex={1}>
                <Box padding={['spacing.4', 'spacing.0']}>
                  <ResellerDetailsHeader
                    mode={mode}
                    merchantId={merchantId}
                    resellerId={resellerId}
                  />
                </Box>
                <Box
                  display="flex"
                  justifyContent="space-between"
                  alignItems="center"
                  paddingBottom="spacing.4"
                >
                  <Heading size="small" color="surface.text.subtle.lowContrast">
                    Programs
                  </Heading>
                  <Button
                    variant="secondary"
                    size="medium"
                    onClick={() =>
                      navigate('/gcms/orders/create/programs', {
                        state: { resellerId, prevPath: location.pathname },
                      })
                    }
                  >
                    Add Programs
                  </Button>
                </Box>
                <OrderCartItemsSection />
                <OrderCartDeliveryTypeSection />
                <OrderCartBillingSection />
                <Divider
                  thickness="thick"
                  margin={['spacing.5', 'spacing.0', 'spacing.5', 'spacing.0']}
                />
                <Box>
                  <Box display="flex" justifyContent="space-between">
                    <Box>
                      <Heading size="medium" color="surface.text.subtle.lowContrast">
                        Cancel this order?
                      </Heading>
                      <Text color="surface.text.subdued.lowContrast">
                        This action can not be undone
                      </Text>
                    </Box>
                    <Button
                      isLoading={isLoadingOrderUpdate}
                      variant="secondary"
                      color="negative"
                      size="medium"
                      iconPosition="left"
                      icon={CloseIcon}
                      onClick={handleOrderCancel}
                      accessibilityLabel="Cancel"
                    >
                      Cancel order
                    </Button>
                  </Box>
                </Box>
              </Box>
              <Box paddingTop="spacing.4">
                <OrderCartStatusSection />
                <OrderCartSummarySection />
                <Box paddingTop="spacing.4">
                  <Button
                    isLoading={isLoadingOrderSubmit}
                    isFullWidth
                    variant="primary"
                    onClick={handleOrderSubmit}
                  >
                    Verify & Place Order
                  </Button>
                </Box>
              </Box>
            </Box>
          </Box>
        </Box>
      </div>
    </Box>
  );
};

export default connect(null, {
  showNotification,
})(OrderCart);

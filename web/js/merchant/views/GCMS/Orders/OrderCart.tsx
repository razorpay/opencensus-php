import React, { useContext } from 'react';
import { Title, Box, Button, Link, ChevronLeftIcon, Heading } from '@razorpay/blade/components';
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
import { orderSubmit } from './queries';

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
      navigate(`/gcms/orders`);
      queryClient.setQueryData(['wallet:order', merchantId, orderId, mode], data);
      showNotification({
        type: 'success',
        message: 'Order has been submitted successfully',
      });
    },
    onError: (error) => {
      showNotification({
        type: 'error',
        /* @ts-expect-error */
        message: error?.message || 'Error submitting the order',
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

  return (
    <Box>
      <div className="tabbed-container">
        <Box padding={['spacing.4', 'spacing.0']}>
          <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
            Go back
          </Link>
        </Box>
        <Box>
          <Title color="surface.text.subtle.lowContrast">Cart</Title>
        </Box>
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <Box display="flex" flexDirection="column" flex={1}>
            <Box
              paddingTop="spacing.4"
              display="flex"
              flexDirection="row"
              flexWrap="wrap"
              maxWidth={{
                l: '1200px',
                m: '100%',
                s: '100%',
              }}
            >
              <Box paddingRight="spacing.4">
                <Box padding={['spacing.4', 'spacing.0']}>
                  <ResellerDetailsHeader merchantId={merchantId} resellerId={resellerId} />
                </Box>
                <Box
                  display="flex"
                  justifyContent="space-between"
                  alignItems="center"
                  padding={['spacing.4', 'spacing.0']}
                >
                  <Heading size="small" color="surface.text.subtle.lowContrast">
                    Program Details
                  </Heading>
                  <Button
                    variant="secondary"
                    size="medium"
                    onClick={() =>
                      navigate('/gcms/orders/create/programs', { state: { resellerId } })
                    }
                  >
                    Add Programs
                  </Button>
                </Box>
                <OrderCartItemsSection />
                <OrderCartDeliveryTypeSection />
                <OrderCartBillingSection />
              </Box>
              <Box>
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

import React, { useContext } from 'react';
import { Box, Divider, Heading, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { Error } from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import { convertUnixToDate } from 'common/utils/rzp-utils';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';

import { GCMSOrderSession, OrderSessionContext } from './context';
import { fetchOrderDetails } from './queries';
import { Order } from './types';

const OrderCartStatusSection = () => {
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const { data: order, isLoading } = useQuery<Order, Error>({
    queryKey: ['gcms:order', merchantId, orderId, mode],
    queryFn: () => fetchOrderDetails({ mode, orderId }),
    enabled: !!orderId,
  });

  return (
    <Box width="400px">
      <div className="content">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        ) : (
          <Box padding={['spacing.6']}>
            <Box
              display="flex"
              flexDirection="row"
              alignItems="center"
              justifyContent="space-between"
            >
              <Box>
                <Text color="surface.text.subdued.lowContrast">Order ID:</Text>
                <Heading size="medium" weight="bold">
                  {order?.id}
                </Heading>
              </Box>
              {order?.status === 'draft' && (
                <Box
                  borderRadius="large"
                  backgroundColor="brand.primary.300"
                  padding={['spacing.2', 'spacing.4']}
                >
                  <Text>Auto saved as draft</Text>
                </Box>
              )}
            </Box>
            <Box padding={['spacing.4', 'spacing.0']}>
              <Divider />
            </Box>

            {order?.updated_at && (
              <Box display="flex" flexDirection="row">
                <Text color="surface.text.subdued.lowContrast">Last Modified On:</Text>
                <Text
                  marginLeft="spacing.4"
                  color="surface.text.subdued.lowContrast"
                  weight="bold"
                >{`${convertUnixToDate(order.updated_at)}`}</Text>
                <Text color="surface.text.subdued.lowContrast" weight="bold" marginLeft="spacing.2">
                  {`${new Date(Number(order.updated_at) * 1000).toLocaleTimeString()}`}
                </Text>
              </Box>
            )}
          </Box>
        )}
      </div>
    </Box>
  );
};

export default OrderCartStatusSection;

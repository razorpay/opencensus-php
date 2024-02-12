import React, { useContext } from 'react';
import { Box, Divider, Heading, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { Error } from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import { getFormattedAmountNew, groupBy } from 'common/utils/rzp-utils';
import { fetchProgramsByResellerId } from 'merchant/views/GCMS/Programs/queries';
import { SKU } from 'merchant/views/GCMS/Programs/types';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { ListApiResponse } from 'merchant/views/GCMS/shared/types';

import { GCMSOrderSession, OrderSessionContext } from './context';
import { fetchOrderDetails, fetchOrderItems } from './queries';
import { Order, OrderItem } from './types';

const OrderCartSummarySection = () => {
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId, resellerId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const { data: order, isLoading: isLoadingOrder } = useQuery<Order, Error>({
    queryKey: ['wallet:order', merchantId, orderId, mode],
    queryFn: () => fetchOrderDetails({ mode, orderId }),
    enabled: !!orderId,
  });
  const { isLoading, data: skus } = useQuery<ListApiResponse<SKU>, Error>({
    queryKey: ['wallet:programs', merchantId, resellerId, mode],
    queryFn: () => fetchProgramsByResellerId({ mode, merchantId, resellerId }),
  });
  const { data: orderItems, isLoading: isLoadingOrderItems } = useQuery<
    ListApiResponse<OrderItem>,
    Error
  >({
    /* @ts-expect-error no-overload */
    queryKey: ['wallet:order:items', merchantId, orderId, mode],
    queryFn: () => fetchOrderItems({ mode, orderId }),
    enabled: !!orderId,
  });

  const orderItemsByPrograms = Array.isArray(orderItems)
    ? groupBy(orderItems, 'program_id')
    : undefined;

  return (
    <Box>
      {isLoading || isLoadingOrder || isLoadingOrderItems ? (
        <Box paddingTop="spacing.4">
          <div className="content">
            <div className="page-spinner-container">
              <Spinner center={undefined} />
            </div>
          </div>
        </Box>
      ) : (
        orderItemsByPrograms && (
          <Box paddingTop="spacing.4">
            <Box width="400px">
              <div className="content">
                <Box padding="spacing.6">
                  <Heading weight="bold" size="small">
                    Order Details:
                  </Heading>
                  <Box padding={['spacing.4', 'spacing.0', 'spacing.6', 'spacing.0']}>
                    <Divider />
                  </Box>
                  <Box>
                    {Object.keys(orderItemsByPrograms).map((programId) => {
                      const program =
                        skus?.items.find((sku) => sku.program_id === programId) || undefined;
                      const orderItems = orderItemsByPrograms[programId];
                      return (
                        <Box padding={['spacing.4', 'spacing.0']} key={programId}>
                          <Box
                            display="flex"
                            flexDirection="row"
                            alignItems="center"
                            justifyContent="space-between"
                          >
                            <Text weight="bold">{program?.name}</Text>
                            <Box display="flex" flexDirection="row" alignItems="center">
                              <Box paddingRight="spacing.2">
                                <Text color="surface.text.subdued.lowContrast">Discount:</Text>
                              </Box>
                              <Box>
                                <Text weight="bold">
                                  {/* @ts-expect-error parseInt Number */}
                                  {parseFloat(program?.default_discount / 100, 10)}%
                                </Text>
                              </Box>
                            </Box>
                          </Box>
                          <Box>
                            {orderItems.map((item) => {
                              return (
                                <Box
                                  key={item.id}
                                  display="flex"
                                  flexDirection="row"
                                  alignItems="flex-end"
                                  justifyContent="space-between"
                                  padding={['spacing.2', 'spacing.0']}
                                >
                                  <Box display="flex" flexDirection="row" alignItems="center">
                                    <Box padding={['spacing.2', 'spacing.0']}>
                                      <Box width="100px">
                                        <Text size="small" color="surface.text.subdued.lowContrast">
                                          Denomination
                                        </Text>
                                      </Box>
                                      <Box width="100px" paddingTop="spacing.2">
                                        <Text>
                                          {getFormattedAmountNew(item.denomination, true)}
                                        </Text>
                                      </Box>
                                    </Box>
                                    <Box padding={['spacing.2', 'spacing.0']}>
                                      <Box width="100px">
                                        <Text size="small" color="surface.text.subdued.lowContrast">
                                          Quantity
                                        </Text>
                                      </Box>
                                      <Box width="100px" paddingTop="spacing.2">
                                        <Text>{item.quantity}</Text>
                                      </Box>
                                    </Box>
                                  </Box>
                                  <Box padding={['spacing.2', 'spacing.0']}>
                                    <Text>
                                      {getFormattedAmountNew(
                                        item.denomination * item.quantity,
                                        true,
                                      )}
                                    </Text>
                                  </Box>
                                </Box>
                              );
                            })}
                          </Box>
                        </Box>
                      );
                    })}
                    <Box>
                      <Box padding={['spacing.4', 'spacing.0']}>
                        <Divider />
                      </Box>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        flexDirection="row"
                        alignItems="center"
                      >
                        <Box>
                          <Text weight="bold">Total Value</Text>
                        </Box>
                        <Box>
                          <Text weight="bold">
                            {getFormattedAmountNew(order?.total_amount, true)}
                          </Text>
                        </Box>
                      </Box>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        flexDirection="row"
                        alignItems="center"
                        padding={['spacing.2', 'spacing.0']}
                      >
                        <Box>
                          <Text color="surface.text.subdued.lowContrast">Less: Discount</Text>
                        </Box>
                        <Box>
                          <Text>
                            {order?.total_amount && order?.net_amount
                              ? getFormattedAmountNew(order.total_amount - order.net_amount, true)
                              : 0}
                          </Text>
                        </Box>
                      </Box>
                      <Box padding={['spacing.4', 'spacing.0']}>
                        <Divider />
                      </Box>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        flexDirection="row"
                        alignItems="center"
                        padding={['spacing.2', 'spacing.0']}
                      >
                        <Box>
                          <Text weight="bold">Net Price</Text>
                        </Box>
                        <Box>
                          <Text weight="bold">
                            {getFormattedAmountNew(order?.net_amount, true)}
                          </Text>
                        </Box>
                      </Box>
                    </Box>
                  </Box>
                </Box>
              </div>
            </Box>
          </Box>
        )
      )}
    </Box>
  );
};

export default OrderCartSummarySection;

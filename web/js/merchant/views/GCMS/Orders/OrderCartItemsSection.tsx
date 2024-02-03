import React, { useContext } from 'react';
import { Box, Heading, IconButton, Text, TextInput, TrashIcon } from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { Error } from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import debounce from 'common/utils/debounce';
import { getFormattedAmountNew, groupBy } from 'common/utils/rzp-utils';
import EmptyList from 'merchant/components/EmptyList';
import { fetchProgramsByResellerId } from 'merchant/views/GCMS/Programs/queries';
import { SKU } from 'merchant/views/GCMS/Programs/types';
import ProgramHeaderSection from 'merchant/views/GCMS/shared/ProgramHeaderSection';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { ListApiResponse } from 'merchant/views/Wallet/types';
import { showNotification } from 'merchant_common/reducers/notifications';

import { GCMSOrderSession, OrderSessionContext } from './context';
import { fetchOrderItems, orderItemsDelete, orderItemsPatch } from './queries';
import { OrderCardItemContainer } from './styled';
import { OrderItem } from './types';

type Props = {
  showNotification: ({ type, message }: { type: string; message: string }) => void;
};
const OrderCartItemsSection = ({ showNotification }: Props) => {
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { resellerId, orderId } = useContext<GCMSOrderSession>(OrderSessionContext);

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

  const { mutateAsync: orderItemPatchMutation, isLoading: isLoadingOrderItemPatchMutation } =
    useMutation({
      mutationFn: orderItemsPatch,
      onSuccess: (data) => {
        if (data?.order_items) {
          queryClient.setQueryData(
            ['wallet:order:items', merchantId, orderId, mode],
            data?.order_items,
          );
          queryClient.invalidateQueries(['wallet:order', merchantId, orderId, mode]);
        }
        showNotification({
          type: 'success',
          message: 'Order item updated successfully',
        });
      },
      onError: (error) => {
        showNotification({
          type: 'error',
          /* @ts-expect-error error-message-check */
          message: error?.message || 'Error updating order item',
        });
      },
    });
  const { mutateAsync: orderItemDeleteMutation, isLoading: isLoadingOrderItemDeleteMutation } =
    useMutation({
      mutationFn: orderItemsDelete,
      onSuccess: (data) => {
        if (data?.order_items) {
          queryClient.setQueryData(
            ['wallet:order:items', merchantId, orderId, mode],
            data?.order_items,
          );
          queryClient.invalidateQueries(['wallet:order', merchantId, orderId, mode]);
        }
        showNotification({
          type: 'success',
          message: 'Order item deleted successfully',
        });
      },
      onError: (error) => {
        showNotification({
          type: 'error',
          /* @ts-expect-error error-message-check */
          message: error?.message || 'Error deleting order item',
        });
      },
    });

  const updateOrderItem = async ({ item, quantity }) => {
    if (item.quantity === quantity && typeof quantity === 'number') {
      return;
    }
    await orderItemPatchMutation({
      orderId,
      itemId: item.id,
      orderItem: { ...item, quantity },
      mode,
      merchantId,
    });
  };

  const deleteOrderItem = async ({ id }: { id: string }) => {
    await orderItemDeleteMutation({ orderId, itemId: id, mode, merchantId });
  };

  const orderItemsByPrograms = Array.isArray(orderItems)
    ? groupBy(orderItems, 'program_id')
    : undefined;

  return (
    <Box minWidth="500px">
      {isLoading || isLoadingOrderItems ? (
        <div className="content">
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        </div>
      ) : (
        <Box>
          {!!orderItemsByPrograms ? (
            Object.keys(orderItemsByPrograms).map((programId) => {
              const program = skus?.items.find((sku) => sku.program_id === programId) || undefined;
              const orderItems = orderItemsByPrograms[programId];
              return (
                <Box margin={['spacing.0', 'spacing.0', 'spacing.4', 'spacing.0']} key={programId}>
                  <div className="content">
                    <Box
                      key={programId}
                      padding={['spacing.4']}
                      display="flex"
                      flex={1}
                      flexDirection="column"
                    >
                      {program && (
                        <ProgramHeaderSection
                          program={program}
                          containerProps={{
                            height: '110px',
                            padding: 'spacing.4',
                          }}
                          imageProps={{ height: '60px', width: '92px' }}
                        />
                      )}
                      <Box>
                        {orderItems.map((item) => {
                          return (
                            <OrderCardItemContainer key={item.id}>
                              <Box
                                display="flex"
                                flexDirection="row"
                                alignItems="center"
                                padding="spacing.4"
                              >
                                <Box width="250px">
                                  <Text color="surface.text.subdued.lowContrast">SKU</Text>
                                </Box>
                                <Box width="200px">
                                  <Text color="surface.text.subdued.lowContrast">Denomination</Text>
                                </Box>
                                <Box width="200px">
                                  <Text color="surface.text.subdued.lowContrast">Quantity</Text>
                                </Box>
                                <Box display="flex" justifyContent="flex-end">
                                  <IconButton
                                    isDisabled={isLoadingOrderItemDeleteMutation}
                                    size="large"
                                    icon={TrashIcon}
                                    accessibilityLabel="Delete"
                                    onClick={() => deleteOrderItem({ id: item.id })}
                                  />
                                </Box>
                              </Box>
                              <Box
                                key={item.id}
                                display="flex"
                                flexDirection="row"
                                alignItems="center"
                                padding="spacing.4"
                              >
                                <Box width="250px">
                                  <Heading size="small" color="surface.text.subdued.lowContrast">
                                    {item.sku_id}
                                  </Heading>
                                </Box>
                                <Box width="200px">
                                  <Heading size="small" color="surface.text.subdued.lowContrast">
                                    {getFormattedAmountNew(item.denomination, true)}
                                  </Heading>
                                </Box>
                                <Box width="200px">
                                  <Box width="175px">
                                    <TextInput
                                      isDisabled={isLoadingOrderItemPatchMutation}
                                      isLoading={isLoadingOrderItemPatchMutation}
                                      label=""
                                      type="number"
                                      placeholder="0"
                                      defaultValue={item.quantity}
                                      onChange={debounce(
                                        ({ value }) =>
                                          updateOrderItem({
                                            quantity: value,
                                            item,
                                          }),
                                        200,
                                      )}
                                    />
                                  </Box>
                                </Box>
                              </Box>
                            </OrderCardItemContainer>
                          );
                        })}
                      </Box>
                    </Box>
                  </div>
                </Box>
              );
            })
          ) : (
            <div className="content">
              <Box width="100%" height="100%">
                <EmptyList
                  description={
                    <React.Fragment>
                      <div>There are no programs yet!!</div>
                      <div>Start adding new programs now.</div>
                    </React.Fragment>
                  }
                />
              </Box>
            </div>
          )}
        </Box>
      )}
    </Box>
  );
};

export default connect(null, {
  showNotification,
})(OrderCartItemsSection);

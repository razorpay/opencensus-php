import React, { useContext } from 'react';
import { Box, Radio, RadioGroup, Text } from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { Error } from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { MerchantReseller } from 'merchant/views/GCMS/shared/types';
import { showNotification } from 'merchant_common/reducers/notifications';

import { GCMSOrderSession, OrderSessionContext } from './context';
import { fetchMerchantDetails, fetchOrderDetails, orderUpdate } from './queries';
import { Order } from './types';

type Props = {
  showNotification: ({ type, message }: { type: string; message: string }) => void;
};
const OrderCartDeliveryTypeSection = ({ showNotification }: Props) => {
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const { data: order, isLoading } = useQuery<Order, Error>({
    queryKey: ['wallet:order', merchantId, orderId, mode],
    queryFn: () => fetchOrderDetails({ mode, orderId }),
    enabled: !!orderId,
  });

  const resellerDetailId = order?.reseller_detail_id;

  const {
    data: merchantDetails,
    isLoading: isLoadingMerchantDetails,
    isError: isErrorMerchantDetails,
    error: errorMerchantDetails,
  } = useQuery<MerchantReseller, Error>({
    queryKey: ['merchant:reseller', merchantId, resellerDetailId, mode],
    queryFn: () => fetchMerchantDetails({ resellerDetailId, merchantId, mode }),
    enabled: !!resellerDetailId,
  });

  const isMultipleDelivery = order?.is_multiple_delivery;

  const {
    mutate: orderUpdateMutation,
    isLoading: isLoadingOrderUpdate,
    isError: isErrorOrderUpdate,
    error: errorOrderUpdate,
  } = useMutation({
    mutationFn: orderUpdate,
    onSuccess: (data) => {
      queryClient.setQueryData(['wallet:order', merchantId, orderId, mode], data);
      showNotification({
        type: 'success',
        message: 'Order has been updated successfully',
      });
    },
    onError: (error) => {
      showNotification({
        type: 'error',
        /* @ts-expect-error error-message-check */
        message: error?.message || 'Error updating the order',
      });
    },
  });

  const updateDeliveryType = async ({ value }) => {
    const isMultipleDeliveryType = value !== 'email';
    if (isMultipleDeliveryType === isMultipleDelivery) {
      return;
    }
    await orderUpdateMutation({
      orderId,
      /* @ts-expect-error empty-object-key */
      order: { is_multiple_delivery: isMultipleDeliveryType, id: orderId },
      mode,
      merchantId,
    });
  };

  return (
    <Box padding={['spacing.2', 'spacing.4', 'spacing.0', 'spacing.0']}>
      <div className="content">
        {isLoadingMerchantDetails || isLoading ? (
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        ) : (
          <Box>
            <Box padding={['spacing.6']}>
              <RadioGroup
                isDisabled={isLoadingOrderUpdate}
                display="flex"
                label="Delivery Type"
                name="delivery-type"
                value={isMultipleDelivery ? 'csv' : 'email'}
                onChange={({ value }) => updateDeliveryType({ value })}
                defaultValue={isMultipleDelivery ? 'csv' : 'email'}
              >
                <Radio value="email">Deliver to reseller’s admin email id</Radio>
                <Radio value="csv">Upload recipient csv file</Radio>
              </RadioGroup>
            </Box>
            {!isMultipleDelivery && merchantDetails?.primary_contact?.email && (
              <Box
                borderRadius="small"
                margin={['spacing.0', 'spacing.4', 'spacing.4']}
                padding={['spacing.2', 'spacing.4']}
                height="60px"
                backgroundColor="brand.gray.300.lowContrast"
                display="flex"
                alignItems="center"
              >
                <Text>
                  {`Generated cards will be delivered to ${merchantDetails?.primary_contact?.email}`}
                </Text>
              </Box>
            )}
            {isErrorOrderUpdate ||
              (isErrorMerchantDetails && (
                <Box>
                  <Error
                    text={
                      /* @ts-expect-error error-message-check */
                      errorOrderUpdate?.message ||
                      errorMerchantDetails?.message ||
                      'Something Went Wrong'
                    }
                  />
                </Box>
              ))}
          </Box>
        )}
      </div>
    </Box>
  );
};

export default connect(null, {
  showNotification,
})(OrderCartDeliveryTypeSection);

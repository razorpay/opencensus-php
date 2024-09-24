import React from 'react';
import { Box, Button, Text, ProgressBar, FileIcon, Link } from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useParams } from 'react-router-dom';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { Error } from 'common/new-ui/Input';
import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import { convertUnixToDate } from 'common/utils/rzp-utils';
import { batchDownload } from 'merchant/reducers/batches';
import OrderEmailDeliveryBatchUpload from 'merchant/views/GCMS/BatchUpload/OrderEmailDeliveryBatchUpload';
import { MerchantReseller } from 'merchant/views/GCMS/shared/types';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import OrderDetailsDeliveryBreakup from './OrderDetailsDeliveryBreakup';
import {
  fetchMerchantDetails,
  fetchOrderDetails,
  fetchOrderEmailDeliveryBatch,
  fetchOrderEmailDeliveryBatchDetails,
  fetchOrderEmailDeliveryStatus,
  orderEmailDeliveryStart,
} from './queries';
import { OrderDetailsDeliverySectionDistributionContainer } from './styled';
import { OrderDeliveryStatusEnum, OrderDeliveryStatus, OrderDeliveryBatch } from './types';

interface OpenModalArgs {
  size: string;
  component: JSX.Element;
  className?: string;
}

const OrderDetailsDelivery = ({
  openModal,
  mode,
  merchantId,
  showNotification,
  batchDownload,
  isOrderProcessed,
}: {
  mode: ModeT;
  merchantId: string;
  openModal: (args: OpenModalArgs) => void;
  showNotification: ({ type, message }: { type: string; message: string }) => void;
  batchDownload: (batchId: string) => void;
  isOrderProcessed: Boolean;
}) => {
  const { orderId } = useParams<{ orderId: string }>();

  const {
    data: order,
    isLoading: isLoadingOrder,
    isSuccess: isSuccessOrder,
  } = useQuery({
    queryKey: ['gcms:order', merchantId, orderId, mode],
    queryFn: () => fetchOrderDetails({ mode, orderId }),
    enabled: !!orderId,
  });

  const isOrderDeliveryStarted = !!order?.delivery_status;
  const isOrderDeliveryInProgress =
    order?.delivery_status === OrderDeliveryStatusEnum.DELIVERY_IN_PROGRESS;
  const isOrderDeliveryCompleted =
    order?.delivery_status === OrderDeliveryStatusEnum.DELIVERY_COMPLETED;
  const resellerDetailId = order?.reseller_detail_id;
  const isMultipleDelivery = order?.is_multiple_delivery;

  const { data: merchantDetails } = useQuery<MerchantReseller, Error>({
    queryKey: ['merchant:reseller', merchantId, resellerDetailId, mode],
    queryFn: () => fetchMerchantDetails({ resellerDetailId, merchantId, mode }),
    enabled: !!resellerDetailId && !order?.is_multiple_delivery,
  });

  const {
    mutateAsync: orderEmailDeliveryStatusMutation,
    isLoading: isLoadingOrderEmailDeliveryStatus,
  } = useMutation({
    mutationFn: orderEmailDeliveryStart,
    onSuccess: () => {
      queryClient.invalidateQueries(['gcms:order', merchantId, orderId, mode]);
      showNotification({
        type: 'success',
        message: 'Order Delivery has been submitted successfully',
      });
    },
    onError: (error) => {
      showNotification({
        type: 'error',
        /* @ts-expect-error This is necessary to handle potential errors during order delivery submission */
        message: error?.message || 'Error submitting the order delivery',
      });
    },
  });

  const { data: orderDeliveryStatus } = useQuery<OrderDeliveryStatus, Error>({
    queryKey: ['order:delivery:status', merchantId, orderId, mode],
    queryFn: () => fetchOrderEmailDeliveryStatus({ orderId, mode }),
    enabled: isOrderDeliveryInProgress || isOrderDeliveryCompleted,
  });

  const { data: orderDeliveryBatch, isSuccess: isSuccessOrderDeliveryBatch } = useQuery<
    OrderDeliveryBatch,
    Error
  >({
    queryKey: ['order:delivery:batch', merchantId, orderId, mode],
    queryFn: () => fetchOrderEmailDeliveryBatch({ orderId, mode }),
    enabled: isMultipleDelivery,
  });

  const batches = orderDeliveryBatch?.batches;
  const orderDeliveryBatchId = batches && batches.length > 0 ? batches?.[0]?.id : undefined;

  const { data: orderDeliveryBatchDetails, isSuccess: isSuccessOrderDeliveryBatchDetails } =
    useQuery({
      queryKey: ['order:delivery:batch:details', merchantId, orderDeliveryBatchId],
      queryFn: () =>
        fetchOrderEmailDeliveryBatchDetails({
          batchId: orderDeliveryBatchId,
        }),
      enabled: !!orderDeliveryBatchId && isMultipleDelivery,
    });

  const countsByStatus = orderDeliveryStatus?.items.reduce((acc, item) => {
    acc[item.status] = (acc[item.status] || 0) + item.count;
    return acc;
  }, {});

  /* @ts-expect-error TS2339: Property 'initiated' does not exist on type */
  const initiatedOrderDeliveryItems = countsByStatus?.initiated || 0;
  /* @ts-expect-error TS2339: Property 'success' does not exist on type */
  const successOrderDeliveryItems = countsByStatus?.success || 0;
  /* @ts-expect-error TS2339: Property 'failed' does not exist on type */
  const failedOrderDeliveryItems = countsByStatus?.failed || 0;

  const processedQuantity = Number(order?.processed_quantity);
  const totalEmailsUploaded = Number(orderDeliveryBatch?.total_emails_uploaded);
  const maxRows =
    isSuccessOrder && isSuccessOrderDeliveryBatch
      ? (processedQuantity || 0) - (totalEmailsUploaded || 0)
      : undefined;

  const validateOrderEmailDeliveryStatus = async ({ orderId }) => {
    await orderEmailDeliveryStatusMutation({ orderId, mode });
  };

  const handleOrderDelivery = () => {
    validateOrderEmailDeliveryStatus({ orderId });
  };

  const handleEmailDeliveryBatchUploadSuccess = () => {
    queryClient.invalidateQueries(['order:delivery:status', merchantId, orderId, mode]);
    queryClient.invalidateQueries(['order:delivery:batch', merchantId, orderId, mode]);
  };

  const handleOpenBatchUploadModal = () => {
    openModal({
      component: (
        <OrderEmailDeliveryBatchUpload
          maxRows={maxRows}
          onSuccess={handleEmailDeliveryBatchUploadSuccess}
        />
      ),
      size: 'large',
    });
  };

  const handleBatchDownload = async () => {
    if (orderDeliveryBatchDetails?.id) {
      const response = await batchDownload(orderDeliveryBatchDetails.id);
      /* @ts-expect-error batch-types */
      window.location = response?.data?.url;
      showNotification({
        type: 'success',
        message: 'Batch download has been initiated successfully',
      });
    }
  };

  return (
    <Box>
      <Box
        display="flex"
        flexDirection="column"
        flex={1}
        marginTop="spacing.4"
        borderWidth="thin"
        borderRadius="small"
        borderColor="surface.border.gray.muted"
      >
        {isLoadingOrder || isLoadingOrderEmailDeliveryStatus ? (
          <Box
            display="flex"
            alignItems="center"
            justifyContent="center"
            width="100%"
            minHeight="75px"
          >
            <Spinner center={undefined} />
          </Box>
        ) : isMultipleDelivery ? (
          <Box>
            <Box
              padding="spacing.4"
              display="flex"
              flexDirection="row"
              justifyContent="space-between"
              alignItems="center"
            >
              <Text weight="semibold">
                {!isOrderDeliveryStarted ? 'Distribute Cards' : `Distributed Cards`}
              </Text>
            </Box>
            {!isOrderDeliveryStarted && (
              <Box padding={['spacing.0', 'spacing.4', 'spacing.4', 'spacing.4']}>
                <Text>
                  Upload the excel with recipients email ID to deliver the generated cards.
                </Text>
              </Box>
            )}
            {isOrderDeliveryInProgress && (
              <Box paddingX="spacing.4">
                <ProgressBar
                  value={
                    (initiatedOrderDeliveryItems /
                      (Number(orderDeliveryStatus?.total_giftcard_count) || 1)) *
                    100
                  }
                  min={0}
                  max={100}
                  size="medium"
                  marginBottom="spacing.5"
                />
              </Box>
            )}
            {isOrderDeliveryCompleted && (
              <Box>
                <OrderDetailsDeliveryBreakup
                  delivered={successOrderDeliveryItems}
                  failed={failedOrderDeliveryItems}
                  uploaded={initiatedOrderDeliveryItems}
                />
              </Box>
            )}
            {!isOrderDeliveryStarted && (
              <OrderDetailsDeliverySectionDistributionContainer>
                {isSuccessOrderDeliveryBatch && isSuccessOrderDeliveryBatchDetails && (
                  <Box display="flex" flexDirection="row" alignItems="center">
                    <Box>
                      <Text>Total Emails Found:</Text>
                    </Box>
                    <Box paddingX="spacing.2">
                      <Text weight="semibold">{orderDeliveryBatch?.total_emails_uploaded}</Text>
                    </Box>
                  </Box>
                )}
                {isSuccessOrderDeliveryBatchDetails ? (
                  <Box
                    display="flex"
                    flexDirection="row"
                    alignItems="center"
                    paddingTop="spacing.2"
                  >
                    <FileIcon size="medium" color="interactive.icon.gray.muted" />
                    <Box paddingX="spacing.2">
                      <Link variant="button" onClick={handleBatchDownload}>
                        {orderDeliveryBatchDetails?.name}
                      </Link>
                    </Box>
                  </Box>
                ) : null}
                <Box paddingTop="spacing.4">
                  <Button variant="secondary" onClick={handleOpenBatchUploadModal}>
                    Upload CSV file
                  </Button>
                </Box>
              </OrderDetailsDeliverySectionDistributionContainer>
            )}
          </Box>
        ) : (
          <Box>
            <Box>
              {!isOrderDeliveryStarted && (
                <Box padding="spacing.4">
                  <Text color="surface.text.gray.muted">
                    {`Cards will be delivered to ${merchantDetails?.primary_contact?.email}`}
                  </Text>
                </Box>
              )}
              {isOrderDeliveryInProgress && (
                <Box padding="spacing.4">
                  <Text color="surface.text.gray.muted">
                    {`Cards will be delivered to ${merchantDetails?.primary_contact?.email}`}
                  </Text>
                  <Box paddingTop="spacing.4">
                    <ProgressBar
                      value={
                        (initiatedOrderDeliveryItems /
                          (Number(orderDeliveryStatus?.total_giftcard_count) || 1)) *
                        100
                      }
                      min={0}
                      max={100}
                      size="medium"
                      marginBottom="spacing.5"
                    />
                  </Box>
                </Box>
              )}
              {isOrderDeliveryCompleted && (
                <Box>
                  <Box padding="spacing.4">
                    <Text weight="semibold">
                      {`Cards delivered to ${merchantDetails?.primary_contact?.email}`}
                    </Text>
                    <Text size="small" color="surface.text.gray.muted">
                      {`${convertUnixToDate(order.updated_at)} ${new Date(
                        Number(order.updated_at) * 1000,
                      ).toLocaleTimeString()}`}
                    </Text>
                  </Box>
                  <OrderDetailsDeliveryBreakup
                    delivered={successOrderDeliveryItems}
                    failed={failedOrderDeliveryItems}
                    uploaded={initiatedOrderDeliveryItems}
                  />
                </Box>
              )}
            </Box>
          </Box>
        )}
      </Box>
      {!isOrderDeliveryStarted && (
        <Box paddingTop="spacing.4">
          <Button
            variant="primary"
            onClick={handleOrderDelivery}
            isLoading={isLoadingOrder || isLoadingOrderEmailDeliveryStatus}
            isDisabled={!isOrderProcessed}
          >
            Deliver
          </Button>
        </Box>
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  merchantId: state.session.user.current,
});

export default connect(mapStateToProps, (dispatch) => ({
  showNotification: (params) => dispatch(showNotification(params)),
  openModal: (params) => dispatch(openModal(params)),
  batchDownload: (id) => dispatch(batchDownload(id)),
}))(OrderDetailsDelivery);

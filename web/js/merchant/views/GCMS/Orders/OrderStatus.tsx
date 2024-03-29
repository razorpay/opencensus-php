import React from 'react';
import { Box, CheckIcon, CloseIcon, InfoIcon, ProgressBar, Text } from '@razorpay/blade/components';

import { convertUnixToDate } from 'common/utils/rzp-utils';
import { Order, OrderDeliveryStatusEnum, OrderStatusEnum } from 'merchant/views/GCMS/Orders/types';

import OrderDetailsDelivery from './OrderDetailsDelivery';
import { CustomDivider, IconContainer } from './styled';

const OrderStatus = ({ orderDetails, isLoading }: { orderDetails?: Order; isLoading: boolean }) => {
  const isOrderDraft = orderDetails?.status === OrderStatusEnum.DRAFT;
  const isOrderProcessed = orderDetails?.total_quantity === orderDetails?.processed_quantity;
  const isOrderCancelled = orderDetails?.status === OrderStatusEnum.CANCELLED;
  const isOrderDeliveryCompleted =
    orderDetails?.delivery_status === OrderDeliveryStatusEnum.DELIVERY_COMPLETED;

  if (isOrderDraft) {
    return null;
  }

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <>
      {isLoading ? null : (
        <Box
          backgroundColor="surface.background.gray.intense"
          paddingX="spacing.6"
          paddingTop="spacing.6"
          paddingBottom="spacing.8"
          display="flex"
          flexDirection="column"
          height="fit-content"
          minWidth="350px"
          maxWidth="390px"
        >
          <Text
            marginBottom="spacing.5"
            weight="semibold"
            color="surface.text.gray.normal"
            size="medium"
          >
            Order status
          </Text>
          <Box display="flex" flexDirection="row">
            <Box display="flex" flexDirection="column">
              <IconContainer isOrderProcessed withNoPadding>
                <CheckIcon
                  size="small"
                  color="surface.icon.staticWhite.normal"
                  margin="spacing.1"
                />
              </IconContainer>
              <CustomDivider isOrderProcessed />
            </Box>
            <Box width="100%">
              <Box display="flex" flexDirection="row" justifyContent="space-between">
                <Text
                  size="medium"
                  weight="semibold"
                  color="surface.text.gray.normal"
                  marginBottom="spacing.4"
                >
                  Order Created
                </Text>
                <Text size="small" color="surface.text.gray.muted" marginBottom="spacing.4">
                  {convertUnixToDate(orderDetails?.created_at)}
                </Text>
              </Box>
              <Box
                paddingBottom="spacing.5"
                paddingLeft="spacing.5"
                paddingRight="spacing.7"
                paddingTop="spacing.5"
                backgroundColor="surface.background.gray.moderate"
                marginBottom="spacing.6"
              >
                <Text size="medium" color="surface.text.gray.muted" weight="regular">
                  Order has been created.
                </Text>
              </Box>
            </Box>
          </Box>

          <Box display="flex" flexDirection="row">
            <Box display="flex" flexDirection="column">
              <IconContainer
                isOrderProcessed={isOrderProcessed}
                withNoPadding={isOrderProcessed || isOrderCancelled}
              >
                {isOrderCancelled ? (
                  <CloseIcon
                    color="feedback.icon.negative.intense"
                    size="small"
                    margin="spacing.1"
                  />
                ) : isOrderProcessed ? (
                  <CheckIcon
                    color="surface.icon.staticWhite.normal"
                    size="small"
                    margin="spacing.1"
                  />
                ) : (
                  <Box
                    backgroundColor="surface.background.primary.intense"
                    height="spacing.3"
                    width="spacing.3"
                    borderRadius="round"
                  />
                )}
              </IconContainer>
              <CustomDivider isOrderProcessed={isOrderProcessed} />
            </Box>
            <Box width="100%">
              <Box display="flex" flexDirection="row" justifyContent="space-between">
                <Text
                  size="medium"
                  weight="semibold"
                  color="surface.text.gray.normal"
                  marginBottom="spacing.4"
                >
                  {isOrderCancelled
                    ? 'Order Cancelled'
                    : isOrderProcessed
                    ? 'Generate cards'
                    : 'Generating cards'}
                </Text>
                {isOrderProcessed || isOrderCancelled ? (
                  <Text size="small" color="surface.text.gray.muted" marginBottom="spacing.4">
                    {convertUnixToDate(orderDetails?.created_at)}
                  </Text>
                ) : null}
              </Box>
              {isOrderCancelled ? null : (
                <Box
                  paddingBottom="spacing.7"
                  paddingLeft="spacing.5"
                  paddingRight="spacing.7"
                  paddingTop="spacing.5"
                  backgroundColor="surface.background.gray.moderate"
                  marginBottom="spacing.7"
                >
                  <Box display="flex" flexDirection="row" justifyContent="space-between">
                    <Text
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                      marginBottom="spacing.4"
                    >
                      {isOrderProcessed ? 'Order Processed' : 'Order Processing'}
                    </Text>
                    {isOrderProcessed || isOrderCancelled ? null : (
                      <Box alignSelf="center" display="flex" flexDirection="row">
                        <Text
                          size="medium"
                          color="interactive.text.primary.subtle"
                          marginBottom="13px"
                        >
                          In-progress
                        </Text>
                        <InfoIcon
                          color="interactive.icon.gray.normal"
                          marginLeft="spacing.1"
                          marginTop="spacing.2"
                          size="medium"
                        />
                      </Box>
                    )}
                  </Box>
                  {isOrderProcessed ? (
                    <Box display="flex" flexDirection="row">
                      <Box display="flex" flexDirection="column" marginRight="spacing.7">
                        <Text
                          size="medium"
                          color="surface.text.gray.muted"
                          weight="regular"
                          marginBottom="spacing.4"
                        >
                          Cards Requested:
                        </Text>
                        <Text
                          size="medium"
                          color="surface.text.gray.muted"
                          weight="regular"
                          marginBottom="spacing.4"
                        >
                          Successfully Processed:
                        </Text>
                        <Text size="medium" color="surface.text.gray.muted" weight="regular">
                          Failed:
                        </Text>
                      </Box>
                      <Box display="flex" flexDirection="column">
                        <Text
                          size="medium"
                          color="surface.text.gray.normal"
                          weight="regular"
                          marginBottom="spacing.4"
                        >
                          {orderDetails?.total_quantity}
                        </Text>
                        <Text
                          size="medium"
                          color="surface.text.gray.normal"
                          weight="regular"
                          marginBottom="spacing.4"
                        >
                          {orderDetails?.processed_quantity}
                        </Text>
                        <Text size="medium" color="surface.text.gray.normal" weight="regular">
                          {(orderDetails?.total_quantity || 0) -
                            (orderDetails?.processed_quantity || 0)}
                        </Text>
                      </Box>
                    </Box>
                  ) : (
                    <>
                      <ProgressBar
                        value={
                          ((orderDetails?.processed_quantity || 0) /
                            (orderDetails?.total_quantity || 1)) *
                          100
                        }
                        min={0}
                        max={100}
                        size="medium"
                        marginBottom="spacing.5"
                      />
                      <Text size="medium" color="surface.text.gray.muted" weight="regular">
                        Please wait as we process the order. This may take few minutes. Logs will be
                        once processing is complete.
                      </Text>
                    </>
                  )}
                </Box>
              )}
            </Box>
          </Box>

          {/* Delivery Status Section */}
          {isOrderCancelled ? null : (
            <Box>
              <Box display="flex" flexDirection="row" alignItems="center">
                <IconContainer
                  isOrderProcessed={isOrderDeliveryCompleted}
                  withNoPadding={isOrderDeliveryCompleted}
                >
                  {isOrderDeliveryCompleted ? (
                    <CheckIcon color="surface.icon.gray.normal" size="small" margin="spacing.1" />
                  ) : (
                    <Box
                      backgroundColor="surface.background.primary.intense"
                      height="spacing.3"
                      width="spacing.3"
                      borderRadius="round"
                    />
                  )}
                </IconContainer>
                <Box>
                  <Text size="medium" weight="semibold" color="surface.text.gray.normal">
                    Delivery
                  </Text>
                </Box>
              </Box>
              <Box paddingLeft="28px">
                <OrderDetailsDelivery />
              </Box>
            </Box>
          )}
        </Box>
      )}
    </>
  );
};

export default OrderStatus;

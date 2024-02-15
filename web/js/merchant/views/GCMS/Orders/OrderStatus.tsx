import React, { useState, useEffect } from 'react';
import { Box, ProgressBar, InfoIcon, CheckIcon, Text } from '@razorpay/blade/components';

import { convertUnixToDate } from 'common/utils/rzp-utils';
import { Order } from 'merchant/views/GCMS/Orders/types';

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const OrderStatus = ({ orderDetails, isLoading }: { orderDetails?: Order; isLoading: boolean }) => {
  const [isOrderProcessed, setIsOrderProcessed] = useState(false);

  useEffect(() => {
    if (orderDetails) {
      setIsOrderProcessed(orderDetails?.total_quantity === orderDetails?.processed_quantity);
    }
  }, [isLoading, orderDetails]);

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <>
      {isLoading ? null : (
        <Box
          backgroundColor="surface.background.level2.lowContrast"
          paddingX="spacing.6"
          paddingTop="spacing.6"
          paddingBottom="spacing.8"
          display="flex"
          flexDirection="column"
          height="fit-content"
          maxWidth="400px"
        >
          <Text
            marginBottom="spacing.5"
            weight="bold"
            color="surface.text.normal.lowContrast"
            size="medium"
          >
            Order status
          </Text>
          <Box display="flex" flexDirection="row">
            <Box display="flex" flexDirection="column">
              <Box
                backgroundColor="brand.secondary.500"
                height="spacing.5"
                width="spacing.5"
                marginRight="spacing.4"
                borderRadius="round"
                marginY="spacing.1"
              >
                <CheckIcon
                  color="surface.text.normal.highContrast"
                  size="small"
                  margin="spacing.1"
                />
              </Box>
              <Box
                backgroundColor="brand.secondary.500"
                width="1px"
                flexGrow={1}
                marginRight="spacing.6"
                marginLeft="spacing.3"
                marginBottom="spacing.3"
                marginTop="spacing.2"
              />
            </Box>
            <Box width="100%">
              <Box display="flex" flexDirection="row" justifyContent="space-between">
                <Text
                  size="medium"
                  weight="bold"
                  color="surface.text.normal.lowContrast"
                  marginBottom="spacing.4"
                >
                  Discount approved
                </Text>
                <Text
                  size="small"
                  type="muted"
                  color="surface.text.subdued.lowContrast"
                  marginBottom="spacing.4"
                >
                  {convertUnixToDate(orderDetails?.created_at)}
                </Text>
              </Box>
              <Box
                paddingBottom="spacing.8"
                paddingLeft="spacing.5"
                paddingRight="spacing.7"
                paddingTop="spacing.5"
                backgroundColor="brand.gray.200.lowContrast"
                marginBottom="spacing.6"
              >
                <Text size="medium" color="surface.text.subdued.lowContrast" weight="regular">
                  Discount changes have been approved.
                </Text>
              </Box>
            </Box>
          </Box>

          <Box display="flex" flexDirection="row">
            <Box display="flex" flexDirection="column">
              <Box
                borderRadius="round"
                backgroundColor={
                  isOrderProcessed ? 'brand.secondary.500' : 'brand.gray.400.lowContrast'
                }
                height="spacing.5"
                width="spacing.5"
                marginRight="spacing.4"
                marginY="spacing.1"
                padding={isOrderProcessed ? 'spacing.0' : 'spacing.2'}
              >
                {isOrderProcessed ? (
                  <CheckIcon
                    color="surface.text.normal.highContrast"
                    size="small"
                    margin="spacing.1"
                  />
                ) : (
                  <Box
                    backgroundColor="brand.primary.500"
                    height="spacing.3"
                    width="spacing.3"
                    borderRadius="round"
                  />
                )}
              </Box>
              <Box
                backgroundColor={
                  isOrderProcessed ? 'brand.secondary.500' : 'brand.gray.400.lowContrast'
                }
                width="1px"
                flexGrow={1}
                marginRight="spacing.6"
                marginLeft="spacing.3"
                marginBottom="spacing.3"
                marginTop="spacing.2"
              />
            </Box>
            <Box width="100%">
              <Box display="flex" flexDirection="row" justifyContent="space-between">
                <Text
                  size="medium"
                  weight="bold"
                  color="surface.text.normal.lowContrast"
                  marginBottom="spacing.4"
                >
                  {isOrderProcessed ? 'Generate cards' : 'Generating cards'}
                </Text>
                {isOrderProcessed ? (
                  <Text
                    size="small"
                    type="muted"
                    color="surface.text.subdued.lowContrast"
                    marginBottom="spacing.4"
                  >
                    {convertUnixToDate(orderDetails?.created_at)}
                  </Text>
                ) : null}
              </Box>
              <Box
                paddingBottom="spacing.7"
                paddingLeft="spacing.5"
                paddingRight="spacing.7"
                paddingTop="spacing.5"
                backgroundColor="brand.gray.200.lowContrast"
                marginBottom="spacing.7"
              >
                <Box display="flex" flexDirection="row" justifyContent="space-between">
                  <Text
                    size="medium"
                    weight="bold"
                    color="surface.text.normal.lowContrast"
                    marginBottom="spacing.4"
                  >
                    {isOrderProcessed ? 'Order Processed' : 'Order Processing'}
                  </Text>
                  {isOrderProcessed ? null : (
                    <Box alignSelf="center" display="flex" flexDirection="row">
                      <Text
                        size="medium"
                        type="muted"
                        color="action.text.link.default"
                        marginBottom="13px"
                      >
                        In-progress
                      </Text>
                      <InfoIcon
                        color="surface.action.icon.default.lowContrast"
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
                        color="surface.text.subdued.lowContrast"
                        weight="regular"
                        marginBottom="spacing.4"
                      >
                        Total Card request:
                      </Text>
                      <Text
                        size="medium"
                        color="surface.text.subdued.lowContrast"
                        weight="regular"
                        marginBottom="spacing.4"
                      >
                        Successfully Process:
                      </Text>
                      <Text size="medium" color="surface.text.subdued.lowContrast" weight="regular">
                        Failed:
                      </Text>
                    </Box>
                    <Box display="flex" flexDirection="column">
                      <Text
                        size="medium"
                        color="surface.text.normal.lowContrast"
                        weight="regular"
                        marginBottom="spacing.4"
                      >
                        {orderDetails?.total_quantity}
                      </Text>
                      <Text
                        size="medium"
                        color="surface.text.normal.lowContrast"
                        weight="regular"
                        marginBottom="spacing.4"
                      >
                        {orderDetails?.processed_quantity}
                      </Text>
                      <Text size="medium" color="surface.text.normal.lowContrast" weight="regular">
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
                    <Text size="medium" color="surface.text.subdued.lowContrast" weight="regular">
                      Please wait as we process the order. This may take few minutes. Logs will be
                      once processing is complete.
                    </Text>
                  </>
                )}
              </Box>
            </Box>
          </Box>

          <Box display="flex" flexDirection="row" alignItems="center">
            <Box
              backgroundColor="brand.gray.500.highContrast"
              height="spacing.3"
              width="spacing.3"
              marginLeft="spacing.2"
              marginRight="spacing.5"
              borderRadius="round"
            />
            <Box>
              <Text size="medium" weight="bold" color="surface.text.normal.lowContrast">
                Delivery
              </Text>
            </Box>
          </Box>
        </Box>
      )}
    </>
  );
};

export default OrderStatus;

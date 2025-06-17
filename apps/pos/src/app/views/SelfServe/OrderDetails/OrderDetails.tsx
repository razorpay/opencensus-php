import React, { useContext } from 'react';
import {
  Box,
  CalendarIcon,
  Divider,
  Heading,
  MailIcon,
  PhoneIcon,
  Spinner,
  Text,
  useToast,
} from '@razorpay/blade/components';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import moment from 'moment';
import { Navigate, useParams } from 'react-router-dom';

import OrderItem from 'apps/pos/src/app/views/SelfServe/OrderSummary/OrderItems/OrderItem';
import OrderPricing from 'apps/pos/src/app/views/SelfServe/OrderSummary/OrderPricing';
import PosBreadcrumbs from 'apps/pos/src/app/views/SelfServe/PosBreadcrumbs';
import { ORDER_STATUS_META_DATA } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getOrderPricingFromOrderDetails,
  getOrderStatus,
} from 'apps/pos/src/app/views/SelfServe/helpers';
import { getOrderDetails } from 'apps/pos/src/app/views/SelfServe/services';
import { MainContainer } from 'apps/pos/src/app/views/SelfServe/styles';
import { OrderDetailsItem, ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';

import ConfirmationBanner from './ConfirmationBanner';
import OrderStatusTimeline from './OrderStatusTimeline';
import SalesPocBanner from './SalesPocBanner';

type OrderDetailsProps = {
  isOrderConfirmation?: boolean | undefined;
};

const OrderDetails = ({ isOrderConfirmation }: OrderDetailsProps): JSX.Element => {
  const { state } = useContext(PosDeviceStoreContext);
  const { user, productDescriptions } = state;
  const { orderId } = useParams();
  const toast = useToast();
  const QUERY_CACHE_KEY = ['order-details', orderId];
  const queryClient = useQueryClient();
  const { data: orderDetails, isLoading } = useQuery(
    QUERY_CACHE_KEY,
    () => getOrderDetails(orderId),
    {
      enabled: !!orderId,
      retry: 2,
      retryDelay: 800,
      staleTime: Infinity,
      refetchOnWindowFocus: false,
      refetchOnMount: false,
      onError: () => {
        toast.show({ color: 'negative', content: 'Something went wrong!' });
      },
    },
  );

  if (isLoading || !orderDetails || !Object.keys(productDescriptions).length)
    return (
      <MainContainer>
        <Box height="50vh" width="100%" display="flex" alignItems="center" justifyContent="center">
          <Spinner accessibilityLabel="order-details-spinner" size="large" />
        </Box>
      </MainContainer>
    );

  const { delivery_address, created_at, items } = orderDetails;
  const orderStatusMeta = getOrderStatus(orderDetails);

  const orderPricing = getOrderPricingFromOrderDetails({
    orderRespData: orderDetails,
    productDescriptions,
  });

  const handleOnSalesCodeUpdate = (orderDetails: OrderDetailsItem) => {
    queryClient.setQueryData(QUERY_CACHE_KEY, orderDetails);
  };

  if (isOrderConfirmation && orderStatusMeta.key !== ORDER_STATUS_META_DATA.ORDER_RECEIVED.key)
    return <Navigate to={`/pos/orders/${orderId}`} />;

  return (
    <MainContainer isIgnorePadding={isOrderConfirmation}>
      {isOrderConfirmation ? (
        <ConfirmationBanner arrivingDate={orderDetails.arriving_at} />
      ) : (
        <React.Fragment>
          <PosBreadcrumbs />
          <Box
            display={{ base: 'block', l: 'flex' }}
            marginBottom="spacing.5"
            width="100%"
            justifyContent="space-between"
            alignItems="center"
          >
            <Box flex="0 0 35%" marginBottom={{ base: 'spacing.5', l: 'spacing.0' }}>
              {orderStatusMeta ? (
                <Box>
                  <Text color="surface.text.gray.muted">{orderStatusMeta.statusTitle}</Text>
                  <Box marginBottom="spacing.3">
                    <Heading size="large">
                      {moment.unix(orderStatusMeta.statusDate).format('MMMM DD, YYYY')}
                    </Heading>
                  </Box>
                </Box>
              ) : null}
              <Box display="flex" alignItems="center">
                <CalendarIcon
                  size="medium"
                  color="interactive.icon.gray.subtle"
                  marginRight="spacing.2"
                />
                <Text>Ordered on {moment.unix(created_at).format('MMMM DD, YYYY')}</Text>
              </Box>
            </Box>
            <OrderStatusTimeline orderDetails={orderDetails} />
          </Box>
        </React.Fragment>
      )}
      <Box padding={isOrderConfirmation ? 'spacing.6' : 'spacing.0'}>
        {!orderDetails?.sales_code ? (
          <SalesPocBanner orderId={orderId} onSalesPocUpdate={handleOnSalesCodeUpdate} />
        ) : null}
        <Box as="section" display={{ base: 'block', l: 'flex' }} gap="spacing.5">
          <Box flex="0 0 60%">
            <Box
              backgroundColor="surface.background.gray.moderate"
              padding="spacing.6"
              borderRadius="medium"
              marginBottom="spacing.5"
            >
              {delivery_address?.address ? (
                <React.Fragment>
                  <Text marginBottom="spacing.5" size="large">
                    Shipping Address
                  </Text>
                  <Box marginBottom="spacing.5">
                    <Box display="flex" marginBottom="spacing.2">
                      <Text weight="semibold">{delivery_address.name}</Text>
                      <Text marginX="spacing.3">|</Text>
                      <Text>{delivery_address.phone_no}</Text>
                    </Box>
                    <Text>
                      {delivery_address.address}, {delivery_address.city}, {delivery_address.state}-
                      {delivery_address.pin_code}
                    </Text>
                  </Box>
                </React.Fragment>
              ) : null}
              <Divider marginBottom="spacing.5" />
              <Box marginBottom="spacing.5">
                <Box display="flex" marginBottom="spacing.5">
                  <Text marginRight="spacing.3" size="large">
                    Products in this purchase
                  </Text>
                  <Text weight="regular" size="large">
                    {items.length} {items.length === 1 ? 'Item' : 'Items'}
                  </Text>
                </Box>
                {items.map(({ code, count, period }) => (
                  <OrderItem
                    key={`${code}-${period}`}
                    orderItem={{
                      code,
                      quantity: count,
                      plan: period as ProductPlans,
                    }}
                    isOrderDetails
                  />
                ))}
              </Box>
              {user?.contact_email || user?.contact_mobile ? (
                <React.Fragment>
                  <Divider marginBottom="spacing.5" />
                  <Box marginBottom="spacing.5">
                    <Text marginBottom="spacing.3" size="large">
                      Updates sent to
                    </Text>
                    <Box display={{ base: 'block', l: 'flex' }} testID="merchant-contact-container">
                      {user?.contact_email ? (
                        <Box
                          display="flex"
                          alignItems="center"
                          marginRight="spacing.5"
                          marginBottom="spacing.3"
                        >
                          <MailIcon
                            size="medium"
                            color="interactive.icon.gray.subtle"
                            marginRight="spacing.3"
                          />
                          <Text>{user.contact_email}</Text>
                        </Box>
                      ) : null}
                      {user?.contact_mobile ? (
                        <Box display="flex" alignItems="center" marginBottom="spacing.3">
                          <PhoneIcon
                            size="medium"
                            color="interactive.icon.gray.subtle"
                            marginRight="spacing.3"
                          />
                          <Text>{user.contact_mobile}</Text>
                        </Box>
                      ) : null}
                    </Box>
                  </Box>
                </React.Fragment>
              ) : null}
            </Box>
          </Box>
          <Box width="100%">
            <Box
              backgroundColor="surface.background.gray.moderate"
              padding="spacing.6"
              borderRadius="medium"
              display="flex"
              justifyContent="center"
              alignItems="center"
            >
              {orderPricing && <OrderPricing pricing={orderPricing} />}
            </Box>
          </Box>
        </Box>
      </Box>
    </MainContainer>
  );
};

export default OrderDetails;

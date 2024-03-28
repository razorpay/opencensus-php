import React, { useEffect } from 'react';
import {
  Amount,
  Badge,
  Box,
  Heading,
  Text,
  ChevronLeftIcon,
  Link,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import styled from 'styled-components';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import { convertUnixToDate, getFormattedAmountNew, groupBy } from 'common/utils/rzp-utils';
import {
  fetchProgramsForReseller,
  fetchResellerDetails,
} from 'merchant/views/GCMS/Resellers/queries';
import ProgramHeaderSection from 'merchant/views/GCMS/shared/ProgramHeaderSection';
import { ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import OrderStatus from './OrderStatus';
import TransactionDetailsSection from './TransactionDetailsSection';
import { trackOrdersDetailsPageLoadSuccess } from './events';
import { fetchOrderDetails, fetchOrderItems } from './queries';

export const OrderCardItemContainer = styled.div(
  ({ theme }) => `
  display: flex;
  flex-direction: column;
  justify-content: center;
  background-color: ${theme.colors.surface.background.level1.lowContrast} ;
  border-radius: 5px;
  padding: 16px 16px;
  margin: 0px 0px 12px 0px;
`,
);

const OrderDetails = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const { orderId } = useParams<{ orderId: string }>();

  const { data: orderDetails, isLoading: isOrderDetailsLoading } = useQuery({
    queryKey: ['gcms:order', merchantId, orderId, mode],
    queryFn: () => fetchOrderDetails({ mode, orderId }),
    enabled: !!orderId,
  });

  const { data: orderItems, isLoading: isOrderItemsLoading } = useQuery({
    queryKey: ['gcms:order:items', merchantId, orderId, mode],
    queryFn: () => fetchOrderItems({ mode, orderId }),
    enabled: !!orderId,
  });

  const { isLoading: isResellerDetailsLoading, data: resellerDetails } = useQuery({
    queryKey: ['gcms:reseller:details', merchantId, orderDetails?.reseller_id, mode],
    queryFn: () =>
      fetchResellerDetails({ resellerId: orderDetails?.reseller_id, merchantId, mode }),
    enabled: !!orderDetails?.reseller_id,
  });

  const { isLoading: isSkusLoading, data: skus } = useQuery({
    queryKey: ['gcms:programs', merchantId, orderDetails?.reseller_id, mode],
    queryFn: () =>
      fetchProgramsForReseller({ resellerId: orderDetails?.reseller_id, mode, count: 100 }),
    enabled: !!orderDetails?.reseller_id,
  });

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};
    if (prevPath) {
      return navigate(-1);
    }
    return navigate('/gcms/orders');
  };

  //todo need to check the orderDetails object
  useEffect(() => {
    if (orderDetails)
      trackOrdersDetailsPageLoadSuccess({
        orderId: orderDetails?.id,
        resellerId: orderDetails?.reseller_id,
        orderTotalAamount: orderDetails?.total_amount,
        orderCreatedAt: orderDetails?.created_at,
        orderUpdatedAt: orderDetails?.updated_at,
        orderStatus: orderDetails?.status,
        deliveryStatus: orderDetails?.delivery_status,
        isMultipleDelivery: orderDetails?.is_multiple_delivery,
      });
  }, [orderDetails]);

  const orderItemsByPrograms = Array.isArray(orderItems)
    ? groupBy(orderItems, 'program_id')
    : undefined;

  return (
    <div className="tabbed-container">
      <Box padding={['spacing.4', 'spacing.0']}>
        <Link variant="button" icon={ChevronLeftIcon} iconPosition="left" onClick={handleGoBack}>
          Go back
        </Link>
      </Box>
      {isOrderDetailsLoading || isOrderItemsLoading || isResellerDetailsLoading || isSkusLoading ? (
        <Box display="flex" justifyContent="center" alignItems="center" height="100%" width="100%">
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        </Box>
      ) : (
        <Box display="flex" flexDirection="row" justifyContent="space-between" flexWrap="wrap">
          <Box
            flex={1}
            backgroundColor="surface.background.level3.lowContrast"
            marginBottom="spacing.4"
            marginRight="spacing.4"
          >
            <Box
              padding="spacing.6"
              display="flex"
              justifyContent="space-between"
              borderBottomWidth="thick"
              borderBottomColor="surface.border.subtle.lowContrast"
            >
              <Box>
                <Heading size="large" weight="bold">
                  Order ID: {orderDetails?.id}
                </Heading>
                <Box display="flex" marginTop="spacing.3">
                  <Text size="medium" color="surface.text.subdued.lowContrast">
                    Order Date:&nbsp;&nbsp;
                  </Text>
                  <Text size="medium" weight="bold">
                    {convertUnixToDate(orderDetails?.created_at)}
                  </Text>
                </Box>
              </Box>
              <Box display="flex" alignItems="center">
                <Badge color="neutral">Payment mode: Account</Badge>
                {orderDetails?.status ? (
                  <Badge color={ORDERS_STATUS[orderDetails?.status].color} marginLeft="spacing.4">
                    {ORDERS_STATUS[orderDetails?.status].label}
                  </Badge>
                ) : null}
              </Box>
            </Box>
            <Box
              padding="spacing.6"
              display="flex"
              borderBottomWidth="thick"
              borderBottomColor="surface.border.subtle.lowContrast"
            >
              <Box
                paddingRight="spacing.6"
                borderRightWidth="thick"
                borderRightColor="surface.border.subtle.lowContrast"
              >
                <Text marginBottom="spacing.4">Reseller details</Text>
                <Box display="flex">
                  {/* <StyledImg src={resellerDetails.logo} /> */}
                  <Box display="flex" flexDirection="column" justifyContent="space-between">
                    <Heading>{resellerDetails?.name || '-'}</Heading>
                    <Box display="flex">
                      <Text color="surface.text.muted.lowContrast">
                        ID: {resellerDetails?.id || '-'}
                      </Text>
                    </Box>
                  </Box>
                </Box>
              </Box>
              <Box marginLeft="spacing.6" flex={1}>
                <Text marginBottom="spacing.4">Transaction details</Text>
                <Box display="flex">
                  {/* <TransactionDetailsSection heading={'Transaction Id'} value={456789292} /> */}
                  <TransactionDetailsSection
                    heading="Amount"
                    value={
                      <Amount
                        value={orderDetails?.total_amount ? orderDetails.total_amount / 100 : 0}
                        size="body-medium-bold"
                        isAffixSubtle={false}
                      />
                    }
                  />
                  <TransactionDetailsSection
                    heading="Quantity"
                    value={orderDetails?.total_quantity || '-'}
                  />
                  <TransactionDetailsSection
                    heading="Date & Time"
                    value={convertUnixToDate(orderDetails?.created_at)}
                  />
                </Box>
              </Box>
            </Box>
            <Box>
              {!!orderItemsByPrograms
                ? Object.keys(orderItemsByPrograms).map((programId) => {
                    const program =
                      skus?.items.find((sku) => sku.program_id === programId) || undefined;
                    const orderItems = orderItemsByPrograms[programId];
                    return (
                      <Box key={programId} paddingX="spacing.6" marginY="spacing.8">
                        <Box key={programId} display="flex" flex={1} flexDirection="column">
                          {program && (
                            <Box display="flex" marginBottom="spacing.4">
                              <ProgramHeaderSection
                                showOverview={false}
                                program={program}
                                containerProps={{
                                  flex: 1,
                                }}
                                imageProps={{ height: '60px', width: '92px' }}
                              />
                              <Box display="flex">
                                <Text color="surface.text.subdued.lowContrast">
                                  Discount:&nbsp;&nbsp;
                                </Text>
                                <Text weight="bold">
                                  {orderItemsByPrograms[programId][0].discount_percent / 100}%
                                </Text>
                              </Box>
                            </Box>
                          )}
                          {orderItems.map((item) => {
                            return (
                              <OrderCardItemContainer key={item.id}>
                                <Box display="flex" alignItems="center" marginBottom="spacing.1">
                                  <Box display="flex" flex={1} minWidth="250px">
                                    <Text size="small" color="surface.text.subdued.lowContrast">
                                      SKU
                                    </Text>
                                  </Box>
                                  <Box display="flex" flex={1} minWidth="200px">
                                    <Text size="small" color="surface.text.subdued.lowContrast">
                                      Denomination
                                    </Text>
                                  </Box>
                                  <Box display="flex" flex={1} minWidth="200px">
                                    <Text size="small" color="surface.text.subdued.lowContrast">
                                      Quantity
                                    </Text>
                                  </Box>
                                  <Box display="flex" flex={1} minWidth="200px">
                                    <Text size="small" color="surface.text.subdued.lowContrast">
                                      Total Value
                                    </Text>
                                  </Box>
                                </Box>
                                <Box
                                  key={item.id}
                                  display="flex"
                                  alignItems="center"
                                  marginTop="spacing.2"
                                >
                                  <Box display="flex" flex={1} minWidth="250px">
                                    <Text size="medium" weight="bold">
                                      {item.sku_id}
                                    </Text>
                                  </Box>
                                  <Box display="flex" flex={1} minWidth="200px">
                                    <Text size="medium" weight="bold">
                                      {getFormattedAmountNew(item.denomination, true)}
                                    </Text>
                                  </Box>
                                  <Box display="flex" flex={1} minWidth="200px">
                                    <Text size="medium" weight="bold">
                                      {item.quantity}
                                    </Text>
                                  </Box>
                                  <Box display="flex" flex={1} minWidth="200px">
                                    <Text size="medium" weight="bold">
                                      {getFormattedAmountNew(item.total_amount, true)}
                                    </Text>
                                  </Box>
                                </Box>
                              </OrderCardItemContainer>
                            );
                          })}
                        </Box>
                      </Box>
                    );
                  })
                : null}
            </Box>
          </Box>
          <OrderStatus isLoading={isOrderDetailsLoading} orderDetails={orderDetails} />
        </Box>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  merchantId: state.session.user.current,
});

export default connect(mapStateToProps)(OrderDetails);

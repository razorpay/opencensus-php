import React, { useEffect, useState } from 'react';
import {
  Badge,
  Box,
  Text,
  TableBody,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import OrdersFilter from './OrdersFilters';
import PageLayout from 'merchant/views/GCMS/shared/PageLayout';
import { trackOrdersItemClicked, trackOrdersPageLoadSuccess } from './events';
import { LIST_FETCH_BATCH_SIZE, fetchOrders } from './queries';
import { convertUnixToShortDate } from '../shared/utils';

const ORDER_LIST_COLUMNS = [
  {
    label: 'Order ID',
    value: (order) => (
      <NavLink
        key={order.orderId}
        to={`${order.id}`}
        state={{ prevPath: location?.pathname }}
        onClick={() =>
          trackOrdersItemClicked({
            orderId: order?.id,
            resellerId: order?.reseller_id,
            orderTotalAamount: order?.total_amount,
            orderCreatedAt: order?.created_at,
            orderUpdatedAt: order?.updated_at,
            orderStatus: order?.status,
            deliveryStatus: order?.delivery_status,
            isMultipleDelivery: order?.is_multiple_delivery,
          })
        }
      >
        {order.id}
      </NavLink>
    ), //TODO: replace with actual order details link
  },
  {
    label: 'Order Date',
    value: (order) => <Text>{convertUnixToShortDate(order.created_at)}</Text>,
  },
  // {
  //   label: 'Order created by',
  //   value: (order) => <Text>{order.orderCreatedBy}</Text>, //TODO: not present in order response
  // },
  {
    label: 'Reseller',
    value: (order) => <Text>{order.reseller_name}</Text>,
  },

  // {
  //   label: 'Program Type',
  //   value: (order) => <Text>{order.programType}</Text>, //TODO: not present in order response
  // },
  {
    label: 'Total Quantity',
    value: (order) => <Text>{order.total_quantity}</Text>,
  },
  {
    label: 'Total Value',
    value: (order) => <Text>{getFormattedAmountNew(order.total_amount, 10)}</Text>,
  },
  {
    label: 'Status',
    value: (order) => (
      <Badge color={ORDERS_STATUS[order.status].color}>{ORDERS_STATUS[order.status].label}</Badge>
    ),
  },
];

const Orders = ({ mode }: { mode: ModeT }) => {
  const [skip, setSkip] = useState(0);
  const [resellerName, setResellerName] = useState('');
  const [orderId, setOrderId] = useState('');
  const [orderStatus, setOrderStatus] = useState('');
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();

  const { isLoading, data: orders } = useQuery({
    queryKey: ['gcms:orders', skip, resellerName, orderStatus, fromDate, toDate, orderId],
    queryFn: () =>
      fetchOrders({ skip, resellerName, orderStatus, fromDate, toDate, mode, orderId }),
  });

  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  const handleSearch = ({ resellerName, status, date, orderId }) => {
    setResellerName(resellerName);
    setOrderStatus(status);
    setFromDate(date.from);
    setToDate(date.to);
    setOrderId(orderId);
  };
  useEffect(() => {
    trackOrdersPageLoadSuccess();
  }, []);

  const tableData = {
    nodes: orders?.items ?? [],
  };

  return (
    <Wrapper>
      <PageLayout title="Orders" subtitle="A list of orders processed for Gift Cards.">
        <div className="content">
          <OrdersFilter onSearch={handleSearch} />
          {isLoading ? (
            <div className="page-spinner-container">
              <Spinner center={undefined} />
            </div>
          ) : Array.isArray(tableData.nodes) && tableData.nodes.length > 0 ? (
            <>
              <Table data={tableData} showStripedRows={true}>
                {(orderItems) => {
                  return (
                    <>
                      <TableHeader>
                        <TableHeaderRow>
                          {ORDER_LIST_COLUMNS.map(({ label }) => (
                            <TableHeaderCell key={label}>{label}</TableHeaderCell>
                          ))}
                        </TableHeaderRow>
                      </TableHeader>
                      <TableBody>
                        {orderItems.map((order, index) => (
                          <TableRow key={index} item={order}>
                            {ORDER_LIST_COLUMNS.map(({ label, value }) => (
                              <TableCell key={label}>{value(order)}</TableCell>
                            ))}
                          </TableRow>
                        ))}
                      </TableBody>
                    </>
                  );
                }}
              </Table>
              <Box>
                <Box position="absolute" paddingLeft="spacing.5" paddingTop="spacing.1">
                  <Text size="small" color="surface.text.gray.muted">{`Total ${
                    orders?.total_count || 0
                  } Orders`}</Text>
                </Box>
                <Pagination
                  next={handleNext}
                  prev={handlePrev}
                  listData={orders?.items || []}
                  skip={skip}
                  count={LIST_FETCH_BATCH_SIZE}
                />
              </Box>
            </>
          ) : (
            <Box display="flex" alignItems="center" justifyContent="center">
              <EmptyListWithTableRow
                colSpan={8}
                description={
                  <React.Fragment>
                    <div>There are no orders yet!!</div>
                    <div>Start creating new orders now.</div>
                  </React.Fragment>
                }
              />
            </Box>
          )}
        </div>
      </PageLayout>
    </Wrapper>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
});

export default connect(mapStateToProps)(Orders);

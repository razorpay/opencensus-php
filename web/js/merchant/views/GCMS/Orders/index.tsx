import React, { useState } from 'react';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { Amount, Badge, Box, Text, Title } from '@razorpay/blade/components';
import TableBody from 'common/ui/TableBody';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { NavLink } from 'react-router-dom';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';
import { useQuery } from '@tanstack/react-query';
import { LIST_FETCH_BATCH_SIZE, fetchOrders } from './queries';
import Spinner from 'common/ui/Spinner';
import OrdersFilter from './OrdersFilters';
import { ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import moment from 'moment';
import { connect } from 'react-redux';
import { ModeT } from 'common/services/mode';

const ORDER_LIST_COLUMNS = [
  {
    label: 'Order ID',
    value: (order) => (
      <NavLink key={order.orderId} to={`#`}>
        {order.id}
      </NavLink>
    ), //TODO: replace with actual order details link
  },
  {
    label: 'Order Date',
    value: (order) => <Text>{moment(order.created_at).format('Do MMM, YYYY')}</Text>,
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
    value: (order) => <Amount value={parseInt(order.total_amount, 10)} />,
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
  const [orderStatus, setOrderStatus] = useState('');
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();

  const { isLoading, data: orders } = useQuery({
    queryKey: ['wallet:orders', skip, resellerName, orderStatus, fromDate, toDate],
    queryFn: () => fetchOrders({ skip, resellerName, orderStatus, fromDate, toDate, mode }),
  });

  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  const handleSearch = ({ resellerName, status, date }) => {
    setResellerName(resellerName);
    setOrderStatus(status);
    setFromDate(date.from);
    setToDate(date.to);
  };

  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box>
          <Title color="surface.text.subtle.lowContrast">Orders</Title>
        </Box>
        {/**
         * TODO: layout breaks on small screens
         */}
        {/* <Box
          display={'flex'}
          flex={1}
          backgroundColor={'brand.gray.400.lowContrast'}
          paddingY={'spacing.4'}
          width={'100%'}
        >
          <MetricsOverview title="Monthly Volume" b2bValue={26839} b2cValue={3764} />
          <MetricsOverview title="Discount Burn" b2bValue={15672} b2cValue={8372} />
          <MetricsOverview title="Breakage" b2bValue={74873} b2cValue={2992} />
        </Box> */}
        {/* <Box
          backgroundColor={'transparent'}
          marginY={'spacing.4'}
          display={'flex'}
          justifyContent={'space-between'}
        >
          <Box
            width={{
              l: '50%',
              m: '80%',
              s: '80%',
            }}
          >
            <TextInput placeholder="Search" icon={SearchIcon} />
          </Box>
          <IconButton
            size="large"
            accessibilityLabel="filter"
            icon={FilterIcon}
            onClick={() => {}}
          />
        </Box> */}
        <div className="content">
          <OrdersFilter onSearch={handleSearch} />
          {isLoading ? (
            <div className="page-spinner-container">
              <Spinner center />
            </div>
          ) : (
            <>
              <div className="table-responsive">
                <table className="table table-hover">
                  <thead>
                    <tr>
                      {ORDER_LIST_COLUMNS.map(({ label }) => (
                        <th key={label}>{label}</th>
                      ))}
                    </tr>
                  </thead>
                  <TableBody
                    isLoading={isLoading}
                    colSpan={8}
                    rows={orders?.items || []}
                    emptyTableRow={() => (
                      <EmptyListWithTableRow
                        colSpan={8}
                        description={
                          <React.Fragment>
                            <div>There are no orders yet!!</div>
                            <div>Start creating new orders now.</div>
                          </React.Fragment>
                        }
                      />
                    )}
                  >
                    {orders?.items?.map((order) => (
                      <EntityItemRow key={order.id} id={order.id}>
                        {ORDER_LIST_COLUMNS.map(({ label, value }) => (
                          <td
                            style={{ paddingTop: 16, paddingBottom: 16 }}
                            key={`${order.id} + ${label}`}
                          >
                            {value(order)}
                          </td>
                        ))}
                      </EntityItemRow>
                    ))}
                  </TableBody>
                </table>
              </div>
              <Pagination
                next={handleNext}
                prev={handlePrev}
                listData={orders?.items || []}
                skip={skip}
                count={LIST_FETCH_BATCH_SIZE}
              />
            </>
          )}
        </div>
      </div>
    </Wrapper>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
});

export default connect(mapStateToProps)(Orders);

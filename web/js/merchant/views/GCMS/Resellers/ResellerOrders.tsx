import React, { useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useParams } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import TableBody from 'common/ui/TableBody';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import ResellerOrdersFilter from 'merchant/views/GCMS/Orders/OrdersFilters';
import { fetchResellerOrders } from 'merchant/views/GCMS/Orders/queries';
import { RESELLER_ORDER_LIST_COLUMNS } from 'merchant/views/GCMS/Resellers/constants';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import { LIST_FETCH_BATCH_SIZE } from './queries';

const ResellerOrders = ({ mode }: { mode: ModeT }) => {
  const [skip, setSkip] = useState(0);
  const [resellerName, setResellerName] = useState('');
  const [orderStatus, setOrderStatus] = useState('');
  const [orderId, setOrderId] = useState('');
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();
  const { resellerId } = useParams<{ resellerId: string }>();

  const { isLoading, data: orders } = useQuery({
    queryKey: [
      'wallet:reseller:orders',
      skip,
      resellerName,
      orderStatus,
      fromDate,
      toDate,
      orderId,
    ],
    queryFn: () =>
      fetchResellerOrders({ skip, orderStatus, fromDate, toDate, mode, resellerId, orderId }),
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

  return (
    <div>
      <div className="content">
        <ResellerOrdersFilter onSearch={handleSearch} isResellerOrderFilter={true} />
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        ) : (
          <>
            <div className="table-responsive">
              <table className="table table-hover">
                <thead>
                  <tr>
                    {RESELLER_ORDER_LIST_COLUMNS.map(({ label }) => (
                      <th key={label} style={{ paddingLeft: 24 }}>
                        {label}
                      </th>
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
                        </React.Fragment>
                      }
                    />
                  )}
                >
                  {orders?.items?.map((order) => (
                    <EntityItemRow key={order.id} id={order.id}>
                      {RESELLER_ORDER_LIST_COLUMNS.map(({ label, value }) => (
                        <td
                          style={{
                            paddingTop: 16,
                            paddingBottom: 16,
                            paddingLeft: 24,
                            paddingRight: 16,
                          }}
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
            <Box>
              <Box position="absolute" paddingLeft="spacing.5" paddingTop="spacing.1">
                <Text size="small" color="surface.text.subdued.lowContrast">{`Total ${
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
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
});

export default connect(mapStateToProps)(ResellerOrders);

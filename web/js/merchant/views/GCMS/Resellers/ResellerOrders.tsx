import React, { useState } from 'react';
import {
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
import { useParams } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
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
  const tableData = {
    nodes: orders?.items ?? [],
  };
  return (
    <div>
      <div className="content">
        <ResellerOrdersFilter onSearch={handleSearch} isResellerOrderFilter={true} />

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
                        {RESELLER_ORDER_LIST_COLUMNS.map(({ label }) => (
                          <TableHeaderCell key={label}>{label}</TableHeaderCell>
                        ))}
                      </TableHeaderRow>
                    </TableHeader>
                    <TableBody>
                      {orderItems.map((order, index) => (
                        <TableRow key={index} item={order}>
                          {RESELLER_ORDER_LIST_COLUMNS.map(({ label, value }) => (
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
            <EmptyListWithTableRow colSpan={8} description={<div>There are no orders yet!!</div>} />
          </Box>
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
});

export default connect(mapStateToProps)(ResellerOrders);

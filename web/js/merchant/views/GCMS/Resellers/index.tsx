import React, { useEffect, useState } from 'react';
import {
  Badge,
  Box,
  Text,
  Heading,
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
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import ResellersFilter from './ResellersFilters';
import { trackResellerDetailsPageClicked, trackResellersPageLoadSuccess } from './events';
import { fetchResellers, LIST_FETCH_BATCH_SIZE } from './queries';

const merchant_name = {
  title: 'Reseller Name',
  value: (item) => (
    <NavLink
      key={item.merchant_Id}
      to={`${item.merchant_id}`}
      state={{ prevPath: location?.pathname }}
      onClick={() =>
        trackResellerDetailsPageClicked({
          resellerId: item.merchant_id,
          resellerName: item.merchant_name,
        })
      }
    >
      {item.merchant_name}
    </NavLink>
  ),
};
const merchant_id = {
  title: 'Reseller ID',
  value: (item) => <Text>{item.merchant_id}</Text>,
};
const eligible_programs = {
  title: 'Eligible Programs',
  value: (item) => <Text>{item.eligible_programs}</Text>,
};
const order_count = {
  title: 'Order Count',
  value: (item) => <Text>{item.order_count}</Text>,
};
const aggregate_order_value = {
  title: 'Aggregate Order Value',
  value: (item) => <Text>{getFormattedAmountNew(item.aggregate_order_value || 0, 10)}</Text>,
};

const status = {
  title: 'Status',
  value: (item) => (
    <Badge color={RESELLERS_STATUS[item.status].color}>{RESELLERS_STATUS[item.status].label}</Badge>
  ),
};

const resellerListColumns = [
  merchant_name,
  merchant_id,
  eligible_programs,
  order_count,
  aggregate_order_value,
  status,
];

const Resellers = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  const [skip, setSkip] = useState(0);
  const [resellerName, setResellerName] = useState('');
  const [resellerStatus, setResellerStatus] = useState('');

  const { isLoading, data: resellers } = useQuery({
    queryKey: ['gcms:resellers', skip, resellerName, resellerStatus],
    queryFn: () => fetchResellers({ skip, resellerName, resellerStatus, mode, merchantId }),
  });
  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  const handleSearch = ({ resellerName, status }) => {
    setResellerName(resellerName);
    setResellerStatus(status);
  };
  useEffect(() => {
    trackResellersPageLoadSuccess();
  }, []);
  const tableData = {
    nodes: resellers?.items ?? [],
  };

  return (
    <Wrapper>
      <div className="tabbed-container">
        <Box marginBottom="spacing.5">
          <Heading color="surface.text.gray.subtle" size="large">
            Reseller
          </Heading>
        </Box>

        <div className="content">
          <ResellersFilter onSearch={handleSearch} />
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
                          {resellerListColumns.map(({ title }) => (
                            <TableHeaderCell key={title}>{title}</TableHeaderCell>
                          ))}
                        </TableHeaderRow>
                      </TableHeader>
                      <TableBody>
                        {orderItems.map((order, index) => (
                          <TableRow key={index} item={order}>
                            {resellerListColumns.map(({ title, value }) => (
                              <TableCell key={title}>{value(order)}</TableCell>
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
                    resellers?.total_count || 0
                  } Resellers`}</Text>
                </Box>
                <Pagination
                  next={handleNext}
                  prev={handlePrev}
                  listData={resellers?.items || []}
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
                    <div>There are no resellers yet!!</div>
                    <div>Start creating new resellers now.</div>
                  </React.Fragment>
                }
              />
            </Box>
          )}
        </div>
      </div>
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(Resellers);

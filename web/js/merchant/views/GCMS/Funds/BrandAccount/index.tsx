import React, { useState, useEffect } from 'react';
import {
  Box,
  Text,
  Divider,
  TableBody,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import Shimmer from 'common/components/Shimmer';
import Spinner from 'common/ui/Spinner';
import { useStore } from 'shell/commonStore';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import store from 'merchant/store';
import { ACCOUNT_ID_SUFFIX } from 'merchant/views/GCMS/Funds/constants';
import { trackFundsPageLoadSuccess } from 'merchant/views/GCMS/Funds/events';
import {
  fetchBrandBalance,
  fetchBrandTransactions,
  LIST_FETCH_BATCH_SIZE,
} from 'merchant/views/GCMS/Funds/queries';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import BrandAccountFilters from './brandAccountFilters';
import { convertUnixToShortDate } from '../../shared/utils';

const brandTrasactionColumns = [
  {
    label: 'Transaction Id',
    value: (transaction) => <Text>{transaction.id}</Text>,
  },
  {
    label: 'Date and Time',
    value: (transaction) => <Text>{convertUnixToShortDate(transaction.created_at)}</Text>,
  },
  {
    label: 'Reference Id',
    value: (transaction) => <Text>{transaction.reference_id}</Text>,
  },
  {
    label: 'Amount',
    value: (transaction) => (
      <Text color="surface.text.gray.subtle">
        {getFormattedAmountNew(parseInt(transaction.amount, 10), true)}
      </Text>
    ),
  },
  {
    label: 'Type',
    value: (transaction) => <Text>{transaction.type}</Text>,
  },
];

const BrandAccount = (): JSX.Element => {
  const session = useStore((state) => state.session);
  const mode = session.mode;
  const [skip, setSkip] = useState(0);
  const [referenceId, setReferenceId] = useState('');
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();
  const [brandAccountId, setBrandAccountId] = useState('');
  const merchantId = store.getState()?.session?.user?.current;

  const { isLoading: isFetchTransactionsLoading, data: transactions } = useQuery({
    queryKey: ['gcms:brandAccount', skip, referenceId, fromDate, toDate],
    queryFn: () =>
      fetchBrandTransactions({
        skip,
        reference_id: referenceId,
        from: fromDate,
        to: toDate,
        issuing_account_id: `${ACCOUNT_ID_SUFFIX}${brandAccountId}`,
      }),
    enabled: Boolean(brandAccountId),
  });

  const { isLoading: isFetchBrandBalanceLoading, data: brandBalanceData } = useQuery({
    queryKey: ['gcms:brandBalance'],
    queryFn: () => fetchBrandBalance({ merchantId, mode }),
  });

  useEffect(() => {
    if (brandBalanceData?.account_id) {
      setBrandAccountId(brandBalanceData.account_id);
    }
  }, [brandBalanceData?.account_id]);

  useEffect(() => {
    trackFundsPageLoadSuccess();
  }, []);

  const handleNext = () => {
    setSkip(skip + LIST_FETCH_BATCH_SIZE);
  };

  const handlePrev = () => {
    setSkip(skip - LIST_FETCH_BATCH_SIZE);
  };

  const handleSearch = ({ referenceId, date }) => {
    setReferenceId(referenceId);
    setFromDate(date.from);
    setToDate(date.to);
  };
  const tableData = {
    nodes: transactions?.items ?? [],
  };

  return (
    <Wrapper>
      <div className="tabbed-container" style={{ marginTop: '-16px' }}>
        <div className="content">
          <div className="table-responsive">
            <Box marginX="spacing.5" marginTop="spacing.5">
              <Text weight="regular" color="surface.text.gray.subtle" size="large">
                Total Available Fund
              </Text>
              {isFetchBrandBalanceLoading ? (
                <Box>
                  <Shimmer height="30px" width="140px" />
                </Box>
              ) : (
                <Text
                  color="surface.text.gray.subtle"
                  marginTop="spacing.2"
                  size="large"
                  weight="semibold"
                >
                  {getFormattedAmountNew(brandBalanceData?.balance ?? 0, true)}
                </Text>
              )}
            </Box>
            <Divider marginTop="spacing.4" />
            <Text
              color="surface.text.gray.normal"
              marginY="spacing.4"
              marginX="spacing.5"
              size="large"
            >
              Fund Transaction
            </Text>
            <Divider />
            <BrandAccountFilters onSearch={handleSearch} />
            {isFetchTransactionsLoading ? (
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
                            {brandTrasactionColumns.map(({ label }) => (
                              <TableHeaderCell key={label}>{label}</TableHeaderCell>
                            ))}
                          </TableHeaderRow>
                        </TableHeader>
                        <TableBody>
                          {orderItems.map((order, index) => (
                            <TableRow key={index} item={order}>
                              {brandTrasactionColumns.map(({ label, value }) => (
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
                      transactions?.count || 0
                    } Transactions`}</Text>
                  </Box>
                  <Pagination
                    next={handleNext}
                    prev={handlePrev}
                    listData={transactions?.items || []}
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
                      <div>There are no transactions yet!!</div>
                      <div>Start creating new transactions now.</div>
                    </React.Fragment>
                  }
                />
              </Box>
            )}
          </div>
        </div>
      </div>
    </Wrapper>
  );
};

export default BrandAccount;

import React, { useState } from 'react';
import { Box, Text, Amount, Heading, Divider } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import moment from 'moment';

import Shimmer from 'common/components/Shimmer';
import TableBody from 'common/ui/TableBody';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import store from 'merchant/store';
import {
  fetchBrandBalance,
  fetchBrandTransactions,
  LIST_FETCH_BATCH_SIZE,
} from 'merchant/views/GCMS/Funds/queries';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';

import BrandAccountFilters from './brandAccountFilters';

const brandTrasactionColumns = [
  {
    label: 'Transaction Id',
    value: (transaction) => <Text>{transaction.id}</Text>,
  },
  {
    label: 'Date and Time',
    value: (transaction) => <Text>{moment(transaction.created_at).format('Do MMM, YYYY')}</Text>,
  },
  {
    label: 'Reference Id',
    value: (transaction) => <Text>{transaction.reference_id}</Text>,
  },
  {
    label: 'Amount',
    value: (transaction) => <Amount value={parseInt(transaction.amount, 10)} />,
  },
  {
    label: 'Type',
    value: (transaction) => <Text>{transaction.type}</Text>,
  },
];

const BrandAccount = () => {
  const [skip, setSkip] = useState(0);
  const [referenceId, setReferenceId] = useState('');
  const [fromDate, setFromDate] = useState();
  const [toDate, setToDate] = useState();
  const merchantId = store.getState()?.session?.user?.current;

  const { isLoading: isFetchTransactionsLoading, data: transactions } = useQuery({
    queryKey: ['wallet:brandAccount', skip, referenceId, fromDate, toDate],
    queryFn: () =>
      fetchBrandTransactions({ skip, reference_id: referenceId, from: fromDate, to: toDate }),
  });

  const { isLoading: isFetchBrandBalanceLoading, data: brandBalanceData } = useQuery({
    queryKey: ['wallet:brandBalance'],
    queryFn: () => fetchBrandBalance({ merchantId, mode: 'test' }),
  });

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

  return (
    <Wrapper>
      <div className="tabbed-container" style={{ marginTop: '-16px' }}>
        <div className="content">
          <div className="table-responsive">
            <Box marginX="spacing.5" marginTop="spacing.5">
              <Heading
                type="normal"
                weight="regular"
                variant="regular"
                size="small"
                color="surface.text.subtle.lowContrast"
              >
                Total Available Fund
              </Heading>
              {isFetchBrandBalanceLoading ? (
                <Box>
                  <Shimmer height="30px" width="140px" />
                </Box>
              ) : (
                <Amount
                  size="heading-large-bold"
                  marginTop="spacing.2"
                  value={brandBalanceData?.balance ? brandBalanceData.balance : 0}
                />
              )}
            </Box>
            <Divider marginTop="spacing.4" />
            <Heading
              type="normal"
              variant="regular"
              size="small"
              color="surface.text.normal.lowContrast"
              marginY="spacing.4"
              marginX="spacing.5"
            >
              Fund Transaction
            </Heading>
            <Divider />
            <BrandAccountFilters onSearch={handleSearch} />
            <table className="table table-hover">
              <thead>
                <tr>
                  {brandTrasactionColumns.map(({ label }) => (
                    <th key={label} style={{ paddingLeft: 16 }}>
                      {label}
                    </th>
                  ))}
                </tr>
              </thead>
              <TableBody
                isLoading={isFetchTransactionsLoading}
                colSpan={8}
                rows={transactions?.items || []}
                emptyTableRow={() => (
                  <EmptyListWithTableRow
                    colSpan={8}
                    description={<div>There are no transactions yet</div>}
                  />
                )}
              >
                {transactions?.items?.map((transaction) => (
                  <EntityItemRow key={transaction.id} id={transaction.id}>
                    {brandTrasactionColumns.map(({ label, value }) => (
                      <td
                        style={{
                          paddingTop: 16,
                          paddingBottom: 16,
                          paddingLeft: 16,
                          paddingRight: 16,
                        }}
                        key={`${transaction.id} + ${label}`}
                      >
                        {value(transaction)}
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
            listData={transactions?.items || []}
            skip={skip}
            count={LIST_FETCH_BATCH_SIZE}
          />
        </div>
      </div>
    </Wrapper>
  );
};

export default BrandAccount;

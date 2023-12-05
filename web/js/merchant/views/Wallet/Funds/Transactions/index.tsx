import React, { useContext, useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import DataTable from 'common/ui/Table/DataTable';
import AmountCard from 'merchant/views/Wallet/Funds/components/AmountCard';
import { Box, Heading, Text } from '@razorpay/blade/components';

import { fetchFundsSummary, fetchFundTransactions } from 'merchant/views/Wallet/Funds/queries';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';

import type { ListApiResponse } from 'merchant/views/Wallet/types';
import type { FundsSummary, Transaction } from 'merchant/views/Wallet/Funds/types';
import { AMOUNT, ID, REFERENCE_ID, CREATED_AT, TYPE } from './constants';

export const Transactions = (): JSX.Element => {
  const { mode, merchant_id } = useContext<WalletSession>(SessionContext);
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });

  const transactions = useQuery<ListApiResponse<Transaction>, Error>({
    queryKey: ['wallet:funds:transactions', mode, paginationState],
    queryFn: () => fetchFundTransactions({ ...paginationState, mode }),
  });

  const fundsSummary = useQuery<FundsSummary, Error>({
    queryKey: ['wallet:funds:summary', mode, merchant_id],
    queryFn: () => fetchFundsSummary({ mode, merchantId: merchant_id }),
  });

  return (
    <>
      <Box padding={['spacing.5', 'spacing.7', 'spacing.8', 'spacing.7']}>
        <Heading size="small" weight="bold">
          Transactions Info
        </Heading>
        <Box marginTop="spacing.6" flex={1}>
          {!!fundsSummary.error?.message ? (
            <Text color="feedback.text.negative.lowContrast">{fundsSummary.error?.message}</Text>
          ) : (
            <AmountCard
              label="Balance"
              isLoading={fundsSummary.isLoading}
              currency="INR"
              amount={fundsSummary.data?.available_balance || 0}
            />
          )}
        </Box>
      </Box>
      <div className="content-wrapper">
        <DataTable
          title="Transactions"
          columns={[ID, CREATED_AT, AMOUNT, TYPE, REFERENCE_ID]}
          count={paginationState.count}
          skip={paginationState.skip}
          paginate={setPagination}
          error={transactions.error?.message}
          items={transactions.data?.items || []}
          loading={transactions.isLoading}
          hasMoreData={transactions.data?.has_more ?? true}
        />
      </div>
    </>
  );
};

export default Transactions;

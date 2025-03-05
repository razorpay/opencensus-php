import React, { useContext, useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import DataTable from 'common/ui/Table/DataTable';
import AmountCard from 'merchant/views/Wallet/Funds/components/AmountCard';
import { fetchFundTransactions, fetchFundsSummary } from 'merchant/views/Wallet/Funds/queries';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';

import { AMOUNT, CREATED_AT, ID, REFERENCE_ID, TYPE } from './constants';

import type { FundsSummary, Transaction } from 'merchant/views/Wallet/Funds/types';
import type { ListApiResponse } from 'merchant/views/Wallet/types';

export const Transactions = (): JSX.Element => {
  const { mode, merchant_id } = useContext<WalletSession>(SessionContext);
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });

  {
    /**
     * TODO: Uncomment once backend fixes the API. Slack: https://razorpay.slack.com/archives/C34U44N5Q/p1737619157741209?thread_ts=1733222346.707809&cid=C34U44N5Q
     */
  }
  // const transactions = useQuery<ListApiResponse<Transaction>, Error>({
  //   queryKey: ['wallet:funds:transactions', mode, paginationState],
  //   queryFn: () => fetchFundTransactions({ ...paginationState, mode }),
  // });

  const fundsSummary = useQuery<FundsSummary, Error>({
    queryKey: ['wallet:funds:summary', mode, merchant_id],
    queryFn: () => fetchFundsSummary({ mode, merchantId: merchant_id }),
  });

  return (
    <>
      <Box padding={['spacing.5', 'spacing.7', 'spacing.8', 'spacing.7']}>
        <Text weight="semibold" size="large">
          Transactions Info
        </Text>
        <Box marginTop="spacing.6" flex={1}>
          {!!fundsSummary.error?.message ? (
            <Text color="feedback.text.negative.intense">{fundsSummary.error?.message}</Text>
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
      {/**
       * TODO: Uncomment once backend fixes the API
       */}
      {/* <div className="content-wrapper">
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
      </div> */}
    </>
  );
};

export default Transactions;

import moment from 'moment';
import { useQuery } from 'react-query';
import React, { useContext, useEffect, useState } from 'react';

import DataTable from 'common/ui/Table/DataTable';
import Filters from 'merchant/views/Wallet/Transactions/Filters';

import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import { fetchTransactions } from 'merchant/views/Wallet/queries';

import { ListApiResponse, Transaction, TransactionFilterParams } from 'merchant/views/Wallet/types';
import { ID, ACCOUNT_ID, AMOUNT, CREATED_AT, TYPE, SOURCE, REFERENCE_ID } from './constants';

export const Transactions = (): JSX.Element => {
  const { mode } = useContext<WalletSession>(SessionContext);
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });
  const [filters, setFilters] = useState<TransactionFilterParams>({
    from: moment().subtract(7, 'days').unix(),
    to: moment().unix(),
  });

  useEffect(() => {
    setPagination({
      skip: 0,
      count: 25,
    });
  }, [filters]);

  const { isLoading, data, error } = useQuery<ListApiResponse<Transaction>, Error>({
    queryKey: ['wallet:transactions', mode, paginationState],
    queryFn: () => fetchTransactions({ ...filters, ...paginationState, mode }),
  });

  return (
    <div className="content-wrapper">
      <Filters onSubmit={setFilters} />
      <DataTable
        title="Transactions"
        columns={[ID, AMOUNT, TYPE, REFERENCE_ID, ACCOUNT_ID, SOURCE, CREATED_AT]}
        count={paginationState.count}
        skip={paginationState.skip}
        paginate={setPagination}
        error={error?.message}
        items={data?.items || []}
        loading={isLoading}
        hasMoreData={data?.has_more ?? true}
      />
    </div>
  );
};

export default Transactions;

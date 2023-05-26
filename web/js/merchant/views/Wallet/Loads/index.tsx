import React, { useContext, useEffect, useState } from 'react';
import { useQuery } from 'react-query';

import * as items from 'common/ui/item';
import { idItem } from 'common/ui/item/id';
import DataTable from 'common/ui/Table/DataTable';
import { Badge } from '@razorpay/blade/components';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/AccountDetail/constants';

import { SessionContext } from 'merchant/views/Wallet/context';
import { fetchLoads } from 'merchant/views/Wallet/queries';

import type {
  ListApiResponse,
  WalletLoad,
  Column,
  LoadsFilterParams,
} from 'merchant/views/Wallet/types';
import Filters from 'merchant/views/Wallet/Loads/Filters';
import { toTitleCase } from '@razorpay/blade/utils';

const id: LoadColumn = {
  title: 'Load Id',
  value: (item) => idItem(item.id),
};

const amount: LoadColumn = {
  title: 'Amount',
  value: items.getAmount('amount'),
};

const type: LoadColumn = {
  title: 'Type',
  value: (item) => <div>{item.user_load ? 'User' : 'Merchant'}</div>,
};

const created_at: LoadColumn = {
  title: 'Created At',
  value: items.createdAtShort,
};

const statusCol: LoadColumn = {
  title: 'Status',
  value: (item) => <Badge {...STATUS_BADGE_PROPS[item.status]}>{toTitleCase(item.status)}</Badge>,
};

const description: LoadColumn = {
  title: 'Description',
  value: (item) => <div>{item.description}</div>,
};

const failure_reason: LoadColumn = {
  title: 'Failure Reason',
  value: (item) => <div>{item.failure_reason}</div>,
};

const notes: LoadColumn = {
  title: 'Notes',
  value: (item) => <div>{item.notes}</div>,
};

type LoadColumn = Column<WalletLoad>;

const Loads = (): JSX.Element => {
  const { mode } = useContext(SessionContext);
  const [paginationState, setPaginationState] = useState({
    skip: 0,
    count: 25,
  });
  const [filters, setFilters] = useState<LoadsFilterParams>({ accountId: '' });

  const urlQuery = new URLSearchParams(location.search);
  const accountId = urlQuery.get('accountId') || undefined;

  useEffect(() => {
    setPaginationState({
      skip: 0,
      count: 25,
    });
  }, [filters]);

  const { data, isLoading, error } = useQuery<ListApiResponse<WalletLoad>, Error>({
    queryKey: ['wallet:loads', mode, paginationState, filters],
    queryFn: () =>
      fetchLoads({
        account_id: accountId,
        mode,
        count: paginationState.count,
        skip: paginationState.skip,
      }),
  });

  return (
    <div className="content-wrapper">
      <Filters onSubmit={setFilters} />
      <DataTable
        title="Loads"
        count={paginationState.count}
        skip={paginationState.skip}
        error={error?.message}
        columns={[id, created_at, amount, type, description, statusCol, failure_reason, notes]}
        hasMoreData={(data?.count || 0) > paginationState.skip + (data?.items?.length || 0)}
        loading={isLoading}
        items={data?.items || []}
        paginate={setPaginationState}
      />
    </div>
  );
};

export default Loads;

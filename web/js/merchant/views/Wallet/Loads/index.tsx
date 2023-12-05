import React, { useContext, useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import moment from 'moment';

import * as items from 'common/ui/item';
import { idItem } from 'common/ui/item/id';
import DataTable from 'common/ui/Table/DataTable';
import { Badge, Box } from '@razorpay/blade/components';
import { withNoWrap } from 'merchant/views/Wallet/styled';
import Filters from 'merchant/views/Wallet/Loads/Filters';

import { SessionContext } from 'merchant/views/Wallet/context';
import { fetchLoads } from 'merchant/views/Wallet/queries';
import { toTitleCase } from 'common/utils';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/AccountDetail/constants';

import type {
  DashboardListApiResponse,
  WalletLoad,
  Column,
  LoadsFilterParams,
} from 'merchant/views/Wallet/types';

const id: LoadColumn = {
  title: withNoWrap('Load Id'),
  value: (item) => idItem(item.id),
};

const amount: LoadColumn = {
  title: withNoWrap('Amount'),
  value: items.getAmount('amount'),
};

const type: LoadColumn = {
  title: withNoWrap('Type'),
  value: (item) => <div>{item.user_load ? 'User' : 'Merchant'}</div>,
};

const created_at: LoadColumn = {
  title: withNoWrap('Created At'),
  value: items.createdAtShort,
};

const statusCol: LoadColumn = {
  title: withNoWrap('Status'),
  value: (item) => <Badge {...STATUS_BADGE_PROPS[item.status]}>{toTitleCase(item.status)}</Badge>,
};

const description: LoadColumn = {
  title: withNoWrap('Description'),
  value: (item) => <div>{item.description}</div>,
};

const failure_reason: LoadColumn = {
  title: withNoWrap('Failure Reason'),
  value: (item) => <div>{item.failure_reason}</div>,
};

const notes: LoadColumn = {
  title: withNoWrap('Notes'),
  value: (item) => <div>{item.notes}</div>,
};

const accountIdCol: LoadColumn = {
  title: withNoWrap('Account Id'),
  value: (item) => <div>{item.account_id}</div>,
};

const contact: LoadColumn = {
  title: withNoWrap('Contact'),
  value: (item) => <Box>{item.contact}</Box>,
};

const referenceId: LoadColumn = {
  title: withNoWrap('Reference Id'),
  value: (item) => <Box>{item.reference_id}</Box>,
};

type LoadColumn = Column<WalletLoad>;

const Loads = (): JSX.Element => {
  const { mode } = useContext(SessionContext);
  const [paginationState, setPaginationState] = useState({
    skip: 0,
    count: 25,
  });
  const [filters, setFilters] = useState<LoadsFilterParams>({
    account_id: '',
    from: moment().subtract(7, 'days').unix(),
    to: moment().unix(),
  });

  const urlQuery = new URLSearchParams(location.search);
  const accountId = urlQuery.get('accountId') || filters.account_id;

  useEffect(() => {
    setPaginationState({
      skip: 0,
      count: 25,
    });
  }, [filters]);

  const { data, isLoading, error } = useQuery<
    DashboardListApiResponse<{ recharges: WalletLoad[] }>,
    Error
  >({
    queryKey: ['wallet:loads', accountId, mode, paginationState, filters],
    queryFn: () =>
      fetchLoads({
        ...filters,
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
        columns={[
          id,
          amount,
          type,
          statusCol,
          contact,
          accountIdCol,
          referenceId,
          description,
          created_at,
          failure_reason,
          notes,
        ]}
        hasMoreData={true}
        loading={isLoading}
        items={data?.entities.recharges || []}
        paginate={setPaginationState}
      />
    </div>
  );
};

export default Loads;

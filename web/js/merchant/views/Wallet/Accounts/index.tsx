import React, { useContext, useState } from 'react';

import * as items from 'common/ui/item';
import { idLink } from 'common/ui/item/id';
import DataTable from 'common/ui/Table/DataTable';
import { Badge } from '@razorpay/blade/components';
import { useQuery } from 'react-query';

import { fetchAccounts } from 'merchant/views/Wallet/queries';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/Accounts/constants';

import type { Account, ListApiResponse } from 'merchant/views/Wallet/types';

const id = {
  title: 'Account Id',
  value: (item) =>
    idLink(item.account_id, item.account_id, undefined, undefined, {
      type: 'account',
    }),
};

const created_at = {
  title: 'Created At',
  value: items.createdAtShort,
};

const balance = {
  title: 'Available Balance',
  value: items.getAmount('balance'),
};

const account_holder_name = {
  title: 'Account Holder Name',
  value: (item) => <div>{item.account_holder_name}</div>,
};

const mobile_number = {
  title: 'Mobile Number',
  value: (item) => <div>{item.contact}</div>,
};

const statusCol = {
  title: 'Status',
  value: (item) => <Badge {...STATUS_BADGE_PROPS[item.status]} />,
};

const program_name = {
  title: 'Program Name',
  value: (item) => <div>{item.program}</div>,
};

export const Accounts = (): JSX.Element => {
  const { mode } = useContext<WalletSession>(SessionContext);
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });

  const { isLoading, data, error } = useQuery<ListApiResponse<Account>, Error>({
    queryKey: ['wallet:accounts', mode, paginationState],
    queryFn: () => fetchAccounts({ ...paginationState, mode }),
  });

  return (
    <DataTable
      title="Accounts"
      columns={[
        id,
        created_at,
        account_holder_name,
        mobile_number,
        program_name,
        statusCol,
        balance,
      ]}
      count={paginationState.count}
      skip={paginationState.skip}
      paginate={setPagination}
      error={error?.message}
      items={data?.items || []}
      loading={isLoading}
      hasMoreData={(data?.count || 0) > paginationState.skip + (data?.items?.length || 0)}
    />
  );
};

export default Accounts;

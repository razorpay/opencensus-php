import React, { useContext, useEffect, useState } from 'react';
import { useQuery } from 'react-query';

import DataTable from 'common/ui/Table/DataTable';
import { idItem } from 'common/ui/item/id';
import * as items from 'common/ui/item';
import { Badge } from '@razorpay/blade/components';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/AccountDetail/constants';

import { SessionContext } from 'merchant/views/Wallet/context';
import { fetchPayments } from 'merchant/views/Wallet/queries';

import type {
  Column,
  ListApiResponse,
  WalletPayment,
  PaymentsFilterParams,
} from 'merchant/views/Wallet/types';
import Filters from 'merchant/views/Wallet/Payments/Filters';
import { toTitleCase } from '@razorpay/blade/utils';

type PaymentColumn = Column<WalletPayment>;

const id: PaymentColumn = {
  title: 'Payment Id',
  value: (item) => idItem(item.id),
};

const created_at: PaymentColumn = {
  title: 'Created At',
  value: items.createdAtShort,
};

const amount: PaymentColumn = {
  title: 'Amount',
  value: items.getAmount('amount'),
};

const description: PaymentColumn = {
  title: 'Description',
  value: (item) => <div>{item.description}</div>,
};

const statusCol: PaymentColumn = {
  title: 'Status',
  value: (item) => <Badge {...STATUS_BADGE_PROPS[item.status]}>{toTitleCase(item.status)}</Badge>,
};

const failure_reason: PaymentColumn = {
  title: 'Failure Reason',
  value: (item) => <div>{item.failure_reason}</div>,
};

const notes: PaymentColumn = {
  title: 'Notes',
  value: (item) => <div>{item.notes}</div>,
};

const merchantId: PaymentColumn = {
  title: 'Merchant Id',
  value: (item) => <div>{item.merchant_id}</div>,
};

const Payments = (): JSX.Element => {
  const { mode } = useContext(SessionContext);
  const [paginationState, setPaginationState] = useState({
    skip: 0,
    count: 25,
  });
  const [filters, setFilters] = useState<PaymentsFilterParams>({ accountId: '' });

  const urlQuery = new URLSearchParams(location.search);
  const accountId = urlQuery.get('accountId') || undefined;

  useEffect(() => {
    setPaginationState({
      skip: 0,
      count: 25,
    });
  }, [filters]);

  const { data, isLoading, error } = useQuery<ListApiResponse<WalletPayment>, Error>({
    queryKey: ['wallet:payments', mode, filters, paginationState],
    queryFn: () =>
      fetchPayments({
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
        title="Payments"
        count={paginationState.count}
        skip={paginationState.skip}
        error={error?.message}
        hasMoreData={(data?.count || 0) > paginationState.skip + (data?.items?.length || 0)}
        loading={isLoading}
        items={data?.items || []}
        columns={[
          id,
          created_at,
          amount,
          merchantId,
          description,
          statusCol,
          failure_reason,
          notes,
        ]}
        paginate={setPaginationState}
      />
    </div>
  );
};

export default Payments;

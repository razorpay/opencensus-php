import React, { useContext, useEffect, useState } from 'react';
import { useQuery } from 'react-query';
import moment from 'moment';

import DataTable from 'common/ui/Table/DataTable';
import { idItem } from 'common/ui/item/id';
import * as items from 'common/ui/item';
import { Badge, Box } from '@razorpay/blade/components';
import { withNoWrap } from 'merchant/views/Wallet/styled';
import Filters from 'merchant/views/Wallet/Payments/Filters';

import { SessionContext } from 'merchant/views/Wallet/context';
import { fetchPayments } from 'merchant/views/Wallet/queries';
import { toTitleCase } from 'common/utils';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/AccountDetail/constants';

import type {
  Column,
  DashboardListApiResponse,
  WalletPayment,
  PaymentsFilterParams,
} from 'merchant/views/Wallet/types';

type PaymentColumn = Column<WalletPayment>;

const id: PaymentColumn = {
  title: withNoWrap('Payment Id'),
  value: (item) => idItem(item.id),
};

const created_at: PaymentColumn = {
  title: withNoWrap('Created At'),
  value: items.createdAtShort,
};

const amount: PaymentColumn = {
  title: withNoWrap('Amount'),
  value: items.getAmount('amount'),
};

const description: PaymentColumn = {
  title: withNoWrap('Description'),
  value: (item) => <div>{item.description}</div>,
};

const statusCol: PaymentColumn = {
  title: withNoWrap('Status'),
  value: (item) => <Badge {...STATUS_BADGE_PROPS[item.status]}>{toTitleCase(item.status)}</Badge>,
};

const failure_reason: PaymentColumn = {
  title: withNoWrap('Failure Reason'),
  value: (item) => <div>{item.failure_reason}</div>,
};

const notes: PaymentColumn = {
  title: withNoWrap('Notes'),
  value: (item) => <div>{item.notes}</div>,
};

const merchantId: PaymentColumn = {
  title: withNoWrap('Merchant Id'),
  value: (item) => <div>{item.merchant_id}</div>,
};

const accountIdCol: PaymentColumn = {
  title: withNoWrap('Account Id'),
  value: (item) => <div>{item.account_id}</div>,
};

const contact: PaymentColumn = {
  title: withNoWrap('Contact'),
  value: (item) => <Box>{item.contact}</Box>,
};

const referenceId: PaymentColumn = {
  title: withNoWrap('Reference Id'),
  value: (item) => <Box>{item.reference_id}</Box>,
};

const Payments = (): JSX.Element => {
  const { mode } = useContext(SessionContext);
  const [paginationState, setPaginationState] = useState({
    skip: 0,
    count: 25,
  });
  const [filters, setFilters] = useState<PaymentsFilterParams>({
    account_id: '',
    from: moment().subtract(7, 'days').unix(),
    to: moment().unix(),
  });

  const urlQuery = new URLSearchParams(location.search);
  const accountId = urlQuery?.get('accountId') || filters?.account_id;

  useEffect(() => {
    setPaginationState({
      skip: 0,
      count: 25,
    });
  }, [filters]);

  const { data, isLoading, error } = useQuery<
    DashboardListApiResponse<{ payments: WalletPayment[] }>,
    Error
  >({
    queryKey: ['wallet:payments', accountId, mode, filters, paginationState],
    queryFn: () =>
      fetchPayments({
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
        title="Payments"
        count={paginationState.count}
        skip={paginationState.skip}
        error={error?.message}
        hasMoreData={true}
        loading={isLoading}
        items={data?.entities.payments || []}
        columns={[
          id,
          amount,
          merchantId,
          referenceId,
          statusCol,
          contact,
          accountIdCol,
          created_at,
          description,
          failure_reason,
          notes,
        ]}
        paginate={setPaginationState}
      />
    </div>
  );
};

export default Payments;

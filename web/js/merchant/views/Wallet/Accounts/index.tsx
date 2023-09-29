import BaseModal from 'react-modal';
import styled from 'styled-components';
import { useQuery } from 'react-query';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import React, { useContext, useEffect, useState } from 'react';

import * as items from 'common/ui/item';
import { idLink } from 'common/ui/item/id';
import DataTable from 'common/ui/Table/DataTable';
import Filters from 'merchant/views/Wallet/Accounts/Filters';
import { Badge } from '@razorpay/blade/components';
import AccountDetail from 'merchant/views/Wallet/AccountDetail';
import { withNoWrap } from 'merchant/views/Wallet/styled';

import { toTitleCase } from 'common/utils';
import { fetchAccounts } from 'merchant/views/Wallet/queries';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/Accounts/constants';

import type { Account, ListApiResponse } from 'merchant/views/Wallet/types';

const Modal = styled(BaseModal)`
  width: 600px;
  position: fixed;
  top: 0;
  right: 0;
  margin-left: 0px;
  bottom: 0;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
  z-index: 5;

  .txn-details .panel-heading {
    width: 600px;
  }
`;

const id = {
  title: withNoWrap('Account Id'),
  value: (item) =>
    idLink(item.account_id, item.account_id, undefined, undefined, {
      type: 'account',
    }),
};

const created_at = {
  title: withNoWrap('Created At'),
  value: items.createdAtShort,
};

const balance = {
  title: withNoWrap('Available Balance'),
  value: items.getAmount('balance'),
};

const account_holder_name = {
  title: withNoWrap('Account Holder Name'),
  value: (item) => <div>{item.account_holder_name}</div>,
};

const mobile_number = {
  title: withNoWrap('Mobile Number'),
  value: (item) => <div>{item.contact}</div>,
};

const statusCol = {
  title: withNoWrap('Status'),
  value: (item) => <Badge {...STATUS_BADGE_PROPS[item.status]}>{toTitleCase(item.status)}</Badge>,
};

const program_name = {
  title: withNoWrap('Program Name'),
  value: (item) => <div>{item.program}</div>,
};

const type = {
  title: withNoWrap('Account Type'),
  value: (item) => <div>{item.type}</div>,
};

const userId = {
  title: withNoWrap('User ID'),
  value: (item) => <div>{item.user_id}</div>,
};

const partnerCustomerId = {
  title: withNoWrap('Partner Customer ID'),
  value: (item) => <div>{item.partner_customer_id}</div>,
};

export const Accounts = ({ location, history }: RouteComponentProps): JSX.Element => {
  const [isDetailView, setDetailView] = useState(false);
  const [shouldShowListView, setShouldShowListView] = useState(false);
  const { mode } = useContext<WalletSession>(SessionContext);
  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });
  const [filters, setFilters] = useState({});

  useEffect(() => {
    setPagination({
      skip: 0,
      count: 25,
    });
  }, [filters]);

  const { isLoading, data, error } = useQuery<ListApiResponse<Account>, Error>({
    queryKey: ['wallet:accounts', mode, filters, paginationState],
    queryFn: () => fetchAccounts({ ...filters, ...paginationState, mode }),
  });

  useEffect(() => {
    // Does id exist in path?
    if (/\/wallet\/accounts\/(.)/.test(location.pathname)) {
      setDetailView(true);
    } else {
      setDetailView(false);
      setShouldShowListView(true);
    }
  }, [location.pathname]);

  return shouldShowListView ? (
    <div className="content-wrapper">
      <Filters onSubmit={setFilters} />
      <DataTable
        title="Accounts"
        columns={[
          id,
          userId,
          partnerCustomerId,
          type,
          account_holder_name,
          mobile_number,
          statusCol,
          balance,
          program_name,
          created_at,
        ]}
        count={paginationState.count}
        skip={paginationState.skip}
        paginate={setPagination}
        error={error?.message}
        items={data?.items || []}
        loading={isLoading}
        hasMoreData={data?.has_more ?? true}
      />
      {isDetailView && (
        <Modal
          isOpen={isDetailView}
          closeTimeoutMS={300}
          className="ModalSlider__Content"
          contentLabel="SliderModal"
          ariaHideApp={false}
        >
          <button type="button" className="close close-primary" onClick={history.goBack}>
            <i className="i i-close" />
          </button>
          <AccountDetail />
        </Modal>
      )}
    </div>
  ) : (
    <AccountDetail />
  );
};

export default withRouter(Accounts);

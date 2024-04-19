import React, { useState } from 'react';
import { Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { PaginationParamsType, User } from 'common/typings';
import { ShowNotificationType } from 'common/typings/Store/notifications';
import DataTable from 'common/ui/Table/DataTable';
import { getTime } from 'common/ui/item';
import AllInvitesFilter, {
  getDecodedParams,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';
import { SpinnerContainer } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/styles';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import { FetchInvitesParams, SubmerchantInviteItem, fetchInvites } from './api';
import InviteActionButton from './components/InviteActionButton';

const name = {
  title: 'Name',
  value: (item) => item.name,
};

const emailId = {
  title: 'Email ID',
  value: (item) => item.email,
};

const mobileNumber = {
  title: 'Contact',
  value: (item) => item.contact_no,
};

const lastInvitedOn = {
  title: 'Last Invited On',
  value: getTime('updated_at', 'll'),
};

const actions = {
  title: 'Actions',
  value: (item) => <InviteActionButton productType={PRODUCT_TYPE.PG} invite={item} />,
};

interface AllInvitesTableProps {
  user: User;
  showNotification: ShowNotificationType;
}

const AllInvitesTable = ({ user, showNotification }: AllInvitesTableProps): JSX.Element => {
  const [paginationState, setPagination] = useState<PaginationParamsType>({
    skip: 0,
    count: 25,
  });

  const [items, setItems] = useState<Array<SubmerchantInviteItem>>([]);

  const { isFetching, refetch } = useQuery({
    queryKey: ['filter-submerchant-invites', paginationState],
    queryFn: () =>
      fetchInvites(
        user.id as string,
        {
          product: PRODUCT_TYPE.PG,
          ...paginationState,
          ...getDecodedParams(),
        } as FetchInvitesParams,
      ),
    refetchOnWindowFocus: false,
    onSuccess: (data) => {
      setItems(data.data?.items || []);
    },
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: 'There was an error',
      });
    },
  });

  return (
    <div>
      <AllInvitesFilter
        onSearch={refetch}
        count={paginationState.count}
        setPagination={setPagination}
      />
      {isFetching ? (
        <SpinnerContainer>
          <Spinner testID="all-invites-spinner" accessibilityLabel="spinner" size="xlarge" />
        </SpinnerContainer>
      ) : (
        <DataTable
          title="Invites"
          columns={[name, emailId, mobileNumber, actions, lastInvitedOn]}
          count={paginationState.count}
          skip={paginationState.skip}
          paginate={setPagination}
          loading={isFetching}
          items={items}
        />
      )}
    </div>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(AllInvitesTable);

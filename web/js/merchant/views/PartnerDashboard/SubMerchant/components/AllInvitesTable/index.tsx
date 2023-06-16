import React, { ComponentType, useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import { useQuery } from 'react-query';
import { withRouter } from 'react-router-dom';
import { Spinner } from '@razorpay/blade/components';
import { History, Location } from 'history';
import { showNotification } from 'merchant_common/reducers/notifications';
import DataTable from 'common/ui/Table/DataTable';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  AllInvitesFilter,
  getDecodedParams,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';
import { SpinnerContainer } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/styles';
import { ShowNotificationType } from 'common/typings/Store/notifications';
import { FetchInvitesParams, SubmerchantInviteItem, fetchInvites } from './api';
import InviteActionButton from './components/InviteActionButton';
import { getTime } from 'common/ui/item';
import { User } from 'common/typings';

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

interface AllInvitesTableProps {
  user: User;
  showNotification: ShowNotificationType;
  history: History;
  location: Location;
}

const AllInvitesTable = ({
  user,
  showNotification,
  history,
  location,
}: AllInvitesTableProps): JSX.Element => {
  const actions = {
    title: 'Actions',
    value: (item) => <InviteActionButton showNotification={showNotification} invite={item} />,
  };

  const [paginationState, setPagination] = useState({
    skip: 0,
    count: 25,
  });

  const [items, setItems] = useState<Array<SubmerchantInviteItem>>([]);

  const { isLoading, isFetching, refetch } = useQuery(
    ['filter-submerchant-invites', paginationState],
    () =>
      fetchInvites(
        user.id as string,
        {
          product: PRODUCT_TYPE.PG,
          ...paginationState,
          ...getDecodedParams(),
        } as FetchInvitesParams,
      ),
    {
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
    },
  );

  return (
    <div>
      <AllInvitesFilter
        onSearch={refetch}
        count={paginationState.count}
        location={location}
        history={history}
        setPagination={setPagination}
      />
      {isLoading || isFetching ? (
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
          loading={isLoading || isFetching}
          items={items}
        />
      )}
    </div>
  );
};

export default compose<ComponentType<AllInvitesTableProps>>(
  withRouter,
  connect(
    (state) => ({
      user: state.session.user,
    }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(AllInvitesTable);

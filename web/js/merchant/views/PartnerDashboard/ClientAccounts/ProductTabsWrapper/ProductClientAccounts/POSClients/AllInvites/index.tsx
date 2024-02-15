import React, { useMemo } from 'react';
import { Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ShowNotificationType, User } from 'common/typings';
import DataTableWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import { SpinnerContainer } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/styles';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import FiltersSection from './FiltersSection';
import {
  FetchInvitesParams,
  fetchInvites,
  POSSubmerchantInviteItem,
  FetchInviteResponse,
  fetchPartnerAgentUsers,
  getPosAgentsMap,
  POSAgents,
} from './api';
import { customColumnsGetter } from './columns';

type AllInvitesTableProps = {
  user: User;
  showNotification: ShowNotificationType;
};
const spinnerTestId = 'pos-all-invites-spinner';
const AllInvitesTable = ({ user, showNotification }: AllInvitesTableProps): JSX.Element => {
  const { isFetching, data: partnerAgentsData } = useQuery({
    queryKey: ['partner-agent-users'],
    queryFn: () => fetchPartnerAgentUsers(),
    refetchOnWindowFocus: false,
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: 'There was an error',
      });
    },
  });

  const posAgentsMap = useMemo(
    () => getPosAgentsMap(user, partnerAgentsData),
    [user, partnerAgentsData],
  );

  if (isFetching) {
    return (
      <SpinnerContainer>
        <Spinner testID={spinnerTestId} accessibilityLabel="spinner" size="xlarge" />
      </SpinnerContainer>
    );
  }

  const getColumns = customColumnsGetter({ posAgentsMap });
  const posAgents = (partnerAgentsData?.data?.users || []) as POSAgents;

  const paginationQueryFn = (paginationState, decodedParams) =>
    fetchInvites(
      user.id as string,
      {
        product: PRODUCT_TYPE.POS,
        ...paginationState,
        ...decodedParams,
      } as FetchInvitesParams,
    );

  const parseDataOnSuccess = (data) => data.data?.items || [];
  return (
    <DataTableWrapper<POSSubmerchantInviteItem, FetchInviteResponse>
      getColumns={getColumns}
      paginationQueryFn={paginationQueryFn}
      queryKey="filter-pos-all-invites"
      parseDataOnSuccess={parseDataOnSuccess}
      renderFiltersSection={({ paginationState, setPagination, refetch }) => (
        <FiltersSection
          refetch={refetch}
          posAgents={posAgents}
          paginationState={paginationState}
          setPagination={setPagination}
          user={user}
        />
      )}
      spinnerTestId={spinnerTestId}
    />
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(AllInvitesTable);

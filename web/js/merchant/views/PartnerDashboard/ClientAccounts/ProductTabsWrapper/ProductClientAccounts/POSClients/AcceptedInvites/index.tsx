import React from 'react';
import { connect } from 'react-redux';

import { User } from 'common/typings';
import DataTableWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import {
  FetchSubmerchantsParams,
  fetchSubmerchants,
  PGAcceptedInviteItem,
  FetchInviteResponse,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import useWelcomeScreenData from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/WelcomeScreenContainer/hooks/useWelcomeScreenData';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import CustomEmptyScreen from './CustomEmptyScreen';
import FiltersSection from './FiltersSection';
import { getColumns } from './columns';

type AcceptedInvitesTableProps = {
  user: User;
};
const AcceptedInvitesTable = ({ user }: AcceptedInvitesTableProps): JSX.Element => {
  const { isFilterSearchUsed, setIsAcceptedInvitesEmpty } = useWelcomeScreenData();
  const paginationQueryFn = (paginationState, decodedParams) =>
    fetchSubmerchants({
      product: PRODUCT_TYPE.POS,
      ...paginationState,
      ...decodedParams,
    } as FetchSubmerchantsParams);

  const parseDataOnSuccess = (data) => {
    const parsedData = data.data?.items || [];

    if (!isFilterSearchUsed) setIsAcceptedInvitesEmpty(parsedData.length === 0);
    return parsedData;
  };

  return (
    <DataTableWrapper<PGAcceptedInviteItem, FetchInviteResponse>
      getColumns={getColumns}
      paginationQueryFn={paginationQueryFn}
      queryKey="filter-pos-accepted-invites"
      empty_placeholder={<CustomEmptyScreen isFilterSearchUsed={isFilterSearchUsed} />}
      parseDataOnSuccess={parseDataOnSuccess}
      renderFiltersSection={({ paginationState, setPagination, refetch }) => (
        <FiltersSection
          paginationState={paginationState}
          refetch={refetch}
          setPagination={setPagination}
          user={user}
        />
      )}
      spinnerTestId="pos-accepted-invites-spinner"
    />
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  null,
)(AcceptedInvitesTable);

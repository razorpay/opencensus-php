import React from 'react';
import { connect } from 'react-redux';

import { User } from 'common/typings';
import DataTableWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/DataTableWrapper';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import FiltersSection from './FiltersSection';
import {
  FetchInvitesParams,
  fetchInvites,
  SubmerchantInviteItem,
  FetchInviteResponse,
} from './api';
import { getColumns } from './columns';

type AllInvitesTableProps = {
  user: User;
};
const AllInvitesTable = ({ user }: AllInvitesTableProps): JSX.Element => {
  const paginationQueryFn = (paginationState, decodedParams) =>
    fetchInvites(
      user.id as string,
      {
        product: PRODUCT_TYPE.PG,
        ...paginationState,
        ...decodedParams,
      } as FetchInvitesParams,
    );
  const parseDataOnSuccess = (data) => data.data?.items || [];

  return (
    <DataTableWrapper<SubmerchantInviteItem, FetchInviteResponse>
      getColumns={getColumns}
      paginationQueryFn={paginationQueryFn}
      queryKey="filter-pg-all-invites"
      parseDataOnSuccess={parseDataOnSuccess}
      renderFiltersSection={({ paginationState, setPagination, refetch }) => (
        <FiltersSection
          refetch={refetch}
          paginationState={paginationState}
          setPagination={setPagination}
          user={user}
        />
      )}
      spinnerTestId="pg-all-invites-spinner"
    />
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  null,
)(AllInvitesTable);

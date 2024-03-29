import React, { ReactNode, useState } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { CommonApiResponse, DataTableColumns, PaginationParamsType, User } from 'common/typings';
import { ShowNotificationType } from 'common/typings/Store/notifications';
import DataTable from 'common/ui/Table/DataTable';
import { RenderFiltersSectionProps } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';
import { getDecodedParams } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/utils';
import { SpinnerContainer } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/styles';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import usePartnerDashboardExperiments, {
  PartnerDashboardExperiments,
} from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { showNotification } from 'merchant_common/reducers/notifications';

export type GetColumnsType = (args: {
  user: User;
  org: Org;
  experiments: PartnerDashboardExperiments;
}) => DataTableColumns;
export interface DataTableWrapperProps<ItemType, FetchInviteResponse> {
  empty_placeholder?: ReactNode;
  getColumns: GetColumnsType;
  org: Org;
  paginationQueryFn: (
    paginationState: PaginationParamsType,
    decodedParams: Record<string, string>,
  ) => Promise<FetchInviteResponse>;
  parseDataOnSuccess: (data: FetchInviteResponse) => Array<ItemType>;
  queryKey: string;
  renderFiltersSection: (props: RenderFiltersSectionProps) => ReactNode;
  showNotification: ShowNotificationType;
  spinnerTestId: string;
  title?: string;
  user: User;
}
const DataTableWrapper = <
  ItemType extends Record<string, unknown>,
  FetchInviteResponse extends CommonApiResponse<unknown>,
>({
  empty_placeholder,
  getColumns,
  org,
  paginationQueryFn,
  parseDataOnSuccess,
  queryKey,
  renderFiltersSection,
  showNotification,
  spinnerTestId = 'invites-spinner',
  title = 'Invites',
  user,
}: DataTableWrapperProps<ItemType, FetchInviteResponse>): JSX.Element => {
  const [paginationState, setPagination] = useState<PaginationParamsType>({
    skip: 0,
    count: 25,
  });

  const [items, setItems] = useState<Array<ItemType>>([]);
  const experiments = usePartnerDashboardExperiments();

  const { isFetching, refetch } = useQuery({
    queryKey: [queryKey, paginationState],
    queryFn: () => paginationQueryFn(paginationState, getDecodedParams()),
    refetchOnWindowFocus: false,
    retry: false,
    onSuccess: (data) => setItems(parseDataOnSuccess(data)),
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: 'There was an error',
      });
    },
  });

  const columns = getColumns({ user, org, experiments });

  return (
    <Box padding="spacing.6" backgroundColor="surface.background.gray.moderate">
      {renderFiltersSection({ user, paginationState, setPagination, refetch })}
      {isFetching ? (
        <SpinnerContainer>
          <Spinner testID={spinnerTestId} accessibilityLabel="spinner" size="xlarge" />
        </SpinnerContainer>
      ) : (
        // TODO v2: replace this with Blade Table
        <DataTable
          columns={columns}
          count={paginationState.count}
          empty_placeholder={empty_placeholder}
          items={items}
          loading={isFetching}
          paginate={setPagination}
          skip={paginationState.skip}
          title={title}
        />
      )}
    </Box>
  );
};
export default connect(
  (state) => ({
    user: state.session.user,
    org: state.session.org,
  }),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(DataTableWrapper);

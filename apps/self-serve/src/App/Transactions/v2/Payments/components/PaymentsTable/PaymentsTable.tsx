import React from 'react';

import { useMobile } from '@libs/shared-utils';
import EntityTable from '@dashboards/payments/components/EntityTable';
import { useStore } from '@federated/apps/shell/commonStore';

import { StyledTable } from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import EmptyComponent from './EmptyComponent';
import { getDesktopColumns, mobileColumns } from './columns';
import { PaymentsTableProps } from './types';

const PaymentsTable = (props: PaymentsTableProps): JSX.Element => {
  const {
    loading: isLoading,
    isOmniView,
    shouldShowCustomTransactionTabView,
    selectedColumnsList,
    shouldDisplayOptimizerColumn,
    isVASOrg,
  } = props;
  const isMobile = useMobile(mobileBreakoints);
  const { user, app } = useStore((state) => ({ user: state.session.user, app: state.app }));

  const columns = isMobile
    ? mobileColumns
    : getDesktopColumns(
        isOmniView,
        shouldShowCustomTransactionTabView,
        selectedColumnsList,
        shouldDisplayOptimizerColumn,
        isVASOrg,
      );

  return (
    <StyledTable loading={isLoading}>
      <EntityTable
        title="Payments"
        progressLoader={true}
        noStripe={true}
        columns={columns}
        EmptyComponent={EmptyComponent}
        customClass="transactions-table-v2"
        limit={25}
        user={user}
        luminateRowId={app?.luminateRowId}
        activeEntityId={app?.activeEntityId}
        activeSecEntityId={app?.activeSecEntityId}
        {...props}
      />
    </StyledTable>
  );
};

export default PaymentsTable;

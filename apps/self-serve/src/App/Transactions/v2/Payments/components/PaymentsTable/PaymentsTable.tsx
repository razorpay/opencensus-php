import React from 'react';

import { useMobile } from '@dashboard/shared-ui/hooks';
import { EntityTable } from '@dashboard/shared-ui/components';
import { useStore } from 'shell/commonStore';

import { StyledTable } from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import EmptyComponent from './EmptyComponent';
import { getDesktopColumns, mobileColumns } from './columns';
import { PaymentsTableProps } from './types';

const PaymentsTable = (props: PaymentsTableProps): JSX.Element => {
  const { loading: isLoading, isOmniView } = props;
  const isMobile = useMobile(mobileBreakoints);
  const { user, app } = useStore((state) => ({ user: state.session.user, app: state.app }));

  const columns = isMobile ? mobileColumns : getDesktopColumns(isOmniView);

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

import React from 'react';
import { useMobile } from '@libs/shared-utils';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { StyledTable } from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import {
  desktopColumns,
  mobileColumns,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/columns';
import EmptyComponent from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/EmptyComponent';
import EntityTable from '@dashboards/payments/components/EntityTable';

const DisputesTable = (props) => {
  const isMobile = useMobile(mobileBreakoints);
  const columns = isMobile ? mobileColumns : desktopColumns;

  return (
    <StyledTable loading={props.loading}>
      <EntityTable
        title="Disputes"
        progressLoader={true}
        noStripe={true}
        columns={columns}
        customClass="transactions-table-v2"
        EmptyComponent={EmptyComponent}
        limit={25}
        {...props}
      />
    </StyledTable>
  );
};

export default DisputesTable;

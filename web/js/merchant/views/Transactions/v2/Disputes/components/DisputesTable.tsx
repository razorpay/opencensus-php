import React from 'react';

import { useMobile } from 'common/hooks/useMobile';
import EntityTable from 'merchant/components/EntityTable';
import { desktopColumns, mobileColumns } from 'merchant/views/Transactions/v2/Disputes/columns';
import EmptyComponent from 'merchant/views/Transactions/v2/Disputes/components/EmptyComponent';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { StyledTable } from 'merchant/views/Transactions/v2/common/styled';

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

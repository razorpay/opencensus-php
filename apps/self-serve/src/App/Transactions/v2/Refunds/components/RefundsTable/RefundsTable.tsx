// TODO: Fix imports, currently its out of scope from phase 1;
// @ts-nocheck
import React from 'react';

import { useMobile } from '@dashboard/shared-ui/hooks';
import EntityTable from 'merchant/components/EntityTable';

import EmptyComponent from './EmptyComponent';
import { desktopColumns, mobileColumns } from './columns';
import { RefundsTableProps } from './types';
import { StyledTable } from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';

const RefundsTable = (props: RefundsTableProps): JSX.Element => {
  const { loading: isLoading } = props;
  const isMobile = useMobile(mobileBreakoints);
  const columns = isMobile ? mobileColumns : desktopColumns;
  return (
    <StyledTable loading={isLoading}>
      <EntityTable
        title="Refunds"
        progressLoader={true}
        noStripe={true}
        columns={columns}
        EmptyComponent={EmptyComponent}
        customClass="transactions-table-v2"
        limit={25}
        {...props}
      />
    </StyledTable>
  );
};

export default RefundsTable;

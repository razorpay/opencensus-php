// TODO: Fix imports, currently its out of scope from phase 1;
// @ts-nocheck
import React from 'react';

import { useMobile } from '@libs/shared-utils';
import { EntityTable } from '@libs/web-nexus/common/ui/EntityTable';

import { StyledTable } from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import EmptyComponent from './EmptyComponent';
import { getDesktopColumns, mobileColumns } from './columns';
import { RefundsTableProps } from './types';

const RefundsTable = (props: RefundsTableProps): JSX.Element => {
  const { loading: isLoading, isOmniView } = props;
  const isMobile = useMobile(mobileBreakoints);
  const columns = isMobile ? mobileColumns : getDesktopColumns(isOmniView);
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

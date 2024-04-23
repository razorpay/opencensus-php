import React from 'react';

import { useMobile } from 'common/hooks/useMobile';
import EntityTable from 'merchant/components/EntityTable';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { StyledTable } from 'merchant/views/Transactions/v2/common/styled';

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

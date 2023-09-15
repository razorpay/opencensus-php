import React from 'react';

import { useMobile } from 'common/hooks/useMobile';
import EntityTable from 'merchant/components/EntityTable';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { StyledTable } from 'merchant/views/Transactions/v2/common/styled';

import EmptyComponent from './EmptyComponent';
import { desktopColumns, mobileColumns } from './columns';
import { PaymentsTableProps } from './types';

const PaymentsTable = (props: PaymentsTableProps): JSX.Element => {
  const { loading: isLoading } = props;
  const isMobile = useMobile(mobileBreakoints);
  const columns = isMobile ? mobileColumns : desktopColumns;
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
        {...props}
      />
    </StyledTable>
  );
};

export default PaymentsTable;

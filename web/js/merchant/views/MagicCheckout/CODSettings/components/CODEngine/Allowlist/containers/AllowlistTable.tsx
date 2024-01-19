import React from 'react';
import DataTable from 'common/ui/Table/DataTable';
import {
  countryCode,
  createdAt,
  zipcode,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/components/CellItems';

import { AllowlistTableProps } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/types';

const AllowlistTable = (props: AllowlistTableProps) => {
  const { items, isLoading, error, skip, count, paginate, EmptyComponent, hasMoreData } = props;

  return (
    <DataTable
      title="Allowlist uploads"
      columns={[countryCode, zipcode, createdAt]}
      items={items}
      loading={isLoading}
      error={error}
      skip={skip}
      count={count}
      paginate={paginate}
      EmptyComponent={EmptyComponent}
      customClass="cod-engine-allowlist-table"
      hasMoreData={hasMoreData}
    />
  );
};

export default AllowlistTable;

import React from 'react';
import { Indicator } from '@razorpay/blade/components';

import { STATUS_TEXT_AND_COLOR_MAP } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/constants';

type StatusCellProps = { status: boolean };

const StatusCell = ({ status }: StatusCellProps): React.ReactElement => {
  const statusLabel = status ? 'ACTIVE' : 'INACTIVE';
  return (
    <Indicator
      accessibilityLabel="store status"
      color={STATUS_TEXT_AND_COLOR_MAP[statusLabel].color}
      size="medium"
    >
      {STATUS_TEXT_AND_COLOR_MAP[statusLabel].text}
    </Indicator>
  );
};

export default StatusCell;

import React from 'react';
import { Indicator, IndicatorProps } from '@razorpay/blade/components';

import { TransactionTypes } from '@apps/digital-bills/src/utils/constants';
import { TransactionType } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

const transactionTypeColorMap: Record<TransactionType, IndicatorProps['color']> = {
  DIGITAL: 'positive',
  PRINT: 'negative',
  DIGITAL_PRINT: 'notice',
  DISCARDED: 'neutral',
};

const TransactionTypeCell = ({
  transactionType,
}: {
  transactionType: TransactionType;
}): React.ReactElement => {
  return (
    <Indicator
      accessibilityLabel={`transaction type ${transactionType}`}
      color={transactionTypeColorMap[transactionType]}
      size="medium"
    >
      {TransactionTypes[transactionType as keyof typeof TransactionTypes].Name}
    </Indicator>
  );
};

export default TransactionTypeCell;

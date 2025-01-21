import React from 'react';
import { Box, Heading, Badge } from '@razorpay/blade/components';

import { getCompactNumber } from '@apps/digital-bills/src/utils/helpers/numberFormatting';

type TransactionsCardProps = {
  heading: string;
  transactions: number;
  transactionsPercent: number;
};

const TransactionCategory = ({
  heading,
  transactions,
  transactionsPercent,
}: TransactionsCardProps): React.ReactElement => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="space-between"
      alignItems="flex-start"
      flex={1}
      height="100%"
    >
      <Heading size="small" weight="regular">
        {heading}
      </Heading>
      <Box width="60%" display="flex" alignItems="center" justifyContent="space-between">
        <Heading size="large" weight="semibold">
          {getCompactNumber(transactions)}
        </Heading>
        <Badge color="primary" size="small">
          {`${transactionsPercent}%`}
        </Badge>
      </Box>
    </Box>
  );
};

export default TransactionCategory;

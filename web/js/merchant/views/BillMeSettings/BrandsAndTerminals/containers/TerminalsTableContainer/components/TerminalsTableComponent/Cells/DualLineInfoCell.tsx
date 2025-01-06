import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

type DualLineInfoCellProps = {
  line1: string;
  line2: string;
};

const DualLineInfoCell = ({ line1, line2 }: DualLineInfoCellProps): React.ReactElement => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.2">
      <Text>{line1 || '-'}</Text>
      <Text color="surface.text.gray.muted">{line2 || '-'}</Text>
    </Box>
  );
};

export default DualLineInfoCell;

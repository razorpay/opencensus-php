import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import moment from 'moment';

type DateTimeCellProps = {
  timestamp: string | null;
};

const DateTimeCell = ({ timestamp }: DateTimeCellProps): React.ReactElement => {
  return timestamp ? (
    <Box whiteSpace="normal">
      <Text wordBreak="break-all">{`${moment(timestamp).format('DD/MM/YYYY')}`}</Text>
      <Text wordBreak="break-all">{moment(timestamp).format('hh:mm:s A')}</Text>
    </Box>
  ) : (
    <Box>-</Box>
  );
};

export default DateTimeCell;

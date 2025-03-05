import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import moment from 'moment';

type DateTimeCellProps = { billCreationTime: string };

const DateTimeCell = ({ billCreationTime }: DateTimeCellProps): React.ReactElement => {
  return billCreationTime ? (
    <Box whiteSpace="normal">
      <Text wordBreak="break-all">{`${moment(billCreationTime).format('DD MMM YYYY')},`}</Text>
      <Text wordBreak="break-all">{moment(billCreationTime).format('h:mm A')}</Text>
    </Box>
  ) : (
    <Box>-</Box>
  );
};

export default DateTimeCell;

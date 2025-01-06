import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import moment from 'moment';

type DateCellProps = {
  time: string;
};

const DateCell = ({ time }: DateCellProps): React.ReactElement => {
  return (
    <Box>
      <Text>{time ? `${moment(time).format('DD/MM/YYYY')}` : '-'}</Text>
    </Box>
  );
};

export default DateCell;

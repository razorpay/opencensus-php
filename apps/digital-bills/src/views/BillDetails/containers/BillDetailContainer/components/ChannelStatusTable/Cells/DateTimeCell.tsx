import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import moment from 'moment';

type DateTimeCellProps = {
  timestamp: string | null;
};

const DateTimeCell = ({ timestamp }: DateTimeCellProps): React.ReactElement => {
  return timestamp ? (
    <Box>
      {/* nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232 */}
      <Text>{`${moment(timestamp).format('DD/MM/YYYY')}`}</Text>
      {/* nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232 */}
      <Text>{moment(timestamp).format('hh:mm:s A')}</Text>
    </Box>
  ) : (
    <Box>-</Box>
  );
};

export default DateTimeCell;

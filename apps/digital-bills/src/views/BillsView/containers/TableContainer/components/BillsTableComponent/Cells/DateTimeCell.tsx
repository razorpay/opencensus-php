import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import moment from 'moment';

type DateTimeCellProps = { billCreationTime: string };

const DateTimeCell = ({ billCreationTime }: DateTimeCellProps): React.ReactElement => {
  return billCreationTime ? (
    <Box>
      {/* nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232 */}
      <Text>{`${moment(billCreationTime).format('DD MMM YYYY')},`}</Text>
      {/* nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232 */}
      <Text>{moment(billCreationTime).format('h:mm A')}</Text>
    </Box>
  ) : (
    <Box>-</Box>
  );
};

export default DateTimeCell;

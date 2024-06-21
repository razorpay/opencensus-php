import React from 'react';
import {
  TableRow,
  TableCell,
  Box,
  Text,
  Amount,
  Badge,
  InfoIcon,
} from '@razorpay/blade/components';
import moment from 'moment';

import { formatTimestampRange } from './helper';
import { REWARD_HISTORY_STATUS } from '../constants';
import { RewardsHistoryItem } from '../types';

interface DataRowsProps {
  tableData: RewardsHistoryItem[];
}

const DataRows = ({ tableData }: DataRowsProps): JSX.Element => {
  return (
    <>
      {tableData.map((tableItem: RewardsHistoryItem) => (
        <TableRow key={tableItem.id} item={tableItem}>
          <TableCell>{formatTimestampRange(tableItem?.start_date, tableItem?.end_date)}</TableCell>
          <TableCell>
            <Box width="100%">
              <Text textAlign="right">
                <Amount value={tableItem?.milestone_details?.current_gmv} />
              </Text>
            </Box>
          </TableCell>
          <TableCell>
            <Text color="interactive.text.primary.normal" weight="semibold">
              {tableItem?.rewards_earned}{' '}
              {tableItem?.rewards_type === 'fee_credit' ? 'credits' : 'vouchers'}
            </Text>
          </TableCell>
          <TableCell>
            {tableItem?.disbursal_date
              ? moment(tableItem.disbursal_date * 1000).format('DD MMM YYYY')
              : ''}
          </TableCell>
          <TableCell>{tableItem?.milestone_details?.milestone}</TableCell>
          <TableCell>
            <Badge
              size="large"
              icon={InfoIcon}
              color={REWARD_HISTORY_STATUS[tableItem?.rewards_disbursal_status]?.color}
            >
              {REWARD_HISTORY_STATUS[tableItem?.rewards_disbursal_status]?.text}
            </Badge>
          </TableCell>
        </TableRow>
      ))}
    </>
  );
};

export default DataRows;

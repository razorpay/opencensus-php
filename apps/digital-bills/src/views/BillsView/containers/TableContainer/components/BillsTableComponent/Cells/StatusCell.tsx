import React from 'react';
import { Text, Box } from '@razorpay/blade/components';

import {
  CHANNEL_ICON_MAP,
  STATUS_TEXT_MAP,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/constants';

import type {
  BillStatus,
  DeliveryStatus,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type StatusCellProps = {
  deliveryStatus: DeliveryStatus;
};

const colorStatusMapper = (status: BillStatus) => {
  const colorStatusMap: Partial<Record<BillStatus, any>> = {
    DELIVERED: 'interactive.text.positive.normal',
    FAILED: 'interactive.text.negative.normal',
    PENDING: 'interactive.text.notice.subtle',
    NOT_ATTEMPTED: 'interactive.text.neutral.subtle',
    ATTEMPTED: 'interactive.text.neutral.subtle',
  };
  return colorStatusMap[status];
};

const StatusCell = ({ deliveryStatus }: StatusCellProps): React.ReactElement => {
  return (
    <Box>
      {Object.entries(deliveryStatus).map(([channel, status]) => {
        // Hide the channel status if it is NOT_ATTEMPTED
        if (!status || status === 'NOT_ATTEMPTED') return null;
        const Icon = CHANNEL_ICON_MAP[channel];
        return (
          <Box
            display="flex"
            alignItems="flex-end"
            gap="spacing.4"
            marginY="spacing.3"
            key={channel}
          >
            <Icon color="surface.icon.gray.muted" />
            <Text variant="body" size="small" weight="medium" color={colorStatusMapper(status)}>
              {STATUS_TEXT_MAP[status]}
            </Text>
          </Box>
        );
      })}
    </Box>
  );
};

export default StatusCell;

import React from 'react';
import { Text, Tooltip, TooltipInteractiveWrapper, Box } from '@razorpay/blade/components';
import moment from 'moment';

import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'merchant/views/Transactions/v2/common/utils';

import { CreatedOnProps } from './types';

const CreatedOn = ({ created_at }: CreatedOnProps): JSX.Element => {
  const createdAt = getCreatedOnTime({ created_at });
  const isMobile = useMobile([...mobileBreakoints, 'l']);
  if (isMobile) {
    const createdAtSplit = createdAt.split(',');
    const date = createdAtSplit.slice(0, createdAtSplit.length - 1).join(',');
    const time = createdAtSplit[createdAtSplit.length - 1];
    return (
      <Box display="flex" flexDirection="column" pointerEvents="none">
        <Text>{date}</Text>
        <Text size="small" color="surface.text.gray.muted">
          {time}
        </Text>
      </Box>
    );
  }
  return (
    <Tooltip content={`${moment.unix(created_at).local().toDate()}`}>
      <TooltipInteractiveWrapper>
        <Text>{createdAt}</Text>
      </TooltipInteractiveWrapper>
    </Tooltip>
  );
};

export default CreatedOn;

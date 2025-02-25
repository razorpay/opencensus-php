import React from 'react';
import { Text, Tooltip, TooltipInteractiveWrapper } from '@razorpay/blade/components';
import moment from 'moment';

import { useMobile } from '@libs/shared-utils';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { CreatedOnProps } from './types';

const CreatedOn = ({ created_at }: CreatedOnProps): JSX.Element => {
  const createdAt = getCreatedOnTime({ created_at });
  const isMobile = useMobile([...mobileBreakoints, 'l']);
  if (isMobile) {
    const createdAtSplit = createdAt.split(',');
    const date = createdAtSplit.slice(0, createdAtSplit.length - 1).join(',');
    const time = createdAtSplit[createdAtSplit.length - 1];
    return (
      <>
        <Text>{date}</Text>
        <Text size="small" color="surface.text.gray.muted">
          {time}
        </Text>
      </>
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

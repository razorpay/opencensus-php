import React from 'react';
import { Text } from '@razorpay/blade/components';

import { useMobile } from '@dashboard/shared-ui/hooks';
import { CreatedOnProps } from './types';
import { mobileBreakoints } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

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
        <Text size="small" color="surface.text.muted.lowContrast">
          {time}
        </Text>
      </>
    );
  }
  return <Text>{createdAt}</Text>;
};

export default CreatedOn;

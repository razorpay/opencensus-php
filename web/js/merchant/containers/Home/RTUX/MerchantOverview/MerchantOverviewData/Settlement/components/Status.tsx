import React from 'react';
import { Text } from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { SettlementStatusBadge } from 'merchant/containers/Home/RTUX/MerchantOverview/types';

function getBadgeColor(status) {
  switch (status) {
    case 'created':
    case 'initiated':
    case 'processed':
    case 'on_track':
      return 'interactive.text.positive.subtle';
    case 'delayed':
      return 'interactive.text.notice.subtle';
    case 'failed':
    case 'blocked':
    case 'skipped':
      return 'interactive.text.negative.subtle';
    /* istanbul ignore next */
    default:
      return 'surface.text.gray.subtle';
  }
}

const Status: React.FC<{ status: SettlementStatusBadge }> = ({ status }): JSX.Element => (
  <Text color={getBadgeColor(status)} size="medium" weight="semibold">
    {titleCase(status)}
  </Text>
);

export default Status;

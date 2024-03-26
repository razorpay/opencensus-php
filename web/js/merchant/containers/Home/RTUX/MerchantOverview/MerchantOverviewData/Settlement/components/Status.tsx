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
      return 'feedback.positive.action.text.primary.active.lowContrast';
    case 'delayed':
      return 'feedback.notice.action.text.primary.active.lowContrast';
    case 'failed':
    case 'blocked':
    case 'skipped':
      return 'feedback.negative.action.text.primary.active.lowContrast';
    /* istanbul ignore next */
    default:
      return 'surface.text.subtle.lowContrast';
  }
}

const Status: React.FC<{ status: SettlementStatusBadge }> = ({ status }): JSX.Element => (
  <Text color={getBadgeColor(status)} size="medium" weight="bold">
    {titleCase(status)}
  </Text>
);

export default Status;

import { Theme } from '@razorpay/blade/components';

import { IPaymentIdRefundDetail } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

import { EntityStatus, RefundTimelineJourneyPoint, RefundTimelineType } from './types';

export const getBackgroundColor = (status: string, id: number, theme: Theme): string => {
  if (status === 'processing' && id === 1) {
    return `${theme.colors.feedback.background.notice.lowContrast}`;
  } else if (status === 'processed') {
    return `${theme.colors.feedback.background.positive.lowContrast}`;
  } else if (status === 'failed') {
    return `${theme.colors.feedback.background.negative.lowContrast}`;
  } else {
    return `${theme.colors.feedback.background.neutral.lowContrast}`;
  }
};

export const getStatusIconColor = (status: string, id: number, theme: Theme): string => {
  if (status === 'processing' && id === 1) {
    return `${theme.colors.feedback.icon.notice.lowContrast}`;
  } else if (status === 'processed') {
    return `${theme.colors.feedback.icon.positive.lowContrast}`;
  } else if (status === 'failed') {
    return `${theme.colors.feedback.icon.negative.lowContrast}`;
  } else {
    return `${theme.colors.feedback.border.neutral.lowContrast}`;
  }
};

export const makeRefundTimelineData = (refund: IPaymentIdRefundDetail): RefundTimelineType => {
  const refundTimeline: RefundTimelineJourneyPoint[] = [];

  refundTimeline.push({
    id: 1,
    entity: 'Refund',
    title: 'Refund processing',
    timestamp: null,
  });
  refundTimeline.push({
    id: 2,
    entity: 'Refund',
    title: refund.status === 'failed' ? 'Refund failed' : 'Refund processed',
    timestamp: refund.processed_at ? refund.processed_at : null,
  });

  return {
    refundStatus: refund.status as EntityStatus,
    timelineJourney: refundTimeline,
  };
};

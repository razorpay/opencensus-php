import { Theme } from '@razorpay/blade/components';
import moment from 'moment';

import {
  PaymentStatus,
  RefundStatus,
  DisputeStatus,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

export const getHumanReadableTimestamp = (epochTime: number): string => {
  const readableTimeStamp = moment.unix(epochTime).format('llll');
  return readableTimeStamp;
};

export const getIconBackgroundColor = (status: string, theme: Theme): string => {
  if (
    [
      PaymentStatus.CREATED,
      PaymentStatus.AUTHORIZED,
      PaymentStatus.CAPTURED,
      RefundStatus.PROCESSED,
      DisputeStatus.WON,
      DisputeStatus.LOST,
      DisputeStatus.CLOSED,
    ].includes(status as PaymentStatus | DisputeStatus | RefundStatus)
  ) {
    return `${theme.colors.feedback.background.positive.lowContrast}`;
  } else if (
    [
      'not-authorized',
      'not-captured',
      RefundStatus.PROCESSING,
      DisputeStatus.OPEN,
      DisputeStatus.UNDER_REVIEW,
    ].includes(status)
  ) {
    return `${theme.colors.feedback.background.notice.lowContrast}`;
  } else if (status === PaymentStatus.FAILED || status === 'auth-failed') {
    return `${theme.colors.feedback.background.negative.lowContrast}`;
  } else {
    return `${theme.colors.feedback.background.neutral.lowContrast}`;
  }
};

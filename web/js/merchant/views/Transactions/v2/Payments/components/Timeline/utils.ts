import { Theme } from '@razorpay/blade/components';
import moment from 'moment';

import { getSettlementTimeFormat } from 'merchant/views/Settlements/components/utils';
import { SettlementStatusIcons } from 'merchant/views/Settlements/v3/typings';
import {
  PaymentStatus,
  RefundStatus,
  DisputeStatus,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

export const getHumanReadableTimestamp = (epochTime: number, isViewSettlements = false): string => {
  const readableTimeStamp = moment
    .unix(epochTime)
    .format(isViewSettlements ? getSettlementTimeFormat('llll') : 'llll');
  return readableTimeStamp;
};

export const getIconBackgroundColor = (status: string, theme: Theme): string => {
  if (
    [
      PaymentStatus.CREATED,
      PaymentStatus.AUTHENTICATED,
      PaymentStatus.AUTHORIZED,
      PaymentStatus.CAPTURED,
      RefundStatus.PROCESSED,
      DisputeStatus.WON,
      DisputeStatus.LOST,
      DisputeStatus.CLOSED,
      SettlementStatusIcons.DONE,
    ].includes(status as PaymentStatus | DisputeStatus | RefundStatus)
  ) {
    return `${theme.colors.feedback.background.positive.subtle}`;
  } else if (
    [
      'not-authorized',
      'not-captured',
      RefundStatus.PROCESSING,
      DisputeStatus.OPEN,
      DisputeStatus.UNDER_REVIEW,
      SettlementStatusIcons.IN_PROGRESS,
    ].includes(status)
  ) {
    return `${theme.colors.feedback.background.notice.subtle}`;
  } else if ([PaymentStatus.FAILED, 'auth-failed', SettlementStatusIcons.FAILED].includes(status)) {
    return `${theme.colors.feedback.background.negative.subtle}`;
  } else {
    return `${theme.colors.feedback.background.neutral.subtle}`;
  }
};

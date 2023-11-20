import React from 'react';
import {
  CloseIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ClockIcon,
  CheckIcon,
} from '@razorpay/blade/components';
import styled from 'styled-components';

import {
  PaymentStatus,
  RefundStatus,
  DisputeStatus,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

import { getIconBackgroundColor } from './utils';
import { SettlementStatusIcons } from 'merchant/views/Settlements/v3/typings';

export const IconBackground = styled.div<{ status: string }>`
  height: 20px;
  width: 20px;
  position: relative;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: ${({ status, theme }) => getIconBackgroundColor(status, theme)};
`;

export const StyledVerticalPath = styled.div<{ height: number }>`
  ${({ height }) => `height:${height}px`};
  width: 0;
  border: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
`;

export const StyledJourneyMetadata = styled.div.attrs({ className: 'timeline-journey-meta' })`
  min-height: 40px;
  top: 20px;
  left: -20px;
  border-left: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
  padding-left: 24px;
  stroke-width: 1px;
  width: 300px;
`;

export const StyledText = styled.p`
  color: ${({ theme }) => `${theme.colors.surface.text.normal.lowContrast}`};
  font-size: ${({ theme }) => `${theme.typography.fonts.size[100]}`};
  font-weight: ${({ theme }) => `${theme.typography.fonts.weight.bold}`};
`;

export const StyledJourneyStatus = styled.div`
  position: absolute;
  left: 24px;
  top: -26px;
  display: flex;
  flex-direction: row;
`;

export const StyledStatusSubText = styled.p`
  color: ${({ theme }) => `${theme.colors.surface.text.subtle.lowContrast}`};
  font-size: ${({ theme }) => `${theme.typography.fonts.size[100]}`};
  padding: 0 5px;
`;

export const StyledGradientBox = styled.div`
  border-radius: ${({ theme }) => `${theme.spacing[2]}px`};
  background: linear-gradient(90deg, rgba(245, 74, 42, 0.09) 0%, rgba(255, 255, 255, 0) 100%);
  margin: 8px 0 2px 0;
  padding: 8px;
  font-size: 12px;
  font-weight: ${({ theme }) => `${theme.typography.fonts.weight.regular}`};
`;

export const StyledRefundTimelineWrapper = styled.div`
  border: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
  background-color: ${({ theme }) => `${theme.colors.surface.background.level3.lowContrast}`};
  border-radius: ${({ theme }) => `${theme.spacing[2]}`};
  margin-bottom: ${({ theme }) => `${theme.spacing[3]}`};
`;

export const StyledTimelineContainer = styled.div`
  div:first-child > div:last-child {
    .timeline-journey-meta:last-child {
      border: none;
    }
  }
`;

export const getStatusIcon = (status: string): JSX.Element => {
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
    return <CheckIcon color="feedback.icon.positive.lowContrast" size="small" />;
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
    return <ClockIcon color="feedback.icon.notice.lowContrast" size="small" />;
  } else if ([PaymentStatus.FAILED, 'auth-failed', SettlementStatusIcons.FAILED].includes(status)) {
    return <CloseIcon color="feedback.icon.negative.lowContrast" size="small" />;
  }

  return status === 'show' ? (
    <ChevronDownIcon color="feedback.icon.neutral.lowContrast" size="small" />
  ) : (
    <ChevronUpIcon color="feedback.icon.neutral.lowContrast" size="small" />
  );
};

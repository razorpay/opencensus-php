import React from 'react';
import {
  AlertCircleIcon,
  Badge,
  CheckIcon,
  ClockIcon,
  CloseIcon,
} from '@razorpay/blade/components';
import { AllBadgeTypes } from 'apps/pos/src/app/types/common';

interface StatusBadgeProps {
  type: AllBadgeTypes;
  size?: 'large' | 'small' | 'medium';
}

const StatusBadge = ({ type, size = 'medium' }: StatusBadgeProps): JSX.Element | null => {
  switch (type) {
    case 'activated':
      return (
        <Badge icon={CheckIcon} color="positive" size={size}>
          Activated
        </Badge>
      );
    case 'pending':
      return (
        <Badge icon={ClockIcon} color="information" size={size}>
          Pending
        </Badge>
      );
    case 'under_review':
      return (
        <Badge icon={ClockIcon} color="information" size={size}>
          Under Review
        </Badge>
      );
    case 'rejected':
      return (
        <Badge icon={CloseIcon} color="negative" size={size}>
          Rejected
        </Badge>
      );
    case 'kyc_qualified_stb':
      return (
        <Badge icon={CheckIcon} color="positive" size={size}>
          KYC Qualified
        </Badge>
      );
    case 'completed':
      return (
        <Badge icon={CheckIcon} color="positive" size={size}>
          Completed
        </Badge>
      );
    case 'payment_pending':
      return (
        <Badge icon={ClockIcon} color="notice" size={size}>
          Payment not initiated
        </Badge>
      );
    case 'payment_completed':
      return (
        <Badge icon={CheckIcon} color="positive" size={size}>
          Payment Completed
        </Badge>
      );

    case 'kyc_completed':
      return (
        <Badge icon={CheckIcon} color="positive" size={size}>
          KYC Completed
        </Badge>
      );

    case 'needs_clarification':
      return (
        <Badge icon={AlertCircleIcon} color="notice" size={size}>
          Needs Clarification
        </Badge>
      );
    case 'pending_agent_action':
      return (
        <Badge icon={AlertCircleIcon} color="notice" size={size}>
          Pricing Needs Clarification
        </Badge>
      );
    default:
      return null;
  }
};

export default StatusBadge;

import { Badge, CheckIcon, ClockIcon, CloseIcon } from '@razorpay/blade/components';
import React from 'react';

interface StatusBadgeProps {
  type: string;
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
        <Badge icon={ClockIcon} color="neutral" size={size}>
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
        <Badge icon={CheckIcon} color="primary" size={size}>
          KYC Qualified
        </Badge>
      );
    case 'completed':
      return (
        <Badge icon={CheckIcon} color="positive" size={size}>
          Completed
        </Badge>
      );
    default:
      return null;
  }
};

export default StatusBadge;

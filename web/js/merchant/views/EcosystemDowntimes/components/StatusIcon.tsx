import { AlertOnlyIcon, CheckIcon } from '@razorpay/blade/components';
import React from 'react';
import { EcosystemStatusIconContainer } from 'merchant/views/EcosystemDowntimes/styles';
import { STATUS } from 'merchant/views/EcosystemDowntimes/constants';

type StatusIconsType = {
  type: string;
};

const StatusIcon = ({ type }: StatusIconsType): JSX.Element | null => {
  switch (type) {
    case STATUS.operational.slug:
      return (
        <EcosystemStatusIconContainer
          height={16}
          width={16}
          bgColorKey="positive"
          aria-label="operational-icon"
        >
          <CheckIcon color="feedback.icon.positive.intense" size="small" />
        </EcosystemStatusIconContainer>
      );
    case STATUS.low.slug:
    case STATUS.medium.slug:
      return (
        <EcosystemStatusIconContainer
          height={16}
          width={16}
          bgColorKey="notice"
          aria-label="medium-sev-icon"
        >
          <AlertOnlyIcon color="feedback.icon.positive.intense" size="medium" />
        </EcosystemStatusIconContainer>
      );
    case STATUS.high.slug:
      return (
        <EcosystemStatusIconContainer
          height={16}
          width={16}
          bgColorKey="negative"
          aria-label="high-sev-icon"
        >
          <AlertOnlyIcon color="feedback.icon.positive.intense" size="medium" />
        </EcosystemStatusIconContainer>
      );
    default:
      return null;
  }
};

export default StatusIcon;

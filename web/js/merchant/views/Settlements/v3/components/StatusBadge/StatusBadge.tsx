import { Badge, InfoIcon } from '@razorpay/blade/components';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { capitalize } from 'common/utils/rzp-utils';
import { BADGE_INFO } from 'merchant/views/Settlements/components/utils';
import { VariantMap } from 'merchant/views/Settlements/v3/constants/info';
import React from 'react';
import { StyledSpan } from './styled';

const StatusBadge = ({ status }: { status: string }): JSX.Element | null => {
  if (!status) {
    return null;
  }
  const uppercaseStatus = status.toUpperCase();
  return (
    <Badge
      variant={VariantMap[uppercaseStatus]}
      icon={(props) => (
        <StyledSpan>
          <InfoIcon {...props} />
          <PopoverComponent align="top" theme="dark">
            <PopoverBody>{BADGE_INFO[uppercaseStatus]}</PopoverBody>
          </PopoverComponent>
        </StyledSpan>
      )}
    >
      {capitalize(status)}
    </Badge>
  );
};

export default StatusBadge;

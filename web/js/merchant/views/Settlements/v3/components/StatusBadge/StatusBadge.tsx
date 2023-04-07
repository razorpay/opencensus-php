import React from 'react';
import { capitalize } from 'common/utils/rzp-utils';
import { Badge, InfoIcon } from '@razorpay/blade/components';
import { VariantMap } from 'merchant/views/Settlements/v3/constants/info';
import { BADGE_INFO } from 'merchant/views/Settlements/components/utils';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { StyledSpan } from './styled';

const StatusBadge = ({ status }: { status: string }) => {
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

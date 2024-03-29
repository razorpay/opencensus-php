import React from 'react';
import {
  TooltipInteractiveWrapper,
  Tooltip as BladeTooltip,
  InfoIcon,
} from '@razorpay/blade/components';

import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';

function Tooltip(props): React.ReactElement {
  const { content, ...restProps } = props;
  return (
    <TooltipWrapper>
      <BladeTooltip content={content || ''}>
        <TooltipInteractiveWrapper>
          <InfoIcon size="small" color="interactive.icon.gray.subtle" {...restProps} />
        </TooltipInteractiveWrapper>
      </BladeTooltip>
    </TooltipWrapper>
  );
}

export default Tooltip;

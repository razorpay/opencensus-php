import React from 'react';
import { TooltipProps } from './types';
import { InfoIcon, Tooltip, TooltipInteractiveWrapper } from '@razorpay/blade/components';

export const TooltipWidget: React.FC<TooltipProps> = ({ tooltip_text }): JSX.Element | null => {
  return (
    <Tooltip content={tooltip_text} placement="bottom">
      <TooltipInteractiveWrapper>
        <InfoIcon color="surface.text.muted.lowContrast" marginTop="spacing.2" size="medium" />
      </TooltipInteractiveWrapper>
    </Tooltip>
  );
};

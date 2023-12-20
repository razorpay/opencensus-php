import React from 'react';
import {
  TooltipInteractiveWrapper,
  Tooltip,
  TooltipProps,
  BoxProps,
} from '@razorpay/blade/components';

type ConditionalTooltipProps = Pick<
  TooltipProps,
  'children' | 'title' | 'content' | 'onOpenChange' | 'placement'
> & {
  showTooltip: boolean;
  /**
   * Sets the z-index of the modal
   * @default 1113
   */
  zIndex?: number;
  padding?: BoxProps['padding'];
};
const ConditionalTooltip = ({
  showTooltip,
  children,
  title,
  content,
  onOpenChange,
  placement,
  padding,
  // Note: Custom zIndex to be shown above the sidebar(1111) and dashboard blade modal(1112)
  zIndex = 1113,
}: ConditionalTooltipProps): JSX.Element => {
  if (!showTooltip) return children;
  else
    return (
      <Tooltip
        zIndex={zIndex}
        title={title}
        content={content}
        onOpenChange={onOpenChange}
        placement={placement}
      >
        <TooltipInteractiveWrapper padding={padding}>{children}</TooltipInteractiveWrapper>
      </Tooltip>
    );
};

export default ConditionalTooltip;

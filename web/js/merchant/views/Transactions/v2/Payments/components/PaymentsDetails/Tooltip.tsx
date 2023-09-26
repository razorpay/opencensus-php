import React from 'react';
import {
  TooltipInteractiveWrapper,
  Tooltip as BladeTooltip,
  InfoIcon,
  IconProps,
} from '@razorpay/blade/components';

import { tooltipContent } from './constants';
import { TooltipWrapper } from './styled';

type TooltipContentKeys = keyof typeof tooltipContent;

interface IProps {
  type: TooltipContentKeys;
  partnerApplicationName?: string;
  size?: IconProps['size'];
}

function Tooltip(props: IProps): React.ReactElement {
  const { type, partnerApplicationName, ...restProps } = props;
  let content = tooltipContent[type] || '';

  if (partnerApplicationName) {
    content = `${tooltipContent[type]} ${partnerApplicationName}`;
  }
  return (
    <TooltipWrapper>
      <BladeTooltip content={content}>
        <TooltipInteractiveWrapper>
          <InfoIcon size="medium" color="surface.text.subtle.lowContrast" {...restProps} />
        </TooltipInteractiveWrapper>
      </BladeTooltip>
    </TooltipWrapper>
  );
}

export default Tooltip;

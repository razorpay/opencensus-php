import React from 'react';
import {
  TooltipInteractiveWrapper,
  Tooltip as BladeTooltip,
  InfoIcon,
  IconProps,
} from '@razorpay/blade/components';

import { getTooltipContent } from './constants';
import { TooltipWrapper } from './styled';
import { TooltipKeys } from './types';

interface IProps {
  type?: TooltipKeys;
  partnerApplicationName?: string;
  size?: IconProps['size'];
}

function Tooltip(props: IProps): React.ReactElement {
  const { type, partnerApplicationName, ...restProps } = props;
  let content = '';

  if (type && getTooltipContent(window.rzp_org?.business_name).hasOwnProperty(type)) {
    content = getTooltipContent(window.rzp_org?.business_name)[type];
  }

  if (partnerApplicationName) {
    content = `${content} ${partnerApplicationName}`;
  }
  return (
    <TooltipWrapper>
      <BladeTooltip content={content}>
        <TooltipInteractiveWrapper>
          <InfoIcon size="medium" color="interactive.icon.gray.subtle" {...restProps} />
        </TooltipInteractiveWrapper>
      </BladeTooltip>
    </TooltipWrapper>
  );
}

export default Tooltip;

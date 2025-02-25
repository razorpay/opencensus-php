import React from 'react';
import {
  TooltipInteractiveWrapper,
  Tooltip as BladeTooltip,
  InfoIcon,
  IconProps,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { getTooltipContent } from './constants';
import { TooltipWrapper } from './styled';
import { TooltipKeys } from './types';
import { DASHBOARD_ZINDEX_MAP } from '@libs/shared-utils';

interface IProps {
  type?: TooltipKeys;
  partnerApplicationName?: string;
  size?: IconProps['size'];
  orgName: string;
}

function Tooltip(props: IProps): React.ReactElement {
  const { orgName, type, partnerApplicationName, ...restProps } = props;
  let content = type ? getTooltipContent(orgName)[type] || '' : '';

  if (partnerApplicationName) {
    content = `${content} ${partnerApplicationName}`;
  }
  return (
    <TooltipWrapper>
      <BladeTooltip content={content} zIndex={DASHBOARD_ZINDEX_MAP.tooltip}>
        <TooltipInteractiveWrapper>
          <InfoIcon size="medium" color="interactive.icon.gray.subtle" {...restProps} />
        </TooltipInteractiveWrapper>
      </BladeTooltip>
    </TooltipWrapper>
  );
}

const mapStateToProps = (state) => {
  return {
    orgName: state.session.org?.business_name,
  };
};

export default connect(mapStateToProps)(Tooltip);

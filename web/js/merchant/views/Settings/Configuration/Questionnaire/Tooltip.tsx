import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { Text, InfoIcon } from '@razorpay/blade/components';
import { StyledLabelWithToolTip } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';

const Tooltip = ({ content }: { content: React.ReactNode }): JSX.Element => {
  return (
    <span>
      <InfoIcon color="surface.action.icon.active.lowContrast" size="small" />
      <Popover theme="dark" align="top" parentQuerySelector=".Modal-body">
        <PopoverBody>
          <div>{content}</div>
        </PopoverBody>
      </Popover>
    </span>
  );
};

const LabelWithTooltip = ({
  tooltip,
  label,
  required = true,
}: {
  tooltip: React.ReactNode;
  label: string;
  required?: boolean;
}): JSX.Element => {
  return (
    <StyledLabelWithToolTip required={required}>
      <span>{label}</span>
      {tooltip && (
        <Tooltip
          content={
            <Text type="subtle" contrast="high">
              {tooltip}
            </Text>
          }
        />
      )}
    </StyledLabelWithToolTip>
  );
};

export { Tooltip, LabelWithTooltip };

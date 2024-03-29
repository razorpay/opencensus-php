import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { Text, InfoIcon, BladeProvider } from '@razorpay/blade/components';
import { StyledLabelWithToolTip } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';
import { bladeTheme } from '@razorpay/blade/tokens';

const Tooltip = ({ content }: { content: React.ReactNode }): JSX.Element => {
  return (
    <span>
      <InfoIcon color="interactive.icon.gray.normal" size="small" />
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
            <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
              <Text color="surface.text.gray.subtle">{tooltip}</Text>{' '}
            </BladeProvider>
          }
        />
      )}
    </StyledLabelWithToolTip>
  );
};

export { Tooltip, LabelWithTooltip };

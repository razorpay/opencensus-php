import React from 'react';
import { StyledCopyButton } from './styled';
import { SettlementInfo } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import { CopyIcon, Text } from '@razorpay/blade/components';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

const CopyButton = ({
  text,
  disabled = false,
  type,
  settlement,
}: {
  text: string;
  disabled?: boolean;
  type: string;
  settlement: SettlementInfo;
}) => {
  const instrumentValueCopy = () => {
    analyticsTrack({
      objectName:
        type === 'settlement-id'
          ? 'Merchant copies Settlement ID '
          : '   Merchant copies UTR number',
      actionName: 'for a given Settlement',
      screen: 'Settlements',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        page: 'Home Screen',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });
  };

  const onCopy = () => {
    if (!disabled) {
      copyToClipboard(text);
      instrumentValueCopy();
    }
  };

  return (
    <StyledCopyButton type="button" onClick={onCopy} data-tip="Copied" data-event="active">
      <Text type="subtle">{text}</Text>
      {!disabled && <CopyIcon color="feedback.icon.neutral.lowContrast" size="medium" />}
    </StyledCopyButton>
  );
};

export default CopyButton;

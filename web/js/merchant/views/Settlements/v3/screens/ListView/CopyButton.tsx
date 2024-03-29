import React from 'react';
import { StyledCopyButton } from './styled';
import { SettlementInfo } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import { CopyIcon, Text } from '@razorpay/blade/components';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

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
}): JSX.Element => {
  const instrumentValueCopy = () => {
    analyticsTrackWithUserInfo({
      objectName: type === 'settlement-id' ? 'Settlements ID ' : 'Settlements UTR',
      actionName: 'Copied',
      screen: 'Settlements',
      properties: {
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
      <Text color="surface.text.gray.subtle">{text}</Text>
      {!disabled && <CopyIcon color="feedback.icon.neutral.intense" size="medium" />}
    </StyledCopyButton>
  );
};

export default CopyButton;

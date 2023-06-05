import React from 'react';
import { StatusIndicator } from 'merchant_common/views/Reports/components';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { checkDownloadsLogStatus } from 'merchant_common/views/Reports/configs/downloads.config';

export const LogStatus = ({ status, file_id }: BaseLogType): JSX.Element | null => {
  const internalLogStatus = checkDownloadsLogStatus(status, file_id);
  return internalLogStatus ? (
    <FlexCentered
      style={{
        width: '100%',
      }}
    >
      <StatusIndicator indicator={false}>{internalLogStatus}</StatusIndicator>
    </FlexCentered>
  ) : null;
};

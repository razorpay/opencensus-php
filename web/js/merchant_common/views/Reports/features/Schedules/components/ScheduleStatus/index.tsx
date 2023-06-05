import React from 'react';
import { StatusIndicator } from 'merchant_common/views/Reports/components';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import { checkScheduleStatus } from 'merchant_common/views/Reports/configs/schedule.config';

export const ScheduleStatus = ({ status }: ScheduleType) => {
  const refScheduleStatus = checkScheduleStatus(status);
  return refScheduleStatus ? (
    <FlexCentered
      style={{
        width: '100%',
      }}
    >
      <StatusIndicator indicator={false}>{refScheduleStatus}</StatusIndicator>
    </FlexCentered>
  ) : null;
};

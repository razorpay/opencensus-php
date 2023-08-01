import React from 'react';
import moment from 'moment';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Downloads/types';
import { DownloadIndicator } from 'merchant_common/views/Reports/features/Downloads/components/DownloadIndicator';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { LogStatus } from 'merchant_common/views/Reports/features/Downloads/components/LogStatus';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { TableText } from 'merchant_common/views/Reports/components/Table/Components/TableText';
import { ScheduleRunHistoryActionType } from 'merchant_common/views/Reports/configs/analytics.config';
import { LogStatusWrapper } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/styled';

export const baseRunHistoryTableTemplate: TableTemplateType<
  BaseLogType,
  AdditionalInformationType<keyof typeof ScheduleRunHistoryActionType>
> = {
  headers: ['Delivered Date', 'Time', 'Format', 'Status', 'Download'],
  cells: [
    {
      render: ({ generated_at, status }) =>
        generated_at ? (
          <TableText>{moment.unix(generated_at).format('MMM DD, YYYY')}</TableText>
        ) : status === 'failed' ? (
          <TableText>Delivery Failed</TableText>
        ) : (
          <TableText>Yet to be delivered</TableText>
        ),
    },
    {
      render: ({ generated_at }) =>
        generated_at ? (
          <TableText>{`Delivered at ${moment.unix(generated_at).format('h:mm A')}`}</TableText>
        ) : (
          <TableText>--</TableText>
        ),
    },
    {
      render: ({ template_overrides, extension }) => (
        <TableText>{extension ?? template_overrides?.file_meta?.extension}</TableText>
      ),
    },
    {
      render: (props) => (
        <LogStatusWrapper>
          <FlexCentered
            style={{
              width: '100%',
            }}
          >
            <LogStatus {...props} />
          </FlexCentered>
        </LogStatusWrapper>
      ),
    },
    {
      style: {
        width: 120,
      },
      render: (props, _, __, additionalInfo) => (
        <FlexCentered
          style={{
            width: '100%',
          }}
        >
          <DownloadIndicator {...props} trackDownloadFile={additionalInfo?.trackDownloadFile} />
        </FlexCentered>
      ),
    },
  ],
};

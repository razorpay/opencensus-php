import React from 'react';
import moment from 'moment';
import { Box } from 'merchant_common/views/Reports/components';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Downloads/types';
import { CollapsibleArray } from 'merchant_common/views/Reports/components/Table/Components/CollapsibleArray/CollapsibleArray';
import { DownloadIndicator } from 'merchant_common/views/Reports/features/Downloads/components/DownloadIndicator';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { LogStatus } from 'merchant_common/views/Reports/features/Downloads/components/LogStatus';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { TableText } from 'merchant_common/views/Reports/components/Table/Components/TableText';
import { DownloadsActionType } from 'merchant_common/views/Reports/configs/analytics.config';
import {
  DataDurationWrapper,
  LogStatusWrapper,
} from 'merchant_common/views/Reports/features/Downloads/style';

const parseDate = (date) => {
  return moment.unix(date).format('MMM DD, YYYY (hh:mm A)').toString();
};

export const baseDownloadsTableTemplate: TableTemplateType<
  BaseLogType,
  AdditionalInformationType<keyof typeof DownloadsActionType>
> = {
  headers: ['Duration Covered', 'Name', 'Format', 'Email', 'Status', 'Download'],
  cells: [
    {
      render: ({ start_time, end_time }) => (
        <TableText>
          <DataDurationWrapper>
            {parseDate(start_time) === parseDate(end_time) ? (
              <Box display="flex" justifyContent="center" alignItems="center" width="70%">
                -
              </Box>
            ) : (
              <>
                <span>{parseDate(start_time)}</span> - <br />
                <span>{parseDate(end_time)}</span>
              </>
            )}
          </DataDurationWrapper>
        </TableText>
      ),
    },

    {
      render: ({ name, id }) => <TableText>{name ?? id}</TableText>,
    },

    {
      render: ({ template_overrides, extension }) => (
        <TableText>{extension ?? template_overrides?.file_meta?.extension}</TableText>
      ),
    },
    {
      render: ({ all_emails = [], id }) => {
        return <CollapsibleArray logId={id} arr={Array.isArray(all_emails) ? all_emails : []} />;
      },
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

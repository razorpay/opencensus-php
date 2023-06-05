import React from 'react';
import moment from 'moment';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Downloads/types';
import { CollapsibleArray } from 'merchant_common/views/Reports/components/Table/Components/CollapsibleArray/CollapsibleArray';
import { DownloadIndicator } from 'merchant_common/views/Reports/features/Downloads/components/DownloadIndicator';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { LogStatus } from 'merchant_common/views/Reports/features/Downloads/components/LogStatus';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { TableText } from 'merchant_common/views/Reports/components/Table/Components/TableText';

const parseDate = (date) => {
  return moment.unix(date).format('MMM DD, YYYY (hh:mm A)').toString();
};

export const baseDownloadsTableTemplate: TableTemplateType<BaseLogType, AdditionalInformationType> =
  {
    headers: ['Duration Covered', 'Name', 'Format', 'Email', 'Status', 'Download'],
    cells: [
      {
        render: ({ start_time, end_time }) => (
          <TableText>
            <p
              style={{
                width: 175,
              }}
            >
              <span>{parseDate(start_time)}</span> - <br />
              <span>{parseDate(end_time)}</span>
            </p>
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
          <div
            style={{
              minWidth: 68,
            }}
          >
            <FlexCentered
              style={{
                width: '100%',
              }}
            >
              <LogStatus {...props} />
            </FlexCentered>
          </div>
        ),
      },
      {
        style: {
          width: 120,
        },
        render: (props) => (
          <FlexCentered
            style={{
              width: '100%',
            }}
          >
            <DownloadIndicator {...props} />
          </FlexCentered>
        ),
      },
    ],
  };

import React from 'react';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Downloads/types';
import { FlexCentered, Skeleton } from 'merchant_common/views/Reports/components/styled';
import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { ScheduleRunHistoryActionType } from 'merchant_common/views/Reports/configs/analytics.config';

export const emptyRunHistoryTableTemplate: TableTemplateType<
  BaseLogType,
  AdditionalInformationType<keyof typeof ScheduleRunHistoryActionType>
> = {
  headers: ['Delivered Date', 'Time', 'Name', 'Format', 'Status', 'Download'],
  cells: [
    {
      render: (): JSX.Element => (
        <Skeleton
          style={{
            height: 12,
            width: 100,
          }}
        />
      ),
    },

    {
      render: (): JSX.Element => (
        <Skeleton
          style={{
            height: 12,
            width: 140,
          }}
        />
      ),
    },

    {
      render: (): JSX.Element => (
        <FlexCentered>
          <Skeleton
            style={{
              height: 12,
              width: 40,
            }}
          />
        </FlexCentered>
      ),
    },
    {
      render: (): JSX.Element => {
        return (
          <Skeleton
            style={{
              height: 12,
              width: 170,
            }}
          />
        );
      },
    },
    {
      render: (): JSX.Element => {
        return (
          <FlexCentered>
            <Skeleton
              style={{
                height: 15,
                width: 60,
              }}
            />
          </FlexCentered>
        );
      },
    },
    {
      render: (): JSX.Element => {
        return (
          <FlexCentered>
            <Skeleton
              style={{
                height: 20,
                width: 20,
                borderRadius: 20,
              }}
            />
          </FlexCentered>
        );
      },
    },
  ],
};

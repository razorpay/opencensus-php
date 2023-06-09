import React from 'react';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Downloads/types';
import { FlexCentered, Skeleton } from 'merchant_common/views/Reports/components/styled';
import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { DownloadsActionType } from 'merchant_common/views/Reports/configs/analytics.config';

export const emptyDownloadsTableTemplate: TableTemplateType<
  BaseLogType,
  AdditionalInformationType<keyof typeof DownloadsActionType>
> = {
  headers: ['Duration Covered', 'Name', 'Format', 'Email', 'Status', 'Download'],
  cells: [
    {
      render: () => (
        <div>
          <span>
            <Skeleton
              style={{
                height: 12,
                width: 100,
              }}
            />
          </span>{' '}
          <br />
          <span>
            <Skeleton
              style={{
                height: 12,
                width: 80,
              }}
            />
          </span>
        </div>
      ),
    },

    {
      render: () => (
        <Skeleton
          style={{
            height: 12,
            width: 140,
          }}
        />
      ),
    },

    {
      render: () => (
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
      render: () => {
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
      render: () => {
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
      render: () => {
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

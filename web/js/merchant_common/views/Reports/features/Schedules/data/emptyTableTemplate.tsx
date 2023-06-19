import React from 'react';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Schedules/types';
import { FlexCentered, Skeleton } from 'merchant_common/views/Reports/components/styled';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';

export const emptySchedulesTableTemplate: TableTemplateType<
  ScheduleType,
  AdditionalInformationType
> = {
  headers: [
    'Schedule & Report Name',
    'Format',
    'Email',
    'Status',
    'Repeat On',
    'Pause/Delete',
    'View Activity',
  ],
  cells: [
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
    {
      render: () => {
        return (
          <FlexCentered>
            <Skeleton
              style={{
                height: 20,
                width: 100,
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
                height: 25,
                width: 70,
              }}
            />
          </FlexCentered>
        );
      },
    },
  ],
};

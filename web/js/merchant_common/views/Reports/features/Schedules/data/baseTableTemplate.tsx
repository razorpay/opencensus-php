import React from 'react';
import moment from 'moment';
import { AdditionalInformationType } from 'merchant_common/views/Reports/features/Schedules/types';
import { CollapsibleArray } from 'merchant_common/views/Reports/components/Table/Components/CollapsibleArray/CollapsibleArray';
import { Button, MaximizeIcon, Text } from 'merchant_common/views/Reports/components';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { ScheduleStatus } from 'merchant_common/views/Reports/features/Schedules/components/ScheduleStatus';
import { TableTemplateType } from 'merchant_common/views/Reports/components/Table/types';
import { TableText } from 'merchant_common/views/Reports/components/Table/Components/TableText';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import { ControlActions } from 'merchant_common/views/Reports/features/Schedules/components/ControlActions';

export const parseDate = (date) => {
  return moment.unix(date).format('MMM DD, YYYY (hh:mm A)').toString();
};

export const baseSchedulesTableTemplate: TableTemplateType<
  ScheduleType,
  AdditionalInformationType
> = {
  headers: [
    'Schedule & Report Name',
    'Format',
    'Email',
    'Status',
    'Repeat On',
    'Modify / Pause / Delete',
    'View Activity',
  ],
  cells: [
    {
      render: ({ name, config_name }) => (
        <div
          style={{
            height: 40,
          }}
        >
          <Text variant="body" weight="regular" color="surface.text.gray.normal">
            {name}
          </Text>
          <Text variant="caption" weight="regular" color="surface.text.gray.normal">
            {config_name}
          </Text>
        </div>
      ),
    },
    {
      render: ({ template_overrides }) => {
        const extension = template_overrides?.file_meta?.extension;
        return (
          <FlexCentered
            style={{
              width: '100%',
            }}
          >
            <TableText>{extension}</TableText>
          </FlexCentered>
        );
      },
    },
    {
      render: ({ emails = [], id }) => {
        return <CollapsibleArray scheduleId={id} arr={Array.isArray(emails) ? emails : []} />;
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
            <ScheduleStatus {...props} />
          </FlexCentered>
        </div>
      ),
    },
    {
      render: ({ period }) => (
        <FlexCentered
          style={{
            width: '100%',
          }}
        >
          <TableText>{period}</TableText>
        </FlexCentered>
      ),
    },
    {
      render: (data) => {
        return <ControlActions scheduleData={data} />;
      },
    },
    {
      render: (scheduleData, __, ___, additionalInfo) => {
        return (
          <FlexCentered
            style={{
              width: '100%',
            }}
          >
            <Button
              onClick={() => additionalInfo?.onViewActivityOpen(scheduleData)}
              variant="secondary"
              iconPosition="right"
              size="small"
              icon={MaximizeIcon}
            >
              Open
            </Button>
          </FlexCentered>
        );
      },
    },
  ],
};

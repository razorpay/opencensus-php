import React from 'react';
import { DayProps } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { Text } from 'merchant_common/views/Reports/components';
import { Th } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';

export const Day = ({ weekIndex, customDayComponent, daySize }: DayProps): JSX.Element => {
  const data = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  return (
    <Th
      aria-label={`${data[weekIndex]}day Column`}
      style={{
        height: daySize,
        width: daySize,
      }}
    >
      {customDayComponent ? (
        customDayComponent({ weekIndex, weekDay: data[weekIndex] })
      ) : (
        <Text size="small" weight="regular" variant="body" color="surface.text.gray.subtle">
          {data[weekIndex]}
        </Text>
      )}
    </Th>
  );
};

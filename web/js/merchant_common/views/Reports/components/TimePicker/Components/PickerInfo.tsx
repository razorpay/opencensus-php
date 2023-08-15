import React from 'react';
import moment from 'moment';
import { TimePickerRow } from 'merchant_common/views/Reports/components/TimePicker/styled';
import { Text } from 'merchant_common/views/Reports/components';
import { TimeInfo } from './TimeInfo';
import { useTimePicker } from 'merchant_common/views/Reports/components/TimePicker/hooks/useTimePicker';

export const PickerInfo = (): JSX.Element => {
  const {
    minutes,
    hour,
    meridiem,
    hourChevDownClick,
    hourChevUpClick,
    meridiemChevDownClick,
    meridiemChevUpClick,
    minutesChevDownClick,
    minutesChevUpClick,
  } = useTimePicker();
  return (
    <>
      <TimeInfo
        role="Hour"
        ariaLabel={hour.toString()}
        chevUpClick={hourChevUpClick}
        chevDownClick={hourChevDownClick}
      >
        {hour}
      </TimeInfo>

      <TimePickerRow>
        <Text variant="body" size="medium" weight="regular">
          :
        </Text>
      </TimePickerRow>
      <TimeInfo
        role="Minute"
        ariaLabel={moment(`${minutes}`, 'm').format('mm')}
        chevUpClick={minutesChevUpClick}
        chevDownClick={minutesChevDownClick}
      >
        {moment(`${minutes}`, 'm').format('mm')}
      </TimeInfo>
      <TimeInfo
        role="Meridiem"
        ariaLabel={meridiem}
        chevUpClick={meridiemChevUpClick}
        chevDownClick={meridiemChevDownClick}
      >
        {meridiem}
      </TimeInfo>
    </>
  );
};

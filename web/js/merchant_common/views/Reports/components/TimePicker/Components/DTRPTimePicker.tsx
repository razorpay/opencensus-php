import React, { useRef } from 'react';
import { ScrollSafeMargin } from 'merchant_common/views/Reports/components/styled';
import {
  TimePicker as TimePi,
  TimePickerWrapper,
} from 'merchant_common/views/Reports/components/TimePicker/styled';
import { PickerInfo } from './PickerInfo';
import { useTimePicker } from 'merchant_common/views/Reports/components/TimePicker/hooks/useTimePicker';
import { useClickOutSide, useTheme } from 'merchant_common/views/Reports/hooks';

const DTRPTimePicker = (): JSX.Element => {
  const { theme } = useTheme();

  const { setShowPicker } = useTimePicker();

  const timePickerRef = useRef<HTMLDivElement>(null);

  useClickOutSide([timePickerRef], () => {
    setShowPicker(false);
  });

  return (
    <TimePickerWrapper ref={timePickerRef} aria-label="Time Picker Container" theme={theme}>
      <ScrollSafeMargin>
        <TimePi theme={theme}>
          <PickerInfo />
        </TimePi>
      </ScrollSafeMargin>
    </TimePickerWrapper>
  );
};

export default DTRPTimePicker;

import React, { useRef } from 'react';
import {
  PickerAbsWrapper,
  TimePickerS,
} from 'merchant_common/views/Reports/components/TimePicker/styled';
import { ScrollSafeMargin } from 'merchant_common/views/Reports/components/styled';
import { PickerInfo } from './PickerInfo';
import { useClickOutSide, useTheme } from 'merchant_common/views/Reports/hooks';
import { useTimePicker } from 'merchant_common/views/Reports/components/TimePicker/hooks/useTimePicker';

const BaseTimePicker = (): JSX.Element => {
  const { theme } = useTheme();
  const { setShowPicker } = useTimePicker();

  const timePickerRef = useRef<HTMLDivElement>(null);

  useClickOutSide([timePickerRef], () => {
    setShowPicker(false);
  });

  return (
    <PickerAbsWrapper ref={timePickerRef} aria-label="Time Picker Container" theme={theme}>
      <ScrollSafeMargin>
        <TimePickerS theme={theme}>
          <PickerInfo />
        </TimePickerS>
      </ScrollSafeMargin>
    </PickerAbsWrapper>
  );
};

export default BaseTimePicker;

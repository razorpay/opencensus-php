import React from 'react';
import {
  AbsoluteWrapper,
  DateTimeRangeContainer as StyledDateTimeRangeContainer,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import { useTheme } from 'merchant_common/views/Reports/hooks';

export const DateTimeRangeContainer = ({ children }): JSX.Element => {
  const { theme } = useTheme();
  const { validationError } = useDateTimeRangeContext();

  return (
    <AbsoluteWrapper>
      <StyledDateTimeRangeContainer
        theme={theme}
        focused={true}
        validation={!Boolean(validationError)}
      >
        {children}
      </StyledDateTimeRangeContainer>
    </AbsoluteWrapper>
  );
};

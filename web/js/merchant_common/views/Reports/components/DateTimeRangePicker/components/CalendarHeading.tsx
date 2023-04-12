import React from 'react';
import { Heading } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { CalendarHeadingPropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { HeaderButton } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';

export const CalendarHeading = ({
  children,
  onClick,
  customCalendarHeading,
  disabled = false,
}: CalendarHeadingPropsType): JSX.Element => {
  const { theme } = useTheme();
  return customCalendarHeading ? (
    customCalendarHeading(children)
  ) : (
    <HeaderButton
      aria-label="Calendar Month, Year"
      theme={theme}
      disabled={disabled}
      onClick={onClick}
    >
      <Heading size="medium" weight="bold" type="subdued" variant="regular">
        {children}
      </Heading>
    </HeaderButton>
  );
};

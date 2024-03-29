import React from 'react';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { CalendarHeadingPropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { HeaderButton } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { Heading } from '@razorpay/blade/components';

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
      <Heading weight="semibold" size="small" color="surface.text.gray.muted">
        {children}
      </Heading>
    </HeaderButton>
  );
};

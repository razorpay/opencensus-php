import React, { useState } from 'react';
import { ArrowRightIcon, Text, Switch, TimePicker } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import {
  CalendarHeader,
  RangeSection,
  RangeSectionHeader,
  SelectedRangeInfo,
  SelectedRangeInfoBadge,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';

export const DateTimeRangeHeader = ({
  disableTimeSelection,
  minutesInterval,
  children,
}): JSX.Element => {
  const { theme } = useTheme();
  const [shouldIncludeTime, setShouldIncludeTime] = useState(!disableTimeSelection);
  const { endDate, setEndDate, setStartDate, startDate } = useDateTimeRangeContext();

  const handleIncludeTime = (val) => {
    if (startDate && endDate) {
      setShouldIncludeTime(val);
      if (!val) {
        setStartDate(startDate.startOf('day'));
        setEndDate(endDate.endOf('day'));
      }
    }
  };

  return (
    <CalendarHeader theme={theme}>
      <RangeSectionHeader>
        <Text variant="body" type="normal" weight="bold">
          Selected Date Range:
        </Text>
        {Boolean(startDate && endDate && !disableTimeSelection) ? (
          <Switch label="Include Time" onChange={handleIncludeTime} value={shouldIncludeTime} />
        ) : null}
      </RangeSectionHeader>

      <SelectedRangeInfo>
        <RangeSection>
          <SelectedRangeInfoBadge aria-label="Picker Start Date" theme={theme}>
            <Text type="normal" size="medium" weight="regular" variant="body">
              {startDate ? startDate.format('MMM DD, YYYY') : 'Choose A Start Date'}
            </Text>
          </SelectedRangeInfoBadge>
          {shouldIncludeTime && startDate && !disableTimeSelection ? (
            <TimePicker
              minutesInterval={minutesInterval}
              value={startDate}
              onChange={({ date }) => setStartDate(date)}
            />
          ) : null}
        </RangeSection>
        <RangeSection>
          <ArrowRightIcon size="medium" color="feedback.icon.neutral.lowContrast" />
        </RangeSection>
        <RangeSection>
          <SelectedRangeInfoBadge aria-label="Picker End Date" theme={theme}>
            <Text type="normal" size="medium" weight="regular" variant="body">
              {endDate ? endDate.format('MMM DD, YYYY') : 'Choose An End Date'}
            </Text>
          </SelectedRangeInfoBadge>
          {shouldIncludeTime && endDate && !disableTimeSelection ? (
            <TimePicker
              minutesInterval={5}
              value={endDate}
              onChange={({ date }) => setEndDate(date)}
            />
          ) : null}
        </RangeSection>
      </SelectedRangeInfo>
      {children}
    </CalendarHeader>
  );
};

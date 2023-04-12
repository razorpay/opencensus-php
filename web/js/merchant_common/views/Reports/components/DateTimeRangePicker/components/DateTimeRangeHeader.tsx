import React, { Fragment, useState } from 'react';
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

export const DateTimeRangeHeader = (): JSX.Element => {
  const { theme } = useTheme();
  const [shouldIncludeTime, setShouldIncludeTime] = useState(true);
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

  return startDate && endDate ? (
    <CalendarHeader theme={theme}>
      <RangeSectionHeader>
        <Text variant="body" type="normal" weight="bold">
          Selected Date Range:
        </Text>
        <Switch label="Include Time" onChange={handleIncludeTime} value={shouldIncludeTime} />
      </RangeSectionHeader>

      <SelectedRangeInfo>
        <RangeSection>
          <SelectedRangeInfoBadge aria-label="Picker Start Date" theme={theme}>
            <Text type="normal" size="medium" weight="regular" variant="body">
              {startDate.format('MMM DD, YYYY')}
            </Text>
          </SelectedRangeInfoBadge>
          {shouldIncludeTime && (
            <TimePicker date={startDate} onChange={({ date }) => setStartDate(date)} />
          )}
        </RangeSection>
        <RangeSection>
          <ArrowRightIcon size="medium" color="feedback.icon.neutral.lowContrast" />
        </RangeSection>
        <RangeSection>
          <SelectedRangeInfoBadge aria-label="Picker End Date" theme={theme}>
            <Text type="normal" size="medium" weight="regular" variant="body">
              {endDate.format('MMM DD, YYYY')}
            </Text>
          </SelectedRangeInfoBadge>
          {shouldIncludeTime && (
            <TimePicker date={endDate} onChange={({ date }) => setEndDate(date)} />
          )}
        </RangeSection>
      </SelectedRangeInfo>
    </CalendarHeader>
  ) : (
    <Fragment />
  );
};

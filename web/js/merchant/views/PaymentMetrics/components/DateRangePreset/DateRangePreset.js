import React, { useCallback } from 'react';
import { PowerSelect } from 'react-power-select';
import moment from 'moment';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';

import DateTimePicker from './DateTimePicker';
import {
  DateRangeContainer,
  DateTimePickerDiv,
  DateTimePickerSeperator,
} from 'merchant/views/PaymentMetrics/components/styled';

const customPreset = { label: 'Custom Range', name: 'custom', value: 0, unit: '' };

function DateRangePreset(props) {
  const { presets, dateRange, setDateRange, pastAllowDays = 90 } = props;
  const { startDate, endDate, preset } = dateRange || {};

  const onPresetChange = useCallback(({ option }) => {
    const { name, value, unit } = option;

    let start = '';
    let end = '';

    // If the dates are not entered manually from the date picker, set the date and time automatically.
    if (name !== customPreset.name) {
      end = moment().endOf('hour');
      start = end.clone().subtract(value, unit).startOf('hour');
    }
    props?.onPresetChange?.({ option, start, end });
  }, []);

  const isOutsideRange = useCallback(
    (day) => {
      const now = moment();
      const past = now.clone().subtract(pastAllowDays, 'days');
      return day.isSameOrAfter(past, 'day') && day.isBefore(now);
    },
    [pastAllowDays],
  );

  const handleDateChange = useCallback(
    ({ date, name }) => {
      setDateRange({
        ...dateRange,
        [name]: date,
        preset: customPreset,
      });
    },
    [dateRange, setDateRange],
  );

  return (
    <div className="rzp-daterange-picker">
      <div className="icon-container">
        <i className="i i-date-range" />
      </div>
      <div className="presets-container">
        {presets.length > 0 && (
          <ErrorBoundary resetOnProps rank={Ranks.P2}>
            <PowerSelect
              className="date-range-preset-select react-normal-select"
              options={presets}
              selected={preset}
              onChange={onPresetChange}
              optionLabelPath="label"
              placeholder="Select Preset"
              searchEnabled={false}
            />
          </ErrorBoundary>
        )}
      </div>

      <DateRangeContainer>
        <SuspenseWithLoader>
          <DateTimePickerDiv>
            <DateTimePicker
              value={startDate}
              placeholder="Select Start Date"
              name="startDate"
              onChange={handleDateChange}
              isOutsideRange={isOutsideRange}
            />

            <DateTimePickerSeperator>to</DateTimePickerSeperator>

            <DateTimePicker
              value={endDate}
              placeholder="Select End Date"
              name="endDate"
              onChange={handleDateChange}
              isOutsideRange={isOutsideRange}
            />
          </DateTimePickerDiv>
        </SuspenseWithLoader>
      </DateRangeContainer>
    </div>
  );
}

export default DateRangePreset;

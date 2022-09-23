import React from 'react';
import { PowerSelect } from 'react-power-select';
import moment from 'moment';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';

import DateTimePicker from './DateTimePicker';

const customPreset = { label: 'Custom Range', name: 'custom', value: 0, unit: '' };

const isOutsideRange = (day) => {
  const now = moment();
  const past = now.clone().subtract(90, 'days');
  return day.isSameOrAfter(past, 'day') && day.isBefore(now);
};

function DateRangePreset(props) {
  const { presets, dateRange, setDateRange } = props;
  const { startDate, endDate, preset } = dateRange || {};

  const onPresetChange = ({ option }) => {
    const { name, value, unit } = option;

    let start = '';
    let end = '';

    // If the dates are not entered manually from the date picker, set the date and time automatically.
    if (name !== 'custom') {
      end = moment().endOf('hour');
      start = end.clone().subtract(value, unit).startOf('hour');
    }

    setDateRange({
      preset: option,
      startDate: start,
      endDate: end,
    });
  };

  const handleDateChange = ({ date, name }) => {
    setDateRange({
      ...dateRange,
      [name]: date,
      preset: customPreset,
    });
  };

  return (
    <div className="rzp-daterange-picker">
      <div className="icon-container">
        <i class="i i-date-range" />
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

      <div className="daterange-container">
        <SuspenseWithLoader>
          <div className="datetime-picker">
            <DateTimePicker
              value={startDate}
              placeholder="Select Start Date"
              name="startDate"
              onChange={handleDateChange}
              isOutsideRange={isOutsideRange}
            />

            <span className="datetime-picker__separator">to</span>

            <DateTimePicker
              value={endDate}
              placeholder="Select End Date"
              name="endDate"
              onChange={handleDateChange}
              isOutsideRange={isOutsideRange}
            />
          </div>
        </SuspenseWithLoader>
      </div>
    </div>
  );
}

export default DateRangePreset;

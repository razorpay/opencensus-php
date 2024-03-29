import React from 'react';
import {
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Box,
  CalendarIcon,
  SelectInput,
} from '@razorpay/blade/components';
import moment from 'moment';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { SuccessRateDateFilterContainer } from 'merchant/views/Transactions/v1/SuccessRate/styles';

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

  const onPresetChange = (selectedValue) => {
    const option = presets.find((preset) => preset.name === selectedValue);
    const { name, value, unit } = option;

    let start = '';
    let end = '';

    // If the dates are not entered manually from the date picker, set the date and time automatically.
    if (name !== 'custom') {
      end = moment().endOf('hour');
      start = end.clone().subtract(value, unit).startOf('hour');
    }
    props?.onPresetChange?.({ option, start, end });
  };

  const handleDateChange = ({ date, name }) => {
    const payload = {
      ...dateRange,
      [name]: date,
      preset: customPreset,
    };
    setDateRange(payload);
  };

  const handleOnPresetChange = ({ values }) => onPresetChange(values[0]);

  return (
    <SuccessRateDateFilterContainer data-testid="sr-dashboard-date-filters">
      <Dropdown>
        <SelectInput
          accessibilityLabel="Date Filter"
          labelPosition="top"
          name="item"
          label=""
          icon={CalendarIcon}
          value={preset.name}
          testID="sr-dashboard-date-presets"
          onChange={handleOnPresetChange}
        />
        <DropdownOverlay>
          <ActionList>
            {presets.map((preset) => (
              <ActionListItem
                key={preset.name}
                title={preset.label}
                value={preset.name}
                testID={`${preset.name}-date-preset`}
              />
            ))}
          </ActionList>
        </DropdownOverlay>
      </Dropdown>
      <Box
        borderWidth="thin"
        borderColor="surface.border.gray.muted"
        alignItems="center"
        height="max-content"
        paddingY="spacing.2"
      >
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
      </Box>
    </SuccessRateDateFilterContainer>
  );
}

export default DateRangePreset;

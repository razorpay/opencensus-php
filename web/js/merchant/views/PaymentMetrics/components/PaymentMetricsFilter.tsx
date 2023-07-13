import React, { useEffect, useState, useCallback } from 'react';
import moment from 'moment';
import AsyncButton from 'react-async-button';
import {
  getBreakdownInterval,
  initialFilters,
  validateDateRange,
} from 'merchant/views/PaymentMetrics/helpers';

import { PRESETS, DEFAULT_INTERVAL } from 'merchant/views/PaymentMetrics/constants';
import { DateRangePreset } from './DateRangePreset';
import { PaymentMetricsFilterProps, Preset } from 'merchant/views/PaymentMetrics/types';
import { DatePickerGroup, FilterParent } from './styled';

const PaymentMetricsFilter = ({
  endDate,
  startDate,
  preset,
  updateDateRange,
  updateInterval,
}: PaymentMetricsFilterProps): React.ReactElement => {
  const [dateRange, setDateRange] = useState({
    startDate: moment(),
    endDate: moment(),
    preset: {} as Preset,
  });
  const [errors, setErrors] = useState({ date: '' });

  useEffect(() => {
    setDateRange({ startDate, endDate, preset });
  }, [startDate, endDate, preset]);

  useEffect(() => {
    const errors = validateDateRange(dateRange);
    setErrors(errors);
  }, [dateRange]);

  const onSearch = useCallback(
    (dateRangeParam = dateRange, errorsParam = errors) => {
      const { startDate, endDate } = dateRangeParam;

      if (Object.keys(errorsParam).length) return;

      updateDateRange(dateRangeParam);

      updateInterval(getBreakdownInterval(startDate, endDate));
    },
    [dateRange, errors],
  );

  const onReset = useCallback(() => {
    const initialValue = initialFilters();

    updateDateRange(initialValue);
    updateInterval(DEFAULT_INTERVAL);
  }, [updateDateRange, updateInterval]);

  const onPresetChange = useCallback(
    ({ option, start, end }) => {
      const dateRange = {
        preset: option,
        startDate: start,
        endDate: end,
      };
      const errors = validateDateRange(dateRange);
      setDateRange(dateRange);
      setErrors(errors);
      if (option?.name !== 'custom') {
        onSearch(dateRange, errors);
      }
    },
    [onSearch, setDateRange, setErrors, validateDateRange],
  );

  const handleSearch = () => onSearch(dateRange, errors);

  return (
    <FilterParent>
      <div>
        <label>Date Range</label>
        <DatePickerGroup>
          <DateRangePreset
            presets={PRESETS}
            dateRange={dateRange}
            pastAllowDays={30}
            setDateRange={setDateRange}
            setErrors={setErrors}
            onPresetChange={onPresetChange}
          />

          <div>
            <AsyncButton
              className="btn btn-primary btn-sm bold"
              onClick={handleSearch}
              disabled={Object.keys(errors).length > 0}
              text="Apply"
            />
            <AsyncButton className="btn btn-sm btn-text" onClick={onReset} text="Clear" />
          </div>
        </DatePickerGroup>
        {errors.date && (
          <small className="error-text text-danger">
            <i className="i i-info-outline" />
            <i>{errors.date}</i>
          </small>
        )}
      </div>
    </FilterParent>
  );
};

export default PaymentMetricsFilter;

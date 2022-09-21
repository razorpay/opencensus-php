import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import AsyncButton from 'react-async-button';
import {
  updateDateRange,
  fetchSuccessRate,
  fetchMerchantErrors,
  setDefaultInterval,
  setActiveTab,
} from 'merchant/reducers/successRate';
import {
  getBreakdownInterval,
  initialFilters,
  queryFilters,
  getMerchantErrorsPayload,
} from '../helper';
import { PRESETS, DEFAULT_INTERVAL } from '../constants';
import { clearFilterSuccessRate, filterSuccessRate, trackSuccessRateEvents } from '../trackEvents';
import { DateRangePreset } from './DateRangePreset';
import moment from 'moment';

const validateDateRange = (dateRange) => {
  const { startDate, endDate } = dateRange;
  const errors = {};

  if (!startDate) {
    errors.date = 'Start Date is required';
  } else if (!endDate) {
    errors.date = 'End Date is required';
  } else if (startDate.valueOf() > endDate.valueOf()) {
    errors.date = 'Start Date cannot be greater than End Date';
  } else {
    const duration = moment.duration(endDate.diff(startDate));
    const hours = duration.asHours();

    if (hours < 6) {
      errors.date = 'Please select a minimum range of 6 hours';
    }
  }

  return errors;
};

const SuccessRateFilter = (props) => {
  const {
    endDate,
    startDate,
    preset,
    fetchSuccessRate,
    fetchMerchantErrors,
    updateDateRange,
    setDefaultInterval,
    activeTab,
    setActiveTab,
  } = props;

  const [dateRange, setDateRange] = useState({ startDate: '', endDate: '', preset: '' });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    setDateRange({ startDate, endDate, preset });
  }, [startDate, endDate, preset]);

  useEffect(() => {
    const errors = validateDateRange(dateRange);
    setErrors(errors);
  }, [dateRange]);

  const updateDropdownOptions = activeTab !== 'Overall';

  const onSearch = async () => {
    const { startDate, endDate } = dateRange;

    if (Object.keys(errors).length) {
      return;
    }

    updateDateRange(dateRange);
    setDefaultInterval(getBreakdownInterval(startDate, endDate));
    const refreshMetricTabs = activeTab !== 'Overall';
    if (refreshMetricTabs) {
      await fetchSuccessRate({
        payload: queryFilters(false, refreshMetricTabs),
        refreshMetricTabs,
        updateDropdownOptions: false,
      });
    }
    const payload = queryFilters(updateDropdownOptions);
    await fetchSuccessRate({ payload, updateDropdownOptions });
    const errorsPaylod = getMerchantErrorsPayload(updateDropdownOptions);
    fetchMerchantErrors(errorsPaylod);
    trackSuccessRateEvents(filterSuccessRate(payload));
  };

  const onReset = async () => {
    const initialValue = initialFilters();
    updateDateRange(initialValue);
    setActiveTab('Overall');
    setDefaultInterval(DEFAULT_INTERVAL);
    const payload = queryFilters(updateDropdownOptions);
    await fetchSuccessRate({ payload, updateDropdownOptions });
    const errorsPaylod = getMerchantErrorsPayload(updateDropdownOptions);
    await fetchMerchantErrors(errorsPaylod);
    trackSuccessRateEvents(clearFilterSuccessRate(payload));
  };

  return (
    <div className="sr-filter">
      <label>Date Range</label>

      <div className="datepicker-group">
        <DateRangePreset
          presets={PRESETS}
          dateRange={dateRange}
          setDateRange={setDateRange}
          setErrors={setErrors}
        />

        <div className="filter__actions">
          <AsyncButton
            className="btn btn-primary btn-sm"
            onClick={onSearch}
            disabled={Object.keys(errors).length > 0}
            text="Search"
          />
          <AsyncButton className="btn btn-sm btn-text" onClick={onReset} text="Clear" />
        </div>
      </div>

      {errors.date && (
        <small class="error-text text-danger">
          <i className="i i-info-outline" />
          <i>{errors.date}</i>
        </small>
      )}
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { filters = {}, activeTab } = successRate;
  const { startDate, endDate, preset } = filters || {};

  return { startDate, endDate, preset, activeTab };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { updateDateRange, fetchSuccessRate, fetchMerchantErrors, setDefaultInterval, setActiveTab },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SuccessRateFilter);
